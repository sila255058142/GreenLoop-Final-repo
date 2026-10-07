<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

// ดาวน์โหลดได้เฉพาะผู้ซื้อที่คำสั่งซื้อสำเร็จแล้ว (หรือผู้ดูแลระบบ)
$o = row("SELECT o.*, p.file_path, p.file_name FROM orders o JOIN products p ON p.id = o.product_id WHERE o.id = ? AND o.type = 'digital' AND o.status = 'completed'", [(int) get('order')]);
if (!$o || ($o['buyer_id'] != $u['id'] && $u['role'] !== 'admin')) {
    flash('ไม่พบไฟล์ หรือคุณไม่มีสิทธิ์ดาวน์โหลด', 'danger');
    redirect('orders.php');
}

$path = $o['file_path'] ? ROOT_PATH . '/' . $o['file_path'] : '';
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/octet-stream');
if ($path && is_file($path)) {
    $name = $o['file_name'] ?: basename($path);
    header("Content-Disposition: attachment; filename=\"download." . pathinfo($path, PATHINFO_EXTENSION) . "\"; filename*=UTF-8''" . rawurlencode($name));
    header('Content-Length: ' . filesize($path));
    readfile($path);
} else {
    // สินค้าตัวอย่างที่มากับระบบไม่มีไฟล์จริง
    header('Content-Disposition: attachment; filename="greenloop-demo.txt"');
    echo "GreenLoop — ไฟล์ตัวอย่าง\r\nสินค้า: {$o['product_title']}\r\nคำสั่งซื้อ: #{$o['order_no']}\r\n";
}
