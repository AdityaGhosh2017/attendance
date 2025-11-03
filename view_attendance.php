<?php
// view_attendance.php - View Attendance Records
require_once __DIR__ . '/.env.php';

$pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$results = [];
$search_performed = false;
$error = '';
$pre_roll = (int)($_GET['roll'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no = (int)($_POST['roll_no'] ?? 0);
    $subject = strtoupper(trim($_POST['subject'] ?? ''));

    if ($roll_no < 1 || $roll_no > 100 || empty($subject)) {
        $error = 'Invalid roll number or subject code.';
    } else {
        $search_performed = true;
        try {
            $stmt = $pdo->prepare("
                SELECT roll_no, subject_code, attendance_date, attendance_time 
                FROM attendance 
                WHERE roll_no = ? AND subject_code = ? 
                ORDER BY attendance_date DESC, attendance_time DESC
            ");
            $stmt->execute([$roll_no, $subject]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Error fetching attendance: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: #1e293b;
            padding: 32px;
            border-radius: 24px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 24px;
            color: #60a5fa;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 0.95rem;
            color: #cbd5e1;
            margin-bottom: 8px;
            font-weight: 500;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 14px 16px;
            font-size: 1.1rem;
            border: 2.5px solid rgba(255,255,255,0.2);
            border-radius: 16px;
            background: rgba(255,255,255,0.1);
            color: white;
        }
        input:focus {
            outline: none;
            border-color: #60a5fa;
        }
        button[type="submit"] {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 16px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }
        button[type="submit"]:hover {
            background: #2563eb;
        }
        .results {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        th {
            background: rgba(255,255,255,0.05);
            color: #60a5fa;
        }
        .no-results {
            text-align: center;
            color: #94a3b8;
            padding: 20px;
        }
        .error {
            color: #f87171;
            text-align: center;
            margin: 20px 0;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #60a5fa;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>VIEW ATTENDANCE</h1>
    <form method="POST">
        <div class="form-group">
            <label>Roll Number (1-100)</label>
            <input type="number" name="roll_no" min="1" max="100" placeholder="e.g., 45" value="<?= $pre_roll > 0 ? $pre_roll : '' ?>" required>
        </div>
        <div class="form-group">
            <label>Subject Code</label>
            <input type="text" name="subject" placeholder="e.g., CS101" required>
        </div>
        <button type="submit">Search Attendance</button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($search_performed): ?>
        <div class="results">
            <?php if (!empty($results)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['roll_no']) ?></td>
                                <td><?= htmlspecialchars($row['subject_code']) ?></td>
                                <td><?= htmlspecialchars($row['attendance_date']) ?></td>
                                <td><?= htmlspecialchars($row['attendance_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-results">No attendance records found for Roll <?= htmlspecialchars($_POST['roll_no'] ?? '') ?> in <?= htmlspecialchars($_POST['subject'] ?? '') ?>.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <a href="student.php" class="back-link">Back to Student Portal</a>
    <a href="faculty.php" class="back-link">Back to Faculty Portal</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('student_roll');
    const rollInput = document.querySelector('input[name="roll_no"]');
    if (saved && !rollInput.value) {
        rollInput.value = saved;
    }
});
</script>

</body>
</html>