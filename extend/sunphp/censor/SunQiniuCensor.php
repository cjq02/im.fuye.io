<?php
/*
 * @Author: SonLight Tech
 * @Date: 2024-01-19 14:55:29
 * @LastEditors: light
 * @LastEditTime: 2024-01-22 16:11:27
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\censor;

defined('SUN_IN') or exit('Sunphp Access Denied');

use Qiniu\Auth;
use sunphp\http\SunHttp;

class SunQiniuCensor
{

    public function deleteFile($accessKey, $secretKey,$bucket,$file){
         // 清除远程违规内容
         $auth_del = new Auth($accessKey, $secretKey);
         $config = new \Qiniu\Config();
         $bucketManager = new \Qiniu\Storage\BucketManager($auth_del, $config);
         $err = $bucketManager->delete($bucket, $file);
         // print_r($err);
    }

    public function censorText($accessKey, $secretKey, $body)
    {

        // 构建鉴权对象
        // 注意！！！内容审核不需要时间戳参数！
        $options = ['disableQiniuTimestampSignature' => 1];
        $auth = new Auth($accessKey, $secretKey, $options);

        $url = 'http://ai.qiniuapi.com/v3/text/censor';
        $contentType = 'application/json';


        $sign = $auth->authorizationV2($url, 'POST', json_encode($body), $contentType);
        $header = ['Content-Type: ' . $contentType, 'Authorization: ' . $sign['Authorization']];
        $res = SunHttp::post($url, json_encode($body), $header);

        // result
        $res = json_decode($res, true);

        //同步返回结果
        if ($res['code'] == 200 && $res['result']['suggestion'] == 'block') {
            return [
                "status" => 0, //0失败1成功
                'message' => '内容违规，操作失败！',
                "data" => ''
            ];
        }
        return true;
    }


    public function censorImage($accessKey, $secretKey, $body)
    {

        // 构建鉴权对象
        // 注意！！！内容审核不需要时间戳参数！
        $options = ['disableQiniuTimestampSignature' => 1];
        $auth = new Auth($accessKey, $secretKey, $options);

        $url = 'http://ai.qiniuapi.com/v3/image/censor';
        $contentType = 'application/json';
        // 拦截违规图片级别
        // $suggestions = [
        //     'pass' => 'pass', // 通过
        //     'review' => 'review', // 疑似
        //     'block' => 'block' // 违规
        // ];


        $sign = $auth->authorizationV2($url, 'POST', json_encode($body), $contentType);
        $header = ['Content-Type: ' . $contentType, 'Authorization: ' . $sign['Authorization']];
        $res = SunHttp::post($url, json_encode($body), $header);

        // result
        $res = json_decode($res, true);

        //同步返回结果
        if ($res['code'] == 200 && $res['result']['suggestion'] == 'block') {
            return [
                "status" => 0, //0失败1成功
                'message' => '内容违规，操作失败！',
                "data" => ''
            ];
        }
        return true;
    }


    public function censorAudio($accessKey, $secretKey, $body,$sync=true)
    {

        // 构建鉴权对象
        // 注意！！！内容审核不需要时间戳参数！
        $options = ['disableQiniuTimestampSignature' => 1];
        $auth = new Auth($accessKey, $secretKey, $options);


        $url = 'http://ai.qiniuapi.com/v3/audio/censor';
        $contentType = 'application/json';
        // 拦截违规图片级别
        // $suggestions = [
        //     'pass' => 'pass', // 通过
        //     'review' => 'review', // 疑似
        //     'block' => 'block' // 违规
        // ];


        $sign = $auth->authorizationV2($url, 'POST', json_encode($body), $contentType);
        $header = ['Content-Type: ' . $contentType, 'Authorization: ' . $sign['Authorization']];
        $res = SunHttp::post($url, json_encode($body), $header);

        // result
        $res = json_decode($res, true);

        if($sync===false){
            return [
                'status'=>1,
                'task_id'=>$res['id'],
            ];
        }

        // 根据job_id查询审核结果
        $job_url = 'http://ai.qiniuapi.com/v3/jobs/audio/' . $res['id'];
        $job_sign = $auth->authorizationV2($job_url, 'GET', '', $contentType);
        $job_header = ['Content-Type: ' . $contentType, 'Authorization: ' . $job_sign['Authorization']];

        // 轮询查询审核结果
        for ($i = 0; $i < 100; $i++) {
            $job_res = SunHttp::get($job_url, $job_header);
            $job_res = json_decode($job_res, true);
            if ($job_res['status'] == 'FINISHED') {
                break;
            } else {
                sleep(3);
            }
        }

        //同步返回结果
        if ($job_res['status'] == 'FINISHED' && $job_res['result']['code'] == 200 && $job_res['result']['result']['suggestion'] == 'block') {
            return [
                "status" => 0, //0失败1成功
                'message' => '内容违规，操作失败！',
                "data" => ''
            ];
        }

        return true;
    }

    public function censorVideo($accessKey, $secretKey, $body,$sync=true)
    {

        // 构建鉴权对象
        // 注意！！！内容审核不需要时间戳参数！
        $options = ['disableQiniuTimestampSignature' => 1];
        $auth = new Auth($accessKey, $secretKey, $options);

        $url = 'http://ai.qiniuapi.com/v3/video/censor';
        $contentType = 'application/json';

        // 拦截违规图片级别
        // $suggestions = [
        //     'pass' => 'pass', // 通过
        //     'review' => 'review', // 疑似
        //     'block' => 'block' // 违规
        // ];


        $sign = $auth->authorizationV2($url, 'POST', json_encode($body), $contentType);
        $header = ['Content-Type: ' . $contentType, 'Authorization: ' . $sign['Authorization']];
        $res = SunHttp::post($url, json_encode($body), $header);

        // result
        $res = json_decode($res, true);

        if($sync===false){
            return [
                'status'=>1,
                'task_id'=>$res['job'],
            ];
        }

        // 根据job_id查询审核结果
        $job_url = 'http://ai.qiniuapi.com/v3/jobs/video/' . $res['job'];
        $job_sign = $auth->authorizationV2($job_url, 'GET', '', $contentType);
        $job_header = ['Content-Type: ' . $contentType, 'Authorization: ' . $job_sign['Authorization']];

        // 轮询查询审核结果
        for ($i = 0; $i < 100; $i++) {
            $job_res = SunHttp::get($job_url, $job_header);
            $job_res = json_decode($job_res, true);
            if ($job_res['status'] == 'FINISHED') {
                break;
            } else {
                sleep(3);
            }
        }

        //同步返回结果
        if ($job_res['status'] == 'FINISHED' && $job_res['result']['code'] == 200 && $job_res['result']['result']['suggestion'] == 'block') {

            return [
                "status" => 0, //0失败1成功
                'message' => '内容违规，操作失败！',
                "data" => ''
            ];
        }

        return true;
    }
}
