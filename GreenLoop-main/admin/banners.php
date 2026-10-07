<?php
require __DIR__ . '/../includes/init.php';
require_admin();
$positions = ['top' => 'ด้านบน (Top Banner)', 'middle' => 'กลางหน้า (Middle)', 'side' => 'ด้านข้าง (Side Banner)'];

if (is_post()) {
    $id = (int) post('id');
    $b = $id ? row('SELECT * FROM banners WHERE id = ?', [$id]) : null;
    if (post('action') === 'delete' && $b) {
        q('DELETE FROM banners WHERE id = ?', [$id]);
        if ($b['image'] && is_file(ROOT_PATH . '/' . $b['image'])) {
            unlink(ROOT_PATH . '/' . $b['image']);
        }
        flash('ลบแบนเนอร์แล้ว');
    } elseif (post('action') === 'toggle' && $b) {
        q('UPDATE banners SET active = 1 - active WHERE id = ?', [$id]);
        flash($b['active'] ? 'ปิดการแสดงแบนเนอร์แล้ว' : 'เปิดการแสดงแบนเนอร์แล้ว');
    } elseif (post('action') === 'save') {
        $image = upload_image('image', 'banners');
        $link = post('link');
        if (post('title') === '') {
            flash('กรุณากรอกชื่อแบนเนอร์', 'danger');
        } elseif ($link !== '' && !preg_match('#^(https?://|[a-z0-9_\-/]+\.php)#i', $link)) {
            flash('ลิงก์ต้องขึ้นต้นด้วย http(s):// หรือเป็นหน้าในเว็บไซต์ เช่น products.php?type=market', 'danger');
        } elseif ($image !== false) {
            $data = [mb_substr(post('title'), 0, 150), mb_substr(post('subtitle'), 0, 255), mb_substr($link, 0, 255), isset($positions[post('position')]) ? post('position') : 'top', (int) post('sort_order')];
            if ($b) {
                q('UPDATE banners SET title = ?, subtitle = ?, link = ?, position = ?, sort_order = ?, image = IF(?, NULL, COALESCE(?, image)) WHERE id = ?', array_merge($data, [post('remove_image') ? 1 : 0, $image, $id]));
            } else {
                q('INSERT INTO banners (title, subtitle, link, position, sort_order, image) VALUES (?,?,?,?,?,?)', array_merge($data, [$image]));
            }
            flash('บันทึกแบนเนอร์แล้ว');
        }
    }
    redirect('admin/banners.php');
}

$edit = (int) get('edit') ? row('SELECT * FROM banners WHERE id = ?', [(int) get('edit')]) : null;
$f = $edit ?: ['id' => 0, 'title' => '', 'subtitle' => '', 'link' => '', 'position' => 'top', 'sort_order' => 0, 'image' => null];
$list = rows("SELECT * FROM banners ORDER BY FIELD(position, 'top', 'middle', 'side'), sort_order, id");

$title = 'จัดการแบนเนอร์';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-xl-8">
    <div class="panel p-0 overflow-hidden">
    <?php if ($list): ?>
    <div class="table-responsive"><table class="table mb-0">
      <thead><tr><th class="ps-3">แบนเนอร์</th><th>ตำแหน่ง</th><th>ลำดับ</th><th>สถานะ</th><th class="text-end pe-3">จัดการ</th></tr></thead>
      <tbody>
      <?php foreach ($list as $b): ?>
        <tr>
          <td class="ps-3"><div class="d-flex gap-2 align-items-center">
            <?php if ($b['image']): ?><img src="<?= e(url($b['image'])) ?>" alt="" style="width:96px;height:48px;object-fit:cover;border-radius:.5rem"><?php else: ?><div class="ph" style="width:96px;height:48px;border-radius:.5rem;font-size:1.2rem"><i class="bi bi-image"></i></div><?php endif; ?>
            <div><div class="fw-medium"><?= e($b['title']) ?></div><div class="small text-muted"><?= e($b['link'] ?: 'ไม่มีลิงก์') ?></div></div></div></td>
          <td class="small"><?= $positions[$b['position']] ?></td>
          <td><?= (int) $b['sort_order'] ?></td>
          <td><?= $b['active'] ? '<span class="badge rounded-pill text-bg-success">แสดง</span>' : '<span class="badge rounded-pill text-bg-secondary">ซ่อน</span>' ?></td>
          <td class="text-end pe-3 text-nowrap">
            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary" name="action" value="toggle" title="<?= $b['active'] ? 'ซ่อน' : 'แสดง' ?>"><i class="bi bi-eye<?= $b['active'] ? '-slash' : '' ?>"></i></button>
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/banners.php?edit=' . $b['id'])) ?>" title="แก้ไข"><i class="bi bi-pencil"></i></a>
              <button class="btn btn-sm btn-outline-danger" name="action" value="delete" title="ลบ" data-confirm="ลบแบนเนอร์นี้?"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php else: ?><div class="empty"><i class="bi bi-image"></i>ยังไม่มีแบนเนอร์</div><?php endif; ?>
    </div>
    <div class="panel mt-3 small">
      <div class="panel-title"><i class="bi bi-layout-text-window"></i> ตำแหน่ง Banner บนเว็บไซต์</div>
      <b>1. Top Banner</b> — ด้านบนของหน้าแรก (สไลด์ได้หลายภาพ แนะนำ 1600×420px)<br>
      <b>2. Middle</b> — กลางหน้าแรก ระหว่าง Market และ Digital Market (แนะนำ 1200×260px)<br>
      <b>3. Side Banner</b> — ด้านข้างหน้ารายการสินค้าและหน้ารายละเอียดสินค้า (แนะนำ 300×400px)
    </div>
  </div>
  <div class="col-xl-4">
    <form class="panel" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
      <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
      <div class="panel-title"><i class="bi bi-<?= $edit ? 'pencil' : 'plus-circle' ?>"></i> <?= $edit ? 'แก้ไขแบนเนอร์' : 'เพิ่มแบนเนอร์' ?></div>
      <div class="mb-3"><label class="form-label" for="title">ชื่อ / ข้อความหลัก</label><input id="title" class="form-control" name="title" value="<?= e($f['title']) ?>" required maxlength="150"></div>
      <div class="mb-3"><label class="form-label" for="subtitle">ข้อความรอง</label><input id="subtitle" class="form-control" name="subtitle" value="<?= e($f['subtitle']) ?>" maxlength="255"></div>
      <div class="mb-3"><label class="form-label" for="link">ลิงก์เมื่อคลิก</label><input id="link" class="form-control" name="link" value="<?= e($f['link']) ?>" maxlength="255" placeholder="products.php?type=market หรือ https://..."></div>
      <div class="row g-3 mb-3">
        <div class="col-8"><label class="form-label" for="position">ตำแหน่ง</label><select id="position" class="form-select" name="position"><?php foreach ($positions as $k => $v): ?><option value="<?= $k ?>" <?= $f['position'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="col-4"><label class="form-label" for="sort_order">ลำดับ</label><input id="sort_order" class="form-control" type="number" name="sort_order" value="<?= (int) $f['sort_order'] ?>"></div>
      </div>
      <div class="mb-3"><label class="form-label" for="image">รูปแบนเนอร์</label><input id="image" class="form-control" type="file" name="image" accept="image/*" data-preview="#banner-preview">
        <img id="banner-preview" class="mt-2 rounded w-100 <?= $f['image'] ? '' : 'd-none' ?>" src="<?= $f['image'] ? e(url($f['image'])) : '' ?>" alt="ตัวอย่างแบนเนอร์">
        <div class="form-text">หากไม่อัปโหลดรูป ระบบจะแสดงแบนเนอร์แบบข้อความบนพื้นสีเขียว</div>
        <?php if ($f['image']): ?><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image"><label class="form-check-label small" for="remove_image">ลบรูปและใช้แบบข้อความ</label></div><?php endif; ?></div>
      <button class="btn btn-primary w-100">บันทึก</button>
      <?php if ($edit): ?><a class="btn btn-light w-100 mt-2" href="<?= e(url('admin/banners.php')) ?>">ยกเลิก</a><?php endif; ?>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
