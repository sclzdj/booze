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
 * 行为验证器
 * @package app\admin\validate
 * @author 蔡伟明 <460932465@qq.com>
 */
class Merchantuser extends Validate
{
    //定义验证规则
    protected $rule = [
        'username' => 'require|unique:merchant_user|regex:[a-zA-Z0-9]{4,15}',
        'password'   => 'regex:[a-zA-Z0-9_-]{6,20}',
        'email'=>'email',
        'mobile'=>'regex:^1[3578]\d{9}'
    ];

    //定义验证提示
    protected $message = [
        'username.require' => '用户名不能为空',
        'username.regex' => '用户名为4-15位字母+数字',
        'username.unique' => '用户名已经存在',
        'password'=>'密码为6-20数字字母下划线',
        'mobile'=>'手机号码格式错误',
    ];
}
