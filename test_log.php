<?php
define('IA_ROOT', dirname(__FILE__));
require_once IA_ROOT . "/addons/mdkeji_im/core/config/index_im.php";
require_once IA_ROOT . "/extend/sunphp/log/mdim_log.php";

global $_W;
$_W['uniacid'] = 1;

mdim_log(['test' => 'manual_test', 'time' => date('Y-m-d H:i:s')]);
echo "日志测试完成，请检查日志文件\n";
?>
