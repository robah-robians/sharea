<?php
// Public detail page for viewing full message/announcement content
session_start();
require_once __DIR__ . '/includes/header.php';

$message_id = intval($_GET['id'] ?? 0);

if ($message_id <= 0) {
    header("Location: /share_hope/index.php");
    exit;
}

// Fetch message
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$message_id]);
$message = $stmt->fetch();

if (!$message) {
    header("Location: /share_hope/index.php");
    exit;
}

// Determine message type
$type = 'announcement';
if ($message['is_public']) {
    $type = 'emergency';
}

// Get related messages (same type, different ID)
$stmt = $pdo->prepare("SELECT id, title, message, created_at FROM announcements WHERE id != ? ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$message_id]);
$related = $stmt->fetchAll();
?>

<div style="padding: 4rem 0;">
    <div class="container" style="max-width: 900px;">
        
        <!-- Back Button -->
        <a href="/share_hope/index.php" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 2rem;">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>

        <!-- Message Header -->
        <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 3rem 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; text-align: center;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                <?php if ($type === 'emergency'): ?>
                    <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                        🚨 Emergency Alert
                    </span>
                <?php else: ?>
                    <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                        📢 Announcement
                    </span>
                <?php endif; ?>
            </div>
            <h1 style="font-size: 2.5rem; margin: 0 0 1rem 0; font-weight: 800; line-height: 1.2;">
                <?= h($message['title']) ?>
            </h1>
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; opacity: 0.9; font-size: 0.95rem;">
                <span><i class="fa-regular fa-calendar"></i> <?= date('F j, Y', strtotime($message['created_at'])) ?></span>
                <span>•</span>
                <span><i class="fa-regular fa-clock"></i> <?= date('g:i A', strtotime($message['created_at'])) ?></span>
            </div>
        </div>

        <!-- Message Content -->
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 2.5rem; margin-bottom: 2rem;">
            <div style="font-size: 1.1rem; line-height: 1.8; color: var(--text-main);">
                <?= nl2br(h($message['message'])) ?>
            </div>

            <!-- Action Button -->
            <?php if (!empty($message['action_link'])): ?>
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                    <a href="<?= h($message['action_link']) ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; font-size: 1rem; font-weight: 700;">
                        <i class="fa-solid fa-arrow-right"></i> Take Action
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Share Section -->
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">Share This Message</h3>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="https://wa.me/?text=<?= urlencode($message['title'] . ' - ' . $message['message'] . ' ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" target="_blank" class="btn" style="background: #25D366; color: white; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($message['title'] . ' ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" target="_blank" class="btn" style="background: #1DA1F2; color: white; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                    <i class="fa-brands fa-twitter"></i> Twitter
                </a>
                <button onclick="copyLink()" class="btn" style="background: var(--primary); color: white; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                    <i class="fa-solid fa-link"></i> Copy Link
                </button>
            </div>
        </div>

        <!-- Related Messages -->
        <?php if (!empty($related)): ?>
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1rem; font-weight: 700;">Related Messages</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($related as $rel): ?>
                        <a href="/share_hope/message_detail.php?id=<?= $rel['id'] ?>" style="text-decoration: none; display: block; padding: 1.25rem; background: var(--background); border: 1px solid var(--border); border-radius: var(--radius-md); transition: all 0.3s;" onmouseover="this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.95rem; font-weight: 700; color: var(--text-main); line-height: 1.3;">
                                <?= h(mb_substr($rel['title'], 0, 50)) ?>
                            </h4>
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                                <?= h(mb_substr($rel['message'], 0, 80)) ?>...
                            </p>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <i class="fa-regular fa-calendar"></i> <?= date('M j, Y', strtotime($rel['created_at'])) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
function copyLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        alert('Link copied to clipboard!');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
