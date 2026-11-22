<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Cycles sa user (admin makakita sa tanan)
if ($_SESSION['role'] === 'admin') {
    $stmt = $conn->prepare("SELECT c.*, p.pond_name, u.full_name FROM production_cycles c
        JOIN ponds p ON c.pond_id = p.pond_id
        JOIN users u ON p.user_id = u.user_id
        ORDER BY c.stock_date DESC");
} else {
    $stmt = $conn->prepare("SELECT c.*, p.pond_name, u.full_name FROM production_cycles c
        JOIN ponds p ON c.pond_id = p.pond_id
        JOIN users u ON p.user_id = u.user_id
        WHERE p.user_id = ? ORDER BY c.stock_date DESC");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$cycles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function sumOf($conn, $sql, $id) {
    $s = $conn->prepare($sql);
    $s->bind_param("i", $id);
    $s->execute();
    return (float)($s->get_result()->fetch_row()[0] ?? 0);
}

$rows = [];
$grand_income = 0;
foreach ($cycles as $c) {
    $id    = $c['cycle_id'];
    $feed  = sumOf($conn, "SELECT SUM(quantity_kg) FROM feeding_logs WHERE cycle_id = ?", $id);
    $dead  = sumOf($conn, "SELECT SUM(quantity) FROM mortality_logs WHERE cycle_id = ?", $id);
    $kg    = sumOf($conn, "SELECT SUM(quantity_kg) FROM harvests WHERE cycle_id = ?", $id);
    $inc   = sumOf($conn, "SELECT SUM(quantity_kg * price_per_kg) FROM harvests WHERE cycle_id = ?", $id);
    $surv  = $c['fingerlings_stocked'] > 0 ? round((($c['fingerlings_stocked'] - $dead) / $c['fingerlings_stocked']) * 100, 1) : 0;
    $fcr   = $kg > 0 ? round($feed / $kg, 2) : '-';
    $grand_income += $inc;
    $rows[] = compact('c', 'feed', 'dead', 'kg', 'inc', 'surv', 'fcr');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Report</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System</strong>
        <span><a href="dashboard.php">Dashboard</a> <a href="cycles.php">Cycles</a> <a href="report.php">Report</a> <a href="logout.php">Logout</a></span>
    </div>
    <div class="content">
        <div class="card">
            <h2>Production Report</h2>
            <p>Generated: <?php echo date('F j, Y g:i A'); ?> | Prepared for: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            <button onclick="window.print()">Print / Save as PDF</button>
            <br><br>
            <table>
                <tr>
                    <th>Farmer</th><th>Pond</th><th>Species</th><th>Stock Date</th><th>Stocked</th>
                    <th>Mortality</th><th>Survival</th><th>Feed (kg)</th><th>Harvest (kg)</th><th>FCR</th><th>Income</th><th>Status</th>
                </tr>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['c']['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['c']['pond_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['c']['fish_species']); ?></td>
                    <td><?php echo $r['c']['stock_date']; ?></td>
                    <td><?php echo number_format($r['c']['fingerlings_stocked']); ?></td>
                    <td><?php echo $r['dead']; ?></td>
                    <td><?php echo $r['surv']; ?>%</td>
                    <td><?php echo $r['feed']; ?></td>
                    <td><?php echo $r['kg']; ?></td>
                    <td><?php echo $r['fcr']; ?></td>
                    <td>₱<?php echo number_format($r['inc'], 2); ?></td>
                    <td><?php echo ucfirst($r['c']['status']); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="10">Total Income</td>
                    <td colspan="2">₱<?php echo number_format($grand_income, 2); ?></td>
                </tr>
            </table>
            <p class="note">FCR (Feed Conversion Ratio) = total feed / total harvest. Mas ubos mas maayo.</p>
        </div>
    </div>
</body>
</html>