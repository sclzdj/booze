<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017   [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\index\controller;
use app\common\controller\Common;
use think\Db;
use think\Session;
use think\Request;
use think\Validate;
/**
 * 登录控制器
 * @package app\index\home
 */
class Login extends Common
{
    //登录
    public function index(){
        if (Request::instance()->isAjax()) {
            $data=input('post.');
            //数据输入验证
            $validate = new Validate([
                'username|账户名'=>'require'
            ]);
            if($_POST['password']===''){
                 die(json_encode(['code'=>201,'error'=>'密码不能为空']));
            }
            $data['password']=$_POST['password'];
            if (!$validate->check($data)) {
                die(json_encode(['code'=>201,'error'=>$validate->getError()]));
            }
            $where=['username'=>$data['username']];
            $user=db('users')->where($where)->find();
            if(!$user){
                die(json_encode(['code'=>202,'error'=>'该账户名不存在']));
            }
            if($user['user_status']=='1'){
                die(json_encode(['code'=>203,'error'=>'该账户已停权']));
            }
            $hotel=db('hotel')->find($user['hotel_id']);
            if(!$hotel){
                 die(json_encode(['code'=>205,'error'=>'所属酒店已删除']));
            }
            if($hotel['status']=='1'){
                die(json_encode(['code'=>206,'error'=>'所属酒店已停权']));
            }
            if($user['password']!==md5(md5($data['password']))){
                die(json_encode(['code'=>204,'error'=>'密码错误']));
            }
            session('user_id',$user['id']);
            die(json_encode(['code'=>200,'error'=>'登录成功']));
        }
        return $this->fetch('login/index');
    }
    //退出
    public function logout(){
        session('user_id',null);
        if (Request::instance()->isAjax()) {
            die(json_encode(['code'=>200]));
        }
        return $this->redirect('login/index');
    }
}
