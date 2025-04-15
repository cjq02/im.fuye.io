<?php
/*
 * @Author: SonLight Tech
 * @Date: 2023-05-16 15:31:11
 * @LastEditors: light
 * @LastEditTime: 2024-05-16 17:20:11
 * @Description: SonLight Tech版权所有
 */

declare(strict_types=1);

defined('SUN_IN') or exit('Sunphp Access Denied');


use think\facade\Db;
use think\facade\View;
use sunphp\account\SunAccount;
use sunphp\file\SunFile;
use app\admin\model\CoreSystem;
use sunphp\core\SunHelper;

function message($title,$url='',$type='success'){
    $tpl_file= root_path().'view/sunphp/message/show.html';

    // index.php开头的url，可能不是/app/或者/web/目录访问
    if(preg_match("/^index\.php/i",$url)){
        if(!preg_match("/(^\/app\/)|(^\/web\/)/i",$_SERVER['DOCUMENT_URI'])){
            $url='/app/'.$url;
        }

    }

    View::assign([
        'title'=>$title,
        'url'=>$url,
        'type'=>$type
    ]);

    $template_view=View::fetch($tpl_file);
    echo $template_view;
    die();
}

// web端验证操作用户是否已登录
function checklogin(){
    return true;
}


function strexists($str,$find){
    // return strpos($str,$find);
    $string=strval($str);
    return !(strpos($string, $find) === FALSE);
}

//随机数
function random($len,$num=0){
    if($num){
        //整数
		$chars = "123456789";
    }else{
        //字符串数字混合
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }

	mt_srand(intval(10000000 * (float)microtime()));
	for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
		$str .= $chars[mt_rand(0, $lc)];
	}
	return $str;
}

function iunserializer($str){
    if(empty($str)){
        return [];
    }else if(is_array($str)){
        return $str;
    }
    return unserialize($str);
}

function iserializer($str){
    return serialize($str);
}


// 不可以重复定义
function we_url($segment,$params=[]){

    $url='./index.php?';

    // 加入i参数
    global $_W;
    if(!empty($_W['uniacid'])){
        $url.='i='.$_W['uniacid'].'&';
    }

    $seg=explode('/',$segment);
    $a=['c','a','do'];
    if(!empty($seg)){
       foreach($seg as $k=>$v){
            $url.=$a[$k].'='.$v.'&';
       }
    }
    if(!empty($params)){
        foreach($params as $key=>$val){
             $url.=$key.'='.$val.'&';
        }
    }
    if(substr($url,-1)=='&'){
        $url=substr($url,0,-1);
    }
    return $url;
}


function pagination($total,$pindex=1,$size=10){
    global $_W,$_GPC;

    $web_url='/web/index.php?i='.$_GPC['i'].'&c=site&a=entry&module_name='.$_W['current_module']['name'].'&do='.$_GPC['do'];

    $html='<ul class="pagination">';

    // 传递了页码
	// $page = intval($_GPC["page"]);
	// $pindex = max(1, $page);

    $num=ceil($total/$size);

    $preindex=1;
    if($pindex>1){
        $preindex=$pindex-1;
    }
    $next=$pindex+1;
    if($next>=$num){
        $next=$num;
    }

    if($num>1){
        $html.='<li><a href="'.$web_url.'&page=1">首页</a></li>';
        $html.='<li><a href="'.$web_url.'&page='.$preindex.'">&laquo;上一页</a></li>';
    }

    // 会显示所有页码
    // for($i=0;$i<$num;$i++){
    //     $page_i=$i+1;
    //     $html.='<li><a href="'.$web_url.'&page='.$page_i.'">'.$page_i.'</a></li>';
    // }

    if($num<=10){
        for($i=0;$i<$num;$i++){
            $page_i=$i+1;
            $page_acitve='';
            if($page_i==$pindex){
                $page_acitve=' class="active"';
            }
            $html.='<li '.$page_acitve.'><a href="'.$web_url.'&page='.$page_i.'">'.$page_i.'</a></li>';
        }
    }else{
        if($pindex<=4){
            $page_array=[1,2,3,4,5,'...',$num];
        }else if($pindex>=($num-3)){
            $page_array=[1,'...',$num-4,$num-3,$num-2,$num-1,$num];
        }else{
            $page_array=[1,'...',$pindex-2,$pindex-1,$pindex,$pindex+1,$pindex+2,'...',$num];
        }
        for($i=0;$i<count($page_array);$i++){
            $page_i=$page_array[$i];
            $page_acitve='';
            if($page_i==$pindex){
                $page_acitve=' class="active"';
            }
            if($page_i=='...'){
                $html.='<li><a href="javascript:void(0);">'.$page_i.'</a></li>';
            }else{
                $html.='<li '.$page_acitve.'><a href="'.$web_url.'&page='.$page_i.'">'.$page_i.'</a></li>';
            }

        }
    }


    if($num>1){
         $html.='<li><a href="'.$web_url.'&page='.$next.'">&raquo;下一页</a></li>';
        $html.='<li><a href="'.$web_url.'&page='.$num.'">尾页</a></li>';
    }

    $html.='</ul>';

    return $html;
}

function checksubmit($var = 'submit', $allowget = false){
    global $_GPC;
    if($_GPC['token']=='sunphp_addons_index'){
        return true;
    }
   return false;
}


function sunphp_addons_template($content=''){
    global $_W,$_GPC;

    switch($content){
        case 'common/header':

            $system=CoreSystem::where('id',1)->find();

            //添加顶部列表
            if(empty($system['sys_logo'])){
                $logo=$_W['siteroot'].'attachment/logo.png';
            }else{
                $logo=$_W['attachurl'].$system['sys_logo'];
            }

            //平台信息
            $account=request()->middleware("account");


            // 添加左侧菜单权限
            $menus=SunHelper::getMenus();


             //应用信息
             $app=request()->middleware("app");

             if(empty($app['logo'])){
                $app_logo=$_W['siteroot'].$app['dir'].'/'.$app['identity'].'/'.$app['icon'];
            }else{
                $app_logo=$_W['attachurl'].$app['logo'];
            }

            // $_W,$_GPC未assign，全局变量模板中还是可用的
            View::assign([
                'system'=>$system,
                'logo'=>$logo,
                'account'=>$account,
                'menus'=>$menus,
                'app'=>$app,
                'app_logo'=>$app_logo
            ]);

            $tpl_file=root_path().'view/sunphp/common/header.html';
            $parseStr=View::fetch($tpl_file);

        break;
        case 'common/footer':
            $system=CoreSystem::where('id',1)->find();

            View::assign([
                'system'=>$system
            ]);
            $tpl_file=root_path().'view/sunphp/common/footer.html';
            $parseStr=View::fetch($tpl_file);

        break;
        default:
            //addons单独模板，到template目录下寻找
            $tpl_file=root_path().'addons/'.$_W['current_module']['name'].'/template/'.$content.'.html';
            if(file_exists($tpl_file)){
                // $parseStr= file_get_contents($tpl_file);读取的是html没有解析
                //读取的模板需要先解析
                $parseStr=\think\facade\View::fetch($tpl_file);

            }else{
                $parseStr='';
            }
        break;
    }
    return $parseStr;
}

function register_jssdk($debug=false){
    global $_W;
    $account=SunAccount::create($_W['uniacid']);
    $jssdk=$account->getJssdkConfig();

    $sysinfo=[
        'uniacid'=>$_W['uniacid'],
        'acid'=>$_W['acid'],
        'uid'=>'',
        'siteroot'=>$_W['siteroot'],
        'siteurl'=>$_W['siteurl'],
        'attachurl'=>$_W['attachurl'],
        'pre'=>'',
        'MODULE_URL'=>defined('MODULE_URL')?MODULE_URL:''
    ];

    $html='<script src="https://res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>';

    $html.='<script type="text/javascript">';

    $html.='window.sysinfo=window.sysinfo||'.json_encode($sysinfo).';';
	$html.='jssdkconfig = '.json_encode($jssdk).' || {};';

	if($debug){
        $html.='jssdkconfig.debug = true;';
    }else{
        $html.='jssdkconfig.debug = false;';
    }

	$html.='jssdkconfig.jsApiList = [
		"checkJsApi",
		"onMenuShareTimeline",
		"onMenuShareAppMessage",
		"onMenuShareQQ",
		"onMenuShareWeibo",
		"hideMenuItems",
		"showMenuItems",
		"hideAllNonBaseMenuItem",
		"showAllNonBaseMenuItem",
		"translateVoice",
		"startRecord",
		"stopRecord",
		"onRecordEnd",
		"playVoice",
		"pauseVoice",
		"stopVoice",
		"uploadVoice",
		"downloadVoice",
		"chooseImage",
		"previewImage",
		"uploadImage",
		"downloadImage",
		"getNetworkType",
		"openLocation",
		"getLocation",
		"hideOptionMenu",
		"showOptionMenu",
		"closeWindow",
		"scanQRCode",
		"chooseWXPay",
		"openProductSpecificView",
		"addCard",
		"chooseCard",
		"openCard"
	];';

	$html.='wx.config(jssdkconfig);';
    $html.='</script>';

    return $html;
}


function tpl_form_field_color($field, $value = '') {

    if(empty($value)){
        $value='#ffffff';
    }

    $html='<div>';

    $html.='<div>';
    $html.='<input class="form-control"  style="width: 100px;display: inline-block;" id="color_'.$field.'" type="text" name="'.$field.'"  value="'.$value.'">';
    $html.='<span style="width: 34px;height: 34px;display: inline-block;vertical-align: middle;background-color:'.$value.'"></span>';

    $html.='<button id="btn_picker_'.$field.'" type="button" class="btn btn-default">选择颜色</button>';
    $html.='</div>';

    $html.='</div>';

    $html.='
    <script>
    $(function(){
            require(["colorpicker"],function(e){
                Colorpicker.create({
                    el: "btn_picker_'.$field.'",
                    color: "'.$value.'",
                    change: function (elem, hex) {
                        $("input#color_'.$field.'").val(hex);
                        $("input#color_'.$field.'").next().css("background-color", hex);
                    }
                })
            });

    });
    </script>
    ';

    return $html;

}


function tpl_form_field_multi_image($field, $url, $arg='', $extras=''){

    global $_W,$_GPC;
    $upload_url=$_W['siteroot']."index.php/admin/file/upload?i=".$_GPC['i'];
    $attach_url=$_W['attachurl'];

    $html='<div>';

    $html.='<div>';
    $html.='<input class="form-control" readonly="readonly" value="批量上传图片">';

    $html.='<input class="sun-input-file" accept="image/*" multiple type="file" onchange="tapMultiImage'.$field.'(this)">';


    $html.='</div>';


    $html.='<div id="multi_imgs_'.$field.'" style="display:flex;flex-wrap: wrap;">';

    // 动态生成，动态删减
    if(!empty($url)){
        foreach($url as $img){
            if(!empty($img)){
                    $html.='<div  style="display:flex;margin-bottom:10px;">';
                    $html.='<input type="hidden" name="'.$field.'[]" value="'.$img.'">';
                    $html.='<img  src="'.$img.'" class="sun-img">';
                    $html.='<div onclick="closeImg'.$field.'()" style="padding-left: 5px;margin-right: 15px;cursor:pointer;">X</div>';
                    $html.='</div>';
            }
        }
    }


    $html.='</div>';



    $html.='</div>';



    $html.='<script>';

    $html.='function closeImg'.$field.'(){';
        // 移除html节点
        $html.='document.getElementById("multi_imgs_'.$field.'").removeChild(event.currentTarget.parentNode);';
    $html.='}';



    $html.='async function tapMultiImage'.$field.'(t){';

    $html.='var files=$(t)[0].files;';
    $html.='if (files.length<=0) {return;}';



    $html.='for(var i=0;i<files.length;i++){';
    $html.='var file=files[i];';


        $html.='if(file.type==""){';
            $html.='file = new File([file], new Date().getTime()+".jpg",{type:"image/jpeg"});';
            $html.='}';

            $html.='var formdata=new FormData();';
            $html.='formdata.append("file_type","img");';

            $html.='formdata.append("session_id",localStorage.getItem("sunphp_admin_session_id"));';

            $html.='formdata.append("file_img",file);';
            $html.='await $.ajax({';
                $html.='url:"'.$upload_url.'",';
                $html.='data:formdata,';
                $html.='headers:{"token":localStorage.getItem("sunphp_admin_access_token")},';
                $html.='type:"POST",';
                $html.='catch:false,';
                $html.='contentType:false,';
                $html.='processData:false,';
                $html.='success:function(result){';
                    $html.='if(result.status==200){';
                        $html.='var imgurl ="'.$attach_url.'"+result.data.path;';

                        // 区分单引号、转移单引号
                        $html.='var img_node=\'<div  style="display:flex;margin-bottom:10px;"><input type="hidden" name="'.$field.'[]" value="\'+imgurl+\'"><img src="\'+imgurl+\'" class="sun-img"><div onclick="closeImg'.$field.'()" style="padding-left: 5px;margin-right: 15px;cursor:pointer;">X</div></div>\';';

                        $html.='$("div#multi_imgs_'.$field.'").append(img_node);';

                        $html.='}else if([401,402,403].indexOf(result.status)>-1){';
                        $html.='location.href="'.$_W['siteroot'].'";';
                        $html.='}';

                        $html.='}';
                        $html.='});';


    $html.='}';



    $html.='}';


    $html.='</script>';

return $html;

}


function tpl_form_field_image($field, $url, $arg='', $extras=''){

    global $_W,$_GPC;
    $upload_url=$_W['siteroot']."index.php/admin/file/upload?i=".$_GPC['i'];
    $attach_url=$_W['attachurl'];

    $html='<div>';

    $html.='<div>';
    $html.='<input class="form-control" id="'.$field.'" type="text" name="'.$field.'" url="'.$url.'" value="'.$url.'">';

    $html.='<input class="sun-input-file" accept="image/*" type="file" onchange="tapImage'.$field.'(this)">';



    $html.='</div>';

    $html.='<div>';
    $html.='<img id="img'.$field.'" src="'.$url.'" class="sun-img">';
    $html.='</div>';

    $html.='</div>';



    $html.='<script>';

    $html.='function tapImage'.$field.'(t){';

    $html.='var file=$(t)[0].files[0];';
    $html.='if(!file) {';
        $html.='	return;';
        $html.=' }';
        $html.='if(file.type==""){';
            $html.='file = new File([file], new Date().getTime()+".jpg",{type:"image/jpeg"});';
            $html.='}';

            $html.='var formdata=new FormData();';
            $html.='formdata.append("file_type","img");';

            $html.='formdata.append("session_id",localStorage.getItem("sunphp_admin_session_id"));';

            $html.='formdata.append("file_img",file);';
            $html.='$.ajax({';
                $html.='url:"'.$upload_url.'",';
                $html.='data:formdata,';
                $html.='headers:{"token":localStorage.getItem("sunphp_admin_access_token")},';
                $html.='type:"POST",';
                $html.='catch:false,';
                $html.='contentType:false,';
                $html.='processData:false,';
                $html.='success:function(result){';
                    $html.='if(result.status==200){';
                        $html.='var imgurl ="'.$attach_url.'"+result.data.path;';
                        $html.='$("input#'.$field.'").val(result.data.path);';
                        $html.='$("input#'.$field.'").attr("url",imgurl);';

                        $html.='$("img#img'.$field.'").attr("src",imgurl);';

                        $html.='}else if([401,402,403].indexOf(result.status)>-1){';
                        $html.='location.href="'.$_W['siteroot'].'";';
                        $html.='}';

                        $html.='}';
                        $html.='});';

    $html.='}';


    $html.='</script>';

return $html;

}


function tpl_form_field_audio($field, $url, $arg='', $extras=''){

    global $_W,$_GPC;
    $upload_url=$_W['siteroot']."index.php/admin/file/upload?i=".$_GPC['i'];
    $attach_url=$_W['attachurl'];

    $html='<div>';

    $html.='<div>';
    $html.='<input class="form-control" id="'.$field.'" type="text" name="'.$field.'" url="'.$url.'" value="'.$url.'">';

    $html.='<input class="sun-input-file" accept="audio/*" type="file" onchange="tapAudio'.$field.'(this)">';



    $html.='</div>';

    $html.='<div>';
    $html.='<audio id="audio'.$field.'" controls>';
    $html.='<source id="source'.$field.'" src="'.$url.'" class="sun-audio">';
    $html.='</audio>';
    $html.='</div>';

    $html.='</div>';



    $html.='<script>';

    $html.='function tapAudio'.$field.'(t){';

    $html.='var file=$(t)[0].files[0];';
    $html.='if(!file) {';
        $html.='	return;';
        $html.=' }';

            $html.='var formdata=new FormData();';
            $html.='formdata.append("file_type","voice");';

            $html.='formdata.append("session_id",localStorage.getItem("sunphp_admin_session_id"));';

            $html.='formdata.append("file_voice",file);';
            $html.='$.ajax({';
                $html.='url:"'.$upload_url.'",';
                $html.='data:formdata,';
                $html.='headers:{"token":localStorage.getItem("sunphp_admin_access_token")},';
                $html.='type:"POST",';
                $html.='catch:false,';
                $html.='contentType:false,';
                $html.='processData:false,';
                $html.='success:function(result){';
                    $html.='if(result.status==200){';
                        $html.='var url ="'.$attach_url.'"+result.data.path;';
                        $html.='$("input#'.$field.'").val(result.data.path);';
                        $html.='$("input#'.$field.'").attr("url",url);';

                        $html.='$("source#source'.$field.'").attr("src",url);';
                        $html.='$("audio#audio'.$field.'").load();';

                        $html.='}else if([401,402,403].indexOf(result.status)>-1){';
                        $html.='location.href="'.$_W['siteroot'].'";';
                        $html.='}';

                        $html.='}';
                        $html.='});';

    $html.='}';


    $html.='</script>';

return $html;

}

function tpl_form_field_video($field, $url, $arg='', $extras=''){

    global $_W,$_GPC;
    $upload_url=$_W['siteroot']."index.php/admin/file/upload?i=".$_GPC['i'];
    $attach_url=$_W['attachurl'];

    $html='<div>';

    $html.='<div>';
    $html.='<input class="form-control" id="'.$field.'" type="text" name="'.$field.'" url="'.$url.'" value="'.$url.'">';

    $html.='<input class="sun-input-file" accept="video/*" type="file" onchange="tapVideo'.$field.'(this)">';



    $html.='</div>';

    $html.='<div>';
    $html.='<video id="video'.$field.'" src="'.$url.'"  controls="controls" class="sun-video"></video>';
    $html.='</div>';

    $html.='</div>';



    $html.='<script>';

    $html.='function tapVideo'.$field.'(t){';

    $html.='var file=$(t)[0].files[0];';
    $html.='if(!file) {';
        $html.='	return;';
        $html.=' }';

            $html.='var formdata=new FormData();';
            $html.='formdata.append("file_type","video");';

            $html.='formdata.append("session_id",localStorage.getItem("sunphp_admin_session_id"));';

            $html.='formdata.append("file_video",file);';
            $html.='$.ajax({';
                $html.='url:"'.$upload_url.'",';
                $html.='data:formdata,';
                $html.='headers:{"token":localStorage.getItem("sunphp_admin_access_token")},';
                $html.='type:"POST",';
                $html.='catch:false,';
                $html.='contentType:false,';
                $html.='processData:false,';
                $html.='success:function(result){';
                    $html.='if(result.status==200){';
                        $html.='var url ="'.$attach_url.'"+result.data.path;';
                        $html.='$("input#'.$field.'").val(result.data.path);';
                        $html.='$("input#'.$field.'").attr("url",url);';

                        $html.='$("video#video'.$field.'").attr("src",url);';

                        $html.='}else if([401,402,403].indexOf(result.status)>-1){';
                        $html.='location.href="'.$_W['siteroot'].'";';
                        $html.='}';

                        $html.='}';
                        $html.='});';

    $html.='}';


    $html.='</script>';

return $html;

}


function load(){
    static $sunphp_load;
	if(empty($sunphp_load)) {
        include_once __DIR__ . '/SunLoader.php';
		$sunphp_load = new SunLoader();
	}
	return $sunphp_load;
}


function logging_run($arg){
    $log = app()->log;
    $log->write($arg);
}

function ihttp_post($url, $post_data)
{
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);     // 设置超时限制防止死循环
        curl_setopt($ch, CURLOPT_TIMEOUT, 35);

        $data = curl_exec($ch); //运行curl
        curl_close($ch);

        return $data;
}

function ihttp_get($url){

    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_URL, $url); //抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 1); //设置头文件的信息作为数据流输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);     // 设置超时限制防止死循环
    curl_setopt($ch, CURLOPT_TIMEOUT, 35);

    $data = curl_exec($ch); //运行curl
    curl_close($ch);

    return $data;
}

function cache_write($name,$value,$expire_time=0){
    $cache=app()->cache;
    if($expire_time>0){
        $cache->set($name,$value, $expire_time);
    }else{
        $cache->set($name,$value);
    }
}

function cache_load($name){
    $cache=app()->cache;
    return $cache->get($name);
}

function cache_delete($name){
    $cache=app()->cache;
    $cache->delete($name);
}

function cache_clean(){
    $cache=app()->cache;
    $cache->clear();
}

// 默认不用框架sun_core_member会员管理
// 如需使用，需要手动开启
function mc_oauth_userinfo($uniacid='',$fans=false){
    global $_W;

    if(empty($uniacid)){
        $uniacid=$_W['uniacid'];
    }
    $account=SunAccount::create($uniacid);
    $userinfo=$account->login();


    // 模块必须手动指定，使用框架会员系统
    if($fans){
        /* 获取粉丝信息 */
        $fans_info = $account->fansQueryInfo($userinfo['openid']);

        //获取框架会员
        $sql='select * from sun_core_member where openid=:openid and uniacid=:uniacid';
        $params=[':openid'=>$userinfo['openid'],':uniacid'=>$uniacid];
        $member=pdo_fetch($sql,$params);
        if(!empty($member)){
            $_W['fans']=$member;
        }else{
            // 写入框架会员
            $member_data=[
                'uniacid'=>$uniacid,
                'openid'=>$userinfo['openid'],
                'nickname'=>$userinfo['nickname'],
                'avatar'=>$userinfo['headimgurl'],
                'gender'=>$userinfo['sex'],
                'create_time'=>date('Y-m-d H:i:s',time())
            ];
            if(!empty($userinfo['unionid'])){
                $member_data['unionid']=$userinfo['unionid'];
            }

            $uid=Db::table('sun_core_member')->insertGetId($member_data);

            // addons存在的参数uid，这种写法首次的参数不完整
            // $_W['fans']=$member_data;
            // $_W['fans']['uid']=$uid;

            $sql='select * from sun_core_member where uid=:uid';
            $params=[':uid'=>$uid];
            $_W['fans']==pdo_fetch($sql,$params);

        }

        $_W['fans']['follow']=$fans_info['subscribe'];

        $_W['member']=$_W['fans'];

        //不用框架session
        // session('fans_core_member_'.$uniacid,$_W['fans']);

        session_start();
        $_SESSION['fans_core_member_'.$uniacid]=$_W['fans'];
        session_commit();

    }

    return $userinfo;
}

//生成随机文件名称
function file_random_name($path,$ext){
    //指定文件名称
    do {
        $data = uniqid("", true);
        $data .= microtime();
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['REMOTE_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $hash = strtolower(hash('ripemd128', "sunphp" . md5($data)));
        $filename = md5($hash) . '.' . $ext;
    } while (file_exists(root_path() . "attachment/" . $path  . $filename));

    return $filename;
}

function setting_load($params=''){
    $res=[];
    switch($params){
        case 'upload':
            global $_W;
            $res['upload']=$_W['setting']['upload'];
        break;
        default:
        break;
    }
    return $res;
}

function file_write($filename, $data) {

	$filename = ATTACHMENT_ROOT . '/' . $filename;
	mkdirs(dirname($filename));
	file_put_contents($filename, $data);
	@chmod($filename, 0644);

	return is_file($filename);
}

// 本地上传，不远程上传
function file_upload($file,$type){

    $key='';
    foreach($_FILES as $k=>$v){
        if($file['tmp_name']==$v['tmp_name']&&$file['size']==$v['size']){
            $key=$k;
            break;
        }
    }

    if(!empty($key)){
         $res=SunFile::upload($key,$type,false,false);
         if($res['status']==1){
            $res['success']=1;
            return $res;
         }else{
            $res['success']=0;
            return $res;
         }
    }
    return false;
}

// 下载远程文件
function file_download($url, $type = '', $file_path = '', $remote_upload = true, $local_delete = true){
    $res=SunFile::remoteDownload($url, $type, $file_path, $remote_upload, $local_delete);
    return $res;
}

function file_remote_upload($path,$local_delete=true,$censor=true,$censor_config=[]){
    $res=SunFile::remoteUpload($path,$local_delete,$censor,$censor_config);
    return $res;
}

function file_remote_censor($path,$type,$scenes=[],$sync=true,$remote_delete=true){
    $res=SunFile::remoteCensor($path,$type,$scenes,$sync,$remote_delete);
    return $res;
}

function error($errno, $message = '', $data = []) {
	return array(
		'errno' => $errno,
		'message' => $message,
        'data' => $data
	);
}

function is_error($arg){
    // 注意！报错返回true

    // $arg===false 报错
    // status 0报错1成功
    // errno 0成功 其他-报错——考虑到兼容性
    // 报错一定要求message说明
    // $result = [
    //     "status" => 0,
    //     'message'=>'失败原因',
    //     "data" => []
    // ];

    if($arg===false ||(is_array($arg) && array_key_exists('status', $arg) && $arg['status']===0) ||(is_array($arg) && array_key_exists('errno', $arg) && $arg['errno']!=0)){
        return true;
    }
    return false;
}

// 编译文件
/*
$source：原始文件
$compile：编译后的模板文件
*/

function mkdirs($path) {
	if (!is_dir($path)) {
		mkdirs(dirname($path));
		mkdir($path);
	}
	return is_dir($path);
}

function file_move($temp_file, $real_file) {
	mkdirs(dirname($real_file));
	if (is_uploaded_file($temp_file)) {
		move_uploaded_file($temp_file, $real_file);
	} else {
		rename($temp_file, $real_file);
	}
	return is_file($real_file);
}

function template_compile($source, $compile){
    global $_W,$_GPC;
    View::assign(get_defined_vars());
    // View::assign(get_defined_constants());

    $template_view=View::fetch($source);
    //将文件写入编译后的目录

    $path = dirname($compile);
	if (!is_dir($path)) {
		mkdirs($path);
	}

	file_put_contents($compile, $template_view);
    return true;
}


function tomedia($src='',$local=false,$cache=false){
    global $_W;
    if(empty($src)) return '';

    // 如果有http开头，则不加附件地址
    if(preg_match('/^https?:\/\//i',$src)){
        return $src;
    }

    if($local){
        return $_W['attachurl_local'].$src;
    }
    return $_W['attachurl'].$src;
}


function referer(){
    return $_SERVER['HTTP_REFERER'];
}









// 无实际操作的无效方法
function checkauth(){
    return false;
}
//代金券和折扣券的兑换记录,
function mc_openid2uid($user_openid){
    return false;
}
function mc_credit_update($arg1='',$arg2='',$arg3='',$arg4='',$arg5='',$arg6=''){
    return false;
}

