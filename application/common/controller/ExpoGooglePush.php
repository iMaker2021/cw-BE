<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/25
 * Time: 10:36
 */

namespace app\common\controller;

use ExpoSDK\Expo;
use ExpoSDK\ExpoMessage;
use think\Exception;
use think\Log;

class ExpoGooglePush
{
    /**
     * 消息推送
     * @param string $title
     * @param string $msg
     * @param array $users
     * @return bool|string
     */
    public function push(string $title, string $msg, array $usersToken) {
        try{
            $messages = [
//            [
//                'title' => '愛德生拍賣黨',
//                'to' => $usersToken,
//            ],
                new ExpoMessage([
                    'title' => $title,
                    'body' => $msg,
                ]),
            ];

            /**
             * These recipients are used when ExpoMessage does not have "to" set
             */
            //$defaultRecipients = $pushTokens;

            $result = (new Expo())->send($messages)->to($usersToken)->push();
            return $result;
        }catch (Exception $exception){
            return $exception->getMessage();
            Log::error('推送异常->'.var_export($usersToken));
        }


    }

}