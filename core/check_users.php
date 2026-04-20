<?php
require 'd:/xampp/htdocs/universal/core/db.php';
$stmt = $pdo->query("SELECT name, email, role FROM users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
