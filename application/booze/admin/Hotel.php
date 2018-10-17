<?php
namespace app\booze\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder; // 引入ZBuilder
/**
 * Hotel 后台模块
 */
class Hotel extends Admin{
	//酒店管理
	public function index(){
		// 获取排序
        $order = $this->getOrder();
        if($order===''){
            $order='addtime desc,id desc';
        }
        // 获取筛选
        $map = $this->getMap();
        if(isset($map['addtime'])){
            $map['addtime'][1][0]=strtotime($map['addtime'][1][0]);
            $map['addtime'][1][1]=strtotime($map['addtime'][1][1]);
        }
		// 读取酒店数据
		$data_list = Db::name('hotel')->where($map)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
		$setPageTips='微信端预订地址：<span style="color:#f00000;">'.$_SERVER['HTTP_HOST'].'/booze/booze.php?id=<b style="color:orange;">%d</b></span>'.'，其中%d为酒店的ID。';
        return ZBuilder::make('table')
        	->setPageTitle('酒店列表') // 设置页面标题
        	->setPageTips($setPageTips) // 设置页面提示信息
        	->addOrder('id,addtime') // 添加排序
            ->addTimeFilter('addtime') // 添加时间段筛选
            ->setSearch(['name'=>'酒店名称']) // 设置搜索参数
            ->addFilter('status',['0'=>'正常', '1'=>'已停权']) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
        			['name', '名称', 'link', url('link', ['id' => '__id__'])],
        			['pics', '轮播图','pictures'],
        			['status', '是否停权', 'status', '', ['否', '是']],
        			['addtime', '添加时间', 'datetime', '未知'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['edit','delete']) // 批量添加右侧按钮
    		->addTopButtons(['add','delete','custom'=>['title'=>'无筛选','href'=>url('index')]]) // 批量添加顶部按钮
        	->addRightButton('custom',['title'=>'查看宴会厅','href'=>url('hotel_hall',['id'=>'__ID__']),'icon'=>'fa fa-fw fa-th']) // 添加右侧按钮
        	->addRightButton('custom',['title'=>'查看酒店人员','href'=>url('hotel_users',['id'=>'__ID__']),'icon'=>'fa fa-fw fa-user-secret']) // 添加右侧按钮
        	->addRightButton('custom',['title'=>'查看预订','href'=>url('hotel_book',['id'=>'__ID__']),'icon'=>'fa fa-fw fa-bold']) // 添加右侧按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('hotel') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	//全景链接
	public function link($id=''){
		$hotel = Db::name('hotel')->where('id',$id)->find();
		if($hotel && $hotel['url']!='') $str='<script>location.href="'.$hotel['url'].'";</script>';
        else $str="<script>alert('无该酒店全景链接地址');javascript:window.opener=null;window.open('','_self');window.close();</script>";
		echo $str;
	}
	//添加酒店
	public function add(){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
			    'name|酒店名称'  => 'require|unique:hotel',
			    'pics|酒店图片'  => 'require',
			    'url|酒店全景链接地址'=>'url'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			//数据处理
			$insert=array();
			$insert['name']=$data['name'];
			$insert['info']=$data['info'];
			$insert['url']=$data['url'];
			$insert['pics']=$data['pics'];
			$insert['status']=$data['status'];
			$insert['addtime']=time();
			//数据入库
			$hotel_id=Db::name("hotel")->insertGetId($insert);
			//跳转
			if($hotel_id>0){
				return $this->success('添加酒店成功','index','',1);
	        } else {
	            return $this->error('添加酒店失败');
	        }
		}
		// 使用ZBuilder快速创建表单
		return ZBuilder::make('form')
			->setPageTitle('添加酒店') // 设置页面标题
			->setPageTips('请认真填写相关信息') // 设置页面提示信息
			//->setUrl('add') // 设置表单提交地址
			//->hideBtn(['back']) //隐藏默认按钮
			->setBtnTitle('submit', '确定') //修改默认按钮标题
			->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
			->addText('name', '酒店名称','请最好不要超过25个汉字')
			->addTextarea('info', '酒店介绍','请最好不要超过255个汉字')
			->addText('url', '酒店全景链接地址','如：http://www.baidu.com')
			->addImages('pics','酒店轮播图','图片大小不要超过2MB，上传不要超过10张')
			->addRadio('status', '是否停权','', ['0' => '否', '1' => '是'],'0')
			//->isAjax(false) //默认为ajax的post提交
			->fetch();
	}
	//修改酒店
	public function edit($id='')
	{
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
			    'name|酒店名称'  => 'require|unique:hotel',
			    'pics|酒店图片'  => 'require',
			    'url|酒店全景链接地址'=>'url'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			//数据处理
			$update=array();
			$update['id']=$data['id'];
			$update['name']=$data['name'];
			$update['info']=$data['info'];
			$update['url']=$data['url'];
			$update['pics']=$data['pics'];
			$update['status']=$data['status'];
			//数据更新
			$rt=Db::name("hotel")->update($update);
			//跳转
			if($rt!==false){
				return $this->success('修改酒店成功','index','',1);
	        } else {
	            return $this->error('修改酒店失败');
	        }
		}
		// 接收id
		if ($id>0) {
			// 查处数据
			$hotel=Db::name("hotel")->where('id',$id)->find();
			// 使用ZBuilder快速创建表单
			return ZBuilder::make('form')
				->setPageTitle('修改酒店') // 设置页面标题
				->setPageTips('该操作可能会导致其他的相关数据失效，请勿随意修改信息') // 设置页面提示信息
				//->setUrl('edit') // 设置表单提交地址
				//->hideBtn(['back']) //隐藏默认按钮
				->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
				->addStatic('id', 'ID','唯一标识ID',$hotel['id'])
				->addText('name', '酒店名称','请最好不要超过25个汉字',$hotel['name'])
				->addTextarea('info', '酒店介绍','请最好不要超过255个汉字',$hotel['info'])
				->addText('url', '酒店全景链接地址','如：http://www.baidu.com',$hotel['url'])
				->addImages('pics','酒店轮播图','图片大小不要超过2MB，上传不要超过10张',$hotel['pics'])
				->addRadio('status', '是否停权','', ['0' => '否', '1' => '是'],$hotel['status'])
				->addHidden('id',$hotel['id'])
				//->isAjax(false) //默认为ajax的post提交
				->fetch();
		}
	}
	//查看宴会厅
	public function hotel_hall($id){
		$hotel=db('hotel')->find($id);
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
		$data_list = Db::name('hall a')->join('hotel b','a.hotel_id=b.id','LEFT')->field('a.*,b.id hotel_id')->where($map)->where('a.hotel_id',$id)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('【'.$hotel['name'].'】宴会厅列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('a.id,a.addtime') // 添加排序
            ->addTimeFilter('a.addtime') // 添加时间段筛选
            ->setSearch(['a.name'=>'宴会厅名称']) // 设置搜索参数
            ->addFilter('hall_status',['0'=>'正常', '1'=>'已停权']) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
        			['name', '宴会厅名称', 'link', url('booze/hall/link', ['id' => '__id__'])],
        			['pics', '图片','pictures'],
        			['hall_status', '是否停权', 'status', '', ['否', '是']],
        			['addtime', '添加时间', 'datetime', '未知'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['edit'=>['href'=>url('hall/edit',['id'=>'__ID__'])],'delete'=>['href'=>url('hall/delete',['id'=>'__ID__'])]]) // 批量添加右侧按钮
        	->addRightButton('custom',['title'=>'查看预订','href'=>url('hall/hall_book',['id','__ID__']),'icon'=>'']) // 添加右侧按钮
    		->addTopButtons(['add'=>['href'=>url('hall/add')],'delete'=>['href'=>url('hall/delete')],'custom'=>['title'=>'无筛选','href'=>url('hotel_hall')]]) // 批量添加顶部按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('hall') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	//查看用户
	public function hotel_users($id){
		$hotel=db('hotel')->find($id);
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
		// 读取酒店人员数据
		$data_list = Db::name('users a')->join('hotel b','a.hotel_id=b.id','LEFT')->field('a.*')->where($map)->where('a.hotel_id',$id)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('【'.$hotel['name'].'】酒店人员列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('a.id,a.addtime') // 添加排序
            ->addTimeFilter('a.addtime') // 添加时间段筛选
            ->setSearch(['a.username'=>'账号','a.realname'=>'真实姓名']) // 设置搜索参数
            ->addFilter('user_status',['0'=>'正常', '1'=>'已停权']) // 添加筛选
            ->addFilter('is_special',['0'=>'普通员工', '1'=>'酒店管理员']) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
        			['username', '账号'],
        			['realname', '真实姓名'],
        			['is_special', '类型', 'status', '', ['普通员工', '酒店管理员']],
        			['user_status', '是否停权', 'status', '', ['否', '是']],
        			['addtime', '添加时间', 'datetime', '未知'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['edit'=>['href'=>url('users/edit',['id'=>'__ID__'])],'delete'=>['href'=>url('users/delete',['id'=>'__ID__'])]]) // 批量添加右侧按钮
    		->addTopButtons(['add'=>['href'=>url('users/add')],'delete'=>['href'=>url('users/delete')],'custom'=>['title'=>'无筛选','href'=>url('hotel_users')]]) // 批量添加顶部按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('users') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	//查看预订
	public function hotel_book($id){
		$hotel=db('hotel')->find($id);
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
        $excel_map['b.hotel_id']=$id;
        session('excel_map',$excel_map);
		// 读取预订数据
		$data_list = Db::name('book a')->join('hall b','a.hall_id=b.id','LEFT')->join('hotel c','b.hotel_id=c.id','LEFT')->join('users d','a.user_id=d.id','LEFT')->field('a.*,b.name hall_name,d.username')->where($map)->where('b.hotel_id',$id)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('【'.$hotel['name'].'】预订列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('a.id,a.daytime,a.addtime') // 添加排序
            ->addTimeFilter('a.daytime') // 添加时间段筛选
            ->setSearch(['a.name'=>'预订人姓名','a.phone'=>'预订人手机','b.name'=>'宴会厅名称','d.username'=>'酒店人员账号','d.realname'=>'酒店人员真实姓名']) // 设置搜索参数
            ->addFilter('type',['0'=>'微信端', '1'=>'酒店端']) // 添加筛选
            ->addFilter('chang',['0'=>'午场', '1'=>'夜场']) // 添加筛选
            ->addFilter('genre',['0'=>'其它','1'=>'婚宴','2'=>'生日','3'=>'宵夜','4'=>'会议']) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
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
        	->addRightButtons(['delete'=>['href'=>url('book/delete',['id'=>'__ID__'])]]) // 批量添加右侧按钮
        	
        	->addTopButton('custom',['title'=>'选择导出','class'=>'btn btn-primary js-get','href'=>url('booze/book/excel')])//导出xls按钮
            ->addTopButton('custom',['title'=>'全部导出','href'=>url('booze/book/excelAll')])//导出xls按钮
    		->addTopButtons(['delete'=>['href'=>url('book/delete')],'custom'=>['title'=>'无筛选','href'=>url('hotel_book')]]) // 批量添加顶部按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('book') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
}