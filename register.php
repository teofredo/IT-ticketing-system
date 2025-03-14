<?php
session_start();
require 'db_con/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department_id = $_POST['department_id'];

    $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role, department_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $email, $password, $role, $department_id]);
        $_SESSION['success_reg'] = "User added successfully.";
}

       header('location: index.php');
?>
