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
use think\Db;
use think\Log;


// 为方便系统核心升级，二次开发中需要用到的公共函数请写在这个文件，不要去修改common.php文件
if (! function_exists("get_order_id")) {

    /**
     * 生成订单号
     *
     * @param int $prefix
     * @return string
     */
    function get_order_id($prefix)
    { // 目前订单号适用于1亿用户内注册用户
        $uid = substr("00000000" . $prefix, - 7);
        /* 选择一个随机的方案 */
        return date('ymd', time()) . $prefix . substr(microtime(), 2, 6);
    }
}

/**
 * 报名退款
 */
if (! function_exists('activity_refund')) {

    /**
     * 报名退款
     *
     * @param 报名ID|int $joinId
     * @param 退款金额|单位分 $refund_number
     */
    function activity_refund($joinId, $refund_money)
    {
        $refund_money = intval($refund_money);
        $limitDb = Db::table("activity_join")->where("id = ?", array(
            $joinId
        ));
        $row = $limitDb->find();
        if (empty($row)) {
            return false;
        }
        // 查找活动价格
        $activity = Db::table("activity")->where("id = ?", [
            $row['activity_id']
        ])->find();
        if (empty($activity)) {
            return false;
        }
        if (isset($row['id']) && (intval($row['id']) == $joinId) && intval($row['pay_status']) == 1) {
            if ($row['pay_method'] == 'wx') {
                if ($refund_money <= intval($row['pay_money']) * 100) {
                    $refund_orderid = get_order_id('');
                    $outTradeNo = trim($row['pay_orderid']);
                    $total_fee = intval($row['pay_money']) * 100;
                    require_once ROOT_PATH . "lib/wxapi/tp.wx.mppay.php";
                    $helper = new \tpwxmppay();
                    $helper->setAppid('');
                    $helper->setAppsecret('');
                    $helper->setMchid('');
                    $helper->setWxkey('');
                    $helper->setCert_file(ROOT_PATH . 'lib/wxapi/cert/apiclient_cert.pem');
                    $helper->setKey_file(ROOT_PATH . 'lib/wxapi/cert/apiclient_key.pem');

                    $res = $helper->refundOrderByOutTradeNo($outTradeNo, $refund_orderid, $total_fee, $refund_money);
                    Log::record("activity_refund,id={$joinId},result=" . print_r($res, true), 'info');
                    if (isset($res['result_code']) && isset($res['return_code']) && $res['result_code'] == 'SUCCESS' && $res['return_code'] == 'SUCCESS') {
                        $refund_fee = intval(ceil(intval($res['refund_fee']) / 100));
                        $diff_number = ceil($refund_fee / intval($activity['need_money']));
                        // 更新报名表
                        Db::execute("UPDATE activity_join SET  refund_status = 1,refund_orderid = ?,refund_money = refund_money + ?,adult_num = adult_num - ? WHERE id = ?", [
                            $refund_orderid,
                            $refund_fee,
                            $diff_number,
                            $joinId
                        ]);
                        // 加入退款记录
                        Db::execute("INSERT INTO activity_refund(user_id,activity_id,refund_fee,refund_orderid,join_id,created) VALUES(?,?,?,?,?,?)", [
                            $row['user_id'],
                            $row['activity_id'],
                            $refund_fee,
                            $refund_orderid,
                            $row['id'],
                            time()
                        ]);

                        // Db::table("activity_join")->where("id = ?",array($joinId))->update(array('refund_status'=>1,'refund_orderid'=>$refund_orderid))->setInc('refund_money',$refund_fee);
                        return $res;
                    } else {
                        return false;
                    }
                } else {
                    return false; // 超出支付的金额
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
if (! function_exists('toHashmap')) {

    /**
     * 将一个二维数组转换为 HashMap，并返回结果
     *
     * 用法1：
     * @code php
     * $rows = array(
     * array('id' => 1, 'value' => '1-1'),
     * array('id' => 2, 'value' => '2-1'),
     * );
     * $hashmap = Helper_Array::hashMap($rows, 'id', 'value');
     *
     * dump($hashmap);
     * // 输出结果为
     * // array(
     * // 1 => '1-1',
     * // 2 => '2-1',
     * // )
     * @endcode
     *
     * 如果省略 $value_field 参数，则转换结果每一项为包含该项所有数据的数组。
     *
     * 用法2：
     * @code php
     * $rows = array(
     * array('id' => 1, 'value' => '1-1'),
     * array('id' => 2, 'value' => '2-1'),
     * );
     * $hashmap = Helper_Array::hashMap($rows, 'id');
     *
     * dump($hashmap);
     * // 输出结果为
     * // array(
     * // 1 => array('id' => 1, 'value' => '1-1'),
     * // 2 => array('id' => 2, 'value' => '2-1'),
     * // )
     * @endcode
     *
     * @param array $arr
     *            数据源
     * @param string $key_field
     *            按照什么键的值进行转换
     * @param string $value_field
     *            对应的键值
     *
     * @return array 转换后的 HashMap 样式数组
     */
    function toHashmap($arr, $key_field, $value_field = null)
    {
        $ret = array();
        if ($value_field) {
            foreach ($arr as $row) {
                $ret[$row[$key_field]] = $row[$value_field];
            }
        } else {
            foreach ($arr as $row) {
                $ret[$row[$key_field]] = $row;
            }
        }
        return $ret;
    }
    if(!function_exists('cget'))
    {
        function cget($url)
        {
            $cu = curl_init();
            curl_setopt($cu, CURLOPT_URL, $url);
            curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
            $ret = curl_exec($cu);
            curl_close($cu);
            return $ret;
        }
    }
    if(!function_exists('getRequestHost'))
    {
        /**
         * 返回请求的域名
         *
         * @return string
         */
        function getRequestHost()
        {
            $http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
            $http = $http . $_SERVER['SERVER_NAME'];
            $port = $_SERVER["SERVER_PORT"] == 80 ? '' : ':' . $_SERVER["SERVER_PORT"];
            $url = $http . $port;
            $host = $url . '/';
            return $host;
        }
    }
    if(!function_exists('makeSign'))
    {
        /**
         * 计算SIGN
         * @param $data
         * @param $appkey
         */
        function makeSign($data,$appkey)
        {
            ksort($data, SORT_STRING);
            $makeSign = '';
            foreach ($data as $v) {
                if (is_array($v)) {
                    $makeSign .= $v[0];
                } else {
                    $makeSign .= $v;
                }
            }
            $makeSign .= $appkey;
            $makeSign = md5($makeSign);
            return $makeSign;
        }
    }



    if(!function_exists('sendRequest')) {
        /**
         * 调用API请求
         * @param $action API接口名 比如：api/user/login
         * @param $data 接口参数
         * @return array json解密后的数组数据
         */
        function sendRequest($action, $data ,$token = '', $appid = '',$appkey = '')
        {
            if(!$appid)$appid = config("api_appid");
            if(!$appkey)$appkey = config("api_appkey");
            $host = getRequestHost();
            $url = $host  . $action.'?';
            $data['appid'] = $appid;
            $data['timeline'] = time();
            $data['token'] = $token;
            $data['sign'] = makeSign($data, $appkey);
            $url .= http_build_query($data);
            $result = cget($url);
            return json_decode($result, true);
        }
    }
    if(!function_exists('sendRequestUrl')) {
        /**
         * 调用API请求
         * @param $action API接口名 比如：api/user/login
         * @param $data 接口参数
         * @return 可以请求的url
         */
        function sendRequestUrl($action, $data ,$token = '', $appid = '',$appkey = '')
        {
            if(!$appid)$appid = config("api_appid");
            if(!$appkey)$appkey = config("api_appkey");
            $host = getRequestHost();
            $url = $host  . $action.'?';
            $data['appid'] = $appid;
            $data['timeline'] = time();
            $data['token'] = $token;
            $data['sign'] = makeSign($data, $appkey);
            $url .= http_build_query($data);
            return $url;
        }
    }


    if(!function_exists('getRequest')){
        /**
         * 判断 $_RUQUEST 是否接收到$key参数
         * @param $key
         * @param bool $default 默认值
         * @return bool|string
         */
       function getRequest($key,$default=false)
       {
           if(isset($_REQUEST[$key]) && trim($_REQUEST[$key])){
               return trim($_REQUEST[$key]);
           }else{
               return $default;
           }
       }
    }


}

//字符串连接
if(!function_exists('str_linked')){
    function str_linked($str1='',$str2='')
    {
        return $str1.$str2;
    }
}

//取数组每个键值
if(!function_exists('array_v')){
    function array_v($key,$arr=array())
    {
        return $arr?(array_key_exists($key,$arr)?$arr[$key]:$arr[0]):'';

    }
}

//null转为空字符串
if(!function_exists('null2')){
    function null2($data){
        if(is_array($data)){
            return array_map('null2',$data);
        }else{
            if($data===null || $data==='null')
                return $data='';
            else
                return $data;
        }
    }
}

//手机号码保护
if(!function_exists('phoneDis')){
    function phoneDis($arr,$pix=false,$keys=array('username')){
        if($pix){
            foreach ($arr as $key => $value) {
                if(in_array($key, $keys) && preg_match('/^1\d{10}$/', $value)){
                    $arr[$key]=substr_replace($value, '****', 3, 4);
                }
            }
        }else{
            foreach ($arr as $k => $v) {
                foreach ($v as $key => $value) {
                    if(in_array($key, $keys) && preg_match('/^1\d{10}$/', $value)){
                        $arr[$k][$key]=substr_replace($value, '****', 3, 4);
                    }
                }
            }
        }
            
        return $arr;
    }
}





    if(!function_exists('isParam')){

        /**
         * @param $key 判断key是否设置且不为空
         *
         */
        function isParam($key){
            if(isset($key) && trim($key)){
                return trim($key);
            }else{
                return false;
            }
        }
    }


    if(!function_exists('regular')){
        /**
         * @param $key
         *  根据key返回对应正则
         */
        function regular($key){
            $regular = array(
                'url'=>'/^(((h|H)(T|t){2}(P|p)(S|s){0,1}|ftp|rtsp|mms)?:\/\/)[^\s]+/',//匹配可访问url
                'qq'=>'/^[1-9][0-9]{4,12}$/',  //QQ号码
                'email'=>'/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', //电子邮箱
                'phone'=>'/^1[3|4|5|7|8]\d{9}$/',//手机号码
                'sort'=>'/^[0-9]*$/',   //验证数字
            );
            return !isset($regular[$key])?false:$regular[$key];
        }
    }



    if(!function_exists('sortCate')) {
        /**
         * 公共的分类排序   无限极分类
         */
        function sortCate($data, $pid = 0, $lev = 1)
        {
            $arr = array();
            foreach ($data as $k => $v) {
                if ($v['pid'] == $pid) {
                    $v['lev'] = $lev; //一级菜单，二级菜单。。。。等级
//                    $arr[] = $v;

                    //等级添加空格
                    if($v['lev']>1)
                    {
                        $str = "&nbsp;&nbsp;&nbsp;";
                        for($i=1;$i<=$v['lev'];$i++)
                        {
                            $str.=$str;
                        }
                        $v['title'] = $str.$v['title'];
                    }

                    $arr[$v['id']] = $v['title'];

                    $arr = array_merge($arr, sortCate($data, $v['id'], $lev + 1)); //递归，无限调用,array_merge是将2个数组组合
                }
            }
            return $arr;
        }
    }



    if(!function_exists('getClass')) {
        /**
         * 将二维数组对应的值取出，转化为一位数组
         * @param $data 二维数组
         * @param $key  key
         * @param $val   值
         */
        function getClass($data, $key, $val)
        {
            $oneArr = [];
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    if (is_array($v) && isset($v[$key]) && isset($v[$val])) {
                        $oneArr[$v[$key]] = $v[$val];
                    } else {
                        return $oneArr;
                    }
                }
            } else {
                return $oneArr;
            }
            return $oneArr;
        }
    }


    if(!function_exists('getClassName')) {
        /**
         * 获取 新闻 分类名称
         * @param $cid  分类id
         * @param $data   分类名
         */
        function getClassName($cid){
            return Db::name('news_class')->where('id',$cid)->value('name');
        }
    }


    if(!function_exists('getCaseClassName')) {
        /**
         * 获取 案例 分类名称
         * @param $cid  分类id
         * @param $data   分类名
         */
        function getCaseClassName($cid){
            return Db::name('website_cases_class')->where('id',$cid)->value('name');
        }
    }



    if(!function_exists('getImgPath')) {
        /**
         * 通过图片id来获取图片路径
         * $img_id  多图片的id
         * $path  图片路径   完整的图片路径
         */
        function getImgPath($img_id)
        {
            $path =  Db::name('admin_attachment')->where("id",$img_id)->value("path");
            return 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].'/'.$path;
        }
    }


    /**
     * 新闻字符串  截取，超出用...显示
     */
    if(!function_exists('cut_str')) {
        function cut_str($str,$len,$suffix="..."){
            if(function_exists('mb_substr')){
                if(strlen($str) > $len){
                    $str= mb_substr($str,0,$len,'utf-8').$suffix;
                }
                return $str;
            }else{
                if(strlen($str) > $len){
                    $str= substr($str,0,$len).$suffix;
                }
                return $str;
            }
        }
    }


    if(!function_exists('ajaxReturn')) {
        /**
         * ajax返回数据函数 yang
         * @param  $[msg] [返回字符串]
         * @param  $[status] [状态值]
         * @return  [数据]
         */
        function ajaxReturn($msg, $status, $type = '')
        {
            $callback = array();
            $callback['status'] = $status; //报错状态
            $callback['msg'] = $msg; //报错信息
            $callback['type'] = $type; //附带参数
            echo json_encode($callback); //将json字符串转化成json对象
            exit();
        }
    }








/**
 * 随机字符
 * @param number $length 长度
 * @param string $type 类型
 * @param number $convert 转换大小写
 * @return string
 */
if (! function_exists('random')) {
    function random($length=4, $type='all', $convert=0){
        $config = array(
            'number'=>'1234567890',
            'letter'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'small'=>'abcdefghijklmnopqrstuvwxyz',
            'big'=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'string'=>'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
            'all'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        );
        
        if(!isset($config[$type])) $type = 'string';
        $string = $config[$type];
        
        $code = '';
        $strlen = strlen($string) -1;
        for($i = 0; $i < $length; $i++){
            $code .= $string{mt_rand(0, $strlen)};
        }
        if(!empty($convert)){
            $code = ($convert > 0)? strtoupper($code) : strtolower($code);
        }
        return $code;
    }
}
//生成用户username
if (! function_exists('makeUsername')) {
    function makeUsername(){
        $username='ID'.random(6,'number');
        $user=db('users')->where('username',$username)->find();
        if($user){
            makeUsername();
        }else{
            return $username;
        }
    }
}

//导出xls
if (! function_exists('exportexcel')) {
    function exportexcel($data=array(),$title=array(),$filename='report'){
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");  
        header("Content-Disposition:attachment;filename=".$filename.".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        //导出xls 开始
        if (!empty($title)){
            foreach ($title as $k => $v) {
                $title[$k]=iconv("UTF-8", "GBK",$v);
            }
            $title= implode("\t", $title);
            echo "$title\n";
        }
        if (!empty($data)){
            foreach($data as $key=>$val){
                foreach ($val as $ck => $cv) {
                    $cv=str_replace(["\t","\n"],[" "," "],$cv);
                    $data[$key][$ck]=iconv("UTF-8", "GBK", $cv);
                }
                $data[$key]=implode("\t", $data[$key]);
                
            }
            echo implode("\n",$data);
        }
    }   
}

//查出主图
if (! function_exists('mainImgPath')) {
    function mainImgPath($id){
        if(!is_array($id)){
            $id=explode(',', $id);
        }
        return db('admin_attachment')->where('id',$id[0])->value('path');
    }
}

//查出图片列表
if (! function_exists('imgLstPath')) {
    function imgLstPath($id){
        if(!is_array($id)){
            $id=explode(',', $id);
        }
        $arr=array();
        foreach ($id as $k => $v) {
            $arr[]=db('admin_attachment')->where('id',$v)->value('path');
        }
        return $arr;
    }
}