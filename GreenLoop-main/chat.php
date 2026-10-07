<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

$withId = (int) get('with');
$with = $withId && $withId != $u['id'] ? row('SELECT id, name, avatar, role, premium_until FROM users WHERE id = ?', [$withId]) : null;
$productId = (int) get('product');

if (is_post()) {
    if (!$with || !send_message($u['id'], $with['id'], post('body'), (int) post('product_id'))) {
        flash('ส่งข้อความไม่สำเร็จ', 'danger');
    }
    redirect('chat.php' . ($with ? '?with=' . $with['id'] : ''));
}

if ($with) {
    q('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0', [$with['id'], $u['id']]);
}
if (get('ajax')) {
    echo $with ? render_messages(chat_messages($u['id'], $with['id']), $u['id']) : '';
    exit;
}

$threads = chat_threads($u['id']);
$product = $with && $productId ? row('SELECT id, title, price FROM products WHERE id = ?', [$productId]) : null;
$adminId = (int) val("SELECT id FROM users WHERE role = 'admin' AND status = 'active' ORDER BY id LIMIT 1");

$title = 'แชท';
require __DIR__ . '/includes/header.php';
?>
<div class="section-head">
  <h1 class="pill-title">แชท</h1>
  <?php if ($adminId && $adminId != $u['id']): ?><a class="btn btn-outline-primary btn-sm" href="<?= e(url('chat.php?with=' . $adminId)) ?>"><i class="bi bi-headset"></i> ติดต่อผู้ดูแลระบบ</a><?php endif; ?>
</div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="panel p-2 chat-list">
      <?php if (!$threads): ?><div class="empty py-4"><i class="bi bi-chat-dots"></i>ยังไม่มีการสนทนา</div><?php endif; ?>
      <?php foreach ($threads as $t): ?>
      <a href="<?= e(url('chat.php?with=' . $t['other_id'])) ?>" class="<?= $with && $with['id'] == $t['other_id'] ? 'active' : '' ?>">
        <?= avatar_html($t, 42) ?>
        <div class="flex-grow-1 overflow-hidden"><div class="fw-medium"><?= e($t['name']) ?><?= $t['role'] === 'admin' ? ' <span class="badge text-bg-success">Admin</span>' : '' ?></div><div class="last"><?= e($t['body']) ?></div></div>
        <?php if ($t['unread']): ?><span class="badge rounded-pill text-bg-danger"><?= (int) $t['unread'] ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-8">
    <?php if ($with): ?>
    <div class="panel">
      <div class="d-flex align-items-center gap-2 mb-3"><?= avatar_html($with, 40) ?><div class="fw-semibold"><?= e($with['name']) ?><?= premium_badge($with) ?><?= $with['role'] === 'admin' ? ' <span class="badge text-bg-success">Admin</span>' : '' ?></div></div>
      <div class="chat-box" data-poll="<?= e(url('chat.php?ajax=1&with=' . $with['id'])) ?>"><?= render_messages(chat_messages($u['id'], $with['id']), $u['id']) ?></div>
      <form method="post" class="mt-3"><?= csrf_field() ?>
        <?php if ($product): ?>
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <div class="small bg-mint rounded-3 px-3 py-2 mb-2"><i class="bi bi-box-seam"></i> สอบถามเกี่ยวกับ: <b><?= e($product['title']) ?></b> (<?= baht($product['price']) ?>)</div>
        <?php endif; ?>
        <div class="input-group">
          <input class="form-control" name="body" placeholder="พิมพ์ข้อความ..." required maxlength="2000" autocomplete="off" autofocus aria-label="ข้อความ">
          <button class="btn btn-primary px-4"><i class="bi bi-send"></i> ส่ง</button>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="panel empty"><i class="bi bi-chat-square-text"></i>เลือกการสนทนาทางซ้าย หรือกด "ติดต่อผู้ขาย" จากหน้าสินค้า</div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
