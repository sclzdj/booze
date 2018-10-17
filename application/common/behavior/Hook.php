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

namespace app\common\behavior;

use app\admin\model\Hook as HookModel;
use app\admin\model\HookPlugin as HookPluginModel;
use app\admin\model\Plugin as PluginModel;

/**
 * 注册钩子
 * @package app\common\behavior
 * @author 蔡伟明 <460932465@qq.com>
 */
class Hook
{
    /**
     * 执行行为 run方法是Behavior唯一的接口
     * @access public
     * @param mixed $params  行为参数
     * @return void
     */
    public function run(&$params)
    {
        if(defined('BIND_MODULE') && BIND_MODULE === 'install') return;

        if(isset($params['module']) && isset($params['module'][0]))
        {
            $module = $params['module'][0];
            //设置API接口的模块，禁止请求api模块时 触发扣子 插件等
            $api_controllers = config("api_controllers");
            if(is_array($api_controllers) && in_array($module, $api_controllers))
            {
                return;
            }
        }

        $hook_plugins = cache('hook_plugins');
        $hooks        = cache('hooks');
        $plugins      = cache('plugins');

        if (!$hook_plugins) {
            // 所有钩子
            $hooks = HookModel::where('status', 1)->column('status', 'name');
            // 所有插件
            $plugins = PluginModel::where('status', 1)->column('status', 'name');
            // 钩子对应的插件
            $hook_plugins = HookPluginModel::where('status', 1)->order('hook,sort')->select();
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('hook_plugins', $hook_plugins);
                cache('hooks', $hooks);
                cache('plugins', $plugins);
            }
        }

        if ($hook_plugins) {
            foreach ($hook_plugins as $value) {
                if (isset($hooks[$value->hook]) && isset($plugins[$value->plugin])) {
                    \think\Hook::add($value->hook, get_plugin_class($value->plugin));
                }
            }
        }
    }
}
