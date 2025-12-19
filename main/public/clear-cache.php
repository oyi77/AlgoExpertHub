<?php
// Clear browser cache helper
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cache Cleared</title>
</head>
<body>
    <h1>Cache Instructions</h1>
    <p><strong>Press Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)</strong></p>
    <p>JavaScript file checksum: <?php echo md5_file(__DIR__ . '/asset/frontend/trading-v1/js/trading-terminal.js'); ?></p>
    <p>Timestamp: <?php echo date('Y-m-d H:i:s'); ?></p>
    <script>
        console.log('Cache helper loaded at:', new Date().toLocaleTimeString());
        console.log('Please hard refresh the trading terminal page');
    </script>
</body>
</html>
