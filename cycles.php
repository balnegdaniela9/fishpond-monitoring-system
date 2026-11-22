<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// START NEW CYCLE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pond_id = (int)$_POST['pond_id'];
    $species = trim($_POST['fish_species']);
    $stocked = (int)$_POST['fingerlings_stocked'];
    $date    = $_POST['stock_date'];

    $check = $conn->prepare("SELECT pond_id FROM ponds WHERE pond_id = ? AND user_id = ?");
    $check->bind_param("ii", $pond_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 1) {
        $stmt = $conn->prepare("INSERT INTO production_cycles (pond_id, fish_species, fingerlings_stocked, stock_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $pond_id, $species, $stocked, $date);
        $stmt->execute();
    }
    header("Location: cycles.php");
    exit();
}

// Ponds dropdown
$stmt = $conn->prepare("SELECT pond_id, pond_name FROM ponds WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_ponds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Cycles list
$stmt = $conn->prepare("SELECT c.*, p.pond_name FROM production_cycles c
    JOIN ponds p ON c.pond_id = p.pond_id
    WHERE p.user_id = ? ORDER BY c.stock_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cycles = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Cycles</title>
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
        .ongoing { color: #0b6e99; font-weight: bold; }
        .done { color: #2e7d32; font-weight: bold; }
    </style>
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System</strong>
        <span><a href="dashboard.php">Dashboard</a> <a href="ponds.php">Ponds</a> <a href="readings.php">Readings</a> <a href="cycles.php">Cycles</a> <a href="alerts.php">Alerts</a> <a href="logout.php">Logout</a></span>
    </div>
    <div class="content">
        <div class="card">
            <h3>Start New Production Cycle</h3>
            <?php if (count($my_ponds) == 0): ?>
                <p>Wala pa kay pond. <a href="ponds.php">Mag-add una og pond</a>.</p>
            <?php else: ?>
            <form method="POST">
                <select name="pond_id" required>
                    <?php foreach ($my_ponds as $p): ?>
                        <option value="<?php echo $p['pond_id']; ?>"><?php echo htmlspecialchars($p['pond_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="fish_species" placeholder="Fish species (e.g. Bangus)" required>
                <input type="number" name="fingerlings_stocked" placeholder="Fingerlings stocked" min="1" required>
                <input type="date" name="stock_date" value="<?php echo date('Y-m-d'); ?>" required>
                <button type="submit">Start Cycle</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>My Production Cycles</h3>
            <table>
                <tr><th>Pond</th><th>Species</th><th>Stocked</th><th>Stock Date</th><th>Status</th><th></th></tr>
                <?php while ($c = $cycles->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['pond_name']); ?></td>
                    <td><?php echo htmlspecialchars($c['fish_species']); ?></td>
                    <td><?php echo $c['fingerlings_stocked']; ?></td>
                    <td><?php echo $c['stock_date']; ?></td>
                    <td class="<?php echo $c['status'] == 'ongoing' ? 'ongoing' : 'done'; ?>"><?php echo ucfirst($c['status']); ?></td>
                    <td><a href="cycle_view.php?id=<?php echo $c['cycle_id']; ?>">Open</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>