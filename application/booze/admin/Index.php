<?php
namespace app\booze\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder; // 引入ZBuilder
/**
 * Index 后台模块
 */
class Index extends Admin{
	public function index(){
		return ZBuilder::make('form')
			->setPageTitle('酒宴首页') // 设置页面标题
			//->setPageTips('') // 设置页面提示信息
			->hideBtn(['back','submit']) //隐藏默认按钮
			//->isAjax(false) //默认为ajax的post提交
			->fetch();
	}
}