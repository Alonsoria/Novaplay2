<?php
/**
 * NOVAPLAY — PAYPAL_HELPER.PHP
 * Funciones de bajo nivel para la API REST de PayPal.
 * No incluir directamente; usar desde crear_pago.php / capturar_pago.php.
 */

require_once __DIR__ . '/paypal_config.php';

/**
 * Obtiene un access-token Bearer de PayPal usando client_credentials.
 * @return string|false  Token o false en caso de error.
 */
function paypal_get_token(): string|false
{
    $ch = curl_init(PAYPAL_BASE_URL . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Accept-Language: en_US'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$body) return false;
    $data = json_decode($body, true);
    return $data['access_token'] ?? false;
}

/**
 * Crea una orden en PayPal y devuelve el objeto de respuesta.
 * @param  string $token    Access token
 * @param  float  $amount   Total a cobrar (MXN)
 * @param  string $currency Código de moneda (default: MXN)
 * @return array|false      Decoded JSON o false
 */
function paypal_create_order(string $token, float $amount, string $currency = 'MXN'): array|false
{
    $payload = json_encode([
        'intent'         => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'novaplay_' . time(),
            'description'  => 'Compra NovaPlay',
            'amount'       => [
                'currency_code' => $currency,
                'value'         => number_format($amount, 2, '.', ''),
            ],
        ]],
        'application_context' => [
            'brand_name'          => 'NovaPlay',
            'locale'              => 'es-MX',
            'landing_page'        => 'LOGIN',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action'         => 'PAY_NOW',
            'return_url'          => SITE_URL . '/capturar_pago.php',
            'cancel_url'          => SITE_URL . '/cancel.php',
        ],
    ]);

    $ch = curl_init(PAYPAL_BASE_URL . '/v2/checkout/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 201 || !$body) return false;
    return json_decode($body, true);
}

/**
 * Captura una orden aprobada por el usuario.
 * @param  string $token    Access token
 * @param  string $orderId  ID de la orden PayPal
 * @return array|false      Decoded JSON o false
 */
function paypal_capture_order(string $token, string $orderId): array|false
{
    $url = PAYPAL_BASE_URL . '/v2/checkout/orders/' . urlencode($orderId) . '/capture';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{}',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!in_array($code, [200, 201], true) || !$body) return false;
    return json_decode($body, true);
}
