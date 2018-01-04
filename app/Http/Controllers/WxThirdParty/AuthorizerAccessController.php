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

    /**
     * 获取authorizer_access_token(接口调用凭据)
     * @param string $auth_code
     * @return array
     */
    public function getComponentAuthorizerToken(){
        $all = Request::all();
        $return = $this->wx->getAuthorizerToken($all);
        //Cache::store('file')->put($return['authorization_info']['authorizer_appid'],$return['authorization_info']['authorizer_access_token'], 120);
        //$this->getAuthorizerBasicInfo($return);
        $data = $this->UploadAuthorizerTemplate($return);
        $this->bindComponentTester($return['authorization_info']['authorizer_access_token']);
        return $data;
    }

     /**
     * 获取授权方信息
     * @param string $component_access_token
     * @param string $component_appid
     * @param string $authorizer_appid
     * @return array
     */
    public function getAuthorizerBasicInfo($params){
        //$params=$this->getComponentAuthorizerToken();
        $component_access_token = $this->wx->getComponentAccessToken()['component_access_token'];
        $authorizer_appid = $params['authorization_info']['authorizer_appid'];
        $data = $this->wx->getAuthorizerInfo($authorizer_appid,$component_access_token);
        return $data;
    }

     /**
     * 为授权方上传小程序模板
     * @param string $authorizer_access_token
     * @param string $template_id
     * @param string $ext_json
     * @param string $user_version
     * @param string $user_desc
     * @return array
     */
    public function UploadAuthorizerTemplate($params){
        //$params=$this->getComponentAuthorizerToken();
        $service_params=array();
        $authorizer_appid=$params['authorization_info']['authorizer_appid'];
        $ext_json_str=$this->wx->getWxExtJsonString($authorizer_appid);
        $service_params=[
            'access_token'=>$params['authorization_info']['authorizer_access_token'],
            'ext_json_str'=>$ext_json_str
        ];
        //return json_decode(['ext_json'=>$ext_json_str]);
        $data=$this->wx->UploadTemplate($service_params);
        if(!$data['errcode']&&$data['errmsg']=='ok'){
            return $this->getTemplateQrcode($service_params['access_token']);
        }
    }

     /**
     * 获取小程序体验二维码
     * @param string $authorizer_access_token
     * @return null
     */

    public function getTemplateQrcode($access_token){
        $http = new HTTP();
        header('Content-type: image/JPEG');
        echo $data=$http->https_get('https://api.weixin.qq.com/wxa/get_qrcode?access_token='.$access_token);
        // $url='https://api.weixin.qq.com/wxa/get_qrcode?access_token='.$access_token;
        // $result=$this->buildRequestForm(['access_token'=>$access_token],'GET',$url,true);
        // echo $result;
        // exit;
    }

    protected function buildRequestForm( array $param, $method, $target='',$jump=false) {
        $sHtml = "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><form id='autoSubmit' action='".$target."' method='".$method."'>";
    
        if ( !empty( $param ) ) {
            foreach( $param as $key => $value ) {
                $sHtml.= "<input type='hidden' name='".$key."' value='".urldecode($value)."'/>";
            }
        }
            $sHtml .= "</form>";

        if($jump) $sHtml = $sHtml."<script>document.getElementById(\"autoSubmit\").submit();</script>";

        return $sHtml;
    }

    /**
     * 手动添加小程序体验者
     * @param string $wechat_id
     * @param string $access_token
     * @return array
     */

    public function bindComponentTester($access_token){
        $wechat_id='akiraSyu';
        $service_params=array(
            'wechat_id'=>$wechat_id,
            'access_token'=>$access_token
        );
        $res=$this->wx->bindComponentTesterService($service_params);
        print_r($res);
    }
}