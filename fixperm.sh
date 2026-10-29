#!/bin/sh
# DSJIE 站点权限一键修复
# 把本文件放站点根目录（与 index.php 同级），解压新包后执行一次：  sh fixperm.sh
cd "$(dirname "$0")" || exit 1
find . -type d -exec chmod 755 {} +
find . -type f -exec chmod 644 {} +
# === 后台保存与运行期写入目标：全部放开（目录可建文件、文件可读写，不依赖属主） ===
# data/     = content.json（站点内容）、admin.json（后台账号）、identity.php（身份签名）、
#             counter.php / counter_seeded.php / online.php / visits.php / events_*.php（统计日志）
# uploads/  = 后台上传的图片附件；files/ = 附件文件
chmod -R 777 data uploads files 2>/dev/null
# 显式兜底：已知保存目标统一 666，防止任何一步把它们降权
chmod 666 data/content.json data/content.example.json data/admin.json data/identity.php 2>/dev/null
chmod 666 data/counter.php data/counter_seeded.php data/online.php data/visits.php 2>/dev/null
chmod 666 data/events_*.php 2>/dev/null
echo "OK：目录 755 / 普通文件 644 / data、uploads、files 已全部放开（目录 777、写入文件 666）"
