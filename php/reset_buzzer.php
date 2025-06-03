<?php
require_once 'db.php';

if (!isset($_POST['room_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'room_id erforderlich.']);
    exit;
}

$roomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$forceReset = isset($_POST['force']) && $_POST['force'] == 1;

try {
    // Wenn force gesetzt ist, wird der Buzzer nur freigegeben, wenn noch niemand gebuzzert hat.
    if ($forceReset) {
        // Prüfe, ob bereits gebuzzert wurde
        $stmt = $pdo->prepare("SELECT locked FROM buzz_status WHERE room_id = ?");
        $stmt->execute([$roomId]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Falls bereits gebuzzert wurde, führe keinen Reset durch.
        if ($status && $status['locked']) {
            echo json_encode(['success' => true, 'reset' => false]);
            exit;
        }
        
        // Falls noch nicht gesperrt, führe den Reset durch:
        $stmt = $pdo->prepare("UPDATE buzz_status SET locked = 0, buzzer_user_id = NULL, buzz_time = NULL WHERE room_id = ?");
        $stmt->execute([$roomId]);
        echo json_encode(['success' => true, 'reset' => true]);
        exit;
    }

    // Andernfalls: nur freigeben, wenn mehr als 10 Sekunden vergangen sind.
    $stmt = $pdo->prepare("SELECT locked, buzz_time FROM buzz_status WHERE room_id = ?");
    $stmt->execute([$roomId]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$status || !$status['locked']) {
        echo json_encode(['success' => true, 'reset' => false]);
        exit;
    }
  
    $buzzTime = strtotime($status['buzz_time']);
    $now = time();

    if ($now - $buzzTime >= 10) {
        $stmt = $pdo->prepare("UPDATE buzz_status SET locked = 0, buzzer_user_id = NULL, buzz_time = NULL WHERE room_id = ?");
        $stmt->execute([$roomId]);
        echo json_encode(['success' => true, 'reset' => true]);
    } else {
        echo json_encode(['success' => true, 'reset' => false]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>