<?php
/**
 * DSJIE 个人主页 - 全局配置
 * 修改下面的默认账号密码，或登录后台在「账号」页修改（修改结果保存在 data/admin.json）
 */

// 数据文件（网站全部内容都在这里，备份站点 = 备份这个文件）
define('DATA_FILE',   __DIR__ . '/data/content.json');
define('ADMIN_FILE',  __DIR__ . '/data/admin.json');
define('IDENTITY_FILE', __DIR__ . '/data/identity.php');

// 上传目录
define('UPLOAD_DIR',  __DIR__ . '/uploads');   // 图库图片
define('FILES_DIR',   __DIR__ . '/files');     // 下载区文件

// 后台默认账号（首次登录使用）
define('ADMIN_USER_DEFAULT', 'dsjie');
define('ADMIN_PASS_DEFAULT', 'dsjie888');

// 单个上传文件大小上限（MB）
define('MAX_UPLOAD_MB', 20);

// 留言最短提交间隔（秒）
define('GB_COOLDOWN', 8);
