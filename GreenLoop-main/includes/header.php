<?php
$u = user();
$site = setting('site_name', 'GreenLoop');
$unread = $u ? (int) val('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0', [$u['id']]) : 0;
$self = basename($_SERVER['SCRIPT_NAME']);
$navType = $self === 'products.php' ? get('type') : '';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($title) ? $title . ' | ' . $site : $site . ' — ' . setting('tagline')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>?v=1" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top gl-nav">
  <div class="container">
    <a class="navbar-brand" href="<?= e(url('index.php')) ?>"><?= logo_html() ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="เมนู"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?= $navType === 'market' ? 'active' : '' ?>" href="<?= e(url('products.php?type=market')) ?>"><i class="bi bi-cart3"></i> Market</a></li>
        <li class="nav-item"><a class="nav-link <?= $navType === 'digital' ? 'active' : '' ?>" href="<?= e(url('products.php?type=digital')) ?>"><i class="bi bi-display"></i> Digital Market</a></li>
        <li class="nav-item"><a class="nav-link <?= $self === 'recycle.php' ? 'active' : '' ?>" href="<?= e(url('recycle.php')) ?>"><i class="bi bi-recycle"></i> Recycle</a></li>
        <li class="nav-item"><a class="nav-link <?= $self === 'points.php' ? 'active' : '' ?>" href="<?= e(url('points.php')) ?>"><i class="bi bi-award"></i> Green Point</a></li>
      </ul>
      <form class="nav-search me-lg-3 my-2 my-lg-0" action="<?= e(url('products.php')) ?>" method="get" role="search">
        <i class="bi bi-search"></i>
        <input type="search" name="q" class="form-control" placeholder="ค้นหาสินค้า..." value="<?= $self === 'products.php' ? e(get('q')) : '' ?>" aria-label="ค้นหาสินค้า">
      </form>
      <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php if ($u): ?>
        <a class="btn btn-primary btn-sm" href="<?= e(url('sell.php')) ?>"><i class="bi bi-plus-lg"></i> ลงขาย</a>
        <a class="chip" href="<?= e(url('wallet.php')) ?>" title="กระเป๋าเงิน"><i class="bi bi-wallet2"></i> <?= baht($u['wallet_balance']) ?></a>
        <a class="chip chip-point" href="<?= e(url('points.php')) ?>" title="Green Point"><i class="bi bi-recycle"></i> <?= number_format($u['green_points']) ?></a>
        <a class="icon-btn" href="<?= e(url('chat.php')) ?>" title="แชท"><i class="bi bi-chat-dots"></i><?php if ($unread): ?><span class="dot"><?= $unread ?></span><?php endif; ?></a>
        <div class="dropdown">
          <a class="d-flex align-items-center gap-1 text-decoration-none dropdown-toggle text-dark" href="#" data-bs-toggle="dropdown"><?= avatar_html($u, 34) ?></a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li class="px-3 py-2"><div class="fw-semibold"><?= e($u['name']) ?><?= premium_badge($u) ?></div><div class="small text-muted"><?= e($u['email']) ?></div></li>
            <li><hr class="dropdown-divider"></li>
            <?php if ($u['role'] === 'admin'): ?><li><a class="dropdown-item fw-semibold text-success" href="<?= e(url('admin/index.php')) ?>"><i class="bi bi-speedometer2"></i> ระบบแอดมิน</a></li><?php endif; ?>
            <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>"><i class="bi bi-person"></i> ข้อมูลส่วนตัว</a></li>
            <li><a class="dropdown-item" href="<?= e(url('my-products.php')) ?>"><i class="bi bi-tags"></i> สินค้าของฉัน</a></li>
            <li><a class="dropdown-item" href="<?= e(url('orders.php')) ?>"><i class="bi bi-receipt"></i> ประวัติการซื้อ / ขาย</a></li>
            <li><a class="dropdown-item" href="<?= e(url('wallet.php')) ?>"><i class="bi bi-wallet2"></i> กระเป๋าเงิน</a></li>
            <li><a class="dropdown-item" href="<?= e(url('points.php')) ?>"><i class="bi bi-award"></i> Green Point & Achievement</a></li>
            <li><a class="dropdown-item" href="<?= e(url('favorites.php')) ?>"><i class="bi bi-heart"></i> รายการโปรด</a></li>
            <li><a class="dropdown-item" href="<?= e(url('recycle.php')) ?>"><i class="bi bi-recycle"></i> แจ้งรีไซเคิล</a></li>
            <li><a class="dropdown-item" href="<?= e(url('premium.php')) ?>"><i class="bi bi-gem"></i> สมาชิก Premium</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</button></form></li>
          </ul>
        </div>
      <?php else: ?>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(url('login.php')) ?>">เข้าสู่ระบบ</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('register.php')) ?>">สมัครสมาชิก</a>
      <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<main class="<?= isset($mainClass) ? e($mainClass) : 'container py-4' ?>">
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="<?= isset($mainClass) ? 'container pt-3' : '' ?>">
  <?php foreach ($_SESSION['flash'] as $f): ?>
    <div class="alert alert-<?= e($f[0]) ?> alert-dismissible fade show" role="alert"><?= e($f[1]) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button></div>
  <?php endforeach; unset($_SESSION['flash']); ?>
  </div>
<?php endif; ?>
