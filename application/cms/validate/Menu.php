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
 * 菜单验证器
 * @package app\cms\validate
 * @author 蔡伟明 <460932465@qq.com>
 */
class Menu extends Validate
{
    // 定义验证规则
    protected $rule = [
        'column|栏目'  => 'requireIf:type,0',
        'page|单页'    => 'requireIf:type,1',
        'title|菜单标题' => 'requireIf:type,2|length:1,30|unique:cms_nav',
        'url|URL'     => 'requireIf:type,2',
    ];

    // 定义验证提示
    protected $message = [
        'column'        => '请选择栏目',
        'page'          => '请选择单页',
        'title'         => '菜单标题不能为空',
        'url.requireIf' => 'URL不能为空'
    ];

    // 定义验证场景
    protected $scene = [
        'title' => ['title']
    ];
}
