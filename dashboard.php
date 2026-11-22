<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Total ponds
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM ponds WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_ponds = $stmt->get_result()->fetch_assoc()['total'];

// Total readings
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM water_readings r
    JOIN ponds p ON r.pond_id = p.pond_id WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_readings = $stmt->get_result()->fetch_assoc()['total'];

// Unread alerts
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM alerts a
    JOIN water_readings r ON a.reading_id = r.reading_id
    JOIN ponds p ON r.pond_id = p.pond_id
    WHERE p.user_id = ? AND a.is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_alerts = $stmt->get_result()->fetch_assoc()['total'];

// Latest reading per pond
$stmt = $conn->prepare("SELECT p.pond_name, r.ph_level, r.temperature, r.dissolved_oxygen, r.ammonia, r.reading_datetime
    FROM ponds p
    LEFT JOIN water_readings r ON r.reading_id = (
        SELECT reading_id FROM water_readings
        WHERE pond_id = p.pond_id ORDER BY reading_datetime DESC LIMIT 1
    )
    WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$latest = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Fishpond Monitoring System</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f4f8; margin: 0; }
        .nav { background: #0b6e99; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #fff; text-decoration: none; margin-left: 10px; }
        .content { padding: 25px; max-width: 950px; margin: auto; }
        .stats { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat { flex: 1; min-width: 180px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat h1 { margin: 0; color: #0b6e99; font-size: 40px; }
        .stat.warn h1 { color: #b00020; }
        .card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0b6e99; color: #fff; }
        .bad { color: #b00020; font-weight: bold; }
    </style>
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System</strong>
        <span>
            <a href="dashboard.php">Dashboard</a>
            <a href="ponds.php">Ponds</a>
            <a href="readings.php">Readings</a>
            <a href="alerts.php">Alerts</a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a>
             <a href="cycles.php">Cycles</a>
<a href="report.php">Report</a>

        </span>
    </div>
    <div class="content">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>

        <div class="stats">
            <div class="stat"><h1><?php echo $total_ponds; ?></h1>Total Ponds</div>
            <div class="stat"><h1><?php echo $total_readings; ?></h1>Total Readings</div>
            <div class="stat <?php echo $unread_alerts > 0 ? 'warn' : ''; ?>">
                <h1><?php echo $unread_alerts; ?></h1>Unread Alerts
            </div>
        </div>

        <div class="card">
            <h3>Latest Reading per Pond</h3>
            <table>
                <tr><th>Pond</th><th>pH</th><th>Temp (°C)</th><th>DO (mg/L)</th><th>Ammonia</th><th>Last Updated</th></tr>
                <?php while ($row = $latest->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['pond_name']); ?></td>
                    <?php if ($row['ph_level'] === null): ?>
                        <td colspan="5">No readings yet</td>
                    <?php else: ?>
                        <td class="<?php echo ($row['ph_level'] < 6.5 || $row['ph_level'] > 9) ? 'bad' : ''; ?>"><?php echo $row['ph_level']; ?></td>
                        <td class="<?php echo ($row['temperature'] < 25 || $row['temperature'] > 32) ? 'bad' : ''; ?>"><?php echo $row['temperature']; ?></td>
                        <td class="<?php echo ($row['dissolved_oxygen'] < 4) ? 'bad' : ''; ?>"><?php echo $row['dissolved_oxygen']; ?></td>
                        <td class="<?php echo ($row['ammonia'] > 0.5) ? 'bad' : ''; ?>"><?php echo $row['ammonia']; ?></td>
                        <td><?php echo $row['reading_datetime']; ?></td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>