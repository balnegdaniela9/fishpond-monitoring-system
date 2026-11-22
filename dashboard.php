<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Fishpond Monitoring System</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f4f8; margin: 0; }
        .nav { background: #0b6e99; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .nav a { color: #fff; text-decoration: none; background: #095a7d; padding: 8px 14px; border-radius: 5px; }
        .content { padding: 25px; }
    </style>
</head>
<body>
    <div class="nav">
        <strong>Fishpond Monitoring System</strong>
        <span>
            <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo $_SESSION['role']; ?>)
            &nbsp; <a href="logout.php">Logout</a>
        </span>
    </div>
    <div class="content">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
        <p><a href="ponds.php">Manage Ponds →</a></p>
    </div>
</body>
</html>