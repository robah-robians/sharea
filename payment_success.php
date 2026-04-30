<?php
session_start();

// Check if there's a success message BEFORE any output
if (!isset($_SESSION['success']) || !isset($_SESSION['donation_id'])) {
    header('Location: /share_hope/');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$donation_id = $_SESSION['donation_id'];
$success_message = $_SESSION['success'];

// Fetch donation details
try {
    $stmt = $pdo->prepare("SELECT d.*, c.title as campaign_title, c.id as campaign_id FROM donations d LEFT JOIN campaigns c ON d.campaign_id = c.id WHERE d.id = ?");
    $stmt->execute([$donation_id]);
    $donation = $stmt->fetch();
    
    // If not found in regular campaigns, check awareness campaigns
    if (!$donation) {
        $stmt = $pdo->prepare("SELECT d.*, ac.title as campaign_title, ac.id as campaign_id FROM donations d LEFT JOIN awareness_campaigns ac ON d.campaign_id = ac.id WHERE d.id = ?");
        $stmt->execute([$donation_id]);
        $donation = $stmt->fetch();
    }
    
    // Ensure transaction_ref is available
    if ($donation && !isset($donation['transaction_ref'])) {
        $donation['transaction_ref'] = $donation['transaction_id'] ?? 'N/A';
    }
} catch (Exception $e) {
    $donation = null;
}

// Clear session messages
unset($_SESSION['success'], $_SESSION['donation_id']);
?>

<div class="hero" style="padding: 4rem 0; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; text-align: center; position: relative; overflow: hidden;">
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.1;"></div>
    <div class="container" style="position: relative; z-index: 1;">
        <div style="font-size: 5rem; margin-bottom: 1.5rem; animation: bounce 2s ease-in-out;">
            <i class="fa-solid fa-circle-check" style="color: #ffffff; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));"></i>
        </div>
        <h1 style="font-size: 3rem; margin-bottom: 1rem; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Payment Successful!</h1>
        <p style="font-size: 1.3rem; opacity: 0.95; margin-bottom: 2rem;">🎉 Thank you for your generous donation! 🎉</p>
        <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 50px; display: inline-block; backdrop-filter: blur(10px);">
            <i class="fa-solid fa-heart" style="color: #ff6b6b; margin-right: 0.5rem;"></i>
            <span style="font-weight: 600;">Your kindness makes a difference</span>
        </div>
    </div>
</div>

<style>
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-20px); }
    60% { transform: translateY(-10px); }
}
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(180deg); }
}
</style>

<div class="container" style="padding: 3rem 1.5rem; max-width: 700px;">
    <div style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border); margin-bottom: 2rem;">
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--secondary); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; text-align: center; border: 2px solid rgba(16, 185, 129, 0.2);">
            <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-check-circle" style="margin-right: 0.5rem; color: #10b981;"></i>
                Transaction Completed Successfully
            </div>
            <div style="font-family: monospace; font-size: 1rem; background: rgba(16, 185, 129, 0.1); padding: 0.5rem 1rem; border-radius: 6px; display: inline-block; margin-top: 0.5rem;">
                <?= h($success_message) ?>
            </div>
        </div>

        <?php if ($donation): ?>
        <h3 style="margin: 0 0 1.5rem; color: var(--text-main);">Donation Details</h3>
        
        <div style="display: grid; gap: 1rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-muted);">Campaign:</span>
                <span style="font-weight: 600;"><?= h($donation['campaign_title']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-muted);">Amount:</span>
                <span style="font-weight: 600; color: var(--secondary); font-size: 1.1rem;">$<?= number_format($donation['amount'], 2) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-muted);">Payment Method:</span>
                <span style="font-weight: 600;"><?= $donation['payment_method'] === 'mpesa' ? 'M-Pesa' : 'Credit/Debit Card' ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-muted);">Transaction Reference:</span>
                <span style="font-weight: 600; font-family: monospace; background: var(--background); padding: 0.25rem 0.5rem; border-radius: 4px; color: var(--secondary);"><?= h($donation['transaction_ref'] ?? $donation['transaction_id'] ?? 'N/A') ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                <span style="color: var(--text-muted);">Date & Time:</span>
                <span style="font-weight: 600;"><?= date('M j, Y, g:i A', strtotime($donation['created_at'])) ?></span>
            </div>
            <?php if ($donation['message']): ?>
            <div style="padding: 0.75rem 0;">
                <span style="color: var(--text-muted);">Your Message:</span>
                <p style="margin: 0.5rem 0 0; font-style: italic; background: var(--background); padding: 1rem; border-radius: var(--radius-sm);">"<?= h($donation['message']) ?>"</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="background: var(--background); padding: 2rem; border-radius: var(--radius-md); margin-bottom: 2rem; border-left: 4px solid var(--secondary);">
            <h4 style="margin: 0 0 1rem; color: var(--text-main); display: flex; align-items: center;">
                <i class="fa-solid fa-route" style="margin-right: 0.5rem; color: var(--secondary);"></i>
                What happens next?
            </h4>
            <div style="display: grid; gap: 1rem;">
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--secondary); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600; flex-shrink: 0;">1</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Email Confirmation</div>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">You'll receive a detailed receipt in your email within 5 minutes</div>
                    </div>
                </div>
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--secondary); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600; flex-shrink: 0;">2</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Fund Transfer</div>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">Your donation will be processed and sent to the NGO within 24-48 hours</div>
                    </div>
                </div>
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--secondary); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600; flex-shrink: 0;">3</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Impact Updates</div>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">Track the campaign's progress and see how your donation makes a difference</div>
                    </div>
                </div>
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--secondary); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600; flex-shrink: 0;">4</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">Tax Receipt</div>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">Download your PDF receipt below for tax deduction purposes</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
            <a href="/share_hope/donation_receipt.php?id=<?= $donation_id ?>" 
               class="btn btn-outline" style="text-align: center; padding: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.3s ease;">
                <i class="fa-solid fa-download"></i>
                <span>Download Receipt</span>
            </a>
            <a href="/share_hope/campaigns.php" 
               class="btn btn-primary" style="text-align: center; padding: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: var(--secondary); border-color: var(--secondary); transition: all 0.3s ease;">
                <i class="fa-solid fa-heart"></i>
                <span>Support More Causes</span>
            </a>
        </div>
    </div>

    <!-- Social Sharing -->
    <div style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border); text-align: center; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(16, 185, 129, 0.05) 0%, transparent 70%); pointer-events: none;"></div>
        <div style="position: relative; z-index: 1;">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🌟</div>
            <h4 style="margin: 0 0 1rem; color: var(--text-main);">Spread the Love!</h4>
            <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1.1rem;">Your donation is making a real difference. Inspire others to join the cause!</p>
            
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="https://twitter.com/intent/tweet?text=I%20just%20donated%20to%20an%20amazing%20cause%20on%20Share%20Hope!%20%F0%9F%92%9A%20Join%20me%20in%20making%20a%20difference%20in%20someone's%20life.&url=<?= urlencode('https://sharehope.org') ?>" 
                   target="_blank" class="btn btn-outline" style="padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">
                    <i class="fa-brands fa-x-twitter" style="color: #000000;"></i>
                    <span>Share on X</span>
                </a>
                <a href="https://wa.me/?text=I%20just%20made%20a%20donation%20through%20Share%20Hope!%20%F0%9F%8C%9F%20This%20platform%20connects%20donors%20with%20verified%20NGOs%20making%20real%20impact.%20Check%20it%20out!" 
                   target="_blank" class="btn btn-outline" style="padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease;">
                    <i class="fa-brands fa-whatsapp" style="color: #25d366;"></i>
                    <span>Share on WhatsApp</span>
                </a>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="/share_hope/" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
            <i class="fa-solid fa-home" style="margin-right: 0.5rem;"></i>Back to Home
        </a>
    </div>
</div>

<script>
// Confetti animation for successful payment
document.addEventListener('DOMContentLoaded', function() {
    // Simple confetti effect
    function createConfetti() {
        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.style.position = 'fixed';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.top = '-10px';
            confetti.style.width = '10px';
            confetti.style.height = '10px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.pointerEvents = 'none';
            confetti.style.zIndex = '9999';
            confetti.style.borderRadius = '50%';
            
            document.body.appendChild(confetti);
            
            const animation = confetti.animate([
                { transform: 'translateY(-10px) rotate(0deg)', opacity: 1 },
                { transform: `translateY(100vh) rotate(${Math.random() * 360}deg)`, opacity: 0 }
            ], {
                duration: Math.random() * 2000 + 1000,
                easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
            });
            
            animation.onfinish = () => confetti.remove();
        }
    }
    
    // Trigger confetti
    createConfetti();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>