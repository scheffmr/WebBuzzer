<?php
require 'db.php';

$room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
if (!$room_id) {
    echo json_encode(['error' => 'Missing room_id']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT bs.locked, bs.buzzer_user_id, bs.buzz_time, u.name as buzzer_user_name
    FROM buzz_status bs
    LEFT JOIN users u ON bs.buzzer_user_id = u.id
    WHERE bs.room_id = ?
");
$stmt->execute([$room_id]);
$status = $stmt->fetch(PDO::FETCH_ASSOC);

if ($status) {
    echo json_encode([
        'locked' => (int)$status['locked'],
        'buzzer_user_id' => $status['buzzer_user_id'] ? (int)$status['buzzer_user_id'] : null,
        'buzz_time' => $status['buzz_time'],
        'buzzer_user_name' => $status['buzzer_user_name'] ?? null
    ]);
} else {
    // Falls kein Status gefunden wurde, z.B. Raum ohne Status
    echo json_encode([
        'locked' => 0,
        'buzzer_user_id' => null,
        'buzz_time' => null,
        'buzzer_user_name' => null
    ]);
}
