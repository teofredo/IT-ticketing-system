<?php
require 'db_con/db_conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = $_POST['ticket_id'];
    $technician_id = $_POST['technician_id'];
    $rated_by = $_POST['rated_by'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'] ?? null;

    if (empty($rating)) {
        echo "Please select a rating.";
        exit;
    }

    // Check if the ticket exists and is closed
    $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND status_id = 4");
    $stmt->execute([$ticket_id]);

    if ($stmt->rowCount() === 0) {
        echo "Invalid ticket or ticket not closed.";
        exit;
    }

    // Insert the rating and comment
    $stmt = $pdo->prepare("INSERT INTO ratings (ticket_id, technician_id, rating, comment, rated_by, created_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$ticket_id, $technician_id, $rating, $comment, $rated_by]);

    if ($stmt->rowCount()) {
        echo "Rating submitted successfully!";
    } else {
        echo "Failed to submit rating.";
    }
}
?>