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
use app\common\model\MerchantUser as MerchantUserModel;
use app\admin\validate\Merchantuser as Merchantuserva;

class Merchantuser extends Admin{

    /**
     * @return mixed|void
     * 中继路由配置
     */
    function index(){
        $admin = $this->getAdmin();
        if(!$admin){
            $this->error('登录过期');
        }
        //排序
        $order = $this->getOrder();
        //筛选
        $map = $this->getMap();
        $map['agent_id'] = $admin['uid'];
        $data_list = Db::name('merchant_user')->where($map)->order($order)->paginate();
        $page = $data_list->render();

        $btn_access = [
            'title' => '信息设置',
            'icon'  => 'fa fa-fw fa-wrench',
            'href'  => url('editInfo', ['id' => '__id__'])
        ];

        // 使用ZBuilder构建数据表格
        return ZBuilder::make('table')
            ->setTableName('merchant_user')
//            ->addTimeFilter('created') // 添加时间段筛选
            ->setSearch(['username'=>'帐号'])
            ->addColumn('id', 'ID')
            ->addColumn('username', '帐号')
            ->addColumn('nickname', '昵称')
            ->addColumn('agent_id','所属商家','callback',function($agentId){
              $agent = Db::name('admin_user')->where('id = ?',[$agentId])->find();
              return $agent ? $agent['username']:'不存在';
            })
            ->addColumn('email', '邮箱')
            ->addColumn('email_bind', '绑定邮箱', 'yesno')
            ->addColumn('mobile', '手机')
            ->addColumn('mobile_bind', '绑定手机', 'yesno')
            ->addColumn('avatar', '头像', 'picture')
//            ->addColumn('allow_creater', '是否允许创建下级商户', 'yesno')
            ->addColumn('money', '额度')
            ->addColumn('created', '创建时间', 'datetime')
            ->addColumn('status', '状态', 'switch')
            ->addColumn('right_button', '操作', 'btn')
            ->addRightButton('custom',$btn_access)
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
    function editInfo($id = NULL,$group = 'tab1'){

        if ($id === null) return $this->error('缺少参数');

        $list_tab = [
            'tab1' => ['title' => '修改商户信息', 'url' => url('index', ['group' => 'tab1'])],
            'tab2' => ['title' => '呼叫设置', 'url' => url('index', ['group' => 'tab2'])],
        ];


        // 获取数据
        $info = MerchantUserModel::where('id', $id)->find();
        if($group == 'tab1'){
            $this->setInfo($id,$list_tab);
        }


    }

    /**
     * 设置用户信息
     */
    function setInfo($id=null){
        // 保存数据
        if ($this->request->isPost()) {

            $data = $this->request->post();
            $data['username'] = $info['username'];

            $check = new Merchantuserva();
            if (!$check->check($data)) {
                return $this->error($check->getError());
            }else{
                $pwd = input('password');
                if($pwd){
                    $data['password'] = md5($pwd);
                }else{
                    unset($data['password']);
                }
            }

            if ($user = MerchantUserModel::update($data)) {
                // 记录行为
                return $this->success('编辑成功', url('index'));
            } else {
                return $this->error('编辑失败');
            }
        }

        if($info){
            unset($info['password']);
        }

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('商户信息') // 设置页面标题
            ->setTabNav($list_tab,  $group)
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['static', 'username', '用户名'],
                ['text', 'nickname', '昵称'],
                ['text', 'password', '密码'],
                ['text', 'email', '邮箱'],
                ['text', 'mobile', '手机号码'],
                ['image', 'avatar', '头像'],
                ['number', 'money', '额度'],
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
            $admin = $this->getAdmin();
            if(!$admin){
               return $this->error('登录过期');
            }else{
                $data['agent_id'] = $admin['uid'];
                $data['created']  = time();
                $pwd = input('password');
                if(!$pwd){
                    $this->error('密码不能为空');
                }
                $check = new Merchantuserva();
                if (!$check->check($data)) {
                   return $this->error($check->getError());
                }
                //验证帐号是否被注册
                $data['password'] = md5($data['password']);

                if ($user = MerchantUserModel::create($data)) {
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
            ->addText('username', '帐号')
            ->addPassword('password', '密码')
            ->addText('nickname', '昵称')
            ->addText('email', '邮箱')
            ->addText('mobile', '手机')
            ->addImage('avatar', '头像')
            ->addNumber('money', '额度',0)
            ->fetch();
    }
}