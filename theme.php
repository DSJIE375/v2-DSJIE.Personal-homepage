<?php
/** 主题主色 → 整套色板派生：一个 HEX 派生浅色 + 暗色 + 系统暗色三套 CSS 变量 */

/** 两色混合：$a 占 $r，$b 占 1-$r */
function _mix(string $a, string $b, float $r): string {
    $ar = hexdec(substr($a, 1, 2)); $ag = hexdec(substr($a, 3, 2)); $ab = hexdec(substr($a, 5, 2));
    $br = hexdec(substr($b, 1, 2)); $bg = hexdec(substr($b, 3, 2)); $bb = hexdec(substr($b, 5, 2));
    return sprintf('#%02x%02x%02x',
        (int)round($ar * $r + $br * (1 - $r)),
        (int)round($ag * $r + $bg * (1 - $r)),
        (int)round($ab * $r + $bb * (1 - $r)));
}

/** 主色转 rgba（用于彩色阴影/雷达填充） */
function _rgba(string $hex, float $a): string {
    return 'rgba(' . hexdec(substr($hex, 1, 2)) . ',' . hexdec(substr($hex, 3, 2))
        . ',' . hexdec(substr($hex, 5, 2)) . ',' . $a . ')';
}

/**
 * 生成整站色板 <style>：背景/卡片/三级文字/边框/彩色阴影/雷达填充全部随主色联动
 * 输出三段：浅色(:root) / 暗色(html[data-theme="dark"]) / 系统暗色(media，后台页面使用)
 * 按钮字色按主色亮度自动选黑/白
 */
function theme_palette_css(string $hex): string {
    $lr = hexdec(substr($hex, 1, 2));
    $lg = hexdec(substr($hex, 3, 2));
    $lb = hexdec(substr($hex, 5, 2));
    $tx = ((0.299 * $lr + 0.587 * $lg + 0.114 * $lb) / 255) > 0.62 ? '#111827' : '#ffffff';

    // 浅色主题（默认黑白灰体系 → 主色调）
    $light = ':root{'
        . '--bg:' . _mix($hex, '#ffffff', 0.05) . ';'
        . '--tint:' . _mix($hex, '#ffffff', 0.10) . ';'
        . '--card:' . _mix($hex, '#ffffff', 0.03) . ';'
        . '--t1:' . _mix($hex, '#111827', 0.15) . ';'
        . '--t2:' . _mix($hex, '#4b5563', 0.30) . ';'
        . '--t3:' . _mix($hex, '#9ca3af', 0.35) . ';'
        . '--border:' . _mix($hex, '#e5e7eb', 0.35) . ';'
        . '--btnbg:' . $hex . ';--btntx:' . $tx . ';'
        . '--navon:' . $hex . ';--navtx:' . $tx . ';'
        . '--winbord:' . _mix($hex, '#ececec', 0.5) . ';'
        . '--shadow:0 20px 50px ' . _rgba($hex, 0.12) . ';'
        . '--sh8:0 6px 18px ' . _rgba($hex, 0.10) . ';'
        . '--sh14:0 18px 40px ' . _rgba($hex, 0.16) . ';'
        . '--sh18:0 1px 3px ' . _rgba($hex, 0.20) . ';'
        . '--rdfill:' . _rgba($hex, 0.18) . ';'
        . '}';

    // 暗色主题（同主色的暗调派生）
    $darkV = '--bg:' . _mix($hex, '#0a0a0a', 0.06) . ';'
        . '--tint:' . _mix($hex, '#171717', 0.12) . ';'
        . '--card:' . _mix($hex, '#171717', 0.06) . ';'
        . '--t1:' . _mix($hex, '#f5f5f5', 0.12) . ';'
        . '--t2:' . _mix($hex, '#a3a3a3', 0.25) . ';'
        . '--t3:' . _mix($hex, '#737373', 0.30) . ';'
        . '--border:' . _mix($hex, '#2a2a2a', 0.45) . ';'
        . '--btnbg:' . $hex . ';--btntx:' . $tx . ';'
        . '--navon:' . $hex . ';--navtx:' . $tx . ';'
        . '--winbord:' . _mix($hex, '#333333', 0.5) . ';'
        . '--shadow:0 20px 50px ' . _rgba($hex, 0.40) . ';'
        . '--sh8:0 6px 18px ' . _rgba($hex, 0.16) . ';'
        . '--sh14:0 18px 40px ' . _rgba($hex, 0.30) . ';'
        . '--sh18:0 1px 3px ' . _rgba($hex, 0.30) . ';'
        . '--rdfill:' . _rgba($hex, 0.30) . ';';

    $dark  = 'html[data-theme="dark"]{' . $darkV . '}';
    $media = '@media (prefers-color-scheme: dark){html:not([data-theme="light"]){' . $darkV . '}}';

    return '<style id="themeColorCss">' . $light . $dark . $media . '</style>';
}
