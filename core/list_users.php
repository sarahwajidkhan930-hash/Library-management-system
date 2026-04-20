<?php
require 'd:/xampp/htdocs/universal/core/db.php';
require 'd:/xampp/htdocs/universal/core/library_functions.php';
$lib = new Library($pdo);
$stats = $lib->getStudentStats(1);
echo "User 1 Stats:" . PHP_EOL;
print_r($stats);
?>




