<?php
/** 板块渲染器（下）：下载 / 留言板 / 动态 / 友链 + 板块总装 */

function fold_apply(string $h, string $tag, int $limit): string {
    $total = substr_count($h, $tag);
    if ($total <= $limit) return $h;

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $h, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $nodes = $dom->getElementsByTagName('*');
    $idx = 0;
    foreach ($nodes as $node) {
        if (!$node->hasAttribute('class')) continue;
        $classes = preg_split('/\s+/', trim($node->getAttribute('class')));
        if (!in_array($tag, $classes, true)) continue;
        if ($idx >= $limit) {
            $classes[] = 'folded';
            $node->setAttribute('class', trim(implode(' ', $classes)));
        }
        $idx++;
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) return $dom->saveHTML();
    $inner = '';
    foreach ($body->childNodes as $child) {
        $inner .= $dom->saveHTML($child);
    }
    return $inner;
}

/** 格式化文件大小 */
function format_file_size(int $bytes): string {
    return $bytes >= 1048576
        ? round($bytes / 1048576, 1) . ' MB'
        : ($bytes >= 1024 ? (int)ceil($bytes / 1024) . ' KB' : $bytes . ' B');
}

/** 下载（文件大小对 files/ 下真实文件自动读取 filesize，文件不存在时回退手填 note） */
function render_downloads(array $d): string {
    $h = "<section class='sect'>" . sect_head('下载', 'FILES');
    foreach ($d['downloads'] as $f) {
        $file = trim((string)($f['file'] ?? ''));
        $note = trim((string)($f['note'] ?? ''));
        $size = '';
        if ($file !== '') {
            $full = __DIR__ . '/' . $file;
            if (is_file($full)) {
                $bytes = (int)@filesize($full);
                $size = format_file_size($bytes);
            }
        }
        if ($size !== '') {
            $desc = $note;
            if ($note !== '' && mb_strpos($note, '·') !== false) {
                $desc = trim(mb_substr($note, mb_strpos($note, '·') + 1));
            }
            $dsize = $size . ($desc !== '' ? ' · ' . $desc : '');
        } else {
            $dsize = $note;
        }
        $h .= "<div class='drow'><div class='dinfo'>"
            . "<span class='dname'>" . e($f['title'] ?? '') . '</span>'
            . "<span class='dsize'>" . e($dsize) . '</span></div>'
            . ($file !== '' ? "<button class='dbtn' type='button' data-url='" . e($file) . "' data-name='" . e($f['title'] ?? '') . "'>下载</button>" : '<span class="dwait">敬请期待</span>') . '</div>';
    }
    $h = fold_apply($h, 'drow', 5);
    return $h . '</section>';
}

/** App 介绍和安装包下载 */
function render_app(array $d): string {
    $site = $d['site'] ?? [];
    $title = trim((string)($site['app_title'] ?? ''));
    $title = $title !== '' ? $title : '我的 App';
    $version = trim((string)($site['app_version'] ?? ''));
    $intro = trim((string)($site['app_intro'] ?? ''));
    $usage = preg_split('/\r\n|\r|\n/', trim((string)($site['app_use'] ?? '')));
    $file = trim((string)($site['app_file'] ?? ''));
    $size = trim((string)($site['app_size'] ?? ''));

    if (preg_match('#^files/(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9._/-]+$#', $file)) {
        $filesRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'files');
        $fullPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
        if ($filesRoot !== false && $fullPath !== false
            && strpos($fullPath, $filesRoot . DIRECTORY_SEPARATOR) === 0 && is_file($fullPath)) {
            $bytes = filesize($fullPath);
            if ($bytes !== false) $size = format_file_size((int)$bytes);
        }
    }

    $usageItems = '';
    foreach ($usage as $item) {
        $item = trim($item);
        if ($item !== '') $usageItems .= '<li>' . e($item) . '</li>';
    }
    $download = $file !== ''
        ? "<button class='dbtn app-download' type='button' data-url='" . e($file) . "' data-name='" . e($title) . " 安装包'>立即下载</button>"
        : "<button class='dbtn app-download' type='button' disabled>安装包暂未提供</button>";

    return "<section class='sect'>" . sect_head('App 下载', 'APP')
        . "<div class='app-card'><div class='app-head'><div class='app-icon' aria-hidden='true'>"
        . "<svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'><rect x='5' y='2.5' width='14' height='19' rx='3'/><path d='M9 5.5h6M11 18.5h2'/></svg>"
        . "</div><div class='app-title-wrap'><span class='app-kicker'>PERSONAL APP</span><h3>" . e($title) . "</h3>"
        . ($version !== '' ? "<span class='app-version'>版本 " . e($version) . '</span>' : '')
        . "</div></div><div class='app-details'><article><h4>应用介绍</h4><p>"
        . ($intro !== '' ? nl2br(e($intro)) : '应用介绍尚未发布。')
        . "</p></article><article><h4>应用用途</h4>"
        . ($usageItems !== '' ? '<ul>' . $usageItems . '</ul>' : '<p>用途说明尚未发布。</p>')
        . "</article></div><div class='app-download-row'><div class='app-file-meta'><span>安装包大小</span><strong>"
        . ($size !== '' ? e($size) : '暂未提供')
        . "</strong></div>" . $download . '</div></div></section>';
}

/** 留言板（表单 + 容器，消息由前端 gPaint 用 window.GBOOK 渲染） */
function render_guestbook(array $d): string {
    return "<section class='sect'>" . sect_head('留言板', 'GUESTBOOK') . contact_card() .
        "<form class='gform' autocomplete='off'>" .
        "<input class='gif gqq' type='text' inputmode='numeric' maxlength='12' placeholder='QQ 号（5-12 位数字）'>" .
        "<input class='gif gemail' type='text' placeholder='邮箱（必填）'>" .
        "<textarea class='gif txt gtext' rows='3' maxlength='200' placeholder='留言内容（必填，200 字内）'></textarea>" .
        "<input class='gif gpass' hidden type='password' maxlength='30' placeholder='站长/开发者身份校验码'>" .
        "<button class='gsubmit' type='submit'>发表留言</button>" .
        "</form><div class='gmsgs'></div></section>";
}

/** 版权模块（将原页脚版权迁移到单独板块展示） */
function render_copyright(array $d): string {
    $footer = trim((string)($d['site']['footer'] ?? ''));
    if ($footer === '') {
        return "<section class='sect'>" . sect_head('版权', 'COPYRIGHT') . "<div class='copybox'><p>暂无版权信息</p></div></section>";
    }
    $items = preg_split('/\\R/u', $footer, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $items = array_values(array_filter(array_map('trim', $items), static function ($item) {
        return $item !== '';
    }));
    $items = array_reverse($items);
    $boxes = '';
    foreach ($items as $item) {
        $item = strip_tags($item, '<strong><em><br><a><code>');
        $boxes .= "<div class='copybox'><p>" . $item . '</p></div>';
    }
    return "<section class='sect'>" . sect_head('版权', 'COPYRIGHT') . $boxes . '</section>';
}

/** 动态 */
function render_updates(array $d): string {
    $h = "<section class='sect'>" . sect_head('动态', 'UPDATES') . "<div class='timeline'>";
    foreach ($d['updates'] as $u) {
        $h .= "<div class='tlitem'><span class='tldate'>" . e(str_replace('.', '-', (string)($u['date'] ?? ''))) . '</span>'
            . '<div class="tlbody"><h4>' . e($u['title'] ?? '') . '</h4>'
            . '<p>' . e($u['text'] ?? '') . '</p></div></div>';
    }
    $h = fold_apply($h, 'tlitem', 5);
    return $h . '</div></section>';
}

/** 友情链接（头像字段填图片链接 → 显示图片；留单字 → 显示文字；空 → 取首字） */
function render_friends(array $d): string {
    $h = "<section class='sect'>" . sect_head('友情链接', 'FRIENDS') . "<div class='friends'>";
    foreach ($d['friends'] as $fr) {
        $name = (string)($fr['name'] ?? '');
        $av = trim((string)($fr['avatar'] ?? ''));
        $url = trim((string)($fr['url'] ?? ''));
        if ($url === '') $url = '#';
        if ($av === '') {
            $avh = e(mb_substr($name, 0, 1, 'UTF-8'));
        } elseif (preg_match('#^(https?:)?//|^/|^\./|\.(png|jpe?g|gif|webp|svg)$#i', $av)) {
            $avh = "<img src='" . e($av) . "' alt='" . e($name) . "' loading='lazy'>";
        } else {
            $avh = e($av);
        }
        $h .= "<a class='fcard' href='" . e($url) . "' target='_blank' rel='noopener'>"
            . "<span class='favatar'>" . $avh . '</span>'
            . '<div class="finfo"><h4>' . e($name) . '</h4>'
            . '<p>' . e($fr['desc'] ?? '') . '</p></div></a>';
    }
    $h = fold_apply($h, 'fcard', 6);
    return $h . '</div></section>';
}

/** 全部板块（顺序固定，与导航 data-i 一一对应） */
function build_sections(array $d): array {
    return [
        ['id' => 'about',     'html' => render_about($d)],
        ['id' => 'works',     'html' => render_works($d)],
        ['id' => 'essays',    'html' => render_essays($d)],
        ['id' => 'gallery',   'html' => render_gallery($d)],
        ['id' => 'updates',   'html' => render_updates($d)],
        ['id' => 'friends',   'html' => render_friends($d)],
        ['id' => 'downloads', 'html' => render_downloads($d)],
        ['id' => 'guestbook', 'html' => render_guestbook($d)],
        ['id' => 'copyright', 'html' => render_copyright($d)],
        ['id' => 'app',       'html' => render_app($d)],
    ];
}