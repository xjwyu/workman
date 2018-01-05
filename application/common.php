<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Db;
use think\Request;
use think\Response;
use app\admin\controller\Auth;
use think\Lang;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * 所有用到密码的不可逆加密方式
 * @author rainfer <81818832@qq.com>
 * @param string $password
 * @param string $password_salt
 * @return string
 */
function encrypt_password($password, $password_salt){
	return md5(md5($password) . md5($password_salt));
}

/**
 * 数据签名
 * @param array $data 被认证的数据
 * @return string 签名
 */
function data_signature($data = []){
	if(!is_array($data)){
		$data = (array)$data;
	}
	ksort($data);
	$code = http_build_query($data);
	$sign = sha1($code);
	return $sign;
}

/**
 * 加密函数
 * @param string $txt 需加密的字符串
 * @param string $key 加密密钥，默认读取data_auth_key配置
 * @return string 加密后的字符串
 */
function jiami($txt, $key = null){
	empty($key) && $key = config('data_auth_key');
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_";
	$nh = rand(0, 64);
	$ch = $chars[$nh];
	$mdKey = md5($key . $ch);
	$mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
	$txt = base64_encode($txt);
	$tmp = '';
	$k = 0;
	for ($i = 0; $i < strlen($txt); $i++) {
		$k = $k == strlen($mdKey) ? 0 : $k;
		$j = ($nh + strpos($chars, $txt [$i]) + ord($mdKey[$k++])) % 64;
		$tmp .= $chars[$j];
	}
	return $ch . $tmp;
}

/**
 * 获取当前request参数数组,去除值为空
 * @return array
 */
function get_query(){
    $param=request()->except(['s']);
    $rst=array();
    foreach($param as $k=>$v){
        if(!empty($v)){
            $rst[$k]=$v;
        }
    }
    return $rst;
}

/**
 * 列出本地目录的文件(当前路径下的)
 * @param string $path  目录路径
 * @param string $pattern  后缀  默认全部(文件夹,文件),可以多个,已|隔开,例如 *.html|*.php
 * @return array
 */
function list_file($path, $pattern = '*'){
    if (strpos($pattern, '|') !== false) {
        $patterns = explode('|', $pattern);
    } else {
        $patterns [0] = $pattern;
    }
    $i = 0;
    $dir = array();
    if (is_dir($path)) {
        $path = rtrim($path, '/') . '/';
    }
    foreach ($patterns as $pattern) {
        $list = glob($path . $pattern);  //glob()函数返回匹配指定模式的文件名或目录 第一个参数:检索模式
        if ($list !== false) {
            foreach ($list as $file) {
                $dir [$i] ['filename'] = basename($file);  //basename() 函数返回路径中的文件名部分
                $dir [$i] ['path'] = dirname($file);       //dirname() 函数返回路径中的目录部分
                $dir [$i] ['pathname'] = realpath($file);  //realpath() 函数返回绝对路径
                $dir [$i] ['owner'] = fileowner($file);    //fileowner() 函数返回文件的所有者
                $dir [$i] ['perms'] = substr(base_convert(fileperms($file), 10, 8), -4);   //fileperms() 函数返回文件或目录的权限     base_convert()进制数字转换
                $dir [$i] ['atime'] = fileatime($file);  //fileatime() 函数返回指定文件的上次访问时间
                $dir [$i] ['ctime'] = filectime($file);  //filectime() 函数返回指定文件的上次 inode 修改时间
                $dir [$i] ['mtime'] = filemtime($file);  //filemtime() 函数返回文件内容上次的修改时间
                $dir [$i] ['size'] = filesize($file);    //filesize() 函数返回指定文件的大小
                $dir [$i] ['type'] = filetype($file);    //filetype() 函数返回指定文件或目录的类型
                $dir [$i] ['ext'] = is_file($file) ? strtolower(substr(strrchr(basename($file), '.'), 1)) : '';  //strrchr() 函数查找字符串在另一个字符串中最后一次出现的位置，并返回从该位置到字符串结尾的所有字符
                $dir [$i] ['isDir'] = is_dir($file);
                $dir [$i] ['isFile'] = is_file($file);
                $dir [$i] ['isLink'] = is_link($file);  //is_link() 函数判断指定文件名是否为一个符号连接
                $dir [$i] ['isReadable'] = is_readable($file);  //is_readable() 函数判断指定文件名是否可读
                $dir [$i] ['isWritable'] = is_writable($file);  //is_writable() 函数判断指定的文件是否可写
                $i++;
            }
        }
    }
    $cmp_func = create_function('$a,$b', '
		if( ($a["isDir"] && $b["isDir"]) || (!$a["isDir"] && !$b["isDir"]) ){
			return  $a["filename"]>$b["filename"]?1:-1;
		}else{
			if($a["isDir"]){
				return -1;
			}else if($b["isDir"]){
				return 1;
			}
			if($a["filename"]  ==  $b["filename"])  return  0;
			return  $a["filename"]>$b["filename"]?-1:1;
		}
		');
    usort($dir, $cmp_func);   //usort — 使用用户自定义的比较函数对数组中的值进行排序
    return $dir;
}
/**
 * 删除文件夹
 * @param string
 * @param int
 */
function remove_dir($dir, $time_thres = -1){
    foreach (list_file($dir) as $f) {
        if ($f ['isDir']) {
            remove_dir($f ['pathname'] . '/');
        } else if ($f ['isFile'] && $f ['filename']) {
            if ($time_thres == -1 || $f ['mtime'] < $time_thres) {
                @unlink($f ['pathname']);
            }
        }
    }
}

/**
 * 设置全局配置到文件
 * 设置单一个值
 * @param $key
 * @param $value
 * @return boolean
 */
function sys_config_setbykey($key, $value){
    $file = ROOT_PATH."yudata/config.php";
    $cfg = array();
    if (file_exists($file)) {
        $cfg = include $file;
    }
    $item = explode('.', $key);
    switch (count($item)) {
        case 1:
            $cfg[$item[0]] = $value;
            break;
        case 2:
            $cfg[$item[0]][$item[1]] = $value;
            break;
    }
    return file_put_contents($file, "<?php\nreturn " . var_export($cfg, true) . ";");
}

/**
 * 设置全局配置到文件
 * 设置的是一个数组
 * @param array
 * @return boolean
 */
function sys_config_setbyarr($data){
    $file = ROOT_PATH."yudata/config.php";
    if(file_exists($file)){
        $configs=include $file;
    }else {
        $configs=array();
    }
    $configs=array_merge($configs,$data);
    return file_put_contents($file, "<?php\treturn " . var_export($configs, true) . ";");
}

/**
 * 获取全局配置
 *
 * @param $key
 * @return array|null
 */
function sys_config_get($key){
    $file = ROOT_PATH."yudata/config.php";
    $cfg = array();
    if (file_exists($file)) {
        $cfg = (include $file);
    }
    return isset($cfg[$key]) ? $cfg[$key] : null;
}
/**
 * 返回带协议的域名
 * @author rainfer <81818832@qq.com>
 */
function get_host(){
    $host=$_SERVER["HTTP_HOST"];
    $protocol=Request::instance()->isSsl()?"https://":"http://";
    return $protocol.$host;
}

/**
 * 获取图片完整路径
 * @param string $url 待获取图片url
 * @param int $cat 待获取图片类别 0为文章 1前台头像 2后台头像
 * @return string 完整图片imgurl
 */
function get_imgurl($url,$cat=0){
    if(stripos($url,'http')!==false){
        //网络图片
        return $url;
    }elseif($url && stripos($url,'/')===false && stripos($url,'\\')===false){
        //头像
        return __ROOT__.'/data/upload/avatar/'.$url;
    }elseif(empty($url)){
        //$url为空
        if($cat==2){
            $imgurl='girl.jpg';
        }elseif($cat==1){
            $imgurl='headicon.png';
        }else{
            $imgurl='no_img.jpg';
        }
        return __PUBLIC__.'/img/'.$imgurl;
    }else{
        //本地上传图片
        return __ROOT__.$url;
    }
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = ''){
	$units = array(' B', ' KB', ' MB', ' GB', ' TB', ' PB');
	for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
	return round($size, 2) . $delimiter . $units[$i];
}


/**
 * 检测当前数据库中是否含指定表
 * @param $table : 不含前缀的数据表名
 * @return bool
 */
function db_is_valid_table_name($table){
	return in_array($table, db_get_tables());
}

/**
 * 返回不含前缀的数据库表数组
 * @param bool
 * @return array
 */
function db_get_tables($prefix=false){
	$db_prefix =config('database.prefix');
	$list  = Db::query('SHOW TABLE STATUS FROM '.config('database.database'));
	$list  = array_map('array_change_key_case', $list);   //array_change_key_case()将数组的所有的键转换为大写字母
	$tables = array();
	foreach($list as $k=>$v){
		if(empty($prefix)){
			if(stripos($v['name'],strtolower(config('database.prefix')))===0){
				$tables [] = strtolower(substr($v['name'], strlen($db_prefix)));
			}
		}else{
			$tables [] = strtolower($v['name']);
		}

	}
	return $tables;
}

/**
 * 返回数据表的sql
 * @param $table : 不含前缀的表名
 * @return string
 */
function db_get_insert_sqls($table){
	$db_prefix =config('database.prefix');
	$db_prefix_re = preg_quote($db_prefix);  //preg_quote转义正则表达式字符
	$db_prefix_holder = db_get_db_prefix_holder();
	$export_sqls = array();
	$export_sqls [] = "DROP TABLE IF EXISTS $db_prefix_holder$table";
	switch (config('database.type')) {
		case 'mysql' :
			if (!($d = Db::query("SHOW CREATE TABLE $db_prefix$table"))) {
				$this->error("'SHOW CREATE TABLE $table' Error!");
			}
			$table_create_sql = $d [0] ['Create Table'];
			$table_create_sql = preg_replace('/' . $db_prefix_re . '/', $db_prefix_holder, $table_create_sql);
			$export_sqls [] = $table_create_sql;
			$data_rows = Db::query("SELECT * FROM $db_prefix$table");
			$data_values = array();
			foreach ($data_rows as &$v) {
				foreach ($v as &$vv) {
					//TODO mysql_real_escape_string替换方法
					//$vv = "'" . @mysql_real_escape_string($vv) . "'";
					$vv = "'" . addslashes(str_replace(array("\r","\n"),array('\r','\n'),$vv)) . "'"; //addslashes() 在每个双引号（"）前添加反斜杠：//每一个字段值加上单引号
				}
				$data_values [] = '(' . join(',', $v) . ')'; //每一条数据变成一条字符串
			}
			if (count($data_values) > 0) {
				$export_sqls [] = "INSERT INTO `$db_prefix_holder$table` VALUES \n" . join(",\n", $data_values);
			}
			break;
	}
	return join(";\n", $export_sqls) . ";";
}

/**
 * 返回表前缀替代符
 * @return string
 */
function db_get_db_prefix_holder(){
	return '<--db-prefix-->';
}

/**
 * 强制下载
 * @param string $filename
 * @param string $content
 */
function force_download_content($filename, $content){
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=$filename");
	echo $content;
	exit ();
}

/**
 * 数据表导出excel
 *
 * @param string $table,不含前缀表名,必须
 * @param string $file,保存的excel文件名,默认表名为文件名
 * @param string $fields,需要导出的字段名,默认全部,以半角逗号隔开
 * @param string $field_titles,需要导出的字段标题,需与$field一一对应,为空则表示直接以字段名为标题,以半角逗号隔开
 * @param string $tag,筛选条件 以字符串方式传入,例："limit:0,8;order:post_date desc,listorder desc;where:id>0;"
 *      limit:数据条数,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
 *      order:排序方式，如：post_date desc
 *      where:查询条件，字符串形式，和sql语句一样
 */
function export2excel($table,$file='',$fields='',$field_titles='',$tag=''){
	//处理传递的参数
	if(stripos($table,config('database.prefix'))==0){
		//含前缀的表,去除表前缀
		$table=substr($table,strlen(config('database.prefix')));
	}
	$file=empty($file)?config('database.prefix').$table:$file;
	$fieldsall=Db::name($table)->getTableInfo('','fields');  //getTableInfo可以获取表信息，信息类型 包括 fields,type,bind,pk，以数组的形式展示，可以指定某个信息进行获取
	$field_titles=empty($field_titles)?array():explode(",",$field_titles);
	if(empty($fields)){
		$fields=$fieldsall;
		//成员数不一致,则取字段名为标题
		if(count($fields)!=count($field_titles)){
			$field_titles=$fields;
		}
	}else{
		$fields=explode(",",$fields);
		$rst=array();
		$rsttitle=array();
		$title_y_n=(count($fields)==count($field_titles))?true:false;
		foreach($fields as $k=>$v){
			if(in_array($v,$fieldsall)){
				$rst[]=$v;
				//一一对应则取指定标题,否则取字段名
				$rsttitle[]=$title_y_n?$field_titles[$k]:$v;
			}
		}
		$fields=$rst;
		$field_titles=$rsttitle;
	}
	//处理tag标签
	$tag=param2array($tag);
	$limit = !empty($tag['limit']) ? $tag['limit'] : '';
	$order = !empty($tag['order']) ? $tag['order'] : '';
	$where=array();
	if (!empty($tag['where'])) {
		$where_str = $tag['where'];
	}else{
		$where_str='';
	}
	//处理数据
	$data= Db::name($table)->field(join(",",$fields))->where($where_str)->where($where)->order($order)->limit($limit)->select();
	//import("Org.Util.PHPExcel");
	error_reporting(E_ALL);  //规定不同的错误级别报告  E_ALL报告所有错误
	date_default_timezone_set('Asia/chongqing');
	$objPHPExcel = new \PHPExcel();
	//import("Org.Util.PHPExcel.Reader.Excel5");
	/*设置excel的属性*/
	$objPHPExcel->getProperties()->setCreator("yuxun")//创建人
	->setLastModifiedBy("yuxun")//最后修改人
	->setKeywords("excel")//关键字
	->setCategory("result file");//种类

	//第一行数据
	$objPHPExcel->setActiveSheetIndex(0);
	$active = $objPHPExcel->getActiveSheet();
	foreach($field_titles as $i=>$name){
		$ck = num2alpha($i++) . '1';
		$active->setCellValue($ck, $name);
	}
	//填充数据
	foreach($data as $k => $v){
		$k=$k+1;
		$num=$k+1;//数据从第二行开始录入
		$objPHPExcel->setActiveSheetIndex(0);
		foreach($fields as $i=>$name){
			$ck = num2alpha($i++) . $num;
			$active->setCellValue($ck, $v[$name]);
		}
	}
	$objPHPExcel->getActiveSheet()->setTitle($table);
	$objPHPExcel->setActiveSheetIndex(0);
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="'.$file.'.xls"');
	header('Cache-Control: max-age=0');
	$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');
	exit;
}


/**
 * 生成参数列表,以数组形式返回
 * @param string
 * @return array
 */
function param2array($tag = ''){
	$param = array();
	$array = explode(';',$tag);
	foreach ($array as $v){
		$v=trim($v);
		if(!empty($v)){
			list($key,$val) = explode(':',$v);
			$param[trim($key)] = trim($val);
		}
	}
	return $param;
}

/**
 * 数字到字母列
 * @param int
 * @param int
 * @return string
 */
function num2alpha($index, $start = 65){
	$str = '';
	if (floor($index / 26) > 0) {
		$str .= num2alpha(floor($index / 26)-1);
	}
	return $str . chr($index % 26 + $start);
}

/**
 * 读取excel文件到数组
 * @param string $filename,excel文件名（含路径）
 * @param string $type,excel文件类型 'Excel2007', 'Excel5', 'Excel2003XML','OOCalc', 'SYLK', 'Gnumeric', 'HTML','CSV'
 * @return array
 */
function read($filename,$type='Excel5'){
	$objReader = \PHPExcel_IOFactory::createReader($type);
	$objPHPExcel = $objReader->load($filename);
	$objWorksheet = $objPHPExcel->getActiveSheet();
	$highestRow = $objWorksheet->getHighestRow();
	$highestColumn = $objWorksheet->getHighestColumn();
	$highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
	$excelData = array();
	for ($row = 1; $row <= $highestRow; $row++) {
		for ($col = 0; $col < $highestColumnIndex; $col++) {
			$excelData[$row][] =(string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
		}
	}
	return $excelData;
}

/**
 * 随机字符
 * @param int $length 长度
 * @param string $type 类型
 * @param int $convert 转换大小写 1大写 0小写
 * @return string
 */
function random($length=10, $type='letter', $convert=0)
{
	$config = array(
			'number'=>'1234567890',
			'letter'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
			'string'=>'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
			'all'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
	);

	if(!isset($config[$type])) $type = 'letter';
	$string = $config[$type];

	$code = '';
	$strlen = strlen($string) -1;
	for($i = 0; $i < $length; $i++){
		$code .= $string{mt_rand(0, $strlen)};
	}
	if(!empty($convert)){
		$code = ($convert > 0)? strtoupper($code) : strtolower($code);
	}
	return $code;
}

/**
 * 返回按层级加前缀的菜单数组
 * @author  rainfer
 * @param array|mixed $menu 待处理菜单数组
 * @param string $id_field 主键id字段名
 * @param string $pid_field 父级字段名
 * @param string $lefthtml 前缀
 * @param int $pid 父级id
 * @param int $lvl 当前lv
 * @param int $leftpin 左侧距离
 * @return array
 */
function menu_left($menu,$id_field='id',$pid_field='pid',$lefthtml = '─' , $pid=0 , $lvl=0, $leftpin=0)
{
	$arr=array();
	foreach ($menu as $v){
		if($v[$pid_field]==$pid){
			$v['lvl']=$lvl + 1;
			$v['leftpin']=$leftpin;
			$v['lefthtml']='├'.str_repeat($lefthtml,$lvl);
			$arr[]=$v;
			$arr= array_merge($arr,menu_left($menu,$id_field,$pid_field,$lefthtml,$v[$id_field], $lvl+1 ,$leftpin+20));
		}
	}
	return $arr;
}

/**
 * 是否存在控制器
 * @param string $module 模块
 * @param string $controller 待判定控制器名
 * @return boolean
 */
function has_controller($module,$controller)
{
	$arr=\ReadClass::readDir(APP_PATH . $module. DS .'controller');
	if((!empty($arr[$controller])) && $arr[$controller]['class_name']==$controller){
		return true;
	}else{
		return false;
	}
}
/**
 * 是否存在方法
 * @param string $module 模块
 * @param string $controller 待判定控制器名
 * @param string $action 待判定控制器名
 * @return number 方法结果，0不存在控制器 1存在控制器但是不存在方法 2存在控制和方法
 */
function has_action($module,$controller,$action)
{
	$arr=\ReadClass::readDir(APP_PATH . $module. DS .'controller');
	if((!empty($arr[$controller])) && $arr[$controller]['class_name']==$controller ){
		$method_name=array_map('array_shift',$arr[$controller]['method']);
		if(in_array($action, $method_name)){
			return 2;
		}else{
			return 1;
		}
	}else{
		return 0;
	}
}

/**
 * 获取客户端操作系统信息包括win10
 * @return string
 */
function getOs()
{
	$agent = $_SERVER['HTTP_USER_AGENT'];

	if (preg_match('/win/i', $agent) && strpos($agent, '95')) {
		$os = 'Windows 95';
	} else if (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90')) {
		$os = 'Windows ME';
	} else if (preg_match('/win/i', $agent) && preg_match('/98/i', $agent)) {
		$os = 'Windows 98';
	} else if (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent)) {
		$os = 'Windows Vista';
	} else if (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent)) {
		$os = 'Windows 7';
	} else if (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent)) {
		$os = 'Windows 8';
	} else if (preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent)) {
		$os = 'Windows 10';#添加win10判断
	} else if (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent)) {
		$os = 'Windows XP';
	} else if (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent)) {
		$os = 'Windows 2000';
	} else if (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent)) {
		$os = 'Windows NT';
	} else if (preg_match('/win/i', $agent) && preg_match('/32/i', $agent)) {
		$os = 'Windows 32';
	} else if (preg_match('/linux/i', $agent)) {
		$os = 'Linux';
	} else if (preg_match('/unix/i', $agent)) {
		$os = 'Unix';
	} else if (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent)) {
		$os = 'SunOS';
	} else if (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent)) {
		$os = 'IBM OS/2';
	} else if (preg_match('/Mac/i', $agent)) {
		$os = 'Mac';
	} else if (preg_match('/PowerPC/i', $agent)) {
		$os = 'PowerPC';
	} else if (preg_match('/AIX/i', $agent)) {
		$os = 'AIX';
	} else if (preg_match('/HPUX/i', $agent)) {
		$os = 'HPUX';
	} else if (preg_match('/NetBSD/i', $agent)) {
		$os = 'NetBSD';
	} else if (preg_match('/BSD/i', $agent)) {
		$os = 'BSD';
	} else if (preg_match('/OSF1/i', $agent)) {
		$os = 'OSF1';
	} else if (preg_match('/IRIX/i', $agent)) {
		$os = 'IRIX';
	} else if (preg_match('/FreeBSD/i', $agent)) {
		$os = 'FreeBSD';
	} else if (preg_match('/teleport/i', $agent)) {
		$os = 'teleport';
	} else if (preg_match('/flashget/i', $agent)) {
		$os = 'flashget';
	} else if (preg_match('/webzip/i', $agent)) {
		$os = 'webzip';
	} else if (preg_match('/offline/i', $agent)) {
		$os = 'offline';
	} elseif (preg_match('/ucweb|MQQBrowser|J2ME|IUC|3GW100|LG-MMS|i60|Motorola|MAUI|m9|ME860|maui|C8500|gt|k-touch|X8|htc|GT-S5660|UNTRUSTED|SCH|tianyu|lenovo|SAMSUNG/i', $agent)) {
		$os = 'mobile';
	} else {
		$os = '未知操作系统';
	}
	return $os;
}

/**
 * 获取后台管理设置的邮件连接
 * @return array
 */
function get_email_options()
{
	$email_options = cache("email_options");
	if(empty($email_options)){
		$option = Db::name("Options")->where('option_l',Lang::detect())->where("option_name='email_options'")->find();
		if($option){
			$email_options = json_decode($option['option_value'],true);
		}else{
			$email_options = array();
		}
		cache("email_options", $email_options);
	}
	return $email_options;
}

/**
 * 发送邮件
 * @param string $to 收件人邮箱
 * @param string $title 标题
 * @param string $content 内容
 * @return array
 */
function sendMail($to, $title, $content)
{
	$email_options=get_email_options();
	//dump($email_options);exit;
	if($email_options && $email_options['email_open']){
		$mail = new PHPMailer(); //实例化
		// 设置PHPMailer使用SMTP服务器发送Email
		$mail->IsSMTP();
		$mail->Mailer='smtp';
		$mail->IsHTML(true);
		// 设置邮件的字符编码，若不指定，则为'UTF-8'
		$mail->CharSet='UTF-8';
		// 添加收件人地址，可以多次使用来添加多个收件人
		$mail->AddAddress($to);
		//设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
	    //$mail->addReplyTo($to);
		// 设置邮件正文
		$mail->Body=$content;
		// 设置邮件头的From字段。
		$mail->From=$email_options['email_name'];
		// 设置发件人名字
		$mail->FromName=$email_options['email_rename'];
		// 设置邮件标题
		$mail->Subject=$title;
		// 设置SMTP服务器。
		$mail->Host=$email_options['email_smtpname'];
		//by Rainfer
		// 设置SMTPSecure。
		$mail->SMTPSecure=$email_options['smtpsecure'];
		// 设置SMTP服务器端口。
		$port=$email_options['smtp_port'];
		$mail->Port=empty($port)?"25":$port;
		// 设置为"需要验证"
		$mail->SMTPAuth=true;
		// 设置用户名和密码。
		$mail->Username=$email_options['email_emname'];
		$mail->Password=$email_options['email_pwd'];
		//HTML内容转换
		//$mail->msgHTML($content);
		// 发送邮件。
		if(!$mail->Send()) {
			$mailerror=$mail->ErrorInfo;
			return array("error"=>1,"message"=>$mailerror);
		}else{
			return array("error"=>0,"message"=>"success");
		}
	}else{
		return array("error"=>1,"message"=>'未开启邮件发送或未配置');
	}
}


/**
 * 发送短信验证码
 * @param string $account 手机号
 * @param string $type 验证码类型,比如'reg','reset'...
 * @return array 结果
 */
function sendsms($account,$type)
{
	$where['sms_type']=$type;
	$where['sms_tel']=$account;
	$rst=Db::name('smslog')->where($where)->find();
	if($rst){
		if($rst['sms_time']>(time()-120)){
			return ['code'=>0,'msg'=>'已获取过,'.(time()-$rst['sms_time']).'后稍后再试'];
		}
	}
	$rst_sms=false;
	$error='未设置短信平台配置';
	$code=random(6,'number');
	if(config('think_sdk_sms.sms_open')){
		$alisms=  array (
				'app_key' => config('think_sdk_sms.AccessKeyId'),
				'app_secret' => config('think_sdk_sms.accessKeySecret')
		);
		$client = new Client(new App($alisms));
		$req    = new AlibabaAliqinFcSmsNumSend;
		$req->setRecNum($account)
		->setSmsParam([
				'number' => $code
		])
		->setSmsFreeSignName(config('think_sdk_sms.signName'))
		->setSmsTemplateCode(config('think_sdk_sms.TemplateCode'));
		$resp = $client->execute($req);
		if(isset($resp->result) && $resp->result->success){
			$rst_sms=true;
		}else{
			$error=$resp->sub_msg;
		}
	}
	if($rst_sms){
		if($rst){
			//更新
			$rst['sms_time']=time();
			$rst['sms_code']=$code;
			$rst=Db::name('smslog')->update($rst);
			if($rst!==false){
				return ['code'=>1,'msg'=>'发送成功'];
			}else{
				return ['code'=>0,'msg'=>'获取失败,请重试'];
			}
		}else{
			//插入数据库
			$data=[
					'sms_type'=>$type,
					'sms_tel'=>$account,
					'sms_time'=>time(),
					'sms_code'=>$code
			];
			$rst=Db::name('smslog')->insert($data);
			if($rst){
				return ['code'=>1,'msg'=>'发送成功'];
			}else{
				return ['code'=>0,'msg'=>'获取失败,请重试'];
			}
		}
	}else{
		return ['code'=>0,'msg'=>$error];
	}
}
/**
 * 检测短信验证码
 * @param string $account 手机号
 * @param string $type 验证码类型,比如'reg','reset'...
 * @param string $verify 验证码
 * @return boolean true|false
 */
function checksms($account,$type,$verify)
{
	$where['sms_type']=$type;
	$where['sms_tel']=$account;
	$where['sms_time']=['>',time()-120];
	$rst=Db::name('smslog')->where($where)->find();
	if(!$rst || $rst['sms_code']!=$verify){
		return false;
	}else{
		return true;
	}
}