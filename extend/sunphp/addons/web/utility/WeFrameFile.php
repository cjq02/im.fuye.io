<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-05-31 17:20:14
 * @LastEditors: light
 * @LastEditTime: 2024-10-16 09:47:33
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');

use sunphp\file\SunFile;
use app\admin\model\CoreAttachment;
use app\admin\model\CoreAttachgroup;



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

                //是否携带分组信息
                if(isset($_GPC['group_id'])){
                    $data['group_id']=$_GPC['group_id'];
                }

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

        if(isset($_GPC['group_id'])&&$_GPC['group_id']>=0){
            //-1为全部，0为未分组
            $con[] = ['group_id', '=', $_GPC['group_id']];
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

        if(isset($_GPC['group_id'])&&$_GPC['group_id']>=0){
            //-1为全部，0为未分组
            $con[] = ['group_id', '=', $_GPC['group_id']];
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
        global $_W,$_GPC;
        $con=[];
        $con[] = ['uniacid', '=', $_W['uniacid']];
        $con[] = ['pid', '=', 0];
        $data = CoreAttachgroup::where($con)->order('id', 'asc')->select();

        //二级分组
        foreach($data as &$item){
            $item['sub_group'] = CoreAttachgroup::where(['uniacid' => $_W['uniacid'], 'pid' => $item['id']])->order('id', 'asc')->select();
        }
        return $this->result(0,$data);
    }


    // 添加分组
    public function add_group(){
        global $_W,$_GPC;

         // 写入数据库
         $data=[
            'uniacid'=>$_W['uniacid'],
            'uid' => $_W['uid'],
            'name' => $_GPC['name'],
            'pid'=>isset($_GPC['pid'])?$_GPC['pid']:0,
            'type' => isset($_GPC['local'])?0:1,//0本地1微信
        ];
        $group=CoreAttachgroup::create($data);
        $result=[
            'id'=>$group->id
        ];
        return $this->result(0,$result);

    }


    public function change_group(){
        global $_W,$_GPC;
        CoreAttachgroup::where(['id'=>$_GPC['id']])->update(['name' => $_GPC['name']]);
        return $this->result(0,'更新成功');
    }

    public function del_group(){
        global $_W,$_GPC;
        // 修改分组为未分组0
        CoreAttachment::where(['uniacid' => $_W['uniacid'], 'group_id' => $_GPC['group_id']])->update(['group_id' => 0]);

        // 修改子分组为未分组0
        $pid_list=CoreAttachgroup::where(['pid' => $_GPC['group_id']])->column('id');
        if(!empty($pid_list)){
            CoreAttachment::where([
                ['uniacid','=' ,$_W['uniacid']],
                ['group_id','in', $pid_list]
                ])->update(['group_id' => 0]);
        }

        // 删除分组
        CoreAttachgroup::where(['id'=>$_GPC['group_id']])->delete();

        // 删除子分组
        CoreAttachgroup::where(['pid' => $_GPC['group_id']])->delete();

        return $this->result(0,'删除成功');
    }

    public function  delete(){
        global $_W,$_GPC;
        CoreAttachment::where('id','in',$_GPC['id'])->delete();
        return $this->result(0,'删除成功');
    }

    public function move_to_group(){
        global $_W,$_GPC;
        CoreAttachment::where('id','in',$_GPC['id'])->update(['group_id' => $_GPC['group_id']]);
        return $this->result(0,'移动成功');
    }




}