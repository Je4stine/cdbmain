<?php


namespace app\common\service;

use paragraph1\phpFCM\Client;
use paragraph1\phpFCM\Message;
use paragraph1\phpFCM\Recipient\Device;
use paragraph1\phpFCM\Notification;

class Push extends Base {
	var $ppp;
	
	public function __construct($uid)
	{
		parent::_initialize();
		
		$user_info = $this->db->name('user')
		                      ->where(['id' =>$uid])
		                      ->find();
		$this->ppp = [
			'Token' => $user_info['token'],
			'client' => $user_info['client']
		];
	}
	
	public function index( $msg = '' ) {
		$parems = [
			'Title' => '',
			'Content' =>$msg,
			'Token' =>$this->ppp['Token'],
		];
		if($this->ppp['client'] == 'ios'){
			return $this->iosPush($parems);
		}else{
			return $this->googlePush($parems);
		}
		return false;
	}
	
	
	public function googlePush( $data ) {
		$apiKey = 'AAAAcmUFT_g:APA91bGybvv08-O_NgLPVJdJJ6JSJhb-PW73hAcmouEyAeKmZkGJ-BqAHuaVbBypRM-iUytRGZyfQY2DFAgKKRi1pj4CQoBQQLbyWyb2mOByZCU1R0a3xPrAW3Is4Vh0jKAWDRXjMRqL';
		$client = new Client();
		$client->setApiKey( $apiKey );
		$client->injectHttpClient( new \GuzzleHttp\Client() );
		
		$note = new Notification( $data['Title'], $data['Content'] );
		$note->setIcon( 'myicon' )
		     ->setColor( '#ffffff' )
		     ->setBadge( 1 );
		
		$message = new Message();
		$message->addRecipient( new Device( $data['Token'] ) );
		$message->setNotification( $note )
		        ->setData( array( 'someId' => time() ) );
		
		$response = $client->send( $message );
		
		if ( 200 == $response->getStatusCode() ) {
			return true;
		}
		save_log( 'google_push', $response );
		
		return false;
	}
	
	public function iosPush( $data ) {
		// ??????????deviceToken???????????????
		$deviceToken = $data['Token'];

// Put your private key's passphrase here:
		$passphrase = '123456';

// Put your alert message here:
//		$message =  $data['Title'].'\br'.$data['Content'];
		$message = $data['Content'];

////////////////////////////////////////////////////////////////////////////////
		
		$ctx = stream_context_create();
		stream_context_set_option( $ctx, 'ssl', 'local_cert', '/www/wwwroot/india/extend/iospush/sum.pem' );
		stream_context_set_option( $ctx, 'ssl', 'passphrase', $passphrase );

// Open a connection to the APNS server
//??????????
		//$fp = stream_socket_client(?ssl://gateway.push.apple.com:2195?, $err, $errstr, 60, //STREAM_CLIENT_CONNECT, $ctx);
//?????????????appstore??????
		$fp = stream_socket_client(
			'ssl://gateway.sandbox.push.apple.com:2195', $err,
			$errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx );
		
		if ( ! $fp ) {
			save_log( 'ios_push', "Failed to connect: $err $errstr" . PHP_EOL );
			
			return false;
		}
// Create the payload body
		$body['aps'] = array(
			'alert' => $message,
			'sound' => 'default'
		);

// Encode the payload as JSON
		$payload = json_encode( $body );

// Build the binary notification
		$msg = chr( 0 ) . pack( 'n', 32 ) . pack( 'H*', $deviceToken ) . pack( 'n', strlen( $payload ) ) . $payload;

// Send it to the server
		$result = fwrite( $fp, $msg, strlen( $msg ) );
		
		fclose($fp);
		if ( ! $result ) {
			save_log( 'ios_push', 'Message not delivered' . PHP_EOL );
			save_log( 'ios_push', $result );
			
			return false;
		} else {
			return true;
		}
		
		
	}
	
}
