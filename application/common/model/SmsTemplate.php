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

namespace app\common\model;

use think\Model;
use think\fn\Result;

/**
 * 短信模板公共模型
 * @package app\common\model
 */
class SmsTemplate extends Model
{
   /**
    * 发送短信
    * @param 代理ID $agent_id
    * @param 接收号码 $mobile
    * @param 发送内容,支持标签,默认{$mobile}手机号,{$code}验证码,其他的可循环解析param $content
    * @param 内容参数数组,['mobile'=>'180','code'=>12345] 循环替换content内可能包含的内容 $param
    */ 
    public static function sendMsg($agent_id,$mobile,$content,$param = [])
    {
        $agent_id = intval($agent_id);
        
        //1.查找此代理的 正常可用的短信配置数据
        $datas = self::where("agent_id = ? AND status = 1",[$agent_id])->order("weight DESC")->select();
        if(count($datas))
        {
            //先替换content内的参数
            foreach ($param as $k=>$v){
                $content = str_replace('{$'.$k.'}', $v, $content);
            }
            foreach ($datas as $v)
            {
                if(intval($v['sign_pos']) == 0)
                {
                    //签名前置
                    $content = $v['sign'].$content;
                }else{
                    $content = $content.$v['sign'];//签名后置
                }
                if($v['charset'] == 0)
                {
                    //默认utf8不处理
                }else{
                    $content = mb_convert_encoding($content, "GBK","UTF-8");
                }
                
                if($v['mode_class'] == 'url')//url模式
                {
                    $url = $v['url'];//url模式下，get 下，必须至少包含2个%s,分别代表 号码 内容
                    if(intval($v['method']) == 0)
                    {
                        $url = sprintf($url,$mobile,$content);
                        $postData = '';
                    }else{//post
                        $url = explode('|', $url);
                        if(count($url) <> 2){
                            continue;//格式不正确
                        }else{
                            $postData = $url[1];
                            $postData = sprintf($postData,$mobile,$content);
                            $url = $url[0];
                        }
                    }
                }
                $result = self::curl($url,$postData);
                $success = true;
                if($v['success_keyword'])
                {
                    if(strpos($result, $v['success_keyword']) === false){
                        $success = false;
                    }
                }
                $smslog = [
                    'agent_id'=>$agent_id,
                    'template_id'=>$v['id'],
                    'url'=>$url.$postData,
                    'mobile'=>$mobile,
                    'result'=>$result,
                    'success'=>$success ? 1 : 0,
                    'created'=>time()
                ];
                $log = SmsSendLog::insert($smslog);
                if($success)
                {
                    //如果成功则跳出
                    return new Result(200, [], '');
                }
            }
        }else{
            return new Result(201, []  , '当前没有可用的短信通道,请检查是否配置或者所有短信通道都被锁定.');
        }
        return new Result(201, [], '当前所有短信模板都不可用,请检查.');
    }

    /*
     * 访问URL
     */
    static function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
    
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        if ($postFields) {
           curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        $reponse = curl_exec($ch);
    
        curl_close($ch);
        return $reponse;
    }
    
}