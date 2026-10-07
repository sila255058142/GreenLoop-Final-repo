<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

$id = (int) (is_post() ? post('id') : get('id'));
$p = row(PRODUCT_SELECT . ' WHERE p.id = ?', [$id]);
if (!$p || $p['status'] !== 'approved' || ($p['type'] === 'market' && $p['stock'] < 1)) {
    flash('สินค้านี้ไม่พร้อมจำหน่าย', 'warning');
    redirect('products.php');
}
if ($p['user_id'] == $u['id']) {
    flash('ไม่สามารถซื้อสินค้าของตัวเองได้', 'warning');
    redirect('product.php?id=' . $id);
}
$digital = $p['type'] === 'digital';
if ($digital && val("SELECT 1 FROM orders WHERE buyer_id = ? AND product_id = ? AND status = 'completed'", [$u['id'], $id])) {
    flash('คุณซื้อสินค้านี้แล้ว ดาวน์โหลดได้ที่ประวัติการซื้อ', 'info');
    redirect('orders.php');
}

$price = (float) $p['price'];
// คะแนนที่ใช้ได้สูงสุด: ไม่เกินคะแนนที่มี และมูลค่าไม่เกิน POINTS_MAX_PERCENT ของราคา
$maxPoints = (int) min(floor($u['green_points'] / POINTS_PER_BAHT), floor($price * POINTS_MAX_PERCENT / 100)) * POINTS_PER_BAHT;
$coupons = rows("SELECT * FROM redemptions WHERE user_id = ? AND type = 'coupon' AND status = 'active' ORDER BY value DESC", [$u['id']]);

if (is_post()) {
    $points = max(0, min($maxPoints, (int) post('points', '0')));
    $points -= $points % POINTS_PER_BAHT;
    $couponId = (int) post('coupon');
    $ship = ['name' => post('ship_name'), 'phone' => post('ship_phone'), 'address' => post('ship_address')];
    $err = '';
    if (!$digital && ($ship['name'] === '' || $ship['phone'] === '' || $ship['address'] === '')) {
        $err = 'กรุณากรอกข้อมูลจัดส่งให้ครบถ้วน';
    }
    $orderId = $err ? false : in_tx(function () use ($u, $p, $id, $price, $digital, $points, $couponId, $ship, &$err) {
        $live = row('SELECT status, stock, price FROM products WHERE id = ? FOR UPDATE', [$id]);
        if ($live['status'] !== 'approved' || (!$digital && $live['stock'] < 1) || (float) $live['price'] !== $price) {
            $err = 'สินค้ามีการเปลี่ยนแปลงหรือถูกขายไปแล้ว กรุณาตรวจสอบอีกครั้ง';
            return false;
        }
        $couponValue = 0;
        if ($couponId) {
            $couponValue = (float) val("SELECT value FROM redemptions WHERE id = ? AND user_id = ? AND type = 'coupon' AND status = 'active' FOR UPDATE", [$couponId, $u['id']]);
            if (!$couponValue) {
                $err = 'คูปองไม่ถูกต้องหรือถูกใช้ไปแล้ว';
                return false;
            }
            q("UPDATE redemptions SET status = 'used' WHERE id = ?", [$couponId]);
        }
        $discount = min($price, $points / POINTS_PER_BAHT + $couponValue);
        $total = $price - $discount;
        $no = 'GL' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        if (!add_points($u['id'], -$points, 'ใช้คะแนนเป็นส่วนลด คำสั่งซื้อ #' . $no)) {
            $err = 'Green Point ไม่เพียงพอ';
            return false;
        }
        if (!wallet_move($u['id'], -$total, 'purchase', 'ซื้อสินค้า ' . $p['title'] . ' (#' . $no . ')')) {
            $err = 'ยอดเงินใน Wallet ไม่เพียงพอ กรุณาเติมเงินก่อนทำรายการ';
            return false;
        }
        if (!$digital) {
            q("UPDATE products SET stock = stock - 1, status = IF(stock <= 0, 'sold', status) WHERE id = ?", [$id]);
        }
        q('INSERT INTO orders (order_no, buyer_id, seller_id, product_id, product_title, type, price, points_used, coupon_id, discount, total, ship_name, ship_phone, ship_address) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$no, $u['id'], $p['user_id'], $id, $p['title'], $p['type'], $price, $points, $couponId ?: null, $discount, $total, mb_substr($ship['name'], 0, 100), mb_substr($ship['phone'], 0, 30), $ship['address']]);
        $orderId = (int) db()->lastInsertId();
        if ($digital) {
            complete_order($orderId);
        }
        return $orderId;
    });
    if ($orderId) {
        flash($digital ? 'ชำระเงินสำเร็จ ดาวน์โหลดไฟล์ได้ทันที' : 'สั่งซื้อสำเร็จ ระบบหักเงินจาก Wallet แล้ว รอผู้ขายจัดส่งสินค้า');
        redirect('orders.php');
    }
    flash($err, 'danger');
    redirect('checkout.php?id=' . $id);
}

$title = 'ชำระเงิน';
require __DIR__ . '/includes/header.php';
echo page_header('ซื้อสินค้า — ชำระเงินด้วย Wallet');
?>
<form method="post" class="row g-4" id="checkout" data-price="<?= $price ?>" data-rate="<?= POINTS_PER_BAHT ?>" data-balance="<?= (float) $u['wallet_balance'] ?>">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="col-lg-7">
    <div class="panel mb-4">
      <div class="panel-title"><i class="bi bi-bag"></i> สินค้า</div>
      <div class="d-flex gap-3 align-items-center">
        <span class="thumb-sm" style="width:84px;height:84px"><?= product_image($p) ?></span>
        <div class="flex-grow-1"><div class="fw-semibold"><?= e($p['title']) ?></div>
          <div class="small text-muted"><?= $digital ? 'สินค้าดิจิทัล' : 'สินค้ามือสอง' ?> · ผู้ขาย <?= e($p['seller_name']) ?></div>
          <div class="score mt-1"><i class="bi bi-recycle"></i> Green Score <?= (int) $p['green_score'] ?> · รับ +<?= POINTS[$digital ? 'buy_digital' : 'buy_market'] ?> Point เมื่อสำเร็จ</div></div>
        <div class="price"><?= baht($price) ?></div>
      </div>
    </div>
    <?php if (!$digital): ?>
    <div class="panel mb-4">
      <div class="panel-title"><i class="bi bi-truck"></i> ข้อมูลจัดส่ง</div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="ship_name">ชื่อผู้รับ</label><input id="ship_name" class="form-control" name="ship_name" value="<?= e($u['name']) ?>" required maxlength="100"></div>
        <div class="col-md-6"><label class="form-label" for="ship_phone">เบอร์โทรศัพท์</label><input id="ship_phone" class="form-control" name="ship_phone" value="<?= e($u['phone']) ?>" required maxlength="30"></div>
        <div class="col-12"><label class="form-label" for="ship_address">ที่อยู่จัดส่ง</label><textarea id="ship_address" class="form-control" name="ship_address" rows="2" required><?= e($u['address']) ?></textarea></div>
      </div>
    </div>
    <?php endif; ?>
    <div class="panel">
      <div class="panel-title"><i class="bi bi-percent"></i> ส่วนลด</div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="points">ใช้ Green Point (<?= POINTS_PER_BAHT ?> คะแนน = ฿1)</label>
          <input id="points" class="form-control" type="number" name="points" min="0" max="<?= $maxPoints ?>" step="<?= POINTS_PER_BAHT ?>" value="0" <?= $maxPoints ? '' : 'disabled' ?>>
          <div class="form-text">คุณมี <?= number_format($u['green_points']) ?> คะแนน · ใช้ได้สูงสุด <?= number_format($maxPoints) ?> คะแนน (ไม่เกิน <?= POINTS_MAX_PERCENT ?>% ของราคา)</div></div>
        <div class="col-md-6"><label class="form-label" for="coupon">คูปองส่วนลด</label>
          <select id="coupon" class="form-select" name="coupon" <?= $coupons ? '' : 'disabled' ?>>
            <option value="" data-value="0"><?= $coupons ? 'ไม่ใช้คูปอง' : 'ยังไม่มีคูปอง' ?></option>
            <?php foreach ($coupons as $c): ?><option value="<?= $c['id'] ?>" data-value="<?= (float) $c['value'] ?>"><?= e($c['reward_name']) ?> (<?= e($c['code']) ?>)</option><?php endforeach; ?>
          </select>
          <div class="form-text"><a href="<?= e(url('points.php')) ?>">แลกคูปองด้วย Green Point</a></div></div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-receipt"></i> สรุปคำสั่งซื้อ</div>
      <div class="d-flex justify-content-between mb-1"><span>ราคาสินค้า</span><span><?= baht($price) ?></span></div>
      <div class="d-flex justify-content-between mb-1 text-success"><span>ส่วนลด</span><span id="sum-discount">-฿0</span></div>
      <hr>
      <div class="d-flex justify-content-between fs-5 fw-bold"><span>ยอดชำระ</span><span class="text-green" id="sum-total"><?= baht($price) ?></span></div>
      <div class="d-flex justify-content-between small text-muted mt-2"><span><i class="bi bi-wallet2"></i> ยอดเงินใน Wallet</span><span><?= baht($u['wallet_balance']) ?></span></div>
      <div class="alert alert-danger small mt-3 mb-0 d-none" id="sum-warn">ยอดเงินไม่เพียงพอ <a href="<?= e(url('wallet.php')) ?>" class="alert-link">เติมเงิน</a></div>
      <button class="btn btn-primary btn-lg w-100 mt-3" id="pay"><i class="bi bi-wallet2"></i> ชำระเงินด้วย Wallet</button>
      <p class="small text-muted text-center mt-2 mb-0">ระบบจะหักเงินจาก Wallet อัตโนมัติและสร้างคำสั่งซื้อทันที</p>
    </div>
  </div>
</form>
<script>
(function () {
  var f = document.getElementById('checkout'), pts = document.getElementById('points'), cp = document.getElementById('coupon');
  var price = +f.dataset.price, rate = +f.dataset.rate, balance = +f.dataset.balance;
  function fmt(n) { return '฿' + n.toLocaleString('th-TH', { maximumFractionDigits: 2 }); }
  function calc() {
    var p = Math.max(0, Math.min(+pts.max, Math.floor((+pts.value || 0) / rate) * rate));
    var discount = Math.min(price, p / rate + (+cp.selectedOptions[0].dataset.value || 0));
    var total = price - discount;
    document.getElementById('sum-discount').textContent = '-' + fmt(discount);
    document.getElementById('sum-total').textContent = fmt(total);
    document.getElementById('sum-warn').classList.toggle('d-none', total <= balance);
    document.getElementById('pay').disabled = total > balance;
  }
  pts.addEventListener('input', calc);
  cp.addEventListener('change', calc);
  calc();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
