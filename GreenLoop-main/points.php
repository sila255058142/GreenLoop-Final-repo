<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

if (is_post()) {
    $r = row('SELECT * FROM rewards WHERE id = ? AND active = 1', [(int) post('reward_id')]);
    if (!$r) {
        flash('ไม่พบรายการแลกนี้', 'danger');
    } elseif ($r['type'] === 'item' && trim((string) $u['address']) === '') {
        flash('กรุณากรอกที่อยู่จัดส่งในข้อมูลส่วนตัวก่อนแลกของรางวัล', 'warning');
        redirect('profile.php');
    } else {
        $ok = in_tx(function () use ($u, $r) {
            if (!add_points($u['id'], -$r['points_cost'], 'แลก: ' . $r['name'])) {
                return false;
            }
            $status = 'used';
            if ($r['type'] === 'coupon') {
                $status = 'active';
            } elseif ($r['type'] === 'item') {
                $status = 'pending';
            } elseif ($r['type'] === 'fee') {
                q('UPDATE users SET fee_waivers = fee_waivers + 1 WHERE id = ?', [$u['id']]);
            } elseif ($r['type'] === 'premium') {
                extend_premium($u['id'], (int) $r['value']);
            }
            q('INSERT INTO redemptions (user_id, reward_id, reward_name, type, points, value, code, status) VALUES (?,?,?,?,?,?,?,?)',
                [$u['id'], $r['id'], $r['name'], $r['type'], $r['points_cost'], $r['value'], 'GL-' . strtoupper(bin2hex(random_bytes(4))), $status]);
            return true;
        });
        $done = ['coupon' => 'เลือกใช้คูปองได้ในหน้าชำระเงิน', 'item' => 'ทีมงานจะจัดส่งตามที่อยู่ในข้อมูลส่วนตัว', 'fee' => 'การขายครั้งถัดไปจะไม่เสียค่าธรรมเนียม', 'premium' => 'อัปเกรดเป็นสมาชิก Premium แล้ว'];
        $ok ? flash('แลก "' . $r['name'] . '" สำเร็จ — ' . $done[$r['type']]) : flash('Green Point ไม่เพียงพอ', 'danger');
    }
    redirect('points.php');
}

$m = user_metrics($u['id']);
$achievements = rows('SELECT a.*, ua.unlocked_at FROM achievements a LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ? ORDER BY a.metric, a.threshold', [$u['id']]);
$rewards = rows('SELECT * FROM rewards WHERE active = 1 ORDER BY points_cost');
$mine = rows('SELECT * FROM redemptions WHERE user_id = ? ORDER BY id DESC LIMIT 20', [$u['id']]);
$history = rows('SELECT * FROM point_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 50', [$u['id']]);
$unlocked = count(array_filter($achievements, function ($a) {
    return $a['unlocked_at'];
}));

$title = 'Green Point & Achievement';
require __DIR__ . '/includes/header.php';
echo page_header('Green Point & Achievement', 'สะสมคะแนนจากกิจกรรมต่างๆ เพื่อแลกสิทธิพิเศษและส่วนลดในระบบ');
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="wallet-card point-card mb-4">
      <div class="opacity-75">Green Point ของคุณ</div>
      <div class="amount"><?= number_format($u['green_points']) ?> <small class="fs-6 fw-normal">คะแนน</small></div>
      <div class="small mt-2">สะสมรวมทั้งหมด <?= number_format($m['points']) ?> คะแนน · ปลดล็อก <?= $unlocked ?>/<?= count($achievements) ?> Achievement</div>
      <i class="bi bi-recycle bi-bg"></i>
    </div>
    <div class="panel mb-4">
      <div class="panel-title"><i class="bi bi-plus-circle"></i> การได้รับ Green Point</div>
      <?php foreach ([['ขายสินค้ามือสอง', 'sell_market'], ['ซื้อสินค้ามือสอง', 'buy_market'], ['ส่งคำขอรีไซเคิล', 'recycle_submit'], ['รีไซเคิลสำเร็จ', 'recycle_done'], ['ขาย Digital Product', 'sell_digital'], ['ซื้อ Digital Product', 'buy_digital']] as $x): ?>
        <div class="d-flex justify-content-between py-1 border-bottom"><span><?= $x[0] ?></span><b class="text-green">+<?= POINTS[$x[1]] ?></b></div>
      <?php endforeach; ?>
      <div class="small text-muted mt-2">สิทธิ์ฟรีค่าธรรมเนียมคงเหลือ: <b><?= (int) $u['fee_waivers'] ?></b> ครั้ง</div>
    </div>
    <div class="panel">
      <div class="panel-title"><i class="bi bi-clock-history"></i> ประวัติคะแนน</div>
      <?php if (!$history): ?><div class="empty py-3"><i class="bi bi-inbox"></i>ยังไม่มีประวัติ</div><?php endif; ?>
      <?php foreach ($history as $h): ?>
        <div class="d-flex justify-content-between gap-2 py-2 border-bottom small">
          <div><?= e($h['reason']) ?><div class="text-muted"><?= thai_date($h['created_at']) ?></div></div>
          <b class="text-nowrap <?= $h['points'] < 0 ? 'text-danger' : 'text-success' ?>"><?= ($h['points'] > 0 ? '+' : '') . number_format($h['points']) ?></b>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="panel mb-4">
      <div class="panel-title"><i class="bi bi-trophy"></i> Achievement</div>
      <div class="row row-cols-2 row-cols-md-4 g-3">
        <?php foreach ($achievements as $a): ?>
        <div class="col"><div class="ach <?= $a['unlocked_at'] ? '' : 'locked' ?>">
          <span class="ico"><i class="bi <?= e($a['unlocked_at'] ? $a['icon'] : 'bi-lock') ?>"></i></span>
          <b><?= e($a['name']) ?></b><small><?= e($a['description']) ?></small>
          <div class="progress mt-2" style="height:5px" role="progressbar" aria-label="ความคืบหน้า" aria-valuenow="<?= min($m[$a['metric']], $a['threshold']) ?>" aria-valuemin="0" aria-valuemax="<?= (int) $a['threshold'] ?>"><div class="progress-bar bg-success" style="width: <?= min(100, round($m[$a['metric']] / $a['threshold'] * 100)) ?>%"></div></div>
          <small><?= $a['unlocked_at'] ? 'ปลดล็อกเมื่อ ' . thai_date($a['unlocked_at'], false) : number_format(min($m[$a['metric']], $a['threshold'])) . ' / ' . number_format($a['threshold']) ?></small>
        </div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="panel mb-4">
      <div class="panel-title"><i class="bi bi-gift"></i> ใช้คะแนน Green Point แลกสิทธิพิเศษ</div>
      <div class="row row-cols-1 row-cols-md-2 g-3">
        <?php foreach ($rewards as $r): $can = $u['green_points'] >= $r['points_cost']; ?>
        <div class="col"><div class="reward">
          <span class="ico"><i class="bi <?= e($r['icon']) ?>"></i></span>
          <div class="flex-grow-1"><b><?= e($r['name']) ?></b><div class="small text-muted"><?= e($r['description']) ?></div><div class="small fw-semibold text-green"><?= number_format($r['points_cost']) ?> คะแนน</div></div>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="reward_id" value="<?= $r['id'] ?>">
            <button class="btn btn-sm <?= $can ? 'btn-primary' : 'btn-light' ?>" <?= $can ? '' : 'disabled' ?> data-confirm="ใช้ <?= number_format($r['points_cost']) ?> คะแนนแลก <?= e($r['name']) ?> ?">แลก</button></form>
        </div></div>
        <?php endforeach; ?>
      </div>
      <div class="small text-muted mt-3"><i class="bi bi-info-circle"></i> นอกจากนี้ยังใช้คะแนนเป็นส่วนลดได้โดยตรงในหน้าชำระเงิน (<?= POINTS_PER_BAHT ?> คะแนน = ฿1 สูงสุด <?= POINTS_MAX_PERCENT ?>% ของราคาสินค้า)</div>
    </div>
    <?php if ($mine): ?>
    <div class="panel">
      <div class="panel-title"><i class="bi bi-ticket-perforated"></i> รายการที่แลกแล้ว</div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>วันที่</th><th>รายการ</th><th>รหัส</th><th>คะแนน</th><th>สถานะ</th></tr></thead>
        <tbody><?php foreach ($mine as $x): ?>
          <tr><td class="small"><?= thai_date($x['created_at'], false) ?></td><td><?= e($x['reward_name']) ?></td><td><code><?= e($x['code']) ?></code></td><td><?= number_format($x['points']) ?></td>
            <td><?= $x['status'] === 'pending' ? '<span class="badge rounded-pill text-bg-warning">รอจัดส่ง</span>' : badge($x['status']) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
