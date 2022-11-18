<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\TestData;
use think\Response;

/**
 * 示例接口
 */
class Demo extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test', 'test1', 'test2'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['test2'];

    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSummary  (测试描述信息)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/demo/test/id/{id}/name/{name})
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type="integer", required=true, description="会员ID")
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function test()
    {
        \Stripe\Stripe::setApiKey(config('stripe.privateKey'));
        // Use an existing Customer ID if this is a returning customer.
        $customer = \Stripe\Customer::create();
        $ephemeralKey = \Stripe\EphemeralKey::create(
            [
                'customer' => $customer->id,
            ],
            [
                'stripe_version' => '2022-08-01',
            ]);
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => 1099,
            'currency' => 'cny',
            'customer' => $customer->id,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            //'confirm' => true
        ]);
        dump($paymentIntent);
        $data = [
            'paymentIntent' => $paymentIntent->client_secret,
            'ephemeralKey' => $ephemeralKey->secret,
            'customer' => $customer->id,
            'publishableKey' => config('stripe.publishableKey')
        ];
        $this->success('返回成功', $data);
    }

    /**
     * 无需登录的接口
     *
     */
    public function test1()
    {
        $begin = time();
        $nowNum = session('data_num') + 150000;
        $data1 = [
            'cate_id' => random_int(15, 18),
            'title' => $nowNum.'万数据测试',
            'content' => $nowNum.'万数据测试',
            'description' => $nowNum.'万数据测试'
        ];
        for ($j=1; $j<=500;$j++){
            $data[] = $data1;
        }
        for ($i=1;$i<=300;$i++){
            model(TestData::class)->saveAll($data);
        }
        $end = time();
        $s = bcsub($end,$begin);
        session('data_num', $nowNum);
        echo $nowNum;
        $this->success('返回成功', ['action' => "用时{$s}秒"]);
    }

    /**
     * 需要登录的接口
     *
     */
    public function test2()
    {
        $begin = time();
        $result = TestData::with(['category'])->whereLike('title', '%150万数据%')->paginate(500);//where('cate_id', 16)
        $end = time();
        $this->success('返回成功', ['use_time' => bcsub($end, $begin), 'data' => $result]);
    }

    /**
     * 需要登录且需要验证有相应组的权限
     *
     */
    public function test3()
    {
        $this->success('返回成功', ['action' => 'test3']);
    }

}
