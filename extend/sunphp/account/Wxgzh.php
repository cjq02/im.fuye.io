<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-03 15:00:20
 * @LastEditors: light
 * @LastEditTime: 2024-09-14 20:07:08
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\account;

defined('SUN_IN') or exit('Sunphp Access Denied');


use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Video;
use EasyWeChat\Kernel\Messages\Voice;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;
use EasyWeChat\Kernel\Messages\Article;
use sunphp\file\SunFile;
use sunphp\http\SunHttp;

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

    // snsapi_userinfo_auto自定义模式，默认调用snsapi_userinfo，直接登陆，不弹出授权页面
    public function login($scope='snsapi_userinfo_auto',$acid='',$target_url=''){
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



        if($scope=='snsapi_base'){
            $config['oauth']=[
                'scopes'   => ['snsapi_base'],
                'callback' => '/index.php/admin/sunphp/callback'
            ];
        }else{
            $config['oauth']=[
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/index.php/admin/sunphp/userback'
            ];
        }

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

    // 仅能获得openid
    public function openid(){
        $config=$this->config;
        $app = Factory::officialAccount($config);

        // 手动设置模式snsapi_base
        $oauth = $app->oauth->scopes(['snsapi_base']);

        $code = request()->get('code');
        $user = $oauth->userFromCode($code);
        return $user->toArray();
    }

    public function tokenFromCode(){
        $config=$this->config;
        $app = Factory::officialAccount($config);

        // 默认模式是snsapi_userinfo
        $oauth = $app->oauth;

        $code = request()->get('code');
        $token = $oauth->tokenFromCode($code);
        return $token;
    }

    public function userinfo(){
        $config=$this->config;
        $app = Factory::officialAccount($config);


        // 手动设置模式snsapi_base
        // $oauth = $app->oauth->scopes(['snsapi_base']);

        // 默认模式是snsapi_userinfo
        $oauth = $app->oauth;


        $code = request()->get('code');
        $user = $oauth->userFromCode($code);
        return $user->toArray();
    }

    public function getUserinfo($access_token,$openid){
        $config=$this->config;
        $app = Factory::officialAccount($config);

        // 默认模式是snsapi_userinfo
        $oauth = $app->oauth;

        $oauth->withOpenid($openid);
        $user = $oauth->userFromToken($access_token);

        return $user->toArray();
    }

    public function refreshToken($refresh_token){
        $config=$this->config;
        $appid=$config['app_id'];
        $api='https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$appid.'&grant_type=refresh_token&refresh_token='.$refresh_token;
        $res=SunHttp::get($api);
        $res=json_decode($res,true);
        return $res;
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

    // 公众号开通客服功能后，主动通过客服接口发送消息
    public function response($args=[]){
        $config=$this->config;
        $app = Factory::officialAccount($config);

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
            default:
            break;
        }




        // 小程序发送客服消息
        try{
            $res=$app->customer_service->message($msg)->to($args['openid'])->send();
        }catch(\Exception $e){
            // logging_run($e);
        }


        // 成功的返回结果
        // array (
        //     'errcode' => 0,
        //     'errmsg' => 'ok',
        //   )

        return $res;
    }

    //收到推送消息后——被动发送消息
    public function response_passive($args=[]){
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

    public function getMedia($mediaId,$type,$remote_upload=true,$local_delete=true){
        $config=$this->config;
        $app = Factory::officialAccount($config);

        //生成文件路径
        $get=request()->get();

        if (!empty($get['i'])&&intval($get['i'])>0) {
            $uniacid = intval($get['i']);
            $path = "{$type}s/{$uniacid}/" . date('Y/m');
        } else {
            $path = "{$type}s/system";
        }


        // 创建多级目录
        if(!is_dir(root_path() . "attachment/" . $path)){
            mkdir(root_path() . "attachment/" . $path, 0777, true);
        }

        switch ($type) {
            case 'image':
                $ext='jpg';
                break;
            case 'audio':
            case 'voice':
                $ext='mp3';
                break;
            case 'video':
                $ext='mp4';
                break;
            default:
                return;
                break;
        }

        //指定文件名称
        do {
            $data = uniqid("", true);
            $data .= microtime();
            $data .= $_SERVER['HTTP_USER_AGENT'];
            $data .= $_SERVER['REMOTE_PORT'];
            $data .= $_SERVER['REMOTE_ADDR'];
            $hash = strtolower(hash('ripemd128', "sunphp" . md5($data)));
            $filename = md5($hash) . '.' . $ext;


            $filename_noext = md5($hash);

        } while (file_exists(root_path() . "attachment/" . $path . "/" . $filename));


        $stream = $app->media->get($mediaId);

        if ($stream instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            // // 以内容 md5 为文件名存到本地
            // $stream->save('保存目录');

            // 自定义文件名，不需要带后缀
            // 注意不要带有后缀，返回结果带有后缀名
            $res_filename=$stream->saveAs(root_path() . "attachment/" . $path, $filename_noext);


            // 注意，语音是amr格式，需要转码mp3
            if($type=='voice'){
                // $res_filename=SunFile::amrToMp3($res_filename);
            }

            if($remote_upload){
                SunFile::remoteUpload($path . "/" . $res_filename,$local_delete);
            }

            $result = [
                "status" => 1,
                "message"=>"下载成功",
                "path" =>  $path . "/" . $res_filename
            ];
            return $result;

        }

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

    public function currentMenu($args=[]){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        return $app->menu->current();
    }

    public function createMenu($args=[]){
        $config=$this->config;
        $app = Factory::officialAccount($config);
        return $app->menu->create($args);
    }


}