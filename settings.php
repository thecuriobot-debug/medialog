<?php
require_once 'config.php';
$pdo = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_accounts':
                $goodreadsUrl = trim($_POST['goodreads_url'] ?? '');
                $letterboxdUrl = trim($_POST['letterboxd_url'] ?? '');
                $stmt = $pdo->prepare("INSERT INTO user_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute(['goodreads_url', $goodreadsUrl, $goodreadsUrl]);
                $stmt->execute(['letterboxd_url', $letterboxdUrl, $letterboxdUrl]);
                $message = 'Account URLs saved successfully!';
                $messageType = 'success';
                break;
            case 'save_goals':
                $year = (int)($_POST['year'] ?? date('Y'));
                $booksGoal = (int)($_POST['books_goal'] ?? 0);
                $moviesGoal = (int)($_POST['movies_goal'] ?? 0);
                $pagesGoal = (int)($_POST['pages_goal'] ?? 0);
                $stmt = $pdo->prepare("INSERT INTO user_goals (goal_type, target_value, year) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE target_value = ?");
                $stmt->execute(['books', $booksGoal, $year, $booksGoal]);
                $stmt->execute(['movies', $moviesGoal, $year, $moviesGoal]);
                $stmt->execute(['pages', $pagesGoal, $year, $pagesGoal]);
                $message = 'Goals saved successfully!';
                $messageType = 'success';
                break;
        }
    }
}

$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM user_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$currentYear = date('Y');
$goals = [];
$stmt = $pdo->prepare("SELECT goal_type, target_value, current_value FROM user_goals WHERE year = ?");
$stmt->execute([$currentYear]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $goals[$row['goal_type']] = $row;
}

$pageTitle = "Settings";
$pageStyles = "
    .settings-section {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .settings-section h2 {
        color: #1a1a1a;
        margin-bottom: 20px;
        font-size: 1.5em;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    .form-group input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }
    .form-group small {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 0.85em;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
    }
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    .goal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">⚙️ Settings</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Configure your MediaLog</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <div class="settings-section">
        <h2>📚 Account Integration</h2>
        <div class="alert alert-info">
            <strong>Note:</strong> Enter your Goodreads and Letterboxd profile URLs.
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_accounts">
            <div class="form-group">
                <label>Goodreads Profile URL</label>
                <input type="url" name="goodreads_url" value="<?= htmlspecialchars($settings['goodreads_url'] ?? '') ?>" 
                       placeholder="https://www.goodreads.com/user/show/12345-username">
                <small>Example: https://www.goodreads.com/user/show/12345-username</small>
            </div>
            <div class="form-group">
                <label>Letterboxd Profile URL</label>
                <input type="url" name="letterboxd_url" value="<?= htmlspecialchars($settings['letterboxd_url'] ?? '') ?>" 
                       placeholder="https://letterboxd.com/username">
                <small>Example: https://letterboxd.com/username</small>
            </div>
            <button type="submit" class="btn btn-primary">💾 Save URLs</button>
        </form>
    </div>
    
    <div class="settings-section">
        <h2>🎯 <?= $currentYear ?> Goals</h2>
        <form method="POST">
            <input type="hidden" name="action" value="save_goals">
            <input type="hidden" name="year" value="<?= $currentYear ?>">
            <div class="goal-grid">
                <div class="form-group">
                    <label>📚 Books Goal</label>
                    <input type="number" name="books_goal" value="<?= $goals['books']['target_value'] ?? 0 ?>" min="0">
                    <small>Current: <?= $goals['books']['current_value'] ?? 0 ?></small>
                </div>
                <div class="form-group">
                    <label>🎬 Movies Goal</label>
                    <input type="number" name="movies_goal" value="<?= $goals['movies']['target_value'] ?? 0 ?>" min="0">
                    <small>Current: <?= $goals['movies']['current_value'] ?? 0 ?></small>
                </div>
                <div class="form-group">
                    <label>📖 Pages Goal</label>
                    <input type="number" name="pages_goal" value="<?= $goals['pages']['target_value'] ?? 0 ?>" min="0">
                    <small>Current: <?= $goals['pages']['current_value'] ?? 0 ?></small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">🎯 Save Goals</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
