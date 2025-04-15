<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-02-20 09:30:50
 * @LastEditors: light
 * @LastEditTime: 2024-09-14 20:08:43
 * @Description: SonLight Tech版权所有
 */
declare(strict_types=1);

namespace app\admin\controller;
use sunphp\account\SunAccount;
use sunphp\pay\SunPay;
// use app\admin\model\CoreToken;

class Sunphp extends Base{

    public function wx(){
        $get=$this->request->get();
        if(empty($get['open_url'])||empty($get['i'])||empty($get['t'])||empty($get['scope'])){
            return response('url链接错误');
        }
        session('target_url_'.$get['i'],$get['t']);
        if($get['scope']=='snsapi_userinfo'){
            return redirect($get['open_url']);

            // 弹出页面，用户授权
            // 该方法暂时弃用，只支持静默授权
            /* return view('wx',[
                'open_url'=>$get['open_url']
            ]); */
        }else{
            //静默授权跳转
            return redirect($get['open_url']);
        }
    }

    // snsapi_base静默授权回调
    public function callback(){
        $get=$this->request->get();
        $account=SunAccount::create($get['state']);
        $user=$account->openid();
        $targetUrl = session('target_url_'.$get['state']);

        //snsapi_base静默授权——只能获取到openid信息
        // $openid=$user['id'];

        if(!empty($user['id'])){
            session('wechat_user_'.$get['state'],$user);
            return redirect($targetUrl);
        }else{
            $account->login('snsapi_userinfo',$get['state'],urlencode($targetUrl));
        }

    }

    //  snsapi_userinfo用户授权回调
    public function userback(){
        $get=$this->request->get();
        $account=SunAccount::create($get['state']);
        // $userinfo=$account->userinfo();
        $token_code=$account->tokenFromCode();
        $targetUrl = session('target_url_'.$get['state']);


        if(!empty($token_code['is_snapshotuser'])){
            // 快照模式，用户信息为虚拟
            // targetUrl快照模式没有session失效
            $account->login('snsapi_userinfo',$get['state']);

        }else{
            $userinfo=$account->getUserinfo($token_code['access_token'],$token_code['openid']);


            //判断是否api报错
            if(isset($userinfo['raw'])&&isset($userinfo['raw']['errcode'])&&$userinfo['raw']['errcode']>0){
                echo $userinfo['raw']['errmsg'];
                die();
            }

            if(isset($userinfo['raw'])&&isset($userinfo['raw']['openid'])){
                session('wechat_user_'.$get['state'],$userinfo['raw']);

                // 保存token信息
                /* $data=[
                    'access_token'=>$token_code['access_token'],
                    'access_expires'=>date('Y-m-d H:i:s',time()+$token_code['expires_in']),
                    'refresh_token'=>$token_code['refresh_token'],
                    'refresh_expires'=>date('Y-m-d H:i:s',time()+86400*25)
                ];
                $token=CoreToken::where([
                    'openid'=>$userinfo['id'],
                    'acid'=>$get['state']
                ])->field(['id'])->find();
                if(empty($token)){
                    $data['acid']=$get['state'];
                    $data['openid']=$userinfo['id'];
                    CoreToken::create($data);
                }else{
                    CoreToken::where('id',$token['id'])->update($data);
                } */

                return redirect($targetUrl);
            }else{
                $account->login('snsapi_userinfo',$get['state'],urlencode($targetUrl));
            }


        }



    }

    // 静默登录，获取用户资料已经失效
    public function callback_old(){
        $get=$this->request->get();
        $account=SunAccount::create($get['state']);
        $userinfo=$account->userinfo();
        $targetUrl = session('target_url_'.$get['state']);


        //snsapi_base静默授权校验——————失效了，无法获取用户信息
        // 兼容性问题——微信登录——可能没有设置头像昵称，导致循环登录

        //判断是否api报错
        $is_fail=false;
        if(isset($userinfo['raw'])&&isset($userinfo['raw']['errcode'])&&$userinfo['raw']['errcode']>0){
            $is_fail=true;
        }
        if(!$is_fail&&isset($userinfo['nickname'])&&isset($userinfo['avatar'])){
            session('wechat_user_'.$get['state'],$userinfo['raw']);
            return redirect($targetUrl);
        }else{
            $account->login('snsapi_userinfo',$get['state'],urlencode($targetUrl));
        }


        // 方法不对，这里snsapi_base访问会导致数据库token失效
        // 判断access_token是否过期
        /*  $access_time=strtotime($token['access_expires'])-600;
         if($access_time < time()){
             // 刷新token
             $res=$account->refreshToken($token['refresh_token']);
             dump($res);
             die();

         }else{
             $userinfo=$account->getUserinfo($token['access_token'],$openid);
             dump($userinfo);
             die();
         } */



    }


    /* 微信API-v2支付（不推荐）jsapi支付util.js */
    public function payV2(){
        $get=$this->request->get();
        $acid=$get['i'];
        $account=SunAccount::create($acid);
        $userinfo=$account->login();

        $post=$this->request->post();
        $params=[
            'acid'=>$acid,
            'module'=>$post['module'],//模块标识
            'pay_method'=>$post['payMethod'],//支付方式wechat,alipay
            'tid' => $post['orderTid'],
            'money'=>$post['orderFee'],//单位是元
            'title' => $post['orderTitle'],
            'openid'=>$userinfo['openid']
        ];

        $sunpay=SunPay::wechatV2($acid);
        $order=$sunpay->pay($params);

        $data=$sunpay->getJssdkConfig()->bridgeConfig($order['prepay_id'],false);
        //两种都可以，注意timeStamp大小写区别
        // $data=$sunpay->getJssdkConfig()->sdkConfig($order['prepay_id']);

        return json($data);
    }


        /* 微信API-v3支付 jsapi支付util.js */
        public function pay(){
            $get=$this->request->get();
            $acid=$get['i'];
            $account=SunAccount::create($acid);
            $userinfo=$account->login();

            $post=$this->request->post();

            $params = [
                'acid'=>$acid,
                'module'=>$post['module'],//模块标识
                'pay_method'=>$post['payMethod'],
                'tid' => $post['orderTid'],
                'money'=>$post['orderFee'],//单位是元
                'title' => $post['orderTitle'],
                'openid' => $userinfo['openid']
                // 'attach'=>$attach
            ];
            $wechat=SunPay::wechat($acid);
            $data = $wechat->mp($params);
            return json($data);
        }


    /* 微信手机网站H5支付API-V3 */
    /* 微信通过referer来判断来源，不能进入页面就支付 */
    public function wechatWap(){
        $get=$this->request->get();
        $acid=$get['i'];


        $post=$this->request->post();

        $params = [
            'acid'=>$acid,
            'module'=>$post['module'],//模块标识
            'pay_method'=>$post['payMethod'],
            'tid' => $post['orderTid'],
            'money'=>$post['orderFee'],//单位是元
            'title' => $post['orderTitle']
        ];

        $wechat=SunPay::wechat($acid);
        $result=$wechat->wap($params)->all();
        $h5_url=$result['h5_url'];

        $data=[
            'h5_url'=>$h5_url
        ];
        return json($data);
    }


    /* 微信APP支付API-V3 */
    public function wechatApp(){
        $get=$this->request->get();
        $acid=$get['i'];


        $post=$this->request->post();

        $params = [
            'acid'=>$acid,
            'module'=>$post['module'],//模块标识
            'pay_method'=>$post['payMethod'],
            'tid' => $post['orderTid'],
            'money'=>$post['orderFee'],//单位是元
            'title' => $post['orderTitle']
        ];

        $wechat=SunPay::wechat($acid);
        return $wechat->app($params);
    }

     /* 微信扫码支付API-V3 */
     public function wechatScan(){
        $get=$this->request->get();
        $acid=$get['i'];


        $post=$this->request->post();

        $params = [
            'acid'=>$acid,
            'module'=>$post['module'],//模块标识
            'pay_method'=>$post['payMethod'],
            'tid' => $post['orderTid'],
            'money'=>$post['orderFee'],//单位是元
            'title' => $post['orderTitle']
        ];

        $wechat=SunPay::wechat($acid);

        $result = $wechat->scan($params);
        $code_url = $result->code_url; // 二维码 url

        $data=[
            'code_url'=>$code_url
        ];
        return json($data);

    }


    /* 支付宝Web支付 */
    public function alipayWeb(){
        $get=$this->request->get();
        $acid=$get['i'];

        $params = [
            'acid'=>$acid,
            'module'=>$get['module'],//模块标识
            'pay_method'=>$get['payMethod'],
            'tid' => $get['orderTid'],
            'money'=>$get['orderFee'],//单位是元
            'title' => $get['orderTitle']
        ];

        $alipay=SunPay::alipay($acid);
        return $alipay->web($params);
    }

    /* 支付宝手机H5网页支付 */
    public function alipayH5(){
        $get=$this->request->get();
        $acid=$get['i'];

        $params = [
            'acid'=>$acid,
            'module'=>$get['module'],//模块标识
            'pay_method'=>$get['payMethod'],
            'tid' => $get['orderTid'],
            'money'=>$get['orderFee'],//单位是元
            'title' => $get['orderTitle']
        ];

        $alipay=SunPay::alipay($acid);
        // 自定义回调参数
        return $alipay->wap($params);
    }



}