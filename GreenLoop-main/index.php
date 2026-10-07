<?php
require __DIR__ . '/includes/init.php';

$live = " WHERE p.status = 'approved' AND (p.type = 'digital' OR p.stock > 0)";
$featured = rows(PRODUCT_SELECT . $live . ' AND p.promoted_until > NOW() ORDER BY p.promoted_until DESC LIMIT 4');
$market = rows(PRODUCT_SELECT . $live . " AND p.type = 'market' ORDER BY p.created_at DESC, p.id DESC LIMIT 8");
$digital = rows(PRODUCT_SELECT . $live . " AND p.type = 'digital' ORDER BY p.created_at DESC, p.id DESC LIMIT 4");
$top = banners('top');
$middle = banners('middle');
$cats = rows('SELECT * FROM categories ORDER BY type DESC, id');

$mainClass = 'pb-4';
require __DIR__ . '/includes/header.php';
?>
<?php if ($top): ?>
<div id="topBanner" class="carousel slide" data-bs-ride="carousel">
  <?php if (count($top) > 1): ?>
  <div class="carousel-indicators">
    <?php foreach ($top as $i => $b): ?><button type="button" data-bs-target="#topBanner" data-bs-slide-to="<?= $i ?>" class="<?= $i ? '' : 'active' ?>" aria-label="แบนเนอร์ <?= $i + 1 ?>"></button><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="carousel-inner">
    <?php foreach ($top as $i => $b): ?>
    <div class="carousel-item <?= $i ? '' : 'active' ?>">
      <?php if ($b['image']): ?>
        <?= banner_html($b) ?>
      <?php else: ?>
      <div class="banner banner-top">
        <div class="container"><div class="banner-text">
          <span class="banner-label">Powered by GreenDigital</span>
          <h2><?= e($b['title']) ?></h2>
          <p><?= e($b['subtitle']) ?></p>
          <?php if ($b['link'] !== ''): ?><a class="btn btn-light mt-4 px-4" href="<?= e(preg_match('#^https?://#i', $b['link']) ? $b['link'] : url($b['link'])) ?>">ดูรายละเอียด <i class="bi bi-arrow-right"></i></a><?php endif; ?>
        </div></div>
        <i class="bi bi-recycle banner-deco"></i>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="container">
  <div class="concept" <?= $top ? '' : 'style="margin-top:1.5rem"' ?>>
    <div class="row align-items-center g-3">
      <div class="col-lg-8">
        <h2 class="h5 text-green mb-2">แนวคิด (Concept)</h2>
        <p class="mb-0">GreenLoop คือแพลตฟอร์มซื้อขาย-ส่งต่ออุปกรณ์ดิจิทัลมือสอง สินค้าดิจิทัล และระบบรีไซเคิลอุปกรณ์อิเล็กทรอนิกส์ (E-Waste) พร้อมระบบ Green Point เพื่อสร้างแรงจูงใจให้ผู้ใช้มีส่วนร่วมในการลดขยะอิเล็กทรอนิกส์ และใช้เทคโนโลยีอย่างคุ้มค่า</p>
      </div>
      <div class="col-lg-4 text-lg-end">
        <a class="btn btn-primary px-4" href="<?= e(url(user() ? 'sell.php' : 'register.php')) ?>"><?= user() ? 'ลงขายสินค้า' : 'เริ่มต้นใช้งานฟรี' ?></a>
        <a class="btn btn-outline-primary px-4" href="<?= e(url('recycle.php')) ?>">แจ้งรีไซเคิล</a>
      </div>
    </div>
  </div>

  <section class="section">
    <div class="section-head"><h2 class="pill-title">3 แกนหลักของระบบ</h2></div>
    <div class="row g-3">
      <div class="col-md-4"><a class="pillar" href="<?= e(url('products.php?type=market')) ?>">
        <div class="ico"><i class="bi bi-cart3"></i></div>
        <h3>1. MARKET <small class="text-muted fw-normal">(สินค้ามือสอง)</small></h3>
        <p>ซื้อขายอุปกรณ์ไอทีมือสอง เช่น โทรศัพท์, Notebook, Tablet, กล้อง, อุปกรณ์ IT ต่างๆ</p>
      </a></div>
      <div class="col-md-4"><a class="pillar" href="<?= e(url('products.php?type=digital')) ?>">
        <div class="ico"><i class="bi bi-display"></i></div>
        <h3>2. DIGITAL MARKET <small class="text-muted fw-normal">(สินค้าดิจิทัล)</small></h3>
        <p>ซื้อขายสินค้าดิจิทัล เช่น Template, Graphic, E-Book, Digital Art, Font, Icon, Software และอื่นๆ</p>
      </a></div>
      <div class="col-md-4"><a class="pillar" href="<?= e(url('recycle.php')) ?>">
        <div class="ico"><i class="bi bi-recycle"></i></div>
        <h3>3. RECYCLE <small class="text-muted fw-normal">(E-Waste)</small></h3>
        <p>แจ้งความประสงค์รีไซเคิลอุปกรณ์อิเล็กทรอนิกส์ที่ไม่ใช้แล้ว เพื่อเข้าสู่กระบวนการรีไซเคิล</p>
      </a></div>
    </div>
  </section>

  <?php if ($featured): ?>
  <section class="section">
    <div class="section-head"><h2 class="pill-title">สินค้าแนะนำ</h2></div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
      <?php foreach ($featured as $p): ?><div class="col"><?= product_card($p) ?></div><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="section">
    <div class="section-head">
      <h2 class="pill-title">Marketplace — สินค้ามือสองล่าสุด</h2>
      <a href="<?= e(url('products.php?type=market')) ?>">ดูทั้งหมด <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php foreach ($cats as $c): if ($c['type'] !== 'market') continue; ?>
        <a class="chip" href="<?= e(url('products.php?type=market&cat=' . $c['id'])) ?>"><i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($market): ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
      <?php foreach ($market as $p): ?><div class="col"><?= product_card($p) ?></div><?php endforeach; ?>
    </div>
    <?php else: ?><div class="empty panel"><i class="bi bi-box-seam"></i>ยังไม่มีสินค้า</div><?php endif; ?>
  </section>

  <?php if ($middle): ?>
  <section class="section"><?= banner_html($middle[0]) ?></section>
  <?php endif; ?>

  <section class="section">
    <div class="section-head">
      <h2 class="pill-title">Digital Market — สินค้าดิจิทัล</h2>
      <a href="<?= e(url('products.php?type=digital')) ?>">ดูทั้งหมด <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php foreach ($cats as $c): if ($c['type'] !== 'digital') continue; ?>
        <a class="chip" href="<?= e(url('products.php?type=digital&cat=' . $c['id'])) ?>"><i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($digital): ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
      <?php foreach ($digital as $p): ?><div class="col"><?= product_card($p) ?></div><?php endforeach; ?>
    </div>
    <?php else: ?><div class="empty panel"><i class="bi bi-file-earmark"></i>ยังไม่มีสินค้า</div><?php endif; ?>
  </section>

  <section class="section">
    <div class="section-head">
      <h2 class="pill-title">Green Point System</h2>
      <span class="text-muted">ผู้ใช้ได้รับคะแนนจากกิจกรรมต่างๆ เพื่อแลกสิทธิพิเศษและส่วนลดในระบบ</span>
    </div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3">
      <?php
      $earn = [
          ['bi-cart-check', 'ขายสินค้ามือสอง', POINTS['sell_market']],
          ['bi-bag', 'ซื้อสินค้ามือสอง', POINTS['buy_market']],
          ['bi-recycle', 'ส่งคำขอรีไซเคิล', POINTS['recycle_submit']],
          ['bi-patch-check', 'รีไซเคิลสำเร็จ', POINTS['recycle_done']],
          ['bi-display', 'ขาย Digital Product', POINTS['sell_digital']],
          ['bi-download', 'ซื้อ Digital Product', POINTS['buy_digital']],
      ];
      foreach ($earn as $x): ?>
      <div class="col"><div class="point-tile"><i class="bi <?= $x[0] ?>"></i><div class="lbl"><?= $x[1] ?></div><div class="num">+<?= $x[2] ?></div><div class="unit">POINT</div></div></div>
      <?php endforeach; ?>
    </div>
    <div class="panel mt-3">
      <div class="panel-title"><i class="bi bi-gift"></i> ใช้คะแนน Green Point ทำอะไรได้บ้าง?</div>
      <div class="row g-3">
        <div class="col-md-6 col-lg"><div class="use-item"><div class="ico"><i class="bi bi-bag-check"></i></div><div><b>ส่วนลดค่าธรรมเนียม</b><span>ลดค่าธรรมเนียมการขายสินค้าตามจำนวนคะแนน</span></div></div></div>
        <div class="col-md-6 col-lg"><div class="use-item"><div class="ico"><i class="bi bi-percent"></i></div><div><b>แลกส่วนลดซื้อสินค้า</b><span>ใช้คะแนนแลกรับส่วนลดเมื่อซื้อสินค้า</span></div></div></div>
        <div class="col-md-6 col-lg"><div class="use-item"><div class="ico"><i class="bi bi-star"></i></div><div><b>แลกรับคูปองพิเศษ</b><span>แลกรับคูปองส่วนลดหรือโปรโมชั่นพิเศษ</span></div></div></div>
        <div class="col-md-6 col-lg"><div class="use-item"><div class="ico"><i class="bi bi-tree"></i></div><div><b>แลกของรางวัล</b><span>แลกของรางวัลที่เกี่ยวกับเทคโนโลยีหรือสิ่งแวดล้อม</span></div></div></div>
        <div class="col-md-6 col-lg"><div class="use-item"><div class="ico"><i class="bi bi-gem"></i></div><div><b>อัปเกรดสมาชิก</b><span>ปลดล็อกการเป็นสมาชิกระดับพิเศษ</span></div></div></div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="panel h-100">
          <div class="panel-title"><i class="bi bi-signpost-split"></i> กระบวนการทำงาน (User Flow)</div>
          <div class="flow">
            <div class="flow-step"><span class="ico"><i class="bi bi-person-plus"></i></span><div>สมัครสมาชิก / เข้าสู่ระบบ</div></div>
            <div class="flow-step"><span class="ico"><i class="bi bi-bag"></i></span><div>เลือกซื้อ / ลงขายสินค้า</div></div>
            <div class="flow-step"><span class="ico"><i class="bi bi-wallet2"></i></span><div>ชำระเงินด้วย Wallet</div></div>
            <div class="flow-step"><span class="ico"><i class="bi bi-star"></i></span><div>สะสม Green Point จากกิจกรรม</div></div>
            <div class="flow-step"><span class="ico"><i class="bi bi-gift"></i></span><div>ใช้คะแนนแลกสิทธิพิเศษหรือส่วนลด</div></div>
            <div class="flow-step"><span class="ico"><i class="bi bi-globe-asia-australia"></i></span><div>ส่งต่อเทคโนโลยี เพื่อโลกที่ยั่งยืน</div></div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="panel h-100">
          <div class="panel-title"><i class="bi bi-heart"></i> ประโยชน์ที่ผู้ใช้ได้รับ</div>
          <?php foreach (['ลดขยะอิเล็กทรอนิกส์', 'ยืดอายุการใช้งานอุปกรณ์', 'ประหยัดค่าใช้จ่าย', 'สะสม Green Point เพื่อรับสิทธิพิเศษ', 'มีส่วนร่วมในการรักษาสิ่งแวดล้อม'] as $b): ?>
            <div class="benefit"><i class="bi bi-check-circle-fill"></i> <?= $b ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="section-head"><h2 class="pill-title">ค่าบริการและสิทธิพิเศษสำหรับผู้ขาย</h2></div>
    <div class="row g-3">
      <div class="col-md-6 col-lg-3"><div class="revenue-card"><div class="big"><?= e(setting('commission_rate', 5)) ?>%</div><b>ค่าธรรมเนียมการขาย</b><div class="small text-muted">เก็บจากยอดขายเมื่อมีการซื้อขายสำเร็จ ทั้งสินค้ามือสองและดิจิทัล</div></div></div>
      <div class="col-md-6 col-lg-3"><div class="revenue-card"><div class="big"><i class="bi bi-megaphone"></i></div><b>โปรโมทสินค้า</b><div class="small text-muted">ให้สินค้าแสดงอันดับต้นๆ <?= PROMOTE_DAYS ?> วัน เพียง <?= baht(setting('promote_price', 29)) ?></div></div></div>
      <div class="col-md-6 col-lg-3"><div class="revenue-card"><div class="big"><i class="bi bi-gem"></i></div><b>สมาชิก Premium</b><div class="small text-muted">ลดค่าธรรมเนียมเหลือ <?= e(setting('premium_commission_rate', 3)) ?>% และสิทธิประโยชน์เพิ่มเติม <a href="<?= e(url('premium.php')) ?>">ดูรายละเอียด</a></div></div></div>
      <div class="col-md-6 col-lg-3"><div class="revenue-card"><div class="big"><i class="bi bi-badge-ad"></i></div><b>แบนเนอร์โฆษณา</b><div class="small text-muted">ลงโฆษณาบนหน้าแรกและหน้าหมวดหมู่ ติดต่อทีมงานได้ทางแชท</div></div></div>
    </div>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
