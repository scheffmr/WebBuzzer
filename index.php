<?php
$roomPreset = isset($_GET['room']) ? htmlspecialchars($_GET['room'], ENT_QUOTES, 'UTF-8') : '';
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Buzzer</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div id="main">
    <h1>Quiz Buzzer</h1>
    <div id="room-section">
      <button onclick="createRoom()">Neuen Raum erstellen</button>
      <input type="text" id="room-code" placeholder="Raumcode eingeben">
      <button onclick="joinRoom()">Raum beitreten</button>
    </div>
    <div id="join-section" style="display:none">
      <input type="text" id="username" placeholder="Teamname">
      <button onclick="enterRoom()">Beitreten</button>
    </div>
    <div id="buzzer-section" style="display:none">
  <h2 id="room-info"></h2>
  <h3 id="buzzer-status">Bereit zum Buzzern</h3>
  <img id="buzzer" src="buzzer_off.jpg" onclick="buzz()">
  <p id="winner"></p>
  <!-- Button zum Teilen des Raum-Links -->
  <button id="share-room-btn" onclick="shareRoom()">Raum-Link kopieren</button>
</div>
  </div>
	<script>
  // Diesen Wert kannst Du in Deinem JS verwenden
  const presetRoomCode = "<?php echo $roomPreset; ?>";
</script>
  <script src="script.js"></script>

</body>
</html>
