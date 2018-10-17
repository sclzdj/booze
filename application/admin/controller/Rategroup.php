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
use app\common\model\RateGroup as RateGroupModel;
use app\common\model\RateItem as RateItemModel;

class Rategroup extends Admin{

    /**
     * @return mixed|void
     * 路由配置
     */
    function index(){
        //排序
        $order = $this->getOrder();
        $admin = $this->getAdmin();
        if(!$admin){
            $this->error('登录过期请重新登录');
        }

        //筛选
        $map = $this->getMap();
        $map['agent_id'] = $admin['uid'];
        $data_list = Db::name('rate_group')->where($map)->order($order)->paginate();
        $page = $data_list->render();


        $btn_access = [
            'title' => '费率管理',
            'icon'  => 'fa fa-fw fa-toggle-up',
            'href'  => url('item', ['id' => '__id__'])
        ];
        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
            ->setTableName('rate_group')
            ->setSearch(['name'=>'费率租名称'])
            ->addColumn('id', 'ID')
            ->addColumn('name','费率租名称','text.edit')
            ->addColumn('right_button', '操作', 'btn')
            ->addRightButton('delete')
            ->addRightButton('custom', $btn_access)
            ->addOrder($order)
            ->addTopButtons('add,delete') // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch();
    }




    /**
     * @return mixed|void
     * 费率
     */
    function item($id = null){
        if ($id === null) return $this->error('缺少参数');
        //排序
        $order = $this->getOrder();

        //筛选
        $map = $this->getMap();
        $map['group_id'] = intval($id);
        $data_list = Db::name('rate_item')->where($map)->order($order)->paginate();
        $page = $data_list->render();

        // 授权按钮
        $btn_access = [
            'title' => '添加',
            'icon'  => 'fa fa-fw fa-plus-circle',
            'href'  => url('addItem', ['id' =>$id])
        ];

        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
            ->setTableName('rate_item')
            ->setSearch(['name'=>'费率名称'])
            ->addColumn('id', 'ID')
            ->addColumn('name','费率名称','text.edit')
            ->addColumn('prefix','费率前缀','text.edit')
            ->addColumn('price','单价','text.edit')
            ->addColumn('begin_hour','计费开始时间段(0-23)','text.edit')
            ->addColumn('end_hour','计费结束时间段(0-23)','text.edit')
            ->addColumn('cycle','计费周期/秒','text.edit')
            ->addColumn('right_button', '操作', 'btn')
            ->addTopButton('custom', $btn_access) // 添加授权按钮
            ->addRightButton('delete')
            ->addOrder($order)
            ->addTopButtons('delete') // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch();
    }

    /**
     * 添加费率
     */
    function addItem($id = NULL){
        if ($id === null) return $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $admin = $this->getAdmin();
            if(!$admin){
                $this->error('登录过期');
            }else{
                $data['group_id'] = $id;
                if ($user = RateItemModel::create($data)) {
                    // 记录行为
                    return $this->success('新增成功', url('item',['id'=>$id]));
                } else {
                    return $this->error('新增失败');
                }
            }
        }

        // 使用ZBuilder快速创建表单 增加分类
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                    ['text', 'name', '费率名称'],
                    ['text', 'prefix', '费率前缀'],
                    ['text', 'price', '单价'],
                    ['text', 'begin_hour', '计费开始时间段(0-23)'],
                    ['text', 'end_hour', '计费结束时间段(0-23)'],
                    ['text', 'cycle', '计费周期/秒'],
                ])

            ->fetch();
    }


    /**
     * 添加
     */
    function add(){
     // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $admin = $this->getAdmin();
            if(!$admin){
                $this->error('登录过期');
            }else{
                $data['agent_id'] = $admin['uid'];
                if ($user = RateGroupModel::create($data)) {
                    // 记录行为
                    return $this->success('新增成功', url('index'));
                } else {
                    return $this->error('新增失败');
                }
            }
        }



        // 使用ZBuilder快速创建表单 增加分类
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'name', '费率租名称'],
            ])
            ->fetch();
    }
}