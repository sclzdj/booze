<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ ruimeng898 ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017   [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\validate;

use think\Validate;

/**
 * 插件验证器
 * @package app\admin\validate
 * @author 蔡伟明 <460932465@qq.com>
 */
class Plugin extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|插件名称'  => 'require|unique:admin_plugin',
        'title|插件标题' => 'require',
    ];
}
