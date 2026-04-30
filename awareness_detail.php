<?php
session_start();
require_once __DIR__ . '/includes/header.php';

$campaign_id = intval($_GET['id'] ?? 0);

if (!$campaign_id) {
    $_SESSION['error'] = "Campaign not found.";
    header("Location: /share_hope/index.php");
    exit;
}

// Fetch awareness campaign details
$stmt = $pdo->prepare("
    SELECT ac.*, u.name as created_by_name
    FROM awareness_campaigns ac
    LEFT JOIN users u ON ac.created_by = u.id
    WHERE ac.id = ? AND ac.is_active = 1
    AND (ac.start_date IS NULL OR ac.start_date <= CURDATE())
    AND (ac.end_date IS NULL OR ac.end_date >= CURDATE())
");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    $_SESSION['error'] = "Campaign not found or is no longer active.";
    header("Location: /share_hope/index.php");
    exit;
}

// Determine priority color
$priority_colors = [
    'urgent' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'text' => '#ef4444', 'label' => 'Urgent'],
    'high' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'text' => '#f59e0b', 'label' => 'High'],
    'medium' => ['bg' => 'rgba(99, 102, 241, 0.1)', 'text' => '#6366f1', 'label' => 'Medium'],
    'low' => ['bg' => 'rgba(156, 163, 175, 0.1)', 'text' => '#9ca3af', 'label' => 'Low']
];

$priority_info = $priority_colors[$campaign['priority']] ?? $priority_colors['medium'];

// Determine audience label
$audience_labels = [
    'donors' => 'For Donors',
    'ngos' => 'For NGOs',
    'both' => 'For Everyone'
];
$audience_label = $audience_labels[$campaign['target_audience']] ?? 'For Everyone';

// Determine campaign type icon
$type_icons = [
    'awareness' => 'fa-lightbulb',
    'fundraising' => 'fa-hand-holding-heart',
    'education' => 'fa-book',
    'emergency' => 'fa-triangle-exclamation',
    'seasonal' => 'fa-calendar'
];
$type_icon = $type_icons[$campaign['campaign_type']] ?? 'fa-megaphone';
?>

<div class="container" style="padding: 4rem 0; max-width: 900px;">
    <!-- Back Button -->
    <div style="margin-bottom: 2rem;">
        <a href="/share_hope/index.php" style="color: var(--primary); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; width: fit-content;">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <!-- Main Content Card -->
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md);">
        
        <!-- Hero Image Section -->
        <?php if (!empty($campaign['image_url'])): ?>
            <div style="width: 100%; height: 400px; overflow: hidden; background: linear-gradient(135deg, var(--primary), var(--accent));">
                <img src="<?= h($campaign['image_url']) ?>" alt="<?= h($campaign['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        <?php else: ?>
            <div style="width: 100%; height: 400px; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid <?= $type_icon ?>" style="font-size: 5rem; color: rgba(255,255,255,0.3);"></i>
            </div>
        <?php endif; ?>

        <!-- Content Section -->
        <div style="padding: 3rem;">
            
            <!-- Header with Title and Badges -->
            <div style="margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <!-- Priority Badge -->
                    <span style="padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; background: <?= $priority_info['bg'] ?>; color: <?= $priority_info['text'] ?>;">
                        <i class="fa-solid fa-flag" style="margin-right: 0.4rem;"></i><?= $priority_info['label'] ?>
                    </span>
                    
                    <!-- Type Badge -->
                    <span style="padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; background: rgba(0, 102, 255, 0.1); color: var(--primary);">
                        <i class="fa-solid <?= $type_icon ?>" style="margin-right: 0.4rem;"></i><?= ucfirst(str_replace('_', ' ', $campaign['campaign_type'])) ?>
                    </span>
                    
                    <!-- Audience Badge -->
                    <span style="padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; background: rgba(0, 217, 255, 0.1); color: var(--accent);">
                        <i class="fa-solid fa-users" style="margin-right: 0.4rem;"></i><?= $audience_label ?>
                    </span>
                </div>

                <h1 style="margin: 0 0 1rem 0; font-size: 2.5rem; font-weight: 800; color: var(--text-main); line-height: 1.2;">
                    <?= h($campaign['title']) ?>
                </h1>

                <!-- Meta Information -->
                <div style="display: flex; gap: 2rem; flex-wrap: wrap; color: var(--text-muted); font-size: 0.95rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-regular fa-calendar"></i>
                        <span>Published <?= date('M j, Y', strtotime($campaign['created_at'])) ?></span>
                    </div>
                    <?php if (!empty($campaign['created_by_name'])): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-solid fa-user"></i>
                            <span>By <?= h($campaign['created_by_name']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($campaign['end_date'])): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-regular fa-clock"></i>
                            <span>Ends <?= date('M j, Y', strtotime($campaign['end_date'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Divider -->
            <div style="height: 1px; background: var(--border); margin: 2rem 0;"></div>

            <!-- Description -->
            <div style="margin-bottom: 2rem;">
                <h2 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Campaign Details</h2>
                <div style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.8; white-space: pre-wrap;">
                    <?= nl2br(h($campaign['description'])) ?>
                </div>
            </div>

            <!-- Action Section -->
            <?php if (!empty($campaign['action_link'])): ?>
                <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); padding: 2rem; border-radius: var(--radius-md); text-align: center; margin-bottom: 2rem;">
                    <p style="margin: 0 0 1rem 0; color: rgba(255,255,255,0.9); font-size: 1rem;">
                        Ready to make a difference?
                    </p>
                    <a href="<?= h($campaign['action_link']) ?>" class="btn" style="background: white; color: var(--primary); padding: 1rem 2rem; font-weight: 700; text-decoration: none; border-radius: var(--radius-sm); display: inline-block;">
                        <i class="fa-solid fa-arrow-right" style="margin-right: 0.5rem;"></i>Take Action Now
                    </a>
                </div>
            <?php endif; ?>

            <!-- Campaign Info Grid -->
            <div style="background: var(--background); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Campaign Information</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <!-- Type -->
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.5rem;">Campaign Type</div>
                        <div style="font-size: 1rem; font-weight: 600; color: var(--text-main);">
                            <i class="fa-solid <?= $type_icon ?>" style="margin-right: 0.5rem; color: var(--primary);"></i>
                            <?= ucfirst(str_replace('_', ' ', $campaign['campaign_type'])) ?>
                        </div>
                    </div>

                    <!-- Priority -->
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.5rem;">Priority Level</div>
                        <div style="font-size: 1rem; font-weight: 600; color: <?= $priority_info['text'] ?>;">
                            <i class="fa-solid fa-flag" style="margin-right: 0.5rem;"></i>
                            <?= $priority_info['label'] ?>
                        </div>
                    </div>

                    <!-- Target Audience -->
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.5rem;">Target Audience</div>
                        <div style="font-size: 1rem; font-weight: 600; color: var(--text-main);">
                            <i class="fa-solid fa-users" style="margin-right: 0.5rem; color: var(--accent);"></i>
                            <?= $audience_label ?>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.5rem;">Active Period</div>
                        <div style="font-size: 1rem; font-weight: 600; color: var(--text-main);">
                            <?php if (!empty($campaign['start_date'])): ?>
                                <?= date('M j, Y', strtotime($campaign['start_date'])) ?>
                            <?php else: ?>
                                From start
                            <?php endif; ?>
                            
                            <?php if (!empty($campaign['end_date'])): ?>
                                to <?= date('M j, Y', strtotime($campaign['end_date'])) ?>
                            <?php else: ?>
                                (Ongoing)
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.5rem;">Status</div>
                        <div style="font-size: 1rem; font-weight: 600;">
                            <span style="padding: 0.35rem 0.75rem; border-radius: 999px; background: rgba(16, 185, 129, 0.1); color: var(--secondary);">
                                <i class="fa-solid fa-circle-check" style="margin-right: 0.4rem;"></i>Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Share Section -->
            <div style="background: var(--background); padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Share This Campaign</h3>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="https://wa.me/?text=<?= urlencode($campaign['title'] . ' - ' . $campaign['description'] . ' ' . $_SERVER['HTTP_HOST'] . '/share_hope/awareness_detail.php?id=' . $campaign['id']) ?>" target="_blank" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($campaign['title'] . ' ' . $_SERVER['HTTP_HOST'] . '/share_hope/awareness_detail.php?id=' . $campaign['id']) ?>" target="_blank" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                        <i class="fa-brands fa-twitter"></i> Twitter
                    </a>
                    <button onclick="copyToClipboard('<?= $_SERVER['HTTP_HOST'] ?>/share_hope/awareness_detail.php?id=<?= $campaign['id'] ?>')" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                        <i class="fa-solid fa-link"></i> Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Campaigns Section -->
    <div style="margin-top: 4rem;">
        <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin-bottom: 2rem;">More Updates</h2>
        
        <?php
        $stmt = $pdo->query("
            SELECT id, title, campaign_type, priority, created_at
            FROM awareness_campaigns
            WHERE is_active = 1
            AND (start_date IS NULL OR start_date <= CURDATE())
            AND (end_date IS NULL OR end_date >= CURDATE())
            AND id != $campaign_id
            ORDER BY created_at DESC
            LIMIT 3
        ");
        $related = $stmt->fetchAll();
        
        if (!empty($related)):
        ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($related as $item): ?>
                    <a href="/share_hope/awareness_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none; display: block;">
                        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.5rem; transition: all 0.3s; cursor: pointer;" onmouseover="this.style.boxShadow='var(--shadow-md)'; this.style.borderColor='var(--primary)'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.borderColor='var(--border)'; this.style.transform='translateY(0)'">
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                                <span style="padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; background: rgba(0, 102, 255, 0.1); color: var(--primary);">
                                    <?= ucfirst($item['campaign_type']) ?>
                                </span>
                                <span style="padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; background: <?= $priority_colors[$item['priority']]['bg'] ?? 'rgba(99, 102, 241, 0.1)' ?>; color: <?= $priority_colors[$item['priority']]['text'] ?? '#6366f1' ?>;">
                                    <?= $priority_colors[$item['priority']]['label'] ?? 'Medium' ?>
                                </span>
                            </div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 700; color: var(--text-main); line-height: 1.3;">
                                <?= h($item['title']) ?>
                            </h3>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= date('M j, Y', strtotime($item['created_at'])) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(window.location.protocol + '//' + text).then(() => {
        alert('Link copied to clipboard!');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
