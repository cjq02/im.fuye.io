<?php

if (!function_exists('mdim_log')) {
  function mdim_log($msg)
  {
    $ts = date('Y-m-d H:i:s');
    if (!is_string($msg)) {
      $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
    }
    $line = '[' . $ts . '] [mdim] ' . $msg;
    @file_put_contents('php://stderr', $line . PHP_EOL, FILE_APPEND);

    if (defined('IA_ROOT')) {
      $logBaseDir = IA_ROOT . '/runtime/mdim/log';
    } else {
      $logBaseDir = __DIR__ . '/../../../../runtime/mdim/log';
    }
    $yearMonth = date('Ym');
    $dayFile = date('d') . '.log';
    $targetDir = $logBaseDir . '/' . $yearMonth;
    if (!is_dir($targetDir)) {
      @mkdir($logBaseDir, 0775, true);
      @mkdir($targetDir, 0775, true);
    }
    $logFile = $targetDir . '/' . $dayFile;
    $written = @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
    if ($written !== false) {
      @chmod($logFile, 0664);
    } elseif (function_exists('error_log')) {
      error_log('[mdim_log] write failed, fallback: ' . $line . ' (dir=' . $logBaseDir . ')');
    }
  }
}

// Telegram 专用日志：标签 [telegram]，落盘到 runtime/telegram/log
if (!function_exists('telegram_log')) {
  function telegram_log($msg)
  {
    $ts = date('Y-m-d H:i:s');
    if (!is_string($msg)) {
      $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
    }
    $line = '[' . $ts . '] [telegram] ' . $msg;
    @file_put_contents('php://stderr', $line . PHP_EOL, FILE_APPEND);

    if (defined('IA_ROOT')) {
      $logBaseDir = IA_ROOT . '/runtime/telegram/log';
    } else {
      $logBaseDir = __DIR__ . '/../../../../runtime/telegram/log';
    }
    $yearMonth = date('Ym');
    $dayFile = date('d') . '.log';
    $targetDir = $logBaseDir . '/' . $yearMonth;
    if (!is_dir($targetDir)) {
      @mkdir($targetDir, 0775, true);
    }
    $logFile = $targetDir . '/' . $dayFile;
    @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
    @chmod($logFile, 0664);
  }
}
