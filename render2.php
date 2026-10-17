<?php
/** 板块渲染器（下）：下载 / 留言板 / 动态 / 友链 + 板块总装 */

/** 下载 */
function render_downloads(array $d): string {
    $h = "<section class='sect'>" . sect_head('下载', 'FILES');
    foreach ($d['downloads'] as $f) {
        $file = trim((string)($f['file'] ?? ''));
        $h .= "<div class='drow'><div class='dinfo'>"
            . "<span class='dname'>" . e($f['title'] ?? '') . '</span>'
            . "<span class='dsize'>" . e($f['note'] ?? '') . '</span></div>'
            . "<button class='dbtn' type='button' data-url='" . e($file) . "' data-name='"
            . e($f['title'] ?? '') . "'>下载</button></div>";
    }
    return $h . '</section>';
}

/** 留言板（表单 + 容器，消息由前端 gPaint 用 window.GBOOK 渲染） */
function render_guestbook(array $d): string {
    return "<section class='sect'>" . sect_head('留言板', 'GUESTBOOK') .
        "<form class='gform' autocomplete='off'>" .
        "<input class='gif gqq' type='text' inputmode='numeric' maxlength='12' placeholder='QQ 号（5-12 位数字）'>" .
        "<input class='gif gemail' type='text' placeholder='邮箱（必填）'>" .
        "<textarea class='gif txt gtext' rows='3' maxlength='200' placeholder='留言内容（必填，200 字内）'></textarea>" .
        "<button class='gsubmit' type='submit'>发表留言</button>" .
        "</form><div class='gmsgs'></div></section>";
}

/** 动态 */
function render_updates(array $d): string {
    $h = "<section class='sect'>" . sect_head('动态', 'UPDATES') . "<div class='timeline'>";
    foreach ($d['updates'] as $u) {
        $h .= "<div class='tlitem'><span class='tldate'>" . e($u['date'] ?? '') . '</span>'
            . '<div class="tlbody"><h4>' . e($u['title'] ?? '') . '</h4>'
            . '<p>' . e($u['text'] ?? '') . '</p></div></div>';
    }
    return $h . '</div></section>';
}

/** 友情链接 */
function render_friends(array $d): string {
    $h = "<section class='sect'>" . sect_head('友情链接', 'FRIENDS') . "<div class='friends'>";
    foreach ($d['friends'] as $fr) {
        $name = (string)($fr['name'] ?? '');
        $av = trim((string)($fr['avatar'] ?? ''));
        if ($av === '') $av = mb_substr($name, 0, 1, 'UTF-8');
        $url = trim((string)($fr['url'] ?? ''));
        if ($url === '') $url = '#';
        $h .= "<a class='fcard' href='" . e($url) . "' target='_blank' rel='noopener'>"
            . "<span class='favatar'>" . e($av) . '</span>'
            . '<div class="finfo"><h4>' . e($name) . '</h4>'
            . '<p>' . e($fr['desc'] ?? '') . '</p></div></a>';
    }
    return $h . '</div></section>';
}

/** 全部板块（顺序固定，与导航 data-i 一一对应） */
function build_sections(array $d): array {
    return [
        ['id' => 'about',     'html' => render_about($d)],
        ['id' => 'works',     'html' => render_works($d)],
        ['id' => 'essays',    'html' => render_essays($d)],
        ['id' => 'gallery',   'html' => render_gallery($d)],
        ['id' => 'downloads', 'html' => render_downloads($d)],
        ['id' => 'guestbook', 'html' => render_guestbook($d)],
        ['id' => 'updates',   'html' => render_updates($d)],
        ['id' => 'friends',   'html' => render_friends($d)],
    ];
}
