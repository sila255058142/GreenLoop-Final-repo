<?php
require __DIR__ . '/includes/init.php';

if (user()) {
    redirect('index.php');
}
if (is_post()) {
    $found = row('SELECT * FROM users WHERE email = ?', [post('email')]);
    if (!$found || !password_verify(isset($_POST['password']) ? (string) $_POST['password'] : '', $found['password_hash'])) {
        flash('อีเมลหรือรหัสผ่านไม่ถูกต้อง', 'danger');
    } elseif ($found['status'] !== 'active') {
        flash('บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ', 'danger');
    } else {
        $next = isset($_SESSION['after_login']) ? $_SESSION['after_login'] : null;
        session_regenerate_id(true);
        unset($_SESSION['after_login']);
        $_SESSION['uid'] = $found['id'];
        flash('ยินดีต้อนรับ ' . $found['name']);
        if ($next && $next[0] === '/' && substr($next, 0, 2) !== '//') {
            header('Location: ' . $next);
            exit;
        }
        redirect($found['role'] === 'admin' ? 'admin/index.php' : 'index.php');
    }
    redirect('login.php');
}

$title = 'เข้าสู่ระบบ';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <form class="panel" method="post">
    <?= csrf_field() ?>
    <div class="text-center mb-3"><h1 class="pill-title">เข้าสู่ระบบ</h1></div>
    <div class="mb-3"><label class="form-label" for="email">อีเมล</label><input id="email" class="form-control" type="email" name="email" required autofocus autocomplete="email"></div>
    <div class="mb-3"><label class="form-label" for="password">รหัสผ่าน</label><input id="password" class="form-control" type="password" name="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
    <p class="text-center small mt-3 mb-0">ยังไม่มีบัญชี? <a href="<?= e(url('register.php')) ?>">สมัครสมาชิก</a></p>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
