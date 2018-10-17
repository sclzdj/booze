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

use think\Db;
use think\Controller;
use think\Session;
use think\Request;
use think\Validate;
/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Home{
    public function index(){
        if (Request::instance()->isAjax()) {
        	$daytime=strtotime(input('daytime').' 00:00:00');
        	$day=db('calendar')->where(['daytime'=>$daytime])->field('good,bad,remark')->find();
        	if(!$day){
        		$day=['good'=>'<span style="color:#ccc;">无</span>','bad'=>'<span style="color:#ccc;">无</span>','remark'=>'<span style="color:#ccc;">无</span>'];
        	}else{
                if($day['good']===''){
                    $day['good']='<span style="color:#ccc;">无</span>';
                }
                if($day['bad']===''){
                    $day['bad']='<span style="color:#ccc;">无</span>';
                }
                if($day['remark']===''){
                    $day['remark']='<span style="color:#ccc;">无</span>';
                }
            }
        	die(json_encode(['code'=>200,'error'=>'数据返回成功','data'=>$day]));
        }
        $day=db('calendar')->where(['daytime'=>strtotime(date('Y-m-d'.' 00:00:00'))])->field('good,bad,remark')->find();
        if(!$day){
            $day=['good'=>'<span style="color:#ccc;">无</span>','bad'=>'<span style="color:#ccc;">无</span>','remark'=>'<span style="color:#ccc;">无</span>'];
        }else{
            if($day['good']===''){
                $day['good']='<span style="color:#ccc;">无</span>';
            }
            if($day['bad']===''){
                $day['bad']='<span style="color:#ccc;">无</span>';
            }
            if($day['remark']===''){
                $day['remark']='<span style="color:#ccc;">无</span>';
            }
        }
        $user=db('users')->find(session('user_id'));
        $hotel=db('hotel')->find($user['hotel_id']);
        $this->assign([
            'day'=>$day,
            'date'=>date('Y-m-d'),
            'user'=>$user,
            'hotel'=>$hotel
        ]);
        return $this->fetch('index/index');
    }
    public function hallLst(){
        $now_daytime=strtotime(date('Y-m-d').' 00:00:00');
    	$user=db('users')->find(session('user_id'));
    	$hotel=db('hotel')->find($user['hotel_id']);
    	$daytime=strtotime(input('daytime').' 00:00:00');
        if($daytime<$now_daytime){
            return $this->error('不能预订今天之前的宴会');
        }
    	if (Request::instance()->isAjax()) {
            $page=input('page');
            $offset=$page*6;
            $halls=db('hall')->where(['hotel_id'=>$user['hotel_id'],'hall_status'=>'0'])->order('addtime desc,id desc')->limit($offset,6)->select();
            foreach ($halls as $k => $v) {
                $halls[$k]['main_img']=mainImgPath($v['pics']);
	            $books=db('book')->where(['hall_id'=>$v['id'],'daytime'=>$daytime,'type'=>'1'])->select();
	            $halls[$k]['chang']=array();
	            foreach ($books as $key => $value) {
	            	if($value['chang']=='0'){
	            		$halls[$k]['chang'][]=0;
                        $halls[$k]['book_name']['wu']=$value['name'];
	            	}else{
	            		$halls[$k]['chang'][]=1;
                        $halls[$k]['book_name']['ye']=$value['name'];
	            	}
	            }
            }
            die(json_encode(['code'=>200,'error'=>'数据返回成功','data'=>$halls]));
        }
        $halls=db('hall')->where(['hotel_id'=>$user['hotel_id'],'hall_status'=>'0'])->order('addtime desc,id desc')->limit(6)->select();
        foreach ($halls as $k => $v) {
            $halls[$k]['main_img']=mainImgPath($v['pics']);
            $books=db('book')->where(['hall_id'=>$v['id'],'daytime'=>$daytime,'type'=>'1'])->select();
            $halls[$k]['chang']=array();
            foreach ($books as $key => $value) {
            	if($value['chang']=='0'){
            		$halls[$k]['chang'][]=0;
                    $halls[$k]['book_name']['wu']=$value['name'];
            	}else{
            		$halls[$k]['chang'][]=1;
                    $halls[$k]['book_name']['ye']=$value['name'];
            	}
            }
        }
        $this->assign([
            'daytime'=>$daytime,
            'halls' => $halls,
            'hotel'=>$hotel,
            'user'=>$user
        ]);
        return $this->fetch('index/hallLst');
    }
    public function whereHallDate(){
        $user=db('users')->find(session('user_id')); 
        $hotel=db('hotel')->find($user['hotel_id']);
        $hall_id=input('hall_id',db('hall')->where(['hotel_id'=>$user['hotel_id'],'hall_status'=>'0'])->order('addtime desc,id desc')->limit(1)->value('id'));
        $date=input('date',date('Y-m'));
        $date=explode('-',$date);
        $year=$date[0];
        $month=$date[1];
        $day_start=strtotime($year.'-'.$month.'-1 00:00:00');
        if($month==12){
            $day_end=strtotime(($year+1).'-1-1 00:00:00');
        }else{
            $day_end=strtotime($year.'-'.($month+1).'-1 00:00:00');
        }
        $num=($day_end-$day_start)/(24*60*60);
        $data=array();
        $pix=array();
        for ($i=1; $i <= $num; $i++) {
            $daytime=strtotime($year.'-'.$month.'-'.$i.' 00:00:00');
            $w=date('w', $daytime);
            $pix[$w]=['day'=>$i];
            $pix[$w]['daytime']=$daytime;
            $pix[$w]['hall_id']=$hall_id;
            $books=db('book')->where(['type'=>1,'daytime'=>$daytime,'hall_id'=>$hall_id])->select();
            $pix[$w]['chang']=array();
            foreach ($books as $k => $v) {
                if($v['chang']==0){
                    $pix[$w]['chang'][]=0;
                }else{
                    $pix[$w]['chang'][]=1;
                }
            }
            if($w==6){
                $data[]=$pix;
                $pix=array();
            }
            if($i==$num && $w!=6){
                $data[]=$pix;
                $pix=array();
            }
        }
        foreach ($data[0] as $k => $v) {
            for ($i=0; $i < $k; $i++) { 
                $data[0][$i]='';
            }
            break;
        }
        ksort($data[0]);
        if (Request::instance()->isAjax()) {
            die(json_encode(['code'=>200,'error'=>'返回数据成功','data'=>$data]));
        }
        $halls=db('hall')->where(['hotel_id'=>$user['hotel_id'],'hall_status'=>'0'])->order('addtime desc,id desc')->select();
        //dump($data);die;
        $this->assign([
            'halls'  => $halls,
            'data'   => $data,
            'hotel'=>$hotel,
            'user'=>$user
        ]);
        return $this->fetch('index/whereHallDate');
    }
    public function book(){
        $user=db('users')->find(session('user_id')); 
        $hotel=db('hotel')->find($user['hotel_id']);
        $now_daytime=strtotime(date('Y-m-d').' 00:00:00');
        if (Request::instance()->isAjax()) {
            $data=input('post.');
            $validate = new Validate([
                'name|客户姓名'  => 'require',
                'phone|客户手机'  => ['require','regex'=>'/^1\d{10}$/'],
                'hall_id|宴会厅'  => 'require',
                'daytime|预订日期'  => 'require',
                'chang|场别'  => 'require',
            ]);
            if (!$validate->check($data)) {
                die(json_encode(['code'=>201,'error'=>$validate->getError()]));
            }
            if($data['daytime']<$now_daytime){
                die(json_encode(['code'=>202,'error'=>'不能预订今天之前的宴会']));
            }
            $hall=db('hall')->find($data['hall_id']);
            if($hall['hall_status']=='1'){
                die(json_encode(['code'=>204,'error'=>'该宴会厅已停权']));
            }
            $book=db('book')->where(['type'=>'1','hall_id'=>$data['hall_id'],'daytime'=>$data['daytime'],'chang'=>$data['chang']])->find();
            if($book){
                die(json_encode(['code'=>203,'error'=>'该宴会厅此时间段已经预订，请重新选择']));
            }
            $insert=array();
            $insert['name']=$data['name'];
            $insert['phone']=$data['phone'];
            $insert['hall_id']=$data['hall_id'];
            $insert['daytime']=$data['daytime'];
            $insert['chang']=$data['chang'];
            $insert['genre']=$data['genre'];
            $insert['biao']=$data['biao'];
            $insert['remark']=$data['remark'];
            $insert['type']=1;
            $insert['user_id']=session('user_id');
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
        $hall_id=input('hall_id');
        $daytime=input('daytime');
        $chang=input('chang');
        if($daytime<$now_daytime){
            return $this->error('不能预订今天之前的宴会');
        }
        $hall=db('hall')->find($hall_id);
        if($hall['hall_status']=='1'){
            return $this->error('该宴会厅已停权');
        }
        $this->assign([
            'hall'  => $hall,
            'hall_id'  => $hall_id,
            'daytime'=>$daytime,
            'chang'=>$chang,
            'hotel'=>$hotel,
            'user'=>$user
        ]);
        return $this->fetch('index/book');
    }
    public function bookLst($is_special=0){
        $user=db('users')->find(session('user_id'));
        $hotel=db('hotel')->find($user['hotel_id']);
        if($is_special==1){
            if($user['is_special']!='1'){
                if (Request::instance()->isAjax()) {
                    die(json_encode(['code'=>201,'error'=>'你不是酒店管理员']));
                }
                return $this->error('你不是酒店管理员');
            }
            $where=['a.type'=>1,'b.hotel_id'=>$user['hotel_id']];
        }else{
            $where=['a.type'=>1,'b.hotel_id'=>$user['hotel_id'],'a.user_id'=>session('user_id')];
        }
        $time_where='';
        $hall_id=input('hall_id',0);
        $starttime=input('starttime','');
        $endtime=input('endtime','');
        if($hall_id>0){
            $where['a.hall_id']=$hall_id;
        }
        if($starttime==='' && $endtime!==''){
            $endtime=strtotime($endtime.' 23:59:59');
            $where['a.daytime']=['<=',$endtime];
        }elseif($starttime!=='' && $endtime===''){
            $starttime=strtotime($starttime.' 00:00:00');
            $where['a.daytime']=['>=',$starttime];
        }elseif($starttime!=='' && $endtime!==''){
            $starttime=strtotime($starttime.' 00:00:00');
            $endtime=strtotime($endtime.' 23:59:59');
            $time_where="a.daytime>={$starttime} AND a.daytime<=$endtime";
        }
        if (Request::instance()->isAjax()) {
            $page=input('page');
            $offset=$page*6;
            $books=db('book a')->join('hall b','a.hall_id=b.id','LEFT')->where($where)->where($time_where)->order('a.daytime desc,a.id desc')->field('a.*,b.name hall_name,b.pics')->limit($offset,6)->select();
            foreach ($books as $k => $v) {
                $books[$k]['main_img']=mainImgPath($v['pics']);
                $books[$k]['daytime']=date('Y-m-d',$v['daytime']);
            }
            die(json_encode(['code'=>200,'error'=>'返回数据成功','data'=>$books]));
        }
        $books=db('book a')->join('hall b','a.hall_id=b.id','LEFT')->where($where)->where($time_where)->order('a.daytime desc,a.id desc')->field('a.*,b.name hall_name,b.pics')->limit(6)->select();
        foreach ($books as $k => $v) {
            $books[$k]['main_img']=mainImgPath($v['pics']);
        }
        $halls=db('hall')->where(['hotel_id'=>$user['hotel_id'],'hall_status'=>'0'])->order('addtime desc,id desc')->select();
        $this->assign([
            'is_special'=>$is_special,
            'books'  => $books,
            'hotel'=>$hotel,
            'user'=>$user,
            'halls'=>$halls,
            'hall_id'=>$hall_id,
            'starttime'=>input('starttime',''),
            'endtime'=>input('endtime','')
        ]);
        if($is_special==1){
            return $this->fetch('index/bookAllLst');
        }else{
            return $this->fetch('index/bookLst');
        }
    }
    public function bookInfo(){
        $user=db('users')->find(session('user_id')); 
        $hotel=db('hotel')->find($user['hotel_id']);
        $id=input('id');
        $book=db('book a')->join('hall b','a.hall_id=b.id','LEFT')->where('a.type','1')->where('a.id',$id)->field('a.*,b.name hall_name')->find();
        if($book['user_id']!=session('user_id')){
            $user=db('users')->find(session('user_id'));
            if($user['is_special']!='1'){
                return $this->error('你不是酒店管理员');
            }
        }
        $this->assign([
            'book'  => $book,
            'hotel'=>$hotel,
            'user'=>$user
        ]);
        return $this->fetch('index/bookInfo');
    }
    public function bookEdit(){
        $user=db('users')->find(session('user_id'));
        $hotel=db('hotel')->find($user['hotel_id']);
        if (Request::instance()->isAjax()) {
            if($user['is_special']!='1'){
                die(json_encode(['code'=>201,'error'=>'你不是酒店管理员']));
            }
            $data=input('post.');
            $validate = new Validate([
                'id|预订ID'  => 'require',
                'name|客户姓名'  => 'require',
                'phone|客户手机'  => ['require','regex'=>'/^1\d{10}$/'],
                'hall_id|宴会厅'  => 'require',
                'daytime|预订日期'  => 'require',
                'chang|场别'  => 'require',
            ]);
            if (!$validate->check($data)) {
                die(json_encode(['code'=>202,'error'=>$validate->getError()]));
            }
            $data['daytime']=strtotime($data['daytime'].' 00:00:00');
            $now_daytime=strtotime(date('Y-m-d').' 00:00:00');
            if($data['daytime']<$now_daytime){
                die(json_encode(['code'=>203,'error'=>'不能预订今天之前的宴会']));
            }
            $book=db('book')->where(['type'=>'1','hall_id'=>$data['hall_id'],'daytime'=>$data['daytime'],'chang'=>$data['chang']])->where('id','neq',$data['id'])->find();
            if($book){
                die(json_encode(['code'=>204,'error'=>'该宴会厅此时间段已经预订，请重新选择']));
            }
            $update=array();
            $update['id']=$data['id'];
            $update['name']=$data['name'];
            $update['phone']=$data['phone'];
            $update['hall_id']=$data['hall_id'];
            $update['daytime']=$data['daytime'];
            $update['chang']=$data['chang'];
            $update['genre']=$data['genre'];
            $update['biao']=$data['biao'];
            $update['remark']=$data['remark'];
            $update['addtime']=time();
            //数据修改
            $rt=Db::name("book")->update($update);
            //跳转
            if($rt!==false){
                die(json_encode(['code'=>200,'error'=>'修改预订成功']));
            } else {
                die(json_encode(['code'=>205,'error'=>'修改预订失败']));
            }
        }
        if($user['is_special']!='1'){
            return $this->error('你不是酒店管理员');
        }
        $id=input('id');
        $book=db('book')->where('type','1')->find($id);
        $halls=db('hall')->where(['hotel_id'=>$user['hotel_id'],'hall_status'=>'0'])->order('addtime desc,id desc')->select();
        $this->assign([
            'halls'  => $halls,
            'book'   => $book,
            'hotel'=>$hotel,
            'user'=>$user
        ]);
        return $this->fetch('index/bookEdit');
    }
    public function bookDel(){
        $user=db('users')->find(session('user_id')); 
        if (Request::instance()->isAjax()) {
            if($user['is_special']!='1'){
                die(json_encode(['code'=>201,'error'=>'你不是酒店管理员']));
            }
            $id=input('id');
            $rt=Db::name("book")->delete($id);
            if($rt!==false){
                die(json_encode(['code'=>200,'error'=>'删除预订成功']));
            } else {
                die(json_encode(['code'=>202,'error'=>'删除预订失败']));
            }
        }
    }
}
