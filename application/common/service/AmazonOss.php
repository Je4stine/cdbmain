<?php


namespace app\common\service;

vendor("Aws.aws-autoloader");
vendor("daniel-zahariev/php-aws-ses/src/SimpleEmailServiceMessage.php");

use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use PHPMailer\PHPMailer\PHPMailer;
use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;
use Aws\Exception\AwsException;


class AmazonOss {
    // 桶名称
    const AWS_BUCKET = 'go-battery-cl';

    private static $region = 'us-east-1';//'cn-north-1'
    private static $s3version = '2006-03-01';
    private static $profile = 'default';
    private static $_s3clients;

    private static function uploadDemo()
    {
        //上传到服务器的临时文件
        $file_name = $_FILES['aws_test']['tmp_name'];
        // 获取数据流
        $stream = file_get_contents($file_name);
        // 调用工具类上传
//        $name = AmazonOss::upload(AmazonOss::AWS_BUCKET_TEST, 'demo.jpg', $stream);
        $name = self::getObject(AmazonOss::AWS_BUCKET, 'text.txt');
        // 返回完整外网地址
        echo $name;
        exit;
    }


    /*
     * Desciption 使用该方法将文件上传到亚马逊S3,如果成功返回文件在S3中的地址；
     *
     * @param string $bucket AWS存储桶
     * @param string $filename 存储名称
     * @param string||stream $body  文件内容,写入到filename中的数据
     *
     * @return string File Full URL;
     *
     */

    public static function upload( $filename, $body)
    {
        try {
            $bucket = AmazonOss::AWS_BUCKET;
            $s3Client = AmazonOss::getS3Client($bucket);
            $key =  $filename;
            $uploader = new ObjectUploader(
                $s3Client, $bucket, $key, $body
            );
            $uploader->upload();
            $url = self::getObject($bucket, $key);
//            print_r($key);exit;
            // 返回完整外网地址
            return $key;
        } catch (\Exception $e) {
            echo ($e->getMessage());
//			LogHelper::info(
//				'文件上传失败 | '. $e->getMessage()
//			);
        }
        return '';
    }

    public static function getObject($bucket, $filename)
    {
        $s3Client = self::getS3Client($bucket);
        $result = $s3Client->getObject(
            [
                'Bucket'                     => $bucket,
                'Key'                        => $filename,
                'ResponseContentDisposition' => 'attachment; filename='
                    . $filename,
                'ResponseContentType'        => 'text/plain',
                'ResponseContentLanguage'    => 'en-US',
                'ResponseCacheControl'       => 'No-cache',
            ]
        );
    }

    private static function getS3Client($bucket)
    {
        if (!self::$_s3clients[$bucket] instanceof S3Client) {
            $credentials = self::getCredentials($bucket);
            self::$_s3clients[$bucket] = new S3Client(
                [
                    'region'      => self::$region,
                    'version'     => self::$s3version,
                    'credentials' => $credentials
                ]
            );
        }
        return self::$_s3clients[$bucket];
    }

    private static function getCredentials($bucket)

    {
        switch ($bucket) {
            case self::AWS_BUCKET:
                $credentials = [
                    'key'    => 'AKIAS3UXG475EKGIFEUY',
                    'secret' => '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'
                ];
                break;

            default:
                $credentials = [];
        }
        return $credentials;
    }

	public function sendEmail( $reciver ,$url ) {
		// Create an SesClient. Change the value of the region parameter if you're
// using an AWS Region other than US West (Oregon). Change the value of the
// profile parameter if you want to use a profile in your credentials file
// other than the default.
		$SesClient = new SesClient([
			'profile' => 'default',
			'version' => '2010-12-01',
			'region'  => 'us-east-1'
		]);

// Replace sender@example.com with your "From" address.
// This address must be verified with Amazon SES.
		$sender_email = 'master@app.batteryxchangeonline.com';

// Replace these sample addresses with the addresses of your recipients. If
// your account is still in the sandbox, these addresses must be verified.
		$recipient_emails = [$reciver];

// Specify a configuration set. If you do not want to use a configuration
// set, comment the following variable, and the
// 'ConfigurationSetName' => $configuration_set argument below.
		$configuration_set = 'ConfigSet';
		
		$subject = 'Reset Password';
		$plaintext_body = 'Reset Password' ;
		$html_body =  "<!DOCTYPE html>
<html lang=\"en\">
  <head>
    <meta charset=\"utf-8\" />
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" />
    <meta
      name=\"viewport\"
      content=\"width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no\"
    />

    <title></title>
    <style>
      * {
        margin: 0;
        padding: 0;
      }
      html,
      body{
        width: 100%;
        min-height: 100vh;
        background-color: #ffffff;
      }
      .wrap{
        padding: 0 15px 40px;
      }
      .logo_box{
        padding-top: 10px;
        box-sizing: border-box;
      }
      .logo_box .img{
        width: 200px;
        height: 86px;
      }
      .txt{
        font-size: 14px;
        color: #333333;
        line-height: 17px;
        font-weight: bold;
        margin-bottom: 15px;
      }
      .link{
        margin: 40px 0;
      }
      .link a{
        font-size: 14px;
      }
      hr{
        margin-bottom: 30px;
      }
    </style>
  </head>
  <body>
    <div class=\"wrap\">
      <div class=\"logo_box\">
        <img class=\"img\" src=\"https://app.batteryxchangeonline.com/send_email/img/2.png\">
      </div>
      <p class=\"txt\">Hello there,</p>
      <p class=\"txt\">
        We've just received a \"Forgot Password\" request for the account associated with this email address. If you made this request, use the link below to reset your password.
      </p>
      <p class=\"link\">
        <a href=\"{$url}\">Reset Password</a>
      </p>
      <p class=\"txt\">Note: This link above will expire after 3 hours. </p>
      <p class=\"txt\">Thank you, <br/> BatteryXchange</p>

      <hr/>

      <p class=\"txt\">BatteryXchange is committed to preventing fraudulent emails. Emails from BatteryXchange will always contain your full name. </p>
      <p class=\"txt\">Please do not reply to this email. To get in touch with us, click Help Center</p>

    </div>

  </body>
</html>
";
		$char_set = 'UTF-8';
		
		try {
			$result = $SesClient->sendEmail([
				'Destination' => [
					'ToAddresses' => $recipient_emails,
				],
				'ReplyToAddresses' => [$sender_email],
				'Source' => $sender_email,
				'Message' => [
					'Body' => [
						'Html' => [
							'Charset' => $char_set,
							'Data' => $html_body,
						],
						'Text' => [
							'Charset' => $char_set,
							'Data' => $plaintext_body,
						],
					],
					'Subject' => [
						'Charset' => $char_set,
						'Data' => $subject,
					],
				],
				// If you aren't using a configuration set, comment or delete the
				// following line
				//'ConfigurationSetName' => $configuration_set,
			]);
			$messageId = $result['MessageId'];
			echo("Email sent! Message ID: $messageId"."\n");
		} catch (AwsException $e) {
			// output error message if fails
			echo $e->getMessage();
			echo("The email was not sent. Error message: ".$e->getAwsErrorMessage()."\n");
			echo "\n";
		}
    }
	public function email( $reciver ,$url) {
		// Replace sender@example.com with your "From" address.
		// This address must be verified with Amazon SES.
		$sender     = 'master@app.batteryxchangeonline.com';
		$sendername = 'BatteryXchange Inc';

		// Replace recipient@example.com with a "To" address. If your account
		// is still in the sandbox, this address must be verified.
		$recipient = 'charge@batteryxchange.co';

		// Specify a configuration set.
		//$configset = 'ConfigSet';

		// Replace us-west-2 with the AWS Region you're using for Amazon SES.
		$region = 'us-east-1';
		
		$subject = "You're trying to retrieve your password";
		
		$htmlbody = <<<EOD
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="viewport"
      content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"
    />
  </head>
  <body>
    <div style="padding: 0 15px 40px;">
      <div style="padding-top: 10px;">
        <img style="width: 200px;height: 86px;" src="https://app.batteryxchangeonline.com/send_email/img/2.png">
      </div>
      <p style="font-size: 14px;color: #333333;line-height: 17px;font-weight: bold;margin-bottom: 15px;">Hello there,</p>
      <p style="font-size: 14px;color: #333333;line-height: 17px;font-weight: bold;margin-bottom: 15px;">
        We've just received a "Forgot Password" request for the account associated with this email address. If you made this request, use the link below to reset your password.
      </p>
      <p style="margin: 40px 0;">
        <a href="{$url}" style="font-size: 14px;">Reset Password</a>
      </p>
      <p style="font-size: 14px;color: #333333;line-height: 17px;font-weight: bold;margin-bottom: 15px;">Note: This link above will expire after 3 hours. </p>
      <p style="font-size: 14px;color: #333333;line-height: 17px;font-weight: bold;margin-bottom: 15px;">Thank you, <br/> BatteryXchange</p>

      <hr style="margin-bottom: 30px;" />

      <p style="font-size: 14px;color: #333333;line-height: 17px;font-weight: bold;margin-bottom: 15px;">BatteryXchange is committed to preventing fraudulent emails. Emails from BatteryXchange will always contain your full name. </p>
      <p style="font-size: 14px;color: #333333;line-height: 17px;font-weight: bold;margin-bottom: 15px;">Please do not reply to this email. To get in touch with us, click Help Center</p>

    </div>

  </body>
</html>

EOD;
		
		$textbody = <<<EOD
Hello there,

We've just received a "Forgot Password" request for the account associated with this email address.
If you made this request, use the link below to reset your password.

{$url}

Thank you,
BatteryXchange
EOD;

		// The full path to the file that will be attached to the email.
		$att = 'path/to/customers-to-contact.xlsx';

		// Create an SesClient.
		$client = SesClient::factory( array(
			'version' => '2010-12-01',
			'region'  => $region
		) );
		// Create a new PHPMailer object.
		$mail = new PHPMailer;

		// Add components to the email.
		$mail->setFrom( $sender, $sendername );
		$mail->addAddress( $recipient );
		$mail->Subject = $subject;
		$mail->Body    = $htmlbody;
		$mail->AltBody = $textbody;
		$mail->addAttachment( $att );
		//$mail->addCustomHeader( 'X-SES-CONFIGURATION-SET', $configset );

		// Attempt to assemble the above components into a MIME message.
		if ( ! $mail->preSend() ) {
			echo $mail->ErrorInfo;
		} else {
			// Create a new variable that contains the MIME message.
			$message = $mail->getSentMIMEMessage();
		}

		// Try to send the message.
		try {
			$result = $client->sendRawEmail( [
				'RawMessage' => [
					'Data' => $message
				]
			] );
			// If the message was sent, show the message ID.
			$messageId = $result->get( 'MessageId' );
			return true;
//			echo( "Email sent! Message ID: $messageId" . "\n" );
		} catch ( SesException $error ) {
			// If the message was not sent, show a message explaining what went wrong.
			save_log('email_error', "The email was not sent. Error message: "
			                        . $error->getAwsErrorMessage() . "\n");
			return false;
		}
	}

}