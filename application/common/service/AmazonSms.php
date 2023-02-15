<?php


namespace app\common\service;



use Aws\Sns\SnsClient;


class AmazonSms {


	public function index($phone = '',$message = 'Hello, world!'){
//		return ['code' => 1, 'msg' => '发送成功', '@metadata' => ['statusCode' => 200]];
		
		//region:区域信息
		//credentials : 证书
		//version:版本
		//debug:是否是debug
		$snsClient = new SnsClient([
			'region'      => 'eu-west-1',//这是亚马逊在新加坡的服务器，具体要根据情况决定
			'credentials' => [
				'key'         => 'AKIAJ2N62MBEIUNO3IYA',
				'secret'      => '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',
			],
			'version'     => '2010-03-31',    //一般在aws的官方api中会有关于这个插件的版本信息
			'debug'       => false,
		]);
		
		$args = [
			'Message' => $message,           // REQUIRED
			'PhoneNumber' => $phone,
		];
		
		return $snsClient->Publish($args)->toArray();
	}

}