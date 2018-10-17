<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ ruimeng898 ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 成都锐萌软件开发有限公司 [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\controller;
use app\common\model\MerchantUser as MerchantUserModel;
use app\common\model\FindTextNotification as FindTextNotificationModel;
use app\common\builder\ZBuilder;
use think\Cache;
use think\Db;
/**
 * 节点管理
 * @package app\admin\controller
 */
class Announcement extends Admin{
    // 公告列表
    public function index(){
        // 获取排序
        $order = $this->getOrder();
        // 获取筛选
        $map = $this->getMap();
        $data = FindTextNotificationModel::where(['agent_id'=>UID])->where($map)->order('weight asc')->order($order)->select();
        foreach ( $data as $key => $value){
            $merchantUser = MerchantUserModel::where('id', $value->id)->field('id,nickname')->find();
//             dump($merchantUser->id);die;
            if(!empty($merchantUser->id)){
                $data[$key]->nickname = $merchantUser->nickname;
            }else{
                $data[$key]->nickname = '';
            }
        }
        return ZBuilder::make('table')
        ->setPageTitle('文字公告管理') // 设置页面标题
        ->setTableName('find_text_notification') // 设置数据表名
        ->addOrder('weight,created') // 添加排序
        ->addColumns([
            ['nickname', '商家'],
            ['title', '文字内容'],
            ['thumb', '缩略图','picture'],
            ['url', '跳转URL','url'],
            ['view', '跳转视图'],
            ['click_mode', '按钮功能','select',FindTextNotificationModel::getButtonTree()],
            ['status', '状态','switch'],
            ['weight', '排序'],
            ['created', '创建时间','datetime'],
            ['right_button', '操作', 'btn']
        ])
        ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
        ->addRightButtons('edit,delete') // 批量添加右侧按钮
        ->addFilter('type,status') // 添加筛选
        ->setRowList($data) // 设置表格数据
        ->fetch('',[],[],[],false,'layoutClear'); // 渲染页面
    }
    // 公告添加
    public function add(){
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Announcement');
            // 验证失败 输出错误信息
            if(true !== $result) return $this->error($result);
            $data['agent_id'] = UID;
            if ($user = FindTextNotificationModel::create($data)) {
                return $this->success('新增成功',url('index'));
            } else {
                return $this->error('新增失败');
            }
        }
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
        ->setPageTitle('新增') // 设置页面标题
        ->addFormItems([ // 批量添加表单项
            ['select', 'mid', '商家', '', MerchantUserModel::getShopTree(),0],
            ['text', 'title', '文字内容', '必填，255字符以内']
        ])
        ->addImage('thumb', '缩略图')
        ->addFormItems([
            ['select', 'click_mode', '按钮功能', '', FindTextNotificationModel::getButtonTree(),'url'],
            ['text', 'url', '跳转URL','255字符以内'],
            ['text', 'view', '跳转视图','64字符以内'],
        ])
        ->addNumber('weight', '排序')
        ->addFormItems([
            ['radio', 'status', '状态', '', ['禁用','启用'], 1]
        ])
        ->fetch('',[],[],[],false,'layoutClear');
    }
    // 公告编辑
    public function edit($id = null){
        if ($id === null) return $this->error('缺少参数');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Announcement');
            // 验证失败 输出错误信息
            if(true !== $result) return $this->error($result);
            if ($user = FindTextNotificationModel::where('id',$id)->update($data)) {
                return $this->success('编辑成功',url('index'));
            } else {
                return $this->error('编辑失败');
            }
        }
        $info = FindTextNotificationModel::where('id', $id)->find();
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['select', 'mid', '商家', '', MerchantUserModel::getShopTree(),0],
                ['text', 'title', '文字内容', '必填，255字符以内']
            ])
            ->addImage('thumb', '缩略图')
            ->addFormItems([
                ['text', 'url', '跳转URL','255字符以内'],
                ['text', 'view', '跳转视图','64字符以内'],
                ['select', 'click_mode', '按钮功能', '', FindTextNotificationModel::getButtonTree(),'url']
            ])
            ->addNumber('weight', '排序')
            ->addFormItems([
                ['radio', 'status', '状态', '', ['禁用','启用'], 1]
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch('',[],[],[],false,'layoutClear');
    }
}