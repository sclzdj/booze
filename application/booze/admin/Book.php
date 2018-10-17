<?php
namespace app\booze\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder; // 引入ZBuilder
/**
 * Book 后台模块
 */
class Book extends Admin{
	//预订管理
	public function index(){
		// 获取排序
        $order = $this->getOrder();
        if($order===''){
            $order='a.addtime desc,id desc';
        }
        // 获取筛选
        $map = $this->getMap();
        if(isset($map['a.daytime'])){
            $map['a.daytime'][1][0]=strtotime($map['a.daytime'][1][0]);
            $map['a.daytime'][1][1]=strtotime($map['a.daytime'][1][1]);
        }
        session('excel_order',$order);
        session('excel_map',$map);
		// 读取预订数据
		$data_list = Db::name('book a')->join('hall b','a.hall_id=b.id','LEFT')->join('hotel c','b.hotel_id=c.id','LEFT')->join('users d','a.user_id=d.id','LEFT')->field('a.*,c.id hotel_id,c.name hotel_name,b.name hall_name,d.username')->where($map)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('预订列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('a.id,a.daytime,a.addtime') // 添加排序
            ->addTimeFilter('a.daytime') // 添加时间段筛选
            ->setSearch(['a.name'=>'预订人姓名','a.phone'=>'预订人手机','c.name'=>'酒店名称','b.name'=>'宴会厅名称','d.username'=>'酒店人员账号','d.realname'=>'酒店人员真实姓名']) // 设置搜索参数
            ->addFilter('type',['0'=>'微信端', '1'=>'酒店端']) // 添加筛选
            ->addFilter('chang',['0'=>'午场', '1'=>'夜场']) // 添加筛选
            ->addFilter('genre',['0'=>'其它','1'=>'婚宴','2'=>'生日','3'=>'宵夜','4'=>'会议']) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
        			['hotel_name', '酒店名称', 'link', url('booze/hotel/link', ['id' => '__hotel_id__'])],
        			['hall_name', '宴会厅名称', 'link', url('booze/hall/link', ['id' => '__hall_id__'])],
        			['daytime', '预订日期','datetime','','Y-m-d'],
        			['chang', '场别', 'status', '', ['午场', '夜场']],
        			['name', '预订人姓名'],
        			['phone', '预订人手机'],
        			['biao', '场标'],
        			['remark', '预订留言'],
                    ['genre', '种类', 'callback', 'array_v', ['其它','婚宴','生日','宵夜','会议']],
        			['type', '类型', 'status', '', ['微信端', '酒店端']],
        			['username', '酒店人员', 'callback',function($username){
        				return $username?$username.'<br>'.db('users')->where('username',$username)->value('realname'):'';
        			}],
        			['addtime', '申请时间', 'datetime', '未知'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['delete']) // 批量添加右侧按钮
        	->addTopButton('custom',['title'=>'选择导出','class'=>'btn btn-primary js-get','href'=>url('excel')])//导出xls按钮
            ->addTopButton('custom',['title'=>'全部导出','href'=>url('excelAll')])//导出xls按钮
    		->addTopButtons(['delete','custom'=>['title'=>'无筛选','href'=>url('index')]]) // 批量添加顶部按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('book') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
    //微信端预订管理
    public function c_index(){
        // 获取排序
        $order = $this->getOrder();
        if($order===''){
            $order='a.addtime desc,id desc';
        }
        // 获取筛选
        $map = $this->getMap();
        if(isset($map['a.daytime'])){
            $map['a.daytime'][1][0]=strtotime($map['a.daytime'][1][0]);
            $map['a.daytime'][1][1]=strtotime($map['a.daytime'][1][1]);
        }
        session('excel_order',$order);
        $excel_map=$map;
        $excel_map['a.type']=0;
        session('excel_map',$excel_map);
        // 读取预订数据
        $data_list = Db::name('book a')->join('hall b','a.hall_id=b.id','LEFT')->join('hotel c','b.hotel_id=c.id','LEFT')->join('users d','a.user_id=c.id','LEFT')->field('a.*,c.id hotel_id,c.name hotel_name,b.name hall_name,d.username')->where($map)->where('a.type',0)->order($order)->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('微信端预订列表') // 设置页面标题
            ->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
            ->addOrder('a.id,a.daytime,a.addtime') // 添加排序
            ->addTimeFilter('a.daytime') // 添加时间段筛选
            ->setSearch(['a.name'=>'预订人姓名','a.phone'=>'预订人手机','c.name'=>'酒店名称','b.name'=>'宴会厅名称','d.username'=>'酒店人员账号','d.realname'=>'酒店人员真实姓名']) // 设置搜索参数
            ->addFilter('chang',['0'=>'午场', '1'=>'夜场']) // 添加筛选
            ->addColumns([
                    ['id', 'ID'],
                    ['hotel_name', '酒店名称', 'link', url('booze/hotel/link', ['id' => '__hotel_id__'])],
                    ['hall_name', '宴会厅名称', 'link', url('booze/hall/link', ['id' => '__hall_id__'])],
                    ['daytime', '预订日期','datetime','','Y-m-d'],
                    ['chang', '场别', 'status', '', ['午场', '夜场']],
                    ['name', '预订人姓名'],
                    ['phone', '预订人手机'],
                    /*['biao', '场标'],
                    ['remark', '预订留言'],*/
                    ['addtime', '申请时间', 'datetime', '未知'],
                    ['right_button', '操作', 'btn'],
                ]) //添加多列数据
            ->addRightButtons(['delete']) // 批量添加右侧按钮
            ->addTopButton('custom',['title'=>'选择导出','class'=>'btn btn-primary js-get','href'=>url('excel')])//导出xls按钮
            ->addTopButton('custom',['title'=>'全部导出','href'=>url('excelAll')])//导出xls按钮
            ->addTopButtons(['delete','custom'=>['title'=>'无筛选','href'=>url('c_index')]]) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setTableName('book') // 指定数据表名
            ->setPages($page) // 设置分页数据
            ->fetch();
    }
    //酒店端预订管理
    public function b_index(){
        // 获取排序
        $order = $this->getOrder();
        if($order===''){
            $order='a.addtime desc,id desc';
        }
        // 获取筛选
        $map = $this->getMap();
        if(isset($map['a.daytime'])){
            $map['a.daytime'][1][0]=strtotime($map['a.daytime'][1][0]);
            $map['a.daytime'][1][1]=strtotime($map['a.daytime'][1][1]);
        }
        session('excel_order',$order);
        $excel_map['a.type']=1;
        session('excel_map',$excel_map);
        // 读取预订数据
        $data_list = Db::name('book a')->join('hall b','a.hall_id=b.id','LEFT')->join('hotel c','b.hotel_id=c.id','LEFT')->join('users d','a.user_id=d.id','LEFT')->field('a.*,c.id hotel_id,c.name hotel_name,b.name hall_name,d.username')->where($map)->where('a.type',1)->order($order)->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('酒店端预订列表') // 设置页面标题
            ->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
            ->addOrder('a.id,a.daytime,a.addtime') // 添加排序
            ->addTimeFilter('a.daytime') // 添加时间段筛选
            ->setSearch(['a.name'=>'预订人姓名','a.phone'=>'预订人手机','c.name'=>'酒店名称','b.name'=>'宴会厅名称','d.username'=>'酒店人员账号','d.realname'=>'酒店人员真实姓名']) // 设置搜索参数
            ->addFilter('chang',['0'=>'午场', '1'=>'夜场']) // 添加筛选
            ->addFilter('genre',['0'=>'其它','1'=>'婚宴','2'=>'生日','3'=>'宵夜','4'=>'会议']) // 添加筛选
            ->addColumns([
                    ['id', 'ID'],
                    ['hotel_name', '酒店名称', 'link', url('booze/hotel/link', ['id' => '__hotel_id__'])],
                    ['hall_name', '宴会厅名称', 'link', url('booze/hall/link', ['id' => '__hall_id__'])],
                    ['daytime', '预订日期','datetime','','Y-m-d'],
                    ['chang', '场别', 'status', '', ['午场', '夜场']],
                    ['name', '预订人姓名'],
                    ['phone', '预订人手机'],
                    ['biao', '场标'],
                    ['remark', '预订留言'],
                    ['genre', '种类', 'callback', 'array_v', ['其它','婚宴','生日','宵夜','会议']],
                    ['username', '酒店人员', 'callback',function($username){
                        return $username?$username.'<br>'.db('users')->where('username',$username)->value('realname'):'';
                    }],
                    ['addtime', '申请时间', 'datetime', '未知'],
                    ['right_button', '操作', 'btn'],
                ]) //添加多列数据
            ->addRightButtons(['delete']) // 批量添加右侧按钮
            ->addTopButton('custom',['title'=>'选择导出','class'=>'btn btn-primary js-get','href'=>url('excel')])//导出xls按钮
            ->addTopButton('custom',['title'=>'全部导出','href'=>url('excelAll')])//导出xls按钮
            ->addTopButtons(['delete','custom'=>['title'=>'无筛选','href'=>url('b_index')]]) // 批量添加顶部按钮
            ->setRowList($data_list) // 设置表格数据
            ->setTableName('book') // 指定数据表名
            ->setPages($page) // 设置分页数据
            ->fetch();
    }
	//导出xls
    public function excel(){
        $ids=input('get.ids');
        $data_list=Db::name('book a')->join('hall c','a.hall_id=c.id','LEFT')->join('hotel b','c.hotel_id=b.id','LEFT')->join('users d','a.user_id=d.id','LEFT')->field('a.*,b.id hotel_id,b.name hotel_name,c.name hall_name,d.username,d.realname')->where('a.id','in',$ids)->order(session('excel_order'))->select();
        $arr=[];
        $t=true;
        foreach ($data_list as $k => $v) {
            $pix=[];
            $pix['num']=$k+1;
            $pix['id']=$v['id'];
            $pix['hotel_name']=$v['hotel_name'];
            $pix['hall_name']=$v['hall_name'];
            $pix['genre']=$v['genre']==1?'婚宴':($v['genre']==2?'生日':($v['genre']==3?'宵夜':($v['genre']==4?'会议':'其它')));
            $pix['daytime']=date('Y-m-d',$v['daytime']);
            $pix['chang']=$v['chang']>0?'夜场':'午场';
            $pix['name']=$v['name'];
            $pix['phone']=$v['phone'];
            $pix['biao']=$v['biao'];
            $pix['remark']=$v['remark'];
            $pix['addtime']=date('Y-m-d H:i',$v['addtime']);
            $pix['type']=$v['type']>0?'酒店端':'微信端';
            $pix['username']=$v['type']>0?$v['username'].'（'.$v['realname'].'）':'';
            $arr[]=$pix;
            if($v['type']>0){
                $t=false;
            }
        }
        $filename='预订-'.date('Ymd');
        $title_arr=array('序号','ID','酒店名称','宴会厅名称','宴会类型','预订日期','场别','预订人姓名','预订人手机','餐标','预订留言','申请时间','类别','酒店人员账号（真实姓名）');
        if($t){
            foreach ($arr as $k => $v) {
                unset($arr[$k]['genre']);
                unset($arr[$k]['biao']);
                unset($arr[$k]['remark']);
                unset($arr[$k]['username']);
            }
            $title_arr=array('序号','ID','酒店名称','宴会厅名称','预订日期','场别','预订人姓名','预订人手机','申请时间','类别'); 
        }
        exportexcel($arr,$title_arr,$filename);
    }
    //导出xls
    public function excelAll(){
        $data_list=Db::name('book a')->join('hall c','a.hall_id=c.id','LEFT')->join('hotel b','c.hotel_id=b.id','LEFT')->join('users d','a.user_id=d.id','LEFT')->field('a.*,b.id hotel_id,b.name hotel_name,c.name hall_name,d.username,d.realname')->where(session('excel_map'))->order(session('excel_order'))->select();
        $arr=[];
        $t=true;
        foreach ($data_list as $k => $v) {
            $pix=[];
            $pix['num']=$k+1;
            $pix['id']=$v['id'];
            $pix['hotel_name']=$v['hotel_name'];
            $pix['hall_name']=$v['hall_name'];
            $pix['genre']=$v['genre']==1?'婚宴':($v['genre']==2?'生日':($v['genre']==3?'宵夜':($v['genre']==4?'会议':'其它')));
            $pix['daytime']=date('Y-m-d',$v['daytime']);
            $pix['chang']=$v['chang']>0?'夜场':'午场';
            $pix['name']=$v['name'];
            $pix['phone']=$v['phone'];
            $pix['biao']=$v['biao'];
            $pix['remark']=$v['remark'];
            $pix['addtime']=date('Y-m-d H:i',$v['addtime']);
            $pix['type']=$v['type']>0?'酒店端':'微信端';
            $pix['username']=$v['type']>0?$v['username'].'（'.$v['realname'].'）':'';
            $arr[]=$pix;
            if($v['type']>0){
                $t=false;
            }
        }
        $filename='预订-'.date('Ymd');
        $title_arr=array('序号','ID','酒店名称','宴会厅名称','宴会类型','预订日期','场别','预订人姓名','预订人手机','餐标','预订留言','申请时间','类别','酒店人员账号（真实姓名）');
        if($t){
            foreach ($arr as $k => $v) {
                unset($arr[$k]['genre']);
                unset($arr[$k]['biao']);
                unset($arr[$k]['remark']);
                unset($arr[$k]['username']);
            }
            $title_arr=array('序号','ID','酒店名称','宴会厅名称','预订日期','场别','预订人姓名','预订人手机','申请时间','类别'); 
        }
        exportexcel($arr,$title_arr,$filename);
    }
}