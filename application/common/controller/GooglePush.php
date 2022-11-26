<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/25
 * Time: 10:36
 */

namespace app\common\controller;

class GooglePush
{
    /**
     * 消息推送
     * @param string $title
     * @param string $msg
     * @param array $users
     * @return bool|string
     */
    public function push(string $title, string $msg, array $users) {
        //发送push接口，project_id需要替换成自己项目的id
        $send_url = 'https://fcm.googleapis.com/v1/projects/'.config('google_push.project_id').'/messages:send';
        //推送参数
        $params = [
            "message" => [
                "token" => $users, //需要发送的设备号
                "notification" => [
                    "title" => $title,
                    "body" => $msg
                ],
                "data" => ''
            ]
        ];

        //获取令牌
        $accessToken = $this->getAccessToken();
        dump($accessToken);
        if (empty($accessToken)) {
            return false;
        }
        //header请求头，$accessToken 就是你上面获取的令牌
        $header = [
            'Content-Type' => 'application/json; UTF-8',
            'Authorization' => 'Bearer ' . $accessToken
        ];
        $response = curl_post($send_url, $header, $params);

        return $response;
    }

    /**
     * 获取access_token
     * @return bool|mixed
     */
    public function getAccessToken()
    {
        try {
            $cache_key = 'push:access_token:key';
            $accessToken = cache($cache_key);
            if (empty($token)) {
                //国内服务器需要使用代理，不然请求不了google接口
                $proxy = 'vmess://ew0KICAidiI6ICIyIiwNCiAgInBzIjogIlVTQSBDUDE2IiwNCiAgImFkZCI6ICJjcDE2LndvZGVzcy5kZSIsDQogICJwb3J0IjogIjg0NDMiLA0KICAiaWQiOiAiYjZlMjQ3OWYtYTFmYi00Yzc3LThjMDYtNjgwYTcxYWU3MmRkIiwNCiAgImFpZCI6ICI2NCIsDQogICJzY3kiOiAiYXV0byIsDQogICJuZXQiOiAid3MiLA0KICAidHlwZSI6ICJub25lIiwNCiAgImhvc3QiOiAiIiwNCiAgInBhdGgiOiAiL3dzLzY1anY2Zjg6OGIxODI0Mzc2NjIyODYzMzM3MTM3NDU3Nzc5YWU3NmUvIiwNCiAgInRscyI6ICJ0bHMiLA0KICAic25pIjogIiINCn0=';
                $httpClient = new \GuzzleHttp\Client([
                    'defaults' => [
                        'proxy' => $proxy,
                        'verify' => false,
                        'timeout' => 10,
                    ]
                ]);
                $client = new \Google_Client();
                $client->setHttpClient($httpClient);
                //引入配置文件
                $client->setAuthConfig(config('google_push.auth_key_file_path'));
                //设置权限范围
                $scope = 'https://www.googleapis.com/auth/firebase.messaging';
                $client->setScopes([$scope]);
                $client->fetchAccessTokenWithAssertion($client->getHttpClient());
                $token = $client->getAccessToken();
                if (empty($token) || empty($token['access_token']) || empty($token['expires_in'])) {
                    throw new \Exception('access_token is empty!');
                }
                //设置缓存
                cache($cache_key, $token['access_token'], $token['expires_in'] - 20);
                return $token['access_token'];
            }
            return $accessToken;
        } catch (\Exception $e) {
            return false;
        }
    }
}