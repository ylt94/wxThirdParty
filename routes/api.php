<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/accept-wx-verify-ticket', 'WxThirdParty\VerifyTicketController@saveVerifyTicket');
Route::any('/get-component-login-page', 'WxThirdParty\AuthorizerAccessController@getComponentLoginPage');
Route::any('/get-component-authorizer-token', 'WxThirdParty\AuthorizerAccessController@getComponentAuthorizerToken');

Route::any('/get-cache-access-token', 'WxThirdParty\AuthorizerAccessController@getCacheAccessToken');
Route::any('/get-template-category', 'WxThirdParty\AuthorizerAccessController@getTemplateCategory');
Route::any('/get-template-page', 'WxThirdParty\AuthorizerAccessController@getTemplatePage');

