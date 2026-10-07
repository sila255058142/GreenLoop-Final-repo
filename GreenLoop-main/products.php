<?php
require __DIR__ . '/includes/init.php';

$type = in_array(get('type'), ['market', 'digital'], true) ? get('type') : '';
$kw = get('q');
$cat = (int) get('cat');
$min = get('min');
$max = get('max');
$cond = isset(CONDITIONS[get('cond')]) ? get('cond') : '';
$sorts = [
    'new' => ['ใหม่ล่าสุด', 'p.created_at DESC, p.id DESC'],
    'price_asc' => ['ราคาต่ำ - สูง', 'p.price ASC'],
    'price_desc' => ['ราคาสูง - ต่ำ', 'p.price DESC'],
    'score' => ['Green Score สูงสุด', 'p.green_score DESC'],
    'popular' => ['ยอดเข้าชมสูงสุด', 'p.views DESC'],
];
$sort = isset($sorts[get('sort')]) ? get('sort') : 'new';
$page = max(1, (int) get('page'));
$per = 12;

$where = " WHERE p.status = 'approved' AND (p.type = 'digital' OR p.stock > 0)";
$args = [];
if ($type) {
    $where .= ' AND p.type = ?';
    $args[] = $type;
}
if ($kw !== '') {
    $where .= ' AND (p.title LIKE ? OR p.description LIKE ?)';
    $like = '%' . addcslashes($kw, '%_\\') . '%';
    $args[] = $like;
    $args[] = $like;
}
if ($cat) {
    $where .= ' AND p.category_id = ?';
    $args[] = $cat;
}
if (is_numeric($min)) {
    $where .= ' AND p.price >= ?';
    $args[] = (float) $min;
}
if (is_numeric($max)) {
    $where .= ' AND p.price <= ?';
    $args[] = (float) $max;
}
if ($cond) {
    $where .= ' AND p.item_condition = ?';
    $args[] = $cond;
}

$total = (int) val('SELECT COUNT(*) FROM products p' . $where, $args);
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$list = rows(PRODUCT_SELECT . $where . ' ORDER BY (p.promoted_until > NOW()) DESC, ' . $sorts[$sort][1] . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $args);
$cats = $type ? rows('SELECT * FROM categories WHERE type = ? ORDER BY id', [$type]) : rows('SELECT * FROM categories ORDER BY type DESC, id');
$side = banners('side');

function page_link($n)
{
    return e(url('products.php?' . http_build_query(array_merge($_GET, ['page' => $n]))));
}

$title = $type === 'market' ? 'Market — สินค้ามือสอง' : ($type === 'digital' ? 'Digital Market — สินค้าดิจิทัล' : 'สินค้าทั้งหมด');
require __DIR__ . '/includes/header.php';
?>
<div class="section-head">
  <div><h1 class="pill-title"><?= e($title) ?></h1>
    <span class="text-muted ms-2">พบ <?= number_format($total) ?> รายการ<?= $kw !== '' ? ' สำหรับ "' . e($kw) . '"' : '' ?></span></div>
  <ul class="nav nav-pills">
    <li class="nav-item"><a class="nav-link <?= $type === '' ? 'active' : '' ?>" href="<?= e(url('products.php' . ($kw !== '' ? '?q=' . urlencode($kw) : ''))) ?>">ทั้งหมด</a></li>
    <li class="nav-item"><a class="nav-link <?= $type === 'market' ? 'active' : '' ?>" href="<?= e(url('products.php?type=market' . ($kw !== '' ? '&q=' . urlencode($kw) : ''))) ?>">มือสอง</a></li>
    <li class="nav-item"><a class="nav-link <?= $type === 'digital' ? 'active' : '' ?>" href="<?= e(url('products.php?type=digital' . ($kw !== '' ? '&q=' . urlencode($kw) : ''))) ?>">ดิจิทัล</a></li>
  </ul>
</div>
<div class="row g-4">
  <aside class="col-lg-3">
    <form class="panel filter-box" method="get">
      <div class="panel-title"><i class="bi bi-funnel"></i> ค้นหา & กรองสินค้า</div>
      <?php if ($type): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
      <div class="mb-3"><label class="form-label" for="f-q">คำค้นหา</label><input id="f-q" class="form-control" name="q" value="<?= e($kw) ?>" placeholder="ชื่อสินค้า..."></div>
      <div class="mb-3"><label class="form-label" for="f-cat">หมวดหมู่</label>
        <select id="f-cat" class="form-select" name="cat"><option value="">ทุกหมวดหมู่</option>
          <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?><?= $type ? '' : ($c['type'] === 'digital' ? ' (ดิจิทัล)' : ' (มือสอง)') ?></option><?php endforeach; ?>
        </select></div>
      <div class="mb-3"><label class="form-label">ช่วงราคา (บาท)</label>
        <div class="input-group"><input class="form-control" type="number" min="0" name="min" value="<?= e($min) ?>" placeholder="ต่ำสุด" aria-label="ราคาต่ำสุด"><input class="form-control" type="number" min="0" name="max" value="<?= e($max) ?>" placeholder="สูงสุด" aria-label="ราคาสูงสุด"></div></div>
      <?php if ($type !== 'digital'): ?>
      <div class="mb-3"><label class="form-label" for="f-cond">สภาพสินค้า</label>
        <select id="f-cond" class="form-select" name="cond"><option value="">ทุกสภาพ</option>
          <?php foreach (CONDITIONS as $k => $c): ?><option value="<?= $k ?>" <?= $cond === $k ? 'selected' : '' ?>><?= $c[0] ?></option><?php endforeach; ?>
        </select></div>
      <?php endif; ?>
      <div class="mb-3"><label class="form-label" for="f-sort">เรียงลำดับ</label>
        <select id="f-sort" class="form-select" name="sort">
          <?php foreach ($sorts as $k => $s): ?><option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $s[0] ?></option><?php endforeach; ?>
        </select></div>
      <button class="btn btn-primary w-100"><i class="bi bi-search"></i> ค้นหา</button>
      <a class="btn btn-link w-100 btn-sm mt-1" href="<?= e(url('products.php' . ($type ? '?type=' . $type : ''))) ?>">ล้างตัวกรอง</a>
    </form>
    <?php foreach ($side as $b): ?><div class="mt-3"><?= banner_html($b) ?></div><?php endforeach; ?>
  </aside>
  <div class="col-lg-9">
    <?php if ($list): ?>
    <div class="row row-cols-2 row-cols-md-3 g-3">
      <?php foreach ($list as $p): ?><div class="col"><?= product_card($p) ?></div><?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?>
    <nav class="mt-4" aria-label="หน้า"><ul class="pagination justify-content-center">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= page_link($page - 1) ?>">ก่อนหน้า</a></li>
      <?php for ($i = 1; $i <= $pages; $i++): ?><li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= page_link($i) ?>"><?= $i ?></a></li><?php endfor; ?>
      <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= page_link($page + 1) ?>">ถัดไป</a></li>
    </ul></nav>
    <?php endif; ?>
    <?php else: ?>
    <div class="panel empty"><i class="bi bi-search"></i>ไม่พบสินค้าที่ตรงกับเงื่อนไข</div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
