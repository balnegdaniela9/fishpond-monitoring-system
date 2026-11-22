<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Ponds sa user (para sa dropdown)
$stmt = $conn->prepare("SELECT pond_id, pond_name FROM ponds WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_ponds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ADD READING
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pond_id = (int)$_POST['pond_id'];
    $ph      = (float)$_POST['ph_level'];
    $temp    = (float)$_POST['temperature'];
    $do      = (float)$_POST['dissolved_oxygen'];
    $nh3     = (float)$_POST['ammonia'];

    // Siguroha nga iya ang pond
    $check = $conn->prepare("SELECT pond_id FROM ponds WHERE pond_id = ? AND user_id = ?");
    $check->bind_param("ii", $pond_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 1) {
        $stmt = $conn->prepare("INSERT INTO water_readings (pond_id, ph_level, temperature, dissolved_oxygen, ammonia) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idddd", $pond_id, $ph, $temp, $do, $nh3);
        $stmt->execute();
        $reading_id = $conn->insert_id;

        // AUTO-ALERTS
        $alerts = [];
        if ($ph < 6.5 || $ph > 9.0)  $alerts[] = ["pH", "pH level is $ph (safe range: 6.5 - 9.0)"];
        if ($temp < 25 || $temp > 32) $alerts[] = ["Temperature", "Temperature is {$temp}°C (safe range: 25 - 32°C)"];
        if ($do < 4)                  $alerts[] = ["Dissolved Oxygen", "Dissolved oxygen is $do mg/L (should be at least 4 mg/L)"];
        if ($nh3 > 0.5)               $alerts[] = ["Ammonia", "Ammonia is $nh3 mg/L (should not exceed 0.5 mg/L)"];

        $ins = $conn->prepare("INSERT INTO alerts (reading_id, parameter, message) VALUES (?, ?, ?)");
        foreach ($alerts as $a) {
            $ins->bind_param("iss", $reading_id, $a[0], $a[1]);
            $ins->execute();
        }

        $message = count($alerts) > 0
            ? "Reading saved. WARNING: " . count($alerts) . " alert(s) generated!"
            : "Reading saved. All values are within the safe range.";
    } else {
        $message = "Invalid pond.";
    }
}

// LIST (latest 20)
$stmt = $conn->prepare("SELECT r.*, p.pond_name FROM water_readings r
    JOIN ponds p ON r.pond_id = p.pond_id
    WHERE p.user_id = ? ORDER BY r.reading_datetime DESC LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$readings = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Water Readings</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f4f8; margin: 0; }
        .nav { background: #0b6e99; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #fff; text-decoration: none; margin-left: 10px; }
        .content { padding: 25px; max-width: 950px; margin: auto; }
        .card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        input, select { padding: 9px; margin: 5px 5px 5px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { padding: 9px 16px; background: #0b6e99; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0b6e99; color: #fff; }
        .msg { padding: 10px; background: #fff3cd; border-radius: 5px; margin-bottom: 10px; }
        .bad { color: #b00020; font-weight: bold; }
    </style>
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System</strong>
        <span><a href="dashboard.php">Dashboard</a> <a href="ponds.php">Ponds</a> <a href="readings.php">Readings</a> <a href="alerts.php">Alerts</a> <a href="logout.php">Logout</a></span>
    </div>
    <div class="content">
        <div class="card">
            <h3>Record Water Quality</h3>
            <?php if ($message) echo "<div class='msg'>" . htmlspecialchars($message) . "</div>"; ?>
            <?php if (count($my_ponds) == 0): ?>
                <p>Wala pa kay pond. <a href="ponds.php">Mag-add una og pond</a>.</p>
            <?php else: ?>
            <form method="POST">
                <select name="pond_id" required>
                    <?php foreach ($my_ponds as $p): ?>
                        <option value="<?php echo $p['pond_id']; ?>"><?php echo htmlspecialchars($p['pond_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.01" name="ph_level" placeholder="pH" required>
                <input type="number" step="0.1" name="temperature" placeholder="Temp (°C)" required>
                <input type="number" step="0.01" name="dissolved_oxygen" placeholder="DO (mg/L)" required>
                <input type="number" step="0.01" name="ammonia" placeholder="Ammonia (mg/L)" required>
                <button type="submit">Save Reading</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Recent Readings</h3>
            <table>
                <tr><th>Date</th><th>Pond</th><th>pH</th><th>Temp</th><th>DO</th><th>Ammonia</th></tr>
                <?php while ($r = $readings->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $r['reading_datetime']; ?></td>
                    <td><?php echo htmlspecialchars($r['pond_name']); ?></td>
                    <td class="<?php echo ($r['ph_level'] < 6.5 || $r['ph_level'] > 9) ? 'bad' : ''; ?>"><?php echo $r['ph_level']; ?></td>
                    <td class="<?php echo ($r['temperature'] < 25 || $r['temperature'] > 32) ? 'bad' : ''; ?>"><?php echo $r['temperature']; ?></td>
                    <td class="<?php echo ($r['dissolved_oxygen'] < 4) ? 'bad' : ''; ?>"><?php echo $r['dissolved_oxygen']; ?></td>
                    <td class="<?php echo ($r['ammonia'] > 0.5) ? 'bad' : ''; ?>"><?php echo $r['ammonia']; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>