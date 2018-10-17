<?php
/**
 * Created by PhpStorm.
 * User: yangzc
 * Date: 2017/3/16
 * Time: 15:13
 * 路由
 */
namespace app\admin\controller;
use think\Image;
use think\File;
use think\Db;
use think\Session;
use app\common\builder\ZBuilder;
use app\common\model\Gatewaygroup as GatewaygroupModel;

class Gatewaygroup extends Admin{

    /**
     * @return mixed|void
     * 中继路由配置
     */
    function index(){
        //排序
        $order = $this->getOrder();
        //筛选
        $map = $this->getMap();
        $data_list = Db::name('gateway_group')->where($map)->order($order)->paginate();
        $page = $data_list->render();
        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
            ->setTableName('gateway_group')
//            ->addTimeFilter('created') // 添加时间段筛选
            ->setSearch(['name'=>'名称'])
            ->addColumn('id', 'ID')
            ->addColumn('name', '名称', 'text.edit')
            ->addColumn('status', '状态', 'switch')
            ->addColumn('right_button', '操作', 'btn')
            ->addRightButton('delete')
            ->addOrder($order)
            ->addTopButtons('add,delete') // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch();
    }


    /**
     * 修改 理由
     */
    function edit($id = NULL){
        if ($id === null) return $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if ($user = ItemModel::update($data)) {
                // 记录行为
                return $this->success('编辑成功', url('index'));
            } else {
                return $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = ItemModel::where('id', $id)->find();

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑') // 设置页面标题
            ->addSelect('class', '线路类型', '', ['0' => '接口线路', '1' => 'SIP线路'])
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'host', 'HOST'],
                ['text', 'appid', 'APPID'],
                ['text', 'appkey', 'APPKEY'],
                ['number', 'weight', '排序值'],
            ])

            ->setFormData($info) // 设置表单数据
            ->fetch();
    }


    /**
     * 添加
     */
    function add(){
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($user = GatewaygroupModel::create($data)) {
                // 记录行为
                return $this->success('新增成功', url('index'));
            } else {
                return $this->error('新增失败');
            }
        }



        // 使用ZBuilder快速创建表单 增加分类
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'name', '名称'],
            ])
            ->fetch();
    }
}