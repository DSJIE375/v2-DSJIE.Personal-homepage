<?php
/* ---------- 登录页 ---------- */
function view_login(string $err): void {
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>后台登录 · DSJIE</title><script>(function(){var m=null;try{m=localStorage.getItem("dsjie_theme")}catch(e){}var d=m==="dark"||(m!=="light"&&window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches);document.documentElement.setAttribute("data-theme",d?"dark":"light");})();</script><link rel="stylesheet" href="style.css"></head><body class="lbody">'
       . '<form class="lbox" method="post" action="">'
       . '<h1>DSJIE 后台</h1><p class="lsub">个人主页 · 内容管理</p>'
       . ($err !== '' ? '<p class="lerr">' . e($err) . '</p>' : '')
       . '<input name="user" placeholder="账号" autocomplete="username" required autofocus>'
       . '<input name="pass" type="password" placeholder="密码" autocomplete="current-password" required>'
       . '<button type="submit" name="do" value="login">登 录</button>'
       . '<a class="lback" href="../index.php">← 返回前台</a>'
       . '</form></body></html>';
}

/* ---------- 通用字段渲染 ---------- */
function field_input(array $f, string $v): void {
    echo '<label class="fld"><span>' . e($f['label']) . '</span>';
    $t = $f['type'];
    if ($t === 'textarea') echo '<textarea name="' . e($f['key']) . '" rows="4">' . e($v) . '</textarea>';
    elseif ($t === 'big') echo '<textarea name="' . e($f['key']) . '" rows="10">' . e($v) . '</textarea>';
    elseif ($t === 'html') echo '<textarea name="' . e($f['key']) . '" rows="5">' . e($v) . '</textarea><small class="hint">支持 &lt;strong&gt;、&lt;em&gt;、&lt;br&gt;、&lt;a&gt; 标签，脚本会被自动过滤</small>';
    elseif ($t === 'upload') {
        if ($v !== '') echo '<div class="cur">当前文件：' . e(basename($v)) . '</div><input type="hidden" name="cur" value="' . e($v) . '">';
        echo '<input type="file" name="up" accept="' . e($f['accept'] ?? '*/*') . '">';
    }
    else echo '<input name="' . e($f['key']) . '" value="' . e($v) . '">';
    echo '</label>';
}

/* ---------- 列表视图（移动端转卡片式，data-label 供字段名展示） ---------- */
function view_list(string $t, array $defs, array $rows): void {
    echo '<div class="panel"><div class="ptop"><h2>' . e($defs['label']) . ' <span class="num">' . count($rows) . ' 条</span></h2>'
       . '<a class="btn" href="index.php?p=' . e($t) . '&amp;act=new">＋ 新增</a></div>';
    if (!$rows) {
        echo '<p class="empty">还没有内容，点右上角「新增」创建一条。</p></div>';
        return;
    }
    echo '<table class="tbl"><tr>';
    foreach ($defs['fields'] as $f) echo '<th>' . e($f['label']) . '</th>';
    echo '<th class="ops">操作</th></tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($defs['fields'] as $f) {
            $v = (string)($r[$f['key']] ?? '');
            echo '<td data-label="' . e($f['label']) . '">';
            if ($f['type'] === 'upload' && $v !== '') {
                if (($f['dir'] ?? '') === 'uploads') echo '<img class="thumb" src="../' . e($v) . '" alt="">';
                else echo '<a href="../' . e($v) . '" target="_blank">' . e(basename($v)) . '</a>';
            } else echo e(mb_substr($v, 0, 56, 'UTF-8'));
            echo '</td>';
        }
        echo '<td class="ops"><a class="mini" href="index.php?p=' . e($t) . '&amp;act=edit&amp;id=' . e($r['id'] ?? '') . '">编辑</a>'
           . '<form method="post" class="delf" onsubmit="return confirm(\'确定删除这条内容？\')">'
           . '<input type="hidden" name="do" value="delete_item"><input type="hidden" name="p" value="' . e($t) . '">'
           . '<input type="hidden" name="type" value="' . e($t) . '"><input type="hidden" name="id" value="' . e($r['id'] ?? '') . '">'
           . '<button class="mini danger">删除</button></form></td></tr>';
    }
    echo '</table></div>';
}

/* ---------- 新增 / 编辑表单 ---------- */
function view_form(string $t, array $defs, array $item): void {
    $isNew = (($item['id'] ?? '') === '');
    echo '<div class="panel"><div class="ptop"><h2>' . e($defs['label']) . ' · ' . ($isNew ? '新增' : '编辑') . '</h2>'
       . '<a class="btn ghost" href="index.php?p=' . e($t) . '">← 返回列表</a></div>'
       . '<form method="post" class="fbox" enctype="multipart/form-data">'
       . '<input type="hidden" name="do" value="save_item"><input type="hidden" name="type" value="' . e($t) . '">'
       . '<input type="hidden" name="p" value="' . e($t) . '"><input type="hidden" name="id" value="' . e($item['id'] ?? '') . '">';
    foreach ($defs['fields'] as $f) field_input($f, (string)($item[$f['key']] ?? ''));
    echo '<div class="fbtns"><button class="btn">保 存</button></div></form></div>';
}
?>