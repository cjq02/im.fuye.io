<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-02-20 09:30:50
 * @LastEditors: light
 * @LastEditTime: 2023-07-31 15:46:48
 * @Description: SonLight Tech版权所有
 */
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\CoreStorage;
use sunphp\file\SunFile;


class File extends Base{

   	//上传文件接口，返回路径地址
	public function upload()
	{
		$post=$this->request->post();
		$file=$this->request->file();

			if (isset($post['file_type'])) {
				//是否远程上传，本地是否删除
				// 不设置参数，默认远程上传，设置为2，不远程上传
				$remote_upload=empty($post['remote_upload'])?true:false;
				$local_delete=empty($post['local_delete'])?true:false;

				$data=[];

				switch ($post['file_type']) {
					case 'img':
						if (!empty($file['file_img'])) {

							$res = SunFile::upload('file_img', "image",$remote_upload,$local_delete);

							if ($res['status']!=1) {
								return jsonResult(400, $res['message'], array());
							}
							$data = array(
								"path" => $res['path']
							);
						}
						break;
					case 'video':
						if (!empty($file['file_video'])) {
							$res = SunFile::upload('file_video', "video",$remote_upload,$local_delete);

							if ($res['status']!=1) {
								return jsonResult(400, $res['message'], array());
							}
							$data = array(
								"path" => $res['path']
							);
						}
						break;
					case 'voice':
						if (!empty($file['file_voice'])) {


							//h5上传的音频的name没有扩展名
							$ext = pathinfo($_FILES['file_voice']['name'], PATHINFO_EXTENSION);
							if (empty($ext)) {
								$_FILES['file_voice']['name'] .= ".mp3";
							}


							$res = SunFile::upload('file_voice', "voice",$remote_upload,$local_delete);
							if ($res['status']!=1) {
								return jsonResult(400, $res['message'], array());
							}
							$data = array(
								"path" => $res['path']
							);
						}
						break;
					case 'file':
						if (!empty($file['file_file'])) {

							//blob本地资源会自动更改name，必须指定
							if (!empty($post['file_name'])) {
								$_FILES['file_file']['name'] = $post['file_name'];
							}

							$res = SunFile::upload('file_file', "file",$remote_upload,$local_delete);
							if ($res['status']!=1) {
								return jsonResult(400, $res['message'], array());
							}
							$data = array(
								"path" => $res['path']
							);
						}
						break;
					default:
							return jsonResult(400, "参数错误", array());
						break;
				}


				// 是否获取attachurl实际地址
				if(!empty($post['attachurl'])){
						$get=$this->request->get();

						// 本地附件url
						$attachurl_local=$this->request->domain()."/attachment/";

						// 开启远程就是远程附件地址
						$storage='';

						if(!empty($get['i'])){
							$storage=CoreStorage::where('acid',$get['i'])->find();
						}
						if(empty($storage)||$storage['type']==1){
							$storage=CoreStorage::where('acid',0)->find();
						}

						if(empty($storage)){
							$type=1;
						}else{
							$type=$storage->type;
						}
						switch($type){
							case 1:
								$attachurl=$attachurl_local;
								break;
							case 2:
								$oss=$storage->ali_oss;
								$attachurl=$oss['url'].'/';
								break;
							case 3:
								$oss=$storage->tencent_cos;
								$attachurl=$oss['url'].'/';
								break;
							case 4:
								$oss=$storage->qiniu;
								$attachurl=$oss['url'].'/';
								break;
						}


					$data['attachurl'] =$attachurl;
				}

				return jsonResult(200, "上传成功", $data);

			}
			return jsonResult(400, "参数错误", array());
	}


}