#!/bin/bash
# 检查本地/容器内 Telegram 同步是否在运行
# 用法：
#   本机 Linux：./scripts/check_telegram_sync.sh
#   Docker：   docker exec sunphp-php /var/www/im.fuye.io/scripts/check_telegram_sync.sh

set -e
ROOT="${IM_ROOT:-/var/www/im.fuye.io}"
if [ -n "$BASH_SOURCE" ] && [ -d "$(dirname "$BASH_SOURCE")/.." ]; then
  SCRIPT_DIR="$(cd "$(dirname "$BASH_SOURCE")" && pwd)"
  if [ -d "$SCRIPT_DIR/../runtime" ]; then
    ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
  fi
fi

echo "=========================================="
echo "Telegram 同步状态检查（项目根: $ROOT）"
echo "=========================================="
echo ""

# 1. Crontab 是否包含 Telegram 同步
echo "1. Crontab 任务"
if crontab -l 2>/dev/null | grep -q "cron_sync.php"; then
  echo "   ✅ 已配置："
  crontab -l 2>/dev/null | grep -E "cron_sync|Telegram" || true
else
  echo "   ❌ 未发现 Telegram 同步任务（cron_sync.php）"
  echo "   修复：在容器内执行 addons/mdkeji_im/scripts/telegram/setup_docker_cron.sh"
fi
echo ""

# 2. Cron 服务是否在跑（Docker 内多为 cron，本机 CentOS 为 crond）
echo "2. Cron 服务"
CRON_RUNNING=""
if command -v service &>/dev/null && service cron status &>/dev/null; then
  CRON_RUNNING=1
  echo "   ✅ cron 服务在运行（Docker/Debian）"
elif command -v systemctl &>/dev/null && systemctl is-active cron &>/dev/null; then
  CRON_RUNNING=1
  echo "   ✅ cron 服务在运行（Docker/Debian）"
elif command -v service &>/dev/null && service crond status &>/dev/null; then
  CRON_RUNNING=1
  echo "   ✅ crond 服务在运行（CentOS）"
elif command -v systemctl &>/dev/null && systemctl is-active crond &>/dev/null; then
  CRON_RUNNING=1
  echo "   ✅ crond 服务在运行（CentOS）"
fi
if [ -z "$CRON_RUNNING" ]; then
  if [ -n "$(ps aux 2>/dev/null | grep -v grep | grep -E 'crond|cron')" ]; then
    echo "   ✅ 有 cron/crond 进程"
  else
    echo "   ❌ cron 服务未运行"
    echo "   修复 Docker 内：service cron start"
    echo "   修复 CentOS 本机：systemctl start crond && systemctl enable crond"
  fi
fi
echo ""

# 3. 最近同步日志（cron 输出可能写在 telegram 或 mdim 目录）
echo "3. 最近 Telegram 同步日志"
LOG_DIR="$ROOT/runtime/telegram/log"
CRON_LOG=""
for candidate in "$ROOT/runtime/telegram/log/telegram_cron.log" "$ROOT/runtime/mdim/log/telegram_cron.log"; do
  if [ -f "$candidate" ]; then
    CRON_LOG="$candidate"
    break
  fi
done
if [ -n "$CRON_LOG" ]; then
  echo "   最近 10 行 $CRON_LOG："
  tail -n 10 "$CRON_LOG" 2>/dev/null | sed 's/^/   /'
else
  echo "   （cron 输出日志未找到；主流程日志见下方）"
fi
YM="$(date +%Y%m)"
DD="$(date +%d)"
if [ -f "$LOG_DIR/$YM/$DD.log" ]; then
  echo ""
  echo "   今日 telegram 主日志最后 5 条 telegram_sync："
  grep "telegram_sync" "$LOG_DIR/$YM/$DD.log" 2>/dev/null | tail -n 5 | sed 's/^/   /' || echo "   （无匹配）"
fi
echo ""

# 4. 是否有正在运行的 cron_sync 进程
echo "4. 当前同步进程"
if ps aux 2>/dev/null | grep -v grep | grep -q "cron_sync.php"; then
  echo "   ✅ 有 cron_sync.php 进程在运行"
  ps aux 2>/dev/null | grep -v grep | grep "cron_sync.php" | sed 's/^/   /'
else
  echo "   （当前无 cron_sync.php 进程；定时触发时才会有）"
fi
echo ""

echo "=========================================="
echo "若 Crontab 未配置或 cron 未启动，同步会停止。"
echo "  Docker 内：  bash $ROOT/addons/mdkeji_im/scripts/telegram/setup_docker_cron.sh"
echo "  CentOS 本机：systemctl start crond && systemctl enable crond"
echo "  重装 Crontab：bash $ROOT/addons/mdkeji_im/scripts/telegram/install_cron.sh"
echo "=========================================="
