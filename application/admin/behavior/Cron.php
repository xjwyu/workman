<?php
/**
 * 定时任务处理
 */
namespace app\admin\behavior;

use think\Log;

class Cron
{
    protected $options   =  array(
        'cron_max_time' =>  60,
    );
    public function run(&$params)
    {
        $this->options['cron_max_time']=config('cron_max_time')?:60;
        $lockfile	 =	 RUNTIME_PATH.'cron.lock';
        //dump($lockfile);
        //is_writable() 函数判断指定的文件是否可写 ; filemtime() 函数返回文件内容上次的修改时间;  修改的时间到现在不到cron_max_time则不需要进行日志清除,否则创建文件锁cron.lock
        //此处文件锁的作用  如果
        if(is_writable($lockfile) && filemtime($lockfile) > $_SERVER['REQUEST_TIME'] - $this->options['cron_max_time']) {
            return ;
        } else {
            touch($lockfile);  //touch() 函数设置指定文件的访问和修改时间。
        }
        
        @set_time_limit(1000);
        @ignore_user_abort(true);   // ignore_user_abort() 函数设置与客户机断开是否会终止脚本的执行
        if(is_file(RUNTIME_PATH.'~crons.php')) {
            $crons	=	include RUNTIME_PATH.'~crons.php';
        }elseif(is_file(ROOT_PATH.'application/crons.php')){
            $crons	=	include ROOT_PATH.'application/crons.php';
        }
        if(isset($crons) && is_array($crons)) {
            $update	 =	 false;
            $log	=	array();
            foreach ($crons as $key=>$cron){
                if(empty($cron[2]) || $_SERVER['REQUEST_TIME']>=$cron[2]) {
                    debug('cronStart');
                    include ROOT_PATH.'application/cron/'.$cron[0].'.php';
                    debug('cronEnd');
                    $_useTime	 =	 debug('cronStart','cronEnd', 6);
                    $cron[2]	=	$_SERVER['REQUEST_TIME']+$cron[1];
                    $crons[$key]	=	$cron;
                    $log[] = "Cron:$key Runat ".date('Y-m-d H:i:s')." Use $_useTime s\n";
                    $update	 =	 true;
                }
            }
            if($update) {
                Log::write("于洵start");
                Log::write(implode('',$log));
                Log::write("于洵end");
                $content  = "<?php\nreturn ".var_export($crons,true).";\n?>";
                file_put_contents(RUNTIME_PATH.'~crons.php',$content);  //生成定时缓存文件
            }
        }
        file_exists($lockfile) && unlink($lockfile);
        return ;
    }
}