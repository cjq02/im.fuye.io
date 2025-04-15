<?php
/*
 * @Author: SonLight Tech
 * @Date: 2024-01-19 14:55:29
 * @LastEditors: light
 * @LastEditTime: 2024-01-22 16:43:05
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\censor;

defined('SUN_IN') or exit('Sunphp Access Denied');



class SunTencentCensor
{

    public function deleteFile($secretId, $secretKey, $region, $bucket, $file)
    {

        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $region,
                'scheme' => 'https', //协议头部，默认为http
                'credentials' => array(
                    'secretId'  => $secretId,
                    'secretKey' => $secretKey
                )
            )
        );


        try {
            $result = $cosClient->deleteObject(array(
                'Bucket' => $bucket, //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                'Key' => $file //若多路径则写为folder/exampleobject，不要在第一层带/，否则删除会失败
            ));
            // 请求成功
            // print_r($result);
        } catch (\Exception $e) {
            // 请求失败
            // echo($e);
        }
    }


    // 图片审查
    public function censorImage($secretId, $secretKey, $region, $censor_data = [])
    {
        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $region,
                'schema' => 'https', // 审核时必须为 https
                'credentials' => array(
                    'secretId' => $secretId,
                    'secretKey' => $secretKey
                )
            )
        );
        try {
            //存储桶图片审核
            $result = $cosClient->detectImage($censor_data);
            if ($result['Result'] == 1) {
                return [
                    "status" => 0, //0失败1成功
                    'message' => '内容违规，操作失败！',
                    "data" => ''
                ];
            }
            return true;
        } catch (\Exception $e) {
            // 请求失败
            // echo($e);
            return [
                "status" => 0, //0失败1成功
                'message' => '内容安全审查失败',
                "data" => ''
            ];
        }
    }


    // 文本审查
    public function censorText($secretId, $secretKey, $region, $censor_data = [])
    {
        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $region,
                'schema' => 'https', // 审核时必须为 https
                'credentials' => array(
                    'secretId' => $secretId,
                    'secretKey' => $secretKey
                )
            )
        );

        try {
            //存储桶图片审核
            $result = $cosClient->detectText($censor_data);
            if ($result['JobsDetail']['Result'] == 1) {
                return [
                    "status" => 0, //0失败1成功
                    'message' => '内容违规，操作失败！',
                    "data" => ''
                ];
            }
            return true;
        } catch (\Exception $e) {
            // 请求失败
            // echo($e);
            return [
                "status" => 0, //0失败1成功
                'message' => '内容安全审查失败',
                "data" => ''
            ];
        }
    }


    // 音频审查
    public function censorAudio($secretId, $secretKey, $region, $censor_data = [],$sync=true)
    {
        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $region,
                'schema' => 'https', // 审核时必须为 https
                'credentials' => array(
                    'secretId' => $secretId,
                    'secretKey' => $secretKey
                )
            )
        );

        try {
            //存储桶图片审核
            $result = $cosClient->detectAudio($censor_data);

            $job_id = $result['JobsDetail']['JobId'];

            if($sync===false){
                return [
                    'status'=>1,
                    'task_id'=>$job_id,
                ];
            }

            for ($i = 0; $i < 100; $i++) {

                $result = $cosClient->getDetectAudioResult(array(
                    'Bucket' => $censor_data['Bucket'], //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                    'Key' => $job_id, // JobId
                ));

                if ($result['JobsDetail']['State'] == 'Success') {
                    if ($result['JobsDetail']['Result'] == 1) {
                        return [
                            "status" => 0, //0失败1成功
                            'message' => '内容违规，操作失败！',
                            "data" => ''
                        ];
                    }
                    break;
                } else {
                    sleep(3);
                }
            }
            return true;
        } catch (\Exception $e) {
            // 请求失败
            // echo($e);
            return [
                "status" => 0, //0失败1成功
                'message' => '内容安全审查失败',
                "data" => ''
            ];
        }
    }


    // 视频审查
    public function censorVideo($secretId, $secretKey, $region, $censor_data = [],$sync=true)
    {
        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $region,
                'schema' => 'https', // 审核时必须为 https
                'credentials' => array(
                    'secretId' => $secretId,
                    'secretKey' => $secretKey
                )
            )
        );

        try {
            //存储桶审核
            $result = $cosClient->detectVideo($censor_data);
            $job_id = $result['JobsDetail']['JobId'];

            if($sync===false){
                return [
                    'status'=>1,
                    'task_id'=>$job_id,
                ];
            }

            for ($i = 0; $i < 100; $i++) {

                $result = $cosClient->getDetectVideoResult(array(
                    'Bucket' => $censor_data['Bucket'], //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                    'Key' => $job_id, // JobId
                ));

                if ($result['JobsDetail']['State'] == 'Success') {
                    if ($result['JobsDetail']['Result'] == 1) {
                        return [
                            "status" => 0, //0失败1成功
                            'message' => '内容违规，操作失败！',
                            "data" => ''
                        ];
                    }
                    break;
                } else {
                    sleep(3);
                }
            }
            return true;
        } catch (\Exception $e) {
            // 请求失败
            // echo($e);
            return [
                "status" => 0, //0失败1成功
                'message' => '内容安全审查失败',
                "data" => ''
            ];
        }
    }
}
