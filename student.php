<?php
// ---------- DATABASE CONFIG ----------
$host = 'sql.freedb.tech';
$port = 3306;
$dbname = 'freedb_PROJECT';
$user = 'freedb_ADITYA';
$pass = 'FDA74dD3aTMxk#*';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $digit   = (int)($_POST['digit'] ?? 0);
    $subject = strtoupper(trim($_POST['subject'] ?? ''));
    $room    = strtoupper(trim($_POST['room'] ?? ''));
    $roll    = trim($_POST['roll_no'] ?? '');

    // ---- Basic validation ----
    if ($digit < 0 || $digit > 9) {
        $message = "<p style='color:#f87171'>Digit must be 0–9.</p>";
    } elseif (empty($subject) || empty($room) || empty($roll)) {
        $message = "<p style='color:#f87171'>All fields are required.</p>";
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // ---- 1. Look for matching entry in faculty_validation ----
            $sqlSel = "SELECT id FROM faculty_validation 
                       WHERE digit = ? AND subject_code = ? AND room_no = ? AND roll_no = ?";
            $stmtSel = $pdo->prepare($sqlSel);
            $stmtSel->execute([$digit, $subject, $room, $roll]);
            $found = $stmtSel->fetch(PDO::FETCH_ASSOC);

            if (!$found) {
                $message = "<p style='color:#f87171'>No matching record found in faculty validation.</p>";
            } else {
                $pdo->beginTransaction();

                // ---- 2. Delete from faculty_validation ----
                $sqlDel = "DELETE FROM faculty_validation 
                           WHERE id = ?";
                $stmtDel = $pdo->prepare($sqlDel);
                $stmtDel->execute([$found['id']]);

                // ---- 3. Insert / Update student_attendance ----
                $today = date('Y-m-d');   // CURRENT_DATE

                $sqlUpsert = "
                    INSERT INTO student_attendance (subject_code, attendance_date, roll_no, attendance_count)
                    VALUES (:subj, :adate, :roll, 1)
                    ON DUPLICATE KEY UPDATE
                        attendance_count = attendance_count + 1;
                ";
                $stmtUpsert = $pdo->prepare($sqlUpsert);
                $stmtUpsert->execute([
                    ':subj' => $subject,
                    ':adate'=> $today,
                    ':roll' => $roll
                ]);

                $pdo->commit();

                $message = "<p style='color:#34d399'>
                    Attendance marked! <br>
                    <strong>Subject:</strong> $subject |
                    <strong>Room:</strong> $room |
                    <strong>Roll:</strong> $roll
                </p>";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<p style='color:#f87171'>Error: " .
                       htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance</title>
    <style>
        body{background:#0f172a;color:#f1f5f9;font-family:system-ui,sans-serif;
             display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px;}
        .box{background:#1e293b;padding:32px;border-radius:24px;max-width:460px;width:100%;
             box-shadow:0 10px 30px rgba(0,0,0,.3);}
        h1{font-size:1.8rem;text-align:center;margin-bottom:24px;color:#60a5fa;}
        label{display:block;margin:8px 0 4px;color:#cbd5e1;font-weight:500;}
        input, .digit-grid div{
            width:100%;padding:14px 16px;margin-bottom:12px;border:2.5px solid rgba(255,255,255,.2);
            border-radius:16px;background:rgba(255,255,255,.1);color:#fff;font-size:1.1rem;
        }
        input:focus{outline:none;border-color:#60a5fa;background:rgba(255,255,255,.15);}
        .digit-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin:12px 0;}
        .digit-btn{background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.2);
                   border-radius:14px;text-align:center;cursor:pointer;font-weight:700;
                   transition:.2s;}
        .digit-btn:hover{background:rgba(255,255,255,.18);border-color:#60a5fa;}
        .digit-btn.selected{background:#3b82f6;color:#fff;border-color:#3b82f6;transform:scale(.95);}
        button{background:#3b82f6;color:#fff;border:none;padding:16px;border-radius:16px;
               font-weight:700;width:100%;margin-top:12px;cursor:pointer;transition:.3s;}
        button:hover{background:#2563eb;transform:translateY(-2px);}
        .msg{margin-top:16px;padding:12px;border-radius:12px;text-align:center;font-weight:600;}
    </style>
</head>
<body>
<div class="box">
    <h1>Mark Attendance</h1>

    <?php if ($message): ?><div class="msg"><?php echo $message; ?></div><?php endif; ?>

    <form method="POST">
        <label>Digit (0-9)</label>
        <div class="digit-grid">
            <?php for ($i=0;$i<=9;$i++):?>
                <div class="digit-btn" onclick="pick(<?=$i?>)"><?=$i?></div>
            <?php endfor;?>
        </div>
        <input type="hidden" name="digit" id="digit" required>

        <label>Subject Code</label>
        <input type="text" name="subject" placeholder="CS101" maxlength="20" required>

        <label>Room No</label>
        <input type="text" name="room" placeholder="A-101" maxlength="20" required>

        <label>Roll No</label>
        <input type="text" name="roll_no" placeholder="101" required>

        <button type="submit">MARK ATTENDANCE</button>
    </form>
</div>

<script>
function pick(d){
    document.querySelectorAll('.digit-btn').forEach(b=>b.classList.remove('selected'));
    event.target.classList.add('selected');
    document.getElementById('digit').value = d;
}
</script>
</body>
</html>