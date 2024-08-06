<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-05-31 17:20:14
 * @LastEditors: light
 * @LastEditTime: 2024-08-06 18:12:55
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

use sunphp\file\SunFile;
use app\admin\model\CoreAttachment;



/* 注意这里的自定义类名 */
class WeFrameFile{

    public function result($errno=0, $message=[]){
        $result = [
            'message' =>[
                'errno' => $errno,//0成功，非0错误
                'message' => $message
            ],
            'redirect'=> "",
            'type'=> "ajax"
        ];

        header('Content-Type:application/json');
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }


    // 通过框架组件上传内容
    public function upload(){
        global $_W,$_GPC;

        $type = $_GPC['upload_type'];

        // sunphp版本支持file上传
        $type_array = ['image', 'audio', 'voice', 'video', 'file'];

        if (in_array($type, $type_array)) {
            // $upfile = $_FILES['file'];
            // $result = file_upload($upfile, $type);

            // 本地不删除
            $res=SunFile::upload('file',$type,true,false);
            if($res['status']==1){
                // 写入数据库
                $data=[
                    'uniacid'=>$_W['uniacid'],
                    'uid' => $_W['uid'],
                    'filename' => $_FILES['file']['name'],
                    'attachment' => $res['path'],
                    'type' => $type == 'image' ? 1 : 2,
                    'createtime' => time(),
                ];
                CoreAttachment::create($data);
                return $this->result(0);
            }else{
                // 上传失败
                return $this->result(-1);
            }
        }
    }


    // 获取本地图片列表
    public function image(){
        global $_W,$_GPC;

        $size = 15;
        $start = ($_GPC['page'] - 1) * $size;

        $order_item='id';
        $order='desc';

        if($_GPC['order']=='asc'){
            $order='asc';
        }else  if($_GPC['order']=='filename_desc'){
            $order_item='filename';
            $order='desc';
        }else  if($_GPC['order']=='filename_asc'){
            $order_item='filename';
            $order='asc';
        }

        $con[] = ['uniacid', '=', $_W['uniacid']];
        $con[] = ['type', '=', 1];

        if(!empty($_GPC['keyword'])){
            $con[] = ['filename', 'like', '%'.$_GPC['keyword'].'%'];
        }

        $data = CoreAttachment::where($con)->order($order_item, $order)->limit($start, $size)->select();
        $total= CoreAttachment::where($con)->count();

        foreach($data as &$item){
            $item['url'] = $_W['attachurl'].$item['attachment'];
        }
        $res=[
            'items'=>$data,
            'list'=>$data,
            'page'=>$_GPC['page'],
            'page_size'=>15,
            'total'=>$total
        ];

        return $this->result(0,$res);

    }


     // 获取本地视频列表
     public function video(){
        global $_W,$_GPC;

        $size = 15;
        $start = ($_GPC['page'] - 1) * $size;

        $order_item='id';
        $order='desc';

        if($_GPC['order']=='asc'){
            $order='asc';
        }else  if($_GPC['order']=='filename_desc'){
            $order_item='filename';
            $order='desc';
        }else  if($_GPC['order']=='filename_asc'){
            $order_item='filename';
            $order='asc';
        }

        $con[] = ['uniacid', '=', $_W['uniacid']];
        $con[] = ['type', '=', 2];

        if(!empty($_GPC['keyword'])){
            $con[] = ['filename', 'like', '%'.$_GPC['keyword'].'%'];
        }

        $data = CoreAttachment::where($con)->order($order_item, $order)->limit($start, $size)->select();
        $total= CoreAttachment::where($con)->count();

        foreach($data as &$item){
            $item['url'] = $_W['attachurl'].$item['attachment'];
        }
        $res=[
            'items'=>$data,
            'list'=>$data,
            'page'=>$_GPC['page'],
            'page_size'=>15,
            'total'=>$total
        ];

        return $this->result(0,$res);

    }


    // 获取分组列表
    public function group_list(){
        return $this->result(0);
    }

    // 添加分组
    public function add_group(){

    }





}