<?php
// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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

// Fallback logic: if current year data is 0, use previous year
$displayYear = $currentYear;
$displayBooksThisYear = $booksThisYear;
$displayMoviesThisYear = $moviesThisYear;
$displayPagesThisYear = $totalPages;
$displayBooksPerDay = $booksPerDay;
$displayMoviesPerDay = $moviesPerDay;
$displayPagesPerDay = $pagesPerDay;

if ($booksThisYear == 0 || $moviesThisYear == 0) {
    // Try previous year
    $prevYear = $currentYear - 1;
    $prevYearBooks = 0;
    $prevYearMovies = 0;
    $prevYearPages = 0;
    
    foreach ($books as $book) {
        if (date('Y', strtotime($book['publish_date'])) == $prevYear) {
            $prevYearBooks++;
            if (preg_match('/(\d+)\s+pages/', $book['description'], $matches)) {
                $prevYearPages += (int)$matches[1];
            }
        }
    }
    
    foreach ($movies as $movie) {
        if (date('Y', strtotime($movie['publish_date'])) == $prevYear) {
            $prevYearMovies++;
        }
    }
    
    if ($prevYearBooks > 0 || $prevYearMovies > 0) {
        $displayYear = $prevYear;
        $displayBooksThisYear = $prevYearBooks;
        $displayMoviesThisYear = $prevYearMovies;
        $displayPagesThisYear = $prevYearPages;
        $displayBooksPerDay = round($prevYearBooks / 365, 2);
        $displayMoviesPerDay = round($prevYearMovies / 365, 2);
        $displayPagesPerDay = round($prevYearPages / 365, 1);
    }
}

// Projections (use display values)
$daysInYear = 365;
$projectedBooks = round($displayBooksPerDay * $daysInYear);
$projectedMovies = round($displayMoviesPerDay * $daysInYear);
$projectedPages = round($displayPagesPerDay * $daysInYear);

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

// NEW: Combined Media Insights
$bookDates = [];
$movieDates = [];

foreach ($books as $book) {
    $bookDates[] = date('Y-m-d', strtotime($book['publish_date']));
}
foreach ($movies as $movie) {
    $movieDates[] = date('Y-m-d', strtotime($movie['publish_date']));
}

// Days with both book and movie
$bothDays = array_intersect($bookDates, $movieDates);
$daysWithBoth = count(array_unique($bothDays));

// Days with only books
$onlyBookDays = count(array_unique(array_diff($bookDates, $movieDates)));

// Days with only movies
$onlyMovieDays = count(array_unique(array_diff($movieDates, $bookDates)));


// ==========================================
// VISUALIZATION DATA PREPARATION
// ==========================================

// Get rating distribution for visualizations
$ratingDistBooks = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
$ratingDistMovies = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];

foreach ($books as $book) {
    $stars = substr_count($book['title'], '★');
    if ($stars >= 1 && $stars <= 5) {
        $ratingDistBooks[$stars]++;
    }
}

foreach ($movies as $movie) {
    $stars = substr_count($movie['title'], '★');
    if ($stars >= 1 && $stars <= 5) {
        $ratingDistMovies[$stars]++;
    }
}

// Get top genres (from movie descriptions/genres field) - DISABLED: genres column doesn't exist
$genreCounts = [];
// TODO: Extract genres from descriptions when available
$topGenres = [];

// Get yearly comparison data
$yearlyData = [];
$stmt = $pdo->query("SELECT YEAR(publish_date) as year, site_id, COUNT(*) as count 
                     FROM posts 
                     WHERE YEAR(publish_date) >= " . ($currentYear - 4) . "
                     GROUP BY YEAR(publish_date), site_id 
                     ORDER BY year, site_id");
while ($row = $stmt->fetch()) {
    $year = $row['year'];
    if (!isset($yearlyData[$year])) {
        $yearlyData[$year] = ['books' => 0, 'movies' => 0];
    }
    if ($row['site_id'] == 7) {
        $yearlyData[$year]['books'] = $row['count'];
    } else if ($row['site_id'] == 6) {
        $yearlyData[$year]['movies'] = $row['count'];
    }
}

// Find display year for monthly pace (same logic as visualizations.php)
$displayYear = $currentYear;
$currentYearCountViz = $pdo->query("SELECT COUNT(*) as c FROM posts WHERE YEAR(publish_date) = $currentYear")->fetch()['c'];
if ($currentYearCountViz == 0) {
    $displayYear = $currentYear - 1;
    $lastYearCount = $pdo->query("SELECT COUNT(*) as c FROM posts WHERE YEAR(publish_date) = $displayYear")->fetch()['c'];
    if ($lastYearCount == 0) {
        $mostRecentYear = $pdo->query("SELECT YEAR(publish_date) as year FROM posts WHERE YEAR(publish_date) > 0 ORDER BY publish_date DESC LIMIT 1")->fetch()['year'];
        if ($mostRecentYear) {
            $displayYear = $mostRecentYear;
        }
    }
}

// Calculate max values for charts
$maxRating = max(array_merge($ratingDistBooks, $ratingDistMovies)) ?: 1;
$maxGenre = !empty($topGenres) ? max($topGenres) : 1;
$maxYearly = 0;
foreach ($yearlyData as $data) {
    $maxYearly = max($maxYearly, $data['books'], $data['movies']);
}
$maxYearly = $maxYearly ?: 1;
$maxMonthly = max(array_merge($monthlyBooks, $monthlyMovies)) ?: 1;

// Average movies per book
$avgMoviesPerBook = count($books) > 0 ? round(count($movies) / count($books), 2) : 0;

// Movie runtime insights (if data available)
$totalRuntime = 0;
$moviesWithRuntime = 0;
foreach ($movies as $movie) {
    // Try to extract runtime from description or other field
    if (isset($movie['runtime_minutes']) && $movie['runtime_minutes'] > 0) {
        $totalRuntime += $movie['runtime_minutes'];
        $moviesWithRuntime++;
    }
}
$avgMovieRuntime = $moviesWithRuntime > 0 ? round($totalRuntime / $moviesWithRuntime) : 0;
$totalHoursWatched = round($totalRuntime / 60, 1);

// Get totals for footer
$totalBooks = count($books);
$totalMovies = count($movies);

// Movie-specific insights
$moviesByRating = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 0];
$moviesByDirector = [];
$moviesByGenre = [];
$moviesByDecade = [];

foreach ($movies as $movie) {
    // Rating analysis
    $stars = substr_count($movie['title'], '★');
    if (isset($moviesByRating[$stars])) {
        $moviesByRating[$stars]++;
    }
    
    // Director analysis
    if (!empty($movie['director'])) {
        $director = $movie['director'];
        if (!isset($moviesByDirector[$director])) {
            $moviesByDirector[$director] = 0;
        }
        $moviesByDirector[$director]++;
    }
    
    // Genre analysis - DISABLED (genres column doesn't exist)
    // TODO: Extract genres from description when available
    
    // Decade analysis
    if (preg_match('/\b(19\d{2}|20\d{2})\b/', $movie['title'], $matches)) {
        $year = (int)$matches[1];
        $decade = floor($year / 10) * 10;
        $decadeLabel = $decade . 's';
        if (!isset($moviesByDecade[$decadeLabel])) {
            $moviesByDecade[$decadeLabel] = 0;
        }
        $moviesByDecade[$decadeLabel]++;
    }
}

// Sort and get top items
arsort($moviesByDirector);
$topDirectors = array_slice($moviesByDirector, 0, 5, true);

arsort($moviesByGenre);
$topGenres = array_slice($moviesByGenre, 0, 5, true);

krsort($moviesByDecade);

// Book-specific insights
$booksByRating = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 0];
$booksByAuthor = [];

foreach ($books as $book) {
    // Rating analysis
    $stars = substr_count($book['title'], '★');
    if (isset($booksByRating[$stars])) {
        $booksByRating[$stars]++;
    }
    
    // Author analysis
    if (preg_match('/by ([^-]+) -/', $book['title'], $matches)) {
        $author = trim($matches[1]);
        if (!isset($booksByAuthor[$author])) {
            $booksByAuthor[$author] = 0;
        }
        $booksByAuthor[$author]++;
    }
}

arsort($booksByAuthor);
$topAuthors = array_slice($booksByAuthor, 0, 5, true);

// Rating distribution comparison
$avgBookRating = 0;
$avgMovieRating = 0;
$totalBookRatings = 0;
$totalMovieRatings = 0;

foreach ($booksByRating as $stars => $count) {
    if ($stars > 0) {
        $avgBookRating += $stars * $count;
        $totalBookRatings += $count;
    }
}
$avgBookRating = $totalBookRatings > 0 ? round($avgBookRating / $totalBookRatings, 2) : 0;

foreach ($moviesByRating as $stars => $count) {
    if ($stars > 0) {
        $avgMovieRating += $stars * $count;
        $totalMovieRatings += $count;
    }
}
$avgMovieRating = $totalMovieRatings > 0 ? round($avgMovieRating / $totalMovieRatings, 2) : 0;
?>
<?php
$pageTitle = "Insights";
$pageStyles = "
    /* MediaLog Insights - Updated: " . date('Y-m-d H:i:s') . " */
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
        margin-bottom: 40px;
        text-align: center;
    }
    
    /* Override stat-card styles for insights page - Glass morphism design */
    .stat-card {
        background: rgba(139, 148, 228, 0.15);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(139, 148, 228, 0.3);
        padding: 30px 25px;
        border-radius: 15px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .stat-card:hover {
        background: rgba(139, 148, 228, 0.2);
        border-color: rgba(139, 148, 228, 0.4);
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .stat-card .stat-number {
        font-size: 3em;
        font-weight: 800;
        color: #fbbf24;
        margin-bottom: 10px;
        text-shadow: 0 2px 10px rgba(251, 191, 36, 0.4);
    }
    
    .stat-card .stat-label {
        font-size: 0.85em;
        color: rgba(255, 255, 255, 0.95);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-weight: 600;
    }
    
    .stat-card.highlight {
        background: rgba(167, 139, 250, 0.2);
        border: 2px solid rgba(167, 139, 250, 0.5);
    }
    
    .stat-card.highlight:hover {
        background: rgba(167, 139, 250, 0.25);
        border-color: rgba(167, 139, 250, 0.6);
    }
    
    .stat-card.highlight .stat-number {
        color: white;
        text-shadow: 0 2px 10px rgba(255, 255, 255, 0.3);
    }
    
    /* Chart and Container Styles */
    .chart-container {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .chart-container h3 {
        color: #1a1a1a;
        margin-bottom: 20px;
        font-size: 1.3em;
    }
    
    .chart-title {
        font-size: 1.5em;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 20px;
        text-align: center;
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
    
    .comparison-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .insight-box {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .insight-box h3 {
        color: #1a1a1a;
        margin-bottom: 15px;
        font-size: 1.3em;
    }
    
    .insight-box p {
        color: #666;
        line-height: 1.6;
        margin-bottom: 10px;
    }
    
    .month-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .month-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
    }
    
    .month-label {
        font-size: 0.85em;
        color: #666;
        margin-bottom: 8px;
    }
    
    .month-count {
        font-size: 1.8em;
        font-weight: 700;
        color: #667eea;
    }
    
    /* Books vs Movies by Month Chart Styles */
    .dual-line-chart {
        padding: 20px 0;
    }
    
    .chart-lines {
        display: flex;
        align-items: flex-end;
        justify-content: space-around;
        height: 250px;
        gap: 8px;
        margin-bottom: 20px;
    }
    
    .month-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
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
        background: linear-gradient(to top, #d4af37, #f4d483);
    }
    
    .bar.movies {
        background: linear-gradient(to top, #667eea, #764ba2);
    }
    
    .bar:hover {
        opacity: 0.8;
        transform: scaleY(1.05);
    }
    
    .legend {
        display: flex;
        gap: 30px;
        justify-content: center;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95em;
        color: #666;
    }
    
    .legend-color {
        width: 24px;
        height: 24px;
        border-radius: 4px;
    }
    
    @media (max-width: 768px) {
        h1 {
            font-size: 2em;
        }
        
        h2 {
            font-size: 1.5em;
            margin: 30px 0 20px 0;
        }
        
        .bar-label {
            min-width: 80px;
            font-size: 0.85em;
        }
        
        .bar-fill {
            font-size: 0.8em;
            padding: 0 10px;
        }
        
        .comparison-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Visualization Cards Styles */
    .viz-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
        margin-top: 0;
    }
    
    .viz-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .viz-card h2 {
        color: #667eea;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e0e0e0;
        font-size: 1.5em;
    }
    
    .bar-chart {
        margin: 20px 0;
    }
    
    .bar-row {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        gap: 15px;
    }
    
    .bar-label {
        min-width: 80px;
        font-weight: 600;
        color: #666;
        font-size: 0.9em;
    }
    
    .bar-container {
        flex: 1;
        height: 30px;
        background: #f0f0f0;
        border-radius: 15px;
        overflow: hidden;
        position: relative;
    }
    
    .bar-fill {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.85em;
        transition: width 0.3s ease;
    }
    
    .bar-fill.books {
        background: linear-gradient(135deg, #d4af37, #f4d483);
    }
    
    .bar-fill.movies {
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
    
    .bar-value {
        min-width: 40px;
        text-align: right;
        font-weight: 700;
        color: #667eea;
    }
    
    .legend {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 2px solid #e0e0e0;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .legend-color {
        width: 30px;
        height: 20px;
        border-radius: 5px;
    }
    
    .legend-color.books {
        background: linear-gradient(135deg, #d4af37, #f4d483);
    }
    
    .legend-color.movies {
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
";
include 'includes/header.php';
?>

    
    <div class="container">
        <h1>📊 Advanced Insights</h1>
        <div class="subtitle">Deep analytics on your media consumption patterns</div>
        
        <h2>🔥 Current Pace <?php if ($displayYear != date('Y')) echo "({$displayYear} data)"; ?></h2>
        
        <div class="stats-grid">
            <div class="stat-card highlight">
                <div class="stat-number"><?= $displayBooksPerDay ?></div>
                <div class="stat-label">Books/Day</div>
            </div>
            
            <div class="stat-card highlight">
                <div class="stat-number"><?= $displayMoviesPerDay ?></div>
                <div class="stat-label">Movies/Day</div>
            </div>
            
            <div class="stat-card highlight">
                <div class="stat-number"><?= $displayPagesPerDay ?></div>
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
        
        <h2>📈 <?= $displayYear ?> Projections<?php if ($displayYear != date('Y')) echo " (based on {$displayYear} data)"; ?></h2>
        
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
                <div class="stat-number"><?= $displayBooksThisYear + $displayMoviesThisYear ?> / <?= $projectedBooks + $projectedMovies ?></div>
                <div class="stat-label">Total Media</div>
            </div>
        </div>
        
        <div class="insight-box">
            <h3>💡 Key Insights</h3>
            <p><strong>Peak Book Month:</strong> <?= $monthNames[$peakBookMonth - 1] ?> (<?= $monthlyBooks[$peakBookMonth] ?> books)</p>
            <p><strong>Peak Movie Month:</strong> <?= $monthNames[$peakMovieMonth - 1] ?> (<?= $monthlyMovies[$peakMovieMonth] ?> movies)</p>
            <p><strong>Peak Reading Month:</strong> <?= $monthNames[$peakPagesMonth - 1] ?> (<?= number_format($monthlyPages[$peakPagesMonth]) ?> pages)</p>
        </div>
        
        <h2>🎬📚 Combined Media Insights</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $daysWithBoth ?></div>
                <div class="stat-label">Days with Both</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $onlyBookDays ?></div>
                <div class="stat-label">Book-Only Days</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $onlyMovieDays ?></div>
                <div class="stat-label">Movie-Only Days</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $avgMoviesPerBook ?></div>
                <div class="stat-label">Movies/Book Ratio</div>
            </div>
            
            <?php if ($totalHoursWatched > 0): ?>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($totalHoursWatched) ?> hrs</div>
                <div class="stat-label">Movies Watched</div>
            </div>
            <?php endif; ?>
            
            <?php if ($avgMovieRuntime > 0): ?>
            <div class="stat-card">
                <div class="stat-number"><?= $avgMovieRuntime ?> min</div>
                <div class="stat-label">Avg Movie Length</div>
            </div>
            <?php endif; ?>
        </div>
        
        
        <h2>🎬 Movie Deep Dive</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalMovies ?></div>
                <div class="stat-label">Total Movies</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= str_repeat('★', floor($avgMovieRating)) . (fmod($avgMovieRating, 1) >= 0.5 ? '½' : '') ?></div>
                <div class="stat-label">Avg Rating</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= count($topGenres) ?></div>
                <div class="stat-label">Unique Genres</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= count($moviesByDirector) ?></div>
                <div class="stat-label">Unique Directors</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= count($moviesByDecade) ?></div>
                <div class="stat-label">Decades Covered</div>
            </div>
        </div>
        
        <div class="comparison-grid">
            <div class="chart-container">
                <h3 style="margin-bottom: 20px;">🎭 Top Genres</h3>
                <?php foreach ($topGenres as $genre => $count): ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-weight: 600;"><?= htmlspecialchars($genre) ?></span>
                            <span style="color: #667eea; font-weight: 700;"><?= $count ?></span>
                        </div>
                        <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #667eea, #764ba2); height: 100%; width: <?= round(($count / max($topGenres)) * 100) ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chart-container">
                <h3 style="margin-bottom: 20px;">🎬 Top Directors</h3>
                <?php foreach ($topDirectors as $director => $count): ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-weight: 600;"><?= htmlspecialchars(substr($director, 0, 30)) ?></span>
                            <span style="color: #667eea; font-weight: 700;"><?= $count ?></span>
                        </div>
                        <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #667eea, #764ba2); height: 100%; width: <?= round(($count / max($topDirectors)) * 100) ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="insight-box">
            <h3>🎯 Movie Rating Distribution</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 20px; margin-top: 15px;">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <div style="text-align: center;">
                        <div style="color: #d4af37; font-size: 1.5em; margin-bottom: 5px;">
                            <?= str_repeat('★', $i) ?>
                        </div>
                        <div style="font-size: 2em; font-weight: 700; color: #667eea;">
                            <?= $moviesByRating[$i] ?>
                        </div>
                        <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                            <?= $totalMovieRatings > 0 ? round(($moviesByRating[$i] / $totalMovieRatings) * 100) : 0 ?>%
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <?php if (count($moviesByDecade) > 0): ?>
        <div class="chart-container">
            <h3 style="margin-bottom: 20px;">📅 Movies by Decade</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 15px;">
                <?php foreach ($moviesByDecade as $decade => $count): ?>
                    <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div style="font-size: 1.8em; font-weight: 700; color: #667eea;">
                            <?= $count ?>
                        </div>
                        <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                            <?= $decade ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <h2>📚 Book Deep Dive</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalBooks ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= str_repeat('★', floor($avgBookRating)) . (fmod($avgBookRating, 1) >= 0.5 ? '½' : '') ?></div>
                <div class="stat-label">Avg Rating</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= count($booksByAuthor) ?></div>
                <div class="stat-label">Unique Authors</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($displayPagesThisYear) ?></div>
                <div class="stat-label">Pages (<?= $displayYear ?>)</div>
            </div>
        </div>
        
        <div class="comparison-grid">
            <div class="chart-container">
                <h3 style="margin-bottom: 20px;">✍️ Top Authors</h3>
                <?php foreach ($topAuthors as $author => $count): ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-weight: 600;"><?= htmlspecialchars(substr($author, 0, 30)) ?></span>
                            <span style="color: #1976d2; font-weight: 700;"><?= $count ?></span>
                        </div>
                        <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #1976d2, #2196f3); height: 100%; width: <?= round(($count / max($topAuthors)) * 100) ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chart-container">
                <h3 style="margin-bottom: 20px;">📊 Book Rating Distribution</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="color: #d4af37; min-width: 100px;">
                                <?= str_repeat('★', $i) ?>
                            </div>
                            <div style="flex: 1; background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
                                <div style="background: linear-gradient(135deg, #1976d2, #2196f3); height: 100%; width: <?= $totalBookRatings > 0 ? round(($booksByRating[$i] / $totalBookRatings) * 100) : 0 ?>%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.85em;">
                                    <?php if ($booksByRating[$i] > 0): ?>
                                        <?= $booksByRating[$i] ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <h2>⚖️ Books vs Movies Comparison</h2>
        
        <div class="comparison-grid">
            <div class="chart-container">
                <h3 style="margin-bottom: 20px;">📊 Rating Comparison</h3>
                <div style="display: flex; justify-content: space-around; align-items: flex-end; height: 200px; padding: 20px 0;">
                    <div style="text-align: center;">
                        <div style="background: linear-gradient(135deg, #1976d2, #2196f3); width: 80px; border-radius: 8px 8px 0 0; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2em;" 
                             style="height: <?= $avgBookRating * 40 ?>px;">
                            <?= $avgBookRating ?>
                        </div>
                        <div style="margin-top: 10px; font-weight: 600;">Books</div>
                        <div style="color: #d4af37; margin-top: 5px;"><?= str_repeat('★', floor($avgBookRating)) ?></div>
                    </div>
                    <div style="text-align: center;">
                        <div style="background: linear-gradient(135deg, #667eea, #764ba2); width: 80px; border-radius: 8px 8px 0 0; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2em;" 
                             style="height: <?= $avgMovieRating * 40 ?>px;">
                            <?= $avgMovieRating ?>
                        </div>
                        <div style="margin-top: 10px; font-weight: 600;">Movies</div>
                        <div style="color: #d4af37; margin-top: 5px;"><?= str_repeat('★', floor($avgMovieRating)) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="chart-container">
                <h3 style="margin-bottom: 20px;">📈 Volume Comparison</h3>
                <div style="display: flex; flex-direction: column; gap: 15px; padding: 20px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">📚 Books</span>
                            <span style="color: #1976d2; font-weight: 700;"><?= $totalBooks ?></span>
                        </div>
                        <div style="background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #1976d2, #2196f3); height: 100%; width: <?= ($totalBooks + $totalMovies) > 0 ? round(($totalBooks / ($totalBooks + $totalMovies)) * 100) : 0 ?>%; display: flex; align-items: center; padding: 0 15px; color: white; font-weight: 700;">
                                <?= ($totalBooks + $totalMovies) > 0 ? round(($totalBooks / ($totalBooks + $totalMovies)) * 100) : 0 ?>%
                            </div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">🎬 Movies</span>
                            <span style="color: #667eea; font-weight: 700;"><?= $totalMovies ?></span>
                        </div>
                        <div style="background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #667eea, #764ba2); height: 100%; width: <?= ($totalBooks + $totalMovies) > 0 ? round(($totalMovies / ($totalBooks + $totalMovies)) * 100) : 0 ?>%; display: flex; align-items: center; padding: 0 15px; color: white; font-weight: 700;">
                                <?= ($totalBooks + $totalMovies) > 0 ? round(($totalMovies / ($totalBooks + $totalMovies)) * 100) : 0 ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    <!-- VISUALIZATIONS SECTION -->
    <div style="margin-top: 40px;">
        <!-- Rating Distribution -->
        <div class="viz-grid">
            <div class="viz-card">
                <h2>⭐ Book Ratings</h2>
                <div class="bar-chart">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="bar-row">
                            <div class="bar-label"><?php echo str_repeat('★', $i); ?></div>
                            <div class="bar-container">
                                <div class="bar-fill books" style="width: <?php echo ($ratingDistBooks[$i] / $maxRating * 100); ?>%;">
                                    <?php if ($ratingDistBooks[$i] > 0) echo $ratingDistBooks[$i]; ?>
                                </div>
                            </div>
                            <div class="bar-value"><?php echo $ratingDistBooks[$i]; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="viz-card">
                <h2>⭐ Movie Ratings</h2>
                <div class="bar-chart">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="bar-row">
                            <div class="bar-label"><?php echo str_repeat('★', $i); ?></div>
                            <div class="bar-container">
                                <div class="bar-fill movies" style="width: <?php echo ($ratingDistMovies[$i] / $maxRating * 100); ?>%;">
                                    <?php if ($ratingDistMovies[$i] > 0) echo $ratingDistMovies[$i]; ?>
                                </div>
                            </div>
                            <div class="bar-value"><?php echo $ratingDistMovies[$i]; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <!-- Top Genres -->
        <?php if (!empty($topGenres)): ?>
        <div class="viz-card" style="margin-bottom: 30px;">
            <h2>🎬 Top Movie Genres</h2>
            <div class="bar-chart">
                <?php foreach ($topGenres as $genre => $count): ?>
                    <div class="bar-row">
                        <div class="bar-label"><?php echo htmlspecialchars($genre); ?></div>
                        <div class="bar-container">
                            <div class="bar-fill movies" style="width: <?php echo ($count / $maxGenre * 100); ?>%;">
                                <?php if ($count >= $maxGenre * 0.15) echo $count; ?>
                            </div>
                        </div>
                        <div class="bar-value"><?php echo $count; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Yearly Comparison -->
        <?php if (!empty($yearlyData)): ?>
        <div class="viz-card">
            <h2>📈 Year-over-Year Comparison</h2>
            <div class="bar-chart">
                <?php foreach ($yearlyData as $year => $data): ?>
                    <div class="bar-row">
                        <div class="bar-label"><?php echo $year; ?></div>
                        <div class="bar-container">
                            <?php if ($data['books'] > 0): ?>
                                <div class="bar-fill books" style="width: <?php echo ($data['books'] / $maxYearly * 100); ?>%; float: left;">
                                    <?php if ($data['books'] >= $maxYearly * 0.12) echo $data['books']; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($data['movies'] > 0): ?>
                                <div class="bar-fill movies" style="width: <?php echo ($data['movies'] / $maxYearly * 100); ?>%; float: left; margin-left: 2px;">
                                    <?php if ($data['movies'] >= $maxYearly * 0.12) echo $data['movies']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="bar-value"><?php echo ($data['books'] + $data['movies']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color books"></div>
                    <span>Books</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color movies"></div>
                    <span>Movies</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Monthly Pace Chart -->
        <div class="viz-card" style="margin-top: 60px; margin-bottom: 30px;">
            <h2>📅 <?php echo $displayYear; ?> Monthly Pace</h2>
            <?php if ($displayYear != date('Y')): ?>
                <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">
                    <em>Showing <?php echo $displayYear; ?> data (no activity in <?php echo date('Y'); ?> yet)</em>
                </p>
            <?php endif; ?>
            
            <?php 
            $totalItems = array_sum($monthlyBooks) + array_sum($monthlyMovies);
            if ($totalItems == 0): ?>
                <div style="padding: 40px; text-align: center; color: #666; background: #f8f9fa; border-radius: 12px; margin-bottom: 20px;">
                    <p style="font-size: 1.2em; margin-bottom: 10px;">📊 No data available for <?php echo $displayYear; ?></p>
                    <p style="font-size: 0.9em;">Start logging your books and movies to see your monthly pace!</p>
                </div>
            <?php endif; ?>
            
            <div class="bar-chart">
                <?php 
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                for ($i = 1; $i <= 12; $i++): 
                    $booksMonth = $monthlyBooks[$i];
                    $moviesMonth = $monthlyMovies[$i];
                    $total = $booksMonth + $moviesMonth;
                ?>
                    <div class="bar-row">
                        <div class="bar-label"><?php echo $months[$i-1]; ?></div>
                        <div class="bar-container">
                            <?php if ($booksMonth > 0): ?>
                                <div class="bar-fill books" style="width: <?php echo ($booksMonth / $maxMonthly * 100); ?>%; float: left;">
                                    <?php if ($booksMonth >= $maxMonthly * 0.15) echo $booksMonth; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($moviesMonth > 0): ?>
                                <div class="bar-fill movies" style="width: <?php echo ($moviesMonth / $maxMonthly * 100); ?>%; float: left; margin-left: 2px;">
                                    <?php if ($moviesMonth >= $maxMonthly * 0.15) echo $moviesMonth; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="bar-value"><?php echo $total; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color books"></div>
                    <span>Books</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color movies"></div>
                    <span>Movies</span>
                </div>
            </div>
        </div>
    </div>
    
</div> <!-- Close main container -->
</body>
</html>
