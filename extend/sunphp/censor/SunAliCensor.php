<?php
/*
 * @Author: SonLight Tech
 * @Date: 2024-01-19 14:55:29
 * @LastEditors: light
 * @LastEditTime: 2024-01-22 16:25:35
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\censor;

defined('SUN_IN') or exit('Sunphp Access Denied');

use AlibabaCloud\SDK\Green\V20220302\Models\ImageModerationResponse;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use AlibabaCloud\SDK\Green\V20220302\Green;
use AlibabaCloud\SDK\Green\V20220302\Models\ImageModerationRequest;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\SDK\Green\V20220302\Models\TextModerationRequest;
use AlibabaCloud\SDK\Green\V20220302\Models\VoiceModerationRequest;
use AlibabaCloud\Tea\Exception\TeaUnableRetryError;
use AlibabaCloud\SDK\Green\V20220302\Models\VoiceModerationResultRequest;
use AlibabaCloud\SDK\Green\V20220302\Models\VideoModerationResultRequest;
use AlibabaCloud\SDK\Green\V20220302\Models\VideoModerationRequest;
use OSS\OssClient;
use OSS\Core\OssException;

class SunAliCensor
{

    public function deleteFile($accessKeyId, $accessKeySecret, $endpoint, $bucket, $file)
    {
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->deleteObject($bucket, $file);
        } catch (OssException $e) {
            // printf(__FUNCTION__ . ": FAILED\n");
            // printf($e->getMessage() . "\n");
        }
    }

    /**
     * 创建请求客户端
     * @param $accessKeyId
     * @param $accessKeySecret
     * @param $endpoint
     * @return Green
     */
    public function create_client($accessKeyId, $accessKeySecret, $endpoint): Green
    {
        $config = new Config([
            "accessKeyId" => $accessKeyId,
            "accessKeySecret" => $accessKeySecret,
            // 设置HTTP代理。
            // "httpProxy" => "http://10.10.xx.xx:xxxx",
            // 设置HTTPS代理。
            // "httpsProxy" => "https://10.10.xx.xx:xxxx",
            "endpoint" => $endpoint,
        ]);
        return new Green($config);
    }

    /**
     * 提交检测任务
     * @param $accessKeyId
     * @param $accessKeySecret
     * @param $endpoint
     * @return ImageModerationResponse
     */
    public function invoke($accessKeyId, $accessKeySecret, $endpoint, $censor_data = []): ImageModerationResponse
    {
        // 注意：此处实例化的client请尽可能重复使用，避免重复建立连接，提升检测性能。
        $client = $this->create_client($accessKeyId, $accessKeySecret, $endpoint);
        // 创建RuntimeObject实例并设置运行参数。
        $runtime = new RuntimeOptions([]);
        // 检测参数构造。
        $request = new ImageModerationRequest();
        // $serviceParameters = array(
        //     // 待检测文件。 示例：image/001.jpg
        //     'ossObjectName' => 'image/001.jpg',
        //     // 待检测文件所在bucket的区域。 示例：cn-shanghai
        //     'ossRegionId' => 'cn-shanghai',
        //     // 待检测文件所在bucket名称。示例：bucket001
        //     'ossBucketName' => 'bucket001',
        //     // 数据唯一标识。
        //     'dataId' => uniqid()
        // );

        $serviceParameters = $censor_data;

        // 图片检测service：内容安全控制台图片增强版规则配置的serviceCode，示例：baselineCheck
        // 支持service请参考：https://help.aliyun.com/document_detail/467826.html?0#p-23b-o19-gff
        $request->service = "baselineCheck";
        $request->serviceParameters = json_encode($serviceParameters);
        // 提交检测
        return $client->imageModerationWithOptions($request, $runtime);
    }

    // 图片审查
    public function censorImage($accessKeyId, $accessKeySecret, $endpoint, $censor_data = [])
    {
        // $accessKeyId = '建议从环境变量中获取RAM用户AccessKey ID';
        // $accessKeySecret = '建议从环境变量中获取RAM用户AccessKey Secret';
        // // 接入区域和地址请根据实际情况修改
        // $endpoint = "green-cip.cn-shanghai.aliyuncs.com";

        try {
            $response = $this->invoke($accessKeyId, $accessKeySecret, $endpoint, $censor_data);
            // 自动路由。
            if (Utils::equalNumber(500, $response->statusCode) || Utils::equalNumber(500, $response->body->code)) {
                //区域切换到cn-beijing。
                $endpoint = "green-cip.cn-beijing.aliyuncs.com";
                $response = $this->invoke($accessKeyId, $accessKeySecret, $endpoint, $censor_data);
            }
            $result = json_decode(json_encode($response->body, JSON_UNESCAPED_UNICODE), true);

            // dump($result);

            if ($result['code'] == 200 && in_array($result['data']['result'][0]['label'], ['nonLabel', 'nonLabel_lib'])) {
                return true;
            } else {
                return [
                    "status" => 0, //0失败1成功
                    'message' => '内容违规，操作失败！',
                    "data" => ''
                ];
            }
            return true;
        } catch (\Exception $e) {
            // var_dump($e->getMessage());
            return [
                "status" => 0, //0失败1成功
                'message' => '内容安全审查失败',
                "data" => ''
            ];
        }
    }


    public function censorText($accessKeyId, $accessKeySecret, $endpoint, $regionId, $censor_data = [])
    {
        $request = new TextModerationRequest();
        /*
        文本检测service：内容安全控制台文本增强版规则配置的serviceCode，示例：chat_detection
        */
        $request->service = $censor_data['service'] ?? 'chat_detection';
        // 待检测数据。
        $arr = array('content' => $censor_data['content']);
        $request->serviceParameters = json_encode($arr);
        if (empty($arr) || empty(trim($arr["content"]))) {
            return true;
        }
        $config = new Config([
            "accessKeyId" => $accessKeyId,
            "accessKeySecret" => $accessKeySecret,
            // 设置HTTP代理。
            // "httpProxy" => "http://10.10.xx.xx:xxxx",
            // 设置HTTPS代理。
            // "httpsProxy" => "https://10.10.xx.xx:xxxx",
            "endpoint" => $endpoint,
            "regionId" => $regionId
        ]);
        // 注意，此处实例化的client请尽可能重复使用，避免重复建立连接，提升检测性能。
        $client = new Green($config);

        // 创建RuntimeObject实例并设置运行参数。
        $runtime = new RuntimeOptions([]);
        $runtime->readTimeout = 10000;
        $runtime->connectTimeout = 10000;

        try {
            // 调用接口，获取检测结果。
            $response = $client->textModerationWithOptions($request, $runtime);
            // 自动路由。
            if (Utils::equalNumber(500, $response->statusCode) || Utils::equalNumber(500, $response->body->code)) {
                //服务端错误，区域切换到cn-beijing
                $config->endpoint = "green-cip.cn-beijing.aliyuncs.com";
                $config->regionId = "cn-beijing";
                $client = new Green($config);
                $response = $client->textModerationWithOptions($request, $runtime);
            }

            $result = json_decode(json_encode($response->body, JSON_UNESCAPED_UNICODE), true);

            // dump($result);

            if ($result['code'] == 200 && !empty($result['data']['labels'])) {
                return [
                    "status" => 0, //0失败1成功
                    'message' => '内容违规，操作失败！',
                    "data" => ''
                ];
            }
            return true;
        } catch (\Exception $e) {
            // var_dump($e->getMessage());
            return [
                "status" => 0, //0失败1成功
                'message' => '内容安全审查失败',
                "data" => ''
            ];
        }
    }


    public function censorAudio($accessKeyId, $accessKeySecret, $endpoint, $regionId, $censor_data = [],$sync=true)
    {
        $config = new Config([
            "accessKeyId" => $accessKeyId,
            "accessKeySecret" => $accessKeySecret,
            // 设置HTTP代理。
            // "httpProxy" => "http://10.10.xx.xx:xxxx",
            // 设置HTTPS代理。
            // "httpsProxy" => "https://10.10.xx.xx:xxxx",
            "endpoint" => $endpoint,
            "regionId" => $regionId
        ]);
        $client = new Green($config);

        $request = new VoiceModerationRequest();
        // 检测类型：audio_media_detection语音文件检测；live_stream_detection语音直播流检测。
        $request->service = $censor_data['service'] ?? 'audio_media_detection';
        $serviceParameters = array('url' => $censor_data['url']);

        $request->serviceParameters = json_encode($serviceParameters);

        $runtime = new RuntimeOptions();
        $runtime->readTimeout = 6000;
        $runtime->connectTimeout = 3000;

        try {


            $response = $client->voiceModeration($request, $runtime);
            $result = json_decode(json_encode($response->body, JSON_UNESCAPED_UNICODE), true);
            if ($result['code'] == '200' && isset($result['data']['taskId'])) {

                // 根据taskId查询审核结果
                $taskId = $result['data']['taskId'];

                if($sync===false){
                    return [
                        'status'=>1,
                        'task_id'=>$taskId,
                    ];
                }

                $request = new VoiceModerationResultRequest();
                // 检测类型：audio_media_detection语音文件检测；live_stream_detection语音直播流检测。
                $request->service = "audio_media_detection";
                // 提交任务时返回的taskId。
                $serviceParameters = array('taskId' => $taskId);

                $request->serviceParameters = json_encode($serviceParameters);

                $runtime = new RuntimeOptions();
                $runtime->readTimeout = 6000;
                $runtime->connectTimeout = 3000;

                // 轮询查询审核结果
                for ($i = 0; $i < 100; $i++) {

                    try {
                        $response = $client->voiceModerationResult($request, $runtime);
                        $result_task = json_decode(json_encode($response->body, JSON_UNESCAPED_UNICODE), true);
                        // dump($result_task);

                        if ($result_task['code'] == 200) {
                            if (!empty($result_task['data']['sliceDetails'])) {
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
                    } catch (TeaUnableRetryError $e) {
                        // var_dump($e->getMessage());
                        // var_dump($e->getErrorInfo());
                        // var_dump($e->getLastException());
                        // var_dump($e->getLastRequest());
                    }
                }
            }


            return true;
        } catch (TeaUnableRetryError $e) {
            // var_dump($e->getMessage());
            // var_dump($e->getErrorInfo());
            // var_dump($e->getLastException());
            // var_dump($e->getLastRequest());
            return [
                "status" => 0, //0失败1成功
                'message' => '内容安全审查失败',
                "data" => ''
            ];
        }
    }


    public function censorVideo($accessKeyId, $accessKeySecret, $endpoint, $regionId, $censor_data = [],$sync=true)
    {
        $config = new Config([
            "accessKeyId" => $accessKeyId,
            "accessKeySecret" => $accessKeySecret,
            // 设置HTTP代理。
            // "httpProxy" => "http://10.10.xx.xx:xxxx",
            // 设置HTTPS代理。
            // "httpsProxy" => "https://10.10.xx.xx:xxxx",
            "endpoint" => $endpoint,
            "regionId" => $regionId
        ]);
        $client = new Green($config);

        $request = new VideoModerationRequest();
        // 检测类型：audio_media_detection语音文件检测；live_stream_detection语音直播流检测。
        $request->service = $censor_data['service'] ?? 'audio_media_detection';
        $serviceParameters = array('url' => $censor_data['url']);

        $request->serviceParameters = json_encode($serviceParameters);

        $runtime = new RuntimeOptions();
        $runtime->readTimeout = 6000;
        $runtime->connectTimeout = 3000;

        try {


            $response = $client->videoModerationWithOptions($request, $runtime);
            $result = json_decode(json_encode($response->body, JSON_UNESCAPED_UNICODE), true);
            if ($result['code'] == '200' && isset($result['data']['taskId'])) {

                // 根据taskId查询审核结果
                $taskId = $result['data']['taskId'];

                if($sync===false){
                    return [
                        'status'=>1,
                        'task_id'=>$taskId,
                    ];
                }

                $request = new VideoModerationResultRequest();
                // 检测类型：audio_media_detection语音文件检测；live_stream_detection语音直播流检测。
                $request->service = "videoDetection";
                // 提交任务时返回的taskId。
                $serviceParameters = array('taskId' => $taskId);

                $request->serviceParameters = json_encode($serviceParameters);

                $runtime = new RuntimeOptions();
                $runtime->readTimeout = 6000;
                $runtime->connectTimeout = 3000;

                // 轮询查询审核结果
                for ($i = 0; $i < 100; $i++) {

                    try {
                        $response = $client->videoModerationResultWithOptions($request, $runtime);
                        $result_task = json_decode(json_encode($response->body, JSON_UNESCAPED_UNICODE), true);
                        // dump($result_task);

                        if ($result_task['code'] == 200) {
                            if (!empty($result_task['data']['FrameResult']['Frames']) || !empty($result_task['data']['AudioResult']['SliceDetails'])) {
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
                    } catch (TeaUnableRetryError $e) {
                        // var_dump($e->getMessage());
                        // var_dump($e->getErrorInfo());
                        // var_dump($e->getLastException());
                        // var_dump($e->getLastRequest());
                    }
                }
            }


            return true;
        } catch (TeaUnableRetryError $e) {
            // var_dump($e->getMessage());
            // var_dump($e->getErrorInfo());
            // var_dump($e->getLastException());
            // var_dump($e->getLastRequest());
            return [
                "status" => 0, //0失败1成功
                'message' => '内容安全审查失败',
                "data" => ''
            ];
        }
    }
}
