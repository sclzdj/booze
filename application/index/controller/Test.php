<?php
namespace app\index\controller;

use think\Controller;
class Test extends Controller
{
    public function test()
    {
        require_once ROOT_PATH."lib/wxapi/tp.wx.mppay.php";

        $helper = new \tpwxmppay();
        $helper->setAppid('wxfc90a34d2124b5ea');
        $helper->setAppsecret('b9d810675009551d60a0745465903f16');
        $helper->setMchid('1281426601');
        $helper->setWxkey('c722ba6d57022c26bcef682877b093c9');

        $res = $helper->queryOrderByOutTradeNo('170205687867');
        dump($res);
    }
    
    public function refund()
    {
        //报名退款
        $joinid = 110;
        $s = activity_refund($joinid,1*100);
        dump($s);
    }
}