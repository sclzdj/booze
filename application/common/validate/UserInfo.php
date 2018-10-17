<?php
namespace app\common\validate;

use think\Validate;

class UserInfo extends Validate
{
    protected $rule = [
        'realname' => 'require',
        'nickname' => 'length:1,24',
        'bank_id' => 'require',
        'bank_open_address' => 'require',
        'bank_id_number' => 'regex:[\d]{16,19}',
    ];
    protected $message = [
        'realname.require'  => '必须输入姓名',
        'nickname.length' => '昵称长度过长',
        'bank_id.require'  => '必须选择银行',
        'bank_open_address.require'   => '必须输入开户行地址',
        'bank_id_number.regex' => '银行卡号格式不正确',
    ];
}