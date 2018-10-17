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
use think\Db;
use think\fn\Result;
/**
 * 公共模型
 * @package app\common\model
 */
class Stocks extends Model
{
    //公司股份拆成n份，第1份挂卖
    public static function company_n($company_id,$n,$point_price)
    {
        $now=time();
        $company=Db::name('company_config')->find($company_id);
        if(!$company){
            return ['status'=>0,'error'=>'该企业已不存在！'];
        }
        if($company['n']>0){
            return ['status'=>0,'error'=>'该企业已把股权分档！'];
        }
        $stock=$company['count_stock']/$n;
        if($stock%100!=0){
            return ['status'=>0,'error'=>'拆分完后的每一份股份必须被100整除！'];
        }
        // 启动事务
        Db::startTrans();
        try{
            Db::name('company_config')->where('id',$company_id)->update(['n'=>$n,'min_price'=>$point_price,'n_time'=>$now,'begin_sale_time'=>$now,'remain_stock'=>$company['remain_stock']-$stock]);
            Db::name('stock')->insert(['company_id'=>$company_id,'stock'=>$stock,'point_price'=>$point_price,'addtime'=>$now,'num'=>'1','type'=>'0']);
            // 提交事务
            Db::commit();    
            return ['status'=>1];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['status'=>0,'error'=>'拆分失败！'];
        }
    }
    //现金转久映贝
    public static function money2point($user_id,$amount,$payment_password){
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        if(!preg_match("/^[1-9][0-9]*$/",$amount)){
            return new Result(202, [], '转换的数量必须是正整数!');
        }
        if($user['money']<$amount){
            return new Result(209, [], '转换现金余额不足!');
        }
        if($user['payment_password']!=md5($payment_password)){
            return new Result(204, [], '支付密码错误!');
        }
        $rt=Db::name('users')->update(['id'=>$user_id,'point'=>$user['point']+$amount,'money'=>$user['money']-$amount]);
        if($rt!==false){
            return new Result(200, [], '');
        }else{
            return new Result(203, [], '转换失败!');
        }
    }
    //久映贝转增值贝
    public static function point2incr($user_id,$amount,$payment_password){
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        if(!preg_match("/^[1-9][0-9]*$/",$amount)){
            return new Result(202, [], '转换的数量必须是正整数!');
        }
        if($user['point']<$amount){
            return new Result(209, [], '转换久映贝余额不足，请先充值!');
        }
        $stock_config = Db::name('stock_config')->where('id', 1)->find();
        if($amount>$user['point']*$stock_config['point2incr_max_rate']){
            return new Result(210, [], '转换数量不能超过50%!');
        }
        if($user['payment_password']!=md5($payment_password)){
            return new Result(204, [], '支付密码错误!');
        }
        // 启动事务
        Db::startTrans();
        try{
            Db::name('users')->update(['id'=>$user_id,'incr'=>$user['incr']+$amount,'point'=>$user['point']-$amount]);
            self::userLevel($user_id);
            // 提交事务
            Db::commit();    
            return new Result(200, [], '');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
             return new Result(203, [], '转换失败!');
        }
    }
    //企业详情价格
    public static function companyInfo($user_id,$company_id)
    {
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $company=Db::name('company_config')->field('name,max1,max2,max3,max4,max5,abount')->where('id',$company_id)->find();
        if(!$company){
            return new Result(202, [], '该企业不存在!');
        }
        $stock=Db::name('stock')->where('company_id',$company_id)->select();
        $sale_count_stock=-1;
        $sale_min_price=-1;
        $sale_max_price=-1;
        $sale_open_price=-1;
        $sale_day_stock=-1;
        foreach ($stock as $k => $v) {
            if($k==0){
                $sale_count_stock=0;
                $sale_open_price=0.00;
                $sale_day_stock=0;
                $sale_min_price=$v['point_price'];
                $sale_max_price=$v['point_price'];
            }
            if($v['num']=='1') $sale_open_price=$v['point_price'];
            if($v['stock']>0){
                $sale_count_stock+=$v['stock'];
                $sale_min_price=$v['point_price']<$sale_min_price?$v['point_price']:$sale_min_price;
                $sale_max_price=$v['point_price']>$sale_max_price?$v['point_price']:$sale_max_price;
            }
        }
        $day_start_time=strtotime(date('Y-m-d').' 00:00:00');
        $day_end_time=strtotime(date('Y-m-d').' 23:59:59');
        $sale_day_stock=Db::name('buy_stock')->alias('a')->join('stock b','a.stock_id=b.id','LEFT')->where(['b.company_id'=>$company_id,'a.addtime'=>['>=',$day_start_time],'a.addtime'=>['<=',$day_end_time]])->sum('a.stock');
        $company['sale_count_stock']=$sale_count_stock;
        $company['sale_min_price']=$sale_min_price;
        $company['sale_max_price']=$sale_max_price;
        $company['sale_open_price']=$sale_open_price;
        $company['sale_day_stock']=$sale_day_stock;

        $company['url']='index/index/appCompany/company_id/'.$company_id;
        return new Result(200, $company, '');
    }
    //我的股权下七代奖励记录
    public static function stockRewardList($user_id,$offset,$pageSize)
    {
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $list=Db::name('stock_reward')->alias('a')->join('buy_stock b','a.buy_stock_id=b.id','LEFT')->join('stock c','b.stock_id=c.id','LEFT')->join('users d','b.user_id=d.id','LEFT')->join('company_config e','c.company_id=e.id','LEFT')->where('a.user_id',$user_id)->field('a.id,a.addtime,a.point,a.level,b.sn,b.stock,b.point_price,c.type,c.company_id,d.id as sale_id,d.username as sale_username,d.nickname as sale_nickname,e.name as company_name')->order('a.addtime DESC')->limit($offset,$pageSize)->select();
        if($list!==false){
            $list=phoneDis($list,false,array('sale_username'));
            return new Result(200, $list, '');
        }else{
            return new Result(202, [], '数据获取失败!');
        }
    } 
    //股权交易记录
    public static function mytrande($user_id,$company_id,$offset,$pageSize)
    {
        $company_id=intval($company_id);
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $map=['a.user_id|b.user_id'=>$user_id];
        if($company_id>0){
            $company=Db::name('company_config')->where('id',$company_id)->find();
            if(!$company){
                return new Result(203, [], '该企业不存在!');
            }
            $map['b.company_id']=$company_id;
        }
        
        $list=Db::name('buy_stock')->alias('a')->join('stock b','a.stock_id=b.id','LEFT')->join('company_config c','b.company_id=c.id','LEFT')->where($map)->field('a.id,a.addtime,a.stock,a.point_price,a.user_id,b.user_id as sale_id,b.company_id,c.name as company_name')->order('a.addtime DESC')->limit($offset,$pageSize)->select();
        if($list!==false){
            foreach ($list as $k => $v) {
                if($v['user_id']==$user_id && $v['sale_id']!=$user_id){
                    $list[$k]['status']='0';//买入
                }elseif($v['user_id']!=$user_id && $v['sale_id']==$user_id){
                    $list[$k]['status']='1';//卖出
                }
                unset($list[$k]['user_id']);
                unset($list[$k]['sale_id']);
            }
            return new Result(200, $list, '');
        }else{
            return new Result(202, [], '数据获取失败!');
        }
    }
    //企业股权交易明细记录
    public static function companyTrande($user_id,$company_id,$offset,$pageSize)
    {
        $company_id=intval($company_id);
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        if($company_id>0){
            $company=Db::name('company_config')->where('id',$company_id)->find();
            if(!$company){
                return new Result(203, [], '该企业不存在!');
            }
            $map['b.company_id']=$company_id;
        }
        
        $list=Db::name('buy_stock')->alias('a')->join('stock b','a.stock_id=b.id','LEFT')->join('users c','a.user_id=c.id','LEFT')->where($map)->field('a.id,a.addtime,a.stock,a.point_price,a.user_id,b.type,c.username')->order('a.addtime DESC')->limit($offset,$pageSize)->select();
        if($list!==false){
            $list=phoneDis($list);
            return new Result(200, $list, '');
        }else{
            return new Result(202, [], '数据获取失败!');
        }
    }
    //我的股权
    public static function mystock($user_id,$offset,$pageSize)
    {
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $list=Db::name('users_stock')->alias('a')->join('company_config b','a.company_id=b.id','LEFT')->where('a.user_id',$user_id)->field('a.id,a.company_id,b.name as company_name,a.stock')->order('a.id DESC')->limit($offset,$pageSize)->select();
        foreach ($list as $k => $v) {
            $stock=Db::name('stock')->where(['type'=>'1','user_id'=>$user_id,'company_id'=>$v['company_id'],'stock'=>['>','0']])->select();
            if($stock){
                $list[$k]['status']=1;
                $sale_stock=0;
                foreach ($stock as $key => $value) {
                    $sale_stock+=$value['stock'];    
                }
                $list[$k]['sale_stock']=$sale_stock;
            }else{
                $list[$k]['status']=0;
                $list[$k]['sale_stock']=0;
            }
        }
        return new Result(200, $list, '');
    }
    //用户挂卖
    public static function saleStock($user_id,$users_stock_id,$amount,$point_price)
    {
        $amount=intval($amount);
        $point_price=floatval($point_price);
        $point_price=number_format($point_price,2,'.','');
        $now=time();
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $users_stock=Db::name('users_stock')->where(['id'=>$users_stock_id,'user_id'=>$user_id])->find();
        if(!$users_stock){
            return new Result(202, [], '股权信息不存在!');
        }
        $company=Db::name('company_config')->where('id',$users_stock['company_id'])->find();
        if(!$company){
            return new Result(207, [], '股权所属企业不存在!');
        }
        if($company['stage']=='0'){
            $stock=Db::name('stock')->where(['type'=>'0','company_id'=>$users_stock['company_id'],'stock'=>['>','0']])->order('addtime desc,id desc')->limit(1)->find();
            if($point_price!=$stock['point_price']){
                return new Result(214, [], '挂卖单价必须和当前股权企业发售价格相同，当前当前股权企业发售价格为（'.$stock['point_price'].'元）!');
            }
        }elseif($company['stage']=='1'){
            if($point_price<$company['min_price']){
                return new Result(215, [], '挂卖单价不得低于该股权所属企业的自由竞价最低价（'.$company['min_price'].'元）!');
            }
        }elseif($company['stage']=='2'){
            return new Result(216, [], '该企业属于静默期，不能挂卖');
        }
        if($amount<=0){
            return new Result(203, [], '挂卖数量至少为100!');
        }
        if($amount%100!=0){
            return new Result(204, [], '挂卖数量必须被100整除!');
        }
        if($users_stock['stock']<$amount){
            return new Result(205, [], '挂卖数量超过拥有数量!');
        }
        // 启动事务
        Db::startTrans();
        try{
            Db::name('users_stock')->update(['id'=>$users_stock_id,'stock'=>$users_stock['stock']-$amount]);
            $old_stock=Db::name('stock')->where(['type'=>'1','user_id'=>$user_id,'company_id'=>$users_stock['company_id'],'point_price'=>$point_price])->find();
            if($old_stock){
                Db::name('stock')->where('id',$old_stock['id'])->update(['stock'=>$old_stock['stock']+$amount]);
            }else{
                Db::name('stock')->insert([
                    'user_id'=>$user_id,
                    'type'=>'1',
                    'company_id'=>$users_stock['company_id'],
                    'point_price'=>$point_price,
                    'stock'=>$amount,
                    'addtime'=>$now
                ]);
            }
            // 提交事务
            Db::commit(); 
            return new Result(200, [], '');   
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(210, [], '挂卖失败!');
        }
    }
    //用户挂卖列表
    public static function saleStockList($user_id,$company_id,$offset,$pageSize)
    {
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $where=['a.user_id'=>$user_id,'a.type'=>'1','a.stock'=>['>',0]];
        if($company_id>0){
            $where['a.company_id']=$company_id;
        }
        $list=Db::name('stock')->alias('a')->join('company_config b','a.company_id=b.id','LEFT')->where($where)->field('a.id,a.addtime,a.stock,a.point_price,a.company_id,b.name as company_name')->order('a.addtime DESC')->limit($offset,$pageSize)->select();
        if($list!==false){
            return new Result(200, $list, '');
        }else{
            return new Result(202, [], '数据获取失败!');
        }
    }
    //用户取消挂卖
    public static function cancelStock($user_id,$stock_id)
    {
        $now=time();
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        $stock=Db::name('stock')->where(['id'=>$stock_id,'user_id'=>$user_id,'stock'=>['>',0]])->find();
        if(!$stock){
            return new Result(202, [], '挂卖的股权信息不存在!');
        }
        $company=Db::name('company_config')->where('id',$stock['company_id'])->find();
        if(!$company){
            return new Result(203, [], '股权所属企业不存在!');
        }
        if($stock['is_force']=='1'){
            return new Result(204, [], '强制卖出的企业股权不可取消!');
        }
        // 启动事务
        Db::startTrans();
        try{
            Db::name('stock')->update(['id'=>$stock_id,'stock'=>'0']);
            Db::name('users_stock')->where(['user_id'=>$user_id,'company_id'=>$stock['company_id']])->setInc('stock',$stock['stock']);
            // 提交事务
            Db::commit(); 
            return new Result(200, [], '');   
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(206, [], '取消挂卖失败!');
        }
    }
    //购买
    public static function buy($user_id,$stock_id,$amount,$payment_password)
    {
        $amount=intval($amount);
        $now=time();
        $user=Db::name('users')->where('id',$user_id)->find();
        if(!$user){
            return new Result(201, [], '登录用户不存在，请刷新页面重试!');
        }
        if($user['is_activated']=='0'){
            return new Result(219, [], '账号未激活，不能购买，请一次性充值满1000即可激活账号!');
        }
        $stock=Db::name('stock')->where('id',$stock_id)->find();
        if(!$stock){
            return new Result(205, [], '股权挂卖信息不存在!');
        }
        $company=Db::name('company_config')->find($stock['company_id']);
        if(!$company){
            return new Result(206, [], '股权所属企业不存在!');
        }
        if($company['stage']=='0'){
            $stock_user=Db::name('stock')->where(['type'=>'1','company_id'=>$stock['company_id'],'stock'=>['>',0]])->select();
            if($stock_user && $stock['type']=='0'){
                return new Result(230, [], '请先购买用户发售的股份，用户售卖完之后才能继续购买企业发售的股份');
            }
           /* //判断购买时间限制
            $h_time=date('H',$now);
            $i_time=date('i',$now);
            if($user['is_vip']=='1'){
                if(!in_array($h_time,array('9','14','20')) || !(in_array($h_time,array('8','13','19'))) && in_array($i_time,array('55','56','57','58','59'))){
                    return new Result(210, [], '该时间段不可购买平台发售期的企业股权!');
                }
            }else{
                if(!in_array($h_time,array('9','14','20'))){
                    return new Result(210, [], '该时间段不可购买平台发售期的企业股权!');
                }
            }*/
        }elseif($company['stage']=='1'){
            /*//判断购买时间限制
            $h_time=date('H',$now);
            $i_time=date('i',$now);
            if($user['is_vip']=='1'){
                if((!in_array($h_time,array('8','13','19')) || in_array($i_time,array('45','46','47','48','49','50','51','52','53','54','55','56','57','58','59'))) || (!in_array($h_time,array('7','12','18')) || !in_array($i_time,array('55','56','57','58','59')))){
                    return new Result(211, [], '该时间段不可购买自由竞价期的企业股权!');
                }
            }else{
                if(!in_array($h_time,array('8','13','19')) || in_array($i_time,array('45','46','47','48','49','50','51','52','53','54','55','56','57','58','59'))){
                    return new Result(211, [], '该时间段不可购买自由竞价期的企业股权!');
                }
            }*/
        }elseif($company['stage']=='2'){
            return new Result(215, [], '股权所属企业处于静默期不能购买!');
        }
        //判断购买时间限制
        $w_time=date("w",$now);
        $h_time=date('H',$now);
        $i_time=date('i',$now);
        if($w_time=='0'){
            return new Result(210, [], '周日不交易!');
        }else{
            if($user['is_vip']=='1'){
                if(!in_array($h_time,array('9','10','11','12','13','14','15','16','17')) || !(in_array($h_time,array('8'))) && in_array($i_time,array('55','56','57','58','59'))){
                    return new Result(210, [], '该时间段不可购买企业股权!');
                }
            }else{
                if(!in_array($h_time,array('9','10','11','12','13','14','15','16','17'))){
                    return new Result(210, [], '该时间段不可购买企业股权!');
                }
            }
        }
        if($stock['type']=='1'){
            if($stock['user_id']==$user_id){
                return new Result(214, [], '不能购买自己挂买的股份!');
            }
            $sale_user=Db::name('users')->where('id',$stock['user_id'])->find();
            if(!$sale_user){
                return new Result(208, [], '售卖用户不存在!');
            }
        }
        if($amount<=0){
            return new Result(213, [], '购买数量至少为100!');
        }
        if($amount%100!=0){
            return new Result(202, [], '购买数量必须被100整除!');
        }
        if($stock['stock']<$amount){
            return new Result(203, [], '购买数量超过出售数量!');
        }
        $count=intval($amount*$stock['point_price']);
        if($user['point']<$count){
            return new Result(209, [], '久映贝不足，请先充值!');
        }
        if($user['payment_password']!=md5($payment_password)){
            return new Result(204, [], '支付密码错误!');
        }
        //前置股份检测
        $need_target_company=Db::name('company_config')->find($company['need_target_company_id']);
        if($need_target_company){
            $need_target_stock=$amount*$company['need_target_stock_rate'];
            $target_stock=Db::name('users_stock')->where(['user_id'=>$user_id,'company_id'=>$company['need_target_company_id']])->find();
            if($target_stock){
                $target_stock_num=$target_stock['stock'];
            }else{
                $target_stock_num=0;
            }
            if($need_target_stock>$target_stock_num){
                return new Result(216, [], '前置股份不足!');
            }
        }
        //限制购买上限
        $sum_stock=Db::name('buy_stock')->alias('a')->join('stock b','a.stock_id=b.id','LEFT')->where(['b.company_id'=>$stock['company_id'],'a.user_id'=>$user_id])->sum('a.stock');
        if($company['limit_user_buy_number']!='0' && $sum_stock+$amount>$company['limit_user_buy_number']){
            return new Result(217, [], '购买该企业的股份数量已超过单人购买限制!');
        }
        // 启动事务
        Db::startTrans();
        try{
            if($stock['type']=='0'){//平台出售
                //交易号
                $sn='SC'.date('Ymd').mt_rand('10000000','99999999');
                //修改企业挂卖剩余股份
                $j_stock=$stock['stock']-$amount;
                Db::name('stock')->where('id',$stock_id)->update(['stock'=>$j_stock]);
                //判断是否进入下一个段为售卖
                if($j_stock==0 && $company['remain_stock']>0){
                    $new_stock=$company['count_stock']/$company['n'];
                    $new_point_price=$stock['point_price']+0.01;
                    Db::name('company_config')->where('id',$stock['company_id'])->update(['remain_stock'=>$company['remain_stock']-$new_stock]);
                    Db::name('stock')->insert([
                        'type'=>'0',
                        'company_id'=>$stock['company_id'],
                        'stock'=>$new_stock,
                        'point_price'=>$new_point_price,
                        'addtime'=>$now,
                        'num'=>$stock['num']+1
                    ]);
                     Db::name('company_config')->where('id',$stock['company_id'])->update(['min_price'=>$new_point_price]);
                } 
                //平台发售转到自由竞价，记录最低价
                if($j_stock==0 && $company['remain_stock']==0){   
                    Db::name('company_config')->where('id',$stock['company_id'])->update(['stage'=>'1','min_price'=>$stock['point_price']+0.01]);
                }
                //减去用户消费久映贝和企业增加久映贝
                Db::name('users')->update(['id'=>$user_id,'point'=>$user['point']-$count]);
                $saler_point=0;
                $saler_money=0.00;
                $company_get=$count;
                $platform_get=0.00;
                Db::name('company_config')->update(['id'=>$stock['company_id'],'money'=>$company['money']+$company_get]);
                //用户购买记录
                Db::name('buy_stock')->insert([
                    'stock_id'=>$stock_id,
                    'user_id'=>$user_id,
                    'stock'=>$amount,
                    'point_price'=>$stock['point_price'],
                    'addtime'=>$now,
                    'sn'=>$sn,
                    'saler_point'=>$saler_point,
                    'saler_money'=>$saler_money,
                    'company_get'=>$company_get,
                    'platform_get'=>$platform_get
                ]);
                //添加用户自己的股权仓库
                $users_stock=Db::name('users_stock')->where(['user_id'=>$user_id,'company_id'=>$stock['company_id']])->find();
                if($users_stock){
                    Db::name('users_stock')->where('id',$users_stock['id'])->setInc('stock',$amount);
                }else{
                    Db::name('users_stock')->insert([
                        'user_id'=>$user_id,
                        'company_id'=>$stock['company_id'],
                        'stock'=>$amount
                    ]);
                }
            }elseif($stock['type']=='1'){//用户出售
                //交易号
                $sn='SU'.date('Ymd').mt_rand('10000000','99999999');
                //修改用户挂卖剩余股份
                $j_stock=$stock['stock']-$amount;
                Db::name('stock')->where('id',$stock_id)->update(['stock'=>$j_stock]);
                //减去用户消费久映贝和售卖用户增加金钱和增值贝
                $stock_config = Db::name('stock_config')->where('id', 1)->find();
                Db::name('users')->update(['id'=>$user_id,'point'=>$user['point']-$count]);
                $saler_incr=floor($count*(1-$stock_config['stock_buy_platform_get_rate'])*$stock_config['stock_buy_saler_incr_rate']);
                $saler_money=number_format($count*(1-$stock_config['stock_buy_platform_get_rate'])*(1-$stock_config['stock_buy_saler_incr_rate']),2,'.','');
                $company_get=0.00;
                $platform_get=number_format($count*$stock_config['stock_buy_platform_get_rate'],2,'.','');
                $saler=Db::name('users')->find($stock['user_id']);
                Db::name('users')->update(['id'=>$stock['user_id'],'money'=>$saler['money']+$saler_money,'incr'=>$saler['incr']+$saler_incr]);
                //用户等级更新
                self::userLevel($stock['user_id']);
                //用户购买记录
                Db::name('buy_stock')->insert([
                    'stock_id'=>$stock_id,
                    'user_id'=>$user_id,
                    'stock'=>$amount,
                    'point_price'=>$stock['point_price'],
                    'addtime'=>$now,
                    'sn'=>$sn,
                    'saler_incr'=>$saler_incr,
                    'saler_money'=>$saler_money,
                    'company_get'=>$company_get,
                    'platform_get'=>$platform_get
                ]);
                //添加用户自己的股权仓库
                $users_stock=Db::name('users_stock')->where(['user_id'=>$user_id,'company_id'=>$stock['company_id']])->find();
                if($users_stock){
                    Db::name('users_stock')->where('id',$users_stock['id'])->setInc('stock',$amount);
                }else{
                    Db::name('users_stock')->insert([
                        'user_id'=>$user_id,
                        'company_id'=>$stock['company_id'],
                        'stock'=>$amount
                    ]);
                }
                //购买七代奖励(奖励售卖者的上级)
                self::stockReward($sn);
            }
            // 提交事务
            Db::commit();    
            return new Result(200, ['sn'=>$sn], '');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(207, [], '购买失败!');
        }
    }
    //计算会员增值贝等级
    public static function userLevel($user_id){
        $user=Db::name('users')->where('id',$user_id)->find();
        $recommend=Db::name('users')->where('id',$user['recommend_uid'])->find();
        if($recommend){
            $recommend_downs_sum=Db::name('users')->where('recommend_uid',$user['recommend_uid'])->sum('incr');
            if($recommend['incr']>=24000 && $recommend_downs_sum>=200000){
                Db::name('users')->where('id',$user['recommend_uid'])->setField('level', '5');
            }elseif($recommend['incr']>=12000 && $recommend_downs_sum>=100000){
                Db::name('users')->where('id',$user['recommend_uid'])->setField('level', '4');
            }elseif($recommend['incr']>=6000 && $recommend_downs_sum>=30000){
                Db::name('users')->where('id',$user['recommend_uid'])->setField('level', '3');
            }elseif($recommend['incr']>=3000 && $recommend_downs_sum>=10000){
                Db::name('users')->where('id',$user['recommend_uid'])->setField('level', '2');
            }elseif($recommend['incr']>=1000){
                Db::name('users')->where('id',$user['recommend_uid'])->setField('level', '1');
            }else{
                Db::name('users')->where('id',$user['recommend_uid'])->setField('level', '0');
            }
        }
        $user_downs_sum=Db::name('users')->where('recommend_uid',$user_id)->sum('incr');
        if($user['incr']>=24000 && $user_downs_sum>=200000){
            Db::name('users')->where('id',$user_id)->setField('level', '5');
        }elseif($user['incr']>=12000 && $user_downs_sum>=100000){
            Db::name('users')->where('id',$user_id)->setField('level', '4');
        }elseif($user['incr']>=6000 && $user_downs_sum>=30000){
            Db::name('users')->where('id',$user_id)->setField('level', '3');
        }elseif($user['incr']>=3000 && $user_downs_sum>=10000){
            Db::name('users')->where('id',$user_id)->setField('level', '2');
        }elseif($user['incr']>=1000){
            Db::name('users')->where('id',$user_id)->setField('level', '1');
        }else{
            Db::name('users')->where('id',$user_id)->setField('level', '0');
        }
    }
    //七代奖励
    protected static function stockReward($sn){
        $now=time();
        $buy_stock=Db::name('buy_stock')->alias('a')->join('stock b','a.stock_id=b.id','LEFT')->where('a.sn',$sn)->field('a.*,b.user_id as sale_id')->find();
        $count=$buy_stock['point_price']*$buy_stock['stock'];
        $parentUsersAll=self::parentUsersAll($buy_stock['sale_id']);
        foreach ($parentUsersAll as $k => $v) {
            if($v['is_agent']=='0') continue; //判断是否是代理，不是则跳过
            if($k=='lv1'){
                $rate=0.03;
                $level=1;
            }elseif($k=='lv2'){
                $rate=0.02;
                $level=2;
            }elseif($k=='lv3'){
                $rate=0.01;
                $level=3;
            }elseif($k=='lv4'){
                $rate=0.005;
                $level=4;
            }elseif($k=='lv5'){
                $rate=0.005;
                $level=5;
            }elseif($k=='lv6'){
                $rate=0.005;
                $level=6;
            }elseif($k=='lv7'){
                $rate=0.005;
                $level=7;
            }
            $point=floor($count*$rate);//向下取整
            if($point>0){
                Db::name('stock_reward')->insert([
                    'buy_stock_id'=>$buy_stock['id'],
                    'point'=>$point,
                    'user_id'=>$v['id'],
                    'level'=>$level,
                    'addtime'=>$now,
                ]);
                Db::name('users')->where('id',$v['id'])->update(['point'=>$v['point']+$point]);
            }
        }
    }
    //上十八代成员
   /* protected static function parentUsersEigtheen($user_id,$result=[]){
        $user=Db::name('users')->field('recommend_uid')->where('id',$user_id)->find();
        if($user){
             $recommend=Db::name('users')->where('id',$user['recommend_uid'])->find();
             if($recommend){
                $result[]=$recommend;
                if(count($result)>=18){
                    return $result;
                }else{
                    return self::parentUsersEigtheen($recommend['id'],$result);
                }
             }else{
                return $result;
             }
        }else{
            return $result;
        }
    }*/
    //上七代成员
    protected static  function parentUsersAll($user_id){
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
                                if($user_3['recommend_uid']>0){
                                    $user_4=Db::name('users')->field('password,payment_password',true)->where('id',$user_3['recommend_uid'])->find();
                                    if($user_4){
                                        $data['lv4']=$user_4;
                                        if($user_4['recommend_uid']>0){
                                            $user_5=Db::name('users')->field('password,payment_password',true)->where('id',$user_4['recommend_uid'])->find();
                                            if($user_5){
                                                $data['lv5']=$user_5;
                                                if($user_5['recommend_uid']>0){
                                                    $user_6=Db::name('users')->field('password,payment_password',true)->where('id',$user_5['recommend_uid'])->find();
                                                    if($user_6){
                                                        $data['lv6']=$user_6;
                                                        if($user_6['recommend_uid']>0){
                                                            $user_7=Db::name('users')->field('password,payment_password',true)->where('id',$user_6['recommend_uid'])->find();
                                                            if($user_7){
                                                                $data['lv7']=$user_7;
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
            }else{
                return $data;
            }
        }else{
            return $data;
        }
    }
}