<?php
require __DIR__ . '/includes/init.php';
$u = require_login();
$isAdmin = $u['role'] === 'admin';

$id = (int) get('id');
$p = $id ? row('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && (!$p || ($p['user_id'] != $u['id'] && !$isAdmin))) {
    flash('ไม่พบสินค้า หรือคุณไม่มีสิทธิ์แก้ไข', 'danger');
    redirect('my-products.php');
}
$done = $isAdmin && get('from') === 'admin' ? 'admin/products.php' : 'my-products.php';
$form = $p ?: ['type' => in_array(get('type'), ['market', 'digital'], true) ? get('type') : 'market', 'title' => '', 'category_id' => 0, 'price' => '', 'item_condition' => 'good', 'stock' => 1, 'description' => '', 'image' => null, 'file_name' => null];

if (is_post()) {
    $type = $p ? $p['type'] : (post('type') === 'digital' ? 'digital' : 'market');
    $form = array_merge($form, [
        'type' => $type,
        'title' => post('title'),
        'category_id' => (int) post('category_id'),
        'price' => post('price'),
        'item_condition' => $type === 'market' && isset(CONDITIONS[post('item_condition')]) ? post('item_condition') : '',
        'stock' => $type === 'market' ? max(0, min(999, (int) post('stock', '1'))) : 999,
        'description' => post('description'),
    ]);
    $err = '';
    if (mb_strlen($form['title']) < 3 || mb_strlen($form['title']) > 200) {
        $err = 'กรุณากรอกชื่อสินค้า 3-200 ตัวอักษร';
    } elseif (!is_numeric($form['price']) || $form['price'] <= 0 || $form['price'] > 9999999) {
        $err = 'ราคาสินค้าไม่ถูกต้อง';
    } elseif (!val('SELECT 1 FROM categories WHERE id = ? AND type = ?', [$form['category_id'], $type])) {
        $err = 'กรุณาเลือกหมวดหมู่สินค้า';
    } elseif ($type === 'market' && $form['item_condition'] === '') {
        $err = 'กรุณาเลือกสภาพสินค้า';
    }
    $image = $err ? null : upload_image('image', 'products');
    $file = $err || $image === false || $type !== 'digital' ? null : upload_file('file', 'files');
    if (!$err && $image !== false && $file !== false && $type === 'digital' && !$p && !$file) {
        $err = 'กรุณาแนบไฟล์สินค้าดิจิทัล';
    }
    if ($err) {
        flash($err, 'danger');
    } elseif ($image !== false && $file !== false) {
        $score = $type === 'market' ? CONDITIONS[$form['item_condition']][1] : 80;
        $fileName = $file ? mb_substr(basename($_FILES['file']['name']), 0, 200) : null;
        if ($p) {
            $status = $p['status'] === 'rejected' && !$isAdmin ? 'pending' : $p['status'];
            if ($type === 'market' && in_array($status, ['approved', 'sold'], true)) {
                $status = $form['stock'] > 0 ? 'approved' : 'sold';
            }
            q('UPDATE products SET title = ?, category_id = ?, price = ?, item_condition = ?, stock = ?, description = ?, green_score = ?, status = ?,
                image = COALESCE(?, image), file_path = COALESCE(?, file_path), file_name = COALESCE(?, file_name) WHERE id = ?',
                [$form['title'], $form['category_id'], $form['price'], $form['item_condition'], $form['stock'], $form['description'], $score, $status, $image, $file, $fileName, $p['id']]);
            flash('บันทึกการแก้ไขสินค้าแล้ว');
        } else {
            q('INSERT INTO products (user_id, category_id, type, title, description, price, item_condition, stock, image, file_path, file_name, green_score, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$u['id'], $form['category_id'], $type, $form['title'], $form['description'], $form['price'], $form['item_condition'], max(1, $form['stock']), $image, $file, $fileName, $score, $isAdmin ? 'approved' : 'pending']);
            flash($isAdmin ? 'เพิ่มสินค้าแล้ว' : 'ลงขายสินค้าแล้ว รอผู้ดูแลระบบตรวจสอบและอนุมัติ');
        }
        redirect($done);
    }
}

$cats = rows('SELECT * FROM categories ORDER BY id');
$types = $p ? [$p['type']] : ['market', 'digital'];
$title = $p ? 'แก้ไขสินค้า' : 'ลงขายสินค้า';
require __DIR__ . '/includes/header.php';
echo page_header($title, $p ? '' : 'ลงขายอุปกรณ์มือสองหรือสินค้าดิจิทัล เมื่อขายสำเร็จรับ Green Point ทันที');
?>
<form class="panel" method="post" enctype="multipart/form-data" style="max-width: 820px">
  <?= csrf_field() ?>
  <?php if (!$p): ?>
  <div class="mb-3">
    <label class="form-label d-block">ประเภทสินค้า</label>
    <div class="btn-group" role="group">
      <input type="radio" class="btn-check" name="type" id="t-market" value="market" data-type-toggle <?= $form['type'] === 'market' ? 'checked' : '' ?>>
      <label class="btn btn-outline-primary" for="t-market"><i class="bi bi-cart3"></i> สินค้ามือสอง (+<?= POINTS['sell_market'] ?> Point)</label>
      <input type="radio" class="btn-check" name="type" id="t-digital" value="digital" data-type-toggle <?= $form['type'] === 'digital' ? 'checked' : '' ?>>
      <label class="btn btn-outline-primary" for="t-digital"><i class="bi bi-display"></i> สินค้าดิจิทัล (+<?= POINTS['sell_digital'] ?> Point)</label>
    </div>
  </div>
  <?php endif; ?>
  <div class="row g-3">
    <div class="col-12"><label class="form-label" for="title">ชื่อสินค้า</label><input id="title" class="form-control" name="title" value="<?= e($form['title']) ?>" required maxlength="200"></div>
    <?php foreach ($types as $t): ?>
    <div class="col-md-6" data-for-type="<?= $t ?>"><label class="form-label" for="cat-<?= $t ?>">หมวดหมู่</label>
      <select id="cat-<?= $t ?>" class="form-select" name="category_id" required><option value="">— เลือกหมวดหมู่ —</option>
        <?php foreach ($cats as $c): if ($c['type'] !== $t) continue; ?><option value="<?= $c['id'] ?>" <?= $form['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
    <?php endforeach; ?>
    <div class="col-md-6"><label class="form-label" for="price">ราคา (บาท)</label><input id="price" class="form-control" type="number" name="price" min="1" max="9999999" step="0.01" value="<?= e($form['price']) ?>" required></div>
    <?php if (in_array('market', $types, true)): ?>
    <div class="col-md-6" data-for-type="market"><label class="form-label" for="cond">สภาพสินค้า</label>
      <select id="cond" class="form-select" name="item_condition">
        <?php foreach (CONDITIONS as $k => $c): ?><option value="<?= $k ?>" <?= $form['item_condition'] === $k ? 'selected' : '' ?>><?= $c[0] ?> (Green Score <?= $c[1] ?>)</option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6" data-for-type="market"><label class="form-label" for="stock">จำนวน (ชิ้น)</label><input id="stock" class="form-control" type="number" name="stock" min="<?= $p ? 0 : 1 ?>" max="999" value="<?= (int) $form['stock'] ?>"></div>
    <?php endif; ?>
    <div class="col-12"><label class="form-label" for="description">รายละเอียดสินค้า</label><textarea id="description" class="form-control" name="description" rows="5" maxlength="5000"><?= e($form['description']) ?></textarea></div>
    <div class="col-md-6"><label class="form-label" for="image">รูปสินค้า<?= $p ? ' (เว้นว่างหากไม่เปลี่ยน)' : '' ?></label>
      <input id="image" class="form-control" type="file" name="image" accept="image/*" data-preview="#preview">
      <img id="preview" class="mt-2 rounded <?= $form['image'] ? '' : 'd-none' ?>" style="max-height:140px" src="<?= $form['image'] ? e(url($form['image'])) : '' ?>" alt="ตัวอย่างรูปสินค้า"></div>
    <?php if (in_array('digital', $types, true)): ?>
    <div class="col-md-6" data-for-type="digital"><label class="form-label" for="file">ไฟล์สินค้าดิจิทัล<?= $p ? ' (เว้นว่างหากไม่เปลี่ยน)' : '' ?></label>
      <input id="file" class="form-control" type="file" name="file">
      <div class="form-text"><?= $form['file_name'] ? 'ไฟล์ปัจจุบัน: ' . e($form['file_name']) . ' · ' : '' ?>รองรับ <?= implode(', ', DIGITAL_EXTS) ?></div></div>
    <?php endif; ?>
  </div>
  <div class="alert bg-mint border-0 small mt-3 mb-0"><i class="bi bi-info-circle"></i> ค่าธรรมเนียมการขาย <?= e(setting('commission_rate', 5)) ?>% (สมาชิก Premium <?= e(setting('premium_commission_rate', 3)) ?>%) หักจากยอดขายเมื่อการซื้อขายสำเร็จ</div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary px-4"><?= $p ? 'บันทึก' : 'ลงขายสินค้า' ?></button>
    <a class="btn btn-light" href="<?= e(url($done)) ?>">ยกเลิก</a>
  </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
