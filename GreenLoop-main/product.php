<?php
require __DIR__ . '/includes/init.php';

$id = (int) get('id');
$p = row(PRODUCT_SELECT . ' WHERE p.id = ?', [$id]);
$u = user();
$isOwner = $u && $p && $u['id'] == $p['user_id'];
if (!$p || ($p['status'] !== 'approved' && $p['status'] !== 'sold' && !$isOwner && !($u && $u['role'] === 'admin'))) {
    http_response_code(404);
    $title = 'ไม่พบสินค้า';
    require __DIR__ . '/includes/header.php';
    echo '<div class="panel empty"><i class="bi bi-emoji-frown"></i>ไม่พบสินค้าที่คุณต้องการ<br><a class="btn btn-primary mt-3" href="' . e(url('products.php')) . '">กลับไปเลือกสินค้า</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

if (is_post() && post('action') === 'favorite') {
    require_login();
    if (val('SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?', [$u['id'], $id])) {
        q('DELETE FROM favorites WHERE user_id = ? AND product_id = ?', [$u['id'], $id]);
        flash('นำออกจากรายการโปรดแล้ว', 'info');
    } else {
        q('INSERT INTO favorites (user_id, product_id) VALUES (?,?)', [$u['id'], $id]);
        flash('เพิ่มในรายการโปรดแล้ว');
    }
    redirect('product.php?id=' . $id);
}

if (!$isOwner) {
    q('UPDATE products SET views = views + 1 WHERE id = ?', [$id]);
}
$fav = $u && val('SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?', [$u['id'], $id]);
$owned = $u && $p['type'] === 'digital' ? val("SELECT id FROM orders WHERE buyer_id = ? AND product_id = ? AND status = 'completed' LIMIT 1", [$u['id'], $id]) : false;
$seller = row('SELECT * FROM users WHERE id = ?', [$p['user_id']]);
$sellerSales = (int) val("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'completed'", [$seller['id']]);
$related = rows(PRODUCT_SELECT . " WHERE p.status = 'approved' AND (p.type = 'digital' OR p.stock > 0) AND p.id <> ? AND p.type = ? ORDER BY (p.category_id <=> ?) DESC, p.created_at DESC LIMIT 4", [$id, $p['type'], $p['category_id']]);
$available = $p['status'] === 'approved' && ($p['type'] === 'digital' || $p['stock'] > 0);
$side = banners('side');

$title = $p['title'];
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small">
  <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">หน้าแรก</a></li>
  <li class="breadcrumb-item"><a href="<?= e(url('products.php?type=' . $p['type'])) ?>"><?= $p['type'] === 'digital' ? 'Digital Market' : 'Market' ?></a></li>
  <?php if ($p['cat_name']): ?><li class="breadcrumb-item"><a href="<?= e(url('products.php?type=' . $p['type'] . '&cat=' . $p['category_id'])) ?>"><?= e($p['cat_name']) ?></a></li><?php endif; ?>
  <li class="breadcrumb-item active"><?= e($p['title']) ?></li>
</ol></nav>

<?php if ($p['status'] !== 'approved'): ?><div class="alert alert-warning">สถานะสินค้า: <?= badge($p['status']) ?> — <?= $p['status'] === 'sold' ? 'สินค้านี้ขายแล้ว' : 'ยังไม่แสดงต่อผู้ใช้ทั่วไป' ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5"><div class="detail-image"><?= product_image($p) ?></div></div>
  <div class="col-lg-<?= $side ? 5 : 7 ?>">
    <div class="d-flex gap-2 mb-2">
      <span class="badge rounded-pill bg-mint text-green"><?= $p['type'] === 'digital' ? 'สินค้าดิจิทัล' : 'สินค้ามือสอง' ?></span>
      <?php if ($p['cat_name']): ?><span class="badge rounded-pill bg-mint text-green"><i class="bi <?= e($p['cat_icon']) ?>"></i> <?= e($p['cat_name']) ?></span><?php endif; ?>
    </div>
    <h1 class="h3"><?= e($p['title']) ?></h1>
    <div class="price fs-2 mb-3"><?= baht($p['price']) ?></div>

    <div class="panel d-flex align-items-center gap-3 mb-3">
      <div class="score-ring" style="--v: <?= (int) $p['green_score'] ?>"><span><?= (int) $p['green_score'] ?></span></div>
      <div><b class="text-green"><i class="bi bi-recycle"></i> Green Score <?= (int) $p['green_score'] ?> คะแนน</b>
        <div class="small text-muted"><?= $p['type'] === 'digital' ? 'สินค้าดิจิทัล ไม่มีบรรจุภัณฑ์และการขนส่ง ลดการใช้ทรัพยากร' : 'การซื้อสินค้ามือสองช่วยยืดอายุการใช้งานอุปกรณ์และลดขยะอิเล็กทรอนิกส์' ?></div>
        <div class="small mt-1">ซื้อสินค้านี้รับ <b class="text-green">+<?= POINTS[$p['type'] === 'digital' ? 'buy_digital' : 'buy_market'] ?> Green Point</b></div></div>
    </div>

    <table class="table table-sm small mb-3">
      <?php if ($p['type'] === 'market'): ?>
      <tr><th class="text-muted fw-normal" style="width:35%">สภาพสินค้า</th><td><?= isset(CONDITIONS[$p['item_condition']]) ? CONDITIONS[$p['item_condition']][0] : '-' ?></td></tr>
      <tr><th class="text-muted fw-normal">จำนวนคงเหลือ</th><td><?= (int) $p['stock'] ?> ชิ้น</td></tr>
      <?php else: ?>
      <tr><th class="text-muted fw-normal" style="width:35%">การจัดส่ง</th><td>ดาวน์โหลดได้ทันทีหลังชำระเงิน</td></tr>
      <?php endif; ?>
      <tr><th class="text-muted fw-normal">ลงขายเมื่อ</th><td><?= thai_date($p['created_at'], false) ?></td></tr>
      <tr><th class="text-muted fw-normal">เข้าชม</th><td><?= number_format($p['views']) ?> ครั้ง</td></tr>
    </table>

    <div class="d-flex flex-wrap gap-2">
      <?php if ($isOwner): ?>
        <a class="btn btn-primary px-4" href="<?= e(url('sell.php?id=' . $id)) ?>"><i class="bi bi-pencil"></i> แก้ไขสินค้า</a>
      <?php elseif ($owned): ?>
        <a class="btn btn-primary px-4" href="<?= e(url('download.php?order=' . $owned)) ?>"><i class="bi bi-download"></i> ดาวน์โหลดไฟล์</a>
      <?php elseif ($available): ?>
        <a class="btn btn-primary btn-lg px-5" href="<?= e(url('checkout.php?id=' . $id)) ?>"><i class="bi bi-bag-check"></i> ซื้อสินค้า</a>
      <?php else: ?>
        <button class="btn btn-secondary btn-lg px-5" disabled>สินค้าหมด</button>
      <?php endif; ?>
      <?php if (!$isOwner): ?>
        <a class="btn btn-outline-primary btn-lg" href="<?= e(url('chat.php?with=' . $seller['id'] . '&product=' . $id)) ?>"><i class="bi bi-chat-dots"></i> ติดต่อผู้ขาย</a>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="favorite">
          <button class="btn btn-lg <?= $fav ? 'btn-danger' : 'btn-outline-danger' ?>" title="<?= $fav ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด' ?>"><i class="bi bi-heart<?= $fav ? '-fill' : '' ?>"></i></button></form>
      <?php endif; ?>
    </div>

    <div class="panel d-flex align-items-center gap-3 mt-3">
      <?= avatar_html($seller, 48) ?>
      <div class="flex-grow-1"><div class="fw-semibold"><?= e($seller['name']) ?><?= premium_badge($seller) ?></div>
        <div class="small text-muted">ขายสำเร็จ <?= $sellerSales ?> รายการ · สมาชิกตั้งแต่ <?= thai_date($seller['created_at'], false) ?></div></div>
    </div>
  </div>
  <?php if ($side): ?><div class="col-lg-2 d-none d-lg-block"><?= banner_html($side[0]) ?></div><?php endif; ?>
</div>

<div class="panel mt-4">
  <div class="panel-title"><i class="bi bi-card-text"></i> รายละเอียดสินค้า</div>
  <div style="white-space: pre-line"><?= e($p['description'] ?: 'ไม่มีรายละเอียดเพิ่มเติม') ?></div>
</div>

<?php if ($related): ?>
<section class="section">
  <div class="section-head"><h2 class="pill-title">สินค้าที่เกี่ยวข้อง</h2></div>
  <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
    <?php foreach ($related as $r): ?><div class="col"><?= product_card($r) ?></div><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
