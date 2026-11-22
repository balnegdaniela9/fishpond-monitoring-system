<?php
session_start();
include 'config/db.php';

$message = "";

if (isset($_GET['registered'])) {
    $message = "Registration successful! Please login.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id']   = $row['user_id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role']      = $row['role'];
            header("Location: dashboard.php");
            exit();
        }
    }
    $message = "Invalid email or password.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Fishpond Monitoring System</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: #fff; padding: 30px; border-radius: 10px; width: 320px; box-shadow: 0 2px 10px rgba(0,0,0,0.15); }
        h2 { text-align: center; color: #0b6e99; margin-top: 0; }
        input { width: 100%; padding: 10px; margin: 8px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: #0b6e99; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #095a7d; }
        .msg { color: #b00020; text-align: center; font-size: 14px; }
        p { text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Fishpond Login</h2>
        <?php if ($message) echo "<p class='msg'>" . htmlspecialchars($message) . "</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>No account yet? <a href="register.php">Register</a></p>
    </div>
</body>
</html>