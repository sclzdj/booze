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
use app\common\model\Gatewayitem as GatewayitemModel;

class Gatewayitem extends Admin{

    /**
     * @return mixed|void
     * 路由配置
     */
    function index(){
        //排序
        $order = $this->getOrder();
        //筛选
        $map = $this->getMap();
        $data_list = Db::name('gateway_item')->where($map)->order($order)->paginate();
        $page = $data_list->render();

        //中继组
        $gateway_group = Db::name('gateway_group')->select();
        $group = [];
        if($gateway_group){
            foreach($gateway_group as $key=>$val){
                 $group[$val['id']] = $val['name'];
            }
        }

        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
            ->setTableName('gateway_item')
//            ->addTimeFilter('created') // 添加时间段筛选
            ->setSearch(['host'=>'host','appid'=>'APPID'])
            ->addColumn('id', 'ID')
            ->addColumn('class', '线路类型','callback',function($class){
                    return $class==0?"接口线路":'SIP线路'
;            })
            ->addColumn('host', 'host')
            ->addColumn('appid', 'APPID')
            ->addColumn('appkey','APPKEY')
            ->addColumn('weight','排序')
            ->addColumn('group_id', '所属中继', 'select', $group)
            ->addColumn('status', '状态', 'switch')
            ->addColumn('right_button', '操作', 'btn')
            ->addRightButton('delete')
            ->addRightButton('edit')
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

            if ($user = GatewayitemModel::update($data)) {
                // 记录行为
                return $this->success('编辑成功', url('index'));
            } else {
                return $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = GatewayitemModel::where('id', $id)->find();

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
            if ($user = GatewayitemModel::create($data)) {
                // 记录行为
                return $this->success('新增成功', url('index'));
            } else {
                return $this->error('新增失败');
            }
        }






        // 使用ZBuilder快速创建表单 增加分类
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addSelect('class', '线路类型', '',['0' => '接口线路', '1' => 'SIP线路'], '')
            ->addSwitch('status','状态','',1)
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'host', 'HOST'],
                ['text', 'appid', 'APPID'],
                ['text', 'appkey', 'APPKEY'],
                ['number', 'weight', '排序值'],
            ])
            ->fetch();
    }
}