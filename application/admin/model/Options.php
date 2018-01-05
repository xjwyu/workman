<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 */
namespace app\admin\model;

use think\Model;

/**
 * 配置模型
 * @package application\admin\model
 */
class Options extends Model{
    
    /*
     * 前台模板文件数组
     */
    public static function tpls($lang='zh-cn')
    {
        $tpls=cache('tpls_'.$lang);
        if(empty($tpls)){
            $sys=self::get_options('site_options',$lang);
            $arr=list_file(APP_PATH.'index/view/'.$sys['site_tpl'],'*.html');  //列出本地目录的文件
            $tpls=array();
            foreach($arr as $v){
                $tpls[]=basename($v['filename'],'.html');  //basename() 函数返回路径中的文件名部分。
            }
            cache('tpls_'.$lang,$tpls);
        }
        return $tpls;
    }
    
    /*
     * 前台themes
     */
    public static function themes(){
        $themes=cache('themes');
        if(empty($themes)){
            $arr=list_file(APP_PATH.'index/view/');  //列出本地目录的文件
            foreach($arr as $v){
                if($v['isDir'] && strtolower($v['filename']!='public')){
                    $themes[]=$v['filename'];
                }
            }
            cache('themes',$themes);
        }
        return $themes;
    }
    
    /*
     * 获取百度map
     */
    public static function map($lang='zh-cn'){
        $map=cache('site_options_map');
        if(empty($map)){
            $site_options = self::get_options('site_options',$lang);
            $map['map_lat']=isset($site_options['map_lat'])?$site_options['map_lat']:'';
            $map['map_lng']=isset($site_options['map_lng'])?$site_options['map_lng']:'';
            if($site_options['site_co_name'] == ''){$site_options['site_co_name'] = '普宁市流沙中学';}           
            if((empty($map['map_lat']) || empty($map['map_lng'])) && $site_options['site_co_name']){
                $strUrl='http://api.map.baidu.com/place/v2/search?query='.$site_options['site_co_name'].'&region=全国&city_limit=false&output=json&ak='.config('baidumap_ak');
                $jsonStr = file_get_contents($strUrl);
                $arr = json_decode($jsonStr,true);
                //dump($arr);exit;
                if($arr['results'] && $arr['results'][0]['location']){
                    $map['map_lat']=$arr['results'][0]['location']['lat'];
                    $map['map_lng']=$arr['results'][0]['location']['lng'];
                }
            }
            cache('site_options_map',$map);
        }
        return $map;
    }
    
    /*
     * 获取系统基本设置
     */
    public static function get_options($type='site_options',$lang='zh-cn'){
        $options = cache($type.'_'.$lang);
        if(empty($options)){
            switch ($type){
                case 'email_options':  //邮件设置
                    $sys=array(
                    'email_open'=>0,
                    'email_rename'=>'',
                    'email_name'=>'',
                    'email_smtpname'=>'',
                    'smtpsecure'=>'',
                    'smtp_port'=>'',
                    'email_emname'=>'',
                    'email_pwd'=>'',
                    );
                    break;
                case 'active_options':
                    $sys=array(
                    'email_active'=>0,
                    'email_title'=>'',
                    'email_tpl'=>'',
                    );
                    break;
                default: //站点设置
                    $sys=array(
                    'map_lat'=>'',
                    'map_lng'=>'',
                    'site_name'=>'',
                    'site_host'=>'',
                    'site_tpl'=>'',
                    'site_tpl_m'=>'',
                    'site_logo'=>'',
                    'site_icp'=>'',
                    'site_tongji'=>'',
                    'site_copyright'=>'',
                    'site_co_name'=>'',
                    'site_address'=>'',
                    'site_tel'=>'',
                    'site_admin_email'=>'',
                    'site_qq'=>'',
                    'site_seo_title'=>'',
                    'site_seo_keywords'=>'',
                    'site_seo_description'=>'',
                    );
            }
            $options=self::where(array('option_name'=>$type,'option_l'=>$lang))->find()->toArray();
            if(empty($options)){
                $options=self::where(array('option_name'=>$type,'option_l'=>'zh-cn'))->find()->toArray();
                $options['option_l']=$lang;
                unset($options['option_id']);
                self::create($options);
            }
            $options=json_decode($options['option_value'],true);
            $options=array_merge($sys,$options);
            cache($type.'_'.$lang,$options);
        }
        return $options;
    }
    
    /*
     * 设置系统基本设置
     */
    public static function set_options($options=[],$type='site_options',$lang='zh-cn'){
    	//$save['option_value'] = json_encode($options);
    	//return self::where(['option_l'=>$lang,'option_name'=>$type])->update($save);
        return self::where(['option_l'=>$lang,'option_name'=>$type])->setField('option_value',json_encode($options));
    }
    
}
