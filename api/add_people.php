<?php
require __DIR__ . '/../src/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function render_flash(): void {
    if (!empty($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $msg  = $_SESSION['message'];
        echo '<div class="alert alert-' . htmlspecialchars($type) . '">' . htmlspecialchars($msg) . '</div>';
        unset($_SESSION['message'], $_SESSION['message_type']);
    }
}

$rootHeader = dirname(__DIR__) . '/header.php';
$localHeader = __DIR__ . '/header.php';
if (file_exists($rootHeader)) {
    include $rootHeader;
} elseif (file_exists($localHeader)) {
    include $localHeader;
} else {
    trigger_error('header.php not found', E_USER_WARNING);
}
?>

<?php render_flash(); ?>

<!-- rest of the existing HTML and PHP content -->

<?php
$rootFooter = dirname(__DIR__) . '/footer.php';
$localFooter = __DIR__ . '/footer.php';
if (file_exists($rootFooter)) {
    include $rootFooter;
} elseif (file_exists($localFooter)) {
    include $localFooter;
} else {
    trigger_error('footer.php not found', E_USER_WARNING);
}
?>
