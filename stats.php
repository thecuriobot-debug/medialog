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

$pageTitle = "Statistics";
$pageStyles = "
    /* Page-specific overrides and additions */
    h1 {
        font-size: 3em;
        color: white;
        margin-bottom: 15px;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
        text-align: center;
    }
    
    h2 {
        font-size: 1.8em;
        color: white;
        margin: 50px 0 25px 0;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        text-align: center;
        padding: 20px;
        background: rgba(255,255,255,0.1);
        border-radius: 12px;
        backdrop-filter: blur(10px);
    }
    
    .subtitle {
        font-size: 1.2em;
        color: rgba(255,255,255,0.9);
        margin-bottom: 30px;
        text-align: center;
    }
    
    /* Adjust font size for large numbers */
    .stat-number {
        font-size: 2em;
        line-height: 1.1;
    }
    
    /* Chart Styles */
    .chart-container {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .chart-title {
        font-size: 1.5em;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 20px;
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
        font-weight: 600;
        color: #666;
        font-size: 0.95em;
    }
    
    .bar-track {
        flex: 1;
        background: #e0e0e0;
        border-radius: 8px;
        height: 35px;
        position: relative;
        overflow: hidden;
    }
    
    .bar-fill {
        background: linear-gradient(135deg, #667eea, #764ba2);
        height: 100%;
        border-radius: 8px;
        display: flex;
        align-items: center;
        padding: 0 15px;
        color: white;
        font-weight: 700;
        font-size: 0.9em;
        transition: width 0.5s ease;
        min-width: fit-content;
    }
    
    .horizontal-chart {
        display: flex;
        align-items: flex-end;
        justify-content: space-around;
        height: 250px;
        gap: 15px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
    }
    
    .column {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        position: relative;
    }
    
    .column-bar {
        width: 100%;
        background: linear-gradient(180deg, #667eea, #764ba2);
        border-radius: 8px 8px 0 0;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .column-bar:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }
    
    .column-value {
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-weight: 700;
        color: #1a1a1a;
        font-size: 0.9em;
    }
    
    .column-label {
        font-size: 0.85em;
        color: #666;
        font-weight: 600;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        h1 {
            font-size: 2em;
        }
        
        h2 {
            font-size: 1.5em;
        }
        
        .bar-label {
            min-width: 80px;
            font-size: 0.85em;
        }
        
        .bar-fill {
            font-size: 0.8em;
            padding: 0 10px;
        }
    }
";
include 'includes/header.php';
?>

<div class="container">
        <h1>📊 Media Statistics</h1>
        <div class="subtitle">Books & Movies by the numbers</div>
        
        <!-- Quick Summary -->
        <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px 25px; border-radius: 15px; margin: 30px 0; display: flex; flex-wrap: wrap; gap: 30px; align-items: center; justify-content: center;">
            <div style="text-align: center;">
                <div style="font-size: 2.5em; font-weight: 800; text-shadow: 0 2px 10px rgba(0,0,0,0.2);"><?= $totalBooks + $totalMovies ?></div>
                <div style="opacity: 0.95; font-size: 0.9em; margin-top: 5px;">Total Media Items</div>
            </div>
            <div style="width: 1px; height: 40px; background: rgba(255,255,255,0.3);"></div>
            <div style="text-align: center;">
                <div style="font-size: 2em; font-weight: 700;">📚 <?= $totalBooks ?></div>
                <div style="opacity: 0.95; font-size: 0.9em; margin-top: 5px;">Books</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2em; font-weight: 700;">🎬 <?= $totalMovies ?></div>
                <div style="opacity: 0.95; font-size: 0.9em; margin-top: 5px;">Movies</div>
            </div>
            <div style="width: 1px; height: 40px; background: rgba(255,255,255,0.3);"></div>
            <div style="text-align: center;">
                <div style="font-size: 2em; font-weight: 700;"><?= date('Y') - 2011 ?> Years</div>
                <div style="opacity: 0.95; font-size: 0.9em; margin-top: 5px;">2011-2025</div>
            </div>
        </div>
        
        <h2>📚 Books</h2>
        
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
        
        <h2>🎬 Movies</h2>
        
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
        
        <h2>📚 Book Charts</h2>
        
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
