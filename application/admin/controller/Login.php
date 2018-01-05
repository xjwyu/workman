<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 * 后台登录控制器
 */
namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\Admin as AdminModel;

class Login extends Common{
	
	protected function _initialize(){
		parent::_initialize();
	}
	
	/**
	 * 登录显示
	 */
	public function login(){
		$admin=new AdminModel;
		if($admin->is_login()){  //判断是否已经登录
			$this->redirect('admin/Index/index');
		}
		return $this->fetch();
	}
	/**
	 * 验证码
	 */
	public function verify(){
		$admin=new AdminModel;
		if($admin->is_login()){  //判断是否已经登录
			$this->redirect('admin/Index/index');
		}
		return $this->verify_build('aid');
	}
	
	/**
	 * 登录验证（异步）
	 */
	public function runlogin(){
		if (!request()->isAjax()){
			$this->error("提交方式错误！",url('admin/Login/login'));
		}else{
			if(config('geetest.geetest_on')){
				if(!geetest_check(input('post.'))){
					$this->error('验证不通过',url('admin/Login/login'));
				};
			}else{
				$this->verify_check('aid');
			}
			$admin_username=input('admin_username');
			$password=input('admin_pwd');
			$rememberme=input('rememberme');
			$admin=new AdminModel;
			if($admin->login($admin_username,$password,$rememberme)){
				$this->success('恭喜您，登陆成功',url('admin/Index/index'));
			}else{
				$this->error($admin->getError(),url('admin/Login/login'));
			}
		}
	}
	/**
	 * 退出登录
	 */
	public function logout()
	{
		session('admin_auth',null);
		session('admin_auth_sign',null);
		cookie('aid', null);
		cookie('signin_token', null);
		setcookie(session_name(),'',time()-3600,'/');//删除本地相关联的cookie
        session_unset();//清空内存中的cookie或者是$_SESSION = array(); 
		$this->redirect('admin/Login/login');
	}
	
}
	
