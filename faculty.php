<?php
// === PHP: Handle Save Request ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json');

    $subject = trim($_POST['subject'] ?? '');
    $room    = trim($_POST['room'] ?? '');
    $data    = json_decode($_POST['data'] ?? '[]', true);

    if (empty($subject) || empty($room) || !is_array($data) || empty($data)) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }

    $host = 'sql.freedb.tech';
    $port = 3306;
    $dbname = 'freedb_PROJECT';
    $user = 'freedb_ADITYA';
    $pass = 'FDA74dD3aTMxk#*';

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Table uses roll_no (instead of enrollment_no)
        $pdo->exec("CREATE TABLE IF NOT EXISTS faculty_validation (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject_code VARCHAR(20) NOT NULL,
            room_no VARCHAR(20) NOT NULL,
            roll_no INT NOT NULL,
            digit INT NOT NULL CHECK (digit >= 0 AND digit <= 9),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_subject_room (subject_code, room_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $stmt = $pdo->prepare("INSERT INTO faculty_validation (subject_code, room_no, roll_no, digit) VALUES (?, ?, ?, ?)");
        $inserted = 0;
        foreach ($data as $row) {
            $stmt->execute([$subject, $room, $row['roll_no'], $row['digit']]);
            $inserted++;
        }

        echo json_encode(['success' => true, 'inserted' => $inserted]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FACULTY ATTENDANCE PORTAL</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      height: 100vh; width: 100vw;
      background: #0a0a0a; color: white;
      font-family: 'Segoe UI', Arial, sans-serif;
      display: flex; flex-direction: column; align-items: center;
      overflow: hidden;
    }

    .info-box {
      margin: 30px 0 10px; text-align: center;
      color: #0ff; text-shadow: 0 0 12px #0ff; font-weight: bold;
    }
    .subject { font-size: 50px; }
    .room { font-size: 40px; margin-top: 8px; }

    .container {
      width: 70%; max-width: 900px; padding: 30px;
      background: #1a1a1a; border-radius: 15px;
      box-shadow: 0 0 30px rgba(0,255,255,0.4);
      border: 1px solid #0ff;
    }
    #title { font-size: 150px; margin: 0; color: #fff; }
    .circle {
      width: 400px; height: 400px; margin: 20px auto;
      border: 8px solid #0ff; border-radius: 50%;
      display: flex; justify-content: center; align-items: center;
      font-size: 300px; font-weight: bold;
      color: #0ff; text-shadow: 0 0 20px #0ff;
    }

    #inputModal {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.97); display: flex;
      justify-content: center; align-items: center; z-index: 999;
    }
    .modal-content {
      background: #1a1a1a; padding: 40px; border-radius: 20px;
      width: 400px; text-align: center; border: 2px solid #0ff;
      box-shadow: 0 0 30px #0ff;
    }
    .modal-content h2 { color: #0ff; margin-bottom: 20px; }
    input {
      width: 100%; padding: 14px; margin: 12px 0;
      font-size: 18px; border: 2px solid #0ff; border-radius: 10px;
      background: #111; color: white;
    }
    button {
      background: #0ff; color: #111; border: none;
      padding: 14px 40px; font-size: 22px; font-weight: bold;
      border-radius: 10px; cursor: pointer; margin-top: 20px;
      box-shadow: 0 0 15px #0ff; transition: 0.3s;
    }
    button:hover { background: #0cc; transform: scale(1.05); }

    .hidden { display: none !important; }
    #status { font-size: 24px; margin: 20px; }
    .success { color: #0f0; } .error { color: #f00; }
  </style>
</head>
<body>

  <div class="info-box hidden" id="infoBox">
    <div class="subject" id="subjectLine"></div>
    <div class="room" id="roomLine"></div>
  </div>

  <div id="status" class="hidden"></div>

  <div id="inputModal">
    <div class="modal-content">
      <h2>Enter Details</h2>
      <input type="text" id="subjectCode" placeholder="Subject Code" maxlength="10" required />
      <input type="text" id="roomNo" placeholder="Room No." maxlength="10" required />
      <button onclick="startRolling()">START ROLL</button>
    </div>
  </div>

  <div class="container hidden" id="rollContainer">
    <div id="title">ROLL : 1</div>
    <div class="circle" id="digit">–</div>
  </div>

  <script>
    let roll = 1;
    const total = 12;               // Change to 10 for testing
    let subjectCode = "", roomNo = "", rollData = [];

    function showStatus(msg, isError = false) {
      const el = document.getElementById("status");
      el.innerText = msg;
      el.className = isError ? "error" : "success";
      el.classList.remove("hidden");
      setTimeout(() => el.classList.add("hidden"), 5000);
    }

    function startRolling() {
      subjectCode = document.getElementById("subjectCode").value.trim().toUpperCase();
      roomNo      = document.getElementById("roomNo").value.trim();
      if (!subjectCode || !roomNo) return alert("Both fields required!");

      document.getElementById("inputModal").classList.add("hidden");
      document.getElementById("subjectLine").innerText = "SUBJECT CODE : " + subjectCode;
      document.getElementById("roomLine").innerText    = "ROOM NO. : " + roomNo;
      document.getElementById("infoBox").classList.remove("hidden");
      document.getElementById("rollContainer").classList.remove("hidden");

      rollData = [];
      startRoll();
    }

    function startRoll() {
      if (roll > total) {
        document.getElementById("title").innerText = "ALL DONE!";
        document.getElementById("digit").innerText = "";
        saveToDatabase();
        return;
      }
      const digit = Math.floor(Math.random() * 10);
      document.getElementById("title").innerText = "ROLL : " + roll;
      document.getElementById("digit").innerText = digit;

      // Store as roll_no (instead of enrollment_no)
      rollData.push({ roll_no: roll, digit: digit });

      roll++;
      setTimeout(startRoll, 2000);
    }

    async function saveToDatabase() {
      if (!rollData.length) return;

      const formData = new FormData();
      formData.append('action', 'save');
      formData.append('subject', subjectCode);
      formData.append('room', roomNo);
      formData.append('data', JSON.stringify(rollData));

      try {
        const response = await fetch('', {  // Same file
          method: 'POST',
          body: formData
        });
        const result = await response.json();

        if (result.success) {
          showStatus(`Saved ${result.inserted} records!`);
          alert(`Success! ${result.inserted} rolls saved.`);
        } else {
          throw new Error(result.error);
        }
      } catch (err) {
        showStatus("Save failed: " + err.message, true);
        alert("Error: " + err.message);
      }
    }
  </script>
</body>
</html>