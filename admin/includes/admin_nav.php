<button class="module-menu-btn" onclick="document.getElementById('mobile-module-nav').classList.add('mobile-open')"><i class="fa-solid fa-layer-group"></i> Open System Navigation</button>
<div class="admin-sidebar" id="mobile-module-nav" style="left: 0px;">
<button class="close-module-btn" onclick="document.getElementById('mobile-module-nav').classList.remove('mobile-open')"><i class="fa-solid fa-xmark"></i></button>
    <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h3 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">System Admin</h3>
        <p style="margin: 0.5rem 0 0 0; color: #94A3B8; font-size: 0.875rem;"><?= h($_SESSION['user_name'] ?? 'Administrator') ?></p>
    </div>

    <nav>
        <!-- PRIMARY DASHBOARD -->
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line" style="width: 20px;"></i>
            <span>System Overview</span>
        </a>

        <!-- CONTENT MANAGEMENT -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Content</div>
        
        <a href="<?= BASE_URL ?>/admin/campaigns_hub.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'campaigns_hub.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bullhorn" style="width: 20px;"></i>
            <span>Fundraising Campaigns</span>
        </a>

        <!-- COMMUNICATIONS (UNIFIED) -->
        <a href="<?= BASE_URL ?>/admin/communications.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'communications.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-megaphone" style="width: 20px;"></i>
            <span>Communications Hub</span>
        </a>

        <!-- STAKEHOLDERS (UNIFIED) -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Network</div>
        
        <a href="<?= BASE_URL ?>/admin/stakeholders.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'stakeholders.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users" style="width: 20px;"></i>
            <span>Stakeholders</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/staff.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users-gear" style="width: 20px;"></i>
            <span>Staff Manager</span>
        </a>

        <!-- FINANCIAL OPERATIONS (COLLAPSIBLE) -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Finance</div>
        
        <button type="button" class="admin-sidebar-link" style="width: 100%; text-align: left; background: none; border: none; cursor: pointer; padding: 0.75rem 1.25rem; display: flex; align-items: center; gap: 0.75rem; color: #E2E8F0; transition: all 0.3s ease;" onclick="document.getElementById('finance-menu').style.display = document.getElementById('finance-menu').style.display === 'none' ? 'flex' : 'none'; this.querySelector('i:last-child').style.transform = document.getElementById('finance-menu').style.display === 'none' ? 'rotate(0deg)' : 'rotate(180deg)';">
            <i class="fa-solid fa-vault" style="width: 20px; transition: transform 0.3s ease;"></i>
            <span>Finance Operations</span>
            <i class="fa-solid fa-chevron-down" style="margin-left: auto; font-size: 0.75rem; transition: transform 0.3s ease;"></i>
        </button>
        
        <div id="finance-menu" style="display: flex; flex-direction: column; gap: 0; background: rgba(0,0,0,0.2); border-radius: 0 0 var(--radius-md) var(--radius-md); margin: 0 0.75rem 0.5rem 0.75rem; overflow: hidden;">
            <a href="<?= BASE_URL ?>/admin/all_donations.php" class="admin-sidebar-link" style="border-radius: 0; padding: 0.6rem 1rem; font-size: 0.9rem; border-left: 2px solid var(--accent); <?= basename($_SERVER['PHP_SELF']) === 'all_donations.php' ? 'background: rgba(0,217,255,0.1);' : '' ?>">
                <i class="fa-solid fa-hand-holding-heart" style="width: 16px; font-size: 0.9rem;"></i>
                <span>Donations Ledger</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/transaction_controls.php" class="admin-sidebar-link" style="border-radius: 0; padding: 0.6rem 1rem; font-size: 0.9rem; border-left: 2px solid var(--accent); <?= basename($_SERVER['PHP_SELF']) === 'transaction_controls.php' ? 'background: rgba(0,217,255,0.1);' : '' ?>">
                <i class="fa-solid fa-credit-card" style="width: 16px; font-size: 0.9rem;"></i>
                <span>Transactions</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/finance_controls.php" class="admin-sidebar-link" style="border-radius: 0; padding: 0.6rem 1rem; font-size: 0.9rem; border-left: 2px solid var(--accent); <?= basename($_SERVER['PHP_SELF']) === 'finance_controls.php' ? 'background: rgba(0,217,255,0.1);' : '' ?>">
                <i class="fa-solid fa-sliders" style="width: 16px; font-size: 0.9rem;"></i>
                <span>Finance Settings</span>
            </a>
        </div>

        <!-- ANALYTICS (UNIFIED) -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Analytics</div>
        
        <a href="<?= BASE_URL ?>/admin/analytics.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie" style="width: 20px;"></i>
            <span>Analytics Dashboard</span>
        </a>
        
        <a href="<?= BASE_URL ?>/admin/campaign_updates_review.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'campaign_updates_review.php' ? 'active' : '' ?>" style="position:relative;">
            <i class="fa-solid fa-timeline" style="width: 20px;"></i>
            <span>Update Reviews</span>
            <?php
            try {
                $badge = $pdo->query("SELECT COUNT(*) FROM campaign_updates WHERE status='pending'")->fetchColumn();
                if ($badge > 0) echo '<span style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:var(--danger);color:white;border-radius:999px;padding:0.05rem 0.45rem;font-size:0.68rem;font-weight:800;">' . $badge . '</span>';
            } catch(Exception $e) {}
            ?>
        </a>


        <!-- SYSTEM MONITORING -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">System</div>
        
        <a href="<?= BASE_URL ?>/admin/activity_logs.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'activity_logs.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-secret" style="width: 20px;"></i>
            <span>Activity Logs</span>
        </a>
    </nav>

    <div style="margin-top: auto; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);">
        <a href="<?= BASE_URL ?>/actions/logout_action.php" class="admin-sidebar-link" style="color: #ef4444;">
            <i class="fa-solid fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
