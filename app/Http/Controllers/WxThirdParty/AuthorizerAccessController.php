<?php

namespace App\Http\Controllers\WxThirdParty;

use App\Http\Controllers\Controller;
use Cache;
use App\Http\Controllers\WxThirdParty\Services\WxThirdPartyService;
use App\Http\Controllers\WxThirdParty\Services\wxCryptSDK\HTTP;
use Request;

class AuthorizerAccessController extends Controller
{
    private $wx;
    public function __construct(){
        $this->wx=new WxThirdPartyService();
    }
    public function getComponentLoginPage(){
        //$wx=new WxThirdPartyService();
        $pre_auth_code=$this->wx->getPreAuthCode()['pre_auth_code'];
        $authdomain=env('authdomain').'';
        $url="https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=".$this->wx->appId."&pre_auth_code=$pre_auth_code&redirect_uri=$authdomain&auth_type=3";
        return view('test', ['url' => $url]);
    }

    public function getComponentAuthorizerToken(){//获取authorizer_access_token(接口调用凭据)
        $all=Request::all();
        $return=$this->wx->getAuthorizerToken($all);
        //Cache::store('file')->put($return['authorization_info']['authorizer_appid'],$return['authorization_info']['authorizer_access_token'], 120);
        //$this->getAuthorizerBasicInfo($return);
        $data=$this->UploadAuthorizerTemplate($return);
        return $data;
    }

    public function getAuthorizerBasicInfo($params){//获取授权方基本信息
        $http = new HTTP();
        $component_access_token=$this->wx->getComponentAccessToken()['component_access_token'];
        $authorizer_appid=$params['authorization_info']['authorizer_appid'];
        $data=$http->https_post('https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.$component_access_token.'',json_encode([//需要JSON格式！！！
            "component_appid"=>$this->wx->appId,
            "authorizer_appid"=> $authorizer_appid
        ]));
        return $data;
    }

    public function UploadAuthorizerTemplate($params){
        $http = new HTTP();
        $ext_json_str=$this->wx->getWxExtJsonString($params);
        $authorizer_appid=$params['authorization_info']['authorizer_appid'];
        $access_token=$params['authorization_info']['authorizer_access_token'];
        //return json_decode(['ext_json'=>$ext_json_str]);
        $data=$http->https_post('https://api.weixin.qq.com/wxa/commit?access_token='.$access_token.'',json_encode([//需要JSON格式！！！
            'template_id'=>0,
            'ext_json'=>$ext_json_str,
            "user_version"=>"V1.0",
            "user_desc"=>"开发测试11"
        ]));
        if(!$data['errcode']&&$data['errmsg']=='ok'){
            return $this->getTemplateQrcode($access_token);
        }
    }

    public function getTemplateQrcode($access_token){
        $http = new HTTP();
        $data=$http->https_get('https://api.weixin.qq.com/wxa/get_qrcode?access_token='.$access_token);
        return $data;
    }
}