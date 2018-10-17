<?php
// +----------------------------------------------------------------------
// | TPPHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 [ http://www.ruimeng898.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.ruimeng898.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// 此文件 尽量不去修改
namespace app\common\controller;

use think\Controller;
use think\Request;
use think\Exception;
use think\Db;
use think\Cache;
use think\fn\Result;
use app\api\model\UserToken;
use app\common\model\Users;

/**
 * 前台首页控制器
 *
 * @package app\index\controller
 */
class Apibase extends Controller
{

    private $_appid = '';

    private $_appkey = '';

    protected $_data = array();

    protected $_module = '';

    protected $_controller = '';

    protected $_action = '';

    protected $_actionID = 0;

    private $_userId = 0;

    private $_mid = 0;

    private $_agentId = 0;

    /**
     * 是否效验请求参数sign
     *
     * @var unknown
     */
    protected $_isCheckSign = true;

    /**
     * 未登录状态码
     *
     * @var unknown
     */
    protected $_notLoginCode = 501;

    /**
     *
     * @return the $_userId
     */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * 获取商户ID
     *
     * @return the $_mid
     */
    public function getMid()
    {
        if ($this->getUserId() > 0) {
            if ($this->_mid > 0) {
                return $this->_mid;
            } else {
                // 数据库查询
                $m = Db::name("users_call_account")->where("user_id = ?", [
                    $this->getUserId()
                ])
                    ->field("mid")
                    ->find();
                if (! $m) {
                    return 0;
                } else {
                    $this->setMid($m['mid']);
                    return $m['mid'];
                }
            }
        } else {
            return 0;
        }
    }

    /**
     * 獲得用戶代理ID
     * @return the $_agentId
     */
    public function getAgentId()
    {
        if ($this->getUserId() > 0) {
            if ($this->_agentId > 0) {
                return $this->_agentId;
            } else {
                // 数据库查询
                $m = Db::name("users_call_account")->where("user_id = ?", [
                    $this->getUserId()
                ])
                    ->field("agent_id")
                    ->find();
                if (! $m) {
                    return 0;
                } else {
                    $this->setAgentId($m['agent_id']);
                    return $m['agent_id'];
                }
            }
        } else {
            return 0;
        }
    }

    /**
     *
     * @param number $_userId            
     */
    public function setUserId($_userId)
    {
        $this->_userId = $_userId;
    }

    /**
     *
     * @param number $_mid            
     */
    public function setMid($_mid)
    {
        $this->_mid = $_mid;
    }

    /**
     *
     * @param number $_agentId            
     */
    public function setAgentId($_agentId)
    {
        $this->_agentId = $_agentId;
    }

    /**
     * 限制多少秒内不能重复请求
     *
     * @param string $key            
     * @param int $limitTime            
     * @return Result
     */
    function limitRequestRate($key = '', $limitTime = 30)
    {
        if (! $key)
            $key = $this->request->ip() . $this->request->header("User-Agent");
        $cacheId = md5(sprintf("%s-%s-%s", __CLASS__, __FUNCTION__, $key));
        $data = Cache::get($cacheId, false);
        if ($data === false) {
            Cache::set($cacheId, time()); // 设置上次请求时间
                                              // return new Result(200, [], '');
        } else {
            $lasttime = intval($data);
            $diff = abs($lasttime - time());
            if ($diff < $limitTime) {
                $this->response([], 201, '对不起,请不要在' . $limitTime . '秒内重复请求,请在' . ($limitTime - $diff) . '秒后重试!');
            } else {
                Cache::set($cacheId, time()); // 设置上次请求时间
                                                  // return new Result(200, [], '');
            }
        }
    }

    /**
     *
     * @return the $_isCheckSign
     */
    protected function getIsCheckSign()
    {
        return true;
    }

    /**
     *
     * @param unknown $_isCheckSign            
     */
    public function setIsCheckSign($_isCheckSign)
    {
        $this->_isCheckSign = $_isCheckSign;
    }

    /**
     *
     * @return string
     */
    public function getAppid()
    {
        return intval($this->_appid);
    }

    /**
     *
     * @param string $appid            
     */
    public function setAppid($appid)
    {
        $this->_appid = $appid;
    }

    /**
     *
     * @return string
     */
    public function getAppkey()
    {
        // return config('api_appkey');
        $appid = $this->getAppid();
        $cacheID = "admin_clientkey_" . $appid;
        $row = cache($cacheID);
        if ($row === false) {
            $row = Db::name("admin_clientkey")->where("appid = ?", [
                $this->getAppid()
            ])
                ->find();
            cache($cacheID, json_encode($row), 86400 * 7);
        } else {
            $row = json_decode($row, true);
        }
        return isset($row['appkey']) && $row['appkey'] ? $row['appkey'] : '';
    }

    /**
     *
     * @param string $appkey            
     */
    public function setAppkey($appkey)
    {
        $this->_appkey = $appkey;
    }

    /**
     * 获取请求参数,包含post,get
     *
     * @param
     *            $key
     * @param string $default            
     */
    protected function getRequest($key, $default = '')
    {
        $key = trim($key);
        return isset($this->_data[$key]) ? trim($this->_data[$key]) : $default;
    }

    /**
     * api接口记录日志
     *
     * @param string $msg            
     */
    protected function log($msg)
    {
        $path = RUNTIME_PATH . 'log' . DS . date('Ymd');
        $file = sprintf("%s.%s.%s", $this->_module, $this->_controller, $this->_action);
        $file = strtolower($file);
        try {
            if (is_dir($path)) {
                if (! is_writable($path)) {
                    return false;
                }
            } else {
                @mkdir($path, 0700);
            }
            $file = $path . "/{$file}.log";
            if (! is_file($file))
                @touch($file);
            @file_put_contents($file, date('H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
        } catch (Exception $e) {}
    }

    /**
     * API通用输出
     *
     * @param array|mix $data            
     * @param int|错误码 $errcode            
     * @param string $msg            
     */
    protected function response($data, $errcode, $msg)
    {
        $data = !$data ? [] : $data;
        $data=null2($data);
        $result = [
            'data' => $data,
            'errcode' => intval($errcode),
            'msg' => $msg
        ];
        $result = json_encode($result, JSON_UNESCAPED_UNICODE);
        $this->log("actionID:" . $this->_actionID . ",response:" . $result);
        echo ($result);
        die();
    }

    

    /**
     * 初始化方法
     *
     * @author 蔡伟明 <460932465@qq.com>
     */
    protected function _initialize()
    {
        $data = array_merge($_POST, $_GET);
        $this->_data = &$data;
        $this->_module = Request::instance()->module();
        $this->_controller = Request::instance()->controller();
        $this->_action = Request::instance()->action();
        $this->_actionID = Request::instance()->ip(0) . '-' . microtime(true) * 1000;
        $this->log("actionID:" . $this->_actionID . ",request:" . http_build_query($data) . ',header=' . json_encode(Request::instance()->header()));
        if ($this->getIsCheckSign()) {
            // 参数完整性检查
            $this->checkSign($data);
        }
    }

    /**
     * 限制必须传入指定参数
     */
    protected function checkNeedParam($array)
    {
        if (is_array($array) && count($array)) {
            // 如果设置了必要参数 则检查
            foreach ($array as $k => $v) {
                if (! isset($this->_data[$k])) {
                    $this->response([], 1003, $v ? trim($v) : 'need ' . $k);
                }
            }
        }
    }

    /**
     * 检查API请求的合法性
     */
    protected function checkSign(&$data)
    {
        $code = $this->checkParamValid($data);
        
        if ($code != 0) {
            echo (json_encode([
                'errcode' => 1003,
                'msg' => 'sign error',
                'data' => ""
            ]));
            die();
        }
    }

    /**
     * 请求参数效验
     *
     * @param array $questData
     *            当次请求需要效验的参数键
     */
    protected function checkParamValid($questData)
    {
        if (! isset($questData['sign']) || ! $questData['sign']) {
            return 1001; // 未提交sign
        } else 
            if (! isset($questData['timeline']) || ! $questData['timeline']) {
                return 1002;
            } else 
                if (! isset($questData['token'])) {
                    return 1003;
                } else {
                    $sign = strtolower(trim($questData['sign']));
                    unset($questData['sign']);
                    $time = time();
                    if (! isset($questData['appid']) || ! $questData['appid']) {
                        return 1004; // need appid
                    } else {
                        $this->setAppid($questData['appid']);
                        if (intval($questData['appid']) !== $this->getAppid()) {
                            return 1012; // appid <> header appid
                        } else {
                            
                            $appkey = $this->getAppkey();
                            $makeSign = makeSign($questData, $appkey);
                            if ($makeSign == $sign) {
                                return 0;
                            } else {
                                return 1003;
                            }
                        }
                    }
                }
    }

    /**
     * 判断是否APP登录
     *
     * @param boolean $is_return_userModel
     *            是否返回用户模型，否则返回用户ID
     */
    protected function isAppLogin($is_return_userModel = true)
    {
        $token = $this->getRequest('token', '');
        if (! $token) {
            return new Result(201, [], '没有找到登录令牌!');
        } else {
            $uid = UserToken::getUserId($token);
            if ($uid > 0) {
                $this->setUserId($uid);
                if ($is_return_userModel) {
                    $user = Users::get($uid);
                    if (! $user) {
                        return new Result(203, [], '用户未找到!');
                    } else {
                        return new Result(200, $user, '');
                    }
                } else {
                    return new Result(200, $uid, '');
                }
            } else {
                return new Result(202, [], '登录状态已经失效,请重新登录!');
            }
        }
    }
}
