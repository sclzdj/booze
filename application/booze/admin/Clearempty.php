<?php
namespace app\booze\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use app\common\builder\ZBuilder; // 引入ZBuilder
/**
 * Clearempty 后台模块
 */
class Clearempty extends Admin{
	public function index(){
        $tables=['admin_attachmen','users','sms_send_log','sms_template','users_token','verification_code','version','hotel','hall','calendar','book'];
        foreach ($tables as $k => $v) {
            Db::execute("delete from ".config('database.prefix').$v);
            Db::execute("alter table ".config('database.prefix').$v." auto_increment = 1");
        }
        return $this->success('清空表数据成功');
    }
}