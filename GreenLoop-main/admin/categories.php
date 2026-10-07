<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if (is_post()) {
    $id = (int) post('id');
    if (post('action') === 'delete') {
        q('DELETE FROM categories WHERE id = ?', [$id]);
        flash('ลบหมวดหมู่แล้ว');
    } else {
        $name = post('name');
        $type = post('type') === 'digital' ? 'digital' : 'market';
        $icon = preg_match('/^bi-[a-z0-9-]+$/', post('icon')) ? post('icon') : 'bi-box-seam';
        if ($name === '' || mb_strlen($name) > 100) {
            flash('กรุณากรอกชื่อหมวดหมู่', 'danger');
        } elseif ($id) {
            q('UPDATE categories SET name = ?, icon = ? WHERE id = ?', [$name, $icon, $id]);
            flash('บันทึกหมวดหมู่แล้ว');
        } else {
            q('INSERT INTO categories (name, type, icon) VALUES (?,?,?)', [$name, $type, $icon]);
            flash('เพิ่มหมวดหมู่แล้ว');
        }
    }
    redirect('admin/categories.php');
}

$list = rows('SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS products FROM categories c ORDER BY c.type DESC, c.id');
$title = 'หมวดหมู่สินค้า';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-xl-8">
    <div class="panel p-0 overflow-hidden"><div class="table-responsive"><table class="table mb-0">
      <thead><tr><th class="ps-3">ประเภท</th><th>ชื่อหมวดหมู่</th><th>ไอคอน (Bootstrap Icons)</th><th>สินค้า</th><th class="text-end pe-3">จัดการ</th></tr></thead>
      <tbody>
      <?php foreach ($list as $c): ?>
        <tr>
          <td class="ps-3 small"><?= $c['type'] === 'digital' ? 'ดิจิทัล' : 'มือสอง' ?></td>
          <td><input form="c<?= $c['id'] ?>" class="form-control form-control-sm" name="name" value="<?= e($c['name']) ?>" required maxlength="100" aria-label="ชื่อหมวดหมู่"></td>
          <td><div class="input-group input-group-sm"><span class="input-group-text"><i class="bi <?= e($c['icon']) ?>"></i></span><input form="c<?= $c['id'] ?>" class="form-control" name="icon" value="<?= e($c['icon']) ?>" aria-label="ไอคอน"></div></td>
          <td><?= (int) $c['products'] ?></td>
          <td class="text-end pe-3 text-nowrap"><form id="c<?= $c['id'] ?>" method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button class="btn btn-sm btn-outline-primary" name="action" value="save">บันทึก</button>
            <button class="btn btn-sm btn-outline-danger" name="action" value="delete" formnovalidate data-confirm="ลบหมวดหมู่นี้? สินค้าในหมวดจะไม่มีหมวดหมู่"><i class="bi bi-trash"></i></button></form></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div></div>
  </div>
  <div class="col-xl-4">
    <form class="panel" method="post"><?= csrf_field() ?>
      <div class="panel-title"><i class="bi bi-plus-circle"></i> เพิ่มหมวดหมู่</div>
      <div class="mb-3"><label class="form-label" for="type">ประเภท</label><select id="type" class="form-select" name="type"><option value="market">สินค้ามือสอง</option><option value="digital">สินค้าดิจิทัล</option></select></div>
      <div class="mb-3"><label class="form-label" for="name">ชื่อหมวดหมู่</label><input id="name" class="form-control" name="name" required maxlength="100"></div>
      <div class="mb-3"><label class="form-label" for="icon">ไอคอน</label><input id="icon" class="form-control" name="icon" value="bi-box-seam" placeholder="เช่น bi-phone"></div>
      <button class="btn btn-primary w-100" name="action" value="save">เพิ่มหมวดหมู่</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
