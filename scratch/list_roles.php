<?php
require_once 'core/db.php';
$roles = $pdo->query("SELECT * FROM sys_roles")->fetchAll(PDO::FETCH_ASSOC);
print_r($roles);
