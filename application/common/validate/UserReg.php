<?php
namespace app\common\validate;

use think\Validate;

class UserReg extends Validate
{
    protected $rule = [
        'username' => 'require|max:25|length:11|regex:\d{11}',
        'password' => 'require|length:6,16',
        'payment_password' => 'require|length:6,16',
    ];
    protected $message = [
        'username.require' => '手机号码必须输入',
        'username.length'  => '手机号码必须为11位',
        'username.regex'   => '手机号码格式不正确',
        'password.require' => '密码必须输入',
        'password.length' => '密码长度只能为6-16位',
        'payment_password.require' => '支付密码必须输入',
        'payment_password.length' => '支付密码长度只能为6-16位',
    ];
    protected $scene = [
        'register' => ['username','password'],
        'checkname' => ['username'],
        'getPassword' => ['password'],
        'update_payment_password'=>['payment_password'],
    ];
}