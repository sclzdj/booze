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
 * 客服验证器
 * @package app\cms\validate
 * @author 蔡伟明 <460932465@qq.com>
 */
class Support extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|客服名称' => 'require',
        'qq|QQ号码'    => 'number',
        'msn|MSN'     => 'email',
    ];

    // 定义验证场景
    protected $scene = [
        'name' => ['name']
    ];
}
