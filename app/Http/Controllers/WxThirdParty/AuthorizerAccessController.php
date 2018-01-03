<?php

namespace App\Http\Controllers\WxThirdParty;

use App\Http\Controllers\Controller;
use Cache;
use App\Http\Controllers\WxThirdParty\Services\WxThirdPartyService;
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
        $this->getAuthorizerBasicInfo($return);
        return ['success'=>1,'all'=>$return];
    }

    public function getAuthorizerBasicInfo($params){//获取授权方基本信息
        $component_access_token=$this->wx->getComponentAccessToken()['component_access_token'];
        $authorizer_appid=$params['authorization_info']['authorizer_appid'];
        $data=$http->https_post('https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.$component_access_token.'',json_encode([//需要JSON格式！！！
            "component_appid"=>$this->wx->appId,
            "authorizer_appid"=> $authorizer_appid
        ]));
        var_dump($data);
        return $data;
    }

    public function bindXcxTester(){

    }
}