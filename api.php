<?php
/** 留言板 API：前台提交 / 回复，返回最新公开留言列表 */
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/lib.php';

function out(array $a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) out(['ok' => 0, 'msg' => '请求格式错误']);

// 提交频率限制
$act = (string)($in['action'] ?? '');
if ($act === 'check_identity') {
    $qq = (string)($in['qq'] ?? '');
    $required = preg_match('/^[0-9]{5,12}$/', $qq) && is_priv_qq(load_data(), $qq);
    out(['ok' => 1, 'required' => (bool)$required]);
}

$last = (int)($_SESSION['gb_last'] ?? 0);
if (time() - $last < GB_COOLDOWN) out(['ok' => 0, 'msg' => '提交太频繁，请稍等几秒再试']);

$data = load_data();

if ($act === 'add_msg' || $act === 'add_reply') {
    $qq    = (string)($in['qq'] ?? '');
    $pass  = (string)($in['pass'] ?? '');
    $email = trim((string)($in['email'] ?? ''));
    $text  = clean_text((string)($in['text'] ?? ''), 200);

    if (!preg_match('/^[0-9]{5,12}$/', $qq))        out(['ok' => 0, 'msg' => 'QQ 号需为 5-12 位数字']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(['ok' => 0, 'msg' => '邮箱格式不正确']);
    if ($text === '')                               out(['ok' => 0, 'msg' => '内容不能为空']);

    // 身份标签：QQ 与口令同时匹配才授予（dev=内置凭据 / master=后台站长设置）
    $role = auth_role($data, $qq, $pass);

    // 特权 QQ（站长/开发者）发言必须校验通过，口令为空或错误一律拒绝（防顶号冒充）
    if ($role === '' && is_priv_qq($data, $qq)) {
        out(['ok' => 0, 'msg' => '身份口令校验未通过，该 QQ 发言必须填写正确口令']);
    }

    $replyNotification = null;
    if ($act === 'add_msg') {
        $message = [
            'id' => nid(), 'qq' => $qq, 'email' => $email,
            'text' => $text, 'time' => now_str(), 'role' => $role, 'replies' => [],
        ];
        array_unshift($data['messages'], set_guestbook_role($message, $role));
    } else {
        $mid = (string)($in['id'] ?? '');
        $found = false;
        foreach ($data['messages'] as $m) {
            if (($m['id'] ?? '') === $mid) { $found = true; break; }
        }
        if (!$found) out(['ok' => 0, 'msg' => '留言不存在或已被删除']);
        foreach ($data['messages'] as $k => $m) {
            if (($m['id'] ?? '') === $mid) {
                $replyNotification = [
                    'to' => (string)($m['email'] ?? ''),
                    'original' => (string)($m['text'] ?? ''),
                    'reply' => $text,
                ];
                if (!isset($data['messages'][$k]['replies']) || !is_array($data['messages'][$k]['replies'])) {
                    $data['messages'][$k]['replies'] = [];
                }
                $reply = [
                    'id' => nid(), 'qq' => $qq, 'email' => $email,
                    'text' => $text, 'time' => now_str(), 'role' => $role,
                ];
                $data['messages'][$k]['replies'][] = set_guestbook_role($reply, $role);
            }
        }
    }

    if (!save_data($data)) out(['ok' => 0, 'msg' => '服务器保存失败']);
    if ($act === 'add_msg') {
        $notifyTo = trim((string)(identity_config()['mail_notify_to'] ?? ''));
        if ($notifyTo !== '') {
            $subject = '新留言通知';
            $body = "网站收到一条新留言。\n\nQQ：{$qq}\n邮箱：{$email}\n时间：" . now_str() . "\n\n留言内容：\n{$text}\n";
            $html = notification_email_html($subject, '访客刚刚在你的网站发表了留言。', [
                '留言内容' => $text,
                '访客邮箱' => $email,
                'QQ' => $qq,
                '时间' => now_str(),
            ]);
            send_notification_email($notifyTo, $subject, $body, $html);
        }
    } elseif (is_array($replyNotification) && filter_var($replyNotification['to'], FILTER_VALIDATE_EMAIL)) {
        $subject = '你的留言收到新回复';
        $body = "你的留言收到了新回复。\n\n原留言：\n" . $replyNotification['original'] . "\n\n回复内容：\n" . $replyNotification['reply'] . "\n";
        $html = notification_email_html($subject, '有人回复了你在网站上的留言。', [
            '原留言' => $replyNotification['original'],
            '回复内容' => $replyNotification['reply'],
        ]);
        if (!send_notification_email($replyNotification['to'], $subject, $body, $html)) {
            error_log('Guestbook reply notification could not be sent.');
        }
    }
    $_SESSION['gb_last'] = time();
    out(['ok' => 1, 'messages' => public_messages($data)]);
}

out(['ok' => 0, 'msg' => '未知操作']);
