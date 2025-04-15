<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-14 14:43:12
 * @LastEditors: light
 * @LastEditTime: 2024-01-17 20:21:25
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);
namespace app\admin\model;
use think\Model;

class CoreStorage extends Model{
    protected $type=[
        'type'=>'integer',
        'ali_oss'=>'serialize',
        'tencent_cos'=>'serialize',
        'qiniu'=>'serialize',
        'censor'=>'serialize'
    ];
}