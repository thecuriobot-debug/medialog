<?php
require_once 'config.php';
$pdo = getDB();

// Get data for visualizations
$books = $pdo->query("SELECT * FROM posts WHERE site_id = 7 ORDER BY publish_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$movies = $pdo->query("SELECT * FROM posts WHERE site_id = 6 ORDER BY publish_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Process data for charts
$booksByMonth = [];
$moviesByMonth = [];
$booksByYear = [];
$moviesByYear = [];

foreach ($books as $book) {
    $month = date('Y-m', strtotime($book['publish_date']));
    $year = date('Y', strtotime($book['publish_date']));
    $booksByMonth[$month] = ($booksByMonth[$month] ?? 0) + 1;
    $booksByYear[$year] = ($booksByYear[$year] ?? 0) + 1;
}

foreach ($movies as $movie) {
    $month = date('Y-m', strtotime($movie['publish_date']));
    $year = date('Y', strtotime($movie['publish_date']));
    $moviesByMonth[$month] = ($moviesByMonth[$month] ?? 0) + 1;
    $moviesByYear[$year] = ($moviesByYear[$year] ?? 0) + 1;
}

ksort($booksByMonth);
ksort($moviesByMonth);
ksort($booksByYear);
ksort($moviesByYear);

// Get last 12 months for trend chart
$last12Months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $last12Months[] = [
        'month' => date('M Y', strtotime($month . '-01')),
        'books' => $booksByMonth[$month] ?? 0,
        'movies' => $moviesByMonth[$month] ?? 0
    ];
}

$pageTitle = "Visual Analytics";
$pageStyles = "
    .chart-section {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .chart-section h2 {
        color: #1a1a1a;
        margin-bottom: 20px;
        font-size: 1.8em;
    }
    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
    }
    canvas {
        max-height: 400px;
    }
";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">📊 Visual Analytics</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Interactive data visualizations</p>
    </div>
    
    <div class="chart-section">
        <h2>📈 12-Month Trend</h2>
        <canvas id="trendChart"></canvas>
    </div>
    
    <div class="chart-grid">
        <div class="chart-section">
            <h2>📚 Books by Year</h2>
            <canvas id="booksYearChart"></canvas>
        </div>
        
        <div class="chart-section">
            <h2>🎬 Movies by Year</h2>
            <canvas id="moviesYearChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($last12Months, 'month')) ?>,
        datasets: [{
            label: 'Books',
            data: <?= json_encode(array_column($last12Months, 'books')) ?>,
            borderColor: '#d4af37',
            backgroundColor: 'rgba(212, 175, 55, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Movies',
            data: <?= json_encode(array_column($last12Months, 'movies')) ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: true, position: 'top' },
            title: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Books by Year
const booksYearCtx = document.getElementById('booksYearChart').getContext('2d');
new Chart(booksYearCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($booksByYear)) ?>,
        datasets: [{
            label: 'Books',
            data: <?= json_encode(array_values($booksByYear)) ?>,
            backgroundColor: '#d4af37'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// Movies by Year
const moviesYearCtx = document.getElementById('moviesYearChart').getContext('2d');
new Chart(moviesYearCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($moviesByYear)) ?>,
        datasets: [{
            label: 'Movies',
            data: <?= json_encode(array_values($moviesByYear)) ?>,
            backgroundColor: '#667eea'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
