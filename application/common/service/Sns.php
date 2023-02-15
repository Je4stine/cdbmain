<?php

namespace app\common\service;

use anerg\OAuth2\OAuth;
class Sns extends \think\Controller
{
	private $config;
	
	/**
	 * 第三方登录，执行跳转操作
	 *
	 * @param string $name 第三方渠道名称，目前可用的为：weixin,qq,weibo,alipay,facebook,twitter,line,google
	 */
	public function login($name)
	{
		//获取配置
		$this->config = config('sns_' . $name);

		//设置回跳地址
		$this->config['callback'] = $this->makeCallback($name);
		
		//可以设置代理服务器，一般用于调试国外平台
//		$this->config['proxy'] = 'http://127.0.0.1:1080';
		
		/**
		 * 对于微博，如果登录界面要适用于手机，则需要设定->setDisplay('mobile')
		 *
		 * 对于微信，如果是公众号登录，则需要设定->setDisplay('mobile')，否则是WEB网站扫码登录
		 *
		 * 其他登录渠道的这个设置没有任何影响，为了统一，可以都写上
		 */
		return OAuth::$name($this->config)->setDisplay('mobile')->getRedirectUrl();
//		$this->redirect(OAuth::$name($this->config)->setDisplay('mobile')->getRedirectUrl());
		
		/**
		 * 如果需要微信代理登录，则需要：
		 *
		 * 1.将wx_proxy.php放置在微信公众号设定的回调域名某个地址，如 http://www.abc.com/proxy/wx_proxy.php
		 * 2.config中加入配置参数proxy_url，地址为 http://www.abc.com/proxy/wx_proxy.php
		 *
		 * 然后获取跳转地址方法是getProxyURL，如下所示
		 */
//		$this->config['proxy_url'] = 'http://www.abc.com/proxy/wx_proxy.php';
//		return redirect(OAuth::$name($this->config)->setDisplay('mobile')->getProxyURL());
	}
	
	public function callback($name)
	{
		//获取配置
		$this->config = config('sns_' . $name);
		
		//设置回跳地址
		$this->config['callback'] = $this->makeCallback($name);
		
		//获取格式化后的第三方用户信息
		$snsInfo = OAuth::$name($this->config)->userinfo();
		
		//获取第三方返回的原始用户信息
//		$snsInfoRaw = OAuth::$name($this->config)->userinfoRaw();
		//获取第三方openid
//		$openid = OAuth::$name($this->config)->openid();
		$this->createUrl($snsInfo,$name);
	}
	
	/**
	 * 生成回跳地址
	 *
	 * @return string
	 */
	private function makeCallback($name)
	{
		//注意需要生成完整的带http的地址
		return url('/app/callback/snsCallback',['name' => $name], '', true);
	}
	
	public function createUrl( $snsInfo ,$name) {
		$user_info = db()->name('user')->where(['openid' => $snsInfo['openid']])->find();
		//是否需要手机登录   1 需要  0不需要
		$data['is_phone'] = '1';
		$data['token'] = '';
		$data['openid'] = $snsInfo['openid'];
		if(!empty($user_info)){
			$data['is_phone'] = '0';
			$data['token'] = mwencrypt( $user_info['openid'] );
			$data['openid'] = '';
			cache("customer:{$this->oid}:{$user_info['openid']}",$user_info, 24 * 3600);
		}else{
			//授权数据存入缓存
			cache($data['openid'],json_encode($snsInfo),3600);
		}
		$url = 'https://app.getpowerbuddy.com/canada/success.html?is_phone='.$data['is_phone'].'&token='.$data['token'].'&openid='.$data['openid'].'&type='.$name;
		save_log('cadada_url',$url);
		$this->redirect ($url);
	}
	
	//客户端登录
	public function sns()
	{
//		$platform = $this->request->param('sns_platform');
		//默认微信客户端登录
		$platform = 'wechat';
		
		//获取本站的第三方登录配置
		$config = config($platform . '.' . Config::get($platform));
		// $config['proxy'] = 'http://127.0.0.1:1080';
		//QQ,Facebook,Line,要求客户端传递access_token即可
		$config['access_token'] = $this->request->param('code', '');
		//Twitter需要传递下面四个参数
//		$config['oauth_token']        = $this->request->param('oauth_token', '');
//		$config['oauth_token_secret'] = $this->request->param('oauth_token_secret', '');
//		$config['user_id']            = $this->request->param('user_id', '');
//		$config['screen_name']        = $this->request->param('screen_name', '');
		//其他和web登录一样，要求客户端传递code过来即可，可以是post也可以是get方式
		
		$snsInfo = OAuth::$platform($config)->userinfo();
		
		$this->createUrl($snsInfo);
	}
	
}