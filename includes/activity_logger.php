<?php
/**
 * Activity Logger - Logs all admin actions for compliance & auditing
 */

function log_admin_activity($pdo, $admin_id, $action, $action_type, $entity_type = null, $entity_id = null, $entity_name = null, $description = null, $old_value = null, $new_value = null) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs 
            (admin_id, action, action_type, entity_type, entity_id, entity_name, description, old_value, new_value, ip_address, user_agent) 
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $admin_id,
            $action,
            $action_type,
            $entity_type,
            $entity_id,
            $entity_name,
            $description,
            $old_value,
            $new_value,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get NGO Health Score based on multiple metrics
 */
function calculate_ngo_health_score($pdo, $ngo_id) {
    $score = 0;
    $max_score = 100;
    
    // Metric 1: Campaign Success Rate (25 points)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN c.current_amount >= c.goal_amount THEN 1 ELSE 0 END) as successful
        FROM campaigns c WHERE c.ngo_id = ?
    ");
    $stmt->execute([$ngo_id]);
    $campaign_stats = $stmt->fetch();
    if ($campaign_stats['total'] > 0) {
        $success_rate = ($campaign_stats['successful'] / $campaign_stats['total']) * 100;
        $score += min(25, ($success_rate / 100) * 25);
    } else {
        $score += 0; // New NGO with no campaigns
    }
    
    // Metric 2: Impact Updates Frequency (15 points)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as update_count
        FROM campaign_updates cu
        JOIN campaigns c ON cu.campaign_id = c.id
        WHERE c.ngo_id = ? AND cu.created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $stmt->execute([$ngo_id]);
    $updates = $stmt->fetch()['update_count'] ?? 0;
    $active_campaigns = $campaign_stats['total'] ?? 0;
    
    if ($active_campaigns > 0 && $updates > 0) {
        $updates_per_campaign = $updates / $active_campaigns;
        $score += min(15, ($updates_per_campaign / 3) * 15); // Ideal: 3 updates per campaign per quarter
    }
    
    // Metric 3: Donor Satisfaction (Average donation completion rate) (20 points)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_donations,
            SUM(CASE WHEN d.status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM donations d
        JOIN campaigns c ON d.campaign_id = c.id
        WHERE c.ngo_id = ?
    ");
    $stmt->execute([$ngo_id]);
    $donation_stats = $stmt->fetch();
    if ($donation_stats['total_donations'] > 0) {
        $completion_rate = ($donation_stats['completed'] / $donation_stats['total_donations']) * 100;
        $score += ($completion_rate / 100) * 20;
    }
    
    // Metric 4: Total Funds Raised (20 points)
    $stmt = $pdo->prepare("
        SELECT SUM(d.amount) as total FROM donations d
        JOIN campaigns c ON d.campaign_id = c.id
        WHERE c.ngo_id = ? AND d.status = 'completed'
    ");
    $stmt->execute([$ngo_id]);
    $total_raised = $stmt->fetch()['total'] ?? 0;
    // Score based on raised amount (5000+ = full 20 points)
    $score += min(20, ($total_raised / 5000) * 20);
    
    // Metric 5: Account Age & Reliability (10 points)
    $stmt = $pdo->prepare("SELECT n.created_at FROM ngos n WHERE n.id = ?");
    $stmt->execute([$ngo_id]);
    $ngo = $stmt->fetch();
    if ($ngo) {
        $days_active = (time() - strtotime($ngo['created_at'])) / 86400;
        $score += min(10, ($days_active / 365) * 10); // Full 10 points after 1 year
    }
    
    // Metric 6: Campaign Response Time (10 points) - Updates posted within 2 weeks
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_campaigns,
            SUM(CASE WHEN DATEDIFF(
                (SELECT MIN(cu2.created_at) FROM campaign_updates cu2 WHERE cu2.campaign_id = c.id),
                c.created_at
            ) <= 14 THEN 1 ELSE 0 END) as timely_responses
        FROM campaigns c WHERE c.ngo_id = ?
    ");
    $stmt->execute([$ngo_id]);
    $response_stats = $stmt->fetch();
    if ($response_stats['total_campaigns'] > 0) {
        $timely_rate = ($response_stats['timely_responses'] / $response_stats['total_campaigns']) * 100;
        $score += ($timely_rate / 100) * 10;
    }
    
    return min($max_score, round($score, 1));
}

/**
 * Get health score color badge
 */
function get_health_score_badge($score) {
    if ($score >= 85) {
        return ['color' => 'var(--secondary)', 'label' => 'Excellent', 'bg' => 'rgba(16, 185, 129, 0.1)'];
    } elseif ($score >= 70) {
        return ['color' => 'var(--primary)', 'label' => 'Good', 'bg' => 'rgba(79, 70, 229, 0.1)'];
    } elseif ($score >= 50) {
        return ['color' => 'var(--accent)', 'label' => 'Fair', 'bg' => 'rgba(245, 158, 11, 0.1)'];
    } else {
        return ['color' => 'var(--danger)', 'label' => 'Needs Improvement', 'bg' => 'rgba(239, 68, 68, 0.1)'];
    }
}

/**
 * Get count of unread public announcements
 */
function get_unread_announcements_count($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM announcements WHERE is_public = 1 AND COALESCE(is_hidden,0) = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
        return $stmt->fetch()['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Failed to get announcements count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get all active social media links
 */
function get_social_media_links($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM social_media_links 
            WHERE is_active = 1 
            ORDER BY sort_order ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll() ?? [];
    } catch (Exception $e) {
        error_log("Failed to get social media links: " . $e->getMessage());
        return [];
    }
}

