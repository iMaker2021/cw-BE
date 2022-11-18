<?php

namespace app\api\controller;

use app\admin\model\auction\Goods;
use app\common\controller\AliPush;
use app\common\controller\Api;
use app\common\controller\JPush;
use app\common\exception\UploadException;
use app\common\library\Upload;
use app\common\model\Area;
use app\common\model\AuctionOrder;
use app\common\model\GoodsPriceLog;
use app\common\model\Message;
use app\common\model\RechargeOrder;
use app\common\model\Version;
use fast\Random;
use think\Config;
use think\Db;
use think\Exception;
use think\Hook;
use think\Log;

/**
 * 公共接口
 */
class Common extends Api
{
    protected $noNeedLogin = ['init', 'notify', 'order'];
    protected $noNeedRight = '*';

    /**
     * 加载初始化
     *
     * @param string $version 版本号
     * @param string $lng     经度
     * @param string $lat     纬度
     */
    public function init()
    {
        if ($version = $this->request->request('version')) {
            $lng = $this->request->request('lng');
            $lat = $this->request->request('lat');

            //配置信息
            $upload = Config::get('upload');
            //如果非服务端中转模式需要修改为中转
            if ($upload['storage'] != 'local' && isset($upload['uploadmode']) && $upload['uploadmode'] != 'server') {
                //临时修改上传模式为服务端中转
                set_addon_config($upload['storage'], ["uploadmode" => "server"], false);

                $upload = \app\common\model\Config::upload();
                // 上传信息配置后
                Hook::listen("upload_config_init", $upload);

                $upload = Config::set('upload', array_merge(Config::get('upload'), $upload));
            }

            $upload['cdnurl'] = $upload['cdnurl'] ? $upload['cdnurl'] : cdnurl('', true);
            $upload['uploadurl'] = preg_match("/^((?:[a-z]+:)?\/\/)(.*)/i", $upload['uploadurl']) ? $upload['uploadurl'] : url($upload['storage'] == 'local' ? '/api/common/upload' : $upload['uploadurl'], '', false, true);

            $content = [
                'citydata'    => Area::getCityFromLngLat($lng, $lat),
                'versiondata' => Version::check($version),
                'uploaddata'  => $upload,
                'coverdata'   => Config::get("cover"),
            ];
            $this->success('', $content);
        } else {
            $this->error(__('Invalid parameters'));
        }
    }

    /**
     * 上传文件
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function upload()
    {
        Config::set('default_return_type', 'json');
        //必须设定cdnurl为空,否则cdnurl函数计算错误
        Config::set('upload.cdnurl', '');
        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            if (!Config::get('upload.chunking')) {
                $this->error(__('Chunk file disabled'));
            }
            $action = $this->request->post("action");
            $chunkindex = $this->request->post("chunkindex/d");
            $chunkcount = $this->request->post("chunkcount/d");
            $filename = $this->request->post("filename");
            $method = $this->request->method(true);
            if ($action == 'merge') {
                $attachment = null;
                //合并分片文件
                try {
                    $upload = new Upload();
                    $attachment = $upload->merge($chunkid, $chunkcount, $filename);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success(__('Uploaded successful'), ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
            } elseif ($method == 'clean') {
                //删除冗余的分片文件
                try {
                    $upload = new Upload();
                    $upload->clean($chunkid);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            } else {
                //上传分片文件
                //默认普通上传文件
                $file = $this->request->file('file');
                try {
                    $upload = new Upload($file);
                    $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            }
        } else {
            $attachment = null;
            //默认普通上传文件
            $file = $this->request->file('file');
            try {
                $upload = new Upload($file);
                $attachment = $upload->upload();
            } catch (UploadException $e) {
                $this->error($e->getMessage());
            }

            $this->success(__('Uploaded successful'), ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
        }

    }

    /**
     * 支付回调
     */
    public function notify()
    {
        \Stripe\Stripe::setApiKey(config('stripe.privateKey'));
        $payload = @file_get_contents('php://input');
        $event = null;
        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($payload, true)
            );
        } catch(\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
                $this->paySuccess($paymentIntent);
                break;
            default:
                echo 'Received unknown event type ' . $event->type;
        }

        http_response_code(200);
    }

    /**
     * 支付成功变更积分
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function paySuccess($data)
    {
        $orderNo = $data->metadata->order_no;
        $recharge = RechargeOrder::where('order_no', $orderNo)->find();
        if($recharge->status == 1){
            return true;
        }
        Db::startTrans();
        try{
            $recharge->save(['status' => 1, 'paytime' => time(), 'updatetime' => time(), 'notify' => json_encode($data)]);
            $userModel = new \app\common\model\User();
            $userModel::score($recharge->money, $recharge->user_id, '用戶充值');
            $user = $userModel::find($recharge->user_id);
            //充值成功發送推薦獎勵
            if($user->spread_uid){
                $userModel::score(bcdiv($recharge->money, 10, 2), $user->spread_uid, '推薦充值獎勵');
            }
            Db::commit();
            return true;
        }catch (Exception $exception){
            Db::rollback();
        }
    }

    /**
     * 定时任务扫描生成订单
     * @throws \think\exception\DbException
     */
    public function order()
    {
        $list = Goods::where('is_order', 0)->order('end_time', 'asc')->paginate(100);
        foreach ($list as $val){
            if($val->end_time < time()){
                Db::startTrans();
                try{
                    $priceLog = GoodsPriceLog::where('goods_id', $val->id)->order('price', 'desc')->find();
                    //无人出价更新记录为流拍继续下一个商品
                    if(!$priceLog){
                        Goods::where('id', $val->id)->update(['is_order' => 2]);
                        continue;
                    }

                    $data = [
                        'order_no' => get_order_no(),
                        'goods_id' => $val->id,
                        'user_id'  => $priceLog->user_id,
                        'total_score' => $priceLog->price
                    ];
                    //检测默认收货信息
                    $addr = \app\common\model\Address::where('id', $priceLog->user_id)->where('is_default', 1)->find();
                    if(!empty($addr)){
                        $data['receive_name'] = $addr->receive_name;
                        $data['phone'] = $addr->phone;
                        $data['address'] = $addr->address;
                    }
                    //生成订单
                    AuctionOrder::create($data);
                    //扣除用户积分
                    \app\common\model\User::score(-$priceLog->price, $priceLog->user_id, '買得拍賣商品 '.$val->title);
                    //解除已冻结积分
                    \app\common\model\User::lockScore(-$priceLog->price, $priceLog->user_id);
                    //发布人增加拍卖成交积分
                    \app\common\model\User::score($priceLog->price, $val->user_id, '賣出拍賣商品 '.$val->title);
                    //给用户发送站内信
                    $msgData = [
                        'user_id' => $priceLog->user_id,
                        'content' => '拍賣品 '.$val->title.' 您已競拍成功'
                    ];
                    Message::create($msgData);
                    //更新商品信息为已生成订单
                    $result = Goods::where('id', $val->id)->update(['is_order' => 1]);
                    if(!$result) Log::notice('更新状态失败，商品id->'.$val->id);
                    Db::commit();
                    Log::notice('订单生成成功，商品id->'.$val->id);
                }catch (Exception $exception){
                    Db::rollback();
                    Log::error('订单生成失败'.var_export($exception->getMessage()));
                }
            }
        }
    }


    public function test_push()
    {
        $push = new AliPush();
        $result = $push->pushMsg(['190e35f7e0a1867a445'], '这是一个测试推送消息');
        dump($result);
    }
}
