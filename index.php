<?php
include 'config/db.php';

echo "<h2>Connected to fishpond_db successfully!</h2>";

$result = $conn->query("SHOW TABLES");
echo "<p>Tables found: " . $result->num_rows . "</p>";
?>