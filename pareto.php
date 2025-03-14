<?php
// analytics.php - Ticketing System Analytics
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard .php');
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Ticketing Analytics - Pareto Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>Pareto Chart - Ticket Distribution by Category</h2>
    <canvas id="paretoChart"></canvas>

    <script>
        const ctx = document.getElementById('paretoChart').getContext('2d');
        
        const categoryLabels = <?php echo json_encode($categoryNames); ?>;
        const ticketCounts = <?php echo json_encode($ticketCounts); ?>;
        const cumulativePercentages = <?php echo json_encode($cumulativePercentages); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [
                    {
                        label: 'Ticket Count',
                        data: ticketCounts,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Cumulative %',
                        data: cumulativePercentages,
                        type: 'line',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        yAxisID: 'y-axis-2'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Ticket Count'
                        }
                    },
                    'y-axis-2': {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Cumulative Percentage'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    </script>
</body>
</html>
