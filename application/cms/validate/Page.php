<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017   [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\cms\validate;

use think\Validate;

/**
 * 单页验证器
 * @package app\cms\validate
 * @author 蔡伟明 <460932465@qq.com>
 */
class Page extends Validate
{
    // 定义验证规则
    protected $rule = [
        'title|页面标题'  => 'require|length:1,30'
    ];

    // 定义验证场景
    protected $scene = [
        'title' => ['title']
    ];
}
