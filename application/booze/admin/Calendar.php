<?php
namespace app\booze\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder; // 引入ZBuilder
/**
 * Calendar 后台模块
 */
class Calendar extends Admin{
	//万年历管理
	public function index(){
		// 获取排序
        $order = $this->getOrder();
        if($order===''){
            $order='daytime desc,id desc';
        }
        // 获取筛选
        $map = $this->getMap();
        if(isset($map['daytime'])){
            $map['daytime'][1][0]=strtotime($map['daytime'][1][0]);
            $map['daytime'][1][1]=strtotime($map['daytime'][1][1]);
        }
		// 读取万年历数据
		$data_list = Db::name('calendar')->where($map)->order($order)->paginate();
		// 分页数据
		$page = $data_list->render();
		// 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
        	->setPageTitle('万年历列表') // 设置页面标题
        	->setPageTips('修改和删除可能会导致其他的相关数据失效，请谨慎操作') // 设置页面提示信息
        	->addOrder('id,daytime') // 添加排序
            ->addTimeFilter('daytime') // 添加时间段筛选
            ->setSearch(['good'=>'宜','bad'=>'忌']) // 设置搜索参数
            ->addFilter([]) // 添加筛选
        	->addColumns([
        			['id', 'ID'],
        			['daytime', '日期', 'datetime', '未知','Y-m-d'],
        			['good', '宜'],
        			['bad', '忌'],
        			['remark', '备注'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['edit','delete']) // 批量添加右侧按钮
    		->addTopButtons(['add','delete','custom'=>['title'=>'无筛选','href'=>url('index')]]) // 批量添加顶部按钮
        	->setRowList($data_list) // 设置表格数据
        	->setTableName('calendar') // 指定数据表名
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	//添加万年历
	public function add(){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			$data['daytime']=strtotime($data['daytime'].' 00:00:00');
			//数据输入验证
			$validate = new Validate([
			    'daytime|日期'  => 'require|unique:calendar',
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			//数据处理
			$insert=array();
			$insert['daytime']=$data['daytime'];
			$insert['good']=$data['good'];
			$insert['bad']=$data['bad'];
			$insert['remark']=$data['remark'];
			//数据入库
			$calendar_id=Db::name("calendar")->insertGetId($insert);
			//跳转
			if($calendar_id>0){
				return $this->success('添加万年历成功','index','',1);
	        } else {
	            return $this->error('添加万年历失败');
	        }
		}
		// 使用ZBuilder快速创建表单
		return ZBuilder::make('form')
			->setPageTitle('添加万年历') // 设置页面标题
			->setPageTips('请认真填写相关信息') // 设置页面提示信息
			//->setUrl('add') // 设置表单提交地址
			//->hideBtn(['back']) //隐藏默认按钮
			->setBtnTitle('submit', '确定') //修改默认按钮标题
			->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
			->addDate('daytime', '日期','添加过得不能再添加')
			->addTextarea('good', '宜','请最好不要超过255个汉字')
			->addTextarea('bad', '忌','请最好不要超过255个汉字')
			->addTextarea('remark', '备注','请最好不要超过255个汉字')
			//->isAjax(false) //默认为ajax的post提交
			->fetch();
	}
	//修改万年历
	public function edit($id='')
	{
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			$data['daytime']=strtotime($data['daytime'].' 00:00:00');
			//数据输入验证
			$validate = new Validate([
			    'daytime|日期'  => 'require|unique:calendar',
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			//数据处理
			$update=array();
			$update['id']=$data['id'];
			$update['daytime']=$data['daytime'];
			$update['good']=$data['good'];
			$update['bad']=$data['bad'];
			$update['remark']=$data['remark'];
			//数据更新
			$rt=Db::name("calendar")->update($update);
			//跳转
			if($rt!==false){
				return $this->success('修改万年历成功','index','',1);
	        } else {
	            return $this->error('修改万年历失败');
	        }
		}
		// 接收id
		if ($id>0) {
			// 查处数据
			$calendar=Db::name("calendar")->where('id',$id)->find();
			// 使用ZBuilder快速创建表单
			return ZBuilder::make('form')
				->setPageTitle('修改万年历') // 设置页面标题
				->setPageTips('该操作可能会导致其他的相关数据失效，请勿随意修改信息') // 设置页面提示信息
				//->setUrl('edit') // 设置表单提交地址
				//->hideBtn(['back']) //隐藏默认按钮
				->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
				->addStatic('id', 'ID','唯一标识ID',$calendar['id'])
				->addDate('daytime', '日期','添加过得不能再添加',date('Y-m-d',$calendar['daytime']))
				->addTextarea('good', '宜','请最好不要超过255个汉字',$calendar['good'])
				->addTextarea('bad', '忌','请最好不要超过255个汉字',$calendar['bad'])
				->addTextarea('remark', '备注','请最好不要超过255个汉字',$calendar['remark'])
				->addHidden('id',$calendar['id'])
				//->isAjax(false) //默认为ajax的post提交
				->fetch();
		}
	}
}