<?php
/**
 * Created by PhpStorm.
 * User: Watson
 * Date: 2018/10/20 0020
 * Time: 11:14
 */

namespace phpmailer;

use app\common\service\AmazonOss;
use app\common\service\AmazonSms;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PhpmailerClass
{
    public function send($reciver='', $token='')
    {
	   
//        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
	        return (new AmazonOss()) ->email($reciver,$token);

//            //Server settings
//            $mail->SMTPDebug = 0;                                 // Enable verbose debug output
//            $mail->isSMTP();                                      // Set mailer to use SMTP
//            $mail->Host = 'smtp.sina.com';  // Specify main and backup SMTP servers
//            $mail->SMTPAuth = true;                               // Enable SMTP authentication
//            $mail->Username = 'test2010jqx@sina.com';                 // SMTP username
//            $mail->Password = 'wangchao';                           // SMTP password
//            $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
//            $mail->Port = 587;                                    // TCP port to connect to
//
//            //Recipients
//            $mail->setFrom('test2010jqx@sina.com', 'BatteryXchange');
//            $mail->addAddress($reciver, '');     // Add a recipient
////            $mail->addAddress('ellen@example.com');               // Name is optional
////            $mail->addReplyTo('info@example.com', 'Information');
////            $mail->addCC('cc@example.com');
////            $mail->addBCC('bcc@example.com');
//
//            //Attachments
////            $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
////            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
//
//            //Content
//            $mail->isHTML(true);                                  // Set email format to HTML
//            $mail->Subject = 'Verification code';
//            $mail->Body    = 'Your verification code is:'.$code;
//
//            $mail->send();
//
//            return true;
        } catch (Exception $e) {
            return false;
            echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
        }
    }
}