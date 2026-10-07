<?php
require __DIR__ . '/includes/init.php';
$u = user();
$price = (float) setting('premium_price', 99);

if (is_post()) {
    $u = require_login();
    $ok = in_tx(function () use ($u, $price) {
        if (!wallet_move($u['id'], -$price, 'premium', 'สมัครสมาชิก Premium ' . PREMIUM_DAYS . ' วัน')) {
            return false;
        }
        extend_premium($u['id'], PREMIUM_DAYS);
        return true;
    });
    $ok ? flash('ยินดีต้อนรับสู่สมาชิก Premium') : flash('ยอดเงินใน Wallet ไม่เพียงพอ กรุณาเติมเงิน', 'danger');
    redirect('premium.php');
}

$title = 'สมาชิก Premium';
require __DIR__ . '/includes/header.php';
echo page_header('สมาชิกพรีเมียม (Premium Membership)', 'ปลดล็อกสิทธิพิเศษเพิ่มเติมสำหรับผู้ใช้ที่สมัครสมาชิก');
?>
<div class="row g-4 justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="panel h-100">
      <h2 class="h5">สมาชิกทั่วไป</h2>
      <div class="fs-2 fw-bold mb-3">ฟรี</div>
      <div class="benefit"><i class="bi bi-check-circle-fill"></i> ซื้อขายสินค้ามือสองและดิจิทัล</div>
      <div class="benefit"><i class="bi bi-check-circle-fill"></i> สะสมและใช้ Green Point</div>
      <div class="benefit"><i class="bi bi-check-circle-fill"></i> ค่าธรรมเนียมการขาย <?= e(setting('commission_rate', 5)) ?>%</div>
      <div class="benefit"><i class="bi bi-check-circle-fill"></i> โปรโมทสินค้า <?= baht(setting('promote_price', 29)) ?> / <?= PROMOTE_DAYS ?> วัน</div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="panel h-100 border-2" style="border-color: var(--gl-gold)">
      <h2 class="h5"><span class="badge badge-premium"><i class="bi bi-gem"></i> Premium</span></h2>
      <div class="fs-2 fw-bold mb-3"><?= baht($price) ?> <small class="fs-6 fw-normal text-muted">/ <?= PREMIUM_DAYS ?> วัน</small></div>
      <div class="benefit"><i class="bi bi-check-circle-fill"></i> ทุกสิทธิ์ของสมาชิกทั่วไป</div>
      <div class="benefit"><i class="bi bi-check-circle-fill"></i> ค่าธรรมเนียมการขายเหลือ <?= e(setting('premium_commission_rate', 3)) ?>%</div>
      <div class="benefit"><i class="bi bi-check-circle-fill"></i> โปรโมทสินค้าลด 50%</div>
      <div class="benefit"><i class="bi bi-check-circle-fill"></i> ป้าย Premium บนโปรไฟล์ผู้ขาย</div>
      <?php if (is_premium($u)): ?><div class="alert alert-success small mt-3 mb-0">คุณเป็นสมาชิก Premium ถึงวันที่ <?= thai_date($u['premium_until'], false) ?></div><?php endif; ?>
      <form method="post" class="mt-3"><?= csrf_field() ?>
        <button class="btn btn-primary w-100" data-confirm="ชำระ <?= baht($price) ?> จาก Wallet เพื่อ<?= is_premium($u) ? 'ต่ออายุ' : 'สมัคร' ?> Premium <?= PREMIUM_DAYS ?> วัน?"><i class="bi bi-wallet2"></i> <?= is_premium($u) ? 'ต่ออายุ' : 'สมัคร' ?>ด้วย Wallet</button></form>
      <a class="btn btn-link w-100 btn-sm mt-1" href="<?= e(url('points.php')) ?>">หรือแลกด้วย Green Point</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
