<?php
/**
* 
* 这是一个消费者类，用于处理 mailJobQueue 队列中的任务
* 发送邮件
*/
namespace app\admin\job;

use think\queue\Job;

class Mail {

	/**
	 * fire方法是消息队列默认调用的方法
	 * @param Job            $job      当前的任务对象
	 * @param array|mixed    $data     发布任务时自定义的数据
	 */
	public function fire(Job $job,$data){
		$isJobDone = $this->send($data);       
        if ($isJobDone) {
            //成功删除任务
            echo '队列发送邮件成功';
            $job->delete();
        } else {
            //任务轮询4次后删除
            if ($job->attempts() > 3) {              
                // 第1种处理方式：重新发布任务,该任务延迟10秒后再执行
                //$job->release(10); 
                // 第2种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
                //$job->failed();   
                // 第3种处理方式：删除任务
                $job->delete();  
            }
        }
	}
	


	/**
     * 根据消息中的数据进行实际的业务处理
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function send($data) 
    {
        $result = sendMail($data['to'],$data['title'],$data['content']); 
        if ($result['error'] == 0) {
            return true;
        } else {
            return false;
        }            
    }
}