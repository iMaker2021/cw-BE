<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/2
 * Time: 18:17
 */

namespace app\common\controller;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\Log;

class AliPush
{
    protected $accessKeyId = null;
    protected $accessKeySecret = null;
    protected $appKey = null;
    protected $regionId = null;
    public function __construct(){
        $this->appKey = config('aliPush.appKey');
        $this->accessKeyId = config('aliPush.accessKeyId');
        $this->accessKeySecret = config('aliPush.accessKeySecret');
        $this->regionId = config('aliPush.regionId');
    }

    /**
     * @param array $users
     * @param string $msg
     */
    public function pushMsg(array $users, string $msg = '')
    {
        AlibabaCloud::accessKeyClient("{$this->accessKeyId}", "{$this->accessKeySecret}")
            ->regionId($this->regionId)
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Push')
                // ->scheme('https') // https | http
                ->version('2016-08-01')
                ->action('Push')
                ->method('POST')
                ->host('cloudpush.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => $this->regionId,
                        'AppKey' => $this->appKey,
                        'PushType' => "NOTICE",
                        'DeviceType' => "ALL",
                        'Target' => "DEVICE",
                        'TargetValue' => "deviceIds",
                        'Body' => $msg,
                        'Title' => "拍卖",
                    ],
                ])
                ->request();
            return $result->toArray();
        } catch (ClientException $e) {
            Log::info(var_export($e->getErrorMessage()));
        } catch (ServerException $e) {
            Log::info(var_export($e->getErrorMessage()));
        }
    }
}