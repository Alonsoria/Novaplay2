<?php
/**
 * NOVAPLAY — MAILER.PHP
 * Cliente SMTP nativo (sin dependencias externas).
 * Soporta STARTTLS (587/2525) y SSL directo (465).
 * Configurar las constantes MAIL_* en config.php.
 */

/**
 * Envía un correo de texto plano vía SMTP.
 */
function nova_send_mail(string $to, string $subject, string $body): bool
{
    $host = MAIL_HOST;
    $port = (int)MAIL_PORT;
    $user = MAIL_USER;
    $pass = MAIL_PASS;
    $from = MAIL_FROM;
    $name = MAIL_FROM_NAME;

    $ssl = ($port === 465);
    $addr = ($ssl ? 'ssl://' : '') . $host;

    $sock = @fsockopen($addr, $port, $errno, $errstr, 10);
    if (!$sock) {
        error_log("[Novaplay Mailer] Conexión fallida: $errstr ($errno)");
        return false;
    }
    stream_set_timeout($sock, 10);

    try {
        _smtp_expect($sock, 220);

        _smtp_send($sock, "EHLO localhost");
        $caps = _smtp_expect($sock, 250);

        /* STARTTLS en puertos que no son 465 */
        if (!$ssl && str_contains($caps, 'STARTTLS')) {
            _smtp_send($sock, "STARTTLS");
            _smtp_expect($sock, 220);
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            _smtp_send($sock, "EHLO localhost");
            _smtp_expect($sock, 250);
        }

        /* AUTH LOGIN */
        _smtp_send($sock, "AUTH LOGIN");
        _smtp_expect($sock, 334);
        _smtp_send($sock, base64_encode($user));
        _smtp_expect($sock, 334);
        _smtp_send($sock, base64_encode($pass));
        _smtp_expect($sock, 235);

        /* Enviar mensaje */
        _smtp_send($sock, "MAIL FROM:<$from>");
        _smtp_expect($sock, 250);
        _smtp_send($sock, "RCPT TO:<$to>");
        _smtp_expect($sock, 250);
        _smtp_send($sock, "DATA");
        _smtp_expect($sock, 354);

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFrom    = '=?UTF-8?B?' . base64_encode($name) . "?= <$from>";
        $msgId          = '<' . uniqid('nova', true) . '@novaplay.com.mx>';

        $msg  = "Date: " . date('r') . "\r\n";
        $msg .= "Message-ID: $msgId\r\n";
        $msg .= "From: $encodedFrom\r\n";
        $msg .= "To: <$to>\r\n";
        $msg .= "Subject: $encodedSubject\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: base64\r\n";
        $msg .= "\r\n";
        $msg .= chunk_split(base64_encode($body), 76, "\r\n");
        $msg .= "\r\n.";

        _smtp_send($sock, $msg);
        _smtp_expect($sock, 250);

        _smtp_send($sock, "QUIT");
        fclose($sock);
        return true;

    } catch (RuntimeException $e) {
        error_log('[Novaplay Mailer] ' . $e->getMessage());
        if (is_resource($sock)) fclose($sock);
        return false;
    }
}

/* ── Helpers internos ─────────────────────────────────────── */

function _smtp_send($sock, string $cmd): void
{
    fputs($sock, $cmd . "\r\n");
}

function _smtp_expect($sock, int $expected): string
{
    $response = '';
    while ($line = fgets($sock, 512)) {
        $response .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }
    $code = (int)substr($response, 0, 3);
    if ($code !== $expected) {
        throw new RuntimeException("SMTP esperaba $expected, recibió: " . trim($response));
    }
    return $response;
}

/**
 * Genera un código OTP alfanumérico de 6 caracteres sin ambigüedades
 * (excluye 0, O, 1, I, L para evitar confusiones visuales).
 */
function nova_gen_verification_code(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $len   = strlen($chars) - 1;
    $code  = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, $len)];
    }
    return $code;
}
