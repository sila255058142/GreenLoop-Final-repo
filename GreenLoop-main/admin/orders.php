<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if (is_post()) {
    $o = row('SELECT * FROM orders WHERE id = ?', [(int) post('id')]);
    $action = post('action');
    if (!$o) {
        flash('ไม่พบคำสั่งซื้อ', 'danger');
    } elseif ($action === 'ship' && $o['status'] === 'paid') {
        q("UPDATE orders SET status = 'shipped', tracking = ? WHERE id = ?", [mb_substr(post('tracking'), 0, 100), $o['id']]);
        flash('เปลี่ยนสถานะเป็นจัดส่งแล้ว');
    } elseif ($action === 'complete' && complete_order($o['id'])) {
        flash('ปิดคำสั่งซื้อสำเร็จ โอนเงินให้ผู้ขายและแจก Green Point แล้ว');
    } elseif ($action === 'cancel' && cancel_order($o['id'])) {
        flash('ยกเลิกคำสั่งซื้อและคืนเงินให้ผู้ซื้อแล้ว', 'info');
    } else {
        flash('ไม่สามารถเปลี่ยนสถานะคำสั่งซื้อนี้ได้', 'warning');
    }
    back('admin/orders.php');
}

$status = in_array(get('status'), ['paid', 'shipped', 'completed', 'cancelled'], true) ? get('status') : '';
$kw = get('q');
$like = '%' . addcslashes($kw, '%_\\') . '%';
$where = ' WHERE (o.order_no LIKE ? OR o.product_title LIKE ? OR b.name LIKE ? OR s.name LIKE ?)';
$args = [$like, $like, $like, $like];
if ($status) {
    $where .= ' AND o.status = ?';
    $args[] = $status;
}
$list = rows('SELECT o.*, b.name AS buyer, s.name AS seller FROM orders o JOIN users b ON b.id = o.buyer_id JOIN users s ON s.id = o.seller_id' . $where . ' ORDER BY o.id DESC LIMIT 200', $args);

$title = 'จัดการคำสั่งซื้อ';
require __DIR__ . '/../includes/admin_header.php';
?>
<form class="d-flex gap-2 mb-3" method="get" style="max-width:560px">
  <input class="form-control" name="q" value="<?= e($kw) ?>" placeholder="เลขที่ / สินค้า / ผู้ซื้อ / ผู้ขาย" aria-label="ค้นหาคำสั่งซื้อ">
  <select class="form-select" name="status" aria-label="สถานะ"><option value="">ทุกสถานะ</option>
    <?php foreach (['paid', 'shipped', 'completed', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= STATUS_LABELS[$s][0] ?></option><?php endforeach; ?></select>
  <button class="btn btn-outline-primary text-nowrap">กรอง</button>
</form>
<div class="panel p-0 overflow-hidden">
<?php if ($list): ?>
<div class="table-responsive"><table class="table mb-0">
  <thead><tr><th class="ps-3">คำสั่งซื้อ</th><th>สินค้า</th><th>ผู้ซื้อ → ผู้ขาย</th><th class="text-end">การชำระเงิน (Wallet)</th><th>สถานะ</th><th class="text-end pe-3">เปลี่ยนสถานะ</th></tr></thead>
  <tbody>
  <?php foreach ($list as $o): $open = in_array($o['status'], ['paid', 'shipped'], true); ?>
    <tr>
      <td class="ps-3 small"><b>#<?= e($o['order_no']) ?></b><br><?= thai_date($o['created_at']) ?></td>
      <td><?= e($o['product_title']) ?><div class="small text-muted"><?= $o['type'] === 'digital' ? 'ดิจิทัล' : 'มือสอง' ?><?= $o['tracking'] !== '' ? ' · พัสดุ ' . e($o['tracking']) : '' ?></div>
        <?php if ($o['type'] === 'market'): ?><div class="small text-muted"><i class="bi bi-truck"></i> <?= e($o['ship_name']) ?> <?= e($o['ship_phone']) ?> — <?= e($o['ship_address']) ?></div><?php endif; ?></td>
      <td class="small"><?= e($o['buyer']) ?> → <?= e($o['seller']) ?></td>
      <td class="text-end small">ราคา <?= baht($o['price']) ?><?= $o['discount'] > 0 ? '<br>ส่วนลด -' . baht($o['discount']) . ($o['points_used'] ? ' (' . number_format($o['points_used']) . ' คะแนน)' : '') : '' ?>
        <br><b>ชำระแล้ว <?= baht($o['total']) ?></b><?= $o['status'] === 'completed' ? '<br>ค่าธรรมเนียม ' . baht($o['commission']) . ' · ผู้ขายรับ ' . baht($o['seller_amount']) : '' ?></td>
      <td><?= badge($o['status']) ?></td>
      <td class="text-end pe-3">
        <?php if ($open): ?>
        <form method="post" class="d-flex gap-1 justify-content-end flex-wrap"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
          <?php if ($o['status'] === 'paid'): ?>
            <input class="form-control form-control-sm" style="width:120px" name="tracking" placeholder="เลขพัสดุ" maxlength="100" aria-label="เลขพัสดุ">
            <button class="btn btn-sm btn-outline-primary" name="action" value="ship">จัดส่งแล้ว</button>
          <?php endif; ?>
          <button class="btn btn-sm btn-success" name="action" value="complete" data-confirm="ปิดคำสั่งซื้อและโอนเงินให้ผู้ขาย?">สำเร็จ</button>
          <button class="btn btn-sm btn-outline-danger" name="action" value="cancel" data-confirm="ยกเลิกและคืนเงินให้ผู้ซื้อ?">ยกเลิก</button>
        </form>
        <?php else: ?><span class="small text-muted"><?= $o['completed_at'] ? 'สำเร็จเมื่อ ' . thai_date($o['completed_at']) : '-' ?></span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><div class="empty"><i class="bi bi-cart"></i>ไม่พบคำสั่งซื้อ</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
