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

namespace app\user\validate;

use think\Validate;

/**
 * 角色验证器
 * @package app\admin\validate
 * @author 蔡伟明 <460932465@qq.com>
 */
class Role extends Validate
{
    //定义验证规则
    protected $rule = [
        'pid|所属角色'   => 'require',
        'name|角色名称' => 'require|unique:admin_role',
    ];
}