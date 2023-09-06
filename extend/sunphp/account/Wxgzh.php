<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-03 15:00:20
 * @LastEditors: light
 * @LastEditTime: 2023-09-05 18:14:58
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\account;

defined('SUN_IN') or exit('Sunphp Access Denied');

use config;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Video;
use EasyWeChat\Kernel\Messages\Voice;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;
use EasyWeChat\Kernel\Messages\Article;

class Wxgzh {

    protected $config;

    public function __construct($config=[])
    {
        $this->config=$config;
    }

    public function getApp(){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        return $app;
    }

    /* 获取wechat对象类里面的方法 */
    public function __call($name, $arguments)
    {
        $config=$this->config;
        $app = Factory::officialAccount($config);
        return $$app->$name(...$arguments);
    }

    public function login($scope='snsapi_base',$acid='',$target_url=''){
        $uniacid=request()->get('i',$acid);

        // 判断登录
        //来自app/index.php页面，session无法获取
        // 手动初始化session
        if(request()->baseUrl()=='/app/index.php'){
            app()->session->setId(cookie(config('session.name')));
            app()->session->init();
        }


        $userinfo=session('wechat_user_'.$uniacid);
        if(!empty($userinfo)){
            return $userinfo;
        }



        $config=$this->config;
        $config['oauth']=[
            // 'scopes'   => ['snsapi_userinfo'],
            'scopes'=>[$scope],
            'callback' => '/index.php/admin/sunphp/callback'
        ];

        $app = Factory::officialAccount($config);
        $oauth = $app->oauth;

        //携带state参数
        $oauth->withState($uniacid);

        $redirectUrl = $oauth->redirect();

        $domain=request()->domain();
        $url=request()->url();

        //页面跳转session无法写入
        // session('target_url_'.$uniacid,$domain.$url);
        if(empty($target_url)){
            $target_url=urlencode($domain.$url);
        }

        $auth_url=$domain.'/index.php/admin/sunphp/wx?open_url='
        .urlencode($redirectUrl).'&i='.$uniacid.'&t='.$target_url.'&scope='.$scope;
        header("Location: {$auth_url}");
        die();
    }

    public function userinfo(){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        $oauth = $app->oauth;

        $code = request()->get('code');
        $user = $oauth->userFromCode($code);
        return $user->toArray();
    }

    public function sendTplNotice($openid,$template_id,$data,$url='',$miniprogram=''){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        return $app->template_message->send([
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => $url,
            'miniprogram' => $miniprogram,
            'data' => $data
        ]);
    }

    public function clearAccessToken(){
        // $config=$this->config;
        // $app = Factory::officialAccount($config);
        // $app['access_token']->setToken('123456', 0);
    }

    public function getAccessToken(){
        $config=$this->config;
        $app = Factory::officialAccount($config);

        // 这里是授权登陆token
        // return $app->user->getAccessToken()->getToken();

        $accessToken = $app->access_token;
        // token 数组  token['access_token'] 字符串，会超时失效
        // $token = $accessToken->getToken();

        // 强制重新从微信服务器获取 token
        $token = $accessToken->getToken(true);

        // 返回string类型的access_token
        return $token['access_token'];
    }

    public function getJssdkConfig(){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        // json为false返回数组，反之json字符串
        return $app->jssdk->buildConfig($APIs=[], $debug = false, $beta = false, $json = false, $openTagList = []);
    }

    public function fansQueryInfo($openId){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        return $app->user->get($openId);
    }


    public function response($args=[]){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        $app->server->push(function ($message) use($args){

            $msg='';
            switch($args['type']){
                case 'text':
                    $msg = new Text($args['content']);
                    break;
                case 'image':
                    $msg = new Image($args['mediaId']);
                    break;
                case 'video':
                    $msg = new Video($args['mediaId'], [
                        'title' => $args['title'],
                        'description' => $args['description'],
                    ]);
                    break;
                case 'voice':
                    $msg = new Voice($args['mediaId']);
                    break;
                case 'news':
                    // 被动回复消息与客服消息接口的图文消息类型中图文数目只能为一条
                    $items = [
                        new NewsItem([
                            'title'       => $args['title'],
                            'description' => $args['description'],
                            'url'         => $args['url'],//链接 URL
                            'image'       => $args['image'],//注意：图片链接
                            // ...
                        ]),
                    ];
                    $msg = new News($items);
                    break;
                case 'article':
                   /*  title 标题
                    author 作者
                    content 具体内容
                    thumb_media_id 图文消息的封面图片素材 id（必须是永久 mediaID）
                    digest 图文消息的摘要，仅有单图文消息才有摘要，多图文此处为空
                    source_url 来源 URL
                    show_cover 是否显示封面，0 为 false，即不显示，1 为 true，即显示 */
                    $msg = new Article([
                        'title'   => $args['title'],
                        'author'  => $args['author'],
                        'content' => $args['content'],
                        'thumb_media_id' => $args['thumb_media_id'],
                        'digest' => $args['digest'],
                        'source_url' => $args['source_url'],
                        'show_cover' => $args['show_cover']
                    ]);
                    break;
                case 'event':
                    return '收到事件消息';
                    break;
                    case 'location':
                        return '收到坐标消息';
                        break;
                    case 'link':
                        return '收到链接消息';
                        break;
                    case 'file':
                        return '收到文件消息';
                        break;
                default:
                break;
            }

            return $msg;

        });

        $response = $app->server->serve();
        $response->send();

    }


    public function upload($args=[]){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        switch($args['type']){
            case 'image':

                // path是完整的服务器资源路径
                $result = $app->media->uploadImage($args['path']);
                // {
                //    "media_id":MEDIA_ID,
                //    "url":URL
                // }
                break;
            case 'voice':
                $result = $app->media->uploadVoice($args['path']);
                // {
                //    "media_id":MEDIA_ID,
                // }
                break;
            case 'video':
                $result = $app->media->uploadVideo($args['path'], $args['title'], $args['description']);
                // {
                //    "media_id":MEDIA_ID,
                // }
                break;
            case 'thumb':
                $result = $app->media->uploadThumb($args['path']);
                // {
                //    "media_id":MEDIA_ID,
                // }
                break;
            default:
            break;
        }

        return $result;
    }


}