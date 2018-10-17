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
class Pagenew extends Validate
{
    //定义验证规则
    protected $rule = [
        'title|标题' => 'require|max:100',
        'description|描述'  => 'require|max:100',
        'type|类型'      => 'require|number',
        'status|状态'     => 'require|number|in:0,1',
    ];

    //定义验证提示
    protected $message = [
        'title.require' => '请输入标题',
        'title.max' => '标题最多不能超过100个字符',
        'description.require' => '请输入描述文字',
        'description.max' => '描述最多不能超过100个字符',
        'type' => '类型参数错误',
        'status' => '状态参数错误',
    ];

    //定义验证场景
    protected $scene = [
        'add'  =>  ['title','description','type','status'],
        'edit'  =>  ['title','description','type','status'],
        // 'add'  =>  ['title', 'password' => 'length:6,20', 'mobile', 'role'],
        // 'signin'  =>  ['username' => 'require', 'password' => 'require'],
    ];
}
