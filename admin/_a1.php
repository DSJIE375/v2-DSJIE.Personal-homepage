<?php
/** DSJIE 个人主页 - 后台管理（模型 + 认证 + POST 路由） */
session_start();
require_once __DIR__ . '/../lib.php';

/* ---------- 内容模型：字段即后台表单项 ---------- */
$TYPES = [
  'site' => ['label' => '站点设置', 'kind' => 'settings', 'fields' => [
    ['key'=>'seo_title','label'=>'网站标题','type'=>'text'],
    ['key'=>'name','label'=>'站点名（Logo/页头）','type'=>'text'],
    ['key'=>'avatar','label'=>'头像文字','type'=>'text'],
    ['key'=>'slogan','label'=>'口号','type'=>'text'],
    ['key'=>'contact_text','label'=>'联系按钮文字','type'=>'text'],
    ['key'=>'contact_url','label'=>'联系方式链接（mailto: 或 https:）','type'=>'text'],
    ['key'=>'footer','label'=>'页脚版权','type'=>'text'],
    ['key'=>'seo_desc','label'=>'SEO 描述','type'=>'textarea'],
    ['key'=>'seo_keywords','label'=>'SEO 关键词','type'=>'text'],
  ]],
  'about' => ['label' => '关于我', 'kind' => 'settings', 'fields' => [
    ['key'=>'intro','label'=>'简介（支持 <strong> 加粗）','type'=>'html'],
    ['key'=>'skills','label'=>'技能标签（逗号分隔）','type'=>'text'],
    ['key'=>'radar','label'=>'雷达图（每行一条：名称:0-100）','type'=>'textarea'],
  ]],
  'works' => ['label' => '作品', 'kind' => 'list', 'fields' => [
    ['key'=>'title','label'=>'标题','type'=>'text'],
    ['key'=>'desc','label'=>'简介','type'=>'textarea'],
    ['key'=>'tech','label'=>'技术栈','type'=>'text'],
    ['key'=>'link','label'=>'链接（空=不跳转）','type'=>'text'],
  ]],
  'essays' => ['label' => '随笔', 'kind' => 'list', 'fields' => [
    ['key'=>'title','label'=>'标题','type'=>'text'],
    ['key'=>'date','label'=>'日期（2026.10.16）','type'=>'text'],
    ['key'=>'excerpt','label'=>'摘要','type'=>'textarea'],
    ['key'=>'content','label'=>'正文','type'=>'big'],
  ]],
  'gallery' => ['label' => '图库', 'kind' => 'list', 'fields' => [
    ['key'=>'caption','label'=>'图片说明','type'=>'text'],
    ['key'=>'date','label'=>'日期（2026.10）','type'=>'text'],
    ['key'=>'img','label'=>'图片','type'=>'upload','dir'=>'uploads','accept'=>'image/*'],
  ]],
  'downloads' => ['label' => '下载', 'kind' => 'list', 'fields' => [
    ['key'=>'title','label'=>'文件名','type'=>'text'],
    ['key'=>'note','label'=>'说明（128 KB · 简历）','type'=>'text'],
    ['key'=>'file','label'=>'文件','type'=>'upload','dir'=>'files','accept'=>'*/*'],
  ]],
  'updates' => ['label' => '动态', 'kind' => 'list', 'fields' => [
    ['key'=>'date','label'=>'日期','type'=>'text'],
    ['key'=>'title','label'=>'标题','type'=>'text'],
    ['key'=>'text','label'=>'内容','type'=>'textarea'],
  ]],
  'friends' => ['label' => '友链', 'kind' => 'list', 'fields' => [
    ['key'=>'name','label'=>'名称','type'=>'text'],
    ['key'=>'url','label'=>'网址','type'=>'text'],
    ['key'=>'desc','label'=>'简介','type'=>'text'],
    ['key'=>'avatar','label'=>'头像字（空=取首字）','type'=>'text'],
  ]],
  'messages' => ['label' => '留言板', 'kind' => 'messages', 'fields' => []],
  'account' => ['label' => '账号', 'kind' => 'account', 'fields' => []],
];

/* ---------- 字段读写 ---------- */
function fld_post(array $f): string {
    $v = (string)($_POST[$f['key']] ?? '');
    if ($f['type'] === 'html')     return trim(strip_tags($v, '<strong><em><br><a><code>'));
    if ($f['type'] === 'big')      return mb_substr(trim($v), 0, 20000, 'UTF-8');
    if ($f['type'] === 'textarea') return mb_substr(trim(strip_tags($v)), 0, 2000, 'UTF-8');
    return clean_text($v, 300);
}

/* ---------- POST 处理 ---------- */
$err = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $do = (string)($_POST['do'] ?? '');
    if ($do === 'login') {
        if (admin_check((string)($_POST['user'] ?? ''), (string)($_POST['pass'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            header('Location: index.php'); exit;
        }
        $err = '账号或密码不正确';
    } elseif (!empty($_SESSION['admin'])) {
        $data = load_data();
        $p = preg_replace('/[^a-z]/', '', (string)($_POST['p'] ?? 'site'));
        $back = 'index.php?p=' . ($p !== '' ? $p : 'site');
        $m = '';
        if ($do === 'logout') { session_destroy(); header('Location: index.php'); exit; }

        if ($do === 'save_settings') {
            $sec = ($p === 'about') ? 'about' : 'site';
            foreach ($TYPES[$sec]['fields'] as $f) $data[$sec][$f['key']] = fld_post($f);
            if ($sec === 'site') {
                for ($i = 0; $i < 4; $i++) {
                    $data['site']['socials'][$i]['label'] = clean_text($_POST['social_label'][$i] ?? '', 20);
                    $data['site']['socials'][$i]['url']   = clean_text($_POST['social_url'][$i] ?? '', 200);
                }
            }
            $m = '已保存';
        } elseif ($do === 'save_item') {
            $t = (string)($_POST['type'] ?? '');
            if (isset($TYPES[$t]) && $TYPES[$t]['kind'] === 'list') {
                $id = (string)($_POST['id'] ?? '');
                $item = null; $idx = -1;
                foreach ($data[$t] as $k => $it) if (($it['id'] ?? '') === $id) { $item = $it; $idx = $k; break; }
                if ($item === null) $item = ['id' => nid()];
                foreach ($TYPES[$t]['fields'] as $f) {
                    if ($f['type'] === 'upload') {
                        if (!empty($_FILES['up']['name'])) {
                            $np = handle_upload($_FILES['up'], $f['dir']);
                            if ($np) $item[$f['key']] = $np;
                        }
                        if (!isset($item[$f['key']])) $item[$f['key']] = (string)($_POST['cur'] ?? '');
                    } else {
                        $item[$f['key']] = fld_post($f);
                    }
                }
                if ($idx >= 0) $data[$t][$idx] = $item; else $data[$t][] = $item;
                $m = '已保存';
            }
        } elseif ($do === 'delete_item') {
            $t = (string)($_POST['type'] ?? ''); $id = (string)($_POST['id'] ?? '');
            if (isset($TYPES[$t]) && $TYPES[$t]['kind'] === 'list') {
                $data[$t] = array_values(array_filter($data[$t], function ($x) use ($id) { return ($x['id'] ?? '') !== $id; }));
                $m = '已删除';
            }
        } elseif ($do === 'delete_msg') {
            $id = (string)($_POST['id'] ?? '');
            $data['messages'] = array_values(array_filter($data['messages'], function ($x) use ($id) { return ($x['id'] ?? '') !== $id; }));
            $m = '留言已删除';
        } elseif ($do === 'save_reply') {
            $mid = (string)($_POST['msg_id'] ?? '');
            $txt = clean_text((string)($_POST['reply'] ?? ''), 200);
            foreach ($data['messages'] as $k => $mm) {
                if (($mm['id'] ?? '') === $mid && $txt !== '') {
                    $data['messages'][$k]['replies'][] = [
                        'id' => nid(), 'qq' => '站长 ' . admin_creds()['user'],
                        'email' => '', 'text' => $txt, 'time' => now_str(),
                    ];
                }
            }
            $m = '回复已发布';
        } elseif ($do === 'delete_reply') {
            $mid = (string)($_POST['msg_id'] ?? ''); $rid = (string)($_POST['rid'] ?? '');
            foreach ($data['messages'] as $k => $mm) {
                if (($mm['id'] ?? '') === $mid) {
                    $data['messages'][$k]['replies'] = array_values(array_filter($mm['replies'] ?? [], function ($x) use ($rid) { return ($x['id'] ?? '') !== $rid; }));
                }
            }
            $m = '回复已删除';
        } elseif ($do === 'save_account') {
            $u = clean_text((string)($_POST['user'] ?? ''), 30);
            $p1 = (string)($_POST['pass1'] ?? ''); $p2 = (string)($_POST['pass2'] ?? '');
            if ($u === '') $m = '用户名不能为空';
            elseif ($p1 !== '' && $p1 !== $p2) $m = '两次密码不一致';
            else {
                $j = ['user' => $u];
                if ($p1 !== '') $j['pass_hash'] = password_hash($p1, PASSWORD_DEFAULT);
                else { $c = admin_creds(); if ($c['hash']) $j['pass_hash'] = $c['hash']; else $j['pass'] = $c['plain']; }
                @file_put_contents(ADMIN_FILE, json_encode($j, JSON_PRETTY_PRINT), LOCK_EX);
                $m = '账号已更新';
            }
        }
        save_data($data);
        header('Location: ' . $back . ($m !== '' ? '&msg=' . urlencode($m) : ''));
        exit;
    } else {
        http_response_code(403); exit('Forbidden');
    }
}
$logged = !empty($_SESSION['admin']);
$pnow = preg_replace('/[^a-z]/', '', (string)($_GET['p'] ?? 'site'));
if ($pnow === '' || !isset($TYPES[$pnow])) $pnow = 'site';
$act = (string)($_GET['act'] ?? '');
$msg = (string)($_GET['msg'] ?? '');
?>
