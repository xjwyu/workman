<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 * 管理员控制器
 */
namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;

class Admin extends Base
{
	protected $mod;
	protected function _initialize()
	{
		parent::_initialize();
		$this->mod = model('Admin');
	
	}
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
    	$search_name=input('search_name');
    	$search = array();
    	$search['search_name'] = $search_name;
    	$this->assign('search_name',$search_name);
    	$list = $this->mod->search($search);
    	$page = $list->render();
    	$this->assign('list',$list);
    	$this->assign('page',$page);
    	return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $auth_group=Db::name('auth_group')->select();
		$this->assign('auth_group',$auth_group);
		return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save()
    {
    	$admin = $this->mod;
        $admin_id = $admin::add(input('admin_username'),'',input('admin_pwd'),input('admin_email',''),input('admin_tel',''),input('admin_open',0),input('admin_realname',''),input('group_id'));
		if($admin_id){
			$this->success('管理员添加成功',url('admin/Admin/index'));
		}else{
			$this->error('管理员添加失败',url('admin/Admin/index'));
		}
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
    	
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
    	$admin = $this->mod;
        $auth_group = Db::name('auth_group')->select();
    	$info = $admin::get($id);
    	$this->assign('info',$info);
    	$this->assign('auth_group',$auth_group);
    	return $this->fetch();
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update()
    {
        $data=input('post.');
        $admin = $this->mod;
		$rst = $admin::edit($data);
		if($rst!==false){
			$this->success('管理员修改成功',url('admin/Admin/index'));
		}else{
			$this->error('管理员修改失败',url('admin/Admin/index'));
		}
    }
    
    

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
    	if (empty($id)){
    		$this->error('用户ID不存在',url('admin/Admin/index'));
    	}
    	
    	$admin = $this->mod;
    	$rst = $admin::del($id);
    	if($rst!==false){
    		$this->success('管理员删除成功',url('admin/Admin/index'));
    	}else{
    		$this->error('管理员删除失败',url('admin/Admin/index'));
    	}
    }
    
    /**
     * 管理员开启/禁止
     */
    public function state()
    {
    	$id=input('x');
    	if (empty($id)){
    		$this->error('用户ID不存在',url('admin/Admin/index'));
    	}
    	$status=Db::name('admin')->where('admin_id',$id)->value('admin_open');//判断当前状态情况
    	if($status==1){
    		$statedata = array('admin_open'=>0);
    		Db::name('admin')->where('admin_id',$id)->setField($statedata);
    		$this->success('状态禁止');
    	}else{
    		$statedata = array('admin_open'=>1);
    		Db::name('admin')->where('admin_id',$id)->setField($statedata);
    		$this->success('状态开启');
    	}
    }
    
    
}
