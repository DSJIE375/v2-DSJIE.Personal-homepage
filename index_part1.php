<?php
/** DSJIE 个人主页 - 前台入口 */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/render2.php';
$data = load_data();
$s = $data['site'];
$sections = build_sections($data);
$radar = parse_radar($data['about']['radar'] ?? '');
$gbook = public_messages($data);
$about_html = $sections[0]['html'];
$enc = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

// 社交图标（4 个固定槽位：GitHub / 邮箱 / 博客 / RSS）
$ico = "viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'";
$svgs = [
  "<svg $ico><path d='M9 19c-4 1.5-4-2-6-2.5M15 21v-3.2c0-1 .3-1.6.9-2.2C19 14.6 21 12.6 21 9.6A5.4 5.4 0 0 0 19.3 5.4 5 5 0 0 0 19.4 2s-1.6-.2-4.4 1.6A13 13 0 0 0 9 3.6 13 13 0 0 0 4.6 2S4 1.6 2 2a5 5 0 0 0 .1 3.4A5.4 5.4 0 0 0 .5 9.6c0 3 2 5 6 6.2.6.6.9 1.2.9 2.2V21'/></svg>",
  "<svg $ico><rect x='3' y='5' width='18' height='14' rx='2'/><path d='M3 7l9 6 9-6'/></svg>",
  "<svg $ico><path d='M12 20h9'/><path d='M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z'/></svg>",
  "<svg $ico><path d='M4 11a9 9 0 0 1 9 9M4 4a16 16 0 0 1 16 16'/><circle cx='5' cy='19' r='1.4' fill='currentColor' stroke='none'/></svg>",
];
$socials_html = '';
for ($i = 0; $i < 4; $i++) {
  $it = $s['socials'][$i] ?? ['label' => '链接', 'url' => ''];
  $url = trim((string)($it['url'] ?? ''));
  if ($url === '') $url = '#';
  $socials_html .= "<a class='sico' href='" . e($url) . "' aria-label='" . e($it['label'] ?? '') . "'>" . $svgs[$i] . '</a>';
}
$themetog = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.8' stroke-linecap='round'><circle cx='12' cy='12' r='4'/><path d='M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4'/></svg>";
$first = mb_substr((string)$s['name'], 0, 1, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= e($s['seo_title']) ?></title>
<meta name='description' content='<?= e($s['seo_desc']) ?>'>
<meta name='keywords' content='<?= e($s['seo_keywords']) ?>'>
<meta property='og:type' content='website'>
<meta property='og:locale' content='zh_CN'>
<meta property='og:site_name' content='<?= e($s['name']) ?>'>
<meta property='og:title' content='<?= e($s['seo_title']) ?>'>
<meta property='og:description' content='<?= e($s['seo_desc']) ?>'>
<meta name='theme-color' content='#ffffff' media='(prefers-color-scheme: light)'>
<meta name='theme-color' content='#0a0a0a' media='(prefers-color-scheme: dark)'>
<link rel='icon' href='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%23111827%22/><text x=%2232%22 y=%2244%22 font-size=%2236%22 font-family=%22Arial%22 font-weight=%22bold%22 fill=%22white%22 text-anchor=%22middle%22>D</text></svg>'>
<link rel='stylesheet' href='assets/style.css'>
<link rel='stylesheet' href='assets/extra.css'>
<script>
(function(){
  var KEY='dsjie_theme';
  var saved=null; try{saved=localStorage.getItem(KEY);}catch(e){}
  var mode=saved||'system';
  var dark=mode==='dark'||(mode==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);
  document.documentElement.setAttribute('data-theme', dark?'dark':'light');
  window.__themeMode=mode;
})();
</script>
</head>
<body>
<div class="loader"><div class="ring"></div></div>
<div class="stage">
  <div class="desk">
    <div class="window">
      <div class="winbar">
        <div class="dots"><i></i><i></i><i></i></div>
        <div class="wintitle"><?= e($s['seo_title']) ?></div>
        <button class="theme-btn" id="themeBtnDesk" aria-label="切换主题"><?= $themetog ?></button>
      </div>
      <div class="winbody">
        <aside class="side">
          <div class="avatar"><?= e($s['avatar']) ?></div>
          <div class="dname"><?= e($s['name']) ?></div>
          <p class="dslogan"><?= e($s['slogan']) ?></p>
          <button class="contact" data-curl="<?= e($s['contact_url']) ?>"><?= e($s['contact_text']) ?></button>
          <div class="socials"><?= $socials_html ?></div>
        </aside>
