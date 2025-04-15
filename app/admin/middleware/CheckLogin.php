<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-24 11:39:45
 * @LastEditors: light
 * @LastEditTime: 2023-08-04 12:05:36
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);
namespace app\admin\middleware;


use app\admin\model\CoreUseaccount;
use app\admin\model\CoreUser;

// 用户应用管理后台登陆校验
class CheckLogin{
    public function handle($request,\Closure $next){

        $cookie=$request->cookie();
        if(empty($cookie['sunphp_user_session_id'])){
            $sunphp_redirect_url='#/'.urlencode($request->domain().$request->url());
			return redirect($request->domain().$sunphp_redirect_url);
        }

        //检查用户是否存在
        $user=CoreUser::where('session_id',$cookie['sunphp_user_session_id'])->where('is_delete',0)->find();
        if(empty($user)){
            $sunphp_redirect_url='#/'.urlencode($request->domain().$request->url());
			return redirect($request->domain().$sunphp_redirect_url);
        }

        $get=$request->get();
        if(empty($get['i'])){
            return response('页面参数错误');
        }

        if($user['type']!=2){
            //检查使用者权限
            $use_account=CoreUseaccount::where([
                'uid'=>$user['id'],
                'acid'=>$get['i']
            ])->find();
            if(empty($use_account)){
                return response('无平台操作权限');
            }
            $request->use_account=$use_account->toArray();
        }

        //保存在middleware中
        $request->user=$user->toArray();

        return $next($request);


    }
}