<?php
/**
 * 消息队列例子测试
 * 配置文件application\extra\queue
 */
namespace app\admin\controller;

use think\Exception;
use think\Queue;

class Jobtest extends Base
{
	
    public function actionWithHelloJob(){
    
    	// 1.当前任务将由哪个类来负责处理。
    	//   当轮到该任务时，系统将生成一个该类的实例，并调用其 fire 方法
    	$jobHandlerClassName  = 'app\admin\job\Hello';
    	// 2.当前任务归属的队列名称，如果为新队列，会自动创建
    	$jobQueueName  	  = "helloJobQueue";
    	// 3.当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
    	//   ( jobData 为对象时，需要在先在此处手动序列化，否则只存储其public属性的键值对)
    	$jobData       	  = [ 'ts' => time(), 'bizId' => uniqid() , 'a' => 1 ] ;
    	// 4.将该任务推送到消息队列，等待对应的消费者去执行(发布任务)
    	$isPushed = Queue::push( $jobHandlerClassName , $jobData , $jobQueueName );
    	// database 驱动时，返回值为 1|false  ;   redis 驱动时，返回值为 随机字符串|false
    	if( $isPushed !== false ){
    		echo date('Y-m-d H:i:s') . " a new Hello Job is Pushed to the MQ"."<br>";
    	}else{
    		echo 'Oops, something went wrong.';
    	}
    }
    
    public function actionWithMultiTask(){
    	$taskType = input('taskType','taskA');
    	$jobHandlerClassName = '';
    	$jobDataArr = array();
    	$jobQueueName = '';
    	switch ($taskType) {
    		case 'taskA':
    			$jobHandlerClassName  = 'app\admin\job\MultiTask@taskA';
    			$jobDataArr = ['a'	=> '1'];
    			$jobQueueName = "multiTaskJobQueue";
    			break;
    		case 'taskB':
    			$jobHandlerClassName  = 'app\admin\job\MultiTask@taskB';
    			$jobDataArr = ['b'	=> '2'];
    			$jobQueueName = "multiTaskJobQueue";
    			break;
    		default:
    			break;
    	}
    
    	$isPushed = Queue::push($jobHandlerClassName, $jobDataArr, $jobQueueName);
    	if ($isPushed !== false) {
    		echo("the $taskType of MultiTask Job has been Pushed to ".$jobQueueName ."<br>");
    	}else{
    		throw new Exception("push a new $taskType of MultiTask Job Failed!");
    	}
    }
    
    public function sendMail(){
    	$jobHandlerClassName  = 'app\admin\job\Mail';   	
    	//$jobQueueName = "mailJobQueue";
    	$jobData = array('to'=>'997823115@qq.com','title'=>'队列发送邮件测试','content'=>'队列挺有意思的嘛!');
    	$isPushed = Queue::push( $jobHandlerClassName , $jobData , $queue = null );
    	if( $isPushed !== false ){
    		echo '队列发送邮件成功';
    	}else{
    		echo '失败';
    	}
    }
    
    
}


/**
 * 抢购思路
 * 添加抢购商品之时，先记录商品 库存队列，同时定义 排队队列，抢购结果队列
 * 高并发情况，每次进来一个用户，就先将进入排队队列，然后判断其用户是否在抢购结果对列中，
 * 如果在抢购结果对列中，则返回已经抢购，
 * 如果不在抢购结果队列中，则未抢购，库存队列减1，并且加入抢购结果队列
 * 加入抢购队列之后，由消息队列后台自动监测生成订单，Queue::push(生成订单所需要的信息);
 */

