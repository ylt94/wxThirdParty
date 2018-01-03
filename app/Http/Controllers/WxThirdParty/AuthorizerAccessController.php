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

    public function getComponentAuthorizerToken(){
        $all=json_decode(Request::all(),true);
        $return=$this->wx->getAuthorizerToken($all);
        return ['success'=>1,'all'=>$return];
    }
}