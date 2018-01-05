<?php 
/**
 * workerman例子测试
 * 需要再控制台进行启动，启动文件位于根目录的server.php文件中
 */
namespace app\admin\controller;
use think\worker\Server;
use Workerman\Lib\Timer;


class Worker extends Server
{

	protected $socket = 'websocket://www.yu.com:2346';
	protected $processes = 1;
	// 新增加一个属性，用来保存uid到connection的映射(uid是用户id或者客户端唯一标识)
	/**
	 * 若改为http协议通讯，则可以在浏览器直接访问http://www.yu.com:2346/
	 * http://doc.workerman.net/315113 文档地址
	 */
	//protected $socket = 'http://www.yu.com:2346';
	
	/**
	 * 收到信息
	 * @param $connection
	 * @param $data
	 */
	public function onMessage($connection, $data)
	{
		//$connection->send('我收到你的信息了-许佳文'.$data);
		//$connection->send($data);
		global $worker;
		// 判断当前客户端是否已经验证,即是否设置了uid
		if(!isset($connection->uid))
		{
			// 没验证的话把第一个包当做uid（这里为了方便演示，没做真正的验证）
			$connection->uid = $data;
			/* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
			 * 实现针对特定uid推送数据
			 */
			$worker->connections[$connection->uid] = $connection;
			//return $connection->send('login success, your uid is ' . $connection->uid);
		}
		// 其它逻辑，针对某个uid发送 或者 全局广播
		// 假设消息格式为 uid:message 时是对 uid 发送 message
		// uid 为 all 时是全局广播
		list($recv_uid, $message) = explode('.', $data);
		$this->broadcast($message);
// 		// 全局广播
// 		if($recv_uid == 'all')
// 		{
// 			$this->broadcast($message);
// 		}
// 		// 给特定uid发送
// 		else
// 		{
// 			$this->sendMessageByUid($recv_uid, $message);
// 		}
	}
	
	/**
	 * 当连接建立时触发的回调函数
	 * @param $connection
	 */
	public function onConnect($connection)
	{
		echo "new connection from ip " . $connection->getRemoteIp() . "\n";
	}
	
	/**
	 * 当连接断开时触发的回调函数
	 * @param $connection
	 */
	public function onClose($connection)
	{
		global $worker;
		if(isset($connection->uid))
		{
			// 连接断开时删除映射
			unset($worker->uidConnections[$connection->uid]);
		}
	}
	
	/**
	 * 当客户端的连接上发生错误时触发
	 * @param $connection
	 * @param $code
	 * @param $msg
	 */
	public function onError($connection, $code, $msg)
	{
		echo "error $code $msg\n";
	}
	
	/**
	 * 每个进程启动
	 * @param $worker
	 */
	public function onWorkerStart($worker)
	{
		//定时，每1秒一次
		Timer::add(1, function()use($worker){
			$time_now = time();
			foreach($worker->connections as $connection) {
				// 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
				if (empty($connection->lastMessageTime)) {
					$connection->lastMessageTime = $time_now;
					continue;
				}
				// 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
				if ($time_now - $connection->lastMessageTime > 3600) {
					$connection->close();
				}
				//$connection->send('许佳文');
			}
		});
	}
	
	// 向所有验证的用户推送数据
	function broadcast($message)
	{
		global $worker;
		//dump($worker->connections);
		foreach($worker->connections as $connection)
		{   var_dump('xjw');
			$connection->send($message);
		}
	}
	
	// 针对uid推送数据
	function sendMessageByUid($uid, $message)
	{
		global $worker;
		if(isset($worker->connections[$uid]))
		{
			$connection = $worker->connections[$uid];
			$connection->send($message);
		}
	}
}


