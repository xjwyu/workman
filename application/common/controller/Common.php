<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 * 公共控制器
 */
namespace app\common\controller;

use think\Controller;
use think\Lang;
use think\captcha\Captcha;

class Common extends Controller{
	// Request实例
	protected $lang;
	protected function _initialize(){
		parent::_initialize();
// 		echo $_SERVER['SCRIPT_NAME'];echo '<br/>';
// 		echo rtrim($_SERVER['SCRIPT_NAME'], '/');echo '<br/>';
// 		echo dirname(rtrim($_SERVER['SCRIPT_NAME'], '/'));echo '<br/>';
// 		echo rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');echo '<br/>';
		if (!defined('__ROOT__')) {
		    $_root = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');
		    define('__ROOT__', (('/' == $_root || '\\' == $_root) ? '' : $_root));
		}
/**		if (!file_exists(ROOT_PATH.'data/install.lock')) {
		    //不存在，则进入安装
		    header('Location: ' . url('install/Index/index'));
		    exit();
		}**/
		if (!defined('MODULE_NAME')){define('MODULE_NAME', $this->request->module());}
		if (!defined('CONTROLLER_NAME')){define('CONTROLLER_NAME', $this->request->controller());}
		if (!defined('ACTION_NAME')){define('ACTION_NAME', $this->request->action());}
		// 多语言
		if(config('lang_switch_on')){
		    $this->lang=Lang::detect();  //自动侦测设置获取语言选择
		}else{
		    $this->lang=config('default_lang');
		}
		$this->assign('lang',$this->lang);
	}
	
	//空操作
	public function _empty(){
		//echo '空操作_empty';
		$this->error(lang('operation not valid'));
	}
	
	/**
	 * 输出验证码并把验证码的值保存的session中
	 * @param unknown $id 保存session的名字
	 * @return \think\Response
	 */
	protected function verify_build($id){
		ob_end_clean();
		$verify = new Captcha (config('verify'));
		return $verify->entry($id); //输出验证码并把验证码的值保存的session中   session名字为$id
	}
	
	/**
	 * 验证验证码是否正确
	 * @param unknown $id
	 */
	protected function verify_check($id){
		$verify =new Captcha ();
		if (!$verify->check(input('verify'), $id)) {
			$this->error(lang('verifiy incorrect'),url(MODULE_NAME.'/Login/login'));
		}
	}
	
}
