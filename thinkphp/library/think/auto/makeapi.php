<?php
namespace think\auto;
class makeapi
{
    /**
     * 生成API action请求模型 文件
     * @param 控制器名称 $apiControll
     * @param 方法名 $action
     */
    public static function action($apiControll,$action)
    {
        $apiControll = strtolower($apiControll);
        $action = strtolower($action);
        if(!ctype_alnum($apiControll) || !ctype_alnum($action))
        {
            exit('请输入安全字符,只能包含数字+字母');
        }
        $controller_name = ucfirst($apiControll);
        $action_name = sprintf("%s.%s.php",$apiControll,$action);
        $controller_file = APP_PATH.DS.'api'.DS.'controller'.DS.$controller_name.'.php';
        $action_file = APP_PATH.DS.'api'.DS.'action'.DS.$action_name;
        if(!file_exists($controller_file))
        {
            $template = '<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 成都锐萌软件开发有限公司 [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\api\controller;

/**
 * API接口控制器
 * @package app\api\controller
 */
class {$classname} extends Baseaction
{
}';
            $template = str_replace('{$classname}', $controller_name, $template);
            file_put_contents($controller_file, $template);
            echo "控制器{$controller_file}文件创建成功<hr>";
        }else{
            echo "控制器{$controller_file}文件已经存在<hr>";
        }
        if(!file_exists($action_file))
        {
            $template = '<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 成都锐萌软件开发有限公司 [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\api\controller;

/**
 * API action控制器
 * @package app\api\controller
 */
class {$controller}{$action} extends Baseaction
{
    function run()
    {
      //输入你的代码
    }
}';
            $template = str_replace('{$controller}', $apiControll  , $template);
            $template = str_replace('{$action}', $action  , $template);
            
            file_put_contents($action_file, $template);
            echo "控制器方法{$action_file}文件创建成功<hr>";
        }else{
            echo "控制器方法{$action_file}文件已经存在<hr>";
        }
        echo "自动创建API方法成功,请求路径="."/api/{$apiControll}/{$action}<hr>";
    }
}