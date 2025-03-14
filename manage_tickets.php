<?php
require 'db_con/db_conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch all tickets
$stmt = $pdo->query("
    SELECT tickets.id, tickets.subject, tickets.description, categories.name AS category, 
           priorities.name AS priority, statuses.name AS status, 
           users.name AS created_by, assignee.name AS assigned_to, tickets.created_at
    FROM tickets
    JOIN categories ON tickets.category_id = categories.id
    JOIN priorities ON tickets.priority_id = priorities.id
    JOIN statuses ON tickets.status_id = statuses.id
    JOIN users ON tickets.created_by = users.id
    LEFT JOIN users AS assignee ON tickets.assigned_to = assignee.id
    ORDER BY tickets.created_at DESC
");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tickets</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="dashboard-container">
    <h2>All Tickets</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Subject</th>
            <th>Description</th>
            <th>Category</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Assigned To</th>
            <th>Created By</th>
            <th>Actions</th>
        </tr>

        <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td><?php echo $ticket['id']; ?></td>
                <td><?php echo $ticket['subject']; ?></td>
                <td><?php echo $ticket['description']; ?></td>
                <td><?php echo $ticket['category']; ?></td>
                <td><?php echo $ticket['priority']; ?></td>
                <td><?php echo $ticket['status']; ?></td>
                <td><?php echo $ticket['assigned_to'] ?? 'Unassigned'; ?></td>
                <td><?php echo $ticket['created_by']; ?></td>
                <td>
                    <a href="assign_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn">Assign</a>
                    <a href="update_status.php?id=<?php echo $ticket['id']; ?>" class="btn">Update Status</a>
                </td>
            </tr>
        <?php endforeach; ?>

    </table>
</div>

</body>
</html>
