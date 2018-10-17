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

/**
 * 公共模型
 * @package app\common\model
 */
class AdminClientkey extends Model
{
    /**
     * 根据appid获取代理ID @todo 需要走缓存
     * @param int $appid
     */
    public static function getAgentId($appid){
        $appid = intval($appid);
        $cacheId = md5(sprintf("%s-%s-%d",__CLASS__,__FUNCTION__,$appid));
        $row = self::where("appid = ?",[$appid])->cache($cacheId,86400*365)->find();
        return isset($row['agent_id']) ? intval($row['agent_id']) : 0;
    }
}