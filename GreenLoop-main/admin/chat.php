<?php
require __DIR__ . '/../includes/init.php';
$admin = require_admin();

$a = (int) get('a');
$b = (int) get('b');
$pair = $a && $b && $a != $b ? rows('SELECT id, name, avatar, role, premium_until FROM users WHERE id IN (?, ?) ORDER BY id', [$a, $b]) : [];
$mine = count($pair) === 2 && ($a == $admin['id'] || $b == $admin['id']);
$otherId = $mine ? ($a == $admin['id'] ? $b : $a) : 0;

if (is_post()) {
    // แอดมินตอบได้เฉพาะห้องสนทนาของตัวเอง ห้องของผู้ใช้คู่อื่นดูได้อย่างเดียว
    if (!$mine || !send_message($admin['id'], $otherId, post('body'))) {
        flash('ส่งข้อความไม่สำเร็จ', 'danger');
    }
    redirect('admin/chat.php?a=' . $a . '&b=' . $b);
}
if ($mine) {
    q('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0', [$otherId, $admin['id']]);
}
if (get('ajax')) {
    echo count($pair) === 2 ? render_messages(chat_messages($a, $b), $mine ? $admin['id'] : $pair[1]['id'], true) : '';
    exit;
}

$threads = rows('SELECT t.a, t.b, t.n, ua.name AS a_name, ub.name AS b_name, m.body, m.created_at,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0 AND sender_id IN (t.a, t.b) AND ? IN (t.a, t.b)) AS unread
    FROM (SELECT LEAST(sender_id, receiver_id) AS a, GREATEST(sender_id, receiver_id) AS b, MAX(id) AS last_id, COUNT(*) AS n FROM messages GROUP BY a, b) t
    JOIN users ua ON ua.id = t.a JOIN users ub ON ub.id = t.b JOIN messages m ON m.id = t.last_id ORDER BY t.last_id DESC LIMIT 200', [$admin['id'], $admin['id']]);
$users = rows('SELECT id, name, email FROM users WHERE id <> ? ORDER BY name', [$admin['id']]);

$title = 'จัดการแชท';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-xl-4">
    <form class="panel mb-3" method="get">
      <div class="panel-title"><i class="bi bi-headset"></i> แชทกับผู้ใช้</div>
      <input type="hidden" name="a" value="<?= $admin['id'] ?>">
      <div class="input-group"><select class="form-select" name="b" required aria-label="เลือกผู้ใช้"><option value="">— เลือกผู้ใช้ —</option>
        <?php foreach ($users as $x): ?><option value="<?= $x['id'] ?>"><?= e($x['name']) ?> (<?= e($x['email']) ?>)</option><?php endforeach; ?></select>
        <button class="btn btn-primary">เปิดแชท</button></div>
    </form>
    <div class="panel p-2 chat-list">
      <div class="panel-title px-2 pt-2"><i class="bi bi-chat-dots"></i> ประวัติแชททั้งหมด</div>
      <?php if (!$threads): ?><div class="empty py-4"><i class="bi bi-chat-dots"></i>ยังไม่มีการสนทนา</div><?php endif; ?>
      <?php foreach ($threads as $t): ?>
      <a href="<?= e(url('admin/chat.php?a=' . $t['a'] . '&b=' . $t['b'])) ?>" class="<?= $t['a'] == min($a, $b) && $t['b'] == max($a, $b) ? 'active' : '' ?>">
        <div class="flex-grow-1 overflow-hidden"><div class="fw-medium small"><?= e($t['a_name']) ?> <i class="bi bi-arrow-left-right text-muted"></i> <?= e($t['b_name']) ?></div>
          <div class="last"><?= e($t['body']) ?></div></div>
        <?php if ($t['unread']): ?><span class="badge rounded-pill text-bg-danger"><?= (int) $t['unread'] ?></span><?php else: ?><span class="badge rounded-pill bg-mint text-green"><?= (int) $t['n'] ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-xl-8">
    <?php if (count($pair) === 2): ?>
    <div class="panel">
      <div class="fw-semibold mb-3"><?= e($pair[0]['name']) ?> <i class="bi bi-arrow-left-right text-muted"></i> <?= e($pair[1]['name']) ?>
        <?php if (!$mine): ?><span class="badge rounded-pill text-bg-secondary ms-1">ดูอย่างเดียว</span><?php endif; ?></div>
      <div class="chat-box" data-poll="<?= e(url('admin/chat.php?ajax=1&a=' . $a . '&b=' . $b)) ?>"><?= render_messages(chat_messages($a, $b), $mine ? $admin['id'] : $pair[1]['id'], true) ?></div>
      <?php if ($mine): ?>
      <form method="post" class="mt-3"><?= csrf_field() ?>
        <div class="input-group"><input class="form-control" name="body" placeholder="พิมพ์ข้อความถึงผู้ใช้..." required maxlength="2000" autocomplete="off" autofocus aria-label="ข้อความ"><button class="btn btn-primary px-4"><i class="bi bi-send"></i> ส่ง</button></div>
      </form>
      <?php else: ?>
      <div class="d-flex gap-2 mt-3">
        <?php foreach ($pair as $x): if ($x['id'] == $admin['id']) continue; ?><a class="btn btn-outline-primary btn-sm" href="<?= e(url('admin/chat.php?a=' . $admin['id'] . '&b=' . $x['id'])) ?>"><i class="bi bi-chat-dots"></i> แชทกับ <?= e($x['name']) ?></a><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?><div class="panel empty"><i class="bi bi-chat-square-text"></i>เลือกการสนทนาเพื่อดูประวัติ หรือเลือกผู้ใช้เพื่อเริ่มแชท</div><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
