<?php
/**
 * ============================================================================
 * 于洵
 * 联系QQ:997823115
 * ============================================================================
 */
namespace app\admin\model;

use think\Model;

/**
 * 文章模型
 * @package application\admin\model
 */
class News extends Model{
	protected $insert = ['news_hits' => 200];
	public function user()
	{
		return $this->belongsTo('MemberList','member_list_id');
	}
	public function menu()
	{
		return $this->belongsTo('Menu','id');
	}
}
