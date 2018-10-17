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

namespace app\website\controller;

use app\common\controller\Common;
use think\Db;
use think\Session;
use think\Request;
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
        $hotel=db('hotel')->field('id,status')->find(input('hotel_id',0));
        if(!$hotel){
            if(Request::instance()->isAjax()){
                die(json_encode(['code'=>302,'error'=>'访问酒店不存在']));
            }
            return $this->error('访问酒店不存在');
        }
        if($hotel['status']=='1'){
            if(Request::instance()->isAjax()){
                die(json_encode(['code'=>304,'error'=>'访问酒店已停权']));
            }
            return $this->error('访问酒店已停权');
        }
        session('hotel_id', $hotel['id']);
    }
}
