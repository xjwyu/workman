<?php
/**
 * Workerman WebServer
 * 测试WebServer的功能
 * 建立一个简单的Web服务器，此服务器无需打开Apache就能运行，运行环境为workerman本身所自带的环境
 * 运行服务之后 浏览器输入http://127.0.0.1:55151/，则会进入 addRoot()方法所设置的入口
 * 如果服务端不在本地，请把127.0.0.1改成实际服务端ip或者域名
 */
namespace app\chat\controller;

use Workerman\Worker;
use Workerman\WebServer;
use GatewayWorker\Gateway;
use GatewayWorker\BusinessWorker;
use Workerman\Autoloader;

class Sweb{
	
	public function __construct(){
		// WebServer
		$web = new WebServer("http://0.0.0.0:55151");
		// WebServer数量
		$web->count = 2;
		// 设置站点根目录
		$web->addRoot('www.your_domain.com', __DIR__.'/Web');
		
		// 如果不是在根目录启动，则运行runAll方法
		if(!defined('GLOBAL_START'))
		{
			Worker::runAll();
		}
	}
	
}