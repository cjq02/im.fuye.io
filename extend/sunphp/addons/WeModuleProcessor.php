<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-05-15 14:14:16
 * @LastEditors: light
 * @LastEditTime: 2023-09-04 14:25:04
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

use sunphp\account\SunAccount;
use sunphp\addons\WeModuleBase;

class WeModuleProcessor extends WeModuleBase{

    protected $message;

    public function __construct($config=[])
    {
        $post=request()->post();
        foreach($post as $k=>$v){
            $key=strtolower($k);
            $message[$key]=$v;
        }
        $message['from']=$post['FromUserName'];
        $message['to']=$post['ToUserName'];
        $message['time']=$post['CreateTime'];
        $message['type']=$post['MsgType'];

        $this->message=$message;

    }

    // 回复文本消息
    public function respText($args=''){
        $account=SunAccount::create();
        $params=[
            'type'=>'text',
			// 'openid'=>$_GPC['FromUserName'],
            'content'=>$args
        ];
        $account->response($params);
    }

    public function respImage($args=''){

    }

    public function respVoice($args=''){

    }

    public function respVideo($args=[]){

    }

    public function respMusic($args=[]){

    }

    public function respNews($args=[]){

    }

    public function respCustom($args=''){

    }


}