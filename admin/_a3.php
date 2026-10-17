<?php
/* ---------- 设置视图（站点 / 关于） ---------- */
function view_settings(string $sec, array $defs, array $data): void {
    $cur = $data[$sec] ?? [];
    echo '<div class="panel"><div class="ptop"><h2>' . e($defs['label']) . '</h2></div>'
       . '<form method="post" class="fbox">'
       . '<input type="hidden" name="do" value="save_settings"><input type="hidden" name="p" value="' . e($sec) . '">';
    foreach ($defs['fields'] as $f) field_input($f, (string)($cur[$f['key']] ?? ''));
    if ($sec === 'site') {
        echo '<h3 class="sub">社交链接（4 个固定图标位：GitHub / 邮箱 / 博客 / RSS）</h3>';
        for ($i = 0; $i < 4; $i++) {
            $lb = (string)($cur['socials'][$i]['label'] ?? '');
            $ur = (string)($cur['socials'][$i]['url'] ?? '');
            echo '<div class="srow"><input name="social_label[' . $i . ']" placeholder="名称" value="' . e($lb) . '">'
               . '<input name="social_url[' . $i . ']" placeholder="链接（空=显示 #）" value="' . e($ur) . '"></div>';
        }
    }
    echo '<div class="fbtns"><button class="btn">保 存</button></div></form></div>';
}

/* ---------- 留言管理 ---------- */
function view_messages(array $rows): void {
    echo '<div class="panel"><div class="ptop"><h2>留言板 <span class="num">' . count($rows) . ' 条</span></h2></div>';
    if (!$rows) echo '<p class="empty">还没有留言。</p>';
    foreach ($rows as $m) {
        echo '<div class="msgbox"><div class="mhead"><b>' . e($m['qq'] ?? '') . '</b><span class="mmail">' . e($m['email'] ?? '') . '</span><span>' . e($m['time'] ?? '') . '</span>'
           . '<form method="post" class="delf" onsubmit="return confirm(\'删除这条留言？\')">'
           . '<input type="hidden" name="do" value="delete_msg"><input type="hidden" name="p" value="messages">'
           . '<input type="hidden" name="id" value="' . e($m['id'] ?? '') . '"><button class="mini danger">删除留言</button></form></div>'
           . '<p class="mtext">' . e($m['text'] ?? '') . '</p>';
        foreach (($m['replies'] ?? []) as $r) {
            echo '<div class="mrep"><b>' . e($r['qq'] ?? '') . '</b><span>' . e($r['time'] ?? '') . '</span>　' . e($r['text'] ?? '')
               . '<form method="post" class="delf" onsubmit="return confirm(\'删除这条回复？\')">'
               . '<input type="hidden" name="do" value="delete_reply"><input type="hidden" name="p" value="messages">'
               . '<input type="hidden" name="msg_id" value="' . e($m['id'] ?? '') . '"><input type="hidden" name="rid" value="' . e($r['id'] ?? '') . '">'
               . '<button class="mini danger">删</button></form></div>';
        }
        echo '<form method="post" class="reps"><input type="hidden" name="do" value="save_reply">'
           . '<input type="hidden" name="p" value="messages"><input type="hidden" name="msg_id" value="' . e($m['id'] ?? '') . '">'
           . '<textarea name="reply" rows="2" maxlength="200" placeholder="以站长身份回复…"></textarea><button class="btn">回复</button></form></div>';
    }
    echo '</div>';
}

/* ---------- 账号 ---------- */
function view_account(array $c): void {
    echo '<div class="panel"><div class="ptop"><h2>账号安全</h2></div><form method="post" class="fbox narrow">'
       . '<input type="hidden" name="do" value="save_account"><input type="hidden" name="p" value="account">'
       . '<label class="fld"><span>用户名</span><input name="user" value="' . e($c['user']) . '" required></label>'
       . '<label class="fld"><span>新密码（留空 = 不修改）</span><input name="pass1" type="password" autocomplete="new-password"></label>'
       . '<label class="fld"><span>确认新密码</span><input name="pass2" type="password" autocomplete="new-password"></label>'
       . '<div class="fbtns"><button class="btn">保存账号</button></div></form>'
       . '<p class="hint">修改后立即生效；忘记密码可删除 data/admin.json 后用 config.php 中的默认账号登录。</p></div>';
}

/* ---------- 分发 + 布局 ---------- */
if (!$logged) { view_login($err); exit; }
$data = load_data();
$kind = $TYPES[$pnow]['kind'];

$item = null;
if ($kind === 'list' && ($act === 'edit' || $act === 'new')) {
    if ($act === 'edit') {
        $want = (string)($_GET['id'] ?? '');
        foreach ($data[$pnow] as $it) if (($it['id'] ?? '') === $want) { $item = $it; break; }
        if ($item === null) { header('Location: index.php?p=' . $pnow . '&msg=条目不存在'); exit; }
    } else {
        $item = ['id' => ''];
    }
}

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
   . '<title>后台管理 · DSJIE</title><script>(function(){var m=null;try{m=localStorage.getItem("dsjie_theme")}catch(e){}var d=m==="dark"||(m!=="light"&&window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches);document.documentElement.setAttribute("data-theme",d?"dark":"light");})();</script><link rel="stylesheet" href="style.css"></head><body class="apage">'
   . '<header class="ahead"><span class="alogo">DSJIE 后台</span><span class="aact">'
   . '<a href="../index.php" target="_blank">查看前台 ↗</a>'
   . '<form method="post" class="logoutf"><button name="do" value="logout" class="btn ghost">退出登录</button></form>'
   . '</span></header><div class="awrap"><nav class="anav">';
foreach ($TYPES as $k => $tdef) {
    $badge = ($k === 'messages' && count($data['messages'])) ? '<b class="cnt">' . count($data['messages']) . '</b>' : '';
    echo '<a class="navi' . ($k === $pnow ? ' on' : '') . '" href="index.php?p=' . $k . '">' . e($tdef['label']) . $badge . '</a>';
}
echo '</nav><main class="amain">';
if ($msg !== '') echo '<div class="flash">' . e($msg) . '</div>';

if ($kind === 'list') {
    if ($item !== null) view_form($pnow, $TYPES[$pnow], $item);
    else view_list($pnow, $TYPES[$pnow], $data[$pnow]);
} elseif ($kind === 'settings') view_settings($pnow, $TYPES[$pnow], $data);
elseif ($kind === 'messages') view_messages($data['messages']);
elseif ($kind === 'account') view_account(admin_creds());

echo '</main></div><footer class="afoot">DSJIE 个人主页 · 后台 v1.0</footer></body></html>';
