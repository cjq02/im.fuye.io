<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-05-15 14:14:16
 * @LastEditors: light
 * @LastEditTime: 2023-07-27 16:15:23
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

use think\facade\Db;


class SunQuery{
    public function from($arg=''){
        if(!empty($arg)){
            $prefix='ims_';
            return Db::table($prefix.$arg);
        }
        return '';
    }

}