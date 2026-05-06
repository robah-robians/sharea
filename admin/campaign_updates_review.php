<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin','super_admin'])) {
    header("Location: " . BASE_URL . "/login.php"); exit;
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$tab = $_GET['tab'] ?? 'pending';

// Fetch pending updates
$pending_updates = $pdo->query("
    SELECT cu.*, c.title as campaign_title, c.id as camp_id,
           u.name as submitter_name
    FROM campaign_updates cu
    JOIN campaigns c ON cu.campaign_id = c.id
    LEFT JOIN users u ON cu.submitted_by = u.id
    WHERE cu.status = 'pending'
    ORDER BY cu.created_at ASC
")->fetchAll();

// Fetch approved updates
$approved_updates = $pdo->query("
    SELECT cu.*, c.title as campaign_title, c.id as camp_id,
           u.name as submitter_name, rv.name as reviewer_name
    FROM campaign_updates cu
    JOIN campaigns c ON cu.campaign_id = c.id
    LEFT JOIN users u ON cu.submitted_by = u.id
    LEFT JOIN users rv ON cu.reviewed_by = rv.id
    WHERE cu.status = 'approved'
    ORDER BY cu.reviewed_at DESC
    LIMIT 50
")->fetchAll();

// Fetch rejected updates
$rejected_updates = $pdo->query("
    SELECT cu.*, c.title as campaign_title, c.id as camp_id,
           u.name as submitter_name, rv.name as reviewer_name
    FROM campaign_updates cu
    JOIN campaigns c ON cu.campaign_id = c.id
    LEFT JOIN users u ON cu.submitted_by = u.id
    LEFT JOIN users rv ON cu.reviewed_by = rv.id
    WHERE cu.status = 'rejected'
    ORDER BY cu.reviewed_at DESC
    LIMIT 50
")->fetchAll();

$counts = [
    'pending'  => count($pending_updates),
    'approved' => count($approved_updates),
    'rejected' => count($rejected_updates),
];
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <div>
                    <h1 style="font-size: 2.5rem; margin: 0 0 0.5rem 0; font-weight: 800; color: var(--text-main);">Campaign Update Reviews</h1>
                    <p style="margin: 0; color: var(--text-muted); font-size: 1.1rem;">Review, approve, and publish NGO-submitted campaign progress updates.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div style="background:rgba(16,185,129,0.1);color:var(--secondary);padding:1rem 1.5rem;border-radius:var(--radius-md);font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fa-solid fa-circle-check"></i> <?= h($_SESSION['success']) ?>
                </div><?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background:rgba(239,68,68,0.1);color:var(--danger);padding:1rem 1.5rem;border-radius:var(--radius-md);font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= h($_SESSION['error']) ?>
                </div><?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div style="background:var(--surface);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--border);overflow:hidden;">
                <!-- Tab Bar -->
                <div style="padding:1.5rem;border-bottom:1px solid var(--border);background:var(--background);display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <?php foreach (['pending'=>['🕐','Awaiting Review','var(--accent)'], 'approved'=>['✅','Approved & Live','var(--secondary)'], 'rejected'=>['❌','Rejected','var(--danger)']] as $t => [$icon, $label, $color]): ?>
                    <button onclick="showTab('<?= $t ?>')" id="<?= $t ?>-tab" class="tab-button"
                        style="padding:0.75rem 1.25rem;background:<?= $tab===$t ? 'var(--primary)' : 'transparent' ?>;color:<?= $tab===$t ? 'white' : 'var(--text-muted)' ?>;border:<?= $tab===$t ? '1px solid var(--primary)' : '1px solid var(--border)' ?>;cursor:pointer;font-weight:600;border-radius:var(--radius-sm);transition:all 0.3s;display:flex;align-items:center;gap:0.4rem;">
                        <?= $icon ?> <?= $label ?>
                        <?php if ($counts[$t] > 0): ?>
                            <span style="background:<?= $t==='pending' ? 'var(--danger)' : 'rgba(255,255,255,0.3)' ?>;color:white;border-radius:999px;padding:0.1rem 0.5rem;font-size:0.72rem;font-weight:800;"><?= $counts[$t] ?></span>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- PENDING TAB -->
                <div id="pending-content" class="tab-content" style="display:<?= $tab==='pending'?'block':'none' ?>;">
                    <?php if (empty($pending_updates)): ?>
                        <div style="padding:3rem;text-align:center;color:var(--text-muted);">
                            <i class="fa-solid fa-check-double" style="font-size:2.5rem;opacity:0.3;display:block;margin-bottom:0.75rem;"></i>
                            No pending updates — all submissions have been reviewed.
                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:0;">
                            <?php foreach ($pending_updates as $upd): ?>
                            <div style="border-bottom:1px solid var(--border);padding:1.75rem 2rem;">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                                            <span style="background:rgba(245,158,11,0.1);color:var(--accent);padding:0.25rem 0.75rem;border-radius:999px;font-size:0.75rem;font-weight:700;">⏳ PENDING REVIEW</span>
                                            <a href="<?= BASE_URL ?>/donate.php?campaign_id=<?= $upd['camp_id'] ?>" target="_blank" style="font-weight:700;color:var(--primary);font-size:0.9rem;"><?= h($upd['campaign_title']) ?></a>
                                            <span style="font-size:0.8rem;color:var(--text-muted);">Submitted by: <?= h($upd['submitter_name'] ?? 'NGO') ?></span>
                                            <span style="font-size:0.78rem;color:var(--text-muted);"><i class="fa-regular fa-clock"></i> <?= date('M j, Y g:i A', strtotime($upd['created_at'])) ?></span>
                                        </div>
                                        <p style="margin:0 0 1rem;color:var(--text-main);line-height:1.7;white-space:pre-wrap;"><?= h($upd['message']) ?></p>
                                        <?php if ($upd['image_url']): ?>
                                            <img src="<?= h($upd['image_url']) ?>" alt="Update image" style="max-height:200px;border-radius:var(--radius-sm);border:1px solid var(--border);margin-bottom:0.75rem;">
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:0.5rem;min-width:160px;">
                                        <!-- Approve -->
                                        <form method="POST" action="<?= BASE_URL ?>/actions/admin_review_update.php">
                                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                            <input type="hidden" name="update_id" value="<?= $upd['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-primary" style="width:100%;padding:0.6rem 1rem;font-size:0.82rem;">
                                                <i class="fa-solid fa-circle-check"></i> Approve & Publish
                                            </button>
                                        </form>
                                        <!-- Reject -->
                                        <button onclick="toggleRejectForm(<?= $upd['id'] ?>)" class="btn btn-outline" style="width:100%;padding:0.6rem 1rem;font-size:0.82rem;color:var(--danger);border-color:rgba(239,68,68,0.4);">
                                            <i class="fa-solid fa-ban"></i> Reject
                                        </button>
                                        <form id="reject-form-<?= $upd['id'] ?>" method="POST" action="<?= BASE_URL ?>/actions/admin_review_update.php" style="display:none;">
                                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                            <input type="hidden" name="update_id" value="<?= $upd['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <textarea name="rejection_reason" rows="3" required placeholder="Reason for rejection..." class="form-control" style="font-size:0.82rem;margin-bottom:0.5rem;padding:0.6rem;background:var(--background);"></textarea>
                                            <button type="submit" style="width:100%;padding:0.5rem;font-size:0.8rem;background:rgba(239,68,68,0.1);color:var(--danger);border:1px solid rgba(239,68,68,0.4);border-radius:var(--radius-sm);cursor:pointer;">Confirm Reject</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- APPROVED TAB -->
                <div id="approved-content" class="tab-content" style="display:<?= $tab==='approved'?'block':'none' ?>;padding:1.5rem 2rem;">
                    <?php if (empty($approved_updates)): ?>
                        <p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No approved updates yet.</p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <?php foreach ($approved_updates as $upd): ?>
                            <div style="background:var(--background);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.25rem;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;flex-wrap:wrap;gap:0.5rem;">
                                    <a href="<?= BASE_URL ?>/donate.php?campaign_id=<?= $upd['camp_id'] ?>" target="_blank" style="font-weight:700;color:var(--primary);"><?= h($upd['campaign_title']) ?></a>
                                    <span style="font-size:0.78rem;color:var(--text-muted);">Published <?= date('M j, Y', strtotime($upd['reviewed_at'])) ?> · by <?= h($upd['reviewer_name'] ?? 'Admin') ?></span>
                                </div>
                                <p style="margin:0;color:var(--text-muted);font-size:0.9rem;line-height:1.6;"><?= nl2br(h(substr($upd['message'],0,200))) ?>...</p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- REJECTED TAB -->
                <div id="rejected-content" class="tab-content" style="display:<?= $tab==='rejected'?'block':'none' ?>;padding:1.5rem 2rem;">
                    <?php if (empty($rejected_updates)): ?>
                        <p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No rejected updates.</p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <?php foreach ($rejected_updates as $upd): ?>
                            <div style="background:rgba(239,68,68,0.04);border:1px solid rgba(239,68,68,0.2);border-radius:var(--radius-md);padding:1.25rem;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;flex-wrap:wrap;gap:0.5rem;">
                                    <a href="<?= BASE_URL ?>/donate.php?campaign_id=<?= $upd['camp_id'] ?>" target="_blank" style="font-weight:700;color:var(--primary);"><?= h($upd['campaign_title']) ?></a>
                                    <span style="font-size:0.78rem;color:var(--danger);">Rejected <?= date('M j, Y', strtotime($upd['reviewed_at'])) ?></span>
                                </div>
                                <p style="margin:0 0 0.5rem;color:var(--text-muted);font-size:0.9rem;"><?= nl2br(h(substr($upd['message'],0,200))) ?>...</p>
                                <div style="font-size:0.82rem;color:var(--danger);font-style:italic;">Rejection reason: <?= h($upd['rejection_reason']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.tab-button').forEach(b => {
        b.style.background = 'transparent'; b.style.color = 'var(--text-muted)'; b.style.border = '1px solid var(--border)';
    });
    document.getElementById(name + '-content').style.display = 'block';
    const btn = document.getElementById(name + '-tab');
    btn.style.background = 'var(--primary)'; btn.style.color = 'white'; btn.style.border = '1px solid var(--primary)';
}
function toggleRejectForm(id) {
    const f = document.getElementById('reject-form-' + id);
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
