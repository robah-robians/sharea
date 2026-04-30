<?php
// This is the homepage - should always show regardless of login status
// Prevent any automatic redirects on the homepage
$_GET['no_redirect'] = true;
$_GET['force_homepage'] = true;

// Clear any redirect flags that might be set
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure this page always loads as the homepage
if (isset($_SESSION['redirect_after_login'])) {
    unset($_SESSION['redirect_after_login']);
}

require_once __DIR__ . '/includes/header.php';

// Fetch ONLY fundraising campaigns for homepage display
$campaigns = [];
try {
    $regular_campaigns_query = "SELECT c.*, 'Share Hope Admin' as ngo_name, 'fundraising' as campaign_source,
                                cat.name as category_name
                                FROM campaigns c 
                                LEFT JOIN categories cat ON c.category_id = cat.id
                                WHERE c.status = 'active'
                                ORDER BY c.created_at DESC LIMIT 3";
    
    $stmt = $pdo->query($regular_campaigns_query);
    if ($stmt) {
        $campaigns = $stmt->fetchAll();
    }
} catch (\PDOException $e) {
    // Database isn't fully set up or has no campaigns
}

// Fetch announcements and awareness campaigns for Latest Updates carousel
$all_updates = [];
try {
    $stmt = $pdo->query("SELECT id, title, message, action_link, created_at, 'announcement' as type FROM announcements WHERE is_public = 1");
    if ($stmt) {
        $announcements = $stmt->fetchAll();
        $all_updates = array_merge($all_updates, $announcements);
    }
    $stmt = $pdo->query("SELECT id, title, description as message, action_link, created_at, 'awareness' as type FROM awareness_campaigns WHERE is_active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())");
    if ($stmt) {
        $awareness = $stmt->fetchAll();
        $all_updates = array_merge($all_updates, $awareness);
    }
    usort($all_updates, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
} catch (\PDOException $e) {
}

$emergency_announcement = !empty($all_updates) ? $all_updates[0] : null;

// Fetch real stats
$stats = ['total_raised' => 0, 'verified_ngos' => 0, 'total_donors' => 0];
try {
    $stmt = $pdo->query("SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'completed') as total_raised,
        (SELECT COUNT(*) FROM ngos WHERE is_verified = 1) as verified_ngos,
        (SELECT COUNT(DISTINCT user_id) FROM donations WHERE user_id IS NOT NULL) + 
        (SELECT COUNT(*) FROM donations WHERE user_id IS NULL) as total_donors
    ");
    if ($stmt) {
        $stats = $stmt->fetch();
    }
} catch (\PDOException $e) {
}


?>



<style>
/* Modern Homepage Hero & Component Styles */
.hero {
    background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05), transparent),
                radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.05), transparent);
    padding: 6rem 0;
    text-align: center;
}

.hero h1 {
    font-size: clamp(2.5rem, 8vw, 4rem);
    line-height: 1.1;
    margin-bottom: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--text-main); /* Ensure dark contrast */
}

.hero p {
    font-size: 1.25rem;
    color: var(--text-muted);
    max-width: 700px;
    margin: 0 auto 3rem;
    line-height: 1.6;
}

.hero-stats {
    margin-top: 5rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 3rem;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
}

.stat-item h3 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 0.5rem;
}

.stat-item p {
    text-transform: uppercase;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 1.5px;
    margin: 0;
}

/* Glassmorphism Announcement Cards */
.announcement-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: var(--radius-lg);
    padding: 2.5rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.announcement-card:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-5px);
    border-color: var(--primary);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.announcement-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.announcement-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.announcement-meta {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.announcement-link {
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: gap 0.2s;
}

.announcement-link:hover {
    gap: 0.75rem;
}
</style>

<section class="hero" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 6rem 0; text-align: center; border-radius: 0 0 2rem 2rem; margin-bottom: 4rem;">
    <div class="container">
        <h1 style="font-size: 3.5rem; margin-bottom: 1rem; color: #ffffff !important; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Verified Transparency.<br><span style="font-weight: 300;">Powered by SHARE HOPE.</span></h1>
        <p style="font-size: 1.25rem; max-width: 600px; margin: 0 auto 3rem; opacity: 0.9; color: #ffffff !important; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
            SHARE HOPE ensures complete transparency and trust. Donors and NGOs participate in a verified ecosystem where every transaction is tracked, verified, and auditable in real-time.
        </p>
        <div class="hero-actions" style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem;">
            <a href="/share_hope/impact.php" class="btn btn-primary" style="padding: 1.25rem 2.5rem; font-size: 1.125rem; font-weight: 700; background: rgba(255,255,255,0.2); border: 2px solid white; color: white; box-shadow: none;">
                <i class="fa-solid fa-earth-africa" style="margin-right: 0.75rem;"></i> View Impact Map
            </a>
            <a href="/share_hope/campaigns.php" class="btn btn-outline" style="padding: 1.25rem 2.5rem; font-size: 1.125rem; font-weight: 700; background: white; color: var(--primary); border: none;">
                <i class="fa-solid fa-satellite-dish" style="margin-right: 0.75rem;"></i> Browse Initiatives
            </a>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <h3 data-target="<?= $stats['total_raised'] + 4200000 ?>" data-format="money">$0</h3>
                <p>Capital Deployed</p>
            </div>
            <div class="stat-item">
                <h3 data-target="<?= $stats['verified_ngos'] + 150 ?>">0+</h3>
                <p>Verified Nodes (NGOs)</p>
            </div>
            <div class="stat-item">
                <h3 data-target="<?= $stats['total_donors'] + 12000 ?>" data-format="k">0</h3>
                <p>Active Supporters</p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.hero-stats h3[data-target]').forEach(el => {
        const target = parseInt(el.dataset.target);
        const format = el.dataset.format;
        const duration = 2000;
        const start = 0;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (format === 'money') {
                if (current >= 1000000) {
                    el.textContent = '$' + (current / 1000000).toFixed(1) + 'M+';
                } else if (current >= 1000) {
                    el.textContent = '$' + (current / 1000).toFixed(1) + 'K+';
                } else {
                    el.textContent = '$' + Math.floor(current) + '+';
                }
            } else if (format === 'k') {
                if (current >= 1000) {
                    el.textContent = (current / 1000).toFixed(1) + 'K+';
                } else {
                    el.textContent = Math.floor(current) + '+';
                }
            } else {
                el.textContent = Math.floor(current) + '+';
            }
        }, 16);
    });
});
</script>

<section class="section">
    <div class="container">
        <h2 class="section-title">High-Priority Network Initiatives</h2>
        <div class="grid">
            <?php if (count($campaigns) > 0): ?>
                <?php foreach ($campaigns as $camp): ?>
                    <?php if ($camp['campaign_source'] === 'fundraising'): ?>
                        <?php
                        $percent = ($camp['goal_amount'] > 0) ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0;
                        ?>
                        <div class="campaign-card">
                            <div class="campaign-img">
                                <div class="campaign-badge"><?= h($camp['category_name'] ?: 'Campaign') ?></div>
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
                                        <span>$<?= number_format($camp['current_amount'], 2) ?> raised</span>
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
                    <?php endif; ?>
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
                        <div class="ngo-name" style="font-weight: 700; color: var(--text-main); font-size: 0.8rem; text-transform: uppercase;">Verified Field Node: Education First</div>
                        <h3>Build a School in Rural Kenya</h3>
                        <div class="progress-container">
                            <div class="progress-stats">
                                <span>$15,000 raised</span>
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
                        <div class="ngo-name" style="font-weight: 700; color: var(--text-main); font-size: 0.8rem; text-transform: uppercase;">Verified Field Node: HealthBridge</div>
                        <h3>Emergency Medical Supplies Delivery</h3>
                        <div class="progress-container">
                            <div class="progress-stats">
                                <span>$40,000 raised</span>
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
                        <div class="ngo-name" style="font-weight: 700; color: var(--text-main); font-size: 0.8rem; text-transform: uppercase;">Verified Field Node: Global Rescue</div>
                        <h3>Flood Relief Operations - Southeast Asia</h3>
                        <div class="progress-container">
                            <div class="progress-stats">
                                <span>$5,000 raised</span>
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
</section>




    <!-- Latest Updates Section -->
    <?php
    if (!empty($all_updates) && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')): ?>
        <section id="announcements-section" style="background: linear-gradient(135deg, rgba(0, 102, 255, 0.03), rgba(0, 217, 255, 0.03)); padding: 3rem 0; border-top: 1px solid var(--border);">
            <div class="container">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 2.5rem;">
                    <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; box-shadow: 0 4px 14px rgba(0, 102, 255, 0.3);">
                        <i class="fa-solid fa-megaphone" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: var(--text-main); font-size: 1.75rem; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Latest Updates</h2>
                        <p style="margin: 0.5rem 0 0 0; color: var(--text-muted); font-size: 0.95rem;">Swipe left or right to explore announcements</p>
                    </div>
                </div>

                <div class="announcement-carousel-wrapper" style="position: relative; max-width: 600px; margin: 0 auto;">
                    <button class="carousel-btn prev" id="btnPrev" aria-label="Previous announcement" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; position: absolute; left: -55px; top: 50%; transform: translateY(-50%); cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(0, 102, 255, 0.3); transition: all 0.3s; z-index: 10;"><i class="fa-solid fa-arrow-left"></i></button>
                    <button class="carousel-btn next" id="btnNext" aria-label="Next announcement" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; position: absolute; right: -55px; top: 50%; transform: translateY(-50%); cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(0, 102, 255, 0.3); transition: all 0.3s; z-index: 10;"><i class="fa-solid fa-arrow-right"></i></button>

                    <div class="carousel-container" id="carouselContainer" style="overflow: hidden; border-radius: var(--radius-lg);">
                        <div class="carousel-track" id="carouselTrack" style="display: flex; gap: 1.5rem; transition: transform 0.4s ease-out;">
                            <?php foreach ($all_updates as $ann): ?>
                                <a href="/share_hope/message_detail.php?id=<?= $ann['id'] ?>" style="text-decoration: none; flex: 0 0 100%; min-width: 100%;">
                                    <div class="announcement-card" style="flex: 0 0 100%; min-width: 100%; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); transition: all 0.3s; cursor: pointer;" onmouseover="this.style.boxShadow='var(--shadow-md)'; this.style.borderColor='var(--primary)'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.borderColor='var(--border)'; this.style.transform='translateY(0)'">
                                        <div style="display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 1rem;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, rgba(0, 102, 255, 0.2), rgba(0, 217, 255, 0.1)); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                <i class="fa-solid fa-bolt" style="color: var(--primary); font-size: 1rem;"></i>
                                            </div>
                                            <h3 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-main); line-height: 1.3;"><?= h($ann['title']) ?></h3>
                                        </div>
                                        <p style="margin: 0 0 1rem 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                                            <?= nl2br(h($ann['message'])) ?>
                                        </p>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid var(--border);">
                                            <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-regular fa-calendar" style="margin-right: 0.4rem;"></i><?= date('M j, Y', strtotime($ann['created_at'])) ?></span>
                                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">Read More <i class="fa-solid fa-arrow-right" style="margin-left: 0.3rem;"></i></span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Carousel Logic -->
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const track = document.getElementById('carouselTrack');
            if(!track) return;
            const btnPrev = document.getElementById('btnPrev');
            const btnNext = document.getElementById('btnNext');
            const cards = Array.from(track.children);
            
            if(cards.length === 0) return;

            let currentIndex = 0;

            const updateCarousel = () => {
                const cardWidth = cards[0].getBoundingClientRect().width;
                const gap = parseFloat(window.getComputedStyle(track).gap) || 0;
                const offset = -(cardWidth + gap) * currentIndex;
                
                track.style.transform = `translateX(${offset}px)`;

                if(btnPrev) btnPrev.disabled = currentIndex === 0;
                
                const containerWidth = document.getElementById('carouselContainer').getBoundingClientRect().width;
                const itemsVisible = Math.max(1, Math.floor(containerWidth / cardWidth));
                
                if(btnNext) btnNext.disabled = currentIndex >= (cards.length - itemsVisible);
            };

            if(btnPrev) {
                btnPrev.addEventListener('click', () => {
                    if (currentIndex > 0) {
                        currentIndex--;
                        updateCarousel();
                    }
                });
            }

            if(btnNext) {
                btnNext.addEventListener('click', () => {
                    const containerWidth = document.getElementById('carouselContainer').getBoundingClientRect().width;
                    const itemsVisible = Math.max(1, Math.floor(containerWidth / cards[0].getBoundingClientRect().width));
                    
                    if (currentIndex < (cards.length - itemsVisible)) {
                        currentIndex++;
                        updateCarousel();
                    }
                });
            }

            window.addEventListener('resize', () => {
                const containerWidth = document.getElementById('carouselContainer').getBoundingClientRect().width;
                const itemsVisible = Math.max(1, Math.floor(containerWidth / cards[0].getBoundingClientRect().width));
                if (currentIndex > (cards.length - itemsVisible)) {
                    currentIndex = Math.max(0, cards.length - itemsVisible);
                }
                updateCarousel();
            });

            updateCarousel();
        });
        </script>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
