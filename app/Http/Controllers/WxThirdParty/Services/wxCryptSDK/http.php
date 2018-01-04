<?php
namespace App\Http\Controllers\WxThirdParty\Services\wxCryptSDK;

class HTTP{
    public function https_get($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $data = curl_exec($curl);
        // 获得响应结果里的：头大小
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        // 根据头大小去获取头信息内容
        $header = substr($data, 0, $headerSize);
        curl_close($curl);
        //显示获得的数据
        print_r("\n---------https get response---------\n");
        var_dump($header);
        print_r("\n---------https get end---------\n");
        return json_decode($data,true);
    }

    public function https_post($url, $post_data){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        // //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        // print_r("\n---------https post $url postdata---------\n");
        // print_r($post_data);        
        // print_r("\n---------https post response---------\n");
        // print_r($data);
        // print_r("\n---------https post $url end---------\n");
        return json_decode($data,true);
    }
}

