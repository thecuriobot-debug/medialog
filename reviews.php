<?php
require_once 'config.php';

// Fetch all Goodreads reviews with content
$pdo = getDB();
$stmt = $pdo->query("
    SELECT p.*
    FROM posts p
    WHERE p.site_id = 7 
    AND (p.full_content IS NOT NULL AND p.full_content != '')
    ORDER BY p.publish_date DESC
");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Book Reviews";
$pageStyles = "
    h1 {
        font-size: 3em;
        color: white;
        margin-bottom: 15px;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }
    
    .subtitle {
        font-size: 1.2em;
        color: rgba(255,255,255,0.9);
        margin-bottom: 30px;
        text-align: center;
    }
    
    .count {
        text-align: center;
        font-size: 1.5em;
        color: white;
        margin-bottom: 30px;
        font-weight: 700;
    }
    
    .reviews-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 320px));
        gap: 30px;
        margin-top: 30px;
        justify-content: center;
    }
    
    .review-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    
    .review-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }
    
    .review-card img {
        width: 100%;
        height: 350px;
        object-fit: cover;
    }
    
    .review-content {
        padding: 20px;
    }
    
    .review-card h3 {
        font-size: 1.2em;
        margin-bottom: 10px;
        color: #1a1a1a;
    }
    
    .review-card h3 a {
        color: #1a1a1a;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .review-card h3 a:hover {
        color: #667eea;
    }
    
    .review-date {
        color: #999;
        font-size: 0.9em;
        margin-bottom: 10px;
    }
    
    .review-excerpt {
        color: #666;
        font-size: 0.95em;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    @media (max-width: 768px) {
        h1 {
            font-size: 2em;
        }
        
        .reviews-grid {
            grid-template-columns: 1fr;
        }
    }
";
include 'includes/header.php';
?>

<div class="container">
    <h1 style="text-align: center;">📝 Book Reviews</h1>
    <div class="subtitle">In-depth reviews and reflections on books I've read</div>
    
    <div class="count">
        <strong><?= count($reviews) ?></strong> reviews
    </div>
    
    <div class="reviews-grid">
        <?php foreach ($reviews as $review): ?>
        <a href="review.php?id=<?= $review['id'] ?>" class="review-card">
            <?php if ($review['image_url']): ?>
                <img src="<?= htmlspecialchars($review['image_url']) ?>" alt="Book cover">
            <?php endif; ?>
            
            <div class="review-content">
                <h3><?= htmlspecialchars(preg_replace('/ by .*$/', '', $review['title'])) ?></h3>
                
                <div class="review-date">
                    <?= date('F j, Y', strtotime($review['publish_date'])) ?>
                </div>
                
                <?php if ($review['description']): ?>
                <div class="review-excerpt">
                    <?= htmlspecialchars(substr(strip_tags($review['description']), 0, 150)) ?>...
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
