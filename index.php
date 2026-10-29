<?php
/**
 * DSJIE 个人主页 - 入口
 * 依次输出：① 头部+桌面侧栏 ② 桌面导航+主内容 ③ 移动端+页脚+脚本
 * （拆分仅为便于维护，等价于单文件模板）
 */
/* 未部署（缺管理员账号或站点身份）→ 自动进入安装向导；已部署正常访问，不触碰向导 */
if (!is_file(__DIR__ . '/data/identity.php') || !is_file(__DIR__ . '/data/admin.json')) {
  header('Location: install.php');
  exit;
}
require __DIR__ . '/index_part1.php';
require __DIR__ . '/index_part2.php';
require __DIR__ . '/index_part3.php';
