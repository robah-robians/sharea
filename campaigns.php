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

$query = "SELECT c.*, n.user_id, u.name as ngo_name, cat.name as category_name
          FROM campaigns c 
          JOIN ngos n ON c.ngo_id = n.id 
          JOIN users u ON n.user_id = u.id
          LEFT JOIN categories cat ON c.category_id = cat.id
          WHERE $where_sql
          ORDER BY $order_sql";

$campaigns = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();
} catch (\PDOException $e) {
}

// Fetch categories for filtering
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>

<section class="hero" style="padding: 4rem 0;">
    <div class="container">
        <h1>Discover Campaigns</h1>
        <p>Browse through hundreds of verified campaigns matching your interests.</p>

        <form method="GET" action="campaigns.php"
            style="max-width: 800px; margin: 2rem auto 0; background: var(--surface); padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" placeholder="Search campaigns..." value="<?= h($search) ?>"
                    class="form-control" style="margin-bottom: 0;" autocomplete="off">
            </div>

            <div style="min-width: 150px;">
                <select name="category" class="form-control" style="margin-bottom: 0;">
                    <option value="all" <?= $category_id === 'all' ? 'selected' : '' ?>>All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (string) $category_id === (string) $cat['id'] ? 'selected' : '' ?>>
                            <?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="min-width: 150px;">
                <select name="sort" class="form-control" style="margin-bottom: 0;">
                    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                    <option value="funded" <?= $sort === 'funded' ? 'selected' : '' ?>>Most Funded</option>
                    <option value="closing" <?= $sort === 'closing' ? 'selected' : '' ?>>Closing Soon</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;"><i
                    class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid">
            <?php if (count($campaigns) > 0): ?>
                <?php foreach ($campaigns as $camp): ?>
                    <?php
                    $percent = ($camp['goal_amount'] > 0) ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0;
                    ?>
                    <div class="campaign-card">
                        <div class="campaign-img">
                            <div class="campaign-badge"><?= h($camp['category_name'] ?? 'General') ?></div>
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
                <div style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
                    <h3>No campaigns available right now</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>