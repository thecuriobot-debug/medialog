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
    SELECT title, publish_date, description, full_content, director, genres, runtime_minutes
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

// Busiest day (most media consumed)
$mediaByDate = [];
foreach ($books as $book) {
    $date = date('Y-m-d', strtotime($book['publish_date']));
    if (!isset($mediaByDate[$date])) {
        $mediaByDate[$date] = ['books' => 0, 'movies' => 0];
    }
    $mediaByDate[$date]['books']++;
}
foreach ($movies as $movie) {
    $date = date('Y-m-d', strtotime($movie['publish_date']));
    if (!isset($mediaByDate[$date])) {
        $mediaByDate[$date] = ['books' => 0, 'movies' => 0];
    }
    $mediaByDate[$date]['movies']++;
}

$busiestDate = null;
$maxMedia = 0;
foreach ($mediaByDate as $date => $counts) {
    $total = $counts['books'] + $counts['movies'];
    if ($total > $maxMedia) {
        $maxMedia = $total;
        $busiestDate = $date;
    }
}

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
    
    // Genre analysis
    if (!empty($movie['genres'])) {
        $genres = explode(',', $movie['genres']);
        foreach ($genres as $genre) {
            $genre = trim($genre);
            if ($genre) {
                if (!isset($moviesByGenre[$genre])) {
                    $moviesByGenre[$genre] = 0;
                }
                $moviesByGenre[$genre]++;
            }
        }
    }
    
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

$pageTitle = "Insights";
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
            
            <?php if ($busiestDate): ?>
            <div class="stat-card highlight">
                <div class="stat-number"><?= $maxMedia ?></div>
                <div class="stat-label">Busiest Day</div>
            </div>
            <?php endif; ?>
            
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
        
        <?php if ($busiestDate): ?>
        <div class="insight-box">
            <h3>🔥 Busiest Media Day</h3>
            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($busiestDate)) ?></p>
            <p><strong>Activity:</strong> <?= $mediaByDate[$busiestDate]['books'] ?> book(s) + <?= $mediaByDate[$busiestDate]['movies'] ?> movie(s) = <?= $maxMedia ?> total</p>
            <p style="margin-top: 10px; color: #666;">
                <?php if ($mediaByDate[$busiestDate]['books'] > 1): ?>
                    📚 Multiple books finished
                <?php endif; ?>
                <?php if ($mediaByDate[$busiestDate]['movies'] > 1): ?>
                    🎬 Movie marathon day
                <?php endif; ?>
                <?php if ($mediaByDate[$busiestDate]['books'] > 0 && $mediaByDate[$busiestDate]['movies'] > 0): ?>
                    | Perfect media balance!
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
        
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
                            <span style="color: #c2185b; font-weight: 700;"><?= $count ?></span>
                        </div>
                        <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #c2185b, #e91e63); height: 100%; width: <?= round(($count / max($topGenres)) * 100) ?>%; transition: width 0.3s;"></div>
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
                            <span style="color: #c2185b; font-weight: 700;"><?= $count ?></span>
                        </div>
                        <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #c2185b, #e91e63); height: 100%; width: <?= round(($count / max($topDirectors)) * 100) ?>%; transition: width 0.3s;"></div>
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
                        <div style="font-size: 2em; font-weight: 700; color: #c2185b;">
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
                        <div style="font-size: 1.8em; font-weight: 700; color: #c2185b;">
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
                        <div style="background: linear-gradient(135deg, #c2185b, #e91e63); width: 80px; border-radius: 8px 8px 0 0; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2em;" 
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
                            <span style="color: #c2185b; font-weight: 700;"><?= $totalMovies ?></span>
                        </div>
                        <div style="background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #c2185b, #e91e63); height: 100%; width: <?= ($totalBooks + $totalMovies) > 0 ? round(($totalMovies / ($totalBooks + $totalMovies)) * 100) : 0 ?>%; display: flex; align-items: center; padding: 0 15px; color: white; font-weight: 700;">
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
    </div>
</body>
</html>
