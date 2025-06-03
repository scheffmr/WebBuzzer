<?php
require 'db.php';

$room_code = trim($_POST['room_code'] ?? '');
$name = trim($_POST['name'] ?? '');

if (!$room_code || !$name) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
    exit;
}

// Raum anhand von room_code finden (Spalte heißt in der DB "code")
$stmt = $pdo->prepare("SELECT id FROM rooms WHERE code = ?");
$stmt->execute([$room_code]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
    exit;
}

$room_id = $room['id'];

// User einfügen
$stmt = $pdo->prepare("INSERT INTO users (room_id, name) VALUES (?, ?)");
$stmt->execute([$room_id, $name]);
$user_id = $pdo->lastInsertId();

echo json_encode(['success' => true, 'user_id' => $user_id]);
?>