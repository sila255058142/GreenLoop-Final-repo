<?php
require __DIR__ . '/includes/init.php';

if (user()) {
    redirect('index.php');
}
$old = ['name' => '', 'email' => '', 'phone' => ''];
if (is_post()) {
    $old = ['name' => post('name'), 'email' => post('email'), 'phone' => post('phone')];
    $pass = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $err = '';
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) {
        $err = 'กรุณากรอกชื่อ 2-100 ตัวอักษร';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) {
        $err = 'รูปแบบอีเมลไม่ถูกต้อง';
    } elseif (strlen($pass) < 8) {
        $err = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif ($pass !== (isset($_POST['password2']) ? (string) $_POST['password2'] : '')) {
        $err = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } elseif (val('SELECT 1 FROM users WHERE email = ?', [$old['email']])) {
        $err = 'อีเมลนี้ถูกใช้งานแล้ว';
    }
    if ($err) {
        flash($err, 'danger');
    } else {
        q('INSERT INTO users (name, email, password_hash, phone) VALUES (?,?,?,?)', [$old['name'], $old['email'], password_hash($pass, PASSWORD_DEFAULT), mb_substr($old['phone'], 0, 30)]);
        session_regenerate_id(true);
        $_SESSION['uid'] = db()->lastInsertId();
        flash('สมัครสมาชิกสำเร็จ ยินดีต้อนรับสู่ GreenLoop');
        redirect('index.php');
    }
}

$title = 'สมัครสมาชิก';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <form class="panel" method="post">
    <?= csrf_field() ?>
    <div class="text-center mb-3"><h1 class="pill-title">สมัครสมาชิก</h1></div>
    <div class="mb-3"><label class="form-label" for="name">ชื่อที่แสดง</label><input id="name" class="form-control" name="name" value="<?= e($old['name']) ?>" required maxlength="100" autofocus></div>
    <div class="mb-3"><label class="form-label" for="email">อีเมล</label><input id="email" class="form-control" type="email" name="email" value="<?= e($old['email']) ?>" required autocomplete="email"></div>
    <div class="mb-3"><label class="form-label" for="phone">เบอร์โทรศัพท์</label><input id="phone" class="form-control" name="phone" value="<?= e($old['phone']) ?>" maxlength="30"></div>
    <div class="mb-3"><label class="form-label" for="password">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label><input id="password" class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="mb-3"><label class="form-label" for="password2">ยืนยันรหัสผ่าน</label><input id="password2" class="form-control" type="password" name="password2" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-primary w-100">สมัครสมาชิก</button>
    <p class="text-center small mt-3 mb-0">มีบัญชีอยู่แล้ว? <a href="<?= e(url('login.php')) ?>">เข้าสู่ระบบ</a></p>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
