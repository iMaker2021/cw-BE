<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/2
 * Time: 18:17
 */

namespace app\common\controller;


use JPush\Client;

class JPush
{
    protected $client = null;
    public function _initialize(){
        $this->client = new Client(config('JPush.appKey'),config('JPush.appSecret'));
    }

    public function pushAuctionPriceUpdate()
    {
        $this->client->push(['user_id']);
    }
}