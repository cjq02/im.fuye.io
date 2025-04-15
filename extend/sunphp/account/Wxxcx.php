<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-03 15:57:30
 * @LastEditors: light
 * @LastEditTime: 2023-09-05 14:37:22
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

class Wxxcx {
    protected $config;

    public function __construct($config=[])
    {
        $this->config=$config;
    }

    public function getApp(){
        $config=$this->config;
        $app = Factory::miniProgram($config);
        return $app;
    }

    /* 获取wxxcx对象类里面的方法 */
    public function __call($name, $arguments)
    {
        $config=$this->config;
        $app = Factory::miniProgram($config);
        return $$app->$name(...$arguments);
    }


    public function session($code){
        $config=$this->config;
        $app = Factory::miniProgram($config);
        return $app->auth->session($code);
    }

    public function decryptData($session, $iv, $encryptedData){
        $config=$this->config;
        $app = Factory::miniProgram($config);
        $decryptedData = $app->encryptor->decryptData($session, $iv, $encryptedData);
        return $decryptedData;
    }

    public function response($args=[]){
        $config=$this->config;
        $app = Factory::miniProgram($config);

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
        $res=$app->customer_service->message($msg)->to($args['openid'])->send();

        // 成功的返回结果
        // array (
        //     'errcode' => 0,
        //     'errmsg' => 'ok',
        //   )

        return $res;
    }

    public function upload($args=[]){
        $config=$this->config;
        $app = Factory::miniProgram($config);
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