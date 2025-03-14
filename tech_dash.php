<?php
include "db_con/db_conn.php";

// Execute the query
$sql = "SELECT users.name, 
            COALESCE(SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END), 0) AS openTickets,
            COALESCE(SUM(CASE WHEN status_id = 2 THEN 1 ELSE 0 END), 0) AS inProgressTickets,
            COALESCE(SUM(CASE WHEN status_id = 3 THEN 1 ELSE 0 END), 0) AS resolvedTickets,
            COALESCE(SUM(CASE WHEN status_id = 4 THEN 1 ELSE 0 END), 0) AS closedTickets,
            COUNT(tickets.id) AS totalTickets,
            COALESCE(SUM(CASE WHEN priority_id = 4 THEN 1 ELSE 0 END), 0) AS criticalCount,
            COALESCE(SUM(CASE WHEN priority_id = 3 THEN 1 ELSE 0 END), 0) AS highCount,
            COALESCE(SUM(CASE WHEN priority_id = 2 THEN 1 ELSE 0 END), 0) AS mediumCount,
            COALESCE(SUM(CASE WHEN priority_id = 1 THEN 1 ELSE 0 END), 0) AS lowCount,
            MIN(TIMESTAMPDIFF(MINUTE, tickets.created_at, updated_at)) AS fastestResolved,
            MAX(TIMESTAMPDIFF(MINUTE, tickets.created_at, updated_at)) AS slowestResolved
        FROM users
        RIGHT JOIN tickets ON users.id = tickets.assigned_to
        GROUP BY users.id, users.name";

$result = $pdo->query($sql);

if ($result->rowCount() > 0) {
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    $technicianName = $row['name'];
    $openTickets = $row['openTickets'];
    $inProgressTickets = $row['inProgressTickets'];
    $resolvedTickets = $row['resolvedTickets'];
    $closedTickets = $row['closedTickets'];
    $totalTickets = $row['totalTickets'];
    $criticalCount = $row['criticalCount'];
    $highCount = $row['highCount'];
    $mediumCount = $row['mediumCount'];
    $lowCount = $row['lowCount'];
    $fastestResolved = $row['fastestResolved'] ?? 0; // Handle NULL values
    $slowestResolved = $row['slowestResolved'] ?? 0; // Handle NULL values
} else {
    echo "No data found.";
    exit;
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Technician Dashboard</title>
    <style>
        body { font-family: sans-serif; }
        table { border-collapse: collapse; width: 80%; margin: 20px auto; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        .dashboard-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; width: 90%; margin: 20px auto; }
        .box { border: 1px solid black; padding: 10px; }
        .performance-meter { width: 150px; height: 150px; position: relative; margin: 20px auto; }
        .performance-meter::before { content: ''; position: absolute; width: 100%; height: 100%; border-radius: 50%; background: linear-gradient(to right, red, green); }
        .performance-meter::after { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 140px; height: 140px; border-radius: 50%; background-color: white; }
        .performance-meter .pointer { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(<?php echo calculateRotation($openTickets, $inProgressTickets, $resolvedTickets, $closedTickets, $totalTickets); ?>deg); width: 70px; height: 4px; background-color: black; transform-origin: left center; }
        .performance-meter .text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; }
        .levels { display: flex; flex-direction: column; align-items: flex-start; }
        .levels div { display: flex; align-items: center; }
        .levels span { width: 20px; height: 20px; margin-right: 5px; border-radius: 50%; }
        .levels .critical { background-color: red; }
        .levels .high { background-color: orange; }
        .levels .medium { background-color: yellow; }
        .levels .low { background-color: blue; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="box">
        <h3>Active Tickets</h3>
        <table>
            <tr><th>Open</th><th>In-Progress</th><th>Resolved</th><th>Closed</th></tr>
            <tr><td><?php echo $openTickets; ?></td><td><?php echo $inProgressTickets; ?></td><td><?php echo $resolvedTickets; ?></td><td><?php echo $closedTickets; ?></td></tr>
        </table>
    </div>
    <div class="box">
        <h3>Fastest Resolved</h3>
        <p><?php echo $fastestResolved; ?> minutes</p>
        <h3>Slowest Resolved</h3>
        <p><?php echo $slowestResolved; ?> minutes</p>
    </div>
    <div class="box">
        <h3>Performance Rating scale</h3>
        <div class="performance-meter">
            <div class="pointer"></div>
            <div class="text">
                <?php 
                $performance = calculatePerformance($openTickets, $inProgressTickets, $resolvedTickets, $closedTickets, $totalTickets);
                echo $performance; 
                ?>
            </div>
        </div>
        <p><?php echo $technicianName; ?></p>
    </div>
    <div class="box">
        <h3>Latest Comments</h3>
        <textarea rows="5" cols="30"></textarea>
    </div>
    <div class="box">
        <h3>Levels</h3>
        <div class="levels">
            <div><span class="critical"></span>Critical: <?php echo $criticalCount; ?></div>
            <div><span class="high"></span>High: <?php echo $highCount; ?></div>
            <div><span class="medium"></span>Medium: <?php echo $mediumCount; ?></div>
            <div><span class="low"></span>Low: <?php echo $lowCount; ?></div>
        </div>
    </div>
    <div class="box">
        <h3>Priority sequence</h3>
        <table>
            <tr><th>Ticket ID</th><th>Category</th></tr>
            <?php 
            // Fetch and display priority sequence (requires another query)
            $prioritySql = "SELECT tickets.id, categories.name AS category FROM tickets JOIN categories ON tickets.category_id = categories.id ORDER BY priority_id DESC";
            $priorityResult = $pdo->query($prioritySql);
            if ($priorityResult->num_rows > 0) {
                while($priorityRow = $priorityResult->fetch_assoc()) {
                    echo "<tr><td>".$priorityRow['id']."</td><td>".$priorityRow['category']."</td></tr>";
                }
            } else {
                echo "<tr><td colspan='2'>No priority data</td></tr>";
            }
            ?>
        </table>
    </div>
    <div class="box" colspan="2">
        <h3>Total Tickets</h3>
        <h1><?php echo $totalTickets; ?></h1>
    </div>
</div>

</body>
</html>

<?php
function calculateRotation($open, $inProgress, $resolved, $closed, $total) {
    if ($total == 0) return 0; // Avoid division by zero
    $resolvedPercentage = ($resolved / $total) * 100;
    return ($resolvedPercentage * 1.8) - 90; // Map 0-100% to -90 to 90 degrees
}

function calculatePerformance($open, $inProgress, $resolved, $closed, $total) {
    if ($total == 0) return;
}