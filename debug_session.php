<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug - Share Hope</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        .debug-box { background: #f5f5f5; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .clear-btn { background: #dc3545; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
        .clear-btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <div class="debug-box">
        <h3>Current Session Data:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="debug-box">
        <h3>Session Status:</h3>
        <p><strong>Session ID:</strong> <?= session_id() ?></p>
        <p><strong>Session Status:</strong> <?= session_status() ?></p>
        <p><strong>Session Name:</strong> <?= session_name() ?></p>
    </div>
    
    <div class="debug-box">
        <h3>Cookies:</h3>
        <pre><?php print_r($_COOKIE); ?></pre>
    </div>
    
    <div class="debug-box">
        <h3>Server Info:</h3>
        <p><strong>Request URI:</strong> <?= $_SERVER['REQUEST_URI'] ?? 'N/A' ?></p>
        <p><strong>HTTP Host:</strong> <?= $_SERVER['HTTP_HOST'] ?? 'N/A' ?></p>
        <p><strong>User Agent:</strong> <?= $_SERVER['HTTP_USER_AGENT'] ?? 'N/A' ?></p>
    </div>
    
    <div style="margin-top: 2rem;">
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="clear_session">
            <button type="submit" class="clear-btn">Clear All Session Data</button>
        </form>
        
        <a href="/share_hope/" style="margin-left: 1rem; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Go to Home Page</a>
    </div>
    
    <?php
    if ($_POST['action'] ?? '' === 'clear_session') {
        session_unset();
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        
        echo '<div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                Session cleared successfully! <a href="/share_hope/">Go to Home Page</a>
              </div>';
    }
    ?>
</body>
</html>