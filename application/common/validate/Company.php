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

namespace app\common\validate;

use think\Validate;

/**
 * 用户验证器
 * @package app\admin\validate
 */
class Company extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|企业名称' => 'require|unique:company_config',
        'count_stock|初始股份'  => 'require',
        'max1|最高价1'      => 'require',
        'max2|最高价2'      => 'require',
        'max3|最高价3'      => 'require',
        'max4|最高价4'      => 'require',
        'max5|最高价5'      => 'require',
    ];

    //定义验证提示
    protected $message = [
        'name.require' => '请输入企业名称',
        'name.unique' => '已存在该企业',
        'count_stock.require'    => '请输入初始股份',
        'max1.require' => '请输入最高价1',
        'max2.require' => '请输入最高价2',
        'max3.require' => '请输入最高价3',
        'max4.require' => '请输入最高价4',
        'max5.require' => '请输入最高价5',
    ];

    //定义验证场景
    protected $scene = [
        //更新
        'add'  =>  ['name','count_stock','max1','max2','max3','max4','max5'],
        //登录
        'update'  =>  ['name'=>'require','max1','max2','max3','max4','max5'],
    ];
}
