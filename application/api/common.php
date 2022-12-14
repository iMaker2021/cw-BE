<?php

/**
 * 根据用户id生成唯一邀请码
 * @param $userId
 * @return string
 */
function get_code_by_user_id($userId)
{
    $sourceString = 'E5FCDG3HQA4B1NOPIJ2RSTUV67MWX89KLYZ';
    $code = '';
    while ($userId > 0) {
        $mod = $userId % 35;
        $userId = ($userId - $mod) / 35;
        $code = $sourceString[$mod] . $code;
    }
    if (strlen($code) < 8)
        $code = str_pad($code, 8, '0', STR_PAD_LEFT);
    return $code;
}

/**
 * 根据邀请码解析user_id
 * @param $code
 * @return float|int
 */
function decode_user_id_by_code($code)
{
    $sourceString = 'E5FCDG3HQA4B1NOPIJ2RSTUV67MWX89KLYZ';
    //移除左侧的 0
    if (strrpos($code, '0') !== false)
        $code = substr($code, strrpos($code, '0') + 1);
    $len = strlen($code);
    $code = strrev($code);
    $num = 0;
    for ($i = 0; $i < $len; $i++) {
        $num += strpos($sourceString, $code[$i]) * pow(35, $i);
    }
    return $num;
}

/**
 * 生成唯一订单号
 * @return string
 */
function get_order_no(){
    @date_default_timezone_set("PRC");
    //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
    $order_id_main = date('YmdHis') . rand(10000000,99999999);
    //订单号码主体长度
    $order_id_len = strlen($order_id_main);

    $order_id_sum = 0;
    for($i=0; $i<$order_id_len; $i++){
        $order_id_sum += (int)(substr($order_id_main,$i,1));
    }
    //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
    return $order_id_main . str_pad((100 - $order_id_sum % 100) % 100,2,'0',STR_PAD_LEFT);
}