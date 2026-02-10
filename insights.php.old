<?php
require_once 'config.php';

$pdo = getDB();

// Get all books
$stmt = $pdo->query("
    SELECT title, publish_date, description, full_content
    FROM posts 
    WHERE site_id = 7
    ORDER BY publish_date DESC
");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all movies
$stmt = $pdo->query("
    SELECT title, publish_date, description, full_content
    FROM posts 
    WHERE site_id = 6
    ORDER BY publish_date DESC
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentYear = date('Y');
$currentMonth = date('n');

// Check if current year has any data, fallback to previous year
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM posts 
    WHERE (site_id = 6 OR site_id = 7) 
    AND YEAR(publish_date) = {$currentYear}
");
$currentYearCount = $stmt->fetch()['total'];

if ($currentYearCount == 0) {
    $currentYear = $currentYear - 1;
    $currentMonth = 12; // Use December of previous year
}

// Initialize monthly data for books
$monthlyBooks = [];
$monthlyMovies = [];
$monthlyPages = [];
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

for ($i = 1; $i <= 12; $i++) {
    $monthlyBooks[$i] = 0;
    $monthlyMovies[$i] = 0;
    $monthlyPages[$i] = 0;
}

// Process books
$totalPages = 0;
$booksThisYear = 0;
$booksThisMonth = 0;
$pagesThisMonth = 0;

foreach ($books as $book) {
    $date = strtotime($book['publish_date']);
    $year = date('Y', $date);
    $month = (int)date('n', $date);
    
    if ($year == $currentYear) {
        $monthlyBooks[$month]++;
        $booksThisYear++;
        
        if ($month == $currentMonth) {
            $booksThisMonth++;
        }
        
        // Extract pages
        if (preg_match('/(\d+)\s+pages/', $book['description'], $matches)) {
            $pages = (int)$matches[1];
            $monthlyPages[$month] += $pages;
            $totalPages += $pages;
            
            if ($month == $currentMonth) {
                $pagesThisMonth += $pages;
            }
        }
    }
}

// Process movies
$moviesThisYear = 0;
$moviesThisMonth = 0;

foreach ($movies as $movie) {
    $date = strtotime($movie['publish_date']);
    $year = date('Y', $date);
    $month = (int)date('n', $date);
    
    if ($year == $currentYear) {
        $monthlyMovies[$month]++;
        $moviesThisYear++;
        
        if ($month == $currentMonth) {
            $moviesThisMonth++;
        }
    }
}

// Calculate velocities
$daysIntoYear = date('z') + 1;
$daysIntoMonth = date('j');
$booksPerDay = $daysIntoYear > 0 ? round($booksThisYear / $daysIntoYear, 2) : 0;
$moviesPerDay = $daysIntoYear > 0 ? round($moviesThisYear / $daysIntoYear, 2) : 0;
$pagesPerDay = $daysIntoYear > 0 ? round($totalPages / $daysIntoYear, 1) : 0;

// This month rates
$booksPerDayThisMonth = $daysIntoMonth > 0 ? round($booksThisMonth / $daysIntoMonth, 2) : 0;
$moviesPerDayThisMonth = $daysIntoMonth > 0 ? round($moviesThisMonth / $daysIntoMonth, 2) : 0;

// Projections
$daysInYear = 365;
$projectedBooks = round($booksPerDay * $daysInYear);
$projectedMovies = round($moviesPerDay * $daysInYear);
$projectedPages = round($pagesPerDay * $daysInYear);

// Find peak months
$peakBookMonth = array_search(max($monthlyBooks), $monthlyBooks);
$peakMovieMonth = array_search(max($monthlyMovies), $monthlyMovies);
$peakPagesMonth = array_search(max($monthlyPages), $monthlyPages);

// Combined media stats
$totalMedia = count($books) + count($movies);
$totalMediaThisYear = $booksThisYear + $moviesThisYear;

// Streaks - consecutive days with activity
$allDates = [];
foreach ($books as $book) {
    $allDates[] = date('Y-m-d', strtotime($book['publish_date']));
}
foreach ($movies as $movie) {
    $allDates[] = date('Y-m-d', strtotime($movie['publish_date']));
}
$allDates = array_unique($allDates);
sort($allDates);

$currentStreak = 0;
$maxStreak = 0;
$tempStreak = 1;
$yesterday = date('Y-m-d', strtotime('-1 day'));
$today = date('Y-m-d');

for ($i = 0; $i < count($allDates) - 1; $i++) {
    $date1 = new DateTime($allDates[$i]);
    $date2 = new DateTime($allDates[$i + 1]);
    $diff = $date1->diff($date2)->days;
    
    if ($diff == 1) {
        $tempStreak++;
    } else {
        $maxStreak = max($maxStreak, $tempStreak);
        $tempStreak = 1;
    }
}
$maxStreak = max($maxStreak, $tempStreak);

// Check current streak
if (in_array($today, $allDates) || in_array($yesterday, $allDates)) {
    $currentStreak = 1;
    for ($i = 1; $i < 365; $i++) {
        $checkDate = date('Y-m-d', strtotime("-{$i} days"));
        if (in_array($checkDate, $allDates)) {
            $currentStreak++;
        } else {
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Insights - MediaLog</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f5f5f5;
            color: #1a1a1a;
        }
        
        .top-nav {
            background: #1a1a1a;
            border-bottom: 3px solid #d4af37;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        
        .nav-brand {
            font-size: 24px;
            font-weight: bold;
            color: #d4af37;
            padding: 15px 0;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 0;
        }
        
        .nav-links a {
            display: block;
            padding: 20px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background: #2a2a2a;
            border-bottom-color: #d4af37;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        h2 {
            font-size: 2em;
            margin: 40px 0 20px 0;
            color: #1a1a1a;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card.highlight {
            background: linear-gradient(135deg, #d4af37 0%, #f4d483 100%);
            color: white;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #d4af37;
            margin-bottom: 10px;
        }
        
        .stat-card.highlight .stat-number {
            color: white;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card.highlight .stat-label {
            color: rgba(255,255,255,0.9);
        }
        
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #1a1a1a;
        }
        
        .dual-line-chart {
            height: 300px;
            position: relative;
            padding: 20px 0;
        }
        
        .chart-lines {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 250px;
            gap: 8px;
        }
        
        .month-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        
        .bars {
            display: flex;
            gap: 4px;
            align-items: flex-end;
            height: 200px;
            margin-bottom: 10px;
        }
        
        .bar {
            width: 20px;
            border-radius: 4px 4px 0 0;
            transition: all 0.3s ease;
        }
        
        .bar.books {
            background: #d4af37;
        }
        
        .bar.movies {
            background: #6c757d;
        }
        
        .bar:hover {
            opacity: 0.8;
        }
        
        .month-label {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        
        .legend {
            display: flex;
            gap: 30px;
            justify-content: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .comparison-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .insight-box {
            background: #f9f9f9;
            border-left: 4px solid #d4af37;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .insight-box h3 {
            margin-bottom: 10px;
            color: #d4af37;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">MEDIALOG</a>
            <ul class="nav-links">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="movies.php">Movies</a></li>
                <li><a href="authors.php">Authors</a></li>
                <li><a href="directors.php">Directors</a></li>
                <li><a href="stats.php">Statistics</a></li>
                <li><a href="insights.php" class="active">Insights</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <h1>📊 Advanced Insights</h1>
        <div class="subtitle">Deep analytics on your media consumption patterns</div>
        
        <h2>🔥 Current Pace</h2>
        
        <div class="stats-grid">
            <div class="stat-card highlight">
                <div class="stat-number"><?= $booksPerDay ?></div>
                <div class="stat-label">Books/Day</div>
            </div>
            
            <div class="stat-card highlight">
                <div class="stat-number"><?= $moviesPerDay ?></div>
                <div class="stat-label">Movies/Day</div>
            </div>
            
            <div class="stat-card highlight">
                <div class="stat-number"><?= $pagesPerDay ?></div>
                <div class="stat-label">Pages/Day</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $currentStreak ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $maxStreak ?></div>
                <div class="stat-label">Longest Streak</div>
            </div>
        </div>
        
        <h2>📈 <?= $currentYear ?> Projections</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $projectedBooks ?></div>
                <div class="stat-label">Books (Projected)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $projectedMovies ?></div>
                <div class="stat-label">Movies (Projected)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($projectedPages) ?></div>
                <div class="stat-label">Pages (Projected)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $booksThisYear + $moviesThisYear ?> / <?= $projectedBooks + $projectedMovies ?></div>
                <div class="stat-label">Total Media</div>
            </div>
        </div>
        
        <div class="insight-box">
            <h3>💡 Key Insights</h3>
            <p><strong>Peak Book Month:</strong> <?= $monthNames[$peakBookMonth - 1] ?> (<?= $monthlyBooks[$peakBookMonth] ?> books)</p>
            <p><strong>Peak Movie Month:</strong> <?= $monthNames[$peakMovieMonth - 1] ?> (<?= $monthlyMovies[$peakMovieMonth] ?> movies)</p>
            <p><strong>Peak Reading Month:</strong> <?= $monthNames[$peakPagesMonth - 1] ?> (<?= number_format($monthlyPages[$peakPagesMonth]) ?> pages)</p>
        </div>
        
        <h2>📅 Monthly Breakdown (<?= $currentYear ?>)</h2>
        
        <div class="chart-container">
            <div class="chart-title">Books vs Movies by Month</div>
            <div class="dual-line-chart">
                <div class="chart-lines">
                    <?php 
                    $maxBooks = max($monthlyBooks);
                    $maxMovies = max($monthlyMovies);
                    $maxTotal = max($maxBooks, $maxMovies);
                    
                    for ($i = 1; $i <= 12; $i++): 
                        $bookHeight = $maxTotal > 0 ? ($monthlyBooks[$i] / $maxTotal) * 200 : 0;
                        $movieHeight = $maxTotal > 0 ? ($monthlyMovies[$i] / $maxTotal) * 200 : 0;
                    ?>
                        <div class="month-group">
                            <div class="bars">
                                <div class="bar books" 
                                     style="height: <?= $bookHeight ?>px"
                                     title="<?= $monthlyBooks[$i] ?> books"></div>
                                <div class="bar movies" 
                                     style="height: <?= $movieHeight ?>px"
                                     title="<?= $monthlyMovies[$i] ?> movies"></div>
                            </div>
                            <div class="month-label"><?= $monthNames[$i - 1] ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background: #d4af37;"></div>
                        <span>Books</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #6c757d;"></div>
                        <span>Movies</span>
                    </div>
                </div>
            </div>
        </div>
        
        <h2>📖 This Month Performance</h2>
        
        <div class="comparison-grid">
            <div class="chart-container">
                <div class="chart-title">Books</div>
                <div class="stat-number" style="text-align: center;"><?= $booksThisMonth ?></div>
                <div class="stat-label" style="text-align: center; margin-top: 10px;">
                    <?= $booksPerDayThisMonth ?> per day
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">Movies</div>
                <div class="stat-number" style="text-align: center;"><?= $moviesThisMonth ?></div>
                <div class="stat-label" style="text-align: center; margin-top: 10px;">
                    <?= $moviesPerDayThisMonth ?> per day
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($pagesThisMonth) ?></div>
                <div class="stat-label">Pages This Month</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $daysIntoMonth > 0 ? round($pagesThisMonth / $daysIntoMonth, 1) : 0 ?></div>
                <div class="stat-label">Pages/Day (Month)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $booksThisMonth + $moviesThisMonth ?></div>
                <div class="stat-label">Total Media</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $daysIntoMonth > 0 ? round(($booksThisMonth + $moviesThisMonth) / $daysIntoMonth, 2) : 0 ?></div>
                <div class="stat-label">Media/Day</div>
            </div>
        </div>
        
        <h2>🎯 All-Time Stats</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= count($books) ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= count($movies) ?></div>
                <div class="stat-label">Total Movies</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $totalMedia ?></div>
                <div class="stat-label">Combined Total</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= round((count($books) / $totalMedia) * 100) ?>%</div>
                <div class="stat-label">Books Ratio</div>
            </div>
        </div>
    </div>
</body>
</html>
