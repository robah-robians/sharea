<?php
require_once __DIR__ . '/includes/header.php';

// Fetch categories for filtering
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// Construct dynamic query
$search = trim($_GET['search'] ?? '');
$category_id = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? 'recent';

$where_clauses = ["c.status = 'active'"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(c.title LIKE ? OR n.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id !== 'all' && is_numeric($category_id)) {
    $where_clauses[] = "c.category_id = ?";
    $params[] = $category_id;
}

$where_sql = implode(' AND ', $where_clauses);

$order_sql = "c.created_at DESC";
if ($sort === 'funded') {
    $order_sql = "(c.current_amount / c.goal_amount) DESC";
} elseif ($sort === 'closing') {
    $order_sql = "c.deadline ASC";
}

$query = "SELECT c.*, 'Share Hope Admin' as ngo_name, cat.name as category_name
          FROM campaigns c 
          LEFT JOIN categories cat ON c.category_id = cat.id
          WHERE $where_sql
          ORDER BY $order_sql";

$campaigns = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();
} catch (\PDOException $e) {}

// Fetch categories for filtering
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>

<style>
/* Campaigns Hub Specific Styles */
.initiatives-hero {
    background: var(--background);
    padding: 5rem 0 3rem;
    text-align: center;
    border-bottom: 1px solid var(--border);
}

.filter-hub {
    max-width: 900px; 
    margin: -2.5rem auto 3rem; 
    background: var(--surface); 
    padding: 0.75rem; 
    border-radius: var(--radius-lg); 
    box-shadow: var(--shadow-md); 
    border: 1px solid var(--border);
    display: flex; 
    flex-wrap: wrap; 
    gap: 0.75rem; 
    align-items: center;
    backdrop-filter: blur(10px);
}

.filter-input-group {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.filter-input-group i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.filter-control {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    background: var(--background);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    color: var(--text-main);
    font-size: 0.95rem;
    transition: all 0.2s;
}

.filter-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
    outline: none;
}

.filter-select {
    padding-left: 1rem;
    cursor: pointer;
}

/* Search Autocomplete Styles */
.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--surface);
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 var(--radius-md) var(--radius-md);
    max-height: 300px;
    overflow-y: auto;
    box-shadow: var(--shadow-md);
    z-index: 100;
    display: none;
}

.search-suggestions.active {
    display: block;
}

.suggestion-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.suggestion-item:hover {
    background: var(--background);
    padding-left: 1.25rem;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(0, 102, 255, 0.2), rgba(0, 217, 255, 0.1));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.9rem;
}

.suggestion-content {
    flex: 1;
    min-width: 0;
}

.suggestion-title {
    font-weight: 600;
    color: var(--text-main);
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
}

.suggestion-meta {
    font-size: 0.8rem;
    color: var(--text-muted);
}
</style>

<section class="initiatives-hero" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 5rem 0 3rem; text-align: center; border-radius: 0 0 2rem 2rem; margin-bottom: 4rem;">
    <div class="container">
        <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 1rem; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Explore <span style="font-weight: 300;">Verified Initiatives</span></h1>
        <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem; max-width: 600px; margin: 0 auto; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">Intelligence feed of active field operations globally.</p>
    </div>
</section>

<div class="container">
    <form method="GET" action="campaigns.php" class="filter-hub">
        <div class="filter-input-group">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" name="search" placeholder="Search by operation title..." value="<?= h($search) ?>" class="filter-control" autocomplete="off">
            <div class="search-suggestions" id="searchSuggestions"></div>
        </div>
        
        <div style="min-width: 180px; flex: 0 1 auto;">
            <select name="category" class="filter-control filter-select">
                <option value="all" <?= $category_id === 'all' ? 'selected' : '' ?>>All Sectors</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (string)$category_id === (string)$cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="min-width: 180px; flex: 0 1 auto;">
            <select name="sort" class="filter-control filter-select">
                <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Sync: Recent</option>
                <option value="funded" <?= $sort === 'funded' ? 'selected' : '' ?>>Sync: Priority</option>
                <option value="closing" <?= $sort === 'closing' ? 'selected' : '' ?>>Sync: Timeline</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-filter"></i> Apply Filters
        </button>
    </form>
</div>

<section class="section">
    <div class="container">
        <div class="grid">
            <?php if (count($campaigns) > 0): ?>
                <?php foreach($campaigns as $camp): ?>
                    <?php 
                        $percent = ($camp['goal_amount'] > 0) ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0;
                    ?>
                    <div class="campaign-card">
                        <div class="campaign-img">
                            <div class="campaign-badge" style="background: rgba(16, 185, 129, 0.9);"><i class="fa-solid fa-satellite-dish"></i> LIVE TRACKING</div>
                            <img src="<?= h($camp['image_url'] ?: 'https://images.unsplash.com/photo-1593113565694-c6f13e46c759?q=80&w=800&auto=format&fit=crop') ?>" alt="Initiative Image">
                        </div>
                        <div class="campaign-content">
                            <div class="ngo-name" style="font-weight: 700; color: var(--text-main); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-server text-primary" style="margin-right: 0.5rem;"></i>
                                Verified Field Node: <span style="color: var(--primary);"><?= h($camp['ngo_name']) ?></span>
                            </div>
                            <h3><?= h($camp['title']) ?></h3>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= h($camp['description']) ?>
                            </p>
                            <div class="progress-container">
                                <div class="progress-stats">
                                    <span style="font-weight: 700;">$<?= number_format($camp['current_amount'], 2) ?> Acquired</span>
                                    <span style="font-weight: 700; color: var(--primary);"><?= $percent ?>%</span>
                                </div>
                                <div class="progress-track" style="margin-bottom: 1.25rem;">
                                    <div class="progress-fill" style="width: <?= $percent ?>%; transition: width 1.5s ease-out;"></div>
                                </div>
                                <a href="/share_hope/donate.php?campaign_id=<?= $camp['id'] ?>" class="btn btn-primary" style="width: 100%; text-align: center; font-weight: 700;">
                                    <i class="fa-solid fa-bolt" style="margin-right: 0.4rem;"></i> Accelerate Mission
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 6rem 2rem; background: var(--surface); border-radius: var(--radius-lg); border: 1px dashed var(--border);">
                    <div style="width: 80px; height: 80px; background: var(--background); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; color: var(--text-muted); border: 1px solid var(--border);">
                        <i class="fa-solid fa-radar"></i>
                    </div>
                    <h3 style="margin-bottom: 0.5rem; color: var(--text-main);">No Operations Synchronized</h3>
                    <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">The system is currently awaiting new field deployment data. Check back in a few moments.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const suggestionsBox = document.getElementById('searchSuggestions');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 2) {
            suggestionsBox.classList.remove('active');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/share_hope/api/search_suggestions.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.suggestions && data.suggestions.length > 0) {
                        let html = '';
                        data.suggestions.forEach(item => {
                            const icon = item.type === 'campaign' ? 'fa-bullseye' : 'fa-building';
                            const label = item.type === 'campaign' ? 'Campaign' : 'NGO';
                            html += `
                                <div class="suggestion-item" onclick="document.getElementById('searchInput').value = '${item.value.replace(/'/g, "\\'")}'">
                                    <div class="suggestion-icon">
                                        <i class="fa-solid ${icon}" style="color: var(--primary);"></i>
                                    </div>
                                    <div class="suggestion-content">
                                        <div class="suggestion-title">${item.display}</div>
                                        <div class="suggestion-meta">${label}</div>
                                    </div>
                                </div>
                            `;
                        });
                        suggestionsBox.innerHTML = html;
                        suggestionsBox.classList.add('active');
                    } else {
                        suggestionsBox.classList.remove('active');
                    }
                })
                .catch(err => console.error('Search error:', err));
        }, 300);
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-input-group')) {
            suggestionsBox.classList.remove('active');
        }
    });
});
</script>
