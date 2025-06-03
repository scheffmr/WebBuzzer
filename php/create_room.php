<?php
require_once 'db.php';

// Funktion zum Erzeugen eines zufälligen Raumcodes
function generateRoomCode($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Keine ähnlichen Zeichen wie O/0 oder I/1
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

try {
    // Neuen Raumcode erzeugen und sicherstellen, dass er noch nicht existiert
    do {
        $roomCode = generateRoomCode();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE code = ?");
        $stmt->execute([$roomCode]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);

    // Raum in Datenbank einfügen
    $stmt = $pdo->prepare("INSERT INTO rooms (code) VALUES (?)");
    $stmt->execute([$roomCode]);
    $roomId = $pdo->lastInsertId();

    // Initialen Buzz-Status anlegen
    $stmt = $pdo->prepare("INSERT INTO buzz_status (room_id, locked, buzzer_user_id, buzz_time) VALUES (?, 0, NULL, NULL)");
    $stmt->execute([$roomId]);

    // Erfolgreiche Rückgabe
    echo json_encode([
        'success' => true,
        'room_code' => $roomCode,
        'room_id' => $roomId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>