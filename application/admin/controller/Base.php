<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 * 基础控制器
 */
namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\Admin as AdminModel;
use app\admin\model\AuthRule;

class Base extends Common{
	
	protected function _initialize(){
		parent::_initialize();
		
	    $admin=new AdminModel;
		if(!$admin->is_login()){  //未登录
			$this->redirect('admin/Login/login');
		}
		
		$auth=new AuthRule;
		$id_curr=$auth->get_url_id();  //获取当前操作的权限ID(yf_auth_rule的主键ID)
		if(!$auth->check_auth($id_curr)) $this->error('没有权限',url('admin/Index/index'));//检查当前操作是否当前用户所具的权限
		//获取有权限的菜单tree
		$menus=$auth->get_admin_menus();
		$this->assign('menus',$menus);
		//当前方法倒推到顶级菜单ids数组   即当前ID、父级ID、祖父级ID......
		$menus_curr=$auth->get_admin_parents($id_curr);
		$this->assign('menus_curr',$menus_curr);
		//取当前操作菜单父节点下菜单 当前菜单id(仅显示状态) 
		$menus_child=$auth->get_admin_parent_menus($id_curr);  //即亲兄弟节点(包括自身)
		$this->assign('menus_child',$menus_child);
		$this->assign('id_curr',$id_curr);
		$this->assign('admin_avatar',session('admin_auth.admin_avatar'));
		
	}
	
	
	
}
	
