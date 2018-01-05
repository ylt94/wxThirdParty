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
    private $auditid= 423128637;
    private $authorizer_appid='wx58d58336c2cd0bbb';
    private $authorizer_access_token='5_wqzEvcMDw03Q28wUY1E6Gjd8nEQUSpFCmoOLsn-YFzfMe3aM8R4xa_ARs8gE_vODsEuufMgvctLR-KdAPPkDwpr5lVGMQB3y8OaNRpmh5QOAi60QsYzjwDSP4iLbHBAM3RZCufRocyzUo6Z8QTWjAKDEHK';
    private $authorizer_refresh_token='refreshtoken@@@ofN63J1K1GBZo4xHA9oNDdfe-HmlIEY4FC7ArNjtnbs';
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
     * 获取authorizer_access_token(授权方接口调用凭据，扫码授权回调API)
     * @param string $auth_code
     * @return array
     */
    public function getComponentAuthorizerToken(){
        $all = Request::all();
        $return = $this->wx->getAuthorizerToken($all);
        //保存刷新令牌,授权方appid(sql)
        /* 
         ***************
        */
        $access_token_cache_name='access_token';
        Cache::store('file')->put($access_token_cache_name,$return['authorization_info']['authorizer_access_token'], 120);
        Cache::store('file')->put('access_refresh_token',$return['authorization_info']['authorizer_refresh_token'],180);
        //$this->getAuthorizerBasicInfo($return);
        //$data = $this->UploadAuthorizerTemplate($return);
        //$this->bindComponentTester($return['authorization_info']['authorizer_access_token']);
        return $this->getTemplatePage();
        return $return;
    }

    /**
     * 获取authorizer_access_token(授权方接口调用凭据，本地缓存的token)
     * @param string $user_id ?
     * @return string
     */

     public function getCacheAccessToken(){
        $access_token=Cache::store('file')->get('access_token');
        if(!$access_token){
            $access_refresh_token=Cache::store('file')->get('access_refresh_token');
            $params=array(
                'authorizer_appid'=>$this->authorizer_appid,
                'access_refresh_token'=>$access_refresh_token
            );
            $result=$this->getRefreshAccessToken($params);
            $access_token=$result['authorizer_access_token'];
            Cache::store('file')->put('access_token',$result['authorizer_access_token'], 120);
            Cache::store('file')->put('access_refresh_token',$result['authorizer_refresh_token'], 180);
        }
        return $access_token;
     }

    /**
     * 获取authorizer_access_token(授权方接口调用凭据,过期刷新)
     * @param string $authorizer_refresh_token
     * @param string $authorizer_appid
     * @return string
     */
    
     public function getRefreshAccessToken($params){
        return $this->wx->refreshAccessToken($params);
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

    /**
     * 获取小程序的第三方提交代码的页面配置
     * @param string $access_token
     * @return array
     */

    public function getTemplatePage(){
        $access_token=$this->getCacheAccessToken();
        return $this->wx->getTemplatePage($access_token);
    }

     /**
     * 获取授权小程序帐号的可选类目
     * @param string $access_token
     * @return array
     */

    public function getTemplateCategory(){
        $access_token=$this->getCacheAccessToken();
        return $this->wx->getTemplateCategory($access_token);
    }

    /**
     * 将第三方提交的代码包提交审核
     * @param array $item_list
     * @param string $access_token
     * @return array
     */

    public function submitTemplateAudit(){
        $access_token=$this->getCacheAccessToken();
        $page_list_result=$this->getTemplatePage();
        if(!$page_list_result['errcode']&&$page_list_result['errmsg']=='ok'){
            $page_list=$page_list_result['page_list'];
        }

        $category_list_result=$this->getTemplateCategory();
        if(!$category_list_result['errcode']&&$category_list_result['errmsg']=='ok'){
            $category_list=$category_list_result['category_list'];
        }
        $item_list=array();
        foreach($page_list as $k => $item){
            $item_list[$k]['address']=$item;
            $item_list[$k]['tag']='test';
            $item_list[$k]['first_class']=$category_list[0]['first_class'];
            $item_list[$k]['second_class']=$category_list[0]['second_class'];
            $item_list[$k]['first_id']=$category_list[0]['first_id'];
            $item_list[$k]['second_id']=$category_list[0]['second_id'];
            $item_list[$k]['title']='wechat';
        }
        return $this->wx->submitTemplateAudit($access_token,$item_list);
    }


    /**
     * 查询某个指定版本的审核状态
     * @param array $auditid
     * @param string $access_token
     * @return array
     */
    public function getAuditStatus(){
        $access_token=$this->getCacheAccessToken();
        $auditid=$this->auditid;
        return $this->wx->getAuditStatus($access_token,$auditid);
    }

    /**
     * 查询最新一次提交的审核状态
     * @param string $access_token
     * @return array
     */
    public function getLatestAuditStatus(){
        $access_token=$this->getCacheAccessToken();
        return $this->wx->getLatestAuditStatus($access_token);
    }
}