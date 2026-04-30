<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: /share_hope/login.php');
    exit;
}
require_once __DIR__ . '/../includes/header.php';

// Fetch system stats
try {
    $stats = $pdo->query("SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'completed') as total_raised,
        (SELECT COUNT(*) FROM ngos WHERE is_verified = 1) as verified_ngos,
        (SELECT COUNT(*) FROM campaigns WHERE status = 'active') as active_campaigns,
        (SELECT COUNT(*) FROM users) as total_users
    ")->fetch();
} catch (\PDOException $e) {
    $stats = ['total_raised' => 0, 'verified_ngos' => 0, 'active_campaigns' => 0, 'total_users' => 0];
}

// Pending campaign requests
try {
    $pending_requests = $pdo->query("
        SELECT cr.*, u.name as ngo_name, cat.name as category_name
        FROM campaign_requests cr
        JOIN ngos n ON cr.ngo_id = n.id
        JOIN users u ON n.user_id = u.id
        LEFT JOIN categories cat ON cr.category_id = cat.id
        WHERE cr.status = 'pending'
        ORDER BY cr.created_at ASC
    ")->fetchAll();
    $pending_updates_count = $pdo->query("SELECT COUNT(*) FROM campaign_updates WHERE status = 'pending'")->fetchColumn();
    $unverified_ngos_count = $pdo->query("SELECT COUNT(*) FROM ngos WHERE is_verified = 0")->fetchColumn();
} catch (\PDOException $e) {
    $pending_requests = [];
    $pending_updates_count = 0;
    $unverified_ngos_count = 0;
}
?>
<div class="container" style="padding: 4rem 0; max-width: 1400px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <!-- Sidebar Navigation -->
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
        
        <!-- Main Content -->
        <div class="admin-main" style="flex: 1; min-width: 0;">
            
            <!-- System Marquee -->
            <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 2.5rem; box-shadow: var(--shadow-md); border-left: 4px solid var(--accent);">
                <marquee scrollamount="5" style="font-size: 0.95rem; font-weight: 500; font-family: monospace;">
                    <i class="fa-solid fa-satellite-dish"></i> 
                    SYS_MSG: Welcome Global Administrator. System uptime: 99.9%. All secure nodes operating nominally. 
                    Verified NGOs: <?= number_format((int)$stats['verified_ngos']) ?>. 
                    Live tracking active across <?= number_format((int)$stats['active_campaigns']) ?> campaigns.
                </marquee>
            </div>

            <!-- Header and Map Grid -->
            <div style="display: grid; grid-template-columns: 1.25fr 1fr; gap: 2.5rem; margin-bottom: 2.5rem; align-items: stretch;">
                
                <!-- Welcome Banner (The other thing that was there) -->
                <div style="background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white; padding: 3rem; border-radius: var(--radius-lg); position: relative; overflow: hidden; box-shadow: var(--shadow-md); display: flex; flex-direction: column; justify-content: center;">
                    <div style="position: relative; z-index: 2;">
                        <h1 style="font-size: 2.5rem; margin: 0 0 1rem 0; font-weight: 800;">Administrative Command Center</h1>
                        <p style="margin: 0; opacity: 0.9; max-width: 600px; font-size: 1.1rem; line-height: 1.6;">
                            Manage global platform operations, authorize institutional campaigns, verify organizations, and monitor synchronized impact streams in real-time.
                        </p>
                    </div>
                    <i class="fa-solid fa-earth-americas" style="position: absolute; right: -2rem; bottom: -2rem; font-size: 15rem; opacity: 0.1; transform: rotate(-15deg);"></i>
                </div>

                <!-- Global Impact Network (Live Map Interface on the Upper Right) -->
                <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column; min-height: 280px;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--text-main);">
                            <i class="fa-solid fa-map-location-dot text-accent"></i> Active Impact Regions
                        </h3>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="width: 10px; height: 10px; border-radius: 50%; background: var(--accent); display: inline-block; animation: pulse 2s infinite;"></span>
                            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Live Campaign Tracking</span>
                        </div>
                    </div>
                    <div style="flex: 1; position: relative; background: var(--secondary); overflow: hidden;">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d15955.166827029578!2d10.0!3d0.0!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2ske!4v1711204856036!5m2!1sen!2ske" width="100%" height="100%" style="border:0; position:absolute; top:0; left:0; width:100%; height:100%; opacity: 0.5; mix-blend-mode: multiply; filter: grayscale(100%) contrast(1.2) drop-shadow(0 0 10px rgba(0,0,0,0.5));" allowfullscreen="" loading="lazy"></iframe>
                        <!-- HUD Decorative overlay -->
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at center, transparent 30%, rgba(30, 58, 138, 0.4) 100%); pointer-events: none;"></div>
                        <!-- Map nodes -->
                        <div style="position: absolute; top: 40%; left: 30%; width: 10px; height: 10px; background: var(--accent); border-radius: 50%; box-shadow: 0 0 15px var(--accent); animation: pulse 2s infinite;"></div>
                        <div style="position: absolute; top: 55%; left: 55%; width: 14px; height: 14px; background: var(--primary); border-radius: 50%; box-shadow: 0 0 20px var(--primary); animation: pulse 2.5s infinite;"></div>
                        <div style="position: absolute; top: 30%; left: 70%; width: 8px; height: 8px; background: var(--accent); border-radius: 50%; box-shadow: 0 0 10px var(--accent); animation: pulse 1.5s infinite;"></div>
                    </div>
                </div>
            </div>

            <style>
            @keyframes pulse {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.5); opacity: 0.5; }
                100% { transform: scale(1); opacity: 1; }
            }
            @media(max-width: 1024px) {
                .admin-main [style*="grid-template-columns: 1.25fr 1fr"] { grid-template-columns: 1fr !important; }
            }
            </style>

            <!-- Quick Actions Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
                <a href="campaigns_hub.php?tab=deploy" style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; transition: all 0.2s; text-decoration: none;" onmouseover="this.style.borderColor='var(--accent)'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)';">
                    <div style="width: 50px; height: 50px; background: rgba(20, 184, 166, 0.1); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fa-solid fa-plus"></i></div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Deploy Campaign</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Initialize tracking parameters</div>
                    </div>
                </a>

                <a href="stakeholders.php?tab=ngos" style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; transition: all 0.2s; text-decoration: none; position:relative;" onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)';">
                    <?php if ($unverified_ngos_count > 0): ?><span style="position:absolute;top:0.5rem;right:0.75rem;background:var(--danger);color:white;border-radius:999px;padding:0.1rem 0.5rem;font-size:0.72rem;font-weight:800;"><?= $unverified_ngos_count ?></span><?php endif; ?>
                    <div style="width: 50px; height: 50px; background: rgba(30, 58, 138, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fa-solid fa-check-double"></i></div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Verify Platform NGOs</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Process pending verifications</div>
                    </div>
                </a>

                <a href="campaign_updates_review.php" style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; transition: all 0.2s; text-decoration: none; position:relative;" onmouseover="this.style.borderColor='var(--secondary)'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)';">
                    <?php if ($pending_updates_count > 0): ?><span style="position:absolute;top:0.5rem;right:0.75rem;background:var(--danger);color:white;border-radius:999px;padding:0.1rem 0.5rem;font-size:0.72rem;font-weight:800;"><?= $pending_updates_count ?></span><?php endif; ?>
                    <div style="width: 50px; height: 50px; background: rgba(16,185,129, 0.1); color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fa-solid fa-timeline"></i></div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Review Updates</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Approve / reject NGO updates</div>
                    </div>
                </a>

                <a href="finance_controls.php?tab=audit" style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; transition: all 0.2s; text-decoration: none;" onmouseover="this.style.borderColor='var(--warning)'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)';">
                    <div style="width: 50px; height: 50px; background: rgba(245, 158, 11, 0.1); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fa-solid fa-shield-halved"></i></div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">System Transactions</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Audit flagged activity</div>
                    </div>
                </a>
            </div>

            <!-- === NGO Campaign Requests Inbox === -->
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);">
                <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);background:var(--background);display:flex;justify-content:space-between;align-items:center;">
                    <h3 style="margin:0;font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                        <i class="fa-solid fa-inbox text-primary"></i> NGO Campaign Requests
                        <?php if (count($pending_requests) > 0): ?>
                            <span style="background:var(--danger);color:white;border-radius:999px;padding:0.1rem 0.6rem;font-size:0.75rem;font-weight:800;"><?= count($pending_requests) ?> pending</span>
                        <?php endif; ?>
                    </h3>
                    <span style="font-size:0.82rem;color:var(--text-muted);">Requests from verified NGOs — review and deploy as campaigns</span>
                </div>

                <?php if (empty($pending_requests)): ?>
                    <div style="padding:2.5rem;text-align:center;color:var(--text-muted);">
                        <i class="fa-solid fa-inbox" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:0.75rem;"></i>
                        No pending campaign requests. All submissions have been reviewed.
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:2px solid var(--border);background:var(--background);color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.5px;">
                                    <th style="padding:1rem 1.5rem;text-align:left;">Campaign Title</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">NGO</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">Category</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">Goal</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">Deadline</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">Submitted</th>
                                    <th style="padding:1rem 1.5rem;text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_requests as $req): ?>
                                <tr style="border-bottom:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:1rem 1.5rem;">
                                        <div style="font-weight:700;color:var(--text-main);"><?= h($req['title']) ?></div>
                                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= h($req['description']) ?></div>
                                    </td>
                                    <td style="padding:1rem 1.5rem;font-size:0.88rem;color:var(--text-muted);"><?= h($req['ngo_name']) ?></td>
                                    <td style="padding:1rem 1.5rem;">
                                        <span style="background:rgba(99,102,241,0.1);color:var(--primary);padding:0.2rem 0.65rem;border-radius:999px;font-size:0.75rem;font-weight:600;"><?= h($req['category_name'] ?? 'General') ?></span>
                                    </td>
                                    <td style="padding:1rem 1.5rem;font-weight:700;color:var(--secondary);">KSh <?= number_format($req['goal_amount'], 0) ?></td>
                                    <td style="padding:1rem 1.5rem;font-size:0.85rem;color:var(--text-muted);"><?= date('M j, Y', strtotime($req['deadline'])) ?></td>
                                    <td style="padding:1rem 1.5rem;font-size:0.82rem;color:var(--text-muted);"><?= date('M j, Y', strtotime($req['created_at'])) ?></td>
                                    <td style="padding:1rem 1.5rem;text-align:right;">
                                        <div style="display:flex;gap:0.4rem;justify-content:flex-end;flex-wrap:wrap;">
                                            <!-- Approve → pre-fill Deploy form -->
                                            <form method="POST" action="/share_hope/actions/admin_review_campaign_request.php" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-primary" style="padding:0.35rem 0.75rem;font-size:0.75rem;" title="Approve & deploy">
                                                    <i class="fa-solid fa-rocket"></i> Approve & Deploy
                                                </button>
                                            </form>
                                            <!-- Reject -->
                                            <button onclick="toggleRejectBox(<?= $req['id'] ?>)" class="btn" style="padding:0.35rem 0.75rem;font-size:0.75rem;background:rgba(239,68,68,0.1);color:var(--danger);border:1px solid rgba(239,68,68,0.3);">
                                                <i class="fa-solid fa-ban"></i> Reject
                                            </button>
                                        </div>
                                        <div id="reject-box-<?= $req['id'] ?>" style="display:none;margin-top:0.5rem;">
                                            <form method="POST" action="/share_hope/actions/admin_review_campaign_request.php">
                                                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <textarea name="rejection_reason" required placeholder="Reason for rejection..." rows="2" style="width:100%;padding:0.5rem;font-size:0.78rem;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--background);margin-bottom:0.35rem;"></textarea>
                                                <button type="submit" style="width:100%;padding:0.4rem;font-size:0.78rem;background:var(--danger);color:white;border:none;border-radius:var(--radius-sm);cursor:pointer;">Confirm Rejection</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>
<script>
function toggleRejectBox(id) {
    const box = document.getElementById('reject-box-' + id);
    box.style.display = box.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
