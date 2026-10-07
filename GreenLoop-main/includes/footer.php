</main>
<footer class="gl-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-2"><?= logo_html(34) ?></div>
        <p class="mb-1"><?= e(setting('tagline')) ?></p>
        <p class="small opacity-75 mb-0">แพลตฟอร์มซื้อขาย-ส่งต่ออุปกรณ์ดิจิทัลมือสอง สินค้าดิจิทัล และบริการรีไซเคิลอุปกรณ์อิเล็กทรอนิกส์ (E-Waste) พร้อมระบบ Green Point</p>
      </div>
      <div class="col-6 col-lg-2">
        <h6>บริการ</h6>
        <a href="<?= e(url('products.php?type=market')) ?>">Market</a>
        <a href="<?= e(url('products.php?type=digital')) ?>">Digital Market</a>
        <a href="<?= e(url('recycle.php')) ?>">Recycle</a>
        <a href="<?= e(url('points.php')) ?>">Green Point</a>
      </div>
      <div class="col-6 col-lg-2">
        <h6>สำหรับสมาชิก</h6>
        <a href="<?= e(url('sell.php')) ?>">ลงขายสินค้า</a>
        <a href="<?= e(url('wallet.php')) ?>">กระเป๋าเงิน</a>
        <a href="<?= e(url('orders.php')) ?>">ประวัติการซื้อ</a>
        <a href="<?= e(url('premium.php')) ?>">สมาชิก Premium</a>
      </div>
      <div class="col-lg-4">
        <h6>ติดต่อเรา</h6>
        <p class="small mb-1"><i class="bi bi-geo-alt"></i> <?= e(setting('contact_address', '-')) ?></p>
        <p class="small mb-1"><i class="bi bi-telephone"></i> <?= e(setting('contact_phone', '-')) ?></p>
        <p class="small mb-1"><i class="bi bi-envelope"></i> <?= e(setting('contact_email', '-')) ?></p>
        <p class="small mb-0"><i class="bi bi-line"></i> <?= e(setting('contact_line', '-')) ?></p>
      </div>
    </div>
    <hr>
    <div class="small text-center opacity-75">&copy; <?= date('Y') ?> <?= e(setting('footer_text', 'GreenLoop')) ?></div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/app.js')) ?>?v=1"></script>
</body>
</html>
