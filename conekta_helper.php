<?php
/**
 * NOVAPLAY — CONEKTA_HELPER.PHP
 * Wrapper para la API de Conekta v2.1 — Conekta Efectivo (pago en tienda).
 */

/**
 * Crea una orden de pago en efectivo (Conekta Efectivo) y devuelve los datos del cargo.
 *
 * @return array { order_id, referencia, barcode_url, redirect_url, expires_at }
 * @throws RuntimeException en error de red o respuesta HTTP ≥ 400
 */
function conekta_create_efectivo_order(
    float  $totalMXN,
    string $customerName,
    string $customerEmail,
    string $customerPhone,
    int    $pedidoId,
    int    $expiresAt
): array {
    if (!defined('CONEKTA_PRIVATE_KEY')) {
        throw new RuntimeException('CONEKTA_PRIVATE_KEY no está definida en config.php.');
    }

    $unitPriceCents = (int)round($totalMXN * 100);

    $phone = preg_replace('/\D/', '', $customerPhone);
    if (strlen($phone) < 10) $phone = '5500000000';
    $phone = substr($phone, 0, 10);

    $payload = json_encode([
        'currency'      => 'MXN',
        'customer_info' => [
            'name'  => $customerName ?: 'Cliente Novaplay',
            'email' => $customerEmail,
            'phone' => $phone,
        ],
        'line_items' => [
            [
                'name'       => "Compra Novaplay #{$pedidoId}",
                'unit_price' => $unitPriceCents,
                'quantity'   => 1,
            ],
        ],
        'charges' => [
            [
                'payment_method' => [
                    'type'       => 'cash',
                    'expires_at' => $expiresAt,
                ],
            ],
        ],
        'metadata' => [
            'novaplay_pedido_id' => (string)$pedidoId,
        ],
    ], JSON_UNESCAPED_UNICODE);

    $authHeader = 'Basic ' . base64_encode(CONEKTA_PRIVATE_KEY . ':');

    $ch = curl_init('https://api.conekta.io/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: '  . $authHeader,
            'Accept: application/vnd.conekta-v2.1.0+json',
            'Content-Type: application/json',
            'Accept-Language: es',
        ],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    error_log('[Conekta Efectivo] HTTP ' . $httpCode . ' | Pedido #' . $pedidoId . ' | ' . $response);

    if ($curlErr) {
        throw new RuntimeException('Error de red con Conekta: ' . $curlErr);
    }

    $data = json_decode($response, true);

    if ($httpCode >= 400 || !$data) {
        $msg = $data['details'][0]['message']
            ?? ($data['details'][0]['debug_message'] ?? null)
            ?? ($data['message'] ?? "HTTP {$httpCode}: " . substr($response, 0, 200));
        throw new RuntimeException($msg . ' [code:' . ($data['details'][0]['code'] ?? 'unknown') . ']');
    }

    /* ── Extraer redirect_url (nivel order — flujo Pago Directo) ── */
    $redirectUrl = $data['redirect_url']
        ?? $data['checkout']['redirect_url']
        ?? '';

    /* ── Extraer datos del cargo (referencia y/o barcode) ── */
    $chargesRaw = $data['charges'] ?? null;
    if (isset($chargesRaw['data']) && is_array($chargesRaw['data'])) {
        $charge = $chargesRaw['data'][0] ?? null;
    } elseif (is_array($chargesRaw)) {
        $charge = $chargesRaw[0] ?? null;
    } else {
        $charge = null;
    }

    $pm = $charge['payment_method'] ?? [];

    $referencia = $pm['reference']
        ?? $pm['barcode']
        ?? $pm['service_number']
        ?? '';

    $barcodeUrl = $pm['barcode_url']
        ?? $pm['image_url']
        ?? '';

    /* Fallback: si no hay referencia local pero hay redirect_url, es válido */
    if (!$referencia && !$redirectUrl) {
        throw new RuntimeException(
            'Conekta no devolvió referencia ni redirect_url. Respuesta: ' . substr($response, 0, 400)
        );
    }

    return [
        'order_id'     => $data['id'] ?? '',
        'referencia'   => $referencia,
        'barcode_url'  => $barcodeUrl,
        'redirect_url' => $redirectUrl,
        'expires_at'   => (int)($pm['expires_at'] ?? $expiresAt),
    ];
}
