<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-05-15 14:14:16
 * @LastEditors: light
 * @LastEditTime: 2023-08-02 15:16:07
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

use think\facade\Db;


class SunQuery{
    public function from($arg=''){
        if(!empty($arg)){
            $prefix='ims_';
            return Db::connect('addons')->table($prefix.$arg);
        }
        return '';
    }

}