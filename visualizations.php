<?php
require_once 'config.php';
$pdo = getDB();

$currentYear = date('Y');

// Check if current year has any data
$currentYearCount = $pdo->query("SELECT COUNT(*) as c FROM posts 
                                 WHERE YEAR(publish_date) = $currentYear")->fetch()['c'];

// If current year is empty, use last year
$displayYear = $currentYear;
if ($currentYearCount == 0) {
    $displayYear = $currentYear - 1;
    
    // Check if last year has data, otherwise go to most recent year with data
    $lastYearCount = $pdo->query("SELECT COUNT(*) as c FROM posts 
                                  WHERE YEAR(publish_date) = $displayYear")->fetch()['c'];
    
    if ($lastYearCount == 0) {
        // Find the most recent year that has data
        $mostRecentYear = $pdo->query("SELECT YEAR(publish_date) as year FROM posts 
                                       WHERE YEAR(publish_date) > 0 
                                       ORDER BY publish_date DESC LIMIT 1")->fetch()['year'];
        if ($mostRecentYear) {
            $displayYear = $mostRecentYear;
        }
    }
}

// Get reading/watching pace data
$monthlyBooks = [];
$monthlyMovies = [];
for ($month = 1; $month <= 12; $month++) {
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    
    $books = $pdo->query("SELECT COUNT(*) as c FROM posts 
                          WHERE site_id = 7 
                          AND YEAR(publish_date) = $displayYear 
                          AND MONTH(publish_date) = $month")->fetch()['c'];
    
    $movies = $pdo->query("SELECT COUNT(*) as c FROM posts 
                           WHERE site_id = 6 
                           AND YEAR(publish_date) = $displayYear 
                           AND MONTH(publish_date) = $month")->fetch()['c'];
    
    $monthlyBooks[$month] = $books;
    $monthlyMovies[$month] = $movies;
}

// Get rating distribution
$ratingDistBooks = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
$ratingDistMovies = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];

$stmt = $pdo->query("SELECT title FROM posts WHERE site_id = 7");
while ($row = $stmt->fetch()) {
    $stars = substr_count($row['title'], '★');
    if ($stars >= 1 && $stars <= 5) {
        $ratingDistBooks[$stars]++;
    }
}

$stmt = $pdo->query("SELECT title FROM posts WHERE site_id = 6");
while ($row = $stmt->fetch()) {
    $stars = substr_count($row['title'], '★');
    if ($stars >= 1 && $stars <= 5) {
        $ratingDistMovies[$stars]++;
    }
}

// Get top genres (from movie descriptions) - DISABLED: genres column doesn't exist
$genreCounts = [];
// TODO: Extract genres from descriptions or titles when available
$topGenres = [];

// Get yearly comparison
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

$pageTitle = "Visualizations";
$pageStyles = "
    .viz-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .viz-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
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
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 15px;
        display: flex;
        align-items: center;
        padding-left: 10px;
        color: white;
        font-weight: 600;
        font-size: 0.85em;
        transition: width 0.5s ease;
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
        gap: 20px;
        margin-top: 15px;
        justify-content: center;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9em;
        font-weight: 600;
    }
    
    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 4px;
    }
    
    .legend-color.books {
        background: linear-gradient(135deg, #d4af37, #f4d483);
    }
    
    .legend-color.movies {
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
    
    .stat-highlight {
        display: inline-block;
        padding: 5px 15px;
        background: #f0f0f0;
        border-radius: 20px;
        font-weight: 700;
        color: #667eea;
        margin: 5px;
    }
    
    @media (max-width: 768px) {
        .viz-grid {
            grid-template-columns: 1fr;
        }
    }
";

include 'includes/header.php';

// Calculate totals for charts
$maxMonthly = max(array_merge($monthlyBooks, $monthlyMovies)) ?: 1;
$maxRating = max(array_merge($ratingDistBooks, $ratingDistMovies)) ?: 1;
$maxGenre = max($topGenres) ?: 1;
$maxYearly = 0;
foreach ($yearlyData as $data) {
    $maxYearly = max($maxYearly, $data['books'], $data['movies']);
}
$maxYearly = $maxYearly ?: 1;
?>

<div class="container viz-container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">📊 Visualizations</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Visual insights into your reading and watching habits</p>
    </div>
    
    <!-- Deprecation Notice -->
    <div style="background: linear-gradient(135deg, #f093fb, #f5576c); padding: 30px; border-radius: 15px; margin-bottom: 40px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
        <h2 style="color: white; font-size: 1.8em; margin-bottom: 15px;">⚠️ Page Deprecated</h2>
        <p style="color: white; font-size: 1.1em; line-height: 1.6; margin-bottom: 20px;">
            This page has been deprecated. All visualizations are now available on the <strong>Insights</strong> page.
        </p>
        <a href="insights.php" style="display: inline-block; background: white; color: #f5576c; padding: 15px 40px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 1.1em; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s;" 
           onmouseover="this.style.transform='scale(1.05)'" 
           onmouseout="this.style.transform='scale(1)'">
            Go to Insights →
        </a>
    </div>
    
    
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
    
    <!-- Monthly Pace Chart (moved to end) -->
    <div class="viz-card" style="margin-top: 60px; margin-bottom: 30px;">
        <h2>📅 <?php echo $displayYear; ?> Monthly Pace</h2>
        <?php if ($displayYear != $currentYear): ?>
            <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">
                <em>Showing <?php echo $displayYear; ?> data (no activity in <?php echo $currentYear; ?> yet)</em>
            </p>
        <?php endif; ?>
        
        <!-- Debug info -->
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
                $books = $monthlyBooks[$i];
                $movies = $monthlyMovies[$i];
                $total = $books + $movies;
            ?>
                <div class="bar-row">
                    <div class="bar-label"><?php echo $months[$i-1]; ?></div>
                    <div class="bar-container">
                        <?php if ($books > 0): ?>
                            <div class="bar-fill books" style="width: <?php echo ($books / $maxMonthly * 100); ?>%; float: left;">
                                <?php if ($books >= $maxMonthly * 0.15) echo $books; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($movies > 0): ?>
                            <div class="bar-fill movies" style="width: <?php echo ($movies / $maxMonthly * 100); ?>%; float: left; margin-left: 2px;">
                                <?php if ($movies >= $maxMonthly * 0.15) echo $movies; ?>
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

<?php include 'includes/footer.php'; ?>
</body>
</html>
