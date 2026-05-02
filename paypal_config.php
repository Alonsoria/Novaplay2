<?php
require 'vendor/autoload.php';

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

/* ── Constantes para paypal_helper.php (REST API v2 con cURL) ── */
define('PAYPAL_CLIENT_ID', 'AZJp8fzGVpSLY6dqtLeujdpK7HkFTFZxzlindd8Qvv3l7aFDDWP7Uw147fZmpzdijyZTts5Cr2NikjhN');
define('PAYPAL_SECRET',    'EJEFn4Oh9q7ewc_kDeLNrsStgmUh3zKHrpwz-jtrHSY9oKPTV8eAiwiZRYmqf4_qM1_-sOzkKKAc_Idt');
define('PAYPAL_BASE_URL',  'https://api-m.sandbox.paypal.com');
define('SITE_URL',         'http://localhost/novaplay2');

/* ── Contexto SDK (compatibilidad con carrito.php legacy) ── */
$paypal = new ApiContext(
    new OAuthTokenCredential(PAYPAL_CLIENT_ID, PAYPAL_SECRET)
);
$paypal->setConfig([
    'mode' => 'sandbox',
]);
?>