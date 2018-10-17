<?php
namespace app\admin\controller;
use think\Image;
use think\File;
use think\Db;
use think\Session;
use app\common\builder\ZBuilder;
use app\user\model\User as UserModel;
use app\common\model\AdminCallConfig as AdminCallConfigModel;
use app\common\model\AdminClientkey as AdminClientkeyModel;
use app\common\model\AdminCallRegSetting as AdminCallRegSettingModel;
use app\common\model\PackageItems as PackageItemsModel;

class Agentconfig extends Admin{


    /**
     * @param string $group  修改类型
     * @param null $uid  uid
     * @return mixed
     */
    function setAgent($group ='tab1',$uid= null){
        if(!$uid){
            $this->error('参数错误',url('user/index/index'));
        }
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if(!isset($data['set_type'])){

                $this->error('参数错误',url('setAgent'));

            }else{

                $setType = $data['set_type'];
                switch($setType){
                    case 'edit_inf':
                        $this->editInfo(null,null,$uid);
                        break;
                    case 'edit_call':
                        $this->editCall(null,null,$uid);
                        break;
                    case 'edit_client':
                        $this->editClient(null,null,$uid);
                        break;
                    case 'edit_reg_seting':
                        $this->editRegSeting(null,null,$uid);
                        break;
                }
            }
        }

        $list_tab = [
            'tab1' => ['title' => '代理信息', 'url' => url('setAgent', ['group' => 'tab1','uid'=>$uid])],
            'tab2' => ['title' => '呼叫配置', 'url' => url('setAgent', ['group' => 'tab2','uid'=>$uid])],
            'tab3' => ['title' => '客户端配置', 'url' => url('setAgent', ['group' => 'tab3','uid'=>$uid])],
            'tab4' => ['title' => '代理免费注册设置', 'url' => url('setAgent', ['group' => 'tab4','uid'=>$uid])],
        ];

        switch ($group) {
            case 'tab1':
                return $this->editInfo($list_tab,$group,$uid);
                break;
            case 'tab2':
                return $this->editCall($list_tab,$group,$uid);
                break;
            case 'tab3':
                return $this->editClient($list_tab,$group,$uid);
            break;
            case 'tab4':
                return $this->editRegSeting($list_tab,$group,$uid);
                break;
        }
    }

    /**
     * 编辑 流量代理注册配置
     * @param null $id 用户id
     * @author yzc <460932465@qq.com>
     * @return mixed
     */
    public function editRegSeting($list_tab=null,$group=null,$id = null){
        if ($id === null) return $this->error('缺少参数');
        $info = AdminCallRegSettingModel::where('agent_id = ?',[$id])->find();

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //判断数据是否存在，不存在先添加数据
               $update = false;
            if($info){
                $update =  AdminCallRegSettingModel::update($data);
            }else{
                $data['agent_id'] = $id;
                $add = new AdminCallRegSettingModel($data);

                $update =  $add->save($data);
            }


            if($update){
                return $this->success('编辑成功');
            }else{
                return $this->error('编辑失败');
            }
        }



        //注册赠送的套餐ID
        $package = [];
        $package_items = Db::name('package_items')->select();
        if($package_items){
            $package = $this->getOneArray($package_items,'id','package_name');
        }
        //费率组
        $rategroup = Db::name('rate_group')->select();
        $rategroups = [];
        if($rategroup){
            $rategroups = $this->getOneArray($rategroup,'id','name');
        }

        //中继组
        $gatewaygroup = Db::name('gateway_group')->where('status = ?',[1])->select();
        $gatewaygroups = [];
        if($gatewaygroup){
            $gatewaygroups = $this->getOneArray($gatewaygroup,'id','name');
        }





        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setTabNav($list_tab,  $group)
            ->setPageTitle('编辑客户端配置') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                    ['hidden', 'agent_id'],
                    ['hidden', 'set_type','','edit_reg_seting'],
                ])
            ->addRadio('open_reg', '状态', '', [0 => '关闭注册', 1 => '开放注册'])
            ->addRadio('verifycode_type', '通知类型', '', [0 => '短信', 1 => '语音'])
            ->addMasked('money', '注册金额', '', '999999999999.99')
            ->addTextarea('msg_template', '短信内容')
            ->addNumber('expday', '注册赠送有效期','','',0)
            ->addSelect('package_id', '套餐', '', $package)
            ->addSelect('rategroup_id_a', '回铃线费率组', '', $rategroups)
            ->addSelect('rategroup_id_b', '呼出线费率组', '', $rategroups)
            ->addSelect('gatewaygroup_id_a', '回领线中继组', '', $gatewaygroups)
            ->addSelect('gatewaygroup_id_b', '呼出线中继组', '', $gatewaygroups)
            ->setFormData($info) // 设置表单数据
            ->fetch();

    }


    /**
     * 编辑 流量代理客户端配置
     * @param null $id 用户id
     * @author yzc <460932465@qq.com>
     * @return mixed
     */
    public function editClient($list_tab=null,$group=null,$id = null){
        if ($id === null) return $this->error('缺少参数');
        $info = AdminClientkeyModel::where('agent_id = ?',[$id])->find();
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $update = false;
            $data['agent_id'] = $id;
            if($info){
                $update =  AdminClientkeyModel::update($data);
            }else{

                $add = new AdminClientkeyModel($data);
                $update =  $add->save($data);
            }

            if($update){
                return $this->success('编辑成功');
            }else{
                return $this->error('编辑失败');
            }
        }


        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setTabNav($list_tab,  $group)
            ->setPageTitle('编辑客户端配置') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                    ['hidden', 'id'],
                    ['hidden', 'agent_id'],
                    ['hidden', 'set_type','','edit_clicent'],
                    ['text', 'appid','APPID'],
                    ['text', 'appkey','APPKEY'],
                ])
            ->setFormData($info) // 设置表单数据
            ->fetch();

    }



    /**修改代理配置
     * @param null $list_tab  模块
     * @param null $group     模块名
     * @param null $id       uid
     */
   public function editCall($list_tab=null,$group=null,$id = null){
       if ($id === null) return $this->error('缺少参数');
       $info = AdminCallConfigModel::where('agent_id = ?',[$id])->find();
       // 保存数据
       if ($this->request->isPost()) {
           $data = $this->request->post();


           $update = false;
           if($info){
               $update =  AdminCallConfigModel::update($data);
           }else{
               $data['agent_id'] = $id;
               $add = new AdminCallConfigModel($data);
               $update =  $add->save($data);
           }

           if($update){
               return $this->success('编辑成功');
           }else{
               return $this->error('编辑失败');
           }
       }

       $info = AdminCallConfigModel::where('agent_id = ?',[$id])->find();
       //所属线路组
       $gateway_group = Db::name('gateway_group')->select();
       $gateways = [];
       if($gateway_group){
           $gateways = $this->getOneArray($gateway_group,'id','name');
       }

       //费率组
       $rategroup = Db::name('rate_group')->select();
       $rategroups = [];
       if($rategroup){
           $rategroups = $this->getOneArray($rategroup,'id','name');
       }


       // 使用ZBuilder快速创建表单
       return ZBuilder::make('form')
           ->setTabNav($list_tab,  $group)
           ->setPageTitle('编辑呼叫配置') // 设置页面标题
           ->addFormItems([ // 批量添加表单项
                   ['hidden', 'agent_id'],
                   ['hidden', 'set_type','','edit_call'],
               ])
           ->addMasked('balance', '代理余额', '保留两位小数的数字', '999999999999.99')
           ->addMasked('over_balance', '代理透支', '保留两位小数的数字', '999999999999.99')
           ->addSelect('gatewaygroup_id', '所属线路组', '', $gateways)
           ->addSelect('rategroup_id_a', '回领费率组ID', '', $rategroups)
           ->addSelect('rategroup_id_b', '呼出费率组ID', '', $rategroups)
           ->setFormData($info) // 设置表单数据
           ->fetch();



   }




    /**
     * 编辑 代理信息
     * @param null $id 用户id
     * @author yzc <460932465@qq.com>
     * @return mixed
     */
    public function editInfo($list_tab=null,$group=null,$id = null)
    {


        if ($id === null) return $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 禁止修改超级管理员的角色和状态
            if ($data['id'] == 1 && $data['role'] != 1) {
                return $this->error('禁止修改超级管理员角色');
            }

            // 禁止修改超级管理员的状态
            if ($data['id'] == 1 && $data['status'] != 1) {
                return $this->error('禁止修改超级管理员状态');
            }

            // 验证
            $result = $this->validate($data, 'Adminuser.update');
            // 验证失败 输出错误信息
            if(true !== $result) return $this->error($result);

            // 如果没有填写密码，则不更新密码
            if ($data['password'] == '') {
                unset($data['password']);
            }

            if ($user = UserModel::update($data)) {
                // 记录行为
                action_log('user_edit', 'admin_user', $user['id'], UID, get_nickname($user['id']));
                return $this->success('编辑成功');
            } else {
                return $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = UserModel::where('id', $id)->field('password', true)->find();

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setTabNav($list_tab,  $group)
            ->setPageTitle('编辑代理信息') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                    ['hidden', 'id'],
                    ['hidden', 'set_type','','edit_info'],
                    ['static', 'username', '用户名', '不可更改'],
                    ['text', 'nickname', '昵称', '可以是中文'],
                    ['text', 'email', '邮箱', ''],
                    ['password', 'password', '密码', '必填，6-20位'],
                    ['text', 'mobile', '手机号'],
                    ['image', 'avatar', '头像'],
                    ['radio', 'status', '状态', '', ['禁用', '启用']]
                ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
}

?>