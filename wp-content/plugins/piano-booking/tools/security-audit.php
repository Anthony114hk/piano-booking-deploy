<?php
/**
 * WP-CLI security audit command.
 */
if (!defined('WP_CLI') || !WP_CLI) return;

\WP_CLI::add_command('pb security-audit', function () {
    $issues = [];
    if (!defined('DISALLOW_FILE_EDIT') || !DISALLOW_FILE_EDIT) $issues[] = 'DISALLOW_FILE_EDIT not set';
    if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) $issues[] = 'WP_DEBUG_DISPLAY is on';
    $perms = fileperms(ABSPATH . 'wp-config.php');
    if (($perms & 0x0044) !== 0) $issues[] = 'wp-config.php is world-readable';
    $adminUser = get_user_by('login', 'admin');
    if ($adminUser && strpos($adminUser->user_email, 'example.com') !== false) {
        $issues[] = 'Default admin user has example.com email';
    }
    $htaccess = ABSPATH . 'wp-content/uploads/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "<Files *.php>\ndeny from all\n</Files>\n");
        $issues[] = 'Created uploads .htaccess (PHP execution blocked)';
    }
    if ($issues) foreach ($issues as $i) \WP_CLI::warning($i);
    else \WP_CLI::success('Security audit clean.');
});