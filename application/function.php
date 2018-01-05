<?php
// 应用公共文件(涉及操作数据库的函数)

use think\Db;
use think\Request;
use think\Response;
use think\Lang;
use app\admin\controller\Auth;


/**
 * 上传单张图片
 * @param unknown $file  要进行上传的图片
 */
function upload_one($file){
    $result = array();
    $img_url='';
    if($file){
        if(config('storage.storage_open')){  //七牛云上传
            //七牛
            $upload = \Qiniu::instance();
            $info = $upload->upload($file->getInfo());
            $error = $upload->getError();
            if ($info) {
                $img_url= config('storage.domain').$info[0]['key'];
                $result['status'] = 1;
                $result['data'] = $img_url;
                $result['info'] = "上传七牛云成功";
                return $result;
            }else{
                $result['status'] = 0;
                $result['data'] = "";
                $result['info'] = $error;
                return $result;
            }
        }else{   //本地上传
            $validate=config('upload_validate');
            $info = $file[0]->validate($validate)->rule('uniqid')->move(config('upload_path') . DS . date('Y-m-d'));
            if($info) {
                $sql_upload_path = trim(config('upload_path'),'.');
                $img_url=$sql_upload_path. '/' . date('Y-m-d') . '/' . $info->getFilename();
                //写入数据库
                $data['uptime']=time();
                $data['filesize']=$info->getSize();
                $data['path']=$img_url;
                Db::name('plug_files')->insert($data);
                $result['status'] = 1;
                $result['data'] = $img_url;
                $result['info'] = "本地图片上传成功";
                return $result;
            }else{
                $result['status'] = 0;
                $result['data'] = "";
                $result['info'] = $file[0]->getError();
                return $result;                
            }
        }
    }else{
        $result['status'] = 0;
        $result['data'] = "";
        $result['info'] = "请上传图片";
        return $result;
    }
}


/**
 * 上传多张图片(也可以用于单张图片上传)
 * @param unknown $files 图片数组
 */
function upload_multiple($files){
    $pic_multiple = array();  //保存上传文件的数组
    $picall_url = array();    //上传成功需返回的图片路径数组
    $result = array();
    if($files){
        if(config('storage.storage_open')){
            //七牛云上传
            $niu_files = array();  //组织数组以供七牛云上传
            foreach ($files as $file) {
                $file_info = $file->getInfo();
                $file_keys = array_keys($file_info);
                foreach ($file_keys as $key) {
                    if($key != "key"){
                        $niu_files[$key][] = $file_info[$key];
                    }
                }
            }

            $upload = \Qiniu::instance();
            $info = $upload->upload($niu_files);
            $error = $upload->getError();
            if ($info) {
                //多图
                foreach ($info as $file) {
                    $pic_all_url=config('storage.domain').$file['key'];
                    $picall_url[] = $pic_all_url;
                }
                $result['status'] = 1;
                $result['data'] = $picall_url;
                $result['info'] = "上传七牛云多张图片成功";
                return $result;
            }else{
                $result['status'] = 0;
                $result['data'] = "";
                $result['info'] = $error;
                return $result;
            }
        }else{  //本地上传
            $validate=config('upload_validate');
            foreach ($files as $file) {
                $info = $file->validate($validate)->rule('uniqid')->move(config('upload_path') . DS . date('Y-m-d'));
                if ($info) {
                    $sql_upload_path = trim(config('upload_path'),'.');
                    $pic_all_url=$sql_upload_path. '/' . date('Y-m-d') . '/' . $info->getFilename();

                    //写入数据库
                    $plug_files['uptime'] = time();
                    $plug_files['filesize'] = $info->getSize();
                    $plug_files['path'] = $pic_all_url;
                    $pic_multiple[] = $plug_files;
                    $picall_url[] = $pic_all_url;
                } else {
                    $result['status'] = 0;
                    $result['data'] = "";
                    $result['info'] = $file->getError();
                    return $result;
                }
            }
            Db::name('plug_files')->insertAll($pic_multiple);
            $result['status'] = 1;
            $result['data'] = $picall_url;
            $result['info'] = "本地上传多张图片成功";
            return $result;
        }
    }else{
        $result['status'] = 0;
        $result['data'] = "";
        $result['info'] = "请上传图片";
        return $result;
    }
}

/**
 * 根据用户id获取用户组,返回值为数组
 * @param   int $uid    用户id
 * @return string
 */
function get_groups($uid)
{
	$auth = new Auth();
	$group = $auth->getGroups($uid);
	return $group[0]['title'];
}

