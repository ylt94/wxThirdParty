<?php
namespace App\Http\Controllers\WxThirdParty\Services;
// include_once "wxCryptSDK/wxBizMsgCrypt.php";
// include_once "wxCryptSDK/http.php";
use Cache;
use App\Http\Controllers\WxThirdParty\Services\wxCryptSDK\WXBizMsgCrypt;
use App\Http\Controllers\WxThirdParty\Services\wxCryptSDK\HTTP;

class WxThirdPartyService{

    public $appId = '';
    public $appsecret='';    
    public $encodingAesKey = '';
    public $token = '';

    public function __construct(){
        $this->appId = env('appId');
        $this->appsecret=env('appsecret');    
        $this->encodingAesKey = env('encodingAesKey');
        $this->token = env('token');
    }
    
    private function getWxMsgData(){
        $format=Cache::store('file')->get('verify_ticket_post_data');
        $get_query_args=Cache::store('file')->get('verify_ticket_get_data');
        return array($format, $get_query_args);
    }

    public function getComponentVerifyTicket(){
        $pc = new WXBizMsgCrypt($this->token, $this->encodingAesKey, $this->appId);
        $msg = '';
        $wx_msg_data=$this->getWxMsgData();
        $errCode = $pc->decryptMsg($wx_msg_data[1]['msg_signature'], $wx_msg_data[1]['timestamp'], $wx_msg_data[1]['nonce'], $wx_msg_data[0], $msg);
        $component_verify_ticket='';
        $xml=simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        foreach($xml->children() as $k=>$v) {
            if($k=='ComponentVerifyTicket'){
                $component_verify_ticket=(string)$v;
            }
        }
        return $component_verify_ticket;
    }

    public function getComponentAccessToken(){
        $http= new HTTP();
        $data['component_access_token']=Cache::store('file')->get('component_access_token');
        if(!$data['component_access_token']){
            $component_verify_ticket=$this->getComponentVerifyTicket();
            $data=$http->https_post('https://api.weixin.qq.com/cgi-bin/component/api_component_token',json_encode([//需要JSON格式！！！
                "component_appid"=>$this->appId ,
                "component_appsecret"=> $this->appsecret, 
                "component_verify_ticket"=> $component_verify_ticket
            ]));
            Cache::store('file')->put('component_access_token', $data['component_access_token'], 120);
        }
        return $data;
    }

    public function getPreAuthCode(){
        $http= new HTTP();
        $componentAccessTokenData=$this->getComponentAccessToken();
        if(empty($componentAccessTokenData['component_access_token'])){
            return $componentAccessTokenData;
        }
        $component_access_token=$this->getComponentAccessToken()['component_access_token'];
        $data=$http->https_post('https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.$component_access_token.'',json_encode([//需要JSON格式！！！
            "component_appid"=>$this->appId ,
        ]));
        return $data;
    }

    public function getAuthorizerToken($params){
        $http= new HTTP();
        if(!$params['auth_code']){
            return ['status'=>-1,'msg'=>'缺少授权码'];
        }
        $component_access_token=$this->getComponentAccessToken()['component_access_token'];
        $auth_code=$params['auth_code'];
        $data=$http->https_post('https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$component_access_token.'',json_encode([//需要JSON格式！！！
            "component_appid"=>$this->appId ,
            "authorization_code"=> $auth_code
        ]));
        
        return $data;

    }

    public function getAuthorizerInfo($authorizer_appid,$component_access_token){
        $http = new HTTP();
        $result=$http->https_post('https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.$component_access_token.'',json_encode([//需要JSON格式！！！
            "component_appid"=>$this->appId,
            "authorizer_appid"=> $authorizer_appid
        ]));
        return $result;
    }

    public function getWxExtJsonString($authorizer_appid){
        $string='{
            "extEnable": false,
            "directCommit": false,  
            "extAppid":"'.$authorizer_appid.'",
            
                "ext":{
        
                }
        }';
        return $string;
    }

    public function UploadTemplate($params){
        $http = new HTTP();
        $result=$http->https_post('https://api.weixin.qq.com/wxa/commit?access_token='.$params['access_token'].'',json_encode([//需要JSON格式！！！
            'template_id'=>0,
            'ext_json'=>$params['ext_json_str'],
            "user_version"=>"V1.0",
            "user_desc"=>"开发测试11"
        ]));
        return $result;
    }

    public function bindComponentTesterService($params){
        $http = new HTTP();
        $result=$http->https_post('https://api.weixin.qq.com/wxa/bind_tester?access_token='.$params['access_token'].'',json_encode([//需要JSON格式！！！
            'wechatid'=>$params['wechat_id']
        ]));
        return $result;
    }

}