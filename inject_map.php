<?php
$content = file_get_contents('admin/dashboard.php');

$parts = preg_split('/<div\s+style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2\.5rem; flex-wrap: wrap; gap: 1rem;">\s+<h1 style="font-size: 2rem; margin: 0;">Admin Control Panel<\/h1>/s', $content);

if (count($parts) < 2) {
    echo "FAILED TO FIND TOP MARKER";
    exit;
}

$bottom = preg_split('/<!-- System Users Management -->/s', $parts[1]);

if (count($bottom) < 2) {
    echo "FAILED TO FIND BOTTOM MARKER";
    exit;
}

$new_block = <<<EOT
    <div class="admin-dashboard-grid" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2.5rem; margin-bottom: 3rem; align-items: stretch;">
        <!-- Left Column (Headers & Stats) -->
        <div style="display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
                <h1 style="font-size: 2rem; margin: 0;">Admin Control Panel</h1>

                <!-- Maintenance Mode Toggle -->
                <div style="display: flex; align-items: center; gap: 1rem; background: var(--surface); padding: 0.75rem 1.5rem; border-radius: 999px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                        <span style="font-size: 0.875rem; color: var(--text-muted);">Status:</span>
                        <?php if (file_exists(__DIR__ . '/../.maintenance_lock')): ?>
                            <span style="color: var(--danger); display: flex; align-items: center; gap: 0.25rem;"><i class="fa-solid fa-lock"></i> Maintenance Mode (Users Blocked)</span>
                        <?php else: ?>
                            <span style="color: var(--accent); display: flex; align-items: center; gap: 0.25rem;"><i class="fa-solid fa-globe"></i> Live (Normal Operation)</span>
                        <?php endif; ?>
                    </div>

                    <form action="/share_hope/actions/toggle_maintenance.php" method="POST" style="margin: 0; padding-left: 1rem; border-left: 1px solid var(--border);">
                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                        <?php if (file_exists(__DIR__ . '/../.maintenance_lock')): ?>
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.75rem; color: var(--accent); border-color: var(--accent);"><i class="fa-solid fa-play"></i> Go Live</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="enable">
                            <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.75rem; color: var(--danger); border-color: var(--danger);"><i class="fa-solid fa-pause"></i> Enter Maintenance Mode</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- System Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; flex-grow: 1;">
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1.5rem;">
                    <div style="width: 50px; height: 50px; background: rgba(79, 70, 229, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Users</div>
                        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);"><?= number_format(\$stats['total_users']) ?></div>
                    </div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1.5rem;">
                    <div style="width: 50px; height: 50px; background: rgba(16, 185, 129, 0.1); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-building-circle-check"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Verified NGOs</div>
                        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);"><?= number_format(\$stats['verified_ngos']) ?></div>
                    </div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1.5rem;">
                    <div style="width: 50px; height: 50px; background: rgba(245, 158, 11, 0.1); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">System Volume</div>
                        <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">KSh <?= number_format(\$stats['total_donations'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column (Map) -->
        <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden; height: 100%; min-height: 250px;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-size: 1.1rem;">
                    <i class="fa-solid fa-map-location-dot text-accent"></i> Active Impact Regions
                </h3>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: var(--accent); display: inline-block; animation: pulse 2s infinite;"></span>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Live System Sync</span>
                </div>
            </div>
            <div style="flex: 1; position: relative; background: var(--secondary);">
                <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d15955.166827029578!2d10.0!3d0.0!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2ske!4v1711204856036!5m2!1sen!2ske" width="100%" height="100%" style="border:0; opacity: 0.5; filter: grayscale(100%) contrast(1.2);" allowfullscreen="" loading="lazy"></iframe>
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at center, transparent 30%, rgba(30, 58, 138, 0.4) 100%); pointer-events: none;"></div>
                <div style="position: absolute; top: 40%; left: 30%; width: 10px; height: 10px; background: var(--accent); border-radius: 50%; box-shadow: 0 0 15px var(--accent); animation: pulse 2s infinite;"></div>
                <div style="position: absolute; top: 55%; left: 55%; width: 14px; height: 14px; background: var(--primary); border-radius: 50%; box-shadow: 0 0 20px var(--primary); animation: pulse 2.5s infinite;"></div>
                <div style="position: absolute; top: 30%; left: 70%; width: 8px; height: 8px; background: var(--accent); border-radius: 50%; box-shadow: 0 0 10px var(--accent); animation: pulse 1.5s infinite;"></div>
            </div>
        </div>
    </div>
    
    <style>@media (max-width: 1024px) { .admin-dashboard-grid { grid-template-columns: 1fr !important; } }</style>

EOT;

$final = $parts[0] . $new_block . "\n    <!-- System Users Management -->" . $bottom[1];
file_put_contents('admin/dashboard.php', $final);
echo "SUCCESS";
?>
