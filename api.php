<?php
/** 留言板 API：前台提交 / 回复，返回最新公开留言列表 */
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/lib.php';

function out(array $a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) out(['ok' => 0, 'msg' => '请求格式错误']);

// 提交频率限制
$last = (int)($_SESSION['gb_last'] ?? 0);
if (time() - $last < GB_COOLDOWN) out(['ok' => 0, 'msg' => '提交太频繁，请稍等几秒再试']);

$act = (string)($in['action'] ?? '');
$data = load_data();

if ($act === 'add_msg' || $act === 'add_reply') {
    $qq    = (string)($in['qq'] ?? '');
    $email = trim((string)($in['email'] ?? ''));
    $text  = clean_text((string)($in['text'] ?? ''), 200);

    if (!preg_match('/^[0-9]{5,12}$/', $qq))        out(['ok' => 0, 'msg' => 'QQ 号需为 5-12 位数字']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(['ok' => 0, 'msg' => '邮箱格式不正确']);
    if ($text === '')                               out(['ok' => 0, 'msg' => '内容不能为空']);

    if ($act === 'add_msg') {
        array_unshift($data['messages'], [
            'id' => nid(), 'qq' => $qq, 'email' => $email,
            'text' => $text, 'time' => now_str(), 'replies' => [],
        ]);
    } else {
        $mid = (string)($in['id'] ?? '');
        $found = false;
        foreach ($data['messages'] as $m) {
            if (($m['id'] ?? '') === $mid) { $found = true; break; }
        }
        if (!$found) out(['ok' => 0, 'msg' => '留言不存在或已被删除']);
        foreach ($data['messages'] as $k => $m) {
            if (($m['id'] ?? '') === $mid) {
                if (!isset($data['messages'][$k]['replies']) || !is_array($data['messages'][$k]['replies'])) {
                    $data['messages'][$k]['replies'] = [];
                }
                $data['messages'][$k]['replies'][] = [
                    'id' => nid(), 'qq' => $qq, 'email' => $email,
                    'text' => $text, 'time' => now_str(),
                ];
            }
        }
    }

    if (!save_data($data)) out(['ok' => 0, 'msg' => '服务器保存失败']);
    $_SESSION['gb_last'] = time();
    out(['ok' => 1, 'messages' => public_messages($data)]);
}

out(['ok' => 0, 'msg' => '未知操作']);
