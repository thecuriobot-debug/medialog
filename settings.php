<?php
require_once 'config.php';
$pdo = getDB();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [
        'goodreads_user_id' => $_POST['goodreads_user_id'] ?? '',
        'letterboxd_username' => $_POST['letterboxd_username'] ?? '',
        'reading_goal_yearly' => $_POST['reading_goal_yearly'] ?? '50',
        'watching_goal_yearly' => $_POST['watching_goal_yearly'] ?? '100'
    ];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) 
                               VALUES (1, :key, :value) 
                               ON DUPLICATE KEY UPDATE setting_value = :value");
        
        foreach ($updates as $key => $value) {
            $stmt->execute([':key' => $key, ':value' => $value]);
        }
        
        $message = 'Settings saved successfully!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error saving settings: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Load current settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM user_settings WHERE user_id = 1");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = "Settings";
$pageStyles = "
    .settings-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .settings-section {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .settings-section h2 {
        color: #667eea;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    
    .form-group input[type='text'],
    .form-group input[type='number'] {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .form-help {
        font-size: 0.85em;
        color: #666;
        margin-top: 5px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 15px 40px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .import-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
        margin-top: 10px;
    }
    
    .import-info h4 {
        margin: 0 0 10px 0;
        color: #667eea;
    }
    
    .import-info p {
        margin: 5px 0;
        font-size: 0.9em;
        line-height: 1.6;
    }
";

include 'includes/header.php';
?>

<div class="container settings-container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">⚙️ Settings</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Manage your MediaLog preferences</p>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- Import Settings -->
        <div class="settings-section">
            <h2>📚 Import Sources</h2>
            
            <div class="form-group">
                <label for="goodreads_user_id">Goodreads User ID</label>
                <input type="text" 
                       id="goodreads_user_id" 
                       name="goodreads_user_id" 
                       value="<?php echo htmlspecialchars($settings['goodreads_user_id'] ?? ''); ?>"
                       placeholder="12345678">
                <div class="form-help">
                    Find your Goodreads ID by visiting your profile page. The number in the URL is your User ID.
                </div>
            </div>
            
            <div class="form-group">
                <label for="letterboxd_username">Letterboxd Username</label>
                <input type="text" 
                       id="letterboxd_username" 
                       name="letterboxd_username" 
                       value="<?php echo htmlspecialchars($settings['letterboxd_username'] ?? ''); ?>"
                       placeholder="yourusername">
                <div class="form-help">
                    Your Letterboxd username (not email). This appears in your profile URL.
                </div>
            </div>
            
            <div class="import-info">
                <h4>🔄 How Import Works</h4>
                <p><strong>Goodreads:</strong> We'll fetch your reading history using the Goodreads RSS feed.</p>
                <p><strong>Letterboxd:</strong> We'll import your films from your public Letterboxd profile.</p>
                <p><em>Note: Imports are currently manual. Automatic sync coming soon!</em></p>
            </div>
        </div>
        
        <!-- Personal Goals -->
        <div class="settings-section">
            <h2>🎯 Personal Goals</h2>
            
            <div class="form-group">
                <label for="reading_goal_yearly">Yearly Reading Goal</label>
                <input type="number" 
                       id="reading_goal_yearly" 
                       name="reading_goal_yearly" 
                       value="<?php echo htmlspecialchars($settings['reading_goal_yearly'] ?? '50'); ?>"
                       min="1"
                       max="1000">
                <div class="form-help">
                    How many books do you want to read this year?
                </div>
            </div>
            
            <div class="form-group">
                <label for="watching_goal_yearly">Yearly Watching Goal</label>
                <input type="number" 
                       id="watching_goal_yearly" 
                       name="watching_goal_yearly" 
                       value="<?php echo htmlspecialchars($settings['watching_goal_yearly'] ?? '100'); ?>"
                       min="1"
                       max="1000">
                <div class="form-help">
                    How many movies do you want to watch this year?
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn-primary">
                💾 Save Settings
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
