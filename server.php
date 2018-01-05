<?php
//workerman监听服务入口
//cmd在当前目录 执行 php server.php
define('APP_PATH', __DIR__ . '/application/');
define('BIND_MODULE','admin/Worker');
// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';