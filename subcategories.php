<?php
require 'db_con/db_conn.php';

$category_id = $_GET['category_id'];
$stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id = ?");
$stmt->execute([$category_id]);
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subcategories);
?>
