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
use app\common\validate\UserReg;
use app\common\validate\UserInfo;
use think\Db;
/**
 * 公共模型
 * @package app\common\model
 */
class Users extends Model
{  
    //注册
    public static function register($username,$password,$sid)
    {
        $user = self::where("username = ?",[$username])->find();
        if($user)
        {
            return new Result(201, [], $username.'号码已经注册过!');
        }else{
            if($sid!=''){
                $recommend = self::where("sid",$sid)->find();
                if(!$recommend){
                    return new Result(204, [], '推荐人不存在!');
                }
                $recommend_id=$recommend['id'];
            }else{
                $recommend_id=0;
            }
            $data = [
                'username'=>$username,
                'password'=>$password,
            ];
            $validate = new UserReg();
            $result = $validate->scene('register')->check($data);
            if(!$result)
            {
                return new Result(202, [], $validate->getError());
            }else{
                $data['sid'] = makeUserSid();
                $data['password'] = md5(md5($password));
                $data['reg_time'] = time();
                $data['recommend_uid'] = $recommend_id;
                $uid = self::insertGetId($data);
                if($uid>0)
                {
                    return new Result(200, ["id"=>$uid], '');
                }else{
                    return new Result(203, [], '用户数据新增失败!');
                }
            }
        }
    }
    //找回密码
    public static function getPassword($username,$password)
    {
        $user = self::where("username = ?",[$username])->find();
        if(!$user)
        {
            return new Result(201, [], $username.'号码还未注册!');
        }else{
            $data = [
                'id'=>$user['id'],
                'password'=>$password,
            ];
            $validate = new UserReg();
            $result = $validate->scene('getPassword')->check($data);
            if(!$result)
            {
                return new Result(202, [],$validate->getError());
            }else{
                $data['password'] = md5(md5($password));
                $rt = self::update($data);
                if($rt!==false)
                {
                    return new Result(200, [], '');
                }else{
                    return new Result(203, [], '找回密码失败!');
                }
            }
        }
    }
    //修改密码
    public static function updatePassword($user_id,$old_password,$password)
    {
        $user = self::field('password')->find($user_id);
        if(!$user)
        {
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }elseif($user['password']!=md5(md5($old_password))){
            return new Result(202, [], '旧密码输入错误!');
        }elseif($user['password']==md5(md5($password))){
            return new Result(203, [], '新密码不能和旧密码相同!');
        }else{
            $data = [
                'id'=>$user_id,
                'password'=>$password,
            ];
            $validate = new UserReg();
            $result = $validate->scene('getPassword')->check($data);
            if(!$result)
            {
                return new Result(204, [],$validate->getError());
            }else{
                $data['password'] = md5(md5($password));
                $rt = self::update($data);
                if($rt!==false)
                {
                    return new Result(200, [], '');
                }else{
                    return new Result(205, [], '修改密码失败!');
                }
            }
        }
    }
    //修改支付密码
    public static function updatePaymentPassword($user_id,$old_payment_password,$payment_password)
    {
        $user = self::field('password,payment_password')->find($user_id);
        if(!$user)
        {
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }else{
            if($user['payment_password'])
            {
                if($user['payment_password']!=md5(md5($old_payment_password))){
                    return new Result(203, [], '旧支付密码输入错误!');
                }elseif($user['payment_password']==md5(md5($payment_password))){
                    return new Result(203, [], '新密码不能和旧密码相同!');
                }else{
                    $data = [
                        'id'=>$user_id,
                        'payment_password'=>$payment_password,
                    ];
                    $validate = new UserReg();
                    $result = $validate->scene('update_payment_password')->check($data);
                    if(!$result)
                    {
                        return new Result(204, [],$validate->getError());
                    }else{
                        $data['payment_password'] = md5(md5($payment_password));
                        $data['is_set_payment']='1';
                        $rt = self::update($data);
                        if($rt!==false)
                        {
                            return new Result(200, [], '');
                        }else{
                            return new Result(205, [], '修改支付密码失败!');
                        }
                    }
                }
            }else{
                if($user['password']!=md5(md5($old_payment_password))){
                    return new Result(202, [], '登录密码输入错误!');
                }else{
                    $data = [
                        'id'=>$user_id,
                        'payment_password'=>$payment_password,
                    ];
                    $validate = new UserReg();
                    $result = $validate->scene('update_payment_password')->check($data);
                    if(!$result)
                    {
                        return new Result(204, [], $result);
                    }else{
                        $data['payment_password'] = md5(md5($payment_password));
                        $data['is_set_payment']='1';
                        $rt = self::update($data);
                        if($rt!==false)
                        {
                            return new Result(200, [], '');
                        }else{
                            return new Result(205, [], '修改支付密码失败!');
                        }
                    } 
                }
            }
        }
    }
    //修改上级
    public static function updateRecommendUid($user_id,$recommend_uid,$content)
    {
        //$rt = self::where('id',$user_id)->setField('recommend_uid',$recommend_uid);
        $id=db('recommend')->insertGetId(['user_id'=>$user_id,'recommend_uid'=>$recommend_uid,'content'=>$content,'addtime'=>time()]);
        if($id>0)
        {
            return new Result(200, [], '');
        }else{
            return new Result(206, [], '修改上级申请失败!');
        }
    }
    //修改银行卡
    public static function updateBank($user_id,$data){
        if(!preg_match('/^\d{16,19}$/',$data['bank_id_number'])){
            return new Result(203, [], '银行卡格式不正确!');
        }
        $data['user_id']=$user_id;
        $data['addtime']=time();
        $user=Bb::name('users')->find($user_id);
        if($user['bank_id']==$data['bank_id'] && $user['bank_open_address']==$data['bank_open_address'] && $user['bank_id_number']==$data['bank_id_number']){
            return new Result(202, [], '你没有修改，不能提交!');
        }
        $id=db('update_bank')->insertGetId($data);
        if($id>0)
        {
            return new Result(200, ['id'=>$id], '');
        }else{
            return new Result(201, [], '申请修改失败!');
        }
    }
    //修改信息
    public static function updateInfo($user_id,$data)
    {
        $user=self::where('id',$user_id)->find();
        if($user['realname'] && $data['realname']!=$user['realname']){
            return new Result(203, [], '姓名不可修改!');
        }else{
            $update['realname']=$data['realname'];
        }
        if($user['bank_id_number'] && $data['bank_id_number']!=$user['bank_id_number']){
            return new Result(204, [], '银行卡号不可修改!');
        }else{
            $update['bank_id_number']=$data['bank_id_number'];
        }
        $update['headimg']=$data['headimg'];
        $update['nickname']=$data['nickname'];
        $update['bank_id']=$data['bank_id'];
        $update['bank_open_address']=$data['bank_open_address'];
        $update['is_set']=1;
        $update['id']=$user_id;
        $validate = new UserInfo();
        $rt = $validate->check($update);
        if(!$rt){
            return new Result(206, [], $validate->getError());
        }
        $result=self::update($update);
        if($result!==false)
        {
            return new Result(200, [], '');
        }else{
            return new Result(205, [], '修改资料失败!');
        }
    }
    //查出增值贝收益列表
    public static function income($user_id){
        $dividends=Db::name('dividend')->alias('a')->join('day_incr b','a.day_incr_id=b.id')->field('a.money,a.addtime,b.daytime')->where('a.user_id',$user_id)->order('b.daytime desc,a.id desc')->select();
        $generation_rewards=Db::name('generation_reward')->alias('a')->join('day_incr b','a.day_incr_id=b.id')->field('a.money,a.addtime,b.daytime')->where('a.user_id',$user_id)->order('b.daytime desc,a.id desc')->select();
        $dividends=array_map(function($v){$v['type']='股息';return $v;}, $dividends);
        $generation_rewards=array_map(function($v){$v['type']='团队分红';return $v;}, $generation_rewards);
        $list=array_merge($dividends,$generation_rewards);
        $sort = array_column($list, 'daytime');      
        array_multisort($sort, SORT_DESC, $list); 
        return $list;
    }
    //查询第几代
    public static function level($user_id,$select_user_id,$appid,$eighteen=false){
        $user = Db::name('users')->find($user_id);
        if(!$user)
        {
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        if($eighteen)
            $team=self::eighteenTeam(1,true,[],true,$le=1,false);
        else
            $team=self::team($user_id,false);
        $level=0;
        foreach ($team as $k => $v) {
            foreach ($v as $key => $value) {
                if($value['id']==$select_user_id){
                    $level=$k+1;
                }
            }
        }
        if($level>0){
            $now = time();
            //判断今天是第几次发
            /*$select_level=Db::name('select_level')->where(['user_id'=>$user_id,'select_user_id'=>$select_user_id,'level'=>$level,'result'=>'1'])->order('id desc')->limit(1)->find();
            $d_now=date('Y-m-d',$now);
            $d_select=date('Y-m-d',$select_level['addtime']);
            if($d_select==$d_now){
                return new Result(201, [], '请不要在一天内重复查询级别相同的团队成员!');
            }*/
            $appid = intval($appid);
            $agentid=0;
            $agentid = AdminClientkey::getAgentId($appid);
            $row = [
                'user_id'=>$user_id,
                'select_user_id'=>$select_user_id,
                'agent_id' =>$agentid,
                'level'=>$level,
                'addtime'=>$now,
                'result'=>1
            ];
            if($eighteen)
                $row['type']=1;
            else
                $row['type']=0;
            $id=0;
            $id = Db::name('select_level')->insertGetId($row);
            if($id>0){
                $select_user = Db::name('users')->find($select_user_id);
                //发送短信？
                if($eighteen)
                    $content='尊敬的用户,您查询的用户（'.substr_replace($select_user['username'], '****', 3, 4).'）是第'.$level.'代成员。';
                else
                    $content='尊敬的用户,您查询的用户（'.substr_replace($select_user['username'], '****', 3, 4).'）是您的第'.$level.'级成员。';
                $r = SmsTemplate::sendMsg($agentid, $user['username'], $content);
                if(!$r->isSuccess())
                {
                    //更新 发送失败
                    Db::name('select_level')->where("id = ?",[$id])->update(['result'=>0]);
                    return new Result(202, [], $r->getMsg());
                }
                return new Result(200, [], '');
            }else{
                return new Result(201, [], '数据库操作失败!');
            }
        }else{
            return new Result(202, [], '查询用户不属于你的团队!');
        }
    }
    //合并七代团队
    public static function teamCount($user_id,$offset,$pageSize){
        $user = self::find($user_id);
        if(!$user)
        {
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $data=array(
            'lv1'=>array(),
            'lv2'=>array(),
            'lv3'=>array(),
            'lv4'=>array(),
            'lv5'=>array(),
            'lv6'=>array(),
            'lv7'=>array()
        );
        //第一代
        $data_1=self::teamRoot(array($user_id));
        if(!$data_1['ids']){
            $data=array_merge($data['lv1'],$data['lv2'],$data['lv3'],$data['lv4'],$data['lv5'],$data['lv6'],$data['lv7']);
            return new Result(200, array_slice($data,$offset,$pageSize), '');
        }
        $data['lv1']=$data_1['users'];
        //第二代
        $data_2=self::teamRoot($data_1['ids']);
        if(!$data_2['ids']){
            $data=array_merge($data['lv1'],$data['lv2'],$data['lv3'],$data['lv4'],$data['lv5'],$data['lv6'],$data['lv7']);
            return new Result(200, array_slice($data,$offset,$pageSize), '');
        }
        $data['lv2']=$data_2['users'];
        //第三代
        $data_3=self::teamRoot($data_2['ids']);
        if(!$data_3['ids']){
            $data=array_merge($data['lv1'],$data['lv2'],$data['lv3'],$data['lv4'],$data['lv5'],$data['lv6'],$data['lv7']);
            return new Result(200, array_slice($data,$offset,$pageSize), '');
        }
        $data['lv3']=$data_3['users'];
        //第四代
        $data_4=self::teamRoot($data_3['ids']);
        if(!$data_4['ids']){
            $data=array_merge($data['lv1'],$data['lv2'],$data['lv3'],$data['lv4'],$data['lv5'],$data['lv6'],$data['lv7']);
            return new Result(200, array_slice($data,$offset,$pageSize), '');
        }
        $data['lv4']=$data_4['users'];
        //第五代
        $data_5=self::teamRoot($data_4['ids']);
        if(!$data_5['ids']){
            $data=array_merge($data['lv1'],$data['lv2'],$data['lv3'],$data['lv4'],$data['lv5'],$data['lv6'],$data['lv7']);
            return new Result(200, array_slice($data,$offset,$pageSize), '');
        }
        $data['lv5']=$data_5['users'];
        //第六代
        $data_6=self::teamRoot($data_5['ids']);
        if(!$data_6['ids']){
            $data=array_merge($data['lv1'],$data['lv2'],$data['lv3'],$data['lv4'],$data['lv5'],$data['lv6'],$data['lv7']);
            return new Result(200, array_slice($data,$offset,$pageSize), '');
        }
        $data['lv6']=$data_6['users'];
        //第七代
        $data_7=self::teamRoot($data_6['ids']);
        if(!$data_7['ids']){
            $data=array_merge($data['lv1'],$data['lv2'],$data['lv3'],$data['lv4'],$data['lv5'],$data['lv6'],$data['lv7']);
            return new Result(200, array_slice($data,$offset,$pageSize), '');
        }
        $data['lv7']=$data_7['users'];
        $data=array_merge($data['lv1'],$data['lv2'],$data['lv3'],$data['lv4'],$data['lv5'],$data['lv6'],$data['lv7']);
        return new Result(200, array_slice($data,$offset,$pageSize), '');
    }
    //团队
    public static function team($user_id,$pix=true)
    {
        $user = self::find($user_id);
        if(!$user)
        {
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $data=array(
            'lv1'=>array(),
            'lv2'=>array(),
            'lv3'=>array(),
            'lv4'=>array(),
            'lv5'=>array(),
            'lv6'=>array(),
            'lv7'=>array()
        );
        //第一代
        $data_1=self::teamRoot(array($user_id));
        if(!$data_1['ids']){
            if($pix){
                return new Result(200, $data, '');
            }else{
                return array_values($data);
            }
        }
        $data['lv1']=$data_1['users'];
        //第二代
        $data_2=self::teamRoot($data_1['ids']);
        if(!$data_2['ids']){
            if($pix){
                return new Result(200, $data, '');
            }else{
                return array_values($data);
            }
        }
        $data['lv2']=$data_2['users'];
        //第三代
        $data_3=self::teamRoot($data_2['ids']);
        if(!$data_3['ids']){
            if($pix){
                return new Result(200, $data, '');
            }else{
                return array_values($data);
            }
        }
        $data['lv3']=$data_3['users'];
        //第四代
        $data_4=self::teamRoot($data_3['ids']);
        if(!$data_4['ids']){
            if($pix){
                return new Result(200, $data, '');
            }else{
                return array_values($data);
            }
        }
        $data['lv4']=$data_4['users'];
        //第五代
        $data_5=self::teamRoot($data_4['ids']);
        if(!$data_5['ids']){
            if($pix){
                return new Result(200, $data, '');
            }else{
                return array_values($data);
            }
        }
        $data['lv5']=$data_5['users'];
        //第六代
        $data_6=self::teamRoot($data_5['ids']);
        if(!$data_6['ids']){
            if($pix){
                return new Result(200, $data, '');
            }else{
                return array_values($data);
            }
        }
        $data['lv6']=$data_6['users'];
        //第七代
        $data_7=self::teamRoot($data_6['ids']);
        if(!$data_7['ids']){
            if($pix){
                return new Result(200, $data, '');
            }else{
                return array_values($data);
            }
        }
        $data['lv7']=$data_7['users'];
        if($pix){
            return new Result(200, $data, '');
        }else{
            return array_values($data);
        }
    }
    protected static function teamRoot($user_id_arr){
        $users=Db::name('users')->field('id,username,realname,headimg,nickname,recommend_uid,is_vip,is_activated,reg_time,status')->where('recommend_uid','in',$user_id_arr)->select();
        $users=phoneDis($users);
        $ids=array();
        foreach ($users as $k => $v) {
            $ids[]=$v['id'];
        }
        return array('users'=>$users,'ids'=>$ids);
    }
    //申请提现
    public static function addWithdraw($user_id,$amount,$payment_password){
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $amount=floatval($amount);
        $amount=number_format($amount,2,".","");
        if($amount<=0){
            return new Result(202, [], '提款金额必须大于0!');
        }
        if($amount>$user['money']){
            return new Result(202, [], '提款金额超过账户余额!');
        }
        if(md5($payment_password)!=$user['payment_password']){
            return new Result(204, [], '支付密码错误!');
        }
        $data=[
            'user_id'=>$user_id,
            'money'=>$amount,
            'addtime'=>time()
        ];
        // 启动事务
        Db::startTrans();
        try{
            $withdraw_id=Db::name('withdraw')->insert($data);
            Db::name('users')->update(['id'=>$user_id,'money'=>$user['money']-$amount]);
            // 提交事务
            Db::commit();
            return new Result(200, ['id'=>$withdraw_id], '');    
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(203, [], '提现申请失败!');
        }
    }
    //充值记录
    public static function rechargeList($user_id,$offset,$pageSize)
    {
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $list=Db::name('recharge')->where(['user_id'=>$user_id,'result'=>'1'])->field('user_id',true)->order('addtime DESC')->limit($offset,$pageSize)->select();
        if($list!==false){
            return new Result(200, $list, '');
        }else{
            return new Result(202, [], '数据获取失败!');
        }
    }
    //下三代充值奖励记录
    public static function rechargeRewardList($user_id,$offset,$pageSize)
    {
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $list=Db::name('recharge_reward')->alias('a')->join('recharge b','a.recharge_id=b.id','LEFT')->join('users c','b.user_id=c.id','LEFT')->where('a.user_id','=',$user_id)->field('a.id,a.addtime,a.money,a.level,b.sn,b.user_id as recharger_id,c.username as recharger_username,c.nickname as recharger_nickname,b.money as recharge_money')->order('a.addtime DESC')->limit($offset,$pageSize)->select();
        if($list!==false){
            $list=phoneDis($list,false,array('recharger_username'));
            return new Result(200, $list, '');
        }else{
            return new Result(202, [], '数据获取失败!');
        }
    }
    //充值
    public static function recharge($user_id,$data)
    {
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $amount=intval($data['amount']);
        if($amount<=0){
            return new Result(211, [], '充值金额至少为100元!');
        }
        if($amount%100!=0){
            return new Result(212, [], '充值金额必须被100整除!');
        }
        $sum=Db::name('recharge')->where(['user_id'=>$user_id,'result'=>'1'])->sum('money');
        if($sum+$amount>300000){
            return new Result(213, [], '你的账户投资超过30万!');
        }
        $sn='CZ'.date('Ymd').mt_rand('10000000','99999999');
        if($data['type']=='0'){//线下人工充值  （审核成功后记得加上奖励和激活）
            if(!$data['content']) return new Result(202, [], '请输入线下充值申请理由!');
            if(!$data['imgs']) return new Result(203, [], '请上传线下充值证据图片!');
            $insert=[
                'user_id'=>$user_id,
                'type'=>'0',
                'addtime'=>time(),
                'money'=>$amount,
                'content'=>$data['content'],
                'imgs'=>$data['imgs'],
                'sn'=>$sn,
            ];
            $rt=Db::name('recharge')->insert($insert);
            if($rt>0)
            {
                return new Result(200, ['sn'=>$sn], '');
            }else{
                return new Result(204, [], '线下充值申请失败!');
            }
        }elseif($data['type']=='1'){//微信充值
            $insert=[
                'user_id'=>$user_id,
                'type'=>'1',
                'addtime'=>time(),
                'money'=>$amount,
                'sn'=>$sn,
            ];
            $rt=Db::name('recharge')->insert($insert);
            if($rt>0)
            {
                //此处应该跳转至微信支付目录操作
                if(true){
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('recharge')->where('sn',$sn)->update(['result'=>'1']);
                        //充值金额自动转久映贝（如：充值50.76元，就是56个久映贝和0.76元）
                        $point=floor($amount);
                        $add_money=$amount-$point;
                        Db::name('users')->where('id',$user_id)->update(['money'=>$user['money']+$add_money,'point'=>$user['point']+$point]);
                        //充值上三代奖励和激活
                        self::rechargeReward($sn);
                        // 提交事务
                        Db::commit();    
                        return new Result(200, ['sn'=>$sn], '');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        return new Result(205, [], '微信付款成功，但充值和奖励失败，请截图联系官方客服恢复充值!');
                    }
                }
            }else{
                return new Result(206, [], '微信充值失败!');
            }
        }elseif($data['type']=='2'){
            $insert=[
                'user_id'=>$user_id,
                'type'=>'2',
                'addtime'=>time(),
                'money'=>$amount,
                'sn'=>$sn,
            ];
            $rt=Db::name('recharge')->insert($insert);
            if($rt>0)
            {
                //此处应该跳转至支付宝支付目录操作
                if(true){
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('recharge')->where('sn',$sn)->update(['result'=>'1']);//充值金额自动转久映贝（如：充值50.76元，就是56个久映贝和0.76元）
                        $point=floor($amount);
                        $add_money=$amount-$point;
                        Db::name('users')->where('id',$user_id)->update(['money'=>$user['money']+$add_money,'point'=>$user['point']+$point]); 
                        //充值上三代奖励和激活
                        self::rechargeReward($sn);
                        // 提交事务
                        Db::commit();
                        return new Result(200, ['sn'=>$sn], '');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        return new Result(207, [], '支付宝付款成功，但充值和奖励失败，请截图联系官方客服恢复充值!');
                    }
                }
            }else{
                return new Result(208, [], '支付宝充值失败!');
            }
        }
    }
    //充值奖励和激活
    public static function rechargeReward($sn){
        $now=time();
        $recharge=Db::name('recharge')->where('sn',$sn)->find();
        $user=Db::name('users')->field('is_activated,is_agent')->where('id',$recharge['user_id'])->find();
        if($user['is_activated']=='0' && $recharge['money']>=1000){//激活
            Db::name('users')->where('id',$recharge['user_id'])->update(['is_activated'=>'1','activated_time'=>$now]);
        }
        if($user['is_agent']=='0' && $recharge['money']>=3000){//成为代理
            Db::name('users')->where('id',$recharge['user_id'])->update(['is_agent'=>'1','agent_time'=>$now]);
        }
        //上三代奖励
        $parentUsers=self::parentUsers($recharge['user_id']);
        foreach ($parentUsers as $k => $v) {
            if($v['is_agent']=='0') continue; //判断是否是代理，不是则跳过
            if($k=='lv1'){
                $rate=0.1;
                $level=1;
            }elseif($k=='lv2'){
                $rate=0.05;
                $level=2;
            }elseif($k=='lv3'){
                $rate=0.025;
                $level=3;
            }
            $amount=number_format($recharge['money']*$rate,2,".","");
            Db::name('recharge_reward')->insert([
                'recharge_id'=>$recharge['id'],
                'money'=>$amount,
                'user_id'=>$v['id'],
                'level'=>$level,
                'addtime'=>$now,
            ]);
            Db::name('users')->where('id',$v['id'])->update(['money'=>$v['money']+$amount]); 
        }
    }
    //上三代成员
    protected static  function parentUsers($user_id){
        $data=array();
        $user=Db::name('users')->field('recommend_uid')->where('id',$user_id)->find();
        if($user['recommend_uid']>0){
            $user_1=Db::name('users')->field('password,payment_password',true)->where('id',$user['recommend_uid'])->find();
            if($user_1){
                $data['lv1']=$user_1;
                if($user_1['recommend_uid']>0){
                    $user_2=Db::name('users')->field('password,payment_password',true)->where('id',$user_1['recommend_uid'])->find();
                    if($user_2){
                        $data['lv2']=$user_2;
                        if($user_2['recommend_uid']>0){
                            $user_3=Db::name('users')->field('password,payment_password',true)->where('id',$user_2['recommend_uid'])->find();
                            if($user_3){
                                $data['lv3']=$user_3;
                                return $data;
                            }else{
                                return $data;
                            }
                        }else{
                            return $data;
                        }
                    }else{
                        return $data;
                    }
                }else{
                    return $data;
                }
            }else{
                return $data;
            }
        }else{
            return $data;
        }
    }
    //查出自己是第几代
    public static function getGeneration($user_id,$generation=-1){
        $user=Db::name('users')->field('recommend_uid')->where('id',$user_id)->find();
        if($user){
            $generation=$generation+1;
            $recommend=Db::name('users')->where('id',$user['recommend_uid'])->find();
            if($recommend){
                return self::getGeneration($recommend['id'],$generation);
            }else{
                return (string)$generation;
            }
        }else{
            return (string)$generation;
        }
    }
    //十八代全部团队
    public static function eighteenTeam($user_id=1,$tree=true,$result=[],$pix=true,$le=1,$return=true)
    {
        if($pix){
            $user = Db::name('users')->find($user_id);
            if(!$user)
            {
                return new Result(201, [], '用户不存在，请刷新页面重试!');
            }
        }
        $users=Db::name('users')->field('password,payment_password',true)->where('recommend_uid','in',(array)$user_id)->where('level','>','0')->select();    
        if($users){
            if($tree){
                $result[]=phoneDis($users);
            }else{
                $result=array_merge($result,phoneDis($users));
            }
            if($le>=18){
                if($return)
                    return new Result(200, $result, '');
                else 
                    return $result;
            }else{
                $ids=[];
                foreach ($users as $k => $v) {
                    $ids[]=$v['id'];
                }
                $le=$le+1;
                return self::eighteenTeam($ids,$tree,$result,false,$le,$return);
            }
        }else{
            if($return)
                return new Result(200, $result, '');
            else
                return $result;
        }
    }
    //十八代中自己下面的团队
    public static function myEighteenTeam($user_id,$uid=1,$result=[],$pix=true,$le=1,$return=true,$generation=-1)
    {
        if($pix){
            $generation=self::getGeneration($user_id);
        }
        $users=Db::name('users')->field('id,sid,username,nickname,level')->where('recommend_uid','in',(array)$uid)->where('level','>','0')->order('id desc,reg_time desc')->select();    
        if($users){
            if($le>$generation){
                $result=array_merge($result,phoneDis($users));
            }
            if($le>=18){
                if($return)
                    return new Result(200, $result, '');
                else 
                    return $result;
            }else{
                $ids=[];
                foreach ($users as $k => $v) {
                    $ids[]=$v['id'];
                }
                $le=$le+1;
                return self::myEighteenTeam($user_id,$ids,$result,false,$le,$return,$generation);
            }
        }else{
            if($return)
                return new Result(200, $result, '');
            else
                return $result;
        }
    }
}
