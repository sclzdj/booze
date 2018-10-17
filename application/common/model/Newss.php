<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 成都锐萌软件开发有限公司 [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\common\model;
use think\Model;
use think\Db;
use think\fn\Result;
/**
 * 公共模型
 * @package app\common\model
 */
class Newss extends Model
{
    //列表
    public static function lst($class_id,$company_id,$offset,$pageSize)
    {
        $where=[];
        if($class_id>0){
            $where=['a.class_id'=>$class_id];
        }
        if($company_id>0){
            $where=['a.company_id'=>$company_id];
        }
    	$list=Db::name('news')->alias('a')->join('news_class b','a.class_id=b.id','LEFT')->join('company_config c','a.company_id=c.id','LEFT')->field('a.id,a.title,a.is_groom,a.class_id,b.name as class_name,a.company_id,c.name as company_name,a.addtime')->where($where)->order('addtime DESC')->limit($offset,$pageSize)->select();
        foreach ($list as $k => $v) {
            $list[$k]['url']='index/index/appNews/news_id/'.$v['id'];
        }
    	if($list!==false){
            return new Result(200, $list, '');
        }else{
            return new Result(201, [], '数据获取失败!');
        }
    }
    //详情
    public static function info($news_id)
    {
    	$news=Db::name('news')->alias('a')->join('news_class b','a.class_id=b.id','LEFT')->join('company_config c','a.company_id=c.id','LEFT')->field('a.id,a.title,a.is_groom,a.content,a.class_id,b.name as class_name,a.company_id,c.name as company_name,a.addtime')->order('addtime DESC')->find($news_id);
        $news['url']='index/index/appNews/news_id/'.$news_id;
    	if($news!==false){
            return new Result(200, $news, '');
        }else{
            return new Result(201, [], '数据获取失败!');
        }
    }
    //列表
    public static function classList()
    {
        $list=Db::name('news_class')->order('sort')->select();
        foreach ($list as $k => $v) {
            $list[$k]['img']=intval($v['img'])>0 ? model('admin/attachment')->getFilePath($v['img']) : $v['img'];
        }
        if($list!==false){
            return new Result(200, $list, '');
        }else{
            return new Result(201, [], '数据获取失败!');
        }
    }
}