<?php
require_once 'config.php';
$pdo = getDB();

$currentYear = date('Y');

// Get goals
$stmt = $pdo->prepare("SELECT goal_type, target_value FROM user_goals WHERE year = ?");
$stmt->execute([$currentYear]);
$goals = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $goals[$row['goal_type']] = $row['target_value'];
}

// Calculate current progress
$booksCount = $pdo->query("SELECT COUNT(*) as c FROM posts WHERE site_id = 7 AND YEAR(publish_date) = $currentYear")->fetch()['c'];
$moviesCount = $pdo->query("SELECT COUNT(*) as c FROM posts WHERE site_id = 6 AND YEAR(publish_date) = $currentYear")->fetch()['c'];

// Calculate pages (rough estimate from descriptions)
$pagesCount = 0;
$stmt = $pdo->query("SELECT description FROM posts WHERE site_id = 7 AND YEAR(publish_date) = $currentYear");
while ($row = $stmt->fetch()) {
    if (preg_match('/(\d+)\s+pages/', $row['description'] ?? '', $matches)) {
        $pagesCount += (int)$matches[1];
    }
}

$pageTitle = "Goals Dashboard";
$pageStyles = "
    .goals-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    .goal-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 25px;
    }
    .goal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .goal-title {
        font-size: 1.5em;
        font-weight: 700;
        color: #1a1a1a;
    }
    .goal-numbers {
        font-size: 1.8em;
        font-weight: 700;
        color: #667eea;
    }
    .progress-bar {
        height: 30px;
        background: #f0f0f0;
        border-radius: 15px;
        overflow: hidden;
        position: relative;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        transition: width 0.5s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.9em;
    }
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: 600;
        z-index: 10;
    }
    .goal-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-top: 15px;
    }
    .stat-box {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .stat-box-value {
        font-size: 1.5em;
        font-weight: 700;
        color: #667eea;
    }
    .stat-box-label {
        font-size: 0.85em;
        color: #666;
        margin-top: 5px;
    }
";
include 'includes/header.php';

function calculateProgress($current, $target) {
    if ($target == 0) return 0;
    return min(100, round(($current / $target) * 100));
}

function getDaysRemaining() {
    $today = new DateTime();
    $endOfYear = new DateTime(date('Y') . '-12-31');
    return $today->diff($endOfYear)->days;
}

function getRequiredPace($current, $target) {
    $remaining = $target - $current;
    $daysLeft = getDaysRemaining();
    if ($remaining <= 0 || $daysLeft <= 0) return 0;
    return round($remaining / $daysLeft, 2);
}
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">🎯 <?= $currentYear ?> Goals</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Track your progress</p>
    </div>
    
    <div class="goals-container">
        <?php if (empty($goals)): ?>
            <div class="goal-card" style="text-align: center; padding: 60px;">
                <h2 style="color: #666; margin-bottom: 15px;">No Goals Set</h2>
                <p style="color: #999; margin-bottom: 20px;">Start tracking your reading and watching goals!</p>
                <a href="settings.php" style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    ⚙️ Set Goals
                </a>
            </div>
        <?php else: ?>
            <?php if (isset($goals['books']) && $goals['books'] > 0): ?>
            <div class="goal-card">
                <div class="goal-header">
                    <div class="goal-title">📚 Books</div>
                    <div class="goal-numbers"><?= $booksCount ?> / <?= $goals['books'] ?></div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= calculateProgress($booksCount, $goals['books']) ?>%">
                        <?php if (calculateProgress($booksCount, $goals['books']) > 10): ?>
                            <?= calculateProgress($booksCount, $goals['books']) ?>%
                        <?php endif; ?>
                    </div>
                    <?php if (calculateProgress($booksCount, $goals['books']) <= 10): ?>
                        <div class="progress-text"><?= calculateProgress($booksCount, $goals['books']) ?>%</div>
                    <?php endif; ?>
                </div>
                <div class="goal-stats">
                    <div class="stat-box">
                        <div class="stat-box-value"><?= $goals['books'] - $booksCount ?></div>
                        <div class="stat-box-label">Remaining</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value"><?= getDaysRemaining() ?></div>
                        <div class="stat-box-label">Days Left</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value"><?= getRequiredPace($booksCount, $goals['books']) ?></div>
                        <div class="stat-box-label">Per Day Needed</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($goals['movies']) && $goals['movies'] > 0): ?>
            <div class="goal-card">
                <div class="goal-header">
                    <div class="goal-title">🎬 Movies</div>
                    <div class="goal-numbers"><?= $moviesCount ?> / <?= $goals['movies'] ?></div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= calculateProgress($moviesCount, $goals['movies']) ?>%">
                        <?php if (calculateProgress($moviesCount, $goals['movies']) > 10): ?>
                            <?= calculateProgress($moviesCount, $goals['movies']) ?>%
                        <?php endif; ?>
                    </div>
                    <?php if (calculateProgress($moviesCount, $goals['movies']) <= 10): ?>
                        <div class="progress-text"><?= calculateProgress($moviesCount, $goals['movies']) ?>%</div>
                    <?php endif; ?>
                </div>
                <div class="goal-stats">
                    <div class="stat-box">
                        <div class="stat-box-value"><?= $goals['movies'] - $moviesCount ?></div>
                        <div class="stat-box-label">Remaining</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value"><?= getDaysRemaining() ?></div>
                        <div class="stat-box-label">Days Left</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value"><?= getRequiredPace($moviesCount, $goals['movies']) ?></div>
                        <div class="stat-box-label">Per Day Needed</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($goals['pages']) && $goals['pages'] > 0): ?>
            <div class="goal-card">
                <div class="goal-header">
                    <div class="goal-title">📖 Pages</div>
                    <div class="goal-numbers"><?= number_format($pagesCount) ?> / <?= number_format($goals['pages']) ?></div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= calculateProgress($pagesCount, $goals['pages']) ?>%">
                        <?php if (calculateProgress($pagesCount, $goals['pages']) > 10): ?>
                            <?= calculateProgress($pagesCount, $goals['pages']) ?>%
                        <?php endif; ?>
                    </div>
                    <?php if (calculateProgress($pagesCount, $goals['pages']) <= 10): ?>
                        <div class="progress-text"><?= calculateProgress($pagesCount, $goals['pages']) ?>%</div>
                    <?php endif; ?>
                </div>
                <div class="goal-stats">
                    <div class="stat-box">
                        <div class="stat-box-value"><?= number_format($goals['pages'] - $pagesCount) ?></div>
                        <div class="stat-box-label">Remaining</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value"><?= getDaysRemaining() ?></div>
                        <div class="stat-box-label">Days Left</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-value"><?= number_format(getRequiredPace($pagesCount, $goals['pages'])) ?></div>
                        <div class="stat-box-label">Per Day Needed</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
