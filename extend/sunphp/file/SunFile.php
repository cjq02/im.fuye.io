<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-03-07 11:16:34
 * @LastEditors: light
 * @LastEditTime: 2024-01-22 16:34:32
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

namespace sunphp\file;

defined('SUN_IN') or exit('Sunphp Access Denied');

use app\admin\model\CoreStorage;
use  think\facade\Filesystem;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use OSS\OssClient;
use OSS\Core\OssException;
use sunphp\censor\SunAliCensor;
use sunphp\censor\SunTencentCensor;
use sunphp\censor\SunQiniuCensor;
use sunphp\http\SunHttp;

class SunFile
{
    protected static $storage = '';

    public static function getStorage()
    {
        $storage = self::$storage;
        if (empty($storage)) {
            $get = request()->get();
            // 开启远程就是远程附件地址
            if (empty($get['i'])) {
                $storage = CoreStorage::where('acid', 0)->find();
            } else {
                $storage = CoreStorage::where('acid', $get['i'])->find();
                if (empty($storage) || $storage['type'] == 1) {
                    $storage = CoreStorage::where('acid', 0)->find();
                }
            }
            self::$storage = $storage;
        }
        return $storage;
    }

    public static function attachurl()
    {

        // $get=request()->get();

        // 开启远程就是远程附件地址
        $storage = self::getStorage();

        // 本地附件url
        $attachurl_local = request()->domain() . "/" . "attachment/";

        if (empty($storage)) {
            $type = 1;
        } else {
            $type = $storage->type;
        }

        $attachurl = '';

        switch ($type) {
            case 1:
                $attachurl = $attachurl_local;
                break;
            case 2:
                $oss = $storage->ali_oss;
                $attachurl = $oss['url'] . '/';
                break;
            case 3:
                $oss = $storage->tencent_cos;
                $attachurl = $oss['url'] . '/';
                break;
            case 4:
                $oss = $storage->qiniu;
                $attachurl = $oss['url'] . '/';
                break;
        }

        return $attachurl;
    }

    public static function upload($file_name, $type, $remote_upload = true, $local_delete = true)
    {

        $get = request()->get();

        $upfile = $_FILES[$file_name];
        if (strstr($upfile['name'], ".") === false) {
            $temp = explode("/", $upfile['type']);
            $ext = end($temp);
        } else {
            $ext = pathinfo($upfile['name'], PATHINFO_EXTENSION);
            $ext = strtolower($ext);
        }

        //检查type类型
        $allow_type = ['image', 'audio', 'voice', 'video', 'file'];
        if (!in_array($type, $allow_type)) {
            $result = [
                "status" => 0,
                'message' => '上传失败！参数type错误',
                "path" => ''
            ];
            return $result;
        }


        //检查系统设置的上传后缀限制
        $s = CoreStorage::where('acid', 0)->field(['suffix', 'img_size', 'video_size', 'file_size'])->find();
        if (empty($s) || empty($s->suffix)) {
            $result = [
                "status" => 0,
                'message' => '上传失败！系统附件未设置',
                "path" => ''
            ];
            return $result;
        }

        $suffix = preg_split("/[\s\r\n]+/", $s->suffix);

        if (!in_array($ext, $suffix)) {
            $result = [
                "status" => 0,
                'message' => '上传失败！附件后缀不支持',
                "path" => ''
            ];
            return $result;
        }

        //检查大小限制
        switch ($type) {
            case 'image':
                if ($upfile['size'] > (1024 * $s->img_size)) {
                    $result = [
                        "status" => 0,
                        'message' => '上传失败！图片大小超过' . $s->img_size . 'KB',
                        "path" => ''
                    ];
                    return $result;
                }
                break;
            case 'audio':
            case 'voice':
            case 'video':
                if ($upfile['size'] > (1024 * $s->video_size)) {
                    $result = [
                        "status" => 0,
                        'message' => '上传失败！音视频大小超过' . $s->video_size . 'KB',
                        "path" => ''
                    ];
                    return $result;
                }
                break;
            case 'file':
                if ($upfile['size'] > (1024 * $s->file_size)) {
                    $result = [
                        "status" => 0,
                        'message' => '上传失败！文件大小超过' . $s->file_size . 'KB',
                        "path" => ''
                    ];
                    return $result;
                }
                break;
        }

        //手动增加上传文件的类型
        require(root_path() . 'extend/sunphp/config/filetype.php');

        //验证文件后缀和MIME
        $check_mime = true;
        if (array_key_exists($ext, $sunphp_file_type)) {
            //检查mime类型
            if (is_array($sunphp_file_type[$ext])) {
                if (!in_array($upfile['type'], $sunphp_file_type[$ext])) {
                    $check_mime = false;
                }
            } else {
                if ($upfile['type'] != $sunphp_file_type[$ext]) {
                    $check_mime = false;
                }
            }
        } else {
            $check_mime = false;
        }

        if (!$check_mime) {
            $result = [
                "status" => 0,
                'message' => '上传失败！MIME类型错误',
                "path" => ''
            ];
            return $result;
        }


        //生成文件路径
        if (!empty($get['i']) && intval($get['i']) > 0) {
            $uniacid = intval($get['i']);
            $path = "{$type}s/{$uniacid}/" . date('Y/m');
        } else {
            $path = "{$type}s/system";
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
        } while (file_exists(root_path() . "attachment/" . $path . "/" . $filename));

        $file = request()->file($file_name);
        $res = Filesystem::putFileAs($path, $file, $filename);


        // window下res是反斜杠
        // $res = Filesystem::putFile( $path, $file,'md5');
        // str_replace('\\', '/', $res)

        //判断是否上传到云存储，是否上传到云存储后删除本地文件
        if ($remote_upload) {
            self::remoteUpload($path . "/" . $filename, $local_delete);
        }

        $result = [
            "status" => 1,
            "message" => "上传成功",
            "path" => $res
        ];
        return $result;
    }

    // oss云存储上传文件
    /*
    *  $file 文件路径
    *  $local_delete 是否删除本地文件
    *  $censor 是否需要审核
    *  $censor_config 审核配置，未配置则使用默认配置
    */
    public static function remoteUpload($file, $local_delete = true, $censor = true, $censor_config = [])
    {

        // 自定义审查配置，覆盖默认配置
        /*  $censor_config = [
            'scenes' => ['pulp', 'terror', 'politician', 'ads'],//自定义审查级别
            'type' => 'image',//自定义审查类型
            'sync' => true,//同步返回结果，还是异步返回任务ID
            'remote_delete'=>true,//是否删除违规的云存储文件
        ]; */


        //远程上传，并尝试删除本地的文件。
        // $get=request()->get();
        $storage = self::getStorage();


        // censor审查配置
        if ($censor) {
            if (empty($storage['censor']) || empty($storage['censor']['scenes']) || empty($storage['censor']['type'])) {
                $censor = false;
            } else {
                $censor_default = [
                    'scenes' => $storage['censor']['scenes'], //自定义审查级别
                    'type' => '', //默认文件是未知类型
                    'sync' => true, //同步返回结果，还是异步返回任务ID
                    'remote_delete' => true //是否删除违规的云存储文件
                ];
                $censor_type = [
                    'image' => ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'tif'],
                    'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'ts', 'mpg'],
                    'audio' => ['mp3', 'wav', 'm3u8', 'aac', 'amr', 'ogg', 'wma']
                ];
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $ext = strtolower($ext);
                foreach ($censor_type as $k => $v) {
                    if (in_array($ext, $v)) {
                        $censor_default['type'] = $k;
                        break;
                    }
                }
                $censor_config = array_merge($censor_default, $censor_config);

                // 如果未找到类型
                // 内容安全不支持的类型
                if (empty($censor_config['type']) || !in_array($censor_config['type'], $storage['censor']['type'])) {
                    $censor = false;
                }
            }
        }


        switch ($storage['type']) {
            case 1:
                return "云存储已关闭";
                break;
            case 2:
                $ali = $storage['ali_oss'];
                // 阿里云账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM用户进行API访问或日常运维，请登录RAM控制台创建RAM用户。
                $accessKeyId = $ali['accesskey'];
                $accessKeySecret = $ali['secretkey'];
                // Endpoint以杭州为例，其它Region请按实际情况填写。
                $endpoint = $ali['endpoint'] ? $ali['endpoint'] : 'https://oss-cn-beijing.aliyuncs.com';
                // 填写Bucket名称，例如examplebucket。
                $bucket = $ali['bucket'];

                // 要上传文件的本地路径
                $filePath = root_path() . "attachment/" . $file;
                // 上传到存储后保存的文件名
                $object = $file;

                $upload_res = true;
                try {
                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $ossClient->uploadFile($bucket, $object, $filePath);
                } catch (OssException $e) {
                    print_r($e->getMessage());
                    $upload_res = false;
                }
                //删除本地文件
                if ($local_delete) {
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }

                if ($upload_res) {
                    //是否开启了内容审查
                    if ($censor) {
                        return self::remoteCensor($file, $censor_config['type'], $censor_config['scenes'], $censor_config['sync'], $censor_config['remote_delete']);
                    }
                }

                return $upload_res;
                break;
            case 3:
                $tencent = $storage['tencent_cos'];

                $secretId = $tencent['accesskey']; //替换为用户的 secretId，请登录访问管理控制台进行查看和管理，https://console.cloud.tencent.com/cam/capi
                $secretKey = $tencent['secretkey']; //替换为用户的 secretKey，请登录访问管理控制台进行查看和管理，https://console.cloud.tencent.com/cam/capi

                $region = $tencent['region'] ? $tencent['region'] : "ap-nanjing"; //替换为用户的 region，已创建桶归属的region可以在控制台查看，https://console.cloud.tencent.com/cos5/bucket

                // 填写Bucket名称，例如examplebucket。
                $bucket = $tencent['bucket'];

                // 要上传文件的本地路径
                $filePath = root_path() . "attachment/" . $file;
                // 上传到存储后保存的文件名
                $object = $file;

                //协议头部，默认为http
                $schema = request()->scheme();

                $cosClient = new \Qcloud\Cos\Client(
                    array(
                        'region' => $region,
                        'schema' => $schema,
                        'credentials' => array(
                            'secretId'  => $secretId,
                            'secretKey' => $secretKey
                        )
                    )
                );

                $upload_res = true;

                try {
                    $result = $cosClient->upload(
                        $bucket = $bucket, //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                        $key = $object, //此处的 key 为对象键
                        $body = fopen($filePath, 'rb')
                    );
                    // 请求成功
                    // print_r($result);
                } catch (\Exception $e) {
                    // 请求失败
                    print_r($e);
                    $upload_res = false;
                };

                //删除本地文件
                if ($local_delete) {
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
                if ($upload_res) {
                    //是否开启了内容审查
                    if ($censor) {
                        return self::remoteCensor($file, $censor_config['type'], $censor_config['scenes'], $censor_config['sync'], $censor_config['remote_delete']);
                    }
                }
                return $upload_res;
                break;
            case 4:

                $qiniu = $storage['qiniu'];
                $accessKey = $qiniu['accesskey'];
                $secretKey = $qiniu['secretkey'];
                $bucket = $qiniu['bucket'];

                // 构建鉴权对象
                $auth = new Auth($accessKey, $secretKey);
                // 生成上传 Token
                $token = $auth->uploadToken($bucket);
                // 要上传文件的本地路径
                $filePath = root_path() . "attachment/" . $file;
                // 上传到存储后保存的文件名
                $key = $file;
                // 初始化 UploadManager 对象并进行文件的上传。
                $uploadMgr = new UploadManager();
                // 调用 UploadManager 的 putFile 方法进行文件的上传。
                list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath, null, 'application/octet-stream', true, null, 'v2');

                //删除本地文件
                if ($local_delete) {
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
                if ($err !== null) {
                    var_dump($err);
                    return false;
                } else {
                    // 上传成功
                    // var_dump($ret);

                    //是否开启了内容审查
                    if ($censor) {
                        return self::remoteCensor($file, $censor_config['type'], $censor_config['scenes'], $censor_config['sync'], $censor_config['remote_delete']);
                    }
                    return true;
                }
                break;
            default:
                break;
        }
    }


    /*
    * $file：oss文件路径
    * $type：文件类型
    * $sync：是否同步返回，音视频审核sync=false时返回的是任务ID
    * $remote_delete：上传后是否删除远程
    */
    public static function remoteCensor($file, $type, $scenes = [], $sync = true, $remote_delete = true)
    {

        // $get=request()->get();
        $storage = self::getStorage();

        if (empty($storage['censor']) || empty($storage['censor']['scenes']) || empty($storage['censor']['type'])) {
            // 框架未配置内容审查
            return [
                "status" => 0, //0失败1成功
                'message' => '未配置内容安全审查',
                "data" => ''
            ];
        }

        if (!in_array($type, $storage['censor']['type'])) {
            // 未配置该类型
            return [
                "status" => 0, //0失败1成功
                'message' => '未配置该审查类型',
                "data" => ''
            ];
        }

        if (empty($scenes)) {
            $scenes = $storage['censor']['scenes'];
        }


        switch ($storage['type']) {
            case 1:
                return "云存储已关闭";
                break;
            case 2:
                $ali = $storage['ali_oss'];
                // 阿里云账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM用户进行API访问或日常运维，请登录RAM控制台创建RAM用户。
                $accessKeyId = $ali['accesskey'];
                $accessKeySecret = $ali['secretkey'];
                // Endpoint以杭州为例，其它Region请按实际情况填写。
                $endpoint = $ali['endpoint'] ? $ali['endpoint'] : 'https://oss-cn-beijing.aliyuncs.com';
                // 填写Bucket名称，例如examplebucket。
                $bucket = $ali['bucket'];

                $pattern = '/oss\-(.*)\.aliyuncs/';
                preg_match($pattern, $endpoint, $matches);
                $region = $matches[1];

                // 内容审查节点
                $endpoint = "green-cip.{$region}.aliyuncs.com";

                $attachurl = $storage['url'];

                switch ($type) {
                    case 'text':
                        $censor_data = [
                            'service' => 'chat_detection',
                            'content' => $file
                        ];
                        $aliCensor = new SunAliCensor();
                        $result = $aliCensor->censorText($accessKeyId, $accessKeySecret, $endpoint, $region, $censor_data);
                        return $result;
                        break;
                    case 'image':
                        $serviceParameters = array(
                            // 待检测oss文件。 示例：image/001.jpg
                            'ossObjectName' => $file,
                            // 待检测文件所在bucket的区域。 示例：cn-shanghai
                            'ossRegionId' => $region,
                            // 待检测文件所在bucket名称。示例：bucket001
                            'ossBucketName' => $bucket,
                            // 数据唯一标识。
                            'dataId' => uniqid()
                        );

                        $aliCensor = new SunAliCensor();
                        $result = $aliCensor->censorImage($accessKeyId, $accessKeySecret, $endpoint, $serviceParameters);
                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $aliCensor->deleteFile($accessKeyId, $accessKeySecret, $endpoint, $bucket, $file);
                        }
                        return $result;
                        break;
                    case 'audio':
                        $censor_data = [
                            'service' => 'audio_media_detection',
                            'url' => $attachurl . '/' . $file
                        ];
                        $aliCensor = new SunAliCensor();
                        $result = $aliCensor->censorAudio($accessKeyId, $accessKeySecret, $endpoint, $region, $censor_data,$sync);
                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $aliCensor->deleteFile($accessKeyId, $accessKeySecret, $endpoint, $bucket, $file);
                        }
                        return $result;
                        break;
                    case 'video':
                        $censor_data = [
                            'service' => 'videoDetection',
                            'url' => $attachurl . '/' . $file
                        ];
                        $aliCensor = new SunAliCensor();
                        $result = $aliCensor->censorVideo($accessKeyId, $accessKeySecret, $endpoint, $region, $censor_data,$sync);

                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $aliCensor->deleteFile($accessKeyId, $accessKeySecret, $endpoint, $bucket, $file);
                        }

                        return $result;
                        break;
                    default:
                        break;
                }

                break;
            case 3:
                $tencent = $storage['tencent_cos'];

                $secretId = $tencent['accesskey']; //替换为用户的 secretId，请登录访问管理控制台进行查看和管理，https://console.cloud.tencent.com/cam/capi
                $secretKey = $tencent['secretkey']; //替换为用户的 secretKey，请登录访问管理控制台进行查看和管理，https://console.cloud.tencent.com/cam/capi

                $region = $tencent['region'] ? $tencent['region'] : "ap-nanjing"; //替换为用户的 region，已创建桶归属的region可以在控制台查看，https://console.cloud.tencent.com/cos5/bucket

                // 填写Bucket名称，例如examplebucket。
                $bucket = $tencent['bucket'];
                $attachurl = $storage['url'];
                switch ($type) {
                    case 'text':
                        $text_data = array(
                            'Bucket' => $bucket, //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                            'Input' => array(
                                'Content' => base64_encode($file), // 文本需base64_encode
                                //            'DataId' => '', // 选填 该字段在审核结果中会返回原始内容，长度限制为512字节。您可以使用该字段对待审核的数据进行唯一业务标识。
                            ),
                            //        'Conf' => array(
                            //            'BizType' => '',
                            //        ), // 非必选，在BizType不传的情况下，走默认策略及默认审核场景。
                        );
                        $tencentCensor = new SunTencentCensor();
                        $result = $tencentCensor->censorText($secretId, $secretKey, $region, $text_data);
                        return $result;
                        break;
                    case 'image':
                        $image_data = array(
                            'Bucket' => $bucket, //存储桶名称，由 BucketName-Appid 组成，可以在 COS 控制台查看 https://console.cloud.tencent.com/cos5/bucket
                            'Key' => $file, // 桶文件
                            'ci-process' => 'sensitive-content-recognition',
                            //    'BizType' => '', // 可选 腾讯后台定制化策略，不传走默认策略
                            //        'Interval' => 5, // 可选 审核 GIF 时使用 截帧的间隔
                            //        'MaxFrames' => 5, // 可选 针对 GIF 动图审核的最大截帧数量，需大于0。
                            //        'LargeImageDetect' => '',
                            //        'DataId' => '',
                            //        'Async' => '',
                            //        'Callback' => '',
                        );
                        $tencentCensor = new SunTencentCensor();
                        $result = $tencentCensor->censorImage($secretId, $secretKey, $region, $image_data);

                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $tencentCensor->deleteFile($secretId, $secretKey, $region, $bucket, $file);
                        }

                        return $result;
                        break;
                    case 'video':
                        $video_data = array(
                            'Bucket' => $bucket, //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                            'Input' => array(
                                'Object' => $file
                            ),
                            'Conf' => array(
                                //            'BizType' => '', // 可选 定制化策略
                                //            'Callback' => '', // 可选 回调URL
                                //            'DetectContent' => 1, // 可选 用于指定是否审核视频声音，当值为0时：表示只审核视频画面截图；值为1时：表示同时审核视频画面截图和视频声音。默认值为0。
                                //            'CallbackVersion' => 'Detail', // 可选 回调内容的结构，有效值：Simple（回调内容包含基本信息）、Detail（回调内容包含详细信息）。默认为 Simple。
                                'Snapshot' => array(
                                    //                'Mode' => 'Average', // 可选 截帧模式，默认值为 Interval。Interval 表示间隔模式；Average 表示平均模式；Fps 表示固定帧率模式。
                                    //                'TimeInterval' => 50, // 可选 视频截帧频率
                                    'Count' => '3', // 视频截帧数量
                                )
                            )
                        );
                        $tencentCensor = new SunTencentCensor();
                        $result = $tencentCensor->censorVideo($secretId, $secretKey, $region, $video_data,$sync);

                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $tencentCensor->deleteFile($secretId, $secretKey, $region, $bucket, $file);
                        }

                        return $result;
                        break;
                    case 'audio':
                        $audio_data = array(
                            'Bucket' => $bucket, //存储桶名称，由BucketName-Appid 组成，可以在COS控制台查看 https://console.cloud.tencent.com/cos5/bucket
                            'Input' => array(
                                'Object' => $file
                            )
                        );
                        $tencentCensor = new SunTencentCensor();
                        $result = $tencentCensor->censorAudio($secretId, $secretKey, $region, $audio_data,$sync);
                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $tencentCensor->deleteFile($secretId, $secretKey, $region, $bucket, $file);
                        }
                        return $result;
                        break;
                    default:
                        break;
                }
                break;
            case 4:

                $qiniu = $storage['qiniu'];
                $accessKey = $qiniu['accesskey'];
                $secretKey = $qiniu['secretkey'];
                $bucket = $qiniu['bucket'];

                switch ($type) {
                    case 'text':
                        $text_url = $file;
                        $body = [
                            'data' => ['text' => $text_url],
                            'params' => ['scenes' => ['antispam']]
                        ];

                        $qiniuCensor = new SunQiniuCensor();
                        $result = $qiniuCensor->censorText($accessKey, $secretKey, $body);
                        return $result;
                        break;
                    case 'image':
                        $image_url = 'qiniu:///' . $bucket . '/' . $file;

                        $body = [
                            'data' => ['uri' => $image_url],
                            'params' => ['scenes' => $scenes]
                        ];

                        $qiniuCensor = new SunQiniuCensor();
                        $result = $qiniuCensor->censorImage($accessKey, $secretKey, $body);

                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $qiniuCensor->deleteFile($accessKey, $secretKey, $bucket, $file);
                        }

                        return $result;
                        break;
                    case 'video':

                        $video_url = 'qiniu:///' . $bucket . '/' . $file;

                        // data.id  异步处理的返回结果中会带上该信息
                        $body = [
                            'data' => ['uri' => $video_url],
                            'params' => ['scenes' => $scenes, "cut_param" => ["interval_msecs" => 10000]]
                        ];

                        $qiniuCensor = new SunQiniuCensor();
                        $result = $qiniuCensor->censorVideo($accessKey, $secretKey, $body,$sync);

                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $qiniuCensor->deleteFile($accessKey, $secretKey, $bucket, $file);
                        }

                        return $result;

                        break;
                    case 'audio':

                        $audio_url = 'qiniu:///' . $bucket . '/' . $file;


                        // data.id  异步处理的返回结果中会带上该信息
                        $body = [
                            'data' => ['uri' => $audio_url],
                            'params' => ['scenes' => ['antispam']]
                        ];


                        $qiniuCensor = new SunQiniuCensor();
                        $result = $qiniuCensor->censorAudio($accessKey, $secretKey, $body,$sync);

                        if (is_array($result) && $result['status'] == 0 && $remote_delete) {
                            $qiniuCensor->deleteFile($accessKey, $secretKey, $bucket, $file);
                        }
                        return $result;
                        break;
                    default:
                        break;
                }


                break;
            default:
                break;
        }

        // 默认审核通过
        return true;
    }

    public static function  remoteDownload($url, $type = '', $file_path = '', $remote_upload = true, $local_delete = true)
    {

        //检查type类型
        $allow_type = ['image', 'audio', 'voice', 'video', 'file'];
        if (!in_array($type, $allow_type)) {
            $result = [
                "status" => 0,
                'message' => '操作失败！参数type错误',
                "path" => ''
            ];
            return $result;
        }

        //远程下载
        $output = $file_path; //本地完整的文件地址（目录+名称+后缀）
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  //兼容https路径文件(忽略证书)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);  //兼容https路径文件(忽略证书)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file = curl_exec($ch);
        curl_close($ch);

        if (!empty($file_path)) {
            if (file_put_contents($output, $file)) {
                $result = [
                    "status" => 1,
                    "message" => "下载成功",
                    "path" =>  $output
                ];
                return $result;
            }
        } else {
            //下载到本地附件，尝试远程存储上传

            //生成文件路径
            $get = request()->get();

            if (!empty($get['i']) && intval($get['i']) > 0) {
                $uniacid = intval($get['i']);
                $path = "{$type}s/{$uniacid}/" . date('Y/m');
            } else {
                $path = "{$type}s/system";
            }


            // 创建多级目录
            if (!is_dir(root_path() . "attachment/" . $path)) {
                mkdir(root_path() . "attachment/" . $path, 0777, true);
            }


            switch ($type) {
                case 'image':
                    $ext = 'jpg';
                    break;
                case 'audio':
                case 'voice':
                    $ext = 'mp3';
                    break;
                case 'video':
                    $ext = 'mp4';
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
            } while (file_exists(root_path() . "attachment/" . $path . "/" . $filename));


            if (file_put_contents(root_path() . "attachment/" . $path . "/" . $filename, $file)) {
                //判断是否上传到云存储，是否上传到云存储后删除本地文件
                if ($remote_upload) {
                    self::remoteUpload($path . "/" . $filename, $local_delete);
                }

                $result = [
                    "status" => 1,
                    "message" => "下载成功",
                    "path" =>  $path . "/" . $filename
                ];
                return $result;
            }
        }


        $result = [
            "status" => 0,
            'message' => '操作失败！远程下载错误',
            "path" => ''
        ];
        return $result;
    }


    //删除目录和目录下所有文件
    /*
    * $dir目录不带斜杠/
    */
    public static function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return true;
        }
        try {
            $handle = opendir($dir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if (is_dir($dir . '/' . $entry)) {
                        self::removeDirectory($dir . '/' . $entry);
                    } else {
                        unlink($dir . '/' . $entry);
                    }
                }
            }
            closedir($handle);
            rmdir($dir);
        } catch (\Exception $e) {
            // dump($e);
            return false;
        }
        return true;
    }

    // 拷贝目录和目录下所有文件
    /*
    * $dir目录没有斜杠/
    */
    public static function copyDirectory($old_dir, $new_dir)
    {
        try {
            $dir = opendir($old_dir);
            if (!is_dir($new_dir)) {
                mkdir($new_dir, 0777, true);
            }
            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($old_dir . '/' . $file)) {
                        self::copyDirectory($old_dir . '/' . $file, $new_dir . '/' . $file);
                    } else {
                        copy($old_dir . '/' . $file, $new_dir . '/' . $file);
                    }
                }
            }
            closedir($dir);
        } catch (\Exception $e) {
            // dump($e);
            return false;
        }
        return true;
    }



    /* 文件写入，加锁 */
    public static function write($file, $mode, $content)
    {
        $fp = fopen($file, $mode);
        if (flock($fp, LOCK_EX)) {
            // 进行排它型锁定
            fwrite($fp, $content);
            flock($fp, LOCK_UN); // 释放锁定
        } else {
            //文件锁定中，程序阻塞
        }
        fclose($fp);
    }
}
