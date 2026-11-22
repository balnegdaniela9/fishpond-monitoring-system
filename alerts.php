<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['markread'])) {
    $stmt = $conn->prepare("UPDATE alerts a
        JOIN water_readings r ON a.reading_id = r.reading_id
        JOIN ponds p ON r.pond_id = p.pond_id
        SET a.is_read = 1 WHERE p.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: alerts.php");
    exit();
}

$stmt = $conn->prepare("SELECT a.*, p.pond_name FROM alerts a
    JOIN water_readings r ON a.reading_id = r.reading_id
    JOIN ponds p ON r.pond_id = p.pond_id
    WHERE p.user_id = ? ORDER BY a.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$alerts = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Alerts</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f4f8; margin: 0; }
        .nav { background: #0b6e99; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #fff; text-decoration: none; margin-left: 10px; }
        .content { padding: 25px; max-width: 900px; margin: auto; }
        .card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0b6e99; color: #fff; }
        .unread { background: #fff3cd; font-weight: bold; }
    </style>
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System</strong>
        <span><a href="dashboard.php">Dashboard</a> <a href="ponds.php">Ponds</a> <a href="readings.php">Readings</a> <a href="alerts.php">Alerts</a> <a href="logout.php">Logout</a></span>
    </div>
    <div class="content">
        <div class="card">
            <h3>Water Quality Alerts</h3>
            <p><a href="alerts.php?markread=1">Mark all as read</a></p>
            <table>
                <tr><th>Date</th><th>Pond</th><th>Parameter</th><th>Message</th></tr>
                <?php while ($a = $alerts->fetch_assoc()): ?>
                <tr class="<?php echo $a['is_read'] ? '' : 'unread'; ?>">
                    <td><?php echo $a['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($a['pond_name']); ?></td>
                    <td><?php echo htmlspecialchars($a['parameter']); ?></td>
                    <td><?php echo htmlspecialchars($a['message']); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>