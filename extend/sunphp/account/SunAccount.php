<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-03 14:14:49
 * @LastEditors: light
 * @LastEditTime: 2023-09-03 16:41:12
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\account;

defined('SUN_IN') or exit('Sunphp Access Denied');

use app\admin\model\CoreAccount;

class SunAccount{


    public static function create($uniacid='',$account_info=[]){

        // 可以通过account_info手动指定一个平台信息
        if(!empty($account_info)){
            $account=$account_info;
        }else{
            if(empty($uniacid)){
                $account=request()->middleware('account');
            }else{
                $account=CoreAccount::where('id',$uniacid)->where('is_delete',0)->find();
                if(empty($account)){
                    echo '平台不存在';
                    die();
                }
            }
        }


        //区分账号类型
        $sun_account='';

        switch(intval($account['type'])){
            case 1:
                //'微信公众号'
                $config = [
                    'app_id' => $account['appid'],
                    'secret' => $account['secret'],
                    'token' => 'sunphp-wechat-token',
                    // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
                    'response_type' => 'array',
                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！
                    'aes_key' => '',
                    //level: 日志级别, 可选为：debug/info/notice/warning/error/critical/alert/emergency
                    'log' => [
                        'default' => 'prod', // 默认使用的 channel，生产环境可以改为下面的 prod
                        'channels' => [
                            // 测试环境
                            'dev' => [
                                //single模式，只有一个文件记录
                                'driver' => 'single',
                                // 'path' => '/tmp/easywechat.log',
                                'path' => root_path() .'runtime/easywechat/easywechat.log',
                                'level' => 'debug',
                            ],
                            // 生产环境
                            'prod' => [
                                // daily模式每天记录，在文件名后加上当天日期
                                'driver' => 'daily',
                                // 'path' => '/tmp/easywechat.log',
                                'path' => root_path() .'runtime/easywechat/easywechat.log',
                                'level' => 'info',
                            ],
                        ]
                    ]
                ];
                if(!empty($account['api_token'])){
                    $config['token']=$account['api_token'];
                }
                if(!empty($account['api_key'])){
                    $config['aes_key']=$account['api_key'];
                }
                $sun_account=new Wxgzh($config);
            break;
            case 2:
                //'微信小程序'
                $config = [
                    'app_id' => $account['appid'],
                    'secret' => $account['secret'],
                    'token' => 'sunphp-wxxcx-token',
                    // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
                    'response_type' => 'array',
                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！
                    'aes_key' => '',
                    // 日志级别, 可选为：debug/info/notice/warning/error/critical/alert/emergency
                    'log' => [
                        // 'level' => 'debug',
                        // 'file' => __DIR__.'/wechat.log',
                        'level' => 'info',
                        'file' => root_path() .'runtime/easywechat/wechat.log'
                    ]
                ];
                if(!empty($account['api_token'])){
                    $config['token']=$account['api_token'];
                }
                if(!empty($account['api_key'])){
                    $config['aes_key']=$account['api_key'];
                }
                $sun_account=new Wxxcx($config);
            break;
            case 3:
                //抖音小程序
            break;
            default:
            break;
        }


        return $sun_account;
    }



}