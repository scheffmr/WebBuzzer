<?php
require 'db.php';

$code = $_POST['code'] ?? '';
if (!$code) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM rooms WHERE code = ?");
$stmt->execute([$code]);
$room = $stmt->fetch();

if ($room) {
    echo json_encode(['exists' => true, 'room_id' => (int)$room['id']]);
} else {
    echo json_encode(['exists' => false]);
}
