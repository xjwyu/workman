<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 */
namespace app\admin\model;

use think\Model;
use app\admin\controller\Auth;


/**
 * 后台菜单模型
 * @package application\admin\model
 */
class AuthRule extends Model{
    
    protected $not_check_id=[1];//不检测权限的管理员id
    protected $not_check_url=['admin/Index/index','admin/Sys/clear','admin/Index/lang'];//不检测权限的url
    
    /**
     * 获取指定url的id(可能为显示状态或非显示状态)该url在菜单表的主键ID
     * @param string $url 为空获取当前操作的id
     * @param int $status 1表示取显示状态,为空或为0则不限制
     * @return int -1表示不需要检测 0表示无后台菜单 其他表示当前url对应id
     */
    public function get_url_id($url='',$status=0){
        $url=$url?:request()->module().'/'.request()->controller().'/'.request()->action();
        if($url=='//'){
            $routeInfo=request()->routeInfo();  //获取当前路由信息
            //插件管理
            if($routeInfo['route']=='\think\addons\Base@execute'){
                $menu_id = self::where('name','admin/Addons/addons_list')->where('is_delete',0)->order('level desc,sort')->value('id');
                return $menu_id?:0;
            }
        }
        //if(in_array($url,$this->not_check_url)) return -1;  //不检测权限
        if(in_array($url,['admin/Model/cmslist','admin/Model/cmsadd'])){
            $id=get_query();
            if(isset($id['id'])){
                $id=$id['id'];
                $url.='?id='.$id;
            }
        }
        $where['name']=$url;
        if($status) $where['status']=$status;
        $menu_id = self::where($where)->order('level desc,sort')->where('is_delete',0)->value('id');//4级或3级(如果4级,status是0,不显示)
        //echo DB('AuthRule')->getlastsql();echo db('AuthRule')->getlastsql(); 打印sql语句
        //echo self::getLastSql(); echo self::fetchSql()->where($where)->order('level desc,sort')->value('id');打印sql语句
        $menu_id=$menu_id?:0;
        return $menu_id;
    }
    
    /**
     * 权限检测
     * @param int
     * @return boolean
     */
    public function check_auth($id=0){
        $id=$id?:$this->get_url_id();
        if($id==-1) return true;
        $uid=session('admin_auth.aid');  //获取当前登录的用户ID
        if(in_array($uid,$this->not_check_id)) return true;  //超级管理员无需检测权限
        $auth_ids_list=cache('auth_ids_list_'.$uid);
        if(empty($auth_ids_list)){
            $auth = new Auth();
            $auth_ids_list=$auth->getAuthList($uid,1,'id'); //获取权限列表
            cache('auth_ids_list_'.$uid,$auth_ids_list);
        }
        if(empty($auth_ids_list)) return false;
        if(in_array($id,$auth_ids_list)){//检测当前操作ID是否在权限列表ID当中
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 获取权限菜单
     * @return array
     */
    public function get_admin_menus(){
        $uid=session('admin_auth.aid');  //当前用户ID
        $menus=cache('menus_admin_'.$uid);
        if($menus) return $menus;
        $where['status']=1;
        //超级管理员不需要获取权限菜单,直接显示全部菜单
        if(!in_array($uid,$this->not_check_id)){
            $auth_ids_list=cache('auth_ids_list_'.$uid);
            if(empty($auth_ids_list)){
                $auth = new Auth();
                $auth_ids_list=$auth->getAuthList($uid,1,'id');  //获得权限列表
                cache('auth_ids_list_'.$uid,$auth_ids_list);
            }
            if(empty($auth_ids_list)) return [];
            $where['id']=array('in',$auth_ids_list);
        }
        $data = self::where($where)->order('sort')->where('is_delete',0)->select();
        //$tree=new Tree();  如果有引入命名空间,则不需要\,没有则需要\  use Tree;
        $tree=new \Tree();
        $tree->init($data,['child'=>'_child','parentid'=>'pid']);
        $menus=$tree->get_arraylist($data);
        cache('menus_admin_'.$uid,$menus);
        return $menus;
    }
    
    /**
     * 获取所有父节点id(含自身) 即当前ID、父级ID、祖父级ID......
     * @param int $id 节点id
     * @return array
     */
    public function get_admin_parents($id =0){
        $id=$id?:$this->get_url_id('',1);  //获取指定url的id
        //if(empty($id)) return [];
        $lists=self::order('level desc,sort')->where('is_delete',0)->column('pid','id');
        //echo self::getLastSql();exit;
        $ids = [];
        while (isset($lists[$id]) && $lists[$id] !=0){
            $ids[]=$id;
            $id=$lists[$id];
        }
        if(isset($lists[$id]) && $lists[$id]==0) $ids[]=$id;
        return array_reverse($ids);
    }
    
    /**
     * 获取当前节点及父节点下菜单(仅显示状态)  即亲兄弟节点(包括自身)
     * @param int $id 节点id
     * @return array|mixed
     */
    public function get_admin_parent_menus(&$id){
        $id=$this->get_url_id('',1);
        $pid=self::where('id',$id)->where('is_delete',0)->value('pid');
        //取$pid下子菜单
        $menus=self::where(array('status'=>1,'pid'=>$pid))->where('is_delete',0)->order('sort')->select();
        return $menus;
    }
    /**
     * 菜单检查是否有效
     * @param int
     * @return string 返回''表示无效,否则返回正确的name字符串
     */
    public static function check_name($name,$level=1)
    {
    	$module='admin';
    	$arr=explode('/',$name);
    	$count=count($arr);
    	$rst='';
    	if($level==1){
    		if($count>1){
    			$module=strtolower($arr[0]);
    			$controller=ucfirst($arr[1]);
    		}else{
    			$controller=ucfirst($name);
    		}
    		if (has_controller($module,$controller)) {
    			$rst=$module.'/'.$controller;
    		}
    	}elseif($level==2){
    		$rst=$name;
    	}else{
    		if($count>2){
    			$module=strtolower($arr[0]);
    			$controller=ucfirst($arr[1]);
    			$action=$arr[2];
    		}elseif($count==2){
    			$controller=ucfirst($arr[0]);
    			$action=$arr[1];
    		}else{
    			return $rst;
    		}
    		if($action){
    			//判断$action是否含?
    			$arr=explode('?',$action);
    			$_action=(count($arr)==1)?$action:$arr[0];
    			if(has_action($module,$controller,$_action)==2){
    				$rst=$module.'/'.$controller.'/'.$action;
    			}
    		}
    	}
    	return $rst;
    }
}