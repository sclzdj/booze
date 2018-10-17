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
 * 前台公共控制器
 * @package app\index\controller
 */
class Home extends Common
{
    /**
     * 初始化方法
     * @author 蔡伟明 <460932465@qq.com>
     */
    protected function _initialize()
    {
        // 系统开关
        if (!config('web_site_status')) {
            if(Request::instance()->isAjax()){
                die(json_encode(['code'=>301,'error'=>'站点已经关闭，请稍后访问~']));
            }
            return $this->error('站点已经关闭，请稍后访问~');
        }
        $user_id=session('user_id');
        if($user_id>0){
            $user=db('users')->field('id,user_status,hotel_id')->find($user_id);
            if($user){
                if($user['user_status']=='1'){
                    if(Request::instance()->isAjax()){
                        die(json_encode(['code'=>302,'error'=>'登录酒店人员已被停权']));
                    }
                    return $this->redirect('login/logout');
                }else{
                    $hotel=db('hotel')->find($user['hotel_id']);
                    if(!$hotel){
                        if(Request::instance()->isAjax()){
                            die(json_encode(['code'=>305,'error'=>'所属酒店已删除']));
                        }
                        return $this->redirect('login/logout');
                    }
                    if($hotel['status']=='1'){
                        if(Request::instance()->isAjax()){
                            die(json_encode(['code'=>305,'error'=>'所属酒店已停权']));
                        }
                        return $this->redirect('login/logout');
                    }
                }
            }else{
                if(Request::instance()->isAjax()){
                    die(json_encode(['code'=>303,'error'=>'登录酒店人员已被删除']));
                }
                return $this->redirect('login/logout');
            }
        }else{
            if(Request::instance()->isAjax()){
                die(json_encode(['code'=>304,'error'=>'酒店人员未登录']));
            }
            return $this->redirect('login/logout');
        }
    }
}
