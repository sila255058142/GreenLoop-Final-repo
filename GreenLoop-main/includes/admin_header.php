<?php
$self = basename($_SERVER['SCRIPT_NAME']);
$pending = [
    'products.php' => (int) val("SELECT COUNT(*) FROM products WHERE status = 'pending'"),
    'topups.php' => (int) val("SELECT COUNT(*) FROM topups WHERE status = 'pending'"),
    'recycles.php' => (int) val("SELECT COUNT(*) FROM recycles WHERE status IN ('pending', 'accepted', 'received')"),
    'orders.php' => (int) val("SELECT COUNT(*) FROM orders WHERE status IN ('paid', 'shipped')"),
    'rewards.php' => (int) val("SELECT COUNT(*) FROM redemptions WHERE status = 'pending'"),
    'chat.php' => (int) val('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0', [user()['id']]),
];
$menu = [
    'index.php' => ['bi-speedometer2', 'Dashboard'],
    'users.php' => ['bi-people', 'จัดการสมาชิก'],
    'products.php' => ['bi-box-seam', 'จัดการสินค้า'],
    'categories.php' => ['bi-grid', 'หมวดหมู่สินค้า'],
    'orders.php' => ['bi-cart-check', 'จัดการคำสั่งซื้อ'],
    'topups.php' => ['bi-cash-coin', 'จัดการเติมเงิน'],
    'recycles.php' => ['bi-recycle', 'จัดการรีไซเคิล'],
    'rewards.php' => ['bi-gift', 'ของรางวัล Green Point'],
    'banners.php' => ['bi-image', 'จัดการแบนเนอร์'],
    'chat.php' => ['bi-chat-dots', 'จัดการแชท'],
    'settings.php' => ['bi-gear', 'ตั้งค่าเว็บไซต์'],
];
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e((isset($title) ? $title : 'Admin') . ' | ' . setting('site_name', 'GreenLoop') . ' Admin') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>?v=1" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-side">
    <a class="brand d-flex align-items-center gap-2 mb-3 px-2" href="<?= e(url('admin/index.php')) ?>"><?= logo_html(30) ?></a>
    <?php foreach ($menu as $file => $m): ?>
      <a class="item <?= $self === $file ? 'active' : '' ?>" href="<?= e(url('admin/' . $file)) ?>"><i class="bi <?= $m[0] ?>"></i> <?= $m[1] ?>
        <?php if (!empty($pending[$file])): ?><span class="badge rounded-pill text-bg-warning"><?= $pending[$file] ?></span><?php endif; ?></a>
    <?php endforeach; ?>
    <hr class="opacity-25">
    <a class="item" href="<?= e(url('index.php')) ?>"><i class="bi bi-house"></i> กลับหน้าเว็บไซต์</a>
    <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="item btn w-100 text-start border-0" style="color:#d9eedf"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</button></form>
  </aside>
  <div class="admin-main">
    <div class="section-head">
      <h1 class="pill-title"><?= e(isset($title) ? $title : 'Admin') ?></h1>
      <div class="small text-muted"><i class="bi bi-person-circle"></i> <?= e(user()['name']) ?></div>
    </div>
    <?php if (!empty($_SESSION['flash'])): foreach ($_SESSION['flash'] as $f): ?>
      <div class="alert alert-<?= e($f[0]) ?> alert-dismissible fade show" role="alert"><?= e($f[1]) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button></div>
    <?php endforeach; unset($_SESSION['flash']); endif; ?>
