#!/bin/bash
# 增加错误处理和日志输出
set -e  # 遇到错误立即终止脚本

echo "=== 开始执行备份和克隆 ==="

# 检查原始目录是否存在
if [ ! -d "mdkeji_im" ]; then
  echo "错误：原始项目目录 'mdkeji_im' 不存在！"
  exit 1
fi

# 备份操作
echo "步骤1：删除旧备份..."
rm -rf mdkeji_im_copy

echo "步骤2：创建新备份..."
cp -r mdkeji_im mdkeji_im_copy || { echo "备份失败！"; exit 1; }

# 安全删除原始目录
echo "步骤3：删除原始目录..."
rm -rf mdkeji_im || { echo "删除失败！"; exit 1; }

# 尝试克隆仓库
echo "步骤4：克隆最新代码..."
git clone git@github.com:cjq02/mdkeji_im.git || {
  echo "克隆失败！正在尝试恢复备份..."
  mv mdkeji_im_copy mdkeji_im
  exit 1
}

echo "=== 执行完成 ==="
                                                                     
