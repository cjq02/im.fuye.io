<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-07 11:16:34
 * @LastEditors: light
 * @LastEditTime: 2023-10-16 14:03:39
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\cache;

defined('SUN_IN') or exit('Sunphp Access Denied');

use app\admin\model\CoreCache;
use think\facade\Cache;

class SunCache{

    public static function prefix(){
        return 'sunphp_cache_';
    }

    public static function set($name,$value,$expire_time=0,$sql_cache=false){
        $name=self::prefix().$name;
        if($expire_time>0){
            Cache::set($name,$value, $expire_time);
        }else{
            Cache::set($name,$value);
        }

        if($sql_cache){
            $core_cache=CoreCache::where('key',$name)->find();
            if(!empty($core_cache)){
                CoreCache::where('key',$name)->update([
                    'value'=>$value
                ]);
            }else{
                CoreCache::create([
                    'key'=>$name,
                    'value'=>$value
                ]);
            }
        }
    }

    public static function get($name,$sql_cache=false){
        $name=self::prefix().$name;
        $value=Cache::get($name);

        if($sql_cache){
            if(empty($value)){
                $core_cache=CoreCache::where('key',$name)->find();
                if(!empty($core_cache)){
                    // 更新缓存
                    Cache::set($name,$core_cache['value'], 3600*24*3);
                    return $core_cache['value'];
                }
            }
        }
        return $value;
    }

    public static function delete($name){
        $name=self::prefix().$name;
        return Cache::delete($name);
    }

    public static function clean(){
        return Cache::clean();
    }



}