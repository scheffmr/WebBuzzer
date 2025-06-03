<?php
require_once 'db.php';

if (!isset($_POST['room_id'], $_POST['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'room_id und user_id erforderlich.']);
    exit;
}

$roomId = (int) $_POST['room_id'];
$userId = (int) $_POST['user_id'];

try {
    $pdo->beginTransaction();

    // Sperre auf den Buzzer-Status setzen
    $stmt = $pdo->prepare("SELECT locked FROM buzz_status WHERE room_id = ? FOR UPDATE");
$stmt->execute([$roomId]);
$status = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$status) {
    // Initialisiere den Status, falls er noch nicht existiert
    $stmt = $pdo->prepare("INSERT INTO buzz_status (room_id, locked, buzzer_user_id, buzz_time)
                           VALUES (?, 1, ?, NOW())");
    $stmt->execute([$roomId, $userId]);
    $pdo->commit();
    echo json_encode(['success' => true, 'locked' => true]);
    exit;
}

if ((int)$status['locked'] === 1) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Buzzer ist bereits gesperrt.']);
    exit;
}

    // Buzzer sperren durch aktuellen Benutzer
    $stmt = $pdo->prepare("UPDATE buzz_status SET locked = 1, buzzer_user_id = ?, buzz_time = NOW() WHERE room_id = ?");
    $stmt->execute([$userId, $roomId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'locked' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>