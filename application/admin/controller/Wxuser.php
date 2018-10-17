<?php
namespace app\admin\controller;
use app\common\builder\ZBuilder;
use app\common\controller\Common;
use app\admin\model\Menu as MenuModel;
use app\admin\model\Module as ModuleModel;
use app\user\model\Role as RoleModel;
use think\Cache;
use think\Db;
use think\helper\Hash;


class Wxuser extends Admin
{
    /**
     * 微信用户列表
     */
    public function index()
    {
        // 获取筛选
        $map = $this->getMap();
        $order = $this->getOrder();
        
        // 读取用户数据
        $data_list = Db::table('user')->where($map)->order($order)->paginate();
        
        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
        ->setSearch(['wx_nickname'=>'微信昵称'])
        ->addOrder('id,wx_nickname') // 添加排序
        ->addFilter('id,wx_nickname') // 添加筛选
        ->addColumn('id', 'ID')
        ->addColumn('wx_openid', '微信openid')
        ->addColumn('wx_nickname', '微信昵称')
        ->addColumn('mobile', '手机号')
        ->setRowList($data_list) // 设置表格数据
        ->fetch();
    }
    /**
     * 活动管理
     */
    public function active()
    {
        // 获取筛选
        $map = $this->getMap();
        $order = $this->getOrder();
        // 读取用户数据
        $data_list = Db::table('activity')->where($map)->order($order)->paginate();
        
        $filed = [
            ['hidden', 'id'],
            ['text','title','活动标题','必填,活动标题'],
            ['text','sub_title','活动子标题','必填,活动子标题'],
            ['text','remark','活动介绍','必填,活动介绍'],
            ['number','need_money','活动经费','参加活动需要的费用'],
        ];
        
        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
        ->setTableName("activity")
        ->setSearch(['title'=>'活动标题'])
        ->addOrder('id') // 添加排序
        ->addFilter('id') // 添加筛选
        ->addColumn('id', 'ID')
        ->addColumn('title', '活动标题')
        ->addColumn('sub_title', '活动介绍')
        ->addColumn('remark', '活动备注')
        ->addColumn('need_money','报名费用','callback',function($value){
            return sprintf("￥%d",$value);
        })
        ->addColumn('status', '状态', 'switch')
        ->addColumn('right_button', '操作', 'btn')
        
        ->autoAdd($filed,'activity') // 添加新增按钮
        ->autoEdit($filed,'activity') // 添加编辑按钮
//             ->addTopButton('add') // 添加顶部按钮
            ->addTopButton('delete',['table' => 'activity']) // 添加顶部按钮
        ->addRightButton('delete',['table' => 'activity'])
        ->setRowList($data_list) // 设置表格数据
        ->fetch();
    }
//     public function delete($record = [])
//     {
//         $ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
//         $table = input('param.table');
//         $field = input('param.field', 'status');
//         if (empty($ids)) return $this->error('缺少主键');
//         if (empty($table)) return $this->error('缺少表名');
//         $pk = 'id'; // 主键名称
//         $map[$pk] = ['in', $ids];
        
//         $result = Db::table($table)->where($map)->delete();
//         if($result)
//         {
//             return $this->success("删除成功");
//         }else{
//             return $this->error("删除失败");
//         }
//     }

    public function refund()
    {
        $id = input('param.id',0);
        $db = Db::table("activity_join")->where("id = ?",[$id])->find();
        if(empty($db))
        {
            exit('not find db');
        }else if($db['pay_method'] !== 'wx')
        {
            exit('只能退款微信渠道支付的订单');
        }else if(intval($db['pay_status']) <> 1)
        {
            exit('只能对成功支付的订单进行退款操作~');
        }
        $activity = Db::table("activity")->where("id = ?",[$db['activity_id']])->find();
        $allowRefund = intval($db['pay_money']-$db['refund_money']);
        $diff_number = ceil($allowRefund/$activity['need_money']);
        if($this->request->isGet())
        {
            if(empty($activity))
            {
                return false;
            }
            return ZBuilder::make('form')
            ->setPageTitle("活动退款")
            ->addStatic('room_num', '房号', '', $db['room_num'])
            ->addStatic('phone', '手机号', '', $db['concat_phone'])
            ->addStatic('name', '联系人', '', $db['concat_name'])
            ->addStatic('pay_money', '可退款', '', '￥'.sprintf("%.2f",$allowRefund))
            ->addNumber('refund_number', '退款人数', '', '1', '1', $diff_number)
            ->fetch();
        }else{
            $refund_number = intval(input("post.refund_number",0));
            if($refund_number > 0 && $refund_number <= $diff_number)
            {
                $s = activity_refund($id,$refund_number*$activity['need_money']*100);
                if($s === false)
                {
                    $this->error("退款失败,请联系管理员~");
                }else{
                    $this->success("退款申请成功,请稍后...",url("activityjoin"));
                }
                
            }else{
                $this->error("请输入正确的退款人数");
            }
        }
    }
    /**
     * 报名情况
     */
    public function activityjoin()
    {
        // 获取筛选
        $map = $this->getMap();
        $order = $this->getOrder();
        if(!$order)
        {
            $order = 'id DESC';
        }
        // 读取用户数据
        $data_list = Db::table('activity_join')->where($map)->order($order)->paginate();
        $btn_access = [
            'title' => '退款',
            'icon'  => 'fa fa-fw fa-calculator',
            'href'  => url('refund', ['id' => '__id__'])
        ];
        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
//         ->setPageTips("费用总计")
        ->setTableName("activity_join")
        ->addTimeFilter("pay_time")
        ->hideCheckbox()
        ->setSearch(['concat_phone'=>'手机号码','concat_name'=>'联系名称','room_num'=>'房号','pay_orderid'=>'订单号'])
        ->addOrder('id,pay_time') // 添加排序
        ->addFilter('concat_phone') // 添加筛选
        ->addColumn('id', 'ID','hidden')
        ->addColumn('room_num', '房号')
        ->addColumn('concat_name', '联系人')
        ->addColumn('concat_phone', '联系号码','tel')
        ->addColumn('adult_num', '成人')
        ->addColumn('children_num', '小朋友')
        ->addColumn('aged_num', '老年人')
        ->addColumn('pay_status', '支付状态','status','', ['未支付', '已支付'])
//         ->addColumn('pay_time', '支付时间','callback',function($v){
//             return $v == 0 ? '':date('m-d H:i',$v);
//         })
        ->addColumn('pay_time', '支付时间','datetime','','m/d H:i')
        ->addColumn('pay_money', '支付金额','callback',function($v){
            return sprintf("￥%.2f",$v);
        })
        ->addColumn('refund_money', '退款金额','callback',function($v){
            return sprintf("￥%.2f",$v);
        })
        ->addColumn('remark', '备注','textarea.edit')
        ->addColumn('is_sign', '是否签到','status','', ['未签到', '已签到'])
        ->addColumn('right_button', '操作', 'btn')
        ->addRightButton('custom', $btn_access)
        ->setRowList($data_list) // 设置表格数据
        ->fetch();
    }
    /**
     * 启用
     * @param array $record 行为日志内容
     * @author 蔡伟明 <460932465@qq.com>
     * @return mixed
     */
    public function delete($record = [])
    {
        return $this->setStatus('delete', $record);
    }
    
    /**
     * 快速编辑
     * @param array $record 行为日志内容
     * @author 蔡伟明 <460932465@qq.com>
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $field = input('post.name', '');
        $value = input('post.value', '');
        $table = input('post.table', '');
        $type  = input('post.type', '');
        $id    = input('post.pk', '');
        $validate = input('post.validate', '');
        $validate_fields = input('post.validate_fields', '');
    
        if ($table == '') return $this->error('缺少表名');
        if ($field == '') return $this->error('缺少字段名');
        if ($id == '') return $this->error('缺少主键值');
    
        // 验证是否操作管理员
        if ($table == 'admin_user' || $table == 'admin_role') {
            if ($id == 1) {
                return $this->error('禁止操作超级管理员');
            }
        }
    
        // 验证器
        if ($validate != '') {
            $validate_fields = array_flip(explode(',', $validate_fields));
            if (isset($validate_fields[$field])) {
                $result = $this->validate([$field => $value], $validate.'.'.$field);
                if (true !== $result) $this->error($result);
            }
        }
    
        switch ($type) {
            // 日期时间需要转为时间戳
            case 'combodate':
                $value = strtotime($value);
                break;
                // 开关
            case 'switch':
                $value = $value == 'true' ? 1 : 0;
                break;
                // 开关
            case 'password':
                $value = Hash::make((string)$value);
                break;
        }
    
        // 主键名
        $pk     = Db::table($table)->getPk($table);
        $result = Db::table($table)->where($pk, $id)->setField($field, $value);
    
        cache('hook_plugins', null);
        cache('system_config', null);
        cache('access_menus', null);
        if (false !== $result) {
            // 记录行为日志
            if (!empty($record)) {
                call_user_func_array('action_log', $record);
            }
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }
    
    /**
     * 自动创建添加页面
     * @author caiweiming <460932465@qq.com>
     */
    public function add()
    {
        // 获取表单项
        $cache_name = $this->request->module().'/'.$this->request->controller().'/add';
        $cache_name = strtolower($cache_name);
        $form       = Cache::get($cache_name, []);
        if (!$form) {
            $this->error('自动新增数据不存在，请重新打开此页面');
        }
    
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
    
            // 验证
            if ($form['validate'] != '') {
                $result = $this->validate($data, $form['validate']);
                if(true !== $result) $this->error($result);
            }
    
            // 是否需要自动插入时间
            if ($form['auto_time'] != '') {
                foreach ($form['auto_time'] as $item) {
                    $data[$item] = $this->request->time();
                }
            }
    
            // 插入数据
            if (Db::table($form['table'])->insert($data)) {
                return $this->success('新增成功', cookie('__forward__'));
            } else {
                return $this->error('新增失败');
            }
        }
    
        // 显示添加页面
        return ZBuilder::make('form')
        ->addFormItems($form['items'])
        ->fetch();
    }
    
    /**
     * 自动创建编辑页面
     * @param string $id 主键值
     * @author caiweiming <460932465@qq.com>
     */
    public function edit($id = '')
    {
        if ($id === '') $this->error('参数错误');
    
        // 获取表单项
        $cache_name = $this->request->module().'/'.$this->request->controller().'/edit';
        $cache_name = strtolower($cache_name);
        $form       = Cache::get($cache_name, []);
        if (!$form) {
            $this->error('自动编辑数据不存在，请重新打开此页面');
        }
    
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
    
            // 验证
            if ($form['validate'] != '') {
                $result = $this->validate($data, $form['validate']);
                if(true !== $result) $this->error($result);
            }
    
            // 是否需要自动插入时间
            if ($form['auto_time'] != '') {
                foreach ($form['auto_time'] as $item) {
                    $data[$item] = $this->request->time();
                }
            }
    
            // 更新数据
            if (Db::table($form['table'])->update($data)) {
                return $this->success('编辑成功', cookie('__forward__'));
            } else {
                return $this->error('编辑失败');
            }
        }
    
        // 获取数据
        $info = Db::table($form['table'])->find($id);
    
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
        ->setPageTitle('编辑')
        ->addFormItems($form['items'])
        ->setFormData($info)
        ->fetch();
    }
    
    /**
     * 设置状态
     * 禁用、启用、删除都是调用这个内部方法
     * @param string $type 操作类型：enable,disable,delete
     * @param array $record 行为日志内容
     * @author 蔡伟明 <460932465@qq.com>
     * @return mixed
     */
    public function setStatus($type = '', $record = [])
    {
        $ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $table = input('param.table');
        $field = input('param.field', 'status');
    
        if (empty($ids)) return $this->error('缺少主键');
        if (empty($table)) return $this->error('缺少表名');
    
        // 验证是否操作管理员
        if ($table == 'admin_user' || $table == 'admin_role' || $table == 'admin_module') {
            if (is_array($ids) && in_array('1', $ids)) {
                // 去掉值为1的数据，比如超级管理员，系统核心模块
                return $this->error('禁止操作');
            } else if($ids === '1') {
                return $this->error('禁止操作');
            }
        }
    
        $pk = Db::table($table)->getPk($table); // 主键名称
        $map[$pk] = ['in', $ids];
    
        switch ($type) {
            case 'disable': // 禁用
                $result = Db::table($table)->where($map)->setField($field, 0);
                break;
            case 'enable': // 启用
                $result = Db::table($table)->where($map)->setField($field, 1);
                break;
            case 'delete': // 删除
                $result = Db::table($table)->where($map)->delete();
                break;
            default:
                return $this->error('非法操作');
                break;
        }
    
        if (false !== $result) {
            Cache::clear();
            // 记录行为日志
            if (!empty($record)) {
                call_user_func_array('action_log', $record);
            }
            return $this->success('操作成功');
        } else {
            return $this->error('操作失败');
        }
    }
    
    public function moneydetail()
    {
        // 获取筛选
        $map = $this->getMap();
        $order = $this->getOrder();
        // 读取数据
        $data_list = Db::table('activity_money_detail')->where($map)->order($order)->paginate();
//         ->addSelect('activity_id', '选择活动', '', $active)
//         ->addText('title','项目名称')
//         ->addNumber('money','项目费用')
//         ->addFile('pic', '项目图片')
//         ->addDate('day', '费用日期')
//         ->addText('operator','经办人')
//         ->addTextarea('description', '摘要')
//         $filed = [
//             ['hidden', 'id'],
//             ['select','activity_id','选择活动'],
//             ['text','title','活动标题','必填,活动标题'],
//             ['text','sub_title','活动子标题','必填,活动子标题'],
//             ['text','remark','活动介绍','必填,活动介绍'],
//             ['number','need_money','活动经费','参加活动需要的费用'],
//         ];
        $btn_access = [
            'title' => '添加',
            'icon'  => 'fa fa-plus-circle',
            'href'  => url('moneydetailadd')
        ];
        $edit_access = [
            'title' => '编辑',
            'icon'  => 'fa fa-pencil',
            'href'  => url('moneydetailedit',['id'=>'__id__'])
        ];
        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
        ->setTableName("activity_money_detail")
        ->setSearch(['title'=>'费用详情'])
        ->addOrder('id') // 添加排序
        ->addFilter('id') // 添加筛选
        ->addColumn('id', 'ID')
        ->addColumn('title', '项目名称')
        ->addColumn('description', '项目介绍')
        ->addColumn('money','项目费用','callback',function($value){
            return sprintf("￥%.2f",$value);
        })
        ->addColumn('day', '日期')
        ->addColumn('operator', '经办人')
        ->addColumn('right_button', '操作', 'btn')
        
//         ->autoAdd($filed,'activity') // 添加新增按钮
//         ->autoEdit($filed,'activity') // 添加编辑按钮
        ->addTopButton('custom',$btn_access) // 添加顶部按钮
        ->addTopButton('delete',['table' => 'activity']) // 添加顶部按钮
        ->addRightButton('delete',['table' => 'activity'])
        ->addRightButton('custom',$edit_access)
        ->setRowList($data_list) // 设置表格数据
        ->fetch();
    }
    public function moneydetailadd()
    {
        if($this->request->isGet())
        {
            $active = Db::table("activity")->field("id,title")->select();
            $active = toHashmap($active, 'id','title');
            return ZBuilder::make('form')
            ->setPageTitle("新增费用项目")
            ->addSelect('activity_id', '选择活动', '', $active)
            ->addText('title','项目名称')
            ->addNumber('money','项目费用')
            ->addImage('pic', '项目图片')
            ->addDate('day', '费用日期')
            ->addText('operator','经办人')
            ->addTextarea('description', '摘要')
            ->fetch();
        }else{
            // 表单数据
            $data = $this->request->post();
            // 插入数据
            if (Db::table('activity_money_detail')->insert($data)) {
                return $this->success('新增费用成功');
            } else {
                return $this->error('新增费用失败');
            }
        }
    }
    public function moneydetailedit($id = '')
    {
        if ($id == '') $this->error('参数错误');
        $info = Db::table('activity_money_detail')->find($id);
        if($this->request->isGet())
        {
            $active = Db::table("activity")->field("id,title")->select();
            $active = toHashmap($active, 'id','title');
            return ZBuilder::make('form')
            ->setPageTitle("新增费用项目")
            ->addHidden("id","id")
            ->addSelect('activity_id', '选择活动', '', $active)
            ->addText('title','项目名称')
            ->addNumber('money','项目费用')
            ->addImage('pic', '项目图片')
            ->addDate('day', '费用日期')
            ->addText('operator','经办人')
            ->addTextarea('description', '摘要')
            ->setFormData($info)
            ->fetch();
        }else{
            // 表单数据
            $data = $this->request->post();
            // 插入数据
            
            if (Db::table('activity_money_detail')->update($data)) {
                return $this->success('编辑成功',url("moneydetail"));
            } else {
                return $this->error('编辑失败');
            }
        }
    }
}