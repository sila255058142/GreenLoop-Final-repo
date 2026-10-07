<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

if (is_post()) {
    if (post('action') === 'password') {
        $new = isset($_POST['new']) ? (string) $_POST['new'] : '';
        if (!password_verify(isset($_POST['current']) ? (string) $_POST['current'] : '', $u['password_hash'])) {
            flash('รหัสผ่านปัจจุบันไม่ถูกต้อง', 'danger');
        } elseif (strlen($new) < 8) {
            flash('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร', 'danger');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        }
    } else {
        $name = post('name');
        $avatar = upload_image('avatar', 'avatars');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            flash('กรุณากรอกชื่อ 2-100 ตัวอักษร', 'danger');
        } elseif ($avatar !== false) {
            q('UPDATE users SET name = ?, phone = ?, address = ?, avatar = COALESCE(?, avatar) WHERE id = ?', [$name, mb_substr(post('phone'), 0, 30), post('address'), $avatar, $u['id']]);
            flash('บันทึกข้อมูลส่วนตัวแล้ว');
        }
    }
    redirect('profile.php');
}

$m = user_metrics($u['id']);
$title = 'ข้อมูลส่วนตัว';
require __DIR__ . '/includes/header.php';
echo page_header('จัดการข้อมูลส่วนตัว');
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel text-center">
      <?= avatar_html($u, 96) ?>
      <h2 class="h5 mt-3 mb-0"><?= e($u['name']) ?><?= premium_badge($u) ?></h2>
      <div class="text-muted small"><?= e($u['email']) ?></div>
      <div class="row g-2 mt-3 text-center small">
        <div class="col-4"><div class="fw-bold fs-5 text-green"><?= $m['sales'] ?></div>ขายสำเร็จ</div>
        <div class="col-4"><div class="fw-bold fs-5 text-green"><?= $m['purchases'] ?></div>ซื้อสำเร็จ</div>
        <div class="col-4"><div class="fw-bold fs-5 text-green"><?= $m['recycles'] ?></div>รีไซเคิล</div>
      </div>
      <hr>
      <div class="d-flex justify-content-between small"><span>กระเป๋าเงิน</span><b><?= baht($u['wallet_balance']) ?></b></div>
      <div class="d-flex justify-content-between small"><span>Green Point</span><b><?= number_format($u['green_points']) ?> คะแนน</b></div>
      <div class="d-flex justify-content-between small"><span>สมาชิกตั้งแต่</span><b><?= thai_date($u['created_at'], false) ?></b></div>
    </div>
  </div>
  <div class="col-lg-8">
    <form class="panel mb-4" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="panel-title"><i class="bi bi-person"></i> ข้อมูลส่วนตัว</div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="name">ชื่อที่แสดง</label><input id="name" class="form-control" name="name" value="<?= e($u['name']) ?>" required maxlength="100"></div>
        <div class="col-md-6"><label class="form-label" for="phone">เบอร์โทรศัพท์</label><input id="phone" class="form-control" name="phone" value="<?= e($u['phone']) ?>" maxlength="30"></div>
        <div class="col-12"><label class="form-label" for="address">ที่อยู่สำหรับจัดส่ง</label><textarea id="address" class="form-control" name="address" rows="2"><?= e($u['address']) ?></textarea></div>
        <div class="col-12"><label class="form-label" for="avatar">รูปโปรไฟล์</label><input id="avatar" class="form-control" type="file" name="avatar" accept="image/*"></div>
      </div>
      <button class="btn btn-primary mt-3 px-4">บันทึก</button>
    </form>
    <form class="panel" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div class="panel-title"><i class="bi bi-shield-lock"></i> เปลี่ยนรหัสผ่าน</div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="current">รหัสผ่านปัจจุบัน</label><input id="current" class="form-control" type="password" name="current" required autocomplete="current-password"></div>
        <div class="col-md-6"><label class="form-label" for="new">รหัสผ่านใหม่</label><input id="new" class="form-control" type="password" name="new" required minlength="8" autocomplete="new-password"></div>
      </div>
      <button class="btn btn-outline-primary mt-3 px-4">เปลี่ยนรหัสผ่าน</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
