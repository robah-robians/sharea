<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$where_conditions = ["c.status = 'active'"];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "c.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(c.title LIKE ? OR c.description LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$order_by = match($sort) {
    'oldest' => 'c.created_at ASC',
    'goal_high' => 'c.goal_amount DESC',
    'goal_low' => 'c.goal_amount ASC',
    'progress' => 'progress_percent DESC',
    default => 'c.created_at DESC'
};

// Get campaigns
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, u.name as ngo_name,
           COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as raised_amount,
           COUNT(CASE WHEN d.status = 'completed' THEN d.id END) as donor_count,
           ROUND((COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) / c.goal_amount) * 100, 1) as progress_percent
    FROM campaigns c
    JOIN ngos n ON c.ngo_id = n.id
    JOIN users u ON n.user_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN donations d ON c.id = d.campaign_id
    WHERE $where_clause
    GROUP BY c.id
    ORDER BY $order_by
");
$stmt->execute($params);
$campaigns = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php include __DIR__ . '/includes/donor_nav.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1><i class="fa-solid fa-search"></i> Find Campaigns</h1>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <span style="color: var(--text-muted);"><?= count($campaigns) ?> campaigns found</span>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section" style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-bottom: 2rem;">
            <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?= h($search) ?>" placeholder="Search campaigns, NGOs...">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= h($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Sort By</label>
                    <select name="sort" class="form-control">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="goal_high" <?= $sort === 'goal_high' ? 'selected' : '' ?>>Highest Goal</option>
                        <option value="goal_low" <?= $sort === 'goal_low' ? 'selected' : '' ?>>Lowest Goal</option>
                        <option value="progress" <?= $sort === 'progress' ? 'selected' : '' ?>>Most Progress</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </form>
        </div>

        <!-- Campaigns Grid -->
        <div class="campaigns-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
            <?php foreach($campaigns as $campaign): ?>
                <div class="campaign-card" style="background: var(--surface); border-radius: var(--radius-lg); padding: 1.5rem; border: 1px solid var(--border); box-shadow: var(--shadow-sm); transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--shadow-sm)'">
                    <?php if($campaign['image_url']): ?>
                        <img src="<?= h($campaign['image_url']) ?>" alt="Campaign Image" style="width: 100%; height: 200px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: 1rem;">
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 1rem;">
                        <span class="badge" style="background: var(--primary); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.75rem;">
                            <?= h($campaign['category_name']) ?>
                        </span>
                        <span style="color: var(--text-muted); font-size: 0.875rem; margin-left: 0.5rem;">
                            by <?= h($campaign['ngo_name']) ?>
                        </span>
                    </div>
                    
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem;"><?= h($campaign['title']) ?></h3>
                    <p style="color: var(--text-muted); margin-bottom: 1rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= h(substr($campaign['description'], 0, 150)) ?>...
                    </p>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-weight: 600; color: var(--success);">$<?= number_format($campaign['raised_amount'], 2) ?></span>
                            <span style="color: var(--text-muted);">of $<?= number_format($campaign['goal_amount'], 2) ?></span>
                        </div>
                        <div style="background: var(--border); height: 8px; border-radius: var(--radius-sm); overflow: hidden;">
                            <div style="background: var(--success); height: 100%; width: <?= min(100, $campaign['progress_percent']) ?>%; transition: width 0.3s ease;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-muted);">
                            <span><?= $campaign['progress_percent'] ?>% funded</span>
                            <span><?= $campaign['donor_count'] ?> donors</span>
                        </div>
                        <div style="text-align: center; margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-muted);">
                            Deadline: <?= date('M j, Y', strtotime($campaign['deadline'])) ?>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="<?= BASE_URL ?>/campaigns.php?id=<?= $campaign['id'] ?>" class="btn btn-outline" style="flex: 1; text-align: center;">
                            <i class="fa-solid fa-eye"></i> View Details
                        </a>
                        <a href="<?= BASE_URL ?>/donate.php?campaign_id=<?= $campaign['id'] ?>" class="btn btn-primary" style="flex: 1; text-align: center;">
                            <i class="fa-solid fa-heart"></i> Donate Now
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($campaigns)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-muted);">
                    <i class="fa-solid fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No Campaigns Found</h3>
                    <p>Try adjusting your search criteria or browse all campaigns.</p>
                    <a href="<?= BASE_URL ?>/donor/find_campaigns.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fa-solid fa-refresh"></i> Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>