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

namespace app\admin\validate;

use think\Validate;

/**
 * 用户验证器
 * @package app\admin\validate
 * @author 蔡伟明 <460932465@qq.com>
 */
class LeaderboardAdd extends Validate
{
    //定义验证规则
    protected $rule = [
        'mid|商家'      => 'require|number',
        'name|标题' => 'max:50',
        'pic|图片'      => 'require|number',
        'href|外链' => 'url|max:255',
        'view|视图' => 'max:64',
        'click_mode|按钮功能' => 'require|max:64',
        'weight|排序'      => 'require|number',
        'click|点击数'      => 'require|number',
        'status|状态'     => 'require|number|in:0,1',
    ];

    //定义验证提示
    protected $message = [
    ];

    //定义验证场景
    protected $scene = [
        'add'  =>  ['title','description','type','status'],
        'edit'  =>  ['title','description','type','status'],
        // 'add'  =>  ['title', 'password' => 'length:6,20', 'mobile', 'role'],
        // 'signin'  =>  ['username' => 'require', 'password' => 'require'],
    ];
}
