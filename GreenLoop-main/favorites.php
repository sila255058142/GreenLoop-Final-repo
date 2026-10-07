<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

$list = rows(PRODUCT_SELECT . ' JOIN favorites f ON f.product_id = p.id AND f.user_id = ? ORDER BY f.created_at DESC', [$u['id']]);
$title = 'รายการโปรด';
require __DIR__ . '/includes/header.php';
echo page_header('รายการโปรด (Favorite)');
?>
<?php if ($list): ?>
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
  <?php foreach ($list as $p): ?><div class="col"><?= product_card($p) ?></div><?php endforeach; ?>
</div>
<?php else: ?>
<div class="panel empty"><i class="bi bi-heart"></i>ยังไม่มีสินค้าในรายการโปรด<br><a class="btn btn-primary mt-3" href="<?= e(url('products.php')) ?>">เลือกดูสินค้า</a></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
