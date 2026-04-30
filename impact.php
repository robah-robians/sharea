<?php
require_once __DIR__ . '/includes/header.php';

// Fetch Impact Statistics
$stmt = $pdo->query("SELECT COUNT(*) as completed FROM campaigns WHERE current_amount >= goal_amount OR status = 'completed'");
$completed_campaigns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(amount) as total_deployed FROM donations WHERE status = 'completed'");
$total_deployed = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) as active_ngos FROM ngos WHERE is_verified = 1");
$verified_ngos = $stmt->fetchColumn();

// Fetch Recent Transparent Ledger Actions
$stmt = $pdo->query("
    SELECT d.amount, d.created_at, c.title as campaign_title, u.name as donor_name 
    FROM donations d 
    JOIN campaigns c ON d.campaign_id = c.id 
    JOIN users u ON d.donor_id = u.id 
    WHERE d.status = 'completed' AND d.is_anonymous = 0
    ORDER BY d.created_at DESC 
    LIMIT 6
");
$recent_ledger = $stmt->fetchAll();
?>
<section class="hero" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 4rem 0; text-align: center; border-radius: 0 0 2rem 2rem; margin-bottom: 4rem;">
    <div class="container">
        <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 1rem; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Global Impact & <span style="font-weight: 300;">Transparency</span></h1>
        <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">Explore the verifiable metrics and live ledger validating the active initiatives our partners deploy globally.</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div style="display: flex; flex-wrap: wrap; gap: 2rem; margin-bottom: 3rem; align-items: stretch;">

            <!-- Left Column: Public Donor NGO Map -->
            <div style="flex: 2 1 600px; background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background);">
                    <h3 style="margin: 0; color: var(--text-main);"><i class="fa-solid fa-map-location-dot text-secondary"></i> Active Impact Regions</h3>
                </div>
                <div style="flex-grow: 1; min-height: 450px; width: 100%; background: #e2e8f0; position: relative;" id="donor-map">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-earth-africa" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Loading System Map Variables...</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Dynamic Impact Metrics -->
            <div style="flex: 1 1 300px; display: flex; flex-direction: column; gap: 1.5rem;">
                <div style="padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--surface); box-shadow: var(--shadow-sm); text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                    <i class="fa-solid fa-vault text-primary" style="font-size: 2.25rem; margin-bottom: 0.75rem;"></i>
                    <div style="font-size: 2.25rem; font-weight: 800; color: var(--text-main);">$<?= number_format($total_deployed) ?></div>
                    <div style="text-transform: uppercase; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); letter-spacing: 1px;">Capital Deployed</div>
                </div>
                
                <div style="padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--surface); box-shadow: var(--shadow-sm); text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                    <i class="fa-solid fa-flag-checkered text-secondary" style="font-size: 2.25rem; margin-bottom: 0.75rem;"></i>
                    <div style="font-size: 2.25rem; font-weight: 800; color: var(--text-main);"><?= number_format($completed_campaigns) ?></div>
                    <div style="text-transform: uppercase; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); letter-spacing: 1px;">Completed Missions</div>
                </div>
                
                <div style="padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--surface); box-shadow: var(--shadow-sm); text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                    <i class="fa-solid fa-shield-check text-accent" style="font-size: 2.25rem; margin-bottom: 0.75rem;"></i>
                    <div style="font-size: 2.25rem; font-weight: 800; color: var(--text-main);"><?= number_format($verified_ngos) ?></div>
                    <div style="text-transform: uppercase; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); letter-spacing: 1px;">Verified Nodes (NGOs)</div>
                </div>
            </div>
            
        </div>

        <!-- Add Leaflet Map Library -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var map = L.map('donor-map').setView([-1.2921, 36.8219], 6); // Kenya focus
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Improvised Campaign Deployment points
                L.marker([-1.2921, 36.8219]).addTo(map).bindPopup('<div style="font-family: var(--font-primary)"><b style="color:var(--primary);">Nairobi Node</b><br>Clean Water Initiative<br><span style="color:var(--text-muted); font-size: 0.8rem;">Status: Active</span></div>');
                L.marker([-0.0917, 34.7680]).addTo(map).bindPopup('<div style="font-family: var(--font-primary)"><b style="color:var(--primary);">Kisumu Node</b><br>Youth Education Drive<br><span style="color:var(--text-muted); font-size: 0.8rem;">Status: Active</span></div>');
                L.marker([-4.0435, 39.6682]).addTo(map).bindPopup('<div style="font-family: var(--font-primary)"><b style="color:var(--primary);">Mombasa Node</b><br>Coastal Relief<br><span style="color:var(--text-muted); font-size: 0.8rem;">Status: Active</span></div>');

                setTimeout(function () { map.invalidateSize(); }, 500);
            });
        </script>

        <h2 class="section-title">Live Transparency Ledger</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Real-time stream of the latest verifiable capital entering active regions.</p>
        <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
            <?php if (empty($recent_ledger)): ?>
                <div style="padding: 3rem; text-align: center; color: var(--text-muted);">No transparent transactions synced to ledger yet.</div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border); background: var(--background); font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">
                            <th style="padding: 1.25rem 1.5rem;">Timestamp</th>
                            <th style="padding: 1.25rem 1.5rem;">Verified Donor</th>
                            <th style="padding: 1.25rem 1.5rem;">Target Initiative</th>
                            <th style="padding: 1.25rem 1.5rem; text-align: right;">Amount Routed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_ledger as $entry): ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 1.25rem 1.5rem; color: var(--text-muted); font-family: monospace; font-size: 0.85rem;">
                                    <?= date('M j, Y - H:i', strtotime($entry['created_at'])) ?>
                                </td>
                                <td style="padding: 1.25rem 1.5rem; font-weight: 600; color: var(--text-main);">
                                    <i class="fa-solid fa-user-shield text-secondary" style="margin-right: 0.5rem;"></i> Verified Supporter
                                </td>
                                <td style="padding: 1.25rem 1.5rem; font-weight: 500; color: var(--primary);">
                                    <?= h($entry['campaign_title']) ?>
                                </td>
                                <td style="padding: 1.25rem 1.5rem; text-align: right; font-weight: 700; color: var(--accent); font-family: monospace; font-size: 1.1rem;">
                                    +$<?= number_format($entry['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>