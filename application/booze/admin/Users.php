<?php
namespace app\booze\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder; // 引入ZBuilder
/**
 * Users 后台模块
 */
class Users extends Admin{
	//酒店人员管理
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
		// 读取酒店人员数据
		$data_list = Db::name('users a')->join('hotel b','a.hotel_id=b.id','LEFT')->field('a.*,b.id hotel_id,b.name hotel_name')->where($map)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('酒店人员列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('a.id,a.addtime') // 添加排序
            ->addTimeFilter('a.addtime') // 添加时间段筛选
            ->setSearch(['a.username'=>'账号','a.realname'=>'真实姓名','b.name'=>'酒店名称']) // 设置搜索参数
            ->addFilter('user_status',['0'=>'正常', '1'=>'已停权']) // 添加筛选
            ->addFilter('is_special',['0'=>'普通员工', '1'=>'酒店管理员']) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
        			['hotel_name', '酒店名称', 'link', url('booze/hotel/link', ['id' => '__hotel_id__'])],
        			['username', '账号'],
        			['realname', '真实姓名'],
        			['is_special', '类型', 'status', '', ['普通员工', '酒店管理员']],
        			['user_status', '是否停权', 'status', '', ['否', '是']],
        			['addtime', '添加时间', 'datetime', '未知'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['edit','delete']) // 批量添加右侧按钮
    		->addTopButtons(['add','delete','custom'=>['title'=>'无筛选','href'=>url('index')]]) // 批量添加顶部按钮
        	->addRightButton('custom',['title'=>'查看预订','href'=>url('users_book',['id'=>'__ID__']),'icon'=>'fa fa-fw fa-bold']) // 添加右侧按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('users') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	//添加酒店人员
	public function add(){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$data['username']=makeUsername();
			$validate = new Validate([
				'hotel_id|所属酒店'  => 'require',
			    'realname|真实姓名'  => 'require'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			if(!preg_match('/^\w{6,18}$/', $_POST['password'])){
				return $this->error('必须输入密码，密码长度在小于6~~18之间且只能是数字、字母和下划线');
			}
			//数据处理
			$insert=array();
			$insert['hotel_id']=$data['hotel_id'];
			$insert['username']=$data['username'];
			$insert['realname']=$data['realname'];
			$insert['password']=md5(md5($_POST['password']));
			$insert['is_special']=$data['is_special'];
			$insert['user_status']=$data['user_status'];
			$insert['addtime']=time();
			//数据入库
			$users_id=Db::name("users")->insertGetId($insert);
			//跳转
			if($users_id>0){
				return $this->success('添加酒店人员成功','index','',5);
	        } else {
	            return $this->error('添加酒店人员失败');
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
			->setPageTitle('添加酒店人员') // 设置页面标题
			->setPageTips('请认真填写相关信息') // 设置页面提示信息
			//->setUrl('add') // 设置表单提交地址
			//->hideBtn(['back']) //隐藏默认按钮
			->setBtnTitle('submit', '确定') //修改默认按钮标题
			->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
			->addSelect('hotel_id', '所属酒店', '必须选择', $select_hotels)
			->addText('realname', '酒店人员真实姓名','必须填写,请最好不要超过10个汉字')
			->addPassword('password', '密码','必须输入密码，密码长度在小于6~~18之间且只能是数字、字母和下划线')
			->addRadio('is_special', '类型', '酒店管理员权限高于普通员工', ['0' => '普通员工', '1' => '酒店管理员'],'0')
			->addRadio('user_status', '是否停权','', ['0' => '否', '1' => '是'],'0')
			//->isAjax(false) //默认为ajax的post提交
			->fetch();
	}
	//修改酒店人员
	public function edit($id='')
	{
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
				'hotel_id|所属酒店'  => 'require',
			    'realname|真实姓名'  => 'require'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			if($_POST['password']!==''){
				if(!preg_match('/^\w{6,18}$/', $_POST['password'])){
					return $this->error('密码长度在小于6~~18之间且只能是数字、字母和下划线');
				}
				$data['password']=md5(md5($_POST['password']));
			}else{
				$data['password']=db('users')->where('id',$data['id'])->value('password');
			}
			//数据处理
			$update=array();
			$update['id']=$data['id'];
			$update['hotel_id']=$data['hotel_id'];
			$update['realname']=$data['realname'];
			$update['password']=$data['password'];
			$update['is_special']=$data['is_special'];
			$update['user_status']=$data['user_status'];
			//数据更新
			$rt=Db::name("users")->update($update);
			//跳转
			if($rt!==false){
				return $this->success('修改酒店人员成功','index','',1);
	        } else {
	            return $this->error('修改酒店人员失败');
	        }
		}
		// 接收id
		if ($id>0) {
			// 查处数据
			$users=Db::name("users")->where('id',$id)->find();
			//选择酒店下拉框数据
			$hotels=db('hotel')->field('id,name')->order('addtime desc,id desc')->select();
			$select_hotels=array();
			foreach ($hotels as $k => $v) {
				$select_hotels[$v['id']]=$v['name'];
			}
			// 使用ZBuilder快速创建表单
			return ZBuilder::make('form')
				->setPageTitle('修改酒店人员') // 设置页面标题
				->setPageTips('该操作可能会导致其他的相关数据失效，请勿随意修改信息') // 设置页面提示信息
				//->setUrl('edit') // 设置表单提交地址
				//->hideBtn(['back']) //隐藏默认按钮
				->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
				->addStatic('id', 'ID','唯一标识ID',$users['id'])
				->addStatic('username', '账号','系统自动生成的账号，不能修改',$users['username'])
				->addSelect('hotel_id', '所属酒店', '必须选择', $select_hotels,$users['hotel_id'])
				->addText('realname', '酒店人员真实姓名','必须填写,请最好不要超过10个汉字',$users['realname'])
				->addPassword('password', '密码','不输入则不修改密码，密码长度在小于6~~18之间且只能是数字、字母和下划线')
				->addRadio('is_special', '类型', '酒店管理员权限高于普通员工', ['0' => '普通员工', '1' => '酒店管理员'],$users['is_special'])
				->addRadio('user_status', '是否停权','', ['0' => '否', '1' => '是'],$users['user_status'])
				->addHidden('id',$users['id'])
				//->isAjax(false) //默认为ajax的post提交
				->fetch();
		}
	}
	//查看预订
	public function users_book($id){
		$user=db('users a')->join('hotel b','a.hotel_id=b.id','LEFT')->field('a.username,b.name hotel_name')->where('a.id',$id)->find();
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
        $excel_map['a.user_id']=$id;
        session('excel_map',$excel_map);
		// 读取预订数据
		$data_list = Db::name('book a')->join('hall b','a.hall_id=b.id','LEFT')->join('hotel c','b.hotel_id=c.id','LEFT')->join('users d','a.user_id=d.id','LEFT')->field('a.*,b.name hall_name')->where($map)->where('a.user_id',$id)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('【'.$user['hotel_name'].'-'.$user['username'].'】预订列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('a.id,a.daytime,a.addtime') // 添加排序
            ->addTimeFilter('a.daytime') // 添加时间段筛选
            ->setSearch(['a.name'=>'预订人姓名','a.phone'=>'预订人手机','b.name'=>'宴会厅名称']) // 设置搜索参数
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
        			['addtime', '申请时间', 'datetime', '未知'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['delete'=>['href'=>url('users/delete',['id'=>'__ID__'])]]) // 批量添加右侧按钮
        	->addTopButton('custom',['title'=>'选择导出','class'=>'btn btn-primary js-get','href'=>url('booze/book/excel')])//导出xls按钮
            ->addTopButton('custom',['title'=>'全部导出','href'=>url('booze/book/excelAll')])//导出xls按钮
    		->addTopButtons(['delete'=>['href'=>url('users/delete')],'custom'=>['title'=>'无筛选','href'=>url('users_book')]]) // 批量添加顶部按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('book') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
}