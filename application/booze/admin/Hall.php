<?php
namespace app\booze\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder; // 引入ZBuilder
/**
 * Hall 后台模块
 */
class Hall extends Admin{
	//宴会厅管理
	public function index(){
		// 获取排序
        $order = $this->getOrder();
        if($order===''){
            $order='a.addtime desc,id desc';
        }
        // 获取筛选
        $map = $this->getMap();
        if(isset($map['a.addtime'])){
            $map['a.addtime'][1][0]=strtotime($map['a.addtime'][1][0]);
            $map['a.addtime'][1][1]=strtotime($map['a.addtime'][1][1]);
        }
		// 读取宴会厅数据
		$data_list = Db::name('hall a')->join('hotel b','a.hotel_id=b.id','LEFT')->field('a.*,b.id hotel_id,b.name hotel_name')->where($map)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('宴会厅列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('a.id,a.addtime') // 添加排序
            ->addTimeFilter('a.addtime') // 添加时间段筛选
            ->setSearch(['a.name'=>'宴会厅名称','b.name'=>'酒店名称']) // 设置搜索参数
            ->addFilter('hall_status',['0'=>'正常', '1'=>'已停权']) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
        			['hotel_name', '酒店名称', 'link', url('booze/hotel/link', ['id' => '__hotel_id__'])],
        			['name', '宴会厅名称', 'link', url('link', ['id' => '__id__'])],
        			['pics', '图片','pictures'],
        			['hall_status', '是否停权', 'status', '', ['否', '是']],
        			['addtime', '添加时间', 'datetime', '未知'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['edit','delete']) // 批量添加右侧按钮
        	->addRightButton('custom',['title'=>'查看预订','href'=>url('hall_book',['id'=>'__ID__']),'icon'=>'fa fa-fw fa-bold']) // 添加右侧按钮
    		->addTopButtons(['add','delete','custom'=>['title'=>'无筛选','href'=>url('index')]]) // 批量添加顶部按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('hall') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	//全景链接
	public function link($id=''){
		$hall = Db::name('hall')->where('id',$id)->find();
		if($hall && $hall['url']!='') $str='<script>location.href="'.$hall['url'].'";</script>';
        else $str="<script>alert('无该宴会厅全景链接地址');javascript:window.opener=null;window.open('','_self');window.close();</script>";
		echo $str;
	}
	//添加宴会厅
	public function add(){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
				'hotel_id|所属酒店'  => 'require',
			    'name|宴会厅名称'  => 'require',
			    'pics|宴会厅图片'  => 'require'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			$hall=db('hall')->where(['hotel_id'=>$data['hotel_id'],'name'=>$data['name']])->find();
			if($hall){
				return $this->error('该宴会厅已存在');
			}
			//数据处理
			$insert=array();
			$insert['hotel_id']=$data['hotel_id'];
			$insert['name']=$data['name'];
			$insert['info']=$data['info'];
			$insert['pics']=$data['pics'];
			$insert['url']=$data['url'];
			$insert['hall_status']=$data['hall_status'];
			$insert['addtime']=time();
			//数据入库
			$hall_id=Db::name("hall")->insertGetId($insert);
			//跳转
			if($hall_id>0){
				return $this->success('添加宴会厅成功','index','',1);
	        } else {
	            return $this->error('添加宴会厅失败');
	        }
		}
		//选择酒店下拉框数据
		$hotels=db('hotel')->field('id,name')->order('addtime desc,id desc')->select();
		$select_hotels=array();
		foreach ($hotels as $k => $v) {
			$select_hotels[$v['id']]=$v['name'];
		}
		// 使用ZBuilder快速创建表单
		return ZBuilder::make('form')
			->setPageTitle('添加宴会厅') // 设置页面标题
			->setPageTips('请认真填写相关信息') // 设置页面提示信息
			//->setUrl('add') // 设置表单提交地址
			//->hideBtn(['back']) //隐藏默认按钮
			->setBtnTitle('submit', '确定') //修改默认按钮标题
			->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
			->addSelect('hotel_id', '所属酒店', '必须选择', $select_hotels)
			->addText('name', '宴会厅名称','请最好不要超过25个汉字')
			->addTextarea('info', '宴会厅介绍','请最好不要超过255个汉字')
			->addText('url', '宴会厅全景链接地址','如：http://www.baidu.com')
			->addImages('pics','宴会厅轮播图','图片大小不要超过2MB，上传不要超过10张')
			->addRadio('hall_status', '是否停权','', ['0' => '否', '1' => '是'],'0')
			//->isAjax(false) //默认为ajax的post提交
			->fetch();
	}
	//修改宴会厅
	public function edit($id='')
	{
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
				'hotel_id|所属酒店'  => 'require',
			    'name|宴会厅名称'  => 'require',
			    'pics|宴会厅图片'  => 'require'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			$hall=db('hall')->where(['id'=>['neq',$data['id']],'hotel_id'=>$data['hotel_id'],'name'=>$data['name']])->find();
			if($hall){
				return $this->error('该宴会厅已存在');
			}
			//数据处理
			$update=array();
			$update['id']=$data['id'];
			$update['hotel_id']=$data['hotel_id'];
			$update['name']=$data['name'];
			$update['info']=$data['info'];
			$update['url']=$data['url'];
			$update['pics']=$data['pics'];
			$update['hall_status']=$data['hall_status'];
			//数据更新
			$rt=Db::name("hall")->update($update);
			//跳转
			if($rt!==false){
				return $this->success('修改宴会厅成功','index','',1);
	        } else {
	            return $this->error('修改宴会厅失败');
	        }
		}
		// 接收id
		if ($id>0) {
			// 查处数据
			$hall=Db::name("hall")->where('id',$id)->find();
			//选择酒店下拉框数据
			$hotels=db('hotel')->field('id,name')->order('addtime desc,id desc')->select();
			$select_hotels=array();
			foreach ($hotels as $k => $v) {
				$select_hotels[$v['id']]=$v['name'];
			}
			// 使用ZBuilder快速创建表单
			return ZBuilder::make('form')
				->setPageTitle('修改宴会厅') // 设置页面标题
				->setPageTips('该操作可能会导致其他的相关数据失效，请勿随意修改信息') // 设置页面提示信息
				//->setUrl('edit') // 设置表单提交地址
				//->hideBtn(['back']) //隐藏默认按钮
				->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
				->addStatic('id', 'ID','唯一标识ID',$hall['id'])
				->addSelect('hotel_id', '所属酒店', '必须选择', $select_hotels,$hall['hotel_id'])
				->addText('name', '宴会厅名称','请最好不要超过25个汉字',$hall['name'])
				->addTextarea('info', '宴会厅介绍','请最好不要超过255个汉字',$hall['info'])
				->addText('url', '宴会厅全景链接地址','如：http://www.baidu.com',$hall['url'])
				->addImages('pics','宴会厅轮播图','图片大小不要超过2MB，上传不要超过10张',$hall['pics'])
				->addRadio('hall_status', '是否停权','', ['0' => '否', '1' => '是'],$hall['hall_status'])
				->addHidden('id',$hall['id'])
				//->isAjax(false) //默认为ajax的post提交
				->fetch();
		}
	}
	//查看预订
	public function hall_book($id){
		$hall=db('hall a')->join('hotel b','a.hotel_id=b.id','LEFT')->field('a.name,b.name hotel_name')->where('a.id',$id)->find();
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
        $excel_map['a.hall_id']=$id;
        session('excel_map',$excel_map);
		// 读取预订数据
		$data_list = Db::name('book a')->join('hall b','a.hall_id=b.id','LEFT')->join('hotel c','b.hotel_id=c.id','LEFT')->join('users d','a.user_id=d.id','LEFT')->field('a.*,c.id hotel_id,c.name hotel_name,d.username')->where($map)->where('a.hall_id',$id)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('【'.$hall['hotel_name'].'-'.$hall['name'].'】预订列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('a.id,a.daytime,a.addtime') // 添加排序
            ->addTimeFilter('a.daytime') // 添加时间段筛选
            ->setSearch(['a.name'=>'预订人姓名','a.phone'=>'预订人手机','d.username'=>'酒店人员账号','d.realname'=>'酒店人员真实姓名']) // 设置搜索参数
            ->addFilter('type',['0'=>'微信端', '1'=>'酒店端']) // 添加筛选
            ->addFilter('chang',['0'=>'午场', '1'=>'夜场']) // 添加筛选
            ->addFilter('genre',['0'=>'其它','1'=>'婚宴','2'=>'生日','3'=>'宵夜','4'=>'会议']) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
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
        	->addRightButtons(['delete'=>['href'=>url('book/delete',['id'=>'__ID__'])]]) // 批量添加右侧按钮
        	->addTopButton('custom',['title'=>'选择导出','class'=>'btn btn-primary js-get','href'=>url('booze/book/excel')])//导出xls按钮
            ->addTopButton('custom',['title'=>'全部导出','href'=>url('booze/book/excelAll')])//导出xls按钮
    		->addTopButtons(['delete'=>['href'=>url('book/delete')],'custom'=>['title'=>'无筛选','href'=>url('hall_book')]]) // 批量添加顶部按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('book') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
}