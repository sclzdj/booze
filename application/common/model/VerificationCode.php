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
 * 公共模型
 * @package app\common\model
 */
class VerificationCode extends Model
{
    /**
     * 效验 验证码 ,@todo 暂时不过期
     * @param string $mobile
     * @param int $tagid
     * @param string $value
     * @return Result
     */
    public static function verify($mobile,$tagid,$value)
    {
        $mobile = trim($mobile);
        $tagid = intval($tagid);
        $row = self::where("mobile = ? AND tagid = ?",[$mobile,$tagid])->order("id DESC")->find();
        if(!$row)
        {
            return new Result(201, [], '没有找到验证码记录.');
        }else if(intval($row['used']) == 1)
        {
            return new Result(202, [], '验证码已经被使用!');
        }else if(intval($row['try_number']) >= 3)
        {
            return new Result(204, [], '验证码错误次数过多,请不要重复尝试!');
        }else if(trim($row['code']) == trim($value)){
            //更新验证码被使用
            self::where("id = ?",[$row['id']])->update(['used'=>1]);
            return new Result(200, [], '');
        }else{
            //更新错误次数
            self::where("id = ?",[$row['id']])->setInc('try_number');
            return new Result(203, [], '验证码错误.');
        }
        
    }
    /**
     * 产生一条验证码
     * @param 验证码用途,注意使用地点 $purpose
     * @param 接收号码 $mobile
     * @param 客户端的APPID $appid
     * @param 验证码过期时间 int $expTime
     * @return Result ,data内包含验证码数据
     */
    public static function makeCode($purpose,$mobile,$appid,$expTime = 60)
    {
        $appid = intval($appid);
        $mobile = trim($mobile);
        $expTime = intval($expTime);
        $purpose = trim($purpose);
        //0.检查此号码是否在数据库内 是否存在相同用途的 并且未过期的
        //1.根据appid 获取agentid
        //2.根据agentid获取代理设置 是走短信、语音 
        //3.走短信和语音 是否对应扣除代理费用？
        $row = self::where("mobile = ? AND purpose = ? AND used = 0",[$mobile,$purpose])->order("id DESC")->find();
        $id = 0;
        $agentid = 0;
        if($row && intval($row['exp_time']) > time())
        {
            $id = $row['id'];
            $agentid = $row['agent_id'];
        }else{
            $agentid = AdminClientkey::getAgentId($appid);
            $time = time();
            //其他情况下 新建数据
            $code = mt_rand(100000, 999999);
            $row = [
                'purpose'=>$purpose,
                'tagid' => $time,
                'code'  => $code,
                'agent_id' =>$agentid,
                'mobile'=>$mobile,
                'created'=>$time,
                'exp_time'=>$time + $expTime,
                'class_mode'=>0,//默认短信？@todo 根据代理设置
                'result'=>1
            ];
            $id = self::insertGetId($row);
        }
        
        if($id)
        {
            //发送短信？
            $r = SmsTemplate::sendMsg($agentid, $mobile, '您本次的操作的验证码为{$code},请尽快使用。',['code'=>$row['code']]);
            if(!$r->isSuccess())
            {
                //更新验证码发送失败
                self::where("id = ?",[$id])->update(['result'=>0]);
                return new Result(202, [], $r->getMsg());
            }
            return new Result(200, $row, '');
        }else{
            return new Result(201, [], '数据库操作失败!');
        }
    }
    
}
