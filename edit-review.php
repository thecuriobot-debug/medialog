<?php
require_once 'config.php';
$pdo = getDB();

$id = $_GET['id'] ?? null;
$message = '';
$type = '';

if (!$id) {
    header('Location: index.php');
    exit;
}

// Get post details
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: index.php');
    exit;
}

$mediaType = $post['site_id'] == 7 ? 'book' : 'movie';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newReview = trim($_POST['review_content']);
    
    $stmt = $pdo->prepare("UPDATE posts SET full_content = ? WHERE id = ?");
    if ($stmt->execute([$newReview, $id])) {
        $message = 'Review updated successfully!';
        $type = 'success';
        $post['full_content'] = $newReview;
    } else {
        $message = 'Error updating review';
        $type = 'error';
    }
}

function cleanTitle($title) {
    return preg_replace('/★+/', '', $title);
}

$title = cleanTitle($post['title']);

$pageTitle = "Edit Review";
$pageStyles = "
    .edit-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .edit-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .post-info {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }
    .post-image {
        width: 120px;
        height: 180px;
        object-fit: cover;
        border-radius: 8px;
    }
    .post-details h2 {
        margin: 0 0 10px 0;
        color: #1a1a1a;
    }
    .post-details p {
        color: #666;
        margin: 5px 0;
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
    .form-group textarea {
        width: 100%;
        min-height: 300px;
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        line-height: 1.6;
        resize: vertical;
    }
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
    }
    .btn-group {
        display: flex;
        gap: 15px;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    .btn-secondary {
        background: #e0e0e0;
        color: #333;
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
    .alert-error {
        background: #f8d7da;
        color: #721c24;
    }
    .char-count {
        text-align: right;
        color: #666;
        font-size: 0.9em;
        margin-top: 5px;
    }
";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">✏️ Edit Review</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Update your thoughts</p>
    </div>
    
    <div class="edit-container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="edit-card">
            <div class="post-info">
                <?php if ($post['image_url']): ?>
                    <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Cover" class="post-image">
                <?php endif; ?>
                <div class="post-details">
                    <h2><?= htmlspecialchars($title) ?></h2>
                    <p><strong><?= $mediaType == 'book' ? 'Book' : 'Movie' ?></strong></p>
                    <p><?= date('F j, Y', strtotime($post['publish_date'])) ?></p>
                </div>
            </div>
            
            <form method="POST" id="reviewForm">
                <div class="form-group">
                    <label>Your Review</label>
                    <textarea name="review_content" id="reviewContent" required><?= htmlspecialchars($post['full_content'] ?? '') ?></textarea>
                    <div class="char-count">
                        <span id="charCount">0</span> characters
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                    <a href="review.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const textarea = document.getElementById('reviewContent');
const charCount = document.getElementById('charCount');

function updateCharCount() {
    charCount.textContent = textarea.value.length.toLocaleString();
}

textarea.addEventListener('input', updateCharCount);
updateCharCount();
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
