<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$s = row("SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'user') AS users,
    (SELECT COUNT(*) FROM users WHERE premium_until > NOW()) AS premium,
    (SELECT COUNT(*) FROM products WHERE status = 'approved') AS products,
    (SELECT COUNT(*) FROM products WHERE status = 'pending') AS products_pending,
    (SELECT COUNT(*) FROM orders) AS orders,
    (SELECT COALESCE(SUM(price), 0) FROM orders WHERE status = 'completed') AS sales,
    (SELECT COALESCE(SUM(commission), 0) FROM orders WHERE status = 'completed') AS commission,
    (SELECT COALESCE(-SUM(amount), 0) FROM wallet_transactions WHERE type IN ('promote', 'premium')) AS services,
    (SELECT COUNT(*) FROM topups WHERE status = 'pending') AS topups_pending,
    (SELECT COALESCE(SUM(amount), 0) FROM topups WHERE status = 'approved') AS topups,
    (SELECT COUNT(*) FROM recycles) AS recycles,
    (SELECT COUNT(*) FROM recycles WHERE status = 'completed') AS recycles_done,
    (SELECT COALESCE(SUM(points), 0) FROM point_transactions WHERE points > 0) AS points_given,
    (SELECT COALESCE(-SUM(points), 0) FROM point_transactions WHERE points < 0) AS points_used");

// ยอดคำสั่งซื้อย้อนหลัง 7 วัน
$byDay = [];
foreach (rows('SELECT DATE(created_at) AS d, COUNT(*) AS n FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY d') as $r) {
    $byDay[$r['d']] = (int) $r['n'];
}
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $days[$d] = isset($byDay[$d]) ? $byDay[$d] : 0;
}
$peak = max(1, max($days));
$latest = rows('SELECT o.*, b.name AS buyer, s.name AS seller FROM orders o JOIN users b ON b.id = o.buyer_id JOIN users s ON s.id = o.seller_id ORDER BY o.id DESC LIMIT 6');

$title = 'Dashboard';
require __DIR__ . '/../includes/admin_header.php';
$cards = [
    ['bi-people', number_format($s['users']), 'ผู้ใช้ทั้งหมด (Premium ' . $s['premium'] . ')', 'users.php'],
    ['bi-box-seam', number_format($s['products']), 'สินค้าที่วางขาย (รออนุมัติ ' . $s['products_pending'] . ')', 'products.php'],
    ['bi-cart-check', number_format($s['orders']), 'คำสั่งซื้อทั้งหมด', 'orders.php'],
    ['bi-graph-up-arrow', baht($s['sales']), 'ยอดขายที่สำเร็จ', 'orders.php'],
    ['bi-percent', baht($s['commission']), 'รายได้ค่าธรรมเนียม', 'orders.php'],
    ['bi-megaphone', baht($s['services']), 'รายได้ Premium + โปรโมทสินค้า', 'users.php'],
    ['bi-cash-coin', baht($s['topups']), 'ยอดเติมเงิน (รอตรวจ ' . $s['topups_pending'] . ')', 'topups.php'],
    ['bi-recycle', number_format($s['recycles_done']) . ' / ' . number_format($s['recycles']), 'รีไซเคิลสำเร็จ / คำขอทั้งหมด', 'recycles.php'],
    ['bi-award', number_format($s['points_given']), 'Green Point ที่แจก (ใช้แล้ว ' . number_format($s['points_used']) . ')', 'rewards.php'],
];
?>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4">
  <?php foreach ($cards as $c): ?>
  <div class="col"><a class="stat text-reset" href="<?= e(url('admin/' . $c[3])) ?>"><span class="ico"><i class="bi <?= $c[0] ?>"></i></span><div><div class="num"><?= $c[1] ?></div><div class="lbl"><?= e($c[2]) ?></div></div></a></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-xl-5">
    <div class="panel h-100">
      <div class="panel-title"><i class="bi bi-bar-chart"></i> คำสั่งซื้อ 7 วันล่าสุด</div>
      <div class="bars">
        <?php foreach ($days as $d => $n): ?>
        <div class="bar"><b><?= $n ?></b><i style="height: <?= round($n / $peak * 80) ?>%" title="<?= thai_date($d, false) ?>: <?= $n ?> คำสั่งซื้อ"></i><span><?= date('j/n', strtotime($d)) ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-7">
    <div class="panel h-100">
      <div class="panel-title"><i class="bi bi-receipt"></i> คำสั่งซื้อล่าสุด</div>
      <?php if ($latest): ?>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>เลขที่</th><th>สินค้า</th><th>ผู้ซื้อ</th><th class="text-end">ยอด</th><th>สถานะ</th></tr></thead>
        <tbody><?php foreach ($latest as $o): ?>
          <tr><td class="small">#<?= e($o['order_no']) ?></td><td><?= e($o['product_title']) ?></td><td class="small"><?= e($o['buyer']) ?></td><td class="text-end"><?= baht($o['price']) ?></td><td><?= badge($o['status']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php else: ?><div class="empty py-4"><i class="bi bi-inbox"></i>ยังไม่มีคำสั่งซื้อ</div><?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
