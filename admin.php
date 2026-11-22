<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only. <a href='dashboard.php'>Back</a>");
}

// Delete farmer (dili ang admin mismo)
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    if ($id !== (int)$_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'farmer'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header("Location: admin.php");
    exit();
}

// Summary counts
$total_users  = $conn->query("SELECT COUNT(*) AS t FROM users WHERE role='farmer'")->fetch_assoc()['t'];
$total_ponds  = $conn->query("SELECT COUNT(*) AS t FROM ponds")->fetch_assoc()['t'];
$total_cycles = $conn->query("SELECT COUNT(*) AS t FROM production_cycles WHERE status='ongoing'")->fetch_assoc()['t'];
$total_alerts = $conn->query("SELECT COUNT(*) AS t FROM alerts WHERE is_read=0")->fetch_assoc()['t'];

// Farmers + ilang pond count
$farmers = $conn->query("SELECT u.user_id, u.full_name, u.email, u.created_at, COUNT(p.pond_id) AS pond_count
    FROM users u LEFT JOIN ponds p ON p.user_id = u.user_id
    WHERE u.role = 'farmer' GROUP BY u.user_id ORDER BY u.created_at DESC");

// Latest 10 alerts (tanang farmers)
$alerts = $conn->query("SELECT a.*, p.pond_name, u.full_name FROM alerts a
    JOIN water_readings r ON a.reading_id = r.reading_id
    JOIN ponds p ON r.pond_id = p.pond_id
    JOIN users u ON p.user_id = u.user_id
    ORDER BY a.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Fishpond Monitoring System</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f4f8; margin: 0; }
        .nav { background: #0b6e99; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #fff; text-decoration: none; margin-left: 10px; }
        .content { padding: 25px; max-width: 950px; margin: auto; }
        .stats { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat { flex: 1; min-width: 160px; background: #fff; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat h1 { margin: 0; color: #0b6e99; }
        .card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0b6e99; color: #fff; }
        .del { color: #b00020; }
    </style>
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System (Admin)</strong>
        <span><a href="dashboard.php">Dashboard</a> <a href="admin.php">Admin</a> <a href="logout.php">Logout</a></span>
    </div>
    <div class="content">
        <div class="stats">
            <div class="stat"><h1><?php echo $total_users; ?></h1>Farmers</div>
            <div class="stat"><h1><?php echo $total_ponds; ?></h1>Total Ponds</div>
            <div class="stat"><h1><?php echo $total_cycles; ?></h1>Ongoing Cycles</div>
            <div class="stat"><h1><?php echo $total_alerts; ?></h1>Unread Alerts</div>
        </div>

        <div class="card">
            <h3>Registered Farmers</h3>
            <table>
                <tr><th>Name</th><th>Email</th><th>Ponds</th><th>Joined</th><th></th></tr>
                <?php while ($f = $farmers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($f['email']); ?></td>
                    <td><?php echo $f['pond_count']; ?></td>
                    <td><?php echo $f['created_at']; ?></td>
                    <td><a class="del" href="admin.php?delete_user=<?php echo $f['user_id']; ?>" onclick="return confirm('Delete this farmer and all their data?')">Delete</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="card">
            <h3>Latest Alerts (All Farmers)</h3>
            <table>
                <tr><th>Date</th><th>Farmer</th><th>Pond</th><th>Parameter</th><th>Message</th></tr>
                <?php while ($a = $alerts->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $a['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($a['full_name']); ?></td>
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