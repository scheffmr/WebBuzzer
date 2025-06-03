let roomId = null;
let userId = null;
let userName = null;
let buzzerLocked = false;
let resetTimeout = null;
let pollingIntervalId = null;


// Elemente
const roomSection = document.getElementById('room-section');
const joinSection = document.getElementById('join-section');
const buzzerSection = document.getElementById('buzzer-section');

const roomInfo = document.getElementById('room-info');
const buzzerStatus = document.getElementById('buzzer-status');
const buzzerButton = document.getElementById('buzzer');
const winnerText = document.getElementById('winner');

const inputRoomCode = document.getElementById('room-code');
const inputUserName = document.getElementById('username');

// Event Listener bei Seitenaufruf
document.addEventListener('DOMContentLoaded', async () => {
  if (presetRoomCode && presetRoomCode.trim() !== "") {
    try {
      const res = await fetch('php/check_room.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `code=${encodeURIComponent(presetRoomCode)}`
      });
      const data = await res.json();
      if (data.exists) {
        roomId = data.room_id; // Room-ID wird hier gesetzt
        showJoinSection(presetRoomCode);
      } else {
        alert('Raum nicht gefunden.');
      }
    } catch (e) {
      alert('Netzwerkfehler beim Überprüfen des Raums.');
    }
  }
});

// Event Listener für Enter in Raumcode-Eingabefeld
inputRoomCode.addEventListener('keyup', (event) => {
  if (event.key === "Enter") {
    joinRoom();
  }
});

// Event Listener für Enter im Teamnamen-Eingabefeld
inputUserName.addEventListener('keyup', (event) => {
  if (event.key === "Enter") {
    enterRoom();
  }
});

// --- Raum erstellen ---
async function createRoom() {
  try {
    const res = await fetch('php/create_room.php', { method: 'POST' });
    const data = await res.json();
    if (data.success) {
      roomId = data.room_id;
      showJoinSection(data.room_code);
    } else {
      alert('Fehler beim Raum erstellen: ' + data.error);
    }
  } catch (e) {
    alert('Netzwerkfehler beim Raum erstellen.');
  }
}

// --- Raum beitreten (Raumcode prüfen) ---
async function joinRoom() {
  const code = inputRoomCode.value.trim();
  if (!code) {
    alert('Bitte Raumcode eingeben.');
    return;
  }
  try {
    const res = await fetch('php/check_room.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `code=${encodeURIComponent(code)}`
    });
    const data = await res.json();
    if (data.exists) {
      roomId = data.room_id;
      showJoinSection(code);
    } else {
      alert('Raum nicht gefunden.');
    }
  } catch (e) {
    alert('Netzwerkfehler beim Raum beitreten.');
  }
}

// --- Benutzername eingeben und User anlegen ---
let currentRoomCode = null;

function showJoinSection(code) {
  currentRoomCode = code;  // speichern
  roomSection.style.display = 'none';
  joinSection.style.display = 'block';
  buzzerSection.style.display = 'none';
  roomInfo.textContent = `Raum: ${code}`;
}

// --- Benutzername eingeben und User anlegen ---
async function enterRoom() {
  const name = inputUserName.value.trim();
  const roomCode = currentRoomCode;
  
  if (!name) {
    alert('Bitte Teamnamen eingeben.');
    return;
  }
  if (!roomCode) {
    alert('Raumcode fehlt.');
    return;
  }

  userName = name;

  try {
    const res = await fetch('php/add_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `room_code=${encodeURIComponent(roomCode)}&name=${encodeURIComponent(userName)}`
    });
    const data = await res.json();
    if (data.success) {
      userId = data.user_id;
      
      // Buzzer-Zustand mit force-Reset beim Betreten des Raumes anfragen.
      // Das Reset-Skript wird nur den Status zurücksetzen, wenn noch niemand gebuzzert hat.
      const resetRes = await fetch('php/reset_buzzer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${encodeURIComponent(roomId)}&force=1`
      });
      const resetData = await resetRes.json();
      
      // Wenn resetData.reset true ist, war der Buzz noch frei – dann zeigen wir "Bereit".
      // Andernfalls (reset=false) hat bereits jemand gebuzzert, also
      // überlassen wir dem Polling (fetchBuzzStatus), die Anzeige des aktuellen Status.
      if (resetData.reset) {
        showBuzzerSection(); // Zeigt "Bereit zum Buzzern" und entsperrt die UI im aktuellen Client.
      }
      
      // Starte das Polling, damit auch im aktuellen Client der aktuelle Status aktuell gehalten wird.
      startStatusPolling();
    } else {
      alert('Fehler beim Hinzufügen des Users: ' + data.error);
    }
  } catch (e) {
    alert('Netzwerkfehler beim Hinzufügen des Users.');
  }
}

// --- Anzeigen der Buzzer-Oberfläche ---
function showBuzzerSection() {
  roomSection.style.display = 'none';
  joinSection.style.display = 'none';
  buzzerSection.style.display = 'block';
  winnerText.textContent = '';
  buzzerStatus.textContent = 'Bereit zum Buzzern';
  
  // Setze den Buzzer auf "off" (rot) per CSS
  buzzerButton.classList.remove("buzzer-on");
  buzzerButton.classList.add("buzzer-off");
  buzzerButton.disabled = false;
}

// --- Buzzer drücken ---
async function buzz() {
  if (buzzerLocked) return;

  try {
    const res = await fetch('php/press_buzzer.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `room_id=${encodeURIComponent(roomId)}&user_id=${encodeURIComponent(userId)}`
    });
    const data = await res.json();
    // Wir erwarten nun, dass der Server "locked" zurückliefert
    if (data.success && data.locked) {
      updateBuzzerUI(userName);
      if (resetTimeout) clearTimeout(resetTimeout);
      resetTimeout = setTimeout(resetBuzzer, 10000);
    } else {
      alert('Der Buzzer ist gerade gesperrt.');
    }
  } catch (e) {
    alert('Fehler beim Buzzern.');
  }
}

// --- Buzzer zurücksetzen (freigeben) ---
async function resetBuzzer() {
  try {
    await fetch('php/reset_buzzer.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `room_id=${encodeURIComponent(roomId)}`
    });
    fetchBuzzStatus();
  } catch (e) {
    console.error('Fehler beim Zurücksetzen des Buzzers', e);
  }
}

// --- Status vom Server holen und UI updaten ---
async function fetchBuzzStatus() {
  if (!roomId) return;

  try {
    const res = await fetch('php/get_buzz_status.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `room_id=${encodeURIComponent(roomId)}`
    });
    const data = await res.json();
    if (data.locked) {
      updateBuzzerUI(data.buzzer_user_name);
    } else {
      resetBuzzerUI();
    }
  } catch (e) {
    console.error('Fehler beim Statusabfragen', e);
  }
}

// --- UI updaten, wenn gebuzzert wurde ---
function updateBuzzerUI(winnerName) {
  buzzerLocked = true;
  buzzerButton.classList.remove("buzzer-off");
  buzzerButton.classList.add("buzzer-on");
  buzzerButton.disabled = true;
  buzzerStatus.textContent = `Gebuzzert von: ${winnerName}`;
}

// --- UI zurücksetzen, wenn frei ---
function resetBuzzerUI() {
  buzzerLocked = false;
  buzzerButton.classList.remove("buzzer-on");
  buzzerButton.classList.add("buzzer-off");
  buzzerButton.disabled = false;
  buzzerStatus.textContent = 'Bereit zum Buzzern';
  winnerText.textContent = '';
}

// --- Statusabfrage alle 2 Sekunden starten ---
function startStatusPolling() {
  fetchBuzzStatus();
  pollingIntervalId = setInterval(fetchBuzzStatus, 1000);
}

function shareRoom() {
  // Ermittelt die Basis-URL (ohne Pfad)
  let baseUrl = window.location.origin;
  // currentRoomCode wird im Join-Prozess gesetzt (über UI oder direkten Aufruf)
  let shareUrl = '';
  
  // Falls die aktuelle URL bereits mit einem Room-Code endet, nutze diese.
  // Andernfalls füge den currentRoomCode als Pfad hinzu.
  if (window.location.href.match(/[A-Z0-9]{6}$/)) {
    shareUrl = window.location.href;
  } else if (currentRoomCode && currentRoomCode.trim() !== "") {
    shareUrl = baseUrl + "/" + currentRoomCode;
  } else {
    // Fallback: benutze einfach die aktuelle URL
    shareUrl = window.location.href;
  }
  
  // Der Nachrichtentext, der in die Zwischenablage kopiert wird:
  const message = `Besuche folgende Seite, um unseren Buzzer zu öffnen: ${shareUrl}`;
  
  navigator.clipboard.writeText(message)
    .then(() => {
      alert('Raum-Link wurde kopiert!');
    })
    .catch(err => {
      alert('Kopieren fehlgeschlagen: ' + err);
    });
}