<?php
// MediaLog - Shared Navigation Component
// Include this at the top of every page

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Track your reading and watching journey with beautiful insights and statistics">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MediaLog">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icon-192.png">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - MediaLog' : 'MediaLog - Your Letterboxd + Goodreads Tracker'; ?></title>
    <link rel="stylesheet" href="includes/shared-styles.css">
    <script>
        // Register service worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed'));
            });
        }
    </script>
    <style>
        /* MediaLog v2.0.1 - Grid System Fixed */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            overflow-x: hidden;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #667eea 100%);
            min-height: 100vh;
            color: #1a1a1a;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }
        
        /* Navigation */
        .top-nav {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0,0,0,0.3);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            gap: 30px;
            flex-wrap: wrap;
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
            flex-shrink: 0;
        }
        
        .nav-search-container {
            flex: 1;
            max-width: 400px;
            position: relative;
            min-width: 200px;
        }
        
        .nav-search-input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 2px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            border-radius: 25px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .nav-search-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .nav-search-input:focus {
            background: rgba(255,255,255,0.1);
            border-color: #d4af37;
        }
        
        .nav-search-button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #d4af37, #f4d483);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            color: #1a1a1a;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .nav-search-button:hover {
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 2px 10px rgba(212, 175, 55, 0.4);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 5px;
            flex-shrink: 0;
        }
        
        .nav-links a {
            padding: 20px 18px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: #d4af37;
            border-bottom-color: #d4af37;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        
        /* Page Header */
        .page-header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 3em;
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.2em;
            color: rgba(255,255,255,0.9);
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: 800;
            color: #d4af37;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .stat-label {
            font-size: 1.1em;
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Item Grid */
        .item-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 30px !important;
        }
        
        @media (max-width: 1400px) {
            .item-grid {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }
        
        @media (max-width: 1000px) {
            .item-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        @media (max-width: 600px) {
            .item-grid {
                grid-template-columns: 1fr !important;
            }
        }
        
        .item-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            width: 100%;
            max-width: none;
        }
        
        .item-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.25);
        }
        
        .item-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .item-content {
            padding: 20px;
        }
        
        .item-title {
            font-size: 1.2em;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        
        .item-meta {
            font-size: 0.9em;
            color: #666;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-book {
            background: #1976d2;
            color: white;
        }
        
        .badge-movie {
            background: #c2185b;
            color: white;
        }
        
        .badge-gold {
            background: #d4af37;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }
            
            .nav-brand {
                font-size: 24px;
                padding: 10px 0;
            }
            
            .nav-search-container {
                width: 100%;
                max-width: 100%;
                order: 2;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0;
                order: 3;
                width: 100%;
            }
            
            .nav-links a {
                padding: 12px 12px;
                font-size: 11px;
            }
                padding: 15px 0 10px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 2px;
            }
            
            .nav-links a {
                font-size: 11px;
                padding: 12px 10px;
            }
            
            .page-header h1 {
                font-size: 2em;
            }
            
            .container {
                padding: 20px 15px;
            }
        }
        
        /* Page-specific styles */
        <?php if (isset($pageStyles)) echo $pageStyles; ?>
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">MEDIALOG</a>
            
            <!-- Integrated Search Box -->
            <div class="nav-search-container">
                <form action="search.php" method="GET">
                    <input type="text" 
                           name="q" 
                           class="nav-search-input" 
                           placeholder="Search books, movies, authors, directors..." 
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                           autocomplete="off">
                    <button type="submit" class="nav-search-button">🔍</button>
                </form>
            </div>
            
            <div class="nav-links">
                <a href="index.php" class="<?php echo $currentPage == 'index' ? 'active' : ''; ?>">Home</a>
                <a href="books.php" class="<?php echo $currentPage == 'books' ? 'active' : ''; ?>">Books</a>
                <a href="movies.php" class="<?php echo $currentPage == 'movies' ? 'active' : ''; ?>">Movies</a>
                <a href="creators.php" class="<?php echo $currentPage == 'creators' ? 'active' : ''; ?>">Creators</a>
                <a href="stats.php" class="<?php echo $currentPage == 'stats' ? 'active' : ''; ?>">Stats</a>
                <a href="insights.php" class="<?php echo $currentPage == 'insights' ? 'active' : ''; ?>">Insights</a>
                <a href="lists.php" class="<?php echo $currentPage == 'lists' ? 'active' : ''; ?>">Lists</a>
                <a href="goals.php" class="<?php echo $currentPage == 'goals' ? 'active' : ''; ?>">Goals</a>
                <a href="settings.php" class="<?php echo $currentPage == 'settings' ? 'active' : ''; ?>">⚙️</a>
            </div>
        </div>
    </nav>
