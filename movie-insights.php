<?php
require_once 'config.php';

$pdo = getDB();

// Get all movies with full data
$stmt = $pdo->query("
    SELECT title, publish_date, description, full_content
    FROM posts 
    WHERE site_id = 6
    ORDER BY publish_date DESC
");

$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentYear = date('Y');

// Parse movie data
$movieData = [];
$directors = [];
$years = [];
$decades = [];
$ratings = ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0, '0' => 0];
$monthlyViews = [];
$yearlyViews = [];

foreach ($movies as $movie) {
    // Extract year and rating from title
    preg_match('/(.*?), (\d{4})(?: - (.+))?/', $movie['title'], $matches);
    $movieTitle = $matches[1] ?? $movie['title'];
    $year = $matches[2] ?? null;
    $ratingStr = $matches[3] ?? '';
    
    $starCount = substr_count($ratingStr, '★');
    
    // Rating distribution
    if ($starCount > 0) {
        $ratings[(string)$starCount]++;
    } else {
        $ratings['0']++;
    }
    
    // Year stats
    if ($year) {
        if (!isset($years[$year])) {
            $years[$year] = 0;
        }
        $years[$year]++;
        
        // Decade stats
        $decade = floor($year / 10) * 10;
        if (!isset($decades[$decade])) {
            $decades[$decade] = 0;
        }
        $decades[$decade]++;
    }
    
    // Watch date stats
    if ($movie['publish_date']) {
        $watchDate = strtotime($movie['publish_date']);
        $watchYear = date('Y', $watchDate);
        $watchMonth = date('F', $watchDate);
        
        // Monthly (current year)
        if ($watchYear == $currentYear) {
            if (!isset($monthlyViews[$watchMonth])) {
                $monthlyViews[$watchMonth] = 0;
            }
            $monthlyViews[$watchMonth]++;
        }
        
        // Yearly
        if (!isset($yearlyViews[$watchYear])) {
            $yearlyViews[$watchYear] = 0;
        }
        $yearlyViews[$watchYear]++;
    }
    
    $movieData[] = [
        'title' => $movieTitle,
        'year' => $year,
        'rating' => $starCount,
        'watch_date' => $movie['publish_date'],
        'has_review' => strlen($movie['full_content']) > 100
    ];
}

// Calculate stats
$totalMovies = count($movies);
$moviesThisYear = $yearlyViews[$currentYear] ?? 0;
$withReviews = count(array_filter($movieData, fn($m) => $m['has_review']));

// Average rating
$totalStars = array_sum(array_map(fn($k, $v) => (int)$k * $v, array_keys($ratings), $ratings));
$ratedMovies = $totalMovies - $ratings['0'];
$avgRating = $ratedMovies > 0 ? $totalStars / $ratedMovies : 0;

// Watching velocity
$daysIntoYear = date('z') + 1;
$moviesPerMonth = $moviesThisYear > 0 ? ($moviesThisYear / (date('n'))) : 0;

// Sort data
krsort($yearlyViews);
krsort($years);
krsort($decades);

// Top decades
arsort($decades);
$topDecades = array_slice($decades, 0, 10, true);

// Top release years
arsort($years);
$topYears = array_slice($years, 0, 10, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Insights - MediaLog</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f5f5f5;
            padding: 0;
            color: #1a1a1a;
        }
        
        .top-nav {
            background: #1a1a1a;
            border-bottom: 3px solid #d4af37;
            padding: 0;
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
            font-family: 'Georgia', serif;
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
            padding: 20px 20px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-links a:hover {
            background: #2a2a2a;
            border-bottom-color: #d4af37;
        }
        
        .nav-links a.active {
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
            color: #1a1a1a;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            color: #d4af37;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        
        .bar-chart {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .bar-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .bar-label {
            min-width: 120px;
            font-weight: bold;
        }
        
        .bar-track {
            flex: 1;
            background: #f0f0f0;
            height: 30px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .bar-fill {
            background: #d4af37;
            height: 100%;
            transition: width 1s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-weight: bold;
        }
        
        .horizontal-chart {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            justify-content: space-around;
            height: 200px;
            margin-top: 20px;
        }
        
        .column {
            flex: 1;
            background: #d4af37;
            border-radius: 4px 4px 0 0;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .column:hover {
            background: #c49d2e;
        }
        
        .column-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.9em;
            color: #666;
            white-space: nowrap;
        }
        
        .column-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-weight: bold;
            color: #1a1a1a;
        }
        
        h2 {
            margin: 40px 0 20px 0;
            color: #1a1a1a;
            font-size: 2em;
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
        <h1>🎬 Movie Insights</h1>
        <div class="subtitle">Deep dive into your viewing habits</div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalMovies ?></div>
                <div class="stat-label">Total Movies</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $moviesThisYear ?></div>
                <div class="stat-label">This Year</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($avgRating, 1) ?> ★</div>
                <div class="stat-label">Avg Rating</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($moviesPerMonth, 1) ?></div>
                <div class="stat-label">Per Month</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $withReviews ?></div>
                <div class="stat-label">With Reviews</div>
            </div>
        </div>
        
        <h2>📊 Rating Analysis</h2>
        
        <div class="chart-container">
            <div class="chart-title">Rating Distribution</div>
            <div class="bar-chart">
                <?php 
                $maxCount = max($ratings);
                foreach ([5, 4, 3, 2, 1, 0] as $stars): 
                    $count = $ratings[(string)$stars];
                    $width = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                    $percentage = $totalMovies > 0 ? round(($count / $totalMovies) * 100) : 0;
                ?>
                    <div class="bar-row">
                        <div class="bar-label">
                            <?= $stars > 0 ? str_repeat('★', $stars) : 'No rating' ?>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $width ?>%">
                                <?= $count ?> (<?= $percentage ?>%)
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <h2>🎞️ Decade Analysis</h2>
        
        <div class="chart-container">
            <div class="chart-title">Movies by Decade</div>
            <div class="bar-chart">
                <?php 
                $maxDecade = max($topDecades);
                foreach ($topDecades as $decade => $count): 
                    $width = $maxDecade > 0 ? ($count / $maxDecade) * 100 : 0;
                ?>
                    <div class="bar-row">
                        <div class="bar-label"><?= $decade ?>s</div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $width ?>%">
                                <?= $count ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <h2>📅 Viewing Patterns</h2>
        
        <div class="chart-container">
            <div class="chart-title">Movies Watched by Year</div>
            <div class="bar-chart">
                <?php 
                $maxYear = max($yearlyViews);
                foreach ($yearlyViews as $year => $count): 
                    $width = $maxYear > 0 ? ($count / $maxYear) * 100 : 0;
                ?>
                    <div class="bar-row">
                        <div class="bar-label"><?= $year ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $width ?>%">
                                <?= $count ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($monthlyViews)): ?>
        <div class="chart-container">
            <div class="chart-title"><?= $currentYear ?> Monthly Viewing</div>
            <div class="horizontal-chart" style="padding-bottom: 40px;">
                <?php 
                $maxMonth = max($monthlyViews);
                foreach ($monthlyViews as $month => $count): 
                    $height = $maxMonth > 0 ? ($count / $maxMonth) * 200 : 0;
                ?>
                    <div class="column" style="height: <?= $height ?>px">
                        <div class="column-value"><?= $count ?></div>
                        <div class="column-label"><?= substr($month, 0, 3) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <h2>🎬 Release Years</h2>
        
        <div class="chart-container">
            <div class="chart-title">Top Release Years</div>
            <div class="bar-chart">
                <?php 
                $maxYearCount = max($topYears);
                foreach ($topYears as $year => $count): 
                    $width = $maxYearCount > 0 ? ($count / $maxYearCount) * 100 : 0;
                ?>
                    <div class="bar-row">
                        <div class="bar-label"><?= $year ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $width ?>%">
                                <?= $count ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
