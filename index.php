<?php
require_once __DIR__ . '/includes/header.php';

// Try to fetch real campaigns, fallback to dummy data if not configured
$query = "SELECT c.*, n.user_id, u.name as ngo_name 
          FROM campaigns c 
          JOIN ngos n ON c.ngo_id = n.id 
          JOIN users u ON n.user_id = u.id
          WHERE c.status = 'active'
          ORDER BY c.created_at DESC LIMIT 3";
$campaigns = [];
try {
    $stmt = $pdo->query($query);
    if ($stmt) {
        $campaigns = $stmt->fetchAll();
    }

    // Fetch recent donations for ticker
    $stmtTicker = $pdo->query("SELECT d.amount, u.name as donor_name, c.title as campaign_title 
                               FROM donations d 
                               JOIN campaigns c ON d.campaign_id = c.id
                               LEFT JOIN users u ON d.donor_id = u.id
                               WHERE d.status = 'completed'
                               ORDER BY d.created_at DESC LIMIT 10");
    $recent_donations = $stmtTicker ? $stmtTicker->fetchAll() : [];

} catch (\PDOException $e) {
    // Database isn't fully set up or has no campaigns
}
?>

<section class="hero">
    <div class="container">
        <h1>Empower Change. <br>Fund Hope.</h1>
        <p>Join a community of thousands making a tangible difference. Discover verified NGOs and transparent campaigns
            that match your passion.</p>
        <div class="hero-actions">
            <a href="/share_hope/campaigns.php" class="btn btn-primary"
                style="padding: 1rem 2rem; font-size: 1.125rem;">Start Donating</a>
            <a href="/share_hope/register.php?role=ngo" class="btn btn-outline"
                style="padding: 1rem 2rem; font-size: 1.125rem; margin-left: 1rem;">Register as an NGO</a>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <h3>KSh 4.2M+</h3>
                <p>Funds Raised</p>
            </div>
            <div class="stat-item">
                <h3>150+</h3>
                <p>Verified NGOs</p>
            </div>
            <div class="stat-item">
                <h3>12K+</h3>
                <p>Global Donors</p>
            </div>
        </div>
    </div>
    </div>
</section>

<?php if (!empty($recent_donations)): ?>
    <!-- Live Donation Ticker -->
    <div class="donation-ticker-wrapper">
        <div class="donation-ticker">
            <?php foreach ($recent_donations as $don): ?>
                <div class="ticker-item">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <?= h($don['donor_name'] ?: 'Anonymous') ?> donated
                    <span>KSh <?= number_format($don['amount']) ?></span> to
                    <?= h($don['campaign_title']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<section class="section animate-on-scroll">
    <div class="container">
        <h2 class="section-title">Urgent Campaigns</h2>
        <div class="grid">
            <?php if (count($campaigns) > 0): ?>
                <?php foreach ($campaigns as $camp): ?>
                    <?php
                    $percent = ($camp['goal_amount'] > 0) ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0;
                    ?>
                    <div class="campaign-card">
                        <div class="campaign-img">
                            <div class="campaign-badge">Campaign</div>
                            <img src="<?= h($camp['image_url'] ?: 'https://images.unsplash.com/photo-1593113565694-c6f13e46c759?q=80&w=800&auto=format&fit=crop') ?>"
                                alt="Campaign Image">
                        </div>
                        <div class="campaign-content">
                            <div class="ngo-name">
                                <?= h($camp['ngo_name']) ?> <i class="fa-solid fa-circle-check verified-icon"
                                    title="Verified NGO"></i>
                            </div>
                            <h3><?= h($camp['title']) ?></h3>
                            <div class="progress-container">
                                <div class="progress-stats">
                                    <span>KSh <?= number_format($camp['current_amount'], 2) ?> raised</span>
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
            <?php else: ?>
                <!-- Dummy Data Fallback -->
                <div class="campaign-card">
                    <div class="campaign-img">
                        <div class="campaign-badge">Education</div>
                        <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=800&auto=format&fit=crop"
                            alt="Build a School">
                    </div>
                    <div class="campaign-content">
                        <div class="ngo-name">Education First <i class="fa-solid fa-circle-check verified-icon"></i></div>
                        <h3>Build a School in Rural Kenya</h3>
                        <div class="progress-container">
                            <div class="progress-stats">
                                <span>KSh 15,000 raised</span>
                                <span>60%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" data-width="60%"></div>
                            </div>
                            <a href="#" class="btn btn-primary" style="width: 100%;">Donate Now</a>
                        </div>
                    </div>
                </div>

                <div class="campaign-card">
                    <div class="campaign-img">
                        <div class="campaign-badge">Health</div>
                        <img src="https://images.unsplash.com/photo-1584515933487-779824d29309?q=80&w=800&auto=format&fit=crop"
                            alt="Medical Supplies">
                    </div>
                    <div class="campaign-content">
                        <div class="ngo-name">HealthBridge <i class="fa-solid fa-circle-check verified-icon"></i></div>
                        <h3>Emergency Medical Supplies Delivery</h3>
                        <div class="progress-container">
                            <div class="progress-stats">
                                <span>KSh 40,000 raised</span>
                                <span>80%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" data-width="80%"></div>
                            </div>
                            <a href="#" class="btn btn-primary" style="width: 100%;">Donate Now</a>
                        </div>
                    </div>
                </div>

                <div class="campaign-card">
                    <div class="campaign-img">
                        <div class="campaign-badge">Disaster Relief</div>
                        <img src="https://images.unsplash.com/photo-1588681664899-f142ff2dc9b1?q=80&w=800&auto=format&fit=crop"
                            alt="Flood Relief">
                    </div>
                    <div class="campaign-content">
                        <div class="ngo-name">Global Rescue <i class="fa-solid fa-circle-check verified-icon"></i></div>
                        <h3>Flood Relief Operations - Southeast Asia</h3>
                        <div class="progress-container">
                            <div class="progress-stats">
                                <span>KSh 5,000 raised</span>
                                <span>10%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" data-width="10%"></div>
                            </div>
                            <a href="#" class="btn btn-primary" style="width: 100%;">Donate Now</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    </div>
</section>

<!-- Scroll Animations Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>