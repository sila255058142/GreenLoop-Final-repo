<?php
require __DIR__ . '/includes/init.php';
$u = require_login();
$tab = get('tab') === 'sales' ? 'sales' : 'purchases';

if (is_post()) {
    $o = row('SELECT * FROM orders WHERE id = ? AND (buyer_id = ? OR seller_id = ?)', [(int) post('id'), $u['id'], $u['id']]);
    $action = post('action');
    $isBuyer = $o && $o['buyer_id'] == $u['id'];
    if (!$o) {
        flash('ไม่พบคำสั่งซื้อ', 'danger');
    } elseif ($action === 'receive' && $isBuyer && $o['status'] === 'shipped') {
        complete_order($o['id']);
        flash('ยืนยันรับสินค้าแล้ว ขอบคุณที่ร่วมส่งต่อเทคโนโลยี');
    } elseif ($action === 'ship' && !$isBuyer && $o['status'] === 'paid') {
        q("UPDATE orders SET status = 'shipped', tracking = ? WHERE id = ?", [mb_substr(post('tracking'), 0, 100), $o['id']]);
        flash('บันทึกการจัดส่งแล้ว');
    } elseif ($action === 'cancel' && ($o['status'] === 'paid' || (!$isBuyer && $o['status'] === 'shipped'))) {
        cancel_order($o['id']);
        flash('ยกเลิกคำสั่งซื้อและคืนเงินให้ผู้ซื้อแล้ว', 'info');
    } else {
        flash('ไม่สามารถทำรายการนี้ได้', 'warning');
    }
    redirect('orders.php?tab=' . ($isBuyer ? 'purchases' : 'sales'));
}

$side = $tab === 'sales' ? 'seller_id' : 'buyer_id';
$other = $tab === 'sales' ? 'buyer_id' : 'seller_id';
$list = rows("SELECT o.*, p.image, p.type AS ptype, c.icon AS cat_icon, x.name AS other_name
    FROM orders o JOIN products p ON p.id = o.product_id LEFT JOIN categories c ON c.id = p.category_id JOIN users x ON x.id = o.$other
    WHERE o.$side = ? ORDER BY o.id DESC", [$u['id']]);

$title = 'ประวัติการซื้อ / ขาย';
require __DIR__ . '/includes/header.php';
?>
<div class="section-head">
  <h1 class="pill-title"><?= $tab === 'sales' ? 'รายการขายของฉัน' : 'ประวัติการซื้อ' ?></h1>
  <ul class="nav nav-pills">
    <li class="nav-item"><a class="nav-link <?= $tab === 'purchases' ? 'active' : '' ?>" href="<?= e(url('orders.php')) ?>"><i class="bi bi-bag"></i> การซื้อ</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'sales' ? 'active' : '' ?>" href="<?= e(url('orders.php?tab=sales')) ?>"><i class="bi bi-shop"></i> การขาย</a></li>
  </ul>
</div>
<?php if (!$list): ?>
  <div class="panel empty"><i class="bi bi-receipt"></i>ยังไม่มีรายการ<br><a class="btn btn-primary mt-3" href="<?= e(url($tab === 'sales' ? 'sell.php' : 'products.php')) ?>"><?= $tab === 'sales' ? 'ลงขายสินค้า' : 'เลือกซื้อสินค้า' ?></a></div>
<?php endif; ?>
<?php foreach ($list as $o):
    $digital = $o['type'] === 'digital';
    $step = ['paid' => 1, 'shipped' => 2, 'completed' => 3, 'cancelled' => 0][$o['status']];
    $thumb = ['image' => $o['image'], 'title' => $o['product_title'], 'cat_icon' => $o['cat_icon'], 'type' => $o['ptype']];
?>
<div class="panel mb-3">
  <div class="d-flex flex-wrap justify-content-between gap-2 small text-muted mb-2">
    <span>คำสั่งซื้อ <b class="text-dark">#<?= e($o['order_no']) ?></b> · <?= thai_date($o['created_at']) ?></span>
    <span><?= badge($o['status']) ?></span>
  </div>
  <div class="d-flex gap-3 align-items-center flex-wrap">
    <a class="thumb-sm" style="width:72px;height:72px" href="<?= e(url('product.php?id=' . $o['product_id'])) ?>"><?= product_image($thumb) ?></a>
    <div class="flex-grow-1">
      <div class="fw-semibold"><?= e($o['product_title']) ?></div>
      <div class="small text-muted"><?= $digital ? 'สินค้าดิจิทัล' : 'สินค้ามือสอง' ?> · <?= $tab === 'sales' ? 'ผู้ซื้อ' : 'ผู้ขาย' ?>: <?= e($o['other_name']) ?>
        <a href="<?= e(url('chat.php?with=' . $o[$other] . '&product=' . $o['product_id'])) ?>"><i class="bi bi-chat-dots"></i> แชท</a></div>
      <?php if ($o['tracking'] !== ''): ?><div class="small">เลขพัสดุ: <b><?= e($o['tracking']) ?></b></div><?php endif; ?>
    </div>
    <div class="text-end">
      <?php if ($tab === 'sales'): ?>
        <div class="price"><?= baht($o['price']) ?></div>
        <?php if ($o['status'] === 'completed'): ?><div class="small text-muted">ค่าธรรมเนียม <?= baht($o['commission']) ?> · รับสุทธิ <b class="text-green"><?= baht($o['seller_amount']) ?></b></div><?php endif; ?>
      <?php else: ?>
        <div class="price"><?= baht($o['total']) ?></div>
        <?php if ($o['discount'] > 0): ?><div class="small text-muted">ส่วนลด <?= baht($o['discount']) ?><?= $o['points_used'] ? ' (ใช้ ' . number_format($o['points_used']) . ' คะแนน)' : '' ?></div><?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!$digital && $step): ?>
  <div class="tracker">
    <div class="st <?= $step >= 1 ? 'on' : '' ?>">ชำระเงินแล้ว</div>
    <div class="st <?= $step >= 2 ? 'on' : '' ?>">จัดส่งแล้ว</div>
    <div class="st <?= $step >= 3 ? 'on' : '' ?>">สำเร็จ</div>
  </div>
  <?php endif; ?>
  <?php if ($tab === 'sales' && !$digital): ?>
    <div class="small bg-mint rounded-3 p-2 mt-2"><i class="bi bi-truck"></i> จัดส่งถึง: <?= e($o['ship_name']) ?> (<?= e($o['ship_phone']) ?>) — <?= e($o['ship_address']) ?></div>
  <?php endif; ?>
  <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
    <?php if ($tab === 'purchases' && $digital && $o['status'] === 'completed'): ?>
      <a class="btn btn-primary btn-sm px-3" href="<?= e(url('download.php?order=' . $o['id'])) ?>"><i class="bi bi-download"></i> ดาวน์โหลดสินค้าดิจิทัล</a>
    <?php endif; ?>
    <form method="post" class="d-flex flex-wrap gap-2 justify-content-end"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
      <?php if ($tab === 'purchases' && $o['status'] === 'shipped'): ?>
        <button class="btn btn-primary btn-sm px-3" name="action" value="receive" data-confirm="ยืนยันว่าได้รับสินค้าแล้ว? ระบบจะโอนเงินให้ผู้ขาย"><i class="bi bi-check2-circle"></i> ยืนยันรับสินค้า</button>
      <?php endif; ?>
      <?php if ($tab === 'sales' && $o['status'] === 'paid'): ?>
        <input class="form-control form-control-sm" style="width:200px" name="tracking" placeholder="เลขพัสดุ (ถ้ามี)" maxlength="100" aria-label="เลขพัสดุ">
        <button class="btn btn-primary btn-sm px-3" name="action" value="ship"><i class="bi bi-truck"></i> แจ้งจัดส่งแล้ว</button>
      <?php endif; ?>
      <?php if ($o['status'] === 'paid' || ($tab === 'sales' && $o['status'] === 'shipped')): ?>
        <button class="btn btn-outline-danger btn-sm" name="action" value="cancel" data-confirm="ยกเลิกคำสั่งซื้อนี้และคืนเงินให้ผู้ซื้อ?">ยกเลิกคำสั่งซื้อ</button>
      <?php endif; ?>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
