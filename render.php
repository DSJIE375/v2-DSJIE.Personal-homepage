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

/** 作品 */
function render_works(array $d): string {
    $h = "<section class='sect'>" . sect_head('作品', 'WORKS');
    foreach ($d['works'] as $w) {
        $link = trim((string)($w['link'] ?? ''));
        $tag = $link !== '' ? 'a' : 'div';
        $attr = $link !== '' ? " href='" . e($link) . "' target='_blank' rel='noopener'" : '';
        $h .= "<{$tag} class='wcard'{$attr}><div class='wthumb'><span>W</span></div>"
            . "<div class='winfo'><h3>" . e($w['title'] ?? '') . "</h3>"
            . "<p>" . e($w['desc'] ?? '') . "</p>"
            . "<span class='wtag'>" . e($w['tech'] ?? '') . "</span></div></{$tag}>";
    }
    return $h . '</section>';
}

/** 随笔 */
function render_essays(array $d): string {
    $h = "<section class='sect'>" . sect_head('随笔', 'NOTES');
    foreach ($d['essays'] as $es) {
        $h .= "<div class='ecard'><h3>" . e($es['title'] ?? '') . "</h3>"
            . "<p>" . e($es['excerpt'] ?? '') . "</p>"
            . "<span class='edate'>" . e($es['date'] ?? '') . "</span></div>";
    }
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
            : "<div class='gimg'><span>DSJIE</span></div>";
        $h .= '<div class="gcell">' . $inner . '<p>' . e($cap) . '</p>'
            . "<span class='gdate'>" . e($g['date'] ?? '') . '</span></div>';
    }
    return $h . '</div></section>';
}
