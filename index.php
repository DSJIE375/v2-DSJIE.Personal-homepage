<?php
/**
 * DSJIE 个人主页 - 入口
 * 依次输出：① 头部+桌面侧栏 ② 桌面导航+主内容 ③ 移动端+页脚+脚本
 * （拆分仅为便于维护，等价于单文件模板）
 */
require __DIR__ . '/index_part1.php';
require __DIR__ . '/index_part2.php';
require __DIR__ . '/index_part3.php';
