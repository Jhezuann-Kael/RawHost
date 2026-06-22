<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../repositories/TicketRepository.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $repo  = new TicketRepository();
    $count = $repo->countTotalUnreadForUser((int) $_SESSION['user_id']);
    echo json_encode(['count' => $count]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
