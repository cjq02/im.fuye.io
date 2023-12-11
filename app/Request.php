<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-02-09 09:46:15
 * @LastEditors: light
 * @LastEditTime: 2023-12-11 11:49:50
 * @Description: SonLight Tech版权所有
 */
declare(strict_types=1);

namespace app;
defined('SUN_IN') or exit('Sunphp Access Denied');

// 应用请求对象类
class Request extends \think\Request
{
    // 在模块中自行设置，过滤规则
    // protected $filter = ['htmlspecialchars'];
}
