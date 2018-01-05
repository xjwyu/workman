<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 * 日志操作
 */
namespace app\common\behavior;

use think\Exception;
use think\Request;
use think\Db;

class WebLog
{
    public function run(&$param)
    {
        $request = Request::instance();
        //不记录的模块
        $not_log_module=config('web_log.not_log_module')?:array();

        //不记录的控制器 'module/controller'
        $not_log_controller=config('web_log.not_log_controller')?:array();

        //不记录的操作方法 'module/controller/action'
        $not_log_action=config('web_log.not_log_action')?:array();

        //不记录data的操作方法 'module/controller/action'	如涉及密码传输的地方：1、前、后台登录runlogin  5、后台admin_runadd admin_runedit
        $not_log_data=['admin/Login/runlogin','home/Login/runlogin','admin/Admin/admin_runadd','admin/Admin/admin_runedit'];
        $not_log_data=array_merge($not_log_data,config('web_log.not_log_data')?:array());

        //不记录的请求类型
        $not_log_request_method=config('web_log.not_log_request_method')?:array();
        
//         dump($not_log_module);
//         dump($not_log_controller);
//         dump($not_log_action);
//         dump($not_log_request_method);
        
        if (
            in_array($request->module(), $not_log_module) ||
            in_array($request->module().'/'.$request->controller(), $not_log_controller) ||
            in_array($request->module().'/'.$request->controller().'/'.$request->action(), $not_log_action) ||
            in_array($request->method(), $not_log_request_method)
            ) {
                return true;
            }
            //只记录存在的操作方法
            if(!has_action($request->module(),$request->controller(),$request->method())){
                return true;
            }
            try {
                if(in_array($request->module().'/'.$request->controller().'/'.$request->action(), $not_log_data)){
                    $requestData='保密数据';
                }else{
                    $requestData = $request->param();
                    foreach ($requestData as &$v) {
                        if (is_string($v)) {
                            $v = mb_substr($v, 0, 200);
                        }
                    }
                }

                $user = session('admin_auth');
                
                //查询操作
                $map = array();
                $map['name'] = $request->module()."/".$request->controller()."/".$request->action();
                $info = Db::name('auth_rule')->where($map)->value('title');
                
                $data = [
                    'uid'       => $user['aid']?:0,
                    'ip'        => $request->ip(), 
                    'location'  => implode(' ', \Ip::find($request->ip())),
                    'os'        => getOs(),
                    'browser'   => getBroswer(),
                    'url'       => $request->url(),
                    'module'    => $request->module(),
                    'controller'=> $request->controller(),
                    'action'    => $request->action(),
                    'method'    => $request->isAjax()?'Ajax':($request->isPjax()?'Pjax':$request->method()),
                    'data'      => serialize($requestData),
                    'otime'     => time(),
                    'info'     => $info,
                ];
                //dump($data);exit;
                Db::name('web_log')->insert($data);
            } catch (Exception $e) {
            }
    }
}

/**
 * 获取客户端浏览器信息 添加win10 edge浏览器判断
 * @return string
 */
function getBroswer()
{
	$sys = $_SERVER['HTTP_USER_AGENT'];  //获取用户代理字符串
	if (stripos($sys, "Firefox/") > 0) {
		preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
		$exp[0] = "Firefox";
		$exp[1] = $b[1];  //获取火狐浏览器的版本号
	} elseif (stripos($sys, "Maxthon") > 0) {
		preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
		$exp[0] = "傲游";
		$exp[1] = $aoyou[1];
	} elseif (stripos($sys, "MSIE") > 0) {
		preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
		$exp[0] = "IE";
		$exp[1] = $ie[1];  //获取IE的版本号
	} elseif (stripos($sys, "OPR") > 0) {
		preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
		$exp[0] = "Opera";
		$exp[1] = $opera[1];
	} elseif (stripos($sys, "Edge") > 0) {
		//win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
		preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
		$exp[0] = "Edge";
		$exp[1] = $Edge[1];
	} elseif (stripos($sys, "Chrome") > 0) {
		preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
		$exp[0] = "Chrome";
		$exp[1] = $google[1];  //获取google chrome的版本号
	} elseif (stripos($sys, 'rv:') > 0 && stripos($sys, 'Gecko') > 0) {
		preg_match("/rv:([\d\.]+)/", $sys, $IE);
		$exp[0] = "IE";
		$exp[1] = $IE[1];
	} elseif (stripos($sys, 'Safari') > 0) {
		preg_match("/safari\/([^\s]+)/i", $sys, $safari);
		$exp[0] = "Safari";
		$exp[1] = $safari[1];
	} else {
		$exp[0] = "未知浏览器";
		$exp[1] = "";
	}
	return $exp[0] . '(' . $exp[1] . ')';
}
