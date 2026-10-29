<?php
/** DSJIE 个人主页 - 公共装配：数据加载 + 桌面侧栏/移动端共享变量 */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/render2.php';
require_once __DIR__ . '/render_contact.php';
require_once __DIR__ . '/theme.php';
$data = load_data();
$s = $data['site'];
$sections = build_sections($data);
$radar = parse_radar($data['about']['radar'] ?? '');
$gbook = public_messages($data);
$content_version = is_file(DATA_FILE) ? (string)hash_file('sha256', DATA_FILE) : '';
$site_version = hash('sha256', (string)json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$about_html = $sections[0]['html'];
$enc = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

// 头像：优先显示上传的图片，未上传则显示文字
$avimg = trim((string)($s['avatar_img'] ?? ''));
$avatar_html = $avimg !== ''
    ? "<img src='" . e($avimg) . "' alt='" . e($s['name']) . "'>"
    : e((string)($s['avatar'] ?? ''));

// 口号（打字机两句）/ 欢迎语 / 博客按钮 / ICP + 公安备案
$sl1 = trim((string)($s['slogan1'] ?? ($s['slogan'] ?? '')));
$sl2 = trim((string)($s['slogan2'] ?? ''));
$welcome = trim((string)($s['welcome'] ?? ''));
$blog_text = trim((string)($s['blog_text'] ?? ''));
$blog_url = trim((string)($s['blog_url'] ?? ''));
$icp_t = trim((string)($s['icp_text'] ?? ''));
$icp_u = trim((string)($s['icp_url'] ?? ''));
if ($icp_u === '') $icp_u = 'https://beian.miit.gov.cn/';
$gp_t = trim((string)($s['gp_text'] ?? ''));
$gp_u = trim((string)($s['gp_url'] ?? ''));
if ($gp_u === '') $gp_u = 'https://www.beian.gov.cn/';
$icp_links = [];
if ($icp_t !== '') {
    $icp_links[] = "<a href='" . e($icp_u) . "' target='_blank' rel='noopener nofollow'>" . e($icp_t) . '</a>';
}
if ($gp_t !== '') {
    $icp_links[] = "<a href='" . e($gp_u) . "' target='_blank' rel='noopener nofollow'>" . e($gp_t) . '</a>';
}
$icp_html = $icp_links ? "<div class='icp'>" . implode('', array_map(function ($x) { return '<div>' . $x . '</div>'; }, $icp_links)) . '</div>' : '';
$slogans_js = json_encode(array_values(array_filter([$sl1, $sl2], function ($x) { return $x !== ''; })), $enc);

// 站长自定义整站配色（后台 theme_color 字段；空 = 默认黑白灰）
// 宽容解析：接受 #abc / #rrggbb / 无 # 的 rrggbb / 大小写混写，全部归一化后由 theme.php 派生两套色板
$tc = trim((string)($s['theme_color'] ?? ''));
$theme_css = '';
$raw = ltrim($tc, '#');
if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $tc)) {
    $hex = strlen($tc) === 4 ? '#' . $tc[1] . $tc[1] . $tc[2] . $tc[2] . $tc[3] . $tc[3] : $tc;
    $theme_css = theme_palette_css($hex);
} elseif (preg_match('/^[0-9a-fA-F]{6}$/', $raw)) {
    $theme_css = theme_palette_css('#' . $raw);
} elseif (preg_match('/^[0-9a-fA-F]{3}$/', $raw)) {
    $theme_css = theme_palette_css('#' . $raw[0] . $raw[0] . $raw[1] . $raw[1] . $raw[2] . $raw[2]);
}

// 后台 head 输出变量：登录页（_a2 view_login）与管理页（_a3b）在 admin_palette() 后输出它；
// 未设置背景图时为空串（后台回原样卡片底）。内容 = 背景图 + 明暗遮罩 + 后台面板半透明规则。
$site_bg_js = '';

// 全站背景图（后台 bg_image 字段：图片链接或 uploads/xx.jpg 路径；留空 = 不显示背景图）
// 关键：palette 自带 <style> 标签且 index_part1 是裸输出，背景块必须自带完整 <style> 否则不执行
// 背景图模式专属适配（只在设置了背景图时输出，未设置时对原样式零影响）：
//   .mob 透明化 —— 移动端容器原是实底 var(--bg)，会把背景图整块盖死
//   .window 毛玻璃 —— 桌面主窗原是实底卡片色，改半透明+模糊让图透进来，文字仍可读
//   内容小卡保持实底（--tint），文字不直接压图
// 安全：拒绝引号/反斜杠/控制字符后单引号包裹注入 url()；明暗两主题分别配遮罩保证正文可读
// 子目录部署兼容：/uploads/... 绝对根路径在站点挂子目录时会 404，统一转为相对当前页面的路径
$bg = trim((string)($s['bg_image'] ?? ''));
if ($bg !== '' && !preg_match('/[\x00-\x1f\'"\\\\]/', $bg)) {
    if (strpos($bg, '/uploads/') === 0 && strpos($bg, '//') !== 0) {
        $bg = substr($bg, 1);
    }
    $theme_css .= '<style id="siteBgCss">/*DSJIE-BG*/body::before{content:"";position:fixed;inset:0;background:url(\'' . $bg . '\') center/cover no-repeat;z-index:-2;}'
                . 'body::after{content:"";position:fixed;inset:0;z-index:-1;pointer-events:none;background:rgba(10,12,20,.45);}'
                . '[data-theme=light] body::after{background:rgba(244,246,250,.55);}'
                . '.mob{background:transparent;}'
                . '.window{background:color-mix(in srgb,var(--card) 45%,transparent);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);}'
                . '.lbox,.awin,.aside,.panel,.ahead,.anav{background:color-mix(in srgb,var(--card) 45%,transparent);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);}'
                . '.msgbox{background:color-mix(in srgb,var(--tint) 55%,transparent);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);}'
                . '</style>';
    $site_bg_js = '<style id="siteBgBackend">/*DSJIE-BG*/body::before{content:"";position:fixed;inset:0;background:url(\'' . $bg . '\') center/cover no-repeat;z-index:-2;}'
                . 'body::after{content:"";position:fixed;inset:0;z-index:-1;pointer-events:none;background:rgba(10,12,20,.45);}'
                . '[data-theme=light] body::after{background:rgba(244,246,250,.55);}'
                . 'body{background:transparent;}'
                . '.lbox,.window,.side,.navcol,.panel{background:color-mix(in srgb,var(--card) 45%,transparent);}'
                . '.msgbox{background:color-mix(in srgb,var(--tint) 55%,transparent);}'
                . '</style>';
}
// 社交图标（动态条目：图标+名称胶囊；条目来自 content.json 的 socials，后台可增删）
require_once __DIR__ . "/inc/social_icons.php";
$ico = social_icon_ico();
$svgmap = social_icon_map();
/* 旧图标表已由 inc/social_icons.php 接管（保留注释备查）：
$ico = "viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'";
$svgs = [
  "<svg $ico><path d='M9 19c-4 1.5-4-2-6-2.5M15 21v-3.2c0-1 .3-1.6.9-2.2C19 14.6 21 12.6 21 9.6A5.4 5.4 0 0 0 19.3 5.4 5 5 0 0 0 19.4 2s-1.6-.2-4.4 1.6A13 13 0 0 0 4.6 2S4 1.6 2 2a5 5 0 0 0 .1 3.4A5.4 5.4 0 0 0 .5 9.6c0 3 2 5 6 6.2.6.6.9 1.2.9 2.2V21'/></svg>",
  "<svg $ico><rect x='3' y='5' width='18' height='14' rx='2'/><path d='M3 7l9 6 9-6'/></svg>",
  "<svg $ico><path d='M12 20h9'/><path d='M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1.4-4-1.4z'/></svg>",
  "<svg $ico><path d='M4 11a9 9 0 0 1 9 9M4 4a16 16 0 0 1 16 16'/><circle cx='5.9' cy='9.9' r='1.4' fill='currentColor' stroke='none'/><circle cx='14.1' cy='9.9' r='1.4' fill='currentColor' stroke='none'/><circle cx='9.9' cy='14.1' r='1.4' fill='currentColor' stroke='none'/></svg>",
  "<svg $ico><path d='M8.5 3.5C4.9 3.5 2 5.9 2 8.9c0 1.7 1 3.2 2.4 4.1l-.7 2.3 2.7-1.4c.7.2 1.4.3 2.1.3 3.6 0 6.5-2.4 6.5-5.3S12.1 3.5 8.5 3.5z'/><path d='M15.5 9c-3.1 0-5.6 2.1-5.6 4.6 0 1.4.8 2.7 2.1 3.5l-.6 1.9 2.3-1.2c.6.2 1.2.2 1.8.2 3.1 0 5.6-2.1 5.6-4.6S18.6 9 15.5 9z'/></svg>",
  "<svg $ico><path d='M10.5 3.5v10.4a3.6 3.6 0 1 1-3.6-3.6'/><path d='M10.5 3.5c.5 2.7 2.5 4.7 5.3 5'/></svg>",
];
$svgmap = [
  'GitHub' => $svgs[0], '邮箱' => $svgs[1], '博客' => $svgs[2], 'RSS' => $svgs[3],
  '微信' => $svgs[4], '抖音' => $svgs[5], 'QQ' => "<svg $ico><circle cx='11' cy='13' r='7.5'/><path d='M16.3 18.3 19.5 21.5'/></svg>",
  'Bilibili' => "<svg $ico><rect x='3' y='7' width='18' height='13' rx='3'/><path d='M7.5 3l3 4M16.5 3l-3 4'/><path d='M9 12.5v2M15 12.5v2'/></svg>",
  '微博' => "<svg $ico><ellipse cx='10.5' cy='14' rx='7.5' ry='5.5'/><circle cx='10.5' cy='14' r='2'/><path d='M16.5 4.5a5 5 0 0 1 4.5 5'/><path d='M17 8a2 2 0 0 1 1.8 1.8'/></svg>",
  '小红书' => "<svg $ico><rect x='5' y='3' width='14' height='18' rx='2.5'/><path d='M8.5 8.5h7M8.5 12h7M8.5 15.5h4'/></svg>",
  '知乎' => "<svg $ico><path d='M4.5 6h10L4.5 18h10'/><circle cx='18' cy='6.5' r='1.6'/></svg>",
  'X' => "<svg $ico><path d='M4.5 4.5l15 15M19.5 4.5l-15 15'/></svg>",
  'YouTube' => "<svg $ico><rect x='2.5' y='5.5' width='19' height='13' rx='4'/><path d='M10.2 9.3l4.6 2.7-4.6 2.7z'/></svg>",
  'Telegram' => "<svg $ico><path d='M21 4L3.5 10.8l5.6 1.9 2.2 5.6 2.8-3.4 4.6 3.4z'/><path d='M9.1 12.7L17.5 6'/></svg>",
  'Instagram' => "<svg $ico><rect x='3' y='3' width='18' height='18' rx='5'/><circle cx='12' cy='12' r='4.2'/><circle cx='17.2' cy='6.8' r='1.1' fill='currentColor' stroke='none'/></svg>",
  'Discord' => "<svg $ico><path d='M8.2 17.6c-2.1-2.6-2.6-6.3-1.8-9.2.6-1.1 2.1-1.9 4-2.2l.6 1.1c1.2-.2 2.5-.2 3.7 0l.6-1.1c1.9.3 3.4 1.1 4 2.2.8 2.9.3 6.6-1.8 9.2L15 16a8.6 8.6 0 0 1-4.4 0z'/><circle cx='9.8' cy='12.3' r='1.3' fill='currentColor' stroke='none'/><circle cx='14.2' cy='12.3' r='1.3' fill='currentColor' stroke='none'/></svg>",
  'LinkedIn' => "<svg $ico><rect x='3' y='3' width='18' height='18' rx='3'/><path d='M7.2 10.5V17'/><circle cx='7.2' cy='7.6' r='1.1' fill='currentColor' stroke='none'/><path d='M11.5 17v-4a2.4 2.4 0 0 1 4.8 0v4M11.5 10.5V17'/></svg>",
];
*/
$socials_html = '';
foreach ((array)($s['socials'] ?? []) as $it) {
  $url = trim((string)($it['url'] ?? ''));
  $lb  = trim((string)($it['label'] ?? ''));
  if ($lb === '') continue;
  if ($url === '') $url = '#';
  $sv = $svgmap[$lb] ?? "<svg $ico><path d='M14 4h6v6'/><path d='M20 4 10 14'/><path d='M18 13v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6'/></svg>";
  $socials_html .= "<a class='sico' href='" . e($url) . "' aria-label='" . e($lb) . "' title='" . e($lb) . "'>" . $sv . '<span>' . e($lb) . '</span></a>';
}
$themetog = "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.8' stroke-linecap='round'><circle cx='12' cy='12' r='4'/><path d='M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4-1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4'/></svg>";
$first = mb_substr((string)$s['name'], 0, 1, 'UTF-8');