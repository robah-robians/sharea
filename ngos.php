<?php
require_once __DIR__ . '/includes/header.php';

// Fetch verified NGOs
$stmt = $pdo->query("SELECT n.*, u.name, u.email FROM ngos n JOIN users u ON n.user_id = u.id WHERE n.is_verified = 1 AND u.status = 'active'");
$ngos = $stmt->fetchAll();
?>
<section class="hero" style="padding: 4rem 0;">
    <div class="container">
        <h1>Our Partner NGOs</h1>
        <p>Discover and support verified organizations making a real impact on the ground.</p>
    </div>
</section>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<section class="section" style="padding-top: 0;">
    <div class="container">
        <!-- Map Container -->
        <div id="ngo-map"
            style="width: 100%; height: 500px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border); margin-bottom: 3rem; z-index: 1;">
        </div>

        <script>
            // Initialize map centered on Kenya
            var map = L.map('ngo-map').setView([0.0236, 37.9062], 6);

            // Add OpenStreetMap tiles (Free)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Fetch NGO data from PHP
            var ngos = <?= json_encode($ngos) ?>;

            // Plot markers
            var markers = [];
            ngos.forEach(function (ngo) {
                if (ngo.latitude && ngo.longitude) {
                    var marker = L.marker([ngo.latitude, ngo.longitude]).addTo(map);

                    var popupContent = `
                        <div style="text-align:center; padding: 0.5rem;">
                            <h4 style="margin: 0 0 0.5rem 0; font-family: 'Montserrat', sans-serif;">${ngo.name} <i class="fa-solid fa-circle-check" style="color:#10B981;"></i></h4>
                            <p style="margin: 0 0 1rem 0; font-size: 0.8rem; color: #64748B;">${ngo.email}</p>
                            <a href="/share_hope/ngo_profile.php?id=${ngo.id}" style="display:inline-block; background:#4F46E5; color:white; padding: 0.4rem 0.8rem; border-radius:4px; text-decoration:none; font-size:0.8rem; font-weight:600;">View Profile</a>
                        </div>
                    `;
                    marker.bindPopup(popupContent);
                    markers.push(marker);
                }
            });

            // Auto-zoom map to fit all markers if any exist
            if (markers.length > 0) {
                var group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        </script>
        <div class="grid">
            <?php foreach ($ngos as $ngo): ?>
                <div class="campaign-card" style="padding: 2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                        <div
                            style="width: 64px; height: 64px; background: var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--text-muted);">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <h3 style="margin-bottom: 0.25rem;"><?= h($ngo['name']) ?> <i
                                    class="fa-solid fa-circle-check text-secondary" title="Verified"></i></h3>
                            <p style="font-size: 0.875rem; color: var(--text-muted);"><i class="fa-solid fa-envelope"></i>
                                <?= h($ngo['email']) ?></p>
                        </div>
                    </div>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem; flex-grow: 1; font-size: 0.95rem;">
                        <?= h(mb_substr($ngo['mission'], 0, 120)) ?>...
                    </p>
                    <a href="/share_hope/ngo_profile.php?id=<?= $ngo['id'] ?>" class="btn btn-outline"
                        style="width: 100%;">View Profile & Campaigns</a>
                </div>
            <?php endforeach; ?>

            <?php if (empty($ngos)): ?>
                <div
                    style="grid-column: 1 / -1; text-align: center; padding: 4rem; background: var(--surface); border-radius: var(--radius-lg);">
                    <i class="fa-solid fa-seedling" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                    <h3>No verified NGOs yet.</h3>
                    <p style="color: var(--text-muted);">Check back soon for updates or <a
                            href="/share_hope/register.php?role=ngo" class="text-primary">register your organization</a>.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>