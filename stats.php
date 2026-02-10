<?php
require_once 'config.php';

$pdo = getDB();

// Get all Goodreads books
$stmt = $pdo->query("
    SELECT title, publish_date, full_content, description
    FROM posts 
    WHERE site_id = 7
");

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all Letterboxd movies
$stmt = $pdo->query("
    SELECT title, publish_date, description
    FROM posts 
    WHERE site_id = 6
");

$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set current year with fallback logic
$currentYear = date('Y');

// Check if current year has any data
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM posts 
    WHERE (site_id = 6 OR site_id = 7) 
    AND YEAR(publish_date) = {$currentYear}
");
$currentYearCount = $stmt->fetch()['total'];

// Fallback to previous year if current year is empty
if ($currentYearCount == 0) {
    $currentYear = $currentYear - 1;
}

// Calculate book statistics
$totalBooks = count($books);
$booksWithReviews = count(array_filter($books, fn($b) => strlen($b['full_content']) > 100));
$booksWithoutReviews = $totalBooks - $booksWithReviews;

// Calculate movie statistics
$totalMovies = count($movies);
$moviesThisYear = 0;
$moviesByYear = [];
$moviesByDecade = [];
$moviesByReleaseYear = [];
$moviesWithReviews = 0;

foreach ($movies as $movie) {
    // Watch date stats
    if (date('Y', strtotime($movie['publish_date'])) == $currentYear) {
        $moviesThisYear++;
    }
    
    $watchYear = date('Y', strtotime($movie['publish_date']));
    if (!isset($moviesByYear[$watchYear])) {
        $moviesByYear[$watchYear] = 0;
    }
    $moviesByYear[$watchYear]++;
    
    // Extract release year from title
    if (preg_match('/, (\d{4})/', $movie['title'], $matches)) {
        $releaseYear = $matches[1];
        if (!isset($moviesByReleaseYear[$releaseYear])) {
            $moviesByReleaseYear[$releaseYear] = 0;
        }
        $moviesByReleaseYear[$releaseYear]++;
        
        // Decade stats
        $decade = floor($releaseYear / 10) * 10;
        if (!isset($moviesByDecade[$decade])) {
            $moviesByDecade[$decade] = 0;
        }
        $moviesByDecade[$decade]++;
    }
    
    // Check for reviews
    if (strlen($movie['description']) > 100) {
        $moviesWithReviews++;
    }
}

// Movie ratings
$movieRatings = ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0, '0' => 0];
foreach ($movies as $movie) {
    $starCount = substr_count($movie['title'], '★');
    if ($starCount > 0) {
        $movieRatings[(string)$starCount]++;
    } else {
        $movieRatings['0']++;
    }
}

// Sort movie data
krsort($moviesByYear);
krsort($moviesByDecade);
arsort($moviesByReleaseYear);

// Top decades and years
$topMovieDecades = array_slice($moviesByDecade, 0, 10, true);
$topReleaseYears = array_slice($moviesByReleaseYear, 0, 10, true);

// Average movie rating
$totalMovieStars = array_sum(array_map(fn($k, $v) => (int)$k * $v, array_keys($movieRatings), $movieRatings));
$ratedMovies = $totalMovies - $movieRatings['0'];
$avgMovieRating = $ratedMovies > 0 ? $totalMovieStars / $ratedMovies : 0;

// Movies per month this year
$moviesPerMonth = $moviesThisYear > 0 ? ($moviesThisYear / date('n')) : 0;

// Calculate total pages read
$totalPages = 0;
foreach ($books as $book) {
    if (preg_match('/(\d+)\s+pages/', $book['description'], $matches)) {
        $totalPages += (int)$matches[1];
    }
}

// Reading velocity (pages per day this year)
$currentYear = date('Y');
$pagesThisYear = 0;
$booksThisYear = 0;
foreach ($books as $book) {
    if (date('Y', strtotime($book['publish_date'])) == $currentYear) {
        $booksThisYear++;
        if (preg_match('/(\d+)\s+pages/', $book['description'], $matches)) {
            $pagesThisYear += (int)$matches[1];
        }
    }
}
$daysIntoYear = date('z') + 1;
$pagesPerDay = $daysIntoYear > 0 ? round($pagesThisYear / $daysIntoYear, 1) : 0;

// Book rating distribution
$ratings = ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0, '0' => 0];
foreach ($books as $book) {
    $starCount = substr_count($book['title'], '★');
    if ($starCount > 0) {
        $ratings[(string)$starCount]++;
    } else {
        $ratings['0']++;
    }
}

// Reading by year
$byYear = [];
foreach ($books as $book) {
    $year = date('Y', strtotime($book['publish_date']));
    if (!isset($byYear[$year])) {
        $byYear[$year] = 0;
    }
    $byYear[$year]++;
}
krsort($byYear);

// Reading by month (current year)
$currentYear = date('Y');
$byMonth = [];
foreach ($books as $book) {
    $date = strtotime($book['publish_date']);
    if (date('Y', $date) == $currentYear) {
        $month = date('F', $date);
        if (!isset($byMonth[$month])) {
            $byMonth[$month] = 0;
        }
        $byMonth[$month]++;
    }
}

// Average rating
$totalStars = array_sum(array_map(fn($k, $v) => (int)$k * $v, array_keys($ratings), $ratings));
$ratedBooks = $totalBooks - $ratings['0'];
$avgRating = $ratedBooks > 0 ? $totalStars / $ratedBooks : 0;

// Authors count with pages
$authors = [];
$authorPages = [];
foreach ($books as $book) {
    $author = null;
    if (preg_match('/by (.+?) -/', $book['title'], $matches)) {
        $author = trim($matches[1]);
    } elseif (preg_match('/by (.+)$/', $book['title'], $matches)) {
        $author = trim($matches[1]);
    }
    
    if ($author) {
        if (!isset($authors[$author])) {
            $authors[$author] = 0;
            $authorPages[$author] = 0;
        }
        $authors[$author]++;
        
        // Add pages for this author
        if (preg_match('/(\d+)\s+pages/', $book['description'], $matches)) {
            $authorPages[$author] += (int)$matches[1];
        }
    }
}
$totalAuthors = count($authors);

// Top authors by book count
arsort($authors);
$topAuthors = array_slice($authors, 0, 10, true);

// Top authors by pages
arsort($authorPages);
$topAuthorsByPages = array_slice($authorPages, 0, 10, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - MediaLog</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
            color: #1a1a1a;
        }
        
        .top-nav {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
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
            font-size: 28px;
            font-weight: 800;
            color: #d4af37;
            padding: 20px 0;
            text-decoration: none;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #d4af37, #f4d483);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 0;
        }
        
        .nav-links a {
            display: block;
            padding: 20px 18px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            color: #d4af37;
            border-bottom-color: #d4af37;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .page-header h1 {
            font-size: 3em;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .page-header p {
            font-size: 1.2em;
            color: rgba(255,255,255,0.9);
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: white;
        }
        
        .subtitle {
            color: rgba(255,255,255,0.9);
            margin-bottom: 40px;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            color: #d4af37;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            margin-bottom: 30px;
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
                <li><a href="stats.php" class="active">Statistics</a></li>
                <li><a href="insights.php">Insights</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <h1>📊 Media Statistics</h1>
        <div class="subtitle">Books & Movies by the numbers</div>
        
        <h2 style="margin: 40px 0 20px 0; color: #1a1a1a;">📚 Books</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalBooks ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($totalPages) ?></div>
                <div class="stat-label">Total Pages</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $totalAuthors ?></div>
                <div class="stat-label">Authors</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($avgRating, 1) ?> ★</div>
                <div class="stat-label">Avg Rating</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $booksThisYear ?></div>
                <div class="stat-label">Books (<?= $currentYear ?>)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($pagesThisYear) ?></div>
                <div class="stat-label">Pages (<?= $currentYear ?>)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $pagesPerDay ?></div>
                <div class="stat-label">Pages/Day</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $booksWithReviews ?></div>
                <div class="stat-label">With Reviews</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($avgRating, 1) ?> ★</div>
                <div class="stat-label">Avg Rating</div>
            </div>
        </div>
        
        <h2 style="margin: 40px 0 20px 0; color: #1a1a1a;">🎬 Movies</h2>
        
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
                <div class="stat-number"><?= number_format($avgMovieRating, 1) ?> ★</div>
                <div class="stat-label">Avg Rating</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($moviesPerMonth, 1) ?></div>
                <div class="stat-label">Per Month</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $moviesWithReviews ?></div>
                <div class="stat-label">With Reviews</div>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">🎬 Movie Rating Distribution</div>
            <div class="bar-chart">
                <?php 
                $maxMovieCount = max($movieRatings);
                foreach ([5, 4, 3, 2, 1, 0] as $stars): 
                    $count = $movieRatings[(string)$stars];
                    $width = $maxMovieCount > 0 ? ($count / $maxMovieCount) * 100 : 0;
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
        
        <div class="chart-container">
            <div class="chart-title">🎬 Movies Watched by Year</div>
            <div class="bar-chart">
                <?php 
                $maxWatchYear = max($moviesByYear);
                foreach ($moviesByYear as $year => $count): 
                    $width = $maxWatchYear > 0 ? ($count / $maxWatchYear) * 100 : 0;
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
        
        <div class="chart-container">
            <div class="chart-title">🎬 Movies by Decade (Release Year)</div>
            <div class="bar-chart">
                <?php 
                $maxDecade = max($topMovieDecades);
                foreach ($topMovieDecades as $decade => $count): 
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
        
        <div class="chart-container">
            <div class="chart-title">🎬 Top Release Years</div>
            <div class="bar-chart">
                <?php 
                $maxReleaseYear = max($topReleaseYears);
                foreach ($topReleaseYears as $year => $count): 
                    $width = $maxReleaseYear > 0 ? ($count / $maxReleaseYear) * 100 : 0;
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
        
        <h2 style="margin: 40px 0 20px 0; color: #1a1a1a;">📚 Book Charts</h2>
        
        <div class="chart-container">
            <div class="chart-title">Book Rating Distribution</div>
            <div class="bar-chart">
                <?php 
                $maxCount = max($ratings);
                foreach ([5, 4, 3, 2, 1, 0] as $stars): 
                    $count = $ratings[(string)$stars];
                    $width = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                ?>
                    <div class="bar-row">
                        <div class="bar-label">
                            <?= $stars > 0 ? str_repeat('★', $stars) : 'No rating' ?>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $width ?>%">
                                <?= $count ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">Books by Year</div>
            <div class="bar-chart">
                <?php 
                $maxYear = max($byYear);
                foreach ($byYear as $year => $count): 
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
        
        <?php if (!empty($byMonth)): ?>
        <div class="chart-container">
            <div class="chart-title"><?= $currentYear ?> Reading Progress</div>
            <div class="horizontal-chart" style="padding-bottom: 40px;">
                <?php 
                $maxMonth = max($byMonth);
                foreach ($byMonth as $month => $count): 
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
        
        <div class="chart-container">
            <div class="chart-title">📖 Top Authors by Books Read</div>
            <div class="bar-chart">
                <?php 
                $maxAuthor = max($topAuthors);
                foreach ($topAuthors as $author => $count): 
                    $width = $maxAuthor > 0 ? ($count / $maxAuthor) * 100 : 0;
                ?>
                    <div class="bar-row">
                        <div class="bar-label"><?= htmlspecialchars($author) ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $width ?>%">
                                <?= $count ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">📚 Top Authors by Pages Read</div>
            <div class="bar-chart">
                <?php 
                $maxPages = max($topAuthorsByPages);
                foreach ($topAuthorsByPages as $author => $pages): 
                    $width = $maxPages > 0 ? ($pages / $maxPages) * 100 : 0;
                ?>
                    <div class="bar-row">
                        <div class="bar-label"><?= htmlspecialchars($author) ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $width ?>%">
                                <?= number_format($pages) ?> pages
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
