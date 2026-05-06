<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['pending_donation'])) {
    header("Location: /share_hope/campaigns.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$phone = $_SESSION['pending_donation']['mpesa_phone'] ?? '07XXXXXXXX';
$amount = number_format($_SESSION['pending_donation']['amount'], 2);
?>
<div class="container" style="padding-top: 4rem; padding-bottom: 6rem; text-align: center;">
    <div
        style="max-width: 500px; margin: 0 auto; background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border-top: 6px solid #4ade80;">
        <i class="fa-solid fa-mobile-screen" style="font-size: 4rem; color: #4ade80; margin-bottom: 1.5rem;"></i>
        <h2 style="margin-bottom: 1rem;">M-Pesa STK Push Sent</h2>
        <p style="color: var(--text-muted); font-size: 1.125rem; margin-bottom: 2rem;">
            Please check your phone <strong>
                <?= h($phone) ?>
            </strong>.<br>
            Enter your M-Pesa PIN to complete the donation of <strong>KSh
                <?= $amount ?>
            </strong> to Share Hope.
        </p>

        <div style="display: flex; justify-content: center; margin-bottom: 2rem;">
            <div class="spinner"
                style="width: 50px; height: 50px; border: 5px solid var(--border); border-top-color: #4ade80; border-radius: 50%; animation: spin 1s linear infinite;">
            </div>
            <style>
                @keyframes spin {
                    0% {
                        transform: rotate(0deg);
                    }

                    100% {
                        transform: rotate(360deg);
                    }
                }
            </style>
        </div>

        <p style="font-size: 0.875rem; color: var(--text-muted);">Simulating Daraja API Sandbox response in <span
                id="countdown">5</span> seconds...</p>
    </div>
</div>

<script>
    let timeLeft = 5;
    const countdownEl = document.getElementById('countdown');
    const timer = setInterval(() => {
        timeLeft--;
        countdownEl.innerText = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(timer);
            window.location.href = '/share_hope/actions/process_mpesa.php';
        }
    }, 1000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>