<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ ruimeng898 ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 成都锐萌软件开发有限公司 [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\controller;
use app\common\model\SiteArticle as SiteArticleModel;
use app\common\builder\ZBuilder;
use think\Cache;
use think\Db;
/**
 * 节点管理
 * @package app\admin\controller
 */
class News extends Admin{
	public function index(){
    // 获取排序
    $order = $this->getOrder();
    // 获取筛选
    $map = $this->getMap();
    $data = SiteArticleModel::name('site_article')->where(['agent_id'=>UID])->where($map)->order($order)->select();
    return ZBuilder::make('table')
      ->setPageTitle('新闻管理') // 设置页面标题
      ->setTableName('site_article') // 设置数据表名
      ->setSearch(['title' => '标题','author'=>'作者','source'=>'新闻来源']) // 设置搜索参数
      ->addOrder('id,weight,created') // 添加排序
      ->addColumns([
          ['title', '标题'],
          // ['description', '描述'],
          ['type', '类型','select',SiteArticleModel::getTypeTree()],
          ['source', '来源'],
          ['author', '作者'],
          ['linkurl', '外链'],
          ['thumb', '缩略图','picture'],
          ['weight', '排序'],
          ['ishot', '热门','switch'],
          ['iscommend', '推荐','switch'],
          ['status', '状态','switch'],
          ['created', '创建时间','datetime'],
          ['right_button', '操作', 'btn']
      ])
      ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
      // ->addRightButton('custom', $btn_access) // 添加授权按钮
      ->addRightButtons('edit,delete') // 批量添加右侧按钮
      ->addTimeFilter('created') // 添加时间段筛选
      ->addFilter('ishot,iscommend,status,type') // 添加筛选
      ->setRowList($data) // 设置表格数据
      // ->setPages($page) // 设置分页数据
      ->fetch(); // 渲染页面
	}
  public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'News');
            // 验证失败 输出错误信息
            if(true !== $result) return $this->error($result);
            $data['agent_id'] = UID;
            if ($user = SiteArticleModel::create($data)) {
                // 记录行为
                // action_log('user_add', 'admin_user', $user['id'], UID);
                return $this->success('新增成功', url('index'));
            } else {
                return $this->error('新增失败');
            }
        }
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'title', '标题', '必填，100字符以内'],
                ['text', 'description', '描述', '必填，100字符以内'],
                ['select', 'type', '类型', '', SiteArticleModel::getTypeTree()],
                ['text', 'source', '来源', '64字符以内'],
                ['text', 'author', '作者', '64字符以内'],
                ['text', 'linkurl', '外链', '255字符以内,例如 http://www.baidu.com'],
            ])
            ->addImage('thumb', '缩略图')
            ->addFormItems([ // 批量添加表单项
                ['number', 'weight', '排序', '5字符以内'],
                ['radio', 'ishot', '热门', '', ['禁用','启用'], 0],
                ['radio', 'iscommend', '推荐', '', ['禁用','启用'], 0],
                ['radio', 'status', '状态', '', ['禁用','启用'], 1],
            ])
            ->addUeditor('content', '内容')
            ->fetch();
    }
    //编辑新闻
  public function edit($id = null)
    {
        if ($id === null) return $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'News');
            if(true !== $result) return $this->error($result);
            if ($user = SiteArticleModel::where('id',$id)->update($data)) {
                // 记录行为
                return $this->success('编辑成功', url('index'));
            } else {
                return $this->error('编辑失败');
            }
        }
        $info = SiteArticleModel::where('id', $id)->find();
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'title', '标题', '必填，100字符以内'],
                ['text', 'description', '描述', '必填，100字符以内'],
                ['select', 'type', '类型', '', SiteArticleModel::getTypeTree()],
                ['text', 'source', '来源', '64字符以内'],
                ['text', 'author', '作者', '64字符以内'],
                ['text', 'linkurl', '外链', '255字符以内,例如 http://www.baidu.com'],
            ])
            ->addImage('thumb', '缩略图')
            // ->addFile('thumb', '缩略图')
            ->addFormItems([ // 批量添加表单项
                ['number', 'weight', '排序', '5字符以内'],
                ['radio', 'ishot', '热门', '', ['禁用','启用'], 0],
                ['radio', 'iscommend', '推荐', '', ['禁用','启用'], 0],
                ['radio', 'status', '状态', '', ['禁用','启用'], 1],
            ])
            ->addUeditor('html', '内容')
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }
    // //删除新闻
    // public function delete($id = [])
    // {
    //     dump($id);die;
    //     $del = User::destroy(['id' => $id]);
    //     dump($del);die;
    //     return $this->success('delete');
    // }


}