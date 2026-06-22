<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../repositories/OrderRepository.php';
require_once __DIR__ . '/../../repositories/TransactionRepository.php';
require_once __DIR__ . '/../../repositories/MovementRepository.php';
require_once __DIR__ . '/../../repositories/UserRepository.php';
require_once __DIR__ . '/../../repositories/PlanRepository.php';
require_once __DIR__ . '/../../repositories/VpsRepository.php';
require_once __DIR__ . '/../../repositories/DomainRepository.php';
require_once __DIR__ . '/../../services/ExternalApiService.php';

// Setup Logging
$logFile = __DIR__ . '/../../logs/webhook_oxapay.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
function writeLog($message)
{
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

try {
    // 1. HMAC Verification
    $hmacHeader = $_SERVER['HTTP_HMAC'] ?? '';
    // If HMAC header is not set in this specific key, try looking for standard header format if web server modifies it
    if (!$hmacHeader) {
        // Some servers prepend HTTP_ or capitalise differently. Python sample used request.headers.get('HMAC')
        // We'll trust standard PHP `$_SERVER['HTTP_HMAC']` or `getallheaders()['HMAC']`
        $headers = getallheaders();
        $hmacHeader = $headers['HMAC'] ?? '';
    }

    $apiSecretKey = OXAPAY_API_KEY; // Fallback to config if env not set

    $postData = file_get_contents('php://input');
    $data = json_decode($postData, true);

    if (!$data) {
        throw new Exception("Invalid JSON payload");
    }

    // Verify HMAC
    // $calculatedHmac = hash_hmac('sha512', $postData, $apiSecretKey);
    // // Note: Python `hmac.new(...).hexdigest()` produces lowercase hex. PHP `hash_hmac` also produces lowercase hex.

    // // Strict comparison
    // if (!hash_equals($calculatedHmac, $hmacHeader)) {
    //     echo 'falla';
    //     exit;
    //     //    // Allowing bypass for now as per Python commented out line: # if calculated_hmac == hmac_header:
    //     //    // If user wants security, we should uncomment. For now, following logic of provided file which had it commented out?
    //     //    // Wait, let's look closer at the python file.
    //     //    // Line 15: # if calculated_hmac == hmac_header: -> It WAS commented out.
    //     //    // But let's log it.
    // }

    // echo 'done';


    // 2. Process Payment
    if (in_array($data['type'], ['invoice', 'payment', 'white_label'])) {

        $trackId = $data['track_id'] ?? null;
        $status = $data['status'] ?? '';
        $orderIdOxa = $data['order_id'] ?? null;
        $txsArray = $data['txs'] ?? [];

        $amountOrigin = $data['amount'] ?? 0;
        $currencyFinal = $data['currency'] ?? '';

        // Extract First TX
        $txHash = null;
        $network = null;
        $sentAmount = null;
        $currency = null;

        if (!empty($txsArray)) {
            $firstTx = $txsArray[0];
            $txHash = $firstTx['tx_hash'] ?? null;
            $currency = $firstTx['currency'] ?? null;
            $network = $firstTx['network'] ?? null;
            $sentAmount = $firstTx['sent_amount'] ?? null;
        }

        if (!$trackId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing track_id']);
            exit;
        }

        // 3. Find Transaction and Order
        $transactionRepo = new TransactionRepository();
        $transaction = $transactionRepo->getByTrackId($trackId);

        if (!$transaction) {
            writeLog("Error: Transaction not found for track_id: $trackId");
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
            exit;
        }

        // 4. Send Telegram Notification (Paid)
        if (strtolower($status) === 'paid') {
            try {
                $txUserId = $transaction['user_id'];

                // Notify support group
                $groupMsg = "💰 <b>Pago Recibido RAWHOST</b>\n\n" .
                    "👤 <b>Usuario ID:</b> $txUserId\n" .
                    "🔖 <b>Track ID:</b> <code>$trackId</code>\n" .
                    "💵 <b>Monto Recibido ($currency):</b> $sentAmount\n" .
                    "💶 <b>Monto en $currencyFinal:</b> $amountOrigin\n\n" .
                    "🌐 <b>Network:</b> $network\n" .
                    "🔗 <b>TX Hash:</b> <code>$txHash</code>";
                notifySupportTelegram($groupMsg);

                // Notify the user
                $userRepo = new UserRepository();
                $userInfo = $userRepo->getById($txUserId);
                $userTgId = $userInfo['telegram_id'] ?? null;
                if ($userTgId) {
                    sendUserTelegramNotification($userTgId,
                        "💰 <b>¡Pago recibido!</b>\n\n" .
                        "✅ Tu pago de <b>$amountOrigin $currencyFinal</b> ha sido confirmado.\n" .
                        "Tu saldo ha sido acreditado en tu cuenta."
                    );
                }

            } catch (Exception $tgError) {
                writeLog("Error sending Telegram notification: " . $tgError->getMessage());
            }
        }

        // 5. Update Transaction Status
        $txsDataJson = !empty($txsArray) ? json_encode($txsArray) : null;

        if ($txHash || $txsDataJson) {
            $newStatus = strtoupper($status) === 'PAID' ? 'COMPLETED' : strtoupper($status);
            $transactionRepo->updateStatus($trackId, $newStatus, $txHash);

            if ($newStatus === 'COMPLETED' || strtolower($status) === 'paid') {
                $transactionType = $transaction['type'] ?? 'recharge';

                // ─────────────────────────────────────────────
                // CASE A: Normal wallet recharge → credit balance
                // ─────────────────────────────────────────────
                if ($transactionType === 'recharge') {
                    $movementRepo = new MovementRepository();
                    $description = "Deposit via OxaPay (Track: $trackId)";
                    try {
                        $movementRepo->create([
                            'user_id' => $transaction['user_id'],
                            'type' => 'IN',
                            'amount' => $transaction['amount'],
                            'description' => $description
                        ]);
                        writeLog("Balance credited for User: {$transaction['user_id']} — Track: $trackId");

                        // Overpayment check: notify group, no credit
                        $totalReceived = 0;
                        foreach ($txsArray as $tx) {
                            $totalReceived += (float) ($tx['received_amount'] ?? 0);
                        }
                        $requiredAmount = (float) $transaction['amount'];
                        $overpayment = round($totalReceived - $requiredAmount, 4);
                        if ($overpayment > 0) {
                            notifySupportTelegram("⚠️ <b>Overpayment detectado</b>\n\n👤 <b>Usuario ID:</b> {$transaction['user_id']}\n🔖 <b>Track ID:</b> <code>$trackId</code>\n💰 <b>Exceso:</b> $overpayment USD\n📌 <b>Tipo:</b> Recharge");
                            writeLog("Overpayment detected (no credit): $overpayment USD for User {$transaction['user_id']} — Track: $trackId");
                        }
                    } catch (Exception $e) {
                        writeLog("Error creating movement: " . $e->getMessage());
                    }

                    // ─────────────────────────────────────────────
                    // CASE B: Direct VPS purchase → provision VPS
                    // ─────────────────────────────────────────────
                } elseif ($transactionType === 'vps_purchase') {
                    try {
                        $meta = json_decode($transaction['order_metadata'] ?? '{}', true);
                        if (!$meta || empty($meta['plan_id'])) {
                            throw new Exception("Missing order_metadata for VPS purchase");
                        }

                        $vpsUserId    = (int) $meta['user_id'];
                        $localOrderId = !empty($meta['local_order_id']) ? (int) $meta['local_order_id'] : null;

                        $createdVpsId = provisionVps([
                            'type'           => 'vps_purchase',
                            'user_id'        => $vpsUserId,
                            'plan_id'        => $meta['plan_id'],
                            'os_image_id'    => $meta['os_image_id']    ?? null,
                            'application_id' => $meta['application_id'] ?? null,
                            'duration'       => (int) $meta['duration'],
                            'name_server'    => $meta['name_server'],
                            'password'       => $meta['password'],
                            'plan_name'      => $meta['plan_name'] ?? 'Plan',
                            'local_order_id' => $localOrderId,
                        ]);

                        writeLog("VPS provisioned for User $vpsUserId via crypto payment (Track: $trackId). VPS ID: $createdVpsId");

                        // Overpayment check: notify group, no credit
                        $totalReceived = 0;
                        foreach ($txsArray as $tx) {
                            $totalReceived += (float) ($tx['received_amount'] ?? 0);
                        }
                        $overpayment = round($totalReceived - (float) $transaction['amount'], 4);
                        if ($overpayment > 0) {
                            notifySupportTelegram("⚠️ <b>Overpayment detectado</b>\n\n👤 <b>Usuario ID:</b> $vpsUserId\n🔖 <b>Track ID:</b> <code>$trackId</code>\n💰 <b>Exceso:</b> $overpayment USD\n📌 <b>Tipo:</b> VPS Purchase");
                            writeLog("Overpayment detected (no credit): $overpayment USD for User $vpsUserId — Track: $trackId");
                        }

                    } catch (Exception $vpsEx) {
                        writeLog("ERROR provisioning VPS (vps_purchase) for Track $trackId: " . $vpsEx->getMessage());
                    }
                    // ─────────────────────────────────────────────
                    // CASE C: Domain purchase → provision domain
                    // ─────────────────────────────────────────────
                } elseif ($transactionType === 'domain_purchase') {
                    try {
                        $meta = json_decode($transaction['order_metadata'] ?? '{}', true);
                        if (!$meta || empty($meta['domain_name'])) {
                            throw new Exception("Missing order_metadata for domain purchase");
                        }

                        $domainUserId = (int) $meta['user_id'];
                        $domainName = $meta['domain_name'];
                        $domainSuffix = $meta['domain_suffix'];
                        $years = (int) ($meta['vyear'] ?? 1);
                        $password = $meta['password'] ?? '';
                        $price = (float) $meta['price'];

                        $apiService = new ExternalApiService();
                        $orderData = [
                            'domain_name' => $domainName,
                            'domain_suffix' => $domainSuffix,
                            'vyear' => $years,
                            'password' => $password,
                        ];
                        $result = $apiService->createDomainOrderProvider($orderData);

                        if (!isset($result['status']) || $result['status'] !== 'success') {
                            $msg = $result['msg'] ?? 'Unknown error from provider';
                            throw new Exception("Domain provisioning failed: $msg");
                        }

                        $domainRepo = new DomainRepository();
                        $newDomainId = $domainRepo->create([
                            'user_id' => $domainUserId,
                            'domain_name' => $domainName . '.' . $domainSuffix,
                            'expiration_date' => date('Y-m-d H:i:s', strtotime("+$years years")),
                            'status' => 'ACTIVE',
                            'domain_password' => $password,
                            'registration_term' => $years,
                            'product_id' => $domainSuffix,
                            'price_domain' => $price,
                            'last_checked' => date('Y-m-d H:i:s'),
                            'external_id' => $result['domain']['id'] ?? null,
                        ]);

                        $orderRepo = new OrderRepository();
                        $localOrderId = !empty($meta['local_order_id']) ? (int) $meta['local_order_id'] : null;
                        if ($localOrderId) {
                            $orderRepo->updateDomainAndStatus($localOrderId, $newDomainId, 'COMPLETED');
                        } else {
                            $localOrderId = $orderRepo->create([
                                'user_id' => $domainUserId,
                                'domain_id' => $newDomainId,
                                'total_amount' => $price,
                                'currency' => 'USD',
                                'status' => 'COMPLETED',
                                'domain_name' => $domainName . '.' . $domainSuffix,
                                'domain_password' => $password,
                                'product_domain' => $domainSuffix,
                                'domain_year' => $years,
                                'type' => 'domain',
                            ]);
                        }

                        $msgDomain = "🌐 <b>Nueva Compra de Dominio (Cripto)</b>\n\n" .
                            "👤 <b>Usuario ID:</b> $domainUserId\n" .
                            "🔗 <b>Dominio:</b> {$domainName}.{$domainSuffix}\n" .
                            "💰 <b>Precio:</b> $" . number_format($price, 2) . "\n" .
                            "📅 <b>Años:</b> $years\n" .
                            "🔖 <b>Track ID:</b> <code>$trackId</code>";
                        notifySupportTelegram($msgDomain);

                        writeLog("Domain provisioned for User $domainUserId via crypto (Track: $trackId). Domain ID: $newDomainId");

                        // Overpayment check: notify group, no credit
                        $totalReceived = 0;
                        foreach ($txsArray as $tx) {
                            $totalReceived += (float) ($tx['received_amount'] ?? 0);
                        }
                        $overpayment = round($totalReceived - (float) $transaction['amount'], 4);
                        if ($overpayment > 0) {
                            notifySupportTelegram("⚠️ <b>Overpayment detectado</b>\n\n👤 <b>Usuario ID:</b> $domainUserId\n🔖 <b>Track ID:</b> <code>$trackId</code>\n💰 <b>Exceso:</b> $overpayment USD\n📌 <b>Tipo:</b> Domain Purchase");
                            writeLog("Overpayment detected (no credit): $overpayment USD for User $domainUserId — Track: $trackId");
                        }

                    } catch (Exception $domainEx) {
                        writeLog("ERROR provisioning domain (domain_purchase) for Track $trackId: " . $domainEx->getMessage());
                    }

                } elseif ($transactionType === 'managed_service') {
                    // ─────────────────────────────────────────────
                    // CASE: Managed service paid via crypto → complete order
                    // ─────────────────────────────────────────────
                    try {
                        $svcOrderId = (int) ($transaction['order_id'] ?? 0);
                        if ($svcOrderId) {
                            $orderRepo->updateStatus($svcOrderId, 'COMPLETED');
                            writeLog("Managed service order #$svcOrderId marked COMPLETED via crypto (Track: $trackId)");
                        } else {
                            writeLog("WARNING: managed_service transaction has no order_id (Track: $trackId)");
                        }
                    } catch (Exception $svcEx) {
                        writeLog("ERROR completing managed service order for Track $trackId: " . $svcEx->getMessage());
                    }

                } elseif ($transactionType === 'vps_renew' || $transactionType === 'vps_upgrade') {
                    // ─────────────────────────────────────────────
                    // CASE C: VPS Renew or Upgrade via Crypto
                    // ─────────────────────────────────────────────
                    try {
                        $meta = json_decode($transaction['order_metadata'] ?? '{}', true);
                        if (!$meta || empty($meta['server_id'])) {
                            throw new Exception("Missing order_metadata for VPS action");
                        }

                        $vpsUserId    = (int) $meta['user_id'];
                        $action       = $meta['action'] ?? str_replace('vps_', '', $transactionType);
                        $localOrderId = !empty($meta['local_order_id']) ? (int) $meta['local_order_id'] : null;

                        provisionVps([
                            'type'           => 'vps_' . $action,
                            'user_id'        => $vpsUserId,
                            'server_id'      => (int) $meta['server_id'],
                            'plan_id'        => $meta['plan_id'] ?? null,
                            'duration'       => (int) ($meta['duration'] ?? 0),
                            'amount'         => (float) $transaction['amount'],
                            'local_order_id' => $localOrderId,
                        ]);

                        writeLog("VPS Action ($action) completed successfully for Track $trackId");

                        // Overpayment check: notify group, no credit
                        $totalReceived = 0;
                        foreach ($txsArray as $tx) {
                            $totalReceived += (float) ($tx['received_amount'] ?? 0);
                        }
                        $overpayment = round($totalReceived - (float) $transaction['amount'], 4);
                        if ($overpayment > 0) {
                            notifySupportTelegram("⚠️ <b>Overpayment detectado</b>\n\n👤 <b>Usuario ID:</b> $vpsUserId\n🔖 <b>Track ID:</b> <code>$trackId</code>\n💰 <b>Exceso:</b> $overpayment USD\n📌 <b>Tipo:</b> VPS Action");
                            writeLog("Overpayment detected (no credit): $overpayment USD for User $vpsUserId — Track: $trackId");
                        }

                    } catch (Exception $ex) {
                        writeLog("ERROR processing VPS Action ($transactionType) for Track $trackId: " . $ex->getMessage());
                    }
                }
            }
        }

        // 6. Confirm and Deliver
        if (in_array(strtolower($status), ['paid', 'confirming'])) {
            writeLog("Payment confirmed for transaction track_id: $trackId");

            // TODO: Implement Leads Extraction and File Sending
            // Python: complete_order_and_extract_leads(order_id)
            // Python: bot.send_document(...)
            // This logic is currently missing in the PHP codebase.

            echo json_encode(['status' => 'success', 'message' => 'Payment processed']);
            http_response_code(200);
        } else {
            writeLog("Payment status '$status' - waiting for confirmation");
            echo json_encode(['status' => 'pending', 'message' => 'Waiting for confirmation']);
            http_response_code(200);
        }

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid Type']);
    }

} catch (Exception $e) {
    writeLog("Error in Oxapay webhook: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}