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
use think\Db;
use think\Controller;
use think\Session;
use think\Request;
use think\Validate;

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Home
{
    /**
     * 首页
     */
    public function index(){
        $hotel=db('hotel')->find(session('hotel_id'));
        $hotel['main_img']=mainImgPath($hotel['pics']);
        $hotel['img_list']=imgLstPath($hotel['pics']);
        $halls=db('hall')->where(['hotel_id'=>session('hotel_id'),'hall_status'=>'0'])->order('addtime desc,id desc')->limit(3)->select();
        foreach ($halls as $k => $v) {
            $halls[$k]['main_img']=mainImgPath($v['pics']);
        }
        $this->assign([
            'hotel_id'=>session('hotel_id'),
            'hotel'=>$hotel,
            'halls' => $halls
        ]);
        return $this->fetch('index/index');
    }
    public function hallLst(){
        if (Request::instance()->isAjax()) {
            $page=input('page');
            $offset=$page*5;
            $halls=db('hall')->where(['hotel_id'=>session('hotel_id'),'hall_status'=>'0'])->order('addtime desc,id desc')->limit($offset,5)->select();
            if ($halls) {
                foreach ($halls as $k => $v) {
                    $halls[$k]['main_img']=mainImgPath($v['pics']);
                }
            }
            die(json_encode(['code'=>200,'error'=>'数据返回成功','data'=>$halls]));
        }
        $hotel=db('hotel')->find(session('hotel_id'));
        $halls=db('hall')->where(['hotel_id'=>session('hotel_id'),'hall_status'=>'0'])->order('addtime desc,id desc')->limit(5)->select();
        foreach ($halls as $k => $v) {
            $halls[$k]['main_img']=mainImgPath($v['pics']);
        }
        $this->assign([
            'hotel_id'=>session('hotel_id'),
            'hotel'=>$hotel,
            'halls' => $halls
        ]);
        return $this->fetch('index/hallLst');
    }
    public function hallInfo($id=''){
        $hotel=db('hotel')->find(session('hotel_id'));
        $hall=db('hall')->where(['hotel_id'=>session('hotel_id'),'hall_status'=>'0'])->find($id);
        $hall['img_list']=imgLstPath($hall['pics']);
        $this->assign([
            'hotel_id'=>session('hotel_id'),
            'hotel'=>$hotel,
            'hall' => $hall
        ]);
        return $this->fetch('index/hallInfo');
    }
    public function book(){
        $hotel=db('hotel')->find(session('hotel_id'));
        if (Request::instance()->isAjax()) {
            $data=input('post.');
            //数据输入验证
            $validate = new Validate([
                'name|姓名'  => 'require',
                'phone|手机号码'=>['require','regex'=>'/^1\d{10}$/'],
                'hall_id|宴会厅'=>'require',
                'daytime|预订时间'=>'require',
                'chang|场别'=>'require'
            ]);
            if (!$validate->check($data)) {
                die(json_encode(['code'=>201,'error'=>$validate->getError()]));
            }
            $data['type']=0;
            $data['daytime']=strtotime($data['daytime'].' 00:00:00');
            $now_daytime=strtotime(date('Y-m-d').' 00:00:00');
            if($data['daytime']<$now_daytime){
                die(json_encode(['code'=>202,'error'=>'不能预订今天之前的宴会']));
            }
            $hall=db('hall')->find($data['hall_id']);
            if($hall['hall_status']=='1'){
                die(json_encode(['code'=>204,'error'=>'该宴会厅已停权']));
            }
            $where=['hall_id'=>$data['hall_id'],'type'=>$data['type'],'name'=>$data['name'],'phone'=>$data['phone'],'daytime'=>$data['daytime'],'chang'=>$data['chang']];
            $book=db('book')->where($where)->find();
            if($book){
                die(json_encode(['code'=>203,'error'=>'该宴会厅此时间段已经预订，请重新选择']));
            }
            //数据处理
            $insert=array();
            $insert['hall_id']=$data['hall_id'];
            $insert['type']=$data['type'];
            $insert['name']=$data['name'];
            $insert['phone']=$data['phone'];
            $insert['daytime']=$data['daytime'];
            $insert['chang']=$data['chang'];
            //$insert['biao']=$data['biao'];
            //$insert['remark']=$data['remark'];
            $insert['addtime']=time();
            //数据入库
            $book_id=Db::name("book")->insertGetId($insert);
            //跳转
            if($book_id>0){
                die(json_encode(['code'=>200,'error'=>'预订成功']));
            } else {
                die(json_encode(['code'=>204,'error'=>'预订失败']));
            }
        }
        $halls=db('hall')->where(['hotel_id'=>session('hotel_id'),'hall_status'=>'0'])->order('addtime desc,id desc')->select();
        $this->assign([
            'hotel_id'=>session('hotel_id'),
            'hotel'=>$hotel,
            'halls' => $halls
        ]);
        return $this->fetch('index/book');
    }
}
