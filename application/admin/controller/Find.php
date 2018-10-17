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
use app\common\model\FindCarousel as FindCarouselModel;
use app\common\model\FindTextNotification as FindTextNotificationModel;
use app\common\model\FindButtons as FindButtonsModel;
use app\common\model\FindLeaderboard as FindLeaderboardModel;
use app\common\model\MerchantUser as MerchantUserModel;
use think\Db;
use \think\Request;
use app\common\builder\ZBuilder;
// use think\Cache;

/**
 * 节点管理
 * @package app\admin\controller
 */
class Find extends Admin{
    // 首页
    public function index(){
        $data = [];
        $data['findCarousel'] = Db::table('v2_find_carousel')
            ->alias('a')
            ->join('v2_admin_attachment b','a.pic = b.id','LEFT')
            ->where(['a.agent_id' => UID,'a.status'=>1])
            ->order('a.weight', 'asc')
            ->field('a.*,b.path')
            ->select();
        $data['findTextNotification'] = Db::table('v2_find_text_notification')
            ->alias('a')
            ->join('v2_admin_attachment b','a.thumb = b.id','LEFT')
            ->where(['a.agent_id' => UID,'a.status'=>1])
            ->order('a.weight', 'asc')
            ->field('a.*,b.path')
            ->select();
        $data['FindButtons'] = Db::table('v2_find_buttons')
            ->alias('a')
            ->join('v2_admin_attachment b','a.pic = b.id','LEFT')
            ->where(['a.agent_id' => UID,'a.status'=>1])
            ->order('a.weight', 'asc')
            ->field('a.*,b.path')
            ->select();
        $data['FindLeaderboard'] = Db::table('v2_find_leaderboard')
            ->alias('a')
            ->join('v2_admin_attachment b','a.pic = b.id','LEFT')
            ->where(['a.agent_id' => UID,'a.status'=>1])
            ->order('a.weight', 'asc')
            ->field('a.*,b.path')
            ->select();
        $html = $this->getFetch('index',$data);  
        return ZBuilder::make('table')
            ->css(['swiper.min','find','jquery.gridly'])
            ->setExtraHtml($html)
            ->fetch();
    }
    // 轮播图新增
    public function carouselAdd()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'CarouselAdd');
            if(true !== $result) return $this->error($result);
            $data['agent_id'] = UID;
            if ($user = FindCarouselModel::create($data)) {
                return $this->success('新增成功');
            } else {
                return $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->setPageTitle('新增')
            ->addFormItems([
                ['text', 'name', '标题', '必填，50字符以内'],
                ['select', 'mid', '商家', '', MerchantUserModel::getShopTree(),0]
            ])
            ->addImage('pic', '图片')
            ->addFormItems([
                ['select', 'click_mode', '按钮功能', '', FindCarouselModel::getButtonTree(),'url'],
                ['text', 'href', '链接','255个字符以内'],
                ['text', 'view', '跳转到客户端视图', '64个字符以内'],
            ])
            ->addNumber('click', '点击数')
            ->addNumber('weight', '排序')
            ->addFormItems([
                ['radio', 'status', '状态', '', ['禁用','启用'], 1],
            ])
            ->js('changeClickMode')
            ->fetch('',[],[],[],false,'layoutClear');
    }
    // 轮播图编辑
    public function carouselEdit($id = null){
        if ($id === null) return $this->error('缺少参数');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'CarouselAdd');
            if(true !== $result) return $this->error($result);
            if ($user = FindCarouselModel::where('id',$id)->update($data)) {
                return $this->success('编辑成功');
            } else {
                return $this->error('编辑失败');
            }
        }
        $info = FindCarouselModel::where('id', $id)->find();
        return ZBuilder::make('form')
            ->setPageTitle('编辑')
            ->addFormItems([
                ['text', 'name', '标题', '必填，50字符以内'],
                ['select', 'mid', '商家', '', MerchantUserModel::getShopTree(),0],
            ])
            ->addImage('pic', '图片')
            ->addFormItems([
                ['select', 'click_mode', '按钮功能', '', FindCarouselModel::getButtonTree(),'url'],
                ['text', 'view', '跳转到客户端视图', '64个字符以内'],
                ['text', 'href', '链接','255个字符以内'],
            ])
            ->addNumber('click', '点击数')
            ->addNumber('weight', '排序')
            ->addFormItems([
                ['radio', 'status', '状态', '', ['禁用','启用'], 1],
            ])
            ->setFormData($info)
            ->js('changeClickMode')
            ->fetch('',[],[],[],false,'layoutClear');
    }
    // 轮播图删除
    public function carouselDelete($id = null){
        if ($id === null) return $this->error('缺少参数');
        if(Db::table('v2_find_carousel')->delete($id)){
            exit(json_encode(['200','删除成功']));
        }else{
            exit(json_encode(['201','删除失败']));
        }
    }
    // 按钮组添加
    public function buttonsAdd(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'ButtonsAdd');
            if(true !== $result) return $this->error($result);
            $data['agent_id'] = UID;
            if ($user = FindButtonsModel::create($data)) {
                return $this->success('新增成功');
            } else {
                return $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->setPageTitle('新增')
            ->addFormItems([
                ['select', 'mid', '商家', '', MerchantUserModel::getShopTree(),0],
                ['text', 'corner', '角标', '必填，4个字符以内'],
                ['text', 'label', '标题', '必填，32个字符以内'],
                ['text', 'vice_label', '副标题', '32个字符以内'],
            ])
            ->addImage('pic', '图片')
            ->addFormItems([
                ['select', 'click_mode', '按钮功能', '', FindButtonsModel::getButtonTree(),'url'],
                ['text', 'href', '链接','255个字符以内'],
                ['text', 'view', '跳转到客户端视图', '64个字符以内'],
            ])
            ->addNumber('click', '点击数')
            ->addNumber('weight', '排序')
            ->addFormItems([
                ['radio', 'status', '状态', '', ['禁用','启用'], 1],
            ])
            ->js('changeClickMode')
            ->fetch('',[],[],[],false,'layoutClear');
    }
    // 按钮组编辑
    public function buttonsEdit($id = null){
        if ($id === null) return $this->error('缺少参数');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'ButtonsAdd');
            if(true !== $result) return $this->error($result);
            if ($user = FindButtonsModel::where('id',$id)->update($data)) {
                return $this->success('编辑成功');
            } else {
                return $this->error('编辑失败');
            }
        }
        $info = FindButtonsModel::where('id', $id)->find();
        return ZBuilder::make('form')
            ->setPageTitle('编辑')
           ->addFormItems([
                ['select', 'mid', '商家', '', MerchantUserModel::getShopTree(),0],
                ['text', 'corner', '角标', '最多2个汉字'],
                ['text', 'label', '标题', '必填，32个字符以内'],
                ['text', 'vice_label', '副标题', '32个字符以内'],
            ])
            ->addImage('pic', '图片')
            ->addFormItems([
                ['select', 'click_mode', '按钮功能', '', FindButtonsModel::getButtonTree(),'url'],
                ['text', 'href', '链接','255个字符以内'],
                ['text', 'view', '跳转到客户端视图', '64个字符以内'],
            ])
            ->addNumber('click', '点击数')
            ->addNumber('weight', '排序')
            ->addFormItems([
                ['radio', 'status', '状态', '', ['禁用','启用'], 1],
            ])
            ->setFormData($info)
            ->js('changeClickMode')
            ->fetch('',[],[],[],false,'layoutClear');
    }
    // 按钮组删除
    public function buttonsDelete($id = null){
        if ($id === null) return $this->error('缺少参数');
        if(Db::table('v2_find_buttons')->delete($id)){
            exit(json_encode(['200','删除成功']));
        }else{
            exit(json_encode(['201','删除失败']));
        }
    }
    // 通栏广告添加
    public function leaderboardAdd(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'LeaderboardAdd');
            if(true !== $result) return $this->error($result);
            $data['agent_id'] = UID;
            if ($user = FindLeaderboardModel::create($data)) {
                return $this->success('新增成功');
            } else {
                return $this->error('新增失败');
            }
        }
        return ZBuilder::make('form')
            ->setPageTitle('新增')
            ->addFormItems([
                ['select', 'mid', '商家', '', MerchantUserModel::getShopTree(),0],
                ['text', 'name', '标题', '必填，50个字符以内'],
            ])
            ->addImage('pic', '图片')
            ->addFormItems([
                ['select', 'click_mode', '按钮功能', '', FindLeaderboardModel::getButtonTree(),'url'],
                ['text', 'href', '链接','255个字符以内'],
                ['text', 'view', '跳转到客户端视图', '64个字符以内'],
            ])
            ->addNumber('click', '点击数')
            ->addNumber('weight', '排序')
            ->addFormItems([
                ['radio', 'status', '状态', '', ['禁用','启用'], 1],
            ])
            ->js('changeClickMode')
            ->fetch('',[],[],[],false,'layoutClear');
    }
    // 通栏广告编辑
    public function leaderboardEdit($id = null){
        if ($id === null) return $this->error('缺少参数');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'LeaderboardAdd');
            if(true !== $result) return $this->error($result);
            if ($user = FindLeaderboardModel::where('id',$id)->update($data)) {
                return $this->success('编辑成功');
            } else {
                return $this->error('编辑失败');
            }
        }
        $info = FindLeaderboardModel::where('id', $id)->find();
        return ZBuilder::make('form')
            ->setPageTitle('编辑')
            ->addFormItems([
                ['select', 'mid', '商家', '', MerchantUserModel::getShopTree(),0],
                ['text', 'name', '标题', '必填，50个字符以内'],
            ])
            ->addImage('pic', '图片')
            ->addFormItems([
                ['select', 'click_mode', '按钮功能', '', FindLeaderboardModel::getButtonTree(),'url'],
                ['text', 'href', '链接','255个字符以内'],
                ['text', 'view', '跳转到客户端视图', '64个字符以内'],
            ])
            ->addNumber('click', '点击数')
            ->addNumber('weight', '排序')
            ->addFormItems([
                ['radio', 'status', '状态', '', ['禁用','启用'], 1],
            ])
            ->setFormData($info)
            ->js('changeClickMode')
            ->fetch('',[],[],[],false,'layoutClear');
    }
    // 通栏广告删除
    public function leaderboardDelete($id = null){
        if ($id === null) return $this->error('缺少参数');
        if(Db::table('v2_find_leaderboard')->delete($id)){
            exit(json_encode(['200','删除成功']));
        }else{
            exit(json_encode(['201','删除失败']));
        }
    }
    // 修改排序（轮播图、模块、通栏广告）
    public function editWeight($type = null){
        if ($type === null) exit(json_encode(['201','缺少参数:type']));
        if(!Request::instance()->has('data','post')) exit(json_encode(['201','缺少参数:data']));
        $param = input('post.')['data'];
        switch ($type) {
            case 'carousel':
                $table = 'v2_find_carousel';
                break;
            case 'buttons':
                $table = 'v2_find_buttons';
                break;
            case 'leaderboard':
                $table = 'v2_find_leaderboard';
                break;
            default:
                return $this->error('参数错误:type');
                break;
        }
        $sql = "UPDATE $table SET weight = CASE id";
        foreach ($param as $k => $v) {
            $sql .= " WHEN $v THEN $k";
        }
        $sql .= ' END WHERE id IN ('.implode(',',$param).')';
        Db::query($sql);
        exit(json_encode(['200','操作成功']));
    }
}