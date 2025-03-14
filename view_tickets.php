<?php

require 'db_con/db_conn.php';
// view_tickets.php - View all tickets
$stmt = $pdo->query("SELECT tickets.id, subject, description, statuses.name AS status FROM tickets JOIN statuses ON tickets.status_id = statuses.id");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tickets as $ticket) {
    echo "<h3>" . $ticket['subject'] . "</h3>";
    echo "<p>Status: " . $ticket['status'] . "</p>";
    echo "<p>Description: " . $ticket['description'] . "</p>";
    echo "<hr>";
}
?>

<a href="logout.php">Logout</a>