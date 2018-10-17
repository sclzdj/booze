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
class Announcement extends Validate
{
    //定义验证规则
    protected $rule = [
        'mid|商家'      => 'require|number',
        'title|文字内容' => 'require|max:255',
        'thumb|图片'      => 'require|number',
        'url|外链' => 'url|max:255',
        'view|跳转视图' => 'max:64',
        'click_mode|按钮功能' => 'require|max:64',
        'weight|排序'      => 'require|number',
        'status|状态'     => 'require|number|in:0,1',
    ];

    //定义验证提示
    protected $message = [
//         'name.require' => '请输入标题',
//         'name.max' => '标题最多不能超过50个字符',
//         'mid.require' => 'need param mid',
//         'mid.number' => 'Parameters of mid mistake',
//         'pic.require' => '未选择图片',
//         'pic.number' => 'Parameters of pic mistake',
//         'click_mode' => '按钮功能参数错误',
//         'weight' => '排序参数错误',
//         'click' => '点击数参数错误',
//         'status' => '状态参数错误',
    ];

    //定义验证场景
    protected $scene = [
        'add'  =>  ['title','description','type','status'],
        'edit'  =>  ['title','description','type','status'],
        // 'add'  =>  ['title', 'password' => 'length:6,20', 'mobile', 'role'],
        // 'signin'  =>  ['username' => 'require', 'password' => 'require'],
    ];
}
