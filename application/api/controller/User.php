<?php

namespace app\api\controller;

use app\admin\model\auction\Goods;
use app\admin\model\auction\Order;
use app\admin\model\Handbook;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\GoodsPriceLog;
use app\common\model\Message;
use app\common\model\RechargeOrder;
use app\common\model\ScoreLog;
use BaconQrCode\Common\ErrorCorrectionLevel;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use fast\Random;
use think\Config;
use think\Db;
use think\Exception;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->param('account');
        $password = $this->request->param('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $invite_code 邀请码
     */
    public function register()
    {
        $username = $this->request->param('username');
        $password = $this->request->param('password');
        $email = $this->request->param('email');
        $mobile = $this->request->param('mobile');
        $birthday = $this->request->param('birthday');
        $bio = $this->request->param('bio', '');
        $cnName = $this->request->param('cn_name', '');
        $enName = $this->request->param('en_name', '');
        $companyName = $this->request->param('company_name', '');
        $inviteCode = $this->request->param('invite_code', '');
        //$code = $this->request->post('code');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($email && !Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
//        $ret = Sms::check($mobile, $code, 'register');
//        if (!$ret) {
//            $this->error(__('Captcha is incorrect'));
//        }
        $ret = $this->auth->register($bio, $cnName, $enName, $birthday, $companyName, $inviteCode, $username, $password, $email, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }


    /**
     * 用户信息
     */
    public function user_info()
    {
        $id = $this->auth->id;
        $userInfo = \app\common\model\User::field('id,username,nickname,company_name,cn_name,en_name,email,mobile,avatar,level,birthday,bio,score')->find($id);
        $this->success('success', $userInfo);
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->param('username');
        $email = $this->request->param('email', '');
        $mobile = $this->request->param('mobile', '');
        $birthday = $this->request->param('birthday', '');
        $bio = $this->request->param('bio', '');
        $cnName = $this->request->param('cn_name', '');
        $enName = $this->request->param('en_name', '');
        $companyName = $this->request->param('company_name', '');
        $avatar = $this->request->param('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if($email){
            $exists = \app\common\model\User::where('email', $email)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Email already exists'));
            }
            $user->email = $email;
        }
//        if ($nickname) {
//            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
//            if ($exists) {
//                $this->error(__('Nickname already exists'));
//            }
//            $user->nickname = $nickname;
//        }
        if($mobile) $user->mobile = $mobile;
        if($birthday) $user->birthday = $birthday;
        if($cnName) $user->cn_name = $cnName;
        if($enName) $user->en_name = $enName;
        if($companyName) $user->company_name = $companyName;
        if($avatar) $user->avatar = $avatar;
        if($bio) $user->bio = $bio;
        $user->save();
        $this->success('success');
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @param string $email   邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 修改密码
     *
     * @ApiMethod (PUT)
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function changepwd()
    {
        $type = $this->request->param("type");
        $mobile = $this->request->param("mobile");
        $email = $this->request->param("email");
        $newpassword = $this->request->param("newpassword");
        $captcha = $this->request->param("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'changepwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'changepwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Change password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }


    /**
     * 重置密码
     *
     * @ApiMethod (PUT)
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd()
    {
        $type = $this->request->param("type");
        $mobile = $this->request->param("mobile");
        $email = $this->request->param("email");
        $newpassword = $this->request->param("newpassword");
        $captcha = $this->request->param("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 个人推广码
     * @ApiMethod (GET)
     */
    public function promotion_code()
    {
        $user = $this->auth->getUser();
        if(empty($user['qr_code'])){
            $writer = new PngWriter();
            //创建实例
            $qrCode = QrCode::create($user['invite_code'])
                ->setSize(300)
                ->setMargin(10)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());
            // 判断是否有这个文件夹  没有的话就创建一个
            if(!is_dir("uploads/qrcode")){
                mkdir("uploads/qrcode");
            }
            //设置二维码图片名称，以及存放的路径
            $filename = '/uploads/qrcode/'.time().rand(10000,9999999).'.png';
            $result = $writer->write($qrCode);
            header('Content-Type: ' . $result->getMimeType());
            $result->saveToFile(ROOT_PATH . '/public' . $filename);
            $user->save(['qr_code' => $filename]);
            $user['qr_code'] = $filename;
        }
        $this->success('success', ['qr_code' => cdnurl($user['qr_code'], true), 'invite_code' => $user['invite_code']]);
    }

    /**
     * 在线充值
     * @ApiMethod (POST)
     */
    public function recharge()
    {
        $user = $this->auth->getUser();
        $money = $this->request->param('money');
        if(!$money || !is_numeric($money) || $money <= 0) $this->error(__('Incorrect recharge amount'));
        try{
            $orderNo = get_order_no();
            \Stripe\Stripe::setApiKey(config('stripe.privateKey'));
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $money * 100, //充值金额
                'currency' => 'hkd', //币种
                'receipt_email' => $user['email'],
                'metadata' => [
                    'order_no' => $orderNo
                ],
            ]);
            $data = [
                'order_no' => $orderNo,
                'user_id'  => $this->auth->id,
                'money'  => $money
            ];
            $result = RechargeOrder::create($data);
            if(!$result){
                $this->error(__('Operation failed'));
            }
            $this->success('success', ['client_secret' => $intent->client_secret]);//确认支付客户密钥
        }catch (Exception $exception){
            $this->error($exception->getMessage());
        }
    }

    /**
     * @ApiMethod (GET)
     * 积分明细
     * @throws \think\exception\DbException
     */
    public function score_log()
    {
        $type = $this->request->param('type', 0);
        $where['user_id'] = ['=', $this->auth->id];
        if($type){
            if(!in_array($type, [1,2])) $this->error(__('Invalid parameters'));
            $where['score'] = [$type == 1 ? '>' : '<', 0];
        }

        $result = ScoreLog::where($where)->paginate(10)->toArray();
        if(!$result){
            $this->error(__('Operation failed'));
        }
        $this->success('success', $result);
    }

    /**
     * 用户系统消息
     * @throws \think\exception\DbException
     */
    public function message()
    {
        $result = Message::field('content,status,createtime')->where('user_id', $this->auth->id)->paginate(10)->toArray();
        if(!$result){
            $this->error(__('Operation failed'));
        }
        $this->success(__('Get data success'), $result);
    }

    /**
     * 设置已读
     */
    public function set_read()
    {
        $param = $this->request->param();
        if(!isset($param['ids']) || !is_array($param['ids'])) $this->error(__('Invalid parameters'));

        $result = Message::whereIn('id', $param['ids'])->update(['status' => 2, 'updatetime' => time()]);
        if($result === false){
            $this->error(__('Operation failed'));
        }
        $this->success('success');
    }

    /**
     * 删除消息(软删除)
     */
    public function del_msg()
    {
        $param = $this->request->param();
        if(!isset($param['ids']) || !is_array($param['ids'])) $this->error(__('Invalid parameters'));

        $result = Message::whereIn('id', $param['ids'])->update(['deletetime' => time()]);
        if($result === false){
            $this->error(__('Operation failed'));
        }
        $this->success('success');
    }

    /**
     * 我的订单
     * @throws \think\exception\DbException
     */
    public function get_owner_order()
    {
        $list = Order::alias('order')->field('id,order_no,total_score,address,express_no,status,createtime')->with(['goods' => function($query){
            $query->alias('goods')->withField('title,images,is_order');
        }])->where('order.user_id', $this->auth->id)->order('order.id', 'desc')->paginate(10)->toArray();
        if(!empty($list['data'])){
            foreach ($list['data'] as &$value){
                $images = explode(',', $value['goods']['images']);
                foreach ($images as &$val){
                    $val = cdnurl($val, true);
                }
                $value['goods']['images'] = $images;
            }
        }
        if(!is_array($list)){
            $this->error(__('Operation failed'));
        }
        $this->success(__('Get data success'), $list);
    }

    /**
     * 订单详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function order_detail()
    {
        $id = $this->request->param('id', 0);
        if(!$id) $this->error(__('Invalid parameters'));
        $detail = Order::alias('order')->field('id,user_id,order_no,total_score,receive_name,phone,address,express_no,status,createtime')->with(['goods' => function($query){
            $query->alias('goods')->withField('title,images,is_order');
        }])->where('order.id', $id)->find();
        if(!$detail) $this->error(__('Order does not exist'));
        if($detail->user_id != $this->auth->id) $this->error(__('You can only operate your own order'));
        $images = explode(',', $detail->goods->images);
        foreach ($images as &$val){
            $val = cdnurl($val, true);
        }
        $detail->goods->images = $images;
        $this->success(__('Get data success'), $detail);
    }

    /**
     * 设置订单收货信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function set_order_address()
    {
        $id = $this->request->param('id', 0);
        $receiveName = $this->request->param('receive_name', '');
        $phone = $this->request->param('phone', '');
        $address = $this->request->param('address', '');
        if(!$receiveName) $this->error(__('The receive_name cannot be blank'));
        if(!$phone) $this->error(__('The receive_phone cannot be blank'));
        if(!$address) $this->error(__('The address cannot be blank'));
        if(!$id) $this->error(__('Invalid parameters'));
        $order = Order::find($id);
        if(!$order) $this->error(__('Order does not exist'));
        if($order->status == 2) $this->error(__('The order has been shipped and the address cannot be modified'));
        if($order->user_id != $this->auth->id) $this->error(__('You can only operate your own order'));
        $order->receive_name = $receiveName;
        $order->phone = $phone;
        $order->address = $address;
        $result = $order->isUpdate(true)->save();
        if($result === false) $this->error(__('Operation failed'));
        $this->success('success');
    }
    

    /**
     * 我的发布商品
     * @throws \think\exception\DbException
     */
    public function get_owner_goods()
    {
        $list = Goods::field('id,title,start_price,now_price,begin_time,end_time,content,images,is_order,status,createtime')->with(['category' => function($query){
            $query->withField('name');
        }])->where('user_id', $this->auth->id)->order('sort', 'desc')->paginate(10)->toArray();
        if(!empty($list)){
            foreach ($list['data'] as &$val){
                $images = explode(',', $val['images']);
                foreach ($images as &$value){
                    $value = cdnurl($value, true);
                }
                $val['images'] = $images;
            }
        }
        if(!is_array($list)){
            $this->error(__('Operation failed'));
        }
        $this->success(__('Get data success'), $list);
    }

    /**
     * 取消拍卖/作废
     */
    public function cancel()
    {
        $id = $this->request->param('id', 0);
        if(!$id) $this->error(__('Invalid parameters'));
        Db::startTrans();
        try{
            $goods = Goods::find($id);
            if(!$goods) $this->error(__('Goods does not exist'));
            if($goods->user_id != $this->auth->id) $this->error(__('You can only operate your own goods'));
            if($goods->is_order != 0) $this->error(__('Operation is not allowed in the commodity status'));
            $goods->status = 0;
            //如果有用户竞价解冻相应冻结积分
            $maxUser = GoodsPriceLog::where('goods_id', $goods->id)->order('price', 'desc')->find();
            if($maxUser)  \app\common\model\User::lockScore(-$maxUser->price, $maxUser->user_id);
            $result = $goods->isUpdate(true)->save();
            if($result === false){
                Db::rollback();
                $this->error(__('Cancel failed'));
            }
            Db::commit();
            $this->success('success');
        }catch (Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
    }


    /**
     * 发货
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deliver()
    {
        $id = $this->request->param('id', 0);
        $status = (int)$this->request->param('status', 1);
        $expressNo = $this->request->param('express_no', '');
        if(!$id) $this->error(__('Invalid parameters'));
        $goods = Goods::find($id);
        if($goods->user_id != $this->auth->id) $this->error(__('You can only operate your own goods'));
        if($goods->status != 0) $this->error(__('Operation is not allowed in the commodity status'));
        $order = Order::where('goods_id', $goods->id)->find();
        if(!$order) $this->error(__('Order does not exist'));
        $order->status = $status;
        $order->express_no = $expressNo;
        $result = $order->isUpdate(true)->save();
        if($result === false){
            $this->error(__('Operation failed'));
        }
        $this->success('success');
    }

    /**
     * 获取用户推送设置
     */
    public function get_push_setting()
    {
        $info = \app\common\model\User::field('id,is_allow_push,is_new_stuff,is_order_and_return,level')->find($this->auth->id);
        if(!$info) $this->error(__('Operation failed'));
        $this->success('success', $info);
    }

    /**
     * 推送消息设置
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function set_push()
    {
        $isAllowPush = (int)$this->request->param('is_allow_push');
        $isNewStuff = (int)$this->request->param('is_new_stuff');
        $isOrderAndReturn = (int)$this->request->param('is_order_and_return');
        if(!in_array($isAllowPush, [0,1]) || !in_array($isNewStuff, [0,1]) || !in_array($isOrderAndReturn, [0,1])){
            $this->error(__('Invalid parameters'));
        }

        $user = \app\common\model\User::find($this->auth->id);
        $user->is_allow_push = $isAllowPush;
        $user->is_new_stuff = $isNewStuff;
        $user->is_order_and_return = $isOrderAndReturn;
        if($user->save() === false){
            $this->error(__('Operation failed'));
        }
        $this->success('success');
    }

    /**
     * 会员手册
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function handbook()
    {
        $info = Handbook::field('content')->find(1);
        if($info === false) $this->error(__('Operation failed'));
        $this->success('success', $info);
    }

}
