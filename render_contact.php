<?php
/** 留言板上方联系信息卡：脱敏手机号 / 邮箱 / 地址（读全局站点设置，留空字段自动隐藏） */

/** 手机号脱敏：11 位手机号 → 前3位****后4位；已含 * 的原样；其余保留分段 */
function mask_phone(string $p): string {
    $p = trim($p);
    if ($p === '') return '';
    if (strpos($p, '*') !== false) return $p;
    $d = preg_replace('/\D+/', '', $p);
    if (strlen($d) === 11 && isset($d[0]) && $d[0] === '1') {
        return substr($d, 0, 3) . '****' . substr($d, 7, 4);
    }
    if (strlen($d) >= 7) {
        return substr($d, 0, 3) . str_repeat('*', 4) . substr($d, -4);
    }
    return $p;
}

/** 输出联系卡 HTML；三项全空返回空串（整卡不显示） */
function contact_card(): string {
    $s  = $GLOBALS['s'] ?? [];
    $ph = trim((string)($s['ct_phone'] ?? ''));
    $em = trim((string)($s['ct_email'] ?? ''));
    $ad = trim((string)($s['ct_addr'] ?? ''));
    if ($ph === '' && $em === '' && $ad === '') return '';

    $icoPhone = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
    $icoMail  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>';
    $icoPin   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>';

    $h = '<div class="ctcard"><div class="cttitle">联系方式 · CONTACT</div>';

    if ($ph !== '') {
        $vals = [];
        foreach ((preg_split('/[、,，;；\n]+/u', $ph) ?: []) as $one) {
            $one = trim($one);
            if ($one !== '') $vals[] = e(mask_phone($one));
        }
        if ($vals) {
            $h .= '<div class="ctrow"><span class="ctic">' . $icoPhone . '</span><span class="ctk">手机</span>'
                . '<span class="ctv">' . implode('　', $vals) . '</span></div>';
        }
    }
    if ($em !== '') {
        $vals = [];
        foreach ((preg_split('/[、,，;；\n]+/u', $em) ?: []) as $one) {
            $one = trim($one);
            if ($one !== '') $vals[] = "<a href='mailto:" . e($one) . "'>" . e($one) . '</a>';
        }
        if ($vals) {
            $h .= '<div class="ctrow"><span class="ctic">' . $icoMail . '</span><span class="ctk">邮箱</span>'
                . '<span class="ctv">' . implode('　', $vals) . '</span></div>';
        }
    }
    if ($ad !== '') {
        $h .= '<div class="ctrow"><span class="ctic">' . $icoPin . '</span><span class="ctk">地址</span>'
            . '<span class="ctv">' . e($ad) . '</span></div>';
    }
    return $h . '</div>';
}
