<?php
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT n.*, u.name, u.email, u.phone FROM ngos n JOIN users u ON n.user_id = u.id WHERE n.id = ? AND n.is_verified = 1");
$stmt->execute([$id]);
$ngo = $stmt->fetch();

if (!$ngo) {
    echo "<div class='container' style='padding:4rem 0;'><h2>NGO Not Found or Not Verified.</h2></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch campaigns for this NGO
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE ngo_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$campaigns = $stmt->fetchAll();
?>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<section class="hero" style="padding: 4rem 0; background: var(--surface);">
    <div class="container"
        style="text-align: left; display: flex; align-items: flex-start; gap: 2rem; flex-wrap: wrap;">
        <div
            style="width: 100px; height: 100px; background: var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--text-muted); flex-shrink: 0;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div style="flex-grow: 1;">
            <h1 style="margin-bottom: 0.5rem; font-size: 2.5rem;"><?= h($ngo['name']) ?> <i
                    class="fa-solid fa-circle-check text-secondary" style="font-size: 1.5rem;" title="Verified"></i>
            </h1>
            <p style="color: var(--text-muted); font-size: 1.125rem; margin-bottom: 1rem;"><i
                    class="fa-solid fa-envelope"></i> <?= h($ngo['email']) ?> &nbsp;|&nbsp; <i
                    class="fa-solid fa-phone"></i> <?= h($ngo['phone'] ?: 'N/A') ?></p>
            <div
                style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: stretch; margin-top: 2rem;">
                <div
                    style="background: var(--background); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; flex-direction: column;">
                    <h4 style="margin-top: 0; margin-bottom: 0.5rem;">Mission Statement</h4>
                    <p style="color: var(--text-main); line-height: 1.8; flex-grow: 1;"><?= nl2br(h($ngo['mission'])) ?>
                    </p>
                </div>

                <?php if ($ngo['latitude'] && $ngo['longitude']): ?>
                    <!-- NGO Location Map -->
                    <div id="ngo-profile-map"
                        style="width: 100%; min-height: 250px; border-radius: var(--radius-md); border: 1px solid var(--border); z-index: 1;">
                    </div>
                    <script>
                        var map = L.map('ngo-profile-map').setView([<?= $ngo['latitude'] ?>, <?= $ngo['longitude'] ?>], 12);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 18,
                            attribution: '© OpenStreetMap'
                        }).addTo(map);
                        L.marker([<?= $ngo['latitude'] ?>, <?= $ngo['longitude'] ?>]).addTo(map)
                            .bindPopup('<b><?= h($ngo['name']) ?></b><br>Headquarters').openPopup();
                    </script>
                <?php endif; ?>
            </div>

            <?php if (!empty($ngo['description'])): ?>
                <div style="margin-top: 1.5rem;">
                    <h4 style="margin-bottom: 0.5rem;">About</h4>
                    <p style="color: var(--text-muted); line-height: 1.8;"><?= nl2br(h($ngo['description'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Active Campaigns</h2>
        <div class="grid">
            <?php foreach ($campaigns as $camp): ?>
                <?php
                $percent = ($camp['goal_amount'] > 0) ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0;
                ?>
                <div class="campaign-card">
                    <div class="campaign-img">
                        <div class="campaign-badge"><?= ucfirst($camp['status']) ?></div>
                        <img src="<?= h($camp['image_url'] ?: 'https://images.unsplash.com/photo-1593113565694-c6f13e46c759?q=80&w=800&auto=format&fit=crop') ?>"
                            alt="Campaign Image">
                    </div>
                    <div class="campaign-content">
                        <h3><?= h($camp['title']) ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
                            <?= h(mb_substr($camp['description'], 0, 100)) ?>...</p>
                        <div class="progress-container">
                            <div class="progress-stats">
                                <span>KSh <?= number_format($camp['current_amount'], 2) ?> raised of KSh
                                    <?= number_format($camp['goal_amount'], 2) ?></span>
                                <span><?= $percent ?>%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" data-width="<?= $percent ?>%"></div>
                            </div>
                            <a href="/share_hope/donate.php?campaign_id=<?= $camp['id'] ?>" class="btn btn-primary"
                                style="width: 100%;">Donate Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($campaigns)): ?>
                <div
                    style="grid-column: 1 / -1; text-align: center; padding: 4rem; background: var(--surface); border-radius: var(--radius-lg);">
                    <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                    <h3>No campaigns available.</h3>
                    <p class="text-muted">This NGO hasn't launched any campaigns recently.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>