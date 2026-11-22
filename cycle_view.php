<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$cycle_id = (int)($_GET['id'] ?? 0);

// Siguroha nga iya ang cycle
$stmt = $conn->prepare("SELECT c.*, p.pond_name FROM production_cycles c
    JOIN ponds p ON c.pond_id = p.pond_id
    WHERE c.cycle_id = ? AND p.user_id = ?");
$stmt->bind_param("ii", $cycle_id, $user_id);
$stmt->execute();
$cycle = $stmt->get_result()->fetch_assoc();

if (!$cycle) {
    die("Cycle not found. <a href='cycles.php'>Back</a>");
}

// POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == 'feed') {
        $stmt = $conn->prepare("INSERT INTO feeding_logs (cycle_id, feed_date, feed_type, quantity_kg) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $cycle_id, $_POST['feed_date'], $_POST['feed_type'], $_POST['quantity_kg']);
        $stmt->execute();
    } elseif ($action == 'mortality') {
        $stmt = $conn->prepare("INSERT INTO mortality_logs (cycle_id, mortality_date, quantity, cause) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $cycle_id, $_POST['mortality_date'], $_POST['quantity'], $_POST['cause']);
        $stmt->execute();
    } elseif ($action == 'harvest') {
        $stmt = $conn->prepare("INSERT INTO harvests (cycle_id, harvest_date, quantity_kg, fish_size, price_per_kg) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsd", $cycle_id, $_POST['harvest_date'], $_POST['h_quantity_kg'], $_POST['fish_size'], $_POST['price_per_kg']);
        $stmt->execute();
    } elseif ($action == 'complete') {
        $stmt = $conn->prepare("UPDATE production_cycles SET status='harvested' WHERE cycle_id = ?");
        $stmt->bind_param("i", $cycle_id);
        $stmt->execute();
    }
    header("Location: cycle_view.php?id=$cycle_id");
    exit();
}

// Data
function getAll($conn, $sql, $id) {
    $s = $conn->prepare($sql);
    $s->bind_param("i", $id);
    $s->execute();
    return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
$feeds   = getAll($conn, "SELECT * FROM feeding_logs WHERE cycle_id = ? ORDER BY feed_date DESC", $cycle_id);
$morts   = getAll($conn, "SELECT * FROM mortality_logs WHERE cycle_id = ? ORDER BY mortality_date DESC", $cycle_id);
$harvest = getAll($conn, "SELECT * FROM harvests WHERE cycle_id = ? ORDER BY harvest_date DESC", $cycle_id);

$total_feed = array_sum(array_column($feeds, 'quantity_kg'));
$total_dead = array_sum(array_column($morts, 'quantity'));
$total_kg   = array_sum(array_column($harvest, 'quantity_kg'));
$total_income = 0;
foreach ($harvest as $h) { $total_income += $h['quantity_kg'] * $h['price_per_kg']; }
$survival = $cycle['fingerlings_stocked'] > 0
    ? round((($cycle['fingerlings_stocked'] - $total_dead) / $cycle['fingerlings_stocked']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cycle Details</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f4f8; margin: 0; }
        .nav { background: #0b6e99; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #fff; text-decoration: none; margin-left: 10px; }
        .content { padding: 25px; max-width: 950px; margin: auto; }
        .card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .stat { flex: 1; min-width: 150px; background: #fff; padding: 15px; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat h2 { margin: 0; color: #0b6e99; }
        input { padding: 8px; margin: 4px 4px 4px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { padding: 8px 14px; background: #0b6e99; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0b6e99; color: #fff; }
    </style>
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System</strong>
        <span><a href="dashboard.php">Dashboard</a> <a href="ponds.php">Ponds</a> <a href="readings.php">Readings</a> <a href="cycles.php">Cycles</a> <a href="alerts.php">Alerts</a> <a href="logout.php">Logout</a></span>
    </div>
    <div class="content">
        <h2><?php echo htmlspecialchars($cycle['pond_name']); ?> - <?php echo htmlspecialchars($cycle['fish_species']); ?>
            (<?php echo ucfirst($cycle['status']); ?>)</h2>
        <p><a href="cycles.php">&larr; Back to cycles</a></p>

        <div class="stats">
            <div class="stat"><h2><?php echo number_format($cycle['fingerlings_stocked']); ?></h2>Stocked</div>
            <div class="stat"><h2><?php echo $total_dead; ?></h2>Mortality</div>
            <div class="stat"><h2><?php echo $survival; ?>%</h2>Survival Rate</div>
            <div class="stat"><h2><?php echo $total_feed; ?> kg</h2>Total Feed</div>
            <div class="stat"><h2><?php echo $total_kg; ?> kg</h2>Harvested</div>
            <div class="stat"><h2>₱<?php echo number_format($total_income, 2); ?></h2>Income</div>
        </div>

        <div class="card">
            <h3>Feeding Logs</h3>
            <form method="POST">
                <input type="hidden" name="action" value="feed">
                <input type="date" name="feed_date" value="<?php echo date('Y-m-d'); ?>" required>
                <input type="text" name="feed_type" placeholder="Feed type" required>
                <input type="number" step="0.01" name="quantity_kg" placeholder="Qty (kg)" required>
                <button type="submit">Add</button>
            </form>
            <table>
                <tr><th>Date</th><th>Feed Type</th><th>Qty (kg)</th></tr>
                <?php foreach ($feeds as $f): ?>
                <tr><td><?php echo $f['feed_date']; ?></td><td><?php echo htmlspecialchars($f['feed_type']); ?></td><td><?php echo $f['quantity_kg']; ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card">
            <h3>Mortality Logs</h3>
            <form method="POST">
                <input type="hidden" name="action" value="mortality">
                <input type="date" name="mortality_date" value="<?php echo date('Y-m-d'); ?>" required>
                <input type="number" name="quantity" placeholder="No. of dead fish" min="1" required>
                <input type="text" name="cause" placeholder="Cause (e.g. low oxygen)">
                <button type="submit">Add</button>
            </form>
            <table>
                <tr><th>Date</th><th>Quantity</th><th>Cause</th></tr>
                <?php foreach ($morts as $m): ?>
                <tr><td><?php echo $m['mortality_date']; ?></td><td><?php echo $m['quantity']; ?></td><td><?php echo htmlspecialchars($m['cause']); ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card">
            <h3>Harvests</h3>
            <form method="POST">
                <input type="hidden" name="action" value="harvest">
                <input type="date" name="harvest_date" value="<?php echo date('Y-m-d'); ?>" required>
                <input type="number" step="0.01" name="h_quantity_kg" placeholder="Qty (kg)" required>
                <input type="text" name="fish_size" placeholder="Size (e.g. 3 pcs/kg)">
                <input type="number" step="0.01" name="price_per_kg" placeholder="Price per kg (₱)" required>
                <button type="submit">Add</button>
            </form>
            <table>
                <tr><th>Date</th><th>Qty (kg)</th><th>Size</th><th>Price/kg</th><th>Total</th></tr>
                <?php foreach ($harvest as $h): ?>
                <tr>
                    <td><?php echo $h['harvest_date']; ?></td>
                    <td><?php echo $h['quantity_kg']; ?></td>
                    <td><?php echo htmlspecialchars($h['fish_size']); ?></td>
                    <td>₱<?php echo number_format($h['price_per_kg'], 2); ?></td>
                    <td>₱<?php echo number_format($h['quantity_kg'] * $h['price_per_kg'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php if ($cycle['status'] == 'ongoing'): ?>
            <form method="POST" style="margin-top:10px" onsubmit="return confirm('Mark this cycle as completed?')">
                <input type="hidden" name="action" value="complete">
                <button type="submit">Mark Cycle as Harvested</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>