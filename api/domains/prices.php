<?php
header('Content-Type: application/json');
require_once __DIR__ . '/domain_prices.php';

echo json_encode([
    'success' => true,
    'data' => [
        'register' => $domainPricesRegister,
        'renew'    => $domainPricesRenew,
        'default'  => DOMAIN_PRICE_DEFAULT,
    ],
]);
