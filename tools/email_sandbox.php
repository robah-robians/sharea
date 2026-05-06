<?php
require_once __DIR__ . '/includes/header.php';

$logFile = __DIR__ . '/logs/emails.json';
$emails = [];
if (file_exists($logFile)) {
    $emails = json_decode(file_get_contents($logFile), true) ?? [];
}

// Action to clear inbox
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_inbox'])) {
    file_put_contents($logFile, json_encode([]));
    header("Location: /share_hope/email_sandbox.php");
    exit;
}
?>

<div class="container" style="padding-top: 4rem; padding-bottom: 6rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="margin: 0; font-size: 2rem;"><i class="fa-solid fa-inbox text-primary"></i> Testing Inbox Viewer
            </h1>
            <p style="color: var(--text-muted); margin-top: 0.5rem;">All outgoing platform emails are intercepted and
                stored here safely instead of attempting real SMTP delivery.</p>
        </div>
        <form method="POST">
            <button type="submit" name="clear_inbox" class="btn btn-outline"
                style="color: var(--danger); border-color: var(--danger);"><i class="fa-solid fa-trash"></i> Empty
                Inbox</button>
        </form>
    </div>

    <?php if (empty($emails)): ?>
        <div
            style="background: var(--surface); padding: 4rem; border-radius: var(--radius-lg); text-align: center; border: 2px dashed var(--border);">
            <i class="fa-regular fa-envelope" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="margin: 0;">Inbox is Empty</h3>
            <p style="color: var(--text-muted);">No emails have been captured yet.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($emails as $email): ?>
                <div
                    style="background: var(--surface); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary); overflow: hidden;">
                    <div
                        style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">
                                <strong>To:</strong>
                                <?= h($email['to']) ?>
                            </div>
                            <h3 style="margin: 0; font-size: 1.25rem;">
                                <?= h($email['subject']) ?>
                            </h3>
                        </div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                            <i class="fa-regular fa-clock"></i>
                            <?= date('M j, Y, g:i A', strtotime($email['date'])) ?>
                        </div>
                    </div>
                    <div style="padding: 1.5rem; line-height: 1.6; white-space: pre-wrap;">
                        <?= $email['body'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>