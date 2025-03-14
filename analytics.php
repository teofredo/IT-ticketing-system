<?php
// analytics.php - Ticketing System Analytics
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require 'db_con/db_conn.php';

// Total Tickets
$totalTickets = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

// Open vs Closed Tickets
$openTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id = 1")->fetchColumn();
$closedTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id = 2")->fetchColumn();

// Tickets by Department
$ticketsByDepartment = $pdo->query("SELECT departments.name, COUNT(tickets.id) AS total FROM tickets JOIN users ON tickets.created_by = users.id JOIN departments ON users.department_id = departments.id GROUP BY departments.name")->fetchAll(PDO::FETCH_ASSOC);

// Tickets by Priority
$ticketsByPriority = $pdo->query("SELECT priorities.name, COUNT(tickets.id) AS total FROM tickets JOIN priorities ON tickets.priority_id = priorities.id GROUP BY priorities.name")->fetchAll(PDO::FETCH_ASSOC);

// Average Resolution Time
$avgResolutionTime = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) FROM tickets WHERE status_id = 2")->fetchColumn();

// Fetch ticket counts by category
$stmt = $pdo->query("
    SELECT categories.name AS category, COUNT(*) AS count
    FROM tickets
    JOIN categories ON tickets.category_id = categories.id
    GROUP BY categories.id
    ORDER BY count DESC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the chart
$categoryNames = [];
$ticketCounts = [];
$totalTickets = 0;

foreach ($categories as $category) {
    $categoryNames[] = $category['category'];
    $ticketCounts[] = $category['count'];
    $totalTickets += $category['count'];
}

// Calculate cumulative percentages
$cumulativePercentages = [];
$cumulativeSum = 0;

foreach ($ticketCounts as $count) {
    $cumulativeSum += $count;
    $cumulativePercentages[] = round(($cumulativeSum / $totalTickets) * 100, 2);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard</title>
</head>
<body>
    <h1>Ticketing System Analytics</h1>
    <p>Total Tickets: <?php echo $totalTickets; ?></p>
    <p>Open Tickets: <?php echo $openTickets; ?></p>
    <p>Closed Tickets: <?php echo $closedTickets; ?></p>
    <p>Average Resolution Time: <?php echo round($avgResolutionTime, 2) . " hours"; ?></p>

    <h2>Tickets by Department</h2>
    <ul>
        <?php foreach ($ticketsByDepartment as $dept) { ?>
            <li><?php echo $dept['name'] . ': ' . $dept['total']; ?></li>
        <?php } ?>
    </ul>

    <h2>Tickets by Priority</h2>
    <ul>
        <?php foreach ($ticketsByPriority as $priority) { ?>
            <li><?php echo $priority['name'] . ': ' . $priority['total']; ?></li>
        <?php } ?>
    </ul>
</body>
</html>
