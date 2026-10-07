<?php
require __DIR__ . '/includes/init.php';

if (is_post()) {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('ออกจากระบบแล้ว', 'info');
}
redirect('index.php');
