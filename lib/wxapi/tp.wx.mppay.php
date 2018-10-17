<?php

/**
 * @author Administrator
 *
 */
class tpwxmppay
{

    /**
     * 公众号|应用 APPID
     *
     * @var appid
     */
    private $_appid = '';

    /**
     * 商户ID
     *
     * @var string
     */
    private $_mchid = '';

    /**
     * 微信支付密钥
     *
     * @var string
     */
    private $_wxkey = '';

    /**
     * 公众号|应用 SECRET
     *
     * @var string
     */
    private $_appsecret = '';

    private $_cert_file = '';

    private $_key_file = '';

    /**
     *
     * @return the $_cert_file
     */
    public function getCert_file()
    {
        return $this->_cert_file;
    }

    /**
     *
     * @return the $_key_file
     */
    public function getKey_file()
    {
        return $this->_key_file;
    }

    /**
     *
     * @param string $_cert_file            
     */
    public function setCert_file($_cert_file)
    {
        $this->_cert_file = trim($_cert_file);
        \WxPayConfig::$SSLCERT_PATH = $this->_cert_file;
    }

    /**
     *
     * @param string $_key_file            
     */
    public function setKey_file($_key_file)
    {
        $this->_key_file = trim($_key_file);
        \WxPayConfig::$SSLKEY_PATH = $this->_key_file;
    }

    /**
     *
     * @return the $_appid
     */
    public function getAppid()
    {
        return $this->_appid;
    }

    /**
     *
     * @return the $_mchid
     */
    public function getMchid()
    {
        return $this->_mchid;
    }

    /**
     *
     * @return the $_wxkey
     */
    public function getWxkey()
    {
        return $this->_wxkey;
    }

    /**
     *
     * @return the $_appsecret
     */
    public function getAppsecret()
    {
        return $this->_appsecret;
    }

    /**
     *
     * @param appid $_appid            
     */
    public function setAppid($_appid)
    {
        $this->_appid = trim($_appid);
        \WxPayConfig::$APPID = $this->_appid;
    }

    /**
     *
     * @param string $_mchid            
     */
    public function setMchid($_mchid)
    {
        $this->_mchid = trim($_mchid);
        \WxPayConfig::$MCHID = $this->_mchid;
    }

    /**
     *
     * @param string $_wxkey            
     */
    public function setWxkey($_wxkey)
    {
        $this->_wxkey = trim($_wxkey);
        \WxPayConfig::$KEY = $this->_wxkey;
    }

    /**
     *
     * @param string $_appsecret            
     */
    public function setAppsecret($_appsecret)
    {
        $this->_appsecret = trim($_appsecret);
        \WxPayConfig::$APPSECRET = $this->_appsecret;
    }

    /**
     * 根据商户订单号查询订单
     * 
     * @param string $out_trade_no            
     */
    public function queryOrderByOutTradeNo($out_trade_no)
    {
        if (! $this->isSetConfig()) {
            return false;
        } else {
            $query = new \WxPayOrderQuery();
            $query->SetOut_trade_no(trim($out_trade_no));
            $res = \WxPayApi::orderQuery($query);
            $query = null;
            return $res;
        }
    }

    /**
     * 根据微信订单号查询订单
     * 
     * @param string $transaction_id            
     */
    public function queryOrderByTransactionId($transaction_id)
    {
        if (! $this->isSetConfig()) {
            return false;
        } else {
            $query = new \WxPayOrderQuery();
            $query->SetTransaction_id(trim($transaction_id));
            $res = \WxPayApi::orderQuery($query);
            $query = null;
            return $res;
        }
    }

    /**
     * 检查是否设置配置
     */
    function isSetConfig()
    {
        return $this->getAppid() && $this->getAppsecret() && $this->getMchid() && $this->getWxkey();
    }

    /**
     * 初始化
     */
    function __construct()
    {
        require_once ROOT_PATH . "lib/wxapi/WxPay.Api.php";
    }

    /**
     * 退款
     * 
     * @param
     *            string | 商户订单号 $out_trade_no
     * @param
     *            string | 退款订单号 $out_refund_no
     * @param 原订单总额|单位分 $total_fee            
     * @param 退款金额|单位分 $refund_fee            
     * @param
     *            string 操作者 varchar(32) 默认商户号 $op_user_id
     */
    function refundOrderByOutTradeNo($out_trade_no, $out_refund_no, $total_fee, $refund_fee, $op_user_id = false)
    {
        if (! $this->isSetConfig()) {
            return false;
        } else {
            $op_user_id = $op_user_id === false ? $this->getAppid() : $op_user_id;
            $query = new \WxPayRefund();
            $query->SetOut_trade_no(trim($out_trade_no));
            $query->SetOut_refund_no(trim($out_refund_no));
            // * 申请退款，WxPayRefund中out_trade_no、transaction_id至少填一个且
            // * out_refund_no、total_fee、refund_fee、op_user_id为必填参数
            $query->SetTotal_fee(intval($total_fee));
            $query->SetRefund_fee(intval($refund_fee));
            $query->SetOp_user_id($op_user_id);
            $res = \WxPayApi::refund($query);
            $query = null;
            return $res;
        }
    }
}