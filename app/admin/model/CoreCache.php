<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-17 10:48:07
 * @LastEditors: light
 * @LastEditTime: 2023-10-16 11:01:41
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);
namespace app\admin\model;

use think\Model;
class CoreCache extends Model{
    protected $type=[
        'value'=>'serialize'
    ];
}