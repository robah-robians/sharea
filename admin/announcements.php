<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_announcement') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $action_link = trim($_POST['action_link'] ?? '');

        if (empty($title) || empty($message)) {
            $error = "Title and message are required fields.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, message, is_public, action_link) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$title, $message, $is_public, $action_link])) {
                $success = "Announcement broadcasted successfully.";
            } else {
                $error = "Failed to post announcement.";
            }
        }
    }
}

// Fetch all previous announcements
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll();
?>

<div style="padding: 2.5rem 0; max-width: none; margin: 0; width: 100%;">
    <div class="admin-layout" style="display: flex; gap: 0; align-items: flex-start; margin: 0; padding: 0;">

        <!-- Sidebar -->
        <div style="padding-left: 0; margin-left: 0;">
            <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
        </div>

        <div class="admin-main" style="flex: 1; min-width: 0; padding-left: 2.5rem; padding-right: 1.5rem; max-width: 1150px;">
            <!-- Professional Header with Glitter Effect -->
            <div style="text-align: center; margin-bottom: 3rem; padding: 2rem; background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(192,192,192,0.1), rgba(255,165,0,0.1)); border-radius: var(--radius-lg); border: 1px solid var(--border);">
                <h1 style="font-size: 3rem; margin: 0; background: linear-gradient(45deg, #FFD700, #C0C0C0, #FFA500, #FFD700, #C0C0C0); background-size: 400% 400%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; animation: glitter 3s ease-in-out infinite; font-weight: 900; text-shadow: 0 0 30px rgba(255,215,0,0.3);">
                    ✨ Global Announcements ✨
                </h1>
                <p style="color: var(--text-muted); margin: 1rem 0 0 0; font-size: 1.2rem; font-weight: 500; opacity: 0.8;">Administrative Broadcasting & Communication Center</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= h($success) ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 3rem;">

                <!-- Enhanced Create Announcement Form -->
                <div style="background: linear-gradient(135deg, var(--surface), rgba(255,255,255,0.9)); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border); padding: 1.5rem; height: fit-content; position: relative; overflow: hidden;">
                    <!-- Decorative Elements -->
                    <div style="position: absolute; top: -30px; right: -30px; width: 60px; height: 60px; background: linear-gradient(45deg, rgba(255,215,0,0.1), rgba(255,165,0,0.1)); border-radius: 50%; opacity: 0.6;"></div>
                    <div style="position: absolute; bottom: -20px; left: -20px; width: 40px; height: 40px; background: linear-gradient(45deg, rgba(192,192,192,0.1), rgba(255,215,0,0.1)); border-radius: 50%; opacity: 0.4;"></div>
                    
                    <div style="position: relative; z-index: 10;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem;">
                            <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: var(--shadow-md);">
                                <i class="fa-solid fa-bullhorn"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main);">Broadcast Center</h3>
                                <p style="margin: 0; color: var(--text-muted); font-size: 0.8rem;">Global Communication Hub</p>
                            </div>
                        </div>
                        
                        <div style="background: rgba(79, 70, 229, 0.05); border-left: 3px solid var(--primary); padding: 0.75rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
                            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; line-height: 1.4; font-weight: 500;">
                                <i class="fa-solid fa-info-circle" style="color: var(--primary); margin-right: 0.25rem;"></i>
                                Messages broadcasted here will instantly appear on all registered NGO and Donor dashboards.
                            </p>
                        </div>

                        <form method="POST" style="position: relative;">
                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                            <input type="hidden" name="action" value="post_announcement">

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; font-size: 0.9rem;">
                                    <i class="fa-solid fa-heading" style="color: var(--primary); font-size: 0.8rem;"></i>
                                    Announcement Title
                                </label>
                                <input type="text" name="title" required placeholder="e.g., Scheduled Maintenance" class="form-control" style="border: 2px solid var(--border); transition: all 0.3s ease; padding: 0.75rem; font-size: 0.9rem;" onFocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(79,70,229,0.1)'" onBlur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; font-size: 0.9rem;">
                                    <i class="fa-solid fa-message" style="color: var(--secondary); font-size: 0.8rem;"></i>
                                    Message Content
                                </label>
                                <textarea name="message" required rows="3" placeholder="Write your broadcast message here..." class="form-control" style="border: 2px solid var(--border); transition: all 0.3s ease; resize: vertical; padding: 0.75rem; font-size: 0.9rem;" onFocus="this.style.borderColor='var(--secondary)'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'" onBlur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'"></textarea>
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; font-size: 0.9rem;">
                                    <i class="fa-solid fa-link" style="color: var(--accent); font-size: 0.8rem;"></i>
                                    Optional Action Link (URL)
                                </label>
                                <input type="url" name="action_link" placeholder="e.g., /donate.php?campaign_id=12" class="form-control" style="border: 2px solid var(--border); transition: all 0.3s ease; padding: 0.75rem; font-size: 0.9rem;" onFocus="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 0 0 3px rgba(245,158,11,0.1)'" onBlur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
                                <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block; font-style: italic;">
                                    <i class="fa-solid fa-lightbulb" style="margin-right: 0.25rem;"></i>
                                    Provide a destination link to add a clickable button.
                                </small>
                            </div>

                            <div class="form-group" style="background: rgba(220, 38, 38, 0.05); border: 2px dashed var(--danger); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem;">
                                <div style="display: flex; gap: 0.75rem; align-items: center;">
                                    <input type="checkbox" name="is_public" id="is_public" value="1" style="width: 1.25rem; height: 1.25rem; accent-color: var(--danger);">
                                    <label for="is_public" style="margin: 0; font-weight: 700; color: var(--danger); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 1rem;">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Mark as Public Emergency Banner
                                    </label>
                                </div>
                                <p style="margin: 0.5rem 0 0 2rem; color: var(--danger); font-size: 0.75rem; opacity: 0.8;">
                                    Emergency announcements will be prominently displayed to all users.
                                </p>
                            </div>

                            <button type="submit" class="btn" style="width: 100%; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: 700; border: none; border-radius: var(--radius-md); box-shadow: var(--shadow-md); transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 0.5px;" onMouseOver="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onMouseOut="this.style.transform='translateY(0)'; this.style.boxShadow='var(--shadow-md)'">
                                <i class="fa-solid fa-paper-plane" style="margin-right: 0.5rem;"></i>
                                Broadcast Announcement
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Enhanced Announcement History -->
                <div style="background: linear-gradient(135deg, var(--surface), rgba(255,255,255,0.9)); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border); overflow: hidden;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: linear-gradient(135deg, var(--accent), rgba(245, 158, 11, 0.8)); color: white;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="background: rgba(255,255,255,0.2); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 800;">Announcement History</h3>
                                <p style="margin: 0; opacity: 0.9; font-size: 0.8rem;">Manage & control announcement visibility</p>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 1.5rem;">
                        <?php if (empty($announcements)): ?>
                            <div style="text-align: center; padding: 3rem 0; color: var(--text-muted);">
                                <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <p style="font-size: 1.1rem; font-weight: 600;">No announcements have been made yet.</p>
                                <p style="font-size: 0.9rem; opacity: 0.7;">Create your first announcement to get started.</p>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <?php foreach ($announcements as $a): ?>
                                    <div style="border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.5rem; background: var(--background); position: relative; transition: all 0.3s ease;" onMouseOver="this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-2px)'" onMouseOut="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                                        <!-- Status Indicator -->
                                        <div style="position: absolute; top: 1rem; right: 1rem; display: flex; gap: 0.5rem;">
                                            <?php if ($a['is_public']): ?>
                                                <span style="background: var(--warning); color: white; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                                    <i class="fa-solid fa-triangle-exclamation" style="margin-right: 0.25rem;"></i>Emergency
                                                </span>
                                            <?php else: ?>
                                                <span style="background: var(--secondary); color: white; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                                    <i class="fa-solid fa-info-circle" style="margin-right: 0.25rem;"></i>Standard
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="margin-bottom: 1rem; padding-right: 8rem;">
                                            <h4 style="font-size: 1.2rem; color: var(--text-main); margin: 0 0 0.5rem 0; font-weight: 700;">
                                                <?= h($a['title']) ?>
                                            </h4>
                                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                                <span style="font-size: 0.85rem; color: var(--text-muted); background: var(--surface); padding: 0.4rem 0.8rem; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fa-regular fa-calendar"></i>
                                                    <?= date('M j, Y H:i', strtotime($a['created_at'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0 0 1.5rem 0; line-height: 1.6;">
                                            <?= nl2br(h($a['message'])) ?>
                                        </p>
                                        
                                        <!-- Action Buttons -->
                                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                            <form action="<?= BASE_URL ?>/actions/admin_moderate_announcement.php" method="POST" style="display: inline-block;">
                                                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                                                <button type="submit" name="mod_action" value="delete" class="btn" style="background: var(--danger); color: white; padding: 0.6rem 1.25rem; font-size: 0.9rem; border: none; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; font-weight: 600;" onMouseOver="this.style.transform='translateY(-1px)'; this.style.boxShadow='var(--shadow-sm)'" onMouseOut="this.style.transform='translateY(0)'; this.style.boxShadow='none'" onclick="return confirm('Remove this announcement from the system? This will permanently delete it from all user dashboards and cannot be undone.');">
                                                    <i class="fa-solid fa-trash-can"></i> Remove from System
                                                </button>
                                            </form>
                                            
                                            <?php if (!$a['is_public']): ?>
                                                <form action="<?= BASE_URL ?>/actions/admin_moderate_announcement.php" method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                                                    <button type="submit" name="mod_action" value="publish" class="btn" style="background: var(--warning); color: white; padding: 0.6rem 1.25rem; font-size: 0.9rem; border: none; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; font-weight: 600;" onMouseOver="this.style.transform='translateY(-1px)'; this.style.boxShadow='var(--shadow-sm)'" onMouseOut="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                        <i class="fa-solid fa-triangle-exclamation"></i> Mark as Emergency
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<style>
@media (max-width: 1024px) {
  .ann-flex-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 768px) {
  .ann-mobile-actions { display:grid !important; grid-template-columns:1fr 1fr; gap:.45rem !important; width:100%; }
  .ann-mobile-row { flex-direction:column !important; align-items:flex-start !important; }
}
</style>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
