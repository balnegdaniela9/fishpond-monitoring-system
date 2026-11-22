<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM ponds WHERE pond_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: ponds.php");
    exit();
}

// ADD or UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pond_name = trim($_POST['pond_name']);
    $size_sqm  = $_POST['size_sqm'];
    $location  = trim($_POST['location']);
    $pond_type = $_POST['pond_type'];
    $pond_id   = (int)($_POST['pond_id'] ?? 0);

    if ($pond_id > 0) {
        $stmt = $conn->prepare("UPDATE ponds SET pond_name=?, size_sqm=?, location=?, pond_type=? WHERE pond_id=? AND user_id=?");
        $stmt->bind_param("sdssii", $pond_name, $size_sqm, $location, $pond_type, $pond_id, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO ponds (user_id, pond_name, size_sqm, location, pond_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $user_id, $pond_name, $size_sqm, $location, $pond_type);
    }
    $stmt->execute();
    header("Location: ponds.php");
    exit();
}

// EDIT (load data into form)
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM ponds WHERE pond_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}

// LIST
$stmt = $conn->prepare("SELECT * FROM ponds WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ponds = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Ponds</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f4f8; margin: 0; }
        .nav { background: #0b6e99; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #fff; text-decoration: none; margin-left: 10px; }
        .content { padding: 25px; max-width: 900px; margin: auto; }
        .card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        input, select { padding: 9px; margin: 5px 5px 5px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { padding: 9px 16px; background: #0b6e99; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0b6e99; color: #fff; }
        .del { color: #b00020; }
    </style>
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System</strong>
        <span><a href="dashboard.php">Dashboard</a> <a href="ponds.php">Ponds</a> <a href="logout.php">Logout</a></span>
    </div>
    <div class="content">
        <div class="card">
            <h3><?php echo $edit ? "Edit Pond" : "Add New Pond"; ?></h3>
            <form method="POST">
                <input type="hidden" name="pond_id" value="<?php echo $edit['pond_id'] ?? 0; ?>">
                <input type="text" name="pond_name" placeholder="Pond name" value="<?php echo htmlspecialchars($edit['pond_name'] ?? ''); ?>" required>
                <input type="number" step="0.01" name="size_sqm" placeholder="Size (sqm)" value="<?php echo $edit['size_sqm'] ?? ''; ?>" required>
                <input type="text" name="location" placeholder="Location (e.g. Butuan City)" value="<?php echo htmlspecialchars($edit['location'] ?? ''); ?>" required>
                <select name="pond_type">
                    <option value="freshwater" <?php if (($edit['pond_type'] ?? '') == 'freshwater') echo 'selected'; ?>>Freshwater</option>
                    <option value="brackish" <?php if (($edit['pond_type'] ?? '') == 'brackish') echo 'selected'; ?>>Brackish</option>
                </select>
                <button type="submit"><?php echo $edit ? "Update" : "Add Pond"; ?></button>
                <?php if ($edit) echo '<a href="ponds.php">Cancel</a>'; ?>
            </form>
        </div>

        <div class="card">
            <h3>My Ponds</h3>
            <table>
                <tr><th>Name</th><th>Size (sqm)</th><th>Location</th><th>Type</th><th>Actions</th></tr>
                <?php while ($p = $ponds->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['pond_name']); ?></td>
                    <td><?php echo $p['size_sqm']; ?></td>
                    <td><?php echo htmlspecialchars($p['location']); ?></td>
                    <td><?php echo ucfirst($p['pond_type']); ?></td>
                    <td>
                        <a href="ponds.php?edit=<?php echo $p['pond_id']; ?>">Edit</a> |
                        <a class="del" href="ponds.php?delete=<?php echo $p['pond_id']; ?>" onclick="return confirm('Delete this pond?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>