<?php
/**
 * DSJIE 个人主页 - 安装向导 install.php
 * 首次部署：浏览器访问 /install.php，填写站点信息与管理员账号即可完成安装。
 * 已安装的站点再次访问需先验证当前管理员账号，才能进入重新配置（防覆盖）。
 */
require __DIR__ . '/config.php';

// 首次部署：没有站点数据时，从示例模板初始化（开源包不携带作者个人数据）
if (!is_file(DATA_FILE) && is_file(__DIR__ . '/data/content.example.json')) {
  @copy(__DIR__ . '/data/content.example.json', DATA_FILE);
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$installed = is_file(IDENTITY_FILE) && is_file(ADMIN_FILE);
$rewrite   = $installed && isset($_GET['rewrite']);
$err = ''; $ok = false;

$form = ['site_name' => '我的主页', 'nick' => '', 'contact' => '',
         'user' => ADMIN_USER_DEFAULT, 'pass' => '', 'pass2' => '',
         'qq' => '', 'cur_user' => '', 'cur_pass' => ''];

// 预填：站点名/联系方式取自现有 content.json，当前账号取自 admin.json（无则默认账号）
if (is_file(DATA_FILE)) {
  $sj = json_decode((string)@file_get_contents(DATA_FILE), true);
  if (is_array($sj) && isset($sj['site'])) {
    if (!empty($sj['site']['name'])) $form['site_name'] = $sj['site']['name'];
    if (!empty($sj['site']['contact_url'])) $form['contact'] = $sj['site']['contact_url'];
  }
}
$cur_user = ADMIN_USER_DEFAULT;
if (is_file(ADMIN_FILE)) {
  $aj = json_decode((string)@file_get_contents(ADMIN_FILE), true);
  if (is_array($aj) && !empty($aj['user'])) $cur_user = $aj['user'];
}
$form['user'] = $form['cur_user'] = $cur_user;

/** 现有管理员账号：admin.json 优先，缺失时回落到默认账号 */
function current_creds(): array {
  $u = ADMIN_USER_DEFAULT; $hash = null; $plain = ADMIN_PASS_DEFAULT;
  if (is_file(ADMIN_FILE)) {
    $j = json_decode((string)@file_get_contents(ADMIN_FILE), true);
    if (is_array($j)) {
      if (!empty($j['user'])) $u = $j['user'];
      if (!empty($j['pass_hash'])) $hash = $j['pass_hash'];
      elseif (isset($j['pass'])) $plain = $j['pass'];
    }
  }
  return ['user' => $u, 'hash' => $hash, 'plain' => $plain];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($form as $k => $v) if (isset($_POST[$k])) $form[$k] = trim((string)$_POST[$k]);

  if ($installed) {   // 重新配置：必须先验证当前管理员身份
    $c = current_creds();
    $pass_ok = $form['cur_user'] === $c['user']
      && ($c['hash'] ? password_verify($form['cur_pass'], $c['hash'])
                     : hash_equals((string)$c['plain'], $form['cur_pass']));
    if (!$pass_ok) $err = '当前管理员账号或密码不正确。';
    if ($err === '' && $form['pass'] !== '' && strlen($form['pass']) < 6) $err = '新密码至少 6 位。';
    if ($err === '' && $form['pass'] !== '' && $form['pass'] !== $form['pass2']) $err = '两次输入的密码不一致。';
  } else {            // 首次安装
    if ($form['site_name'] === '') $err = '请填写站点名称。';
    if ($err === '' && $form['user'] === '') $err = '请填写管理员账号。';
    if ($err === '' && strlen($form['pass']) < 6) $err = '管理员密码至少 6 位。';
    if ($err === '' && $form['pass'] !== $form['pass2']) $err = '两次输入的密码不一致。';
  }
  if ($err === '' && !is_file(DATA_FILE)) $err = '缺少站点数据模板 data/content.example.json，源码包文件不完整，请重新下载上传。';
  if ($err === '' && !is_writable(dirname(DATA_FILE))) $err = 'data/ 目录不可写，请先按 README 修复目录权限（sh fixperm.sh）再重试。';

  if ($err === '') {
    // 1) 管理员账号 → data/admin.json（重新配置且密码留空 = 保持原账号不变）
    if (!$installed || $form['pass'] !== '') {
      file_put_contents(ADMIN_FILE, json_encode(
        ['user' => $form['user'], 'pass_hash' => password_hash($form['pass'], PASSWORD_DEFAULT)],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    // 2) 站点身份 → data/identity.php（随机生成，不再有明文默认口令）
    file_put_contents(IDENTITY_FILE, "<?php\nreturn array (\n"
      . "  'role_key' => '" . bin2hex(random_bytes(32)) . "',\n"
      . "  'master_qq' => '" . addslashes($form['qq'] !== '' ? $form['qq'] : '0') . "',\n"
      . "  'master_pwd' => '" . bin2hex(random_bytes(8)) . "',\n"
      . "  'legacy_role_signatures' => array (),\n);\n");
    // 3) 数据目录保护 + 上传目录
    if (!is_file(__DIR__ . '/data/.htaccess')) {
      file_put_contents(__DIR__ . '/data/.htaccess',
        "# 禁止通过 Web 访问数据目录（配置/账号/访问日志）\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    }
    foreach ([UPLOAD_DIR, FILES_DIR] as $d) if (!is_dir($d)) @mkdir($d, 0755, true);
    // 4) 站点名称 / 昵称 / 联系方式写入 data/content.json
    $j = json_decode((string)file_get_contents(DATA_FILE), true);
    if (!is_array($j)) $j = [];
    if (!isset($j['site']) || !is_array($j['site'])) $j['site'] = [];
    $j['site']['name'] = $form['site_name'];
    if ($form['nick'] !== '') $j['site']['contact_text'] = '联系 ' . $form['nick'] . '.';
    if ($form['contact'] !== '') $j['site']['contact_url'] = $form['contact'];
    file_put_contents(DATA_FILE, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $ok = true;
  }
}

// 已部署的站点再次访问安装向导 → 自动回首页，不打开向导（重新配置用 install.php?rewrite=1）
if ($installed && !$rewrite && !$ok) {
  header('Location: ./');
  exit;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>安装 DSJIE 个人主页</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:#fafafa;color:#111;font-family:-apple-system,'SF Pro Display','PingFang SC',system-ui,sans-serif}
.card{width:100%;max-width:460px;background:#fff;border:1px solid #e7e7e7;border-radius:16px;padding:32px 28px}
h1{font-size:22px;letter-spacing:1px}
.sub{color:#888;font-size:13px;margin-top:8px;line-height:1.7}
label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px}
input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:15px;background:#fff}
input:focus{outline:none;border-color:#111}
.btn{width:100%;margin-top:22px;padding:12px;border:none;border-radius:8px;background:#111;color:#fff;font-size:15px;cursor:pointer}
.err{margin-top:14px;padding:10px 12px;background:#fff1f0;border:1px solid #ffccc7;border-radius:8px;color:#cf1322;font-size:13px}
.big{font-size:16px;font-weight:700;margin-top:16px}
.acts{margin-top:16px}
.acts a{display:inline-block;margin:6px 8px 0 0;padding:10px 18px;background:#111;color:#fff;border-radius:8px;text-decoration:none;font-size:14px}
.acts a.ghost{background:#fff;color:#111;border:1px solid #111}
.hr{border-top:1px dashed #ddd;margin:20px 0 2px}
.hint{font-size:12px;color:#999;margin-top:5px}
.t{font-size:12px;color:#aaa;text-align:center;margin-top:18px}
</style>
</head>
<body>
<div class="card">
<?php if ($ok) : ?>
<h1>安装完成</h1>
<p class="sub">站点「<b><?php echo h($form['site_name']) ?></b>」已部署好。管理员账号已写入 <code>data/admin.json</code>，站点口令随机生成，数据目录已加保护，图库/下载目录已就绪。</p>
<div class="big">接下来：</div>
<p class="sub">① 登录后台，把示例内容换成你自己的；② 作品、随笔、图库等所有内容都能在后台增删改；③ 建议删除本文件 <code>install.php</code>，之后修改账号请在后台「账号」页操作；④ 以后想重新跑一遍安装向导，见下方「④ 重新安装向导部署教程」。</p>
<div class="big">④ 重新安装向导部署教程</div>
<p class="sub">1) 把 <code>install.php</code> 重新上传到站点根目录（首次装完删除后需补回，或整包解压覆盖）；2) 浏览器访问 <code>install.php?rewrite=1</code>；3) 输入<b>当前</b>管理员账号与密码验证身份；4) 表单已预填现有站点名称、昵称、联系方式，按需修改——管理员密码<b>留空 = 保持原账号不变</b>；5) 点「保存配置」自动跳回首页，站点数据全部保留；6) 重新配置完成后，再次删除 <code>install.php</code>。若想彻底重来（数据全清），删掉 <code>data/identity.php</code> 和 <code>data/admin.json</code> 后访问首页，会自动重新进入安装向导（注意：从删除到装完之间，任何访问首页的人都能填写向导，请尽快操作）。</p>
<div class="acts"><a href="./">进入前台</a><a href="./admin/" class="ghost">登录后台</a></div>
<?php else : ?>
<h1><?php echo $rewrite ? '重新配置' : '安装向导' ?></h1>
<p class="sub"><?php echo $rewrite ? '验证当前管理员身份后，可修改站点信息与登录账号。密码留空表示保持原账号不变。' : '填写站点信息和管理员账号即可完成安装，所有内容之后都能在后台随时修改。' ?></p>
<?php if ($err !== '') : ?><div class="err"><?php echo h($err) ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<?php if ($rewrite) : ?>
<div class="hr"></div>
<label>当前管理员账号 *</label>
<input name="cur_user" value="<?php echo h($form['cur_user']) ?>" required>
<label>当前管理员密码 *</label>
<input type="password" name="cur_pass" required>
<div class="hr"></div>
<?php endif; ?>
<label>站点名称 *</label>
<input name="site_name" value="<?php echo h($form['site_name']) ?>" required placeholder="我的主页">
<label>站长昵称（可选）</label>
<input name="nick" value="<?php echo h($form['nick']) ?>" placeholder="用于「联系 XX」等站点文案">
<label>联系方式链接（可选）</label>
<input name="contact" value="<?php echo h($form['contact']) ?>" placeholder="mailto:you@qq.com 或 https://your-blog">
<label>管理员账号 *</label>
<input name="user" value="<?php echo h($form['user']) ?>" required>
<label>管理员密码<?php echo $rewrite ? '（留空 = 保持不变）' : ' *（至少 6 位）' ?></label>
<input type="password" name="pass" <?php echo $rewrite ? '' : 'required' ?>>
<label>确认密码<?php echo $rewrite ? '（与上面同时填写才生效）' : ' *' ?></label>
<input type="password" name="pass2" <?php echo $rewrite ? '' : 'required' ?>>
<label>站长 QQ（可选）</label>
<input name="qq" value="<?php echo h($form['qq']) ?>" placeholder="留言区站长特权身份用">
<button class="btn"><?php echo $rewrite ? '保存配置' : '完成安装' ?></button>
</form>
<p class="t">DSJIE 个人主页 · 无需数据库 · 解压即用</p>
<?php endif; ?>
</div>
</body>
</html>

