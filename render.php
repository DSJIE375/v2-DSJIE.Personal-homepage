
<?php
/** 板块 HTML 渲染器：把 data/content.json 还原成 v25 页面结构 */
require_once __DIR__ . '/lib.php';

/** 解析雷达数据 "PHP:85" 每行一条 -> [["PHP",0.85],...] */
function parse_radar(string $s): array {
    $out = [];
    foreach (preg_split('/\r?\n/', $s) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, ':') === false) continue;
        [$k, $v] = explode(':', $line, 2);
        $v = is_numeric($v) ? (float)$v : 0;
        if ($v > 1) $v = $v / 100;
        $out[] = [trim($k), max(0, min(1, $v))];
    }
    return $out;
}

/** 是否图片链接（http(s)://、//、/、./ 或图片后缀） */
function is_img_url(string $u): bool {
    return $u !== '' && (bool)preg_match('#^(https?:)?//|^/|^\./|\.(png|jpe?g|gif|webp|svg)$#i', $u);
}

/** 顶部 Logo：图片链接显示图片；未填 → 站点名首字 */
function logo_markup(array $s): string {
    $lg = trim((string)($s['logo'] ?? ''));
    if (is_img_url($lg)) {
        return "<img src='" . e($lg) . "' alt='logo' loading='lazy'>";
    }
    return e(mb_substr((string)($s['name'] ?? ''), 0, 1, 'UTF-8'));
}

/** 头像：图片链接优先（http/相对路径/文件名兼容 uploads/ 前缀），未填 → 头像文字，再未填 → 站点名首字 */
function avatar_markup(array $s): string {
    $im = trim((string)($s['avatar_img'] ?? ''));
    if ($im !== '') {
        if (preg_match('#^(https?:)?//|^/|^\./#i', $im)) {
            $src = $im;
        } else {
            $src = 'uploads/' . ltrim($im, '/');
        }
        return "<img src='" . e($src) . "' alt='avatar' loading='lazy'>";
    }
    $tx = trim((string)($s['avatar'] ?? ''));
    if ($tx !== '') return e($tx);
    return e(mb_substr((string)($s['name'] ?? ''), 0, 1, 'UTF-8'));
}

/** 板块标题头 */
function sect_head(string $cn, string $en): string {
    return "<div class='shead'><h2>" . e($cn) . "</h2><span>" . e($en) . "</span></div>";
}

/** 关于我 */
function render_about(array $d): string {
    $a = $d['about'];
    $intro = trim((string)($a['intro'] ?? ''));
    $intro = strip_tags($intro, '<strong><em><br><a><code>');
    $skills = '';
    foreach (preg_split('/[,，]/', (string)($a['skills'] ?? '')) as $sk) {
        $sk = trim($sk);
        if ($sk !== '') $skills .= '<span class="sk">' . e($sk) . '</span>';
    }
    return "<section class='sect'>" . sect_head('关于我', 'INTRO')
         . "<p class='para'>" . $intro . "</p>"
         . "<div class='skwrap'>" . $skills . "</div><div class='radar'></div></section>";
}

/** 作品（封面：图片链接显示图；未填 → 标题首字；有跳转链接时显示入口芯片） */
function render_works(array $d): string {
    $h = "<section class='sect'>" . sect_head('作品', 'WORKS');
    foreach ($d['works'] as $w) {
        $link = trim((string)($w['link'] ?? ''));
        $tag = $link !== '' ? 'a' : 'div';
        $attr = $link !== '' ? " href='" . e($link) . "' target='_blank' rel='noopener'" : '';
        $title = (string)($w['title'] ?? '');
        $pic = trim((string)($w['pic'] ?? ''));
        if (is_img_url($pic)) {
            $thumb = "<img src='" . e($pic) . "' alt='" . e($title) . "' loading='lazy'>";
        } else {
            $thumb = '<span>' . e(mb_substr($title, 0, 1, 'UTF-8')) . '</span>';
        }
        $chip = $link !== '' ? "<span class='wlink'>跳转链接 ↗</span>" : '';
        $h .= "<{$tag} class='wcard'{$attr}><div class='wthumb'>" . $thumb . "</div>" . $chip
            . "<div class='winfo'><h3>" . e($title) . "</h3>"
            . "<p>" . e($w['desc'] ?? '') . "</p>"
            . "<span class='wtag'>" . e($w['tech'] ?? '') . "</span>" . "</div></{$tag}>";
    }
    $h = fold_apply($h, 'wcard', 3);
    return $h . '</section>';
}

/** 随笔（整文随卡片隐藏携带，点击弹阅读器） */
function render_essays(array $d): string {
    $h = "<section class='sect'>" . sect_head('随笔', 'NOTES');
    foreach ($d['essays'] as $es) {
        $full = trim((string)($es['content'] ?? ''));
        $h .= "<div class='ecard'><h3>" . e($es['title'] ?? '') . "</h3>"
            . "<p>" . e($es['excerpt'] ?? '') . "</p>"
            . "<span class='edate'>" . e($es['date'] ?? '') . "</span>"
            . "<span class='emore'>阅读全文 ›</span>"
            . ($full !== '' ? "<div class='efull' hidden>" . nl2br(e($full)) . '</div>' : '')
            . '</div>';
    }
    $h = fold_apply($h, 'ecard', 3);
    return $h . '</section>';
}

/** 图库 */
function render_gallery(array $d): string {
    $h = "<section class='sect'>" . sect_head('图库', 'GALLERY') . "<div class='ggrid'>";
    foreach ($d['gallery'] as $g) {
        $cap = (string)($g['caption'] ?? '');
        $img = trim((string)($g['img'] ?? ''));
        $inner = $img !== ''
            ? "<div class='gimg'><img src='" . e($img) . "' alt='" . e($cap) . "'></div>"
            : "<div class='gimg'><span>" . e(mb_substr((string)($d['site']['name'] ?? 'D'), 0, 1, 'UTF-8')) . "</span></div>";
        $h .= '<div class="gcell">' . $inner . '<p>' . e($cap) . '</p>'
            . "<span class='gdate'>" . e($g['date'] ?? '') . '</span></div>';
    }
    $h = fold_apply($h, 'gcell', 4);
    return $h . '</div></section>';
}