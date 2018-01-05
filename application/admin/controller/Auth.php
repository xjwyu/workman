<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 * 权限认证类
 */
namespace app\admin\controller;

/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and') 
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(think_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(think_auth_group 定义了用户组权限)
 * 
 * 4，支持规则表达式。
 *      在think_auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 */

class Auth{
	
    //默认配置
    protected $_config = array(
        'auth_on'           => true,                      // 认证开关
        'auth_type'         => 1,                         // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'auth_group',        // 用户组数据表名
        'auth_group_access' => 'auth_group_access', // 用户-用户组关系表
        'auth_rule'         => 'auth_rule',         // 权限规则表
        'auth_user'         => 'admin'             // 用户信息表
    );
    
    public function __construct(){
        if (config('auth_config')) {
            //可设置配置项 auth_config, 此配置项为数组。
            $this->_config = array_merge($this->_config, config('auth_config'));
        }
    }
    
    
    /**
     * 获得权限列表
     * @param integer $uid  用户id
     * @param integer $type
     * @param string $mode 'url'或'id'  url权限的路径  id权限的主键ID
     */
    public function getAuthList($uid,$type,$mode='url'){
        static $_authList = array(); //保存用户验证通过的权限列表
        $mode=$mode?:'url';
        $t = implode(',',(array)$type);
        if (isset($_authList[$uid.'_'.$t.'_'.$mode])) {
            return $_authList[$uid.'_'.$t.'_'.$mode];
        }
        //登录验证时,返回保存在session的列表
        if( $this->_config['auth_type']==2 && isset($_SESSION['_AUTH_LIST_'.$uid.'_'.$t.'_'.$mode])){
            return $_SESSION['_AUTH_LIST_'.$uid.'_'.$t.'_'.$mode];
        }
        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array();//保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);//得到当前用户的权限列表ID
        if (empty($ids)) {
            $_authList[$uid.$t] = array();
            return array();
        }
        //rules的ids
        $map = array(
            'id'     => array('in', $ids),
            'type'   => $type,
            'notcheck'=>0,
        );
        //读取用户组所有权限规则
        $rules = db()->name($this->_config['auth_rule'])->where($map)->where('is_delete',0)->whereOr('notcheck',1)->field('id,condition,name,notcheck')->select();
        //循环规则，判断结果。
        $authList = array();
        foreach ($rules as $rule) {
            if($rule['notcheck'] || empty($rule['condition'])){
                $authList[] = ($mode=='url')?strtolower($rule['name']):$rule['id'];
            }else{
                $user = $this->getUserInfo($uid);//获取用户信息,一维数组
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = ($mode=='url')?strtolower($rule['name']):$rule['id'];
                }
            }
        }

        $_authList[$uid.'_'.$t.'_'.$mode] = $authList;
        if($this->_config['auth_type']==2){
            //规则列表结果保存到session
            session('_AUTH_LIST_'.$uid.'_'.$t.'_'.$mode,$authList);
        }
        return array_unique($authList);
    }
    
    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  int  $uid    用户id
     * @return array       用户所属的用户组 array(
     *     array('uid'=>'用户id','group_id'=>'用户组id','title'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
     *     ...)
     */
    public function getGroups($uid){
        static $groups = array();
        if (isset($groups[$uid]))
            return $groups[$uid];
            $user_groups = db()->name($this->_config['auth_group_access'] . ' a')
            ->where("a.uid='$uid' and g.status='1'")
            ->join(config('database.prefix')."{$this->_config['auth_group']} g"," a.group_id = g.id")
            ->field('uid,group_id,title,rules')->select();
            $groups[$uid]=$user_groups?:array();
            return $groups[$uid];
    }
    
    /**
     * 获得当前用户资料
     * $uid 用户ID
     */
    protected function getUserInfo($uid){
        static $userinfo=array();
        if(!isset($userinfo[$uid])){
            $userinfo[$uid] = db()->where(array('admin_id'=>$uid))
            ->name($this->_config['auth_user'])->where('is_delete',0)->find();
        }
        return $userinfo[$uid];
    }
	
	
}
	
