<?php
namespace App\Classes;
class SendinblueCustomerClass 
{
    const KEY = "***REMOVED-BREVO***";
    private static $campaingEmail = "conductores@kamgus.com";
	private static $instance = null;

	const RESET_PASSWORD_CAMPAING = 219;
	const DELETE_ACCOUNT_CONFIRMATION = 270;

    function __construct() {

	}
	public static function getInstance(){
        if(empty(self::$instance)){
            self::$instance = new SendinblueCustomerClass();
        }
        return self::$instance;
    }
   
    public function sendEmailCurl($to, $message, $subject = "", $headers = "", $name = ""){
		//return mail($to, $subject, $message, $headers) ? null: "Error enviando correo";
        //return $this->createCampaingSendinBlue($to, $message, $subject, $name);
        //return $this->sendCampaingEmail($to, $message, $subject, $name);
        return $this->sendEmailWithCurl($to, $message, $subject, $headers);
		
    }
	public function sendEmailWithCurl($to, $message, $subject = "", $headers = "", $name = ""){
		$headers .= "api-key: ".self::KEY."\r\n";			
		$headers .= "accept: application/json\r\n";			
		$headers .= "content-type: application/json";			
		$url = "https://api.sendinblue.com/v3/smtp/email";
		$params = array(

			'sender'  => [
				"email" => self::$campaingEmail
			],

			'to'        => $to,

			'subject'   => $subject,

			'htmlContent'      => $message,

		);      
		$session = curl_init($url);

		// Tell curl to use HTTP POST

		curl_setopt($session, CURLOPT_POST, true);

		// Tell curl that this is the body of the POST

		curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params));

		// Tell curl not to return headers, but do return the response

		curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_HTTPHEADER, $this->explodeHeaders($headers));

		// Tell PHP not to use SSLv3 (instead opting for TLS)
        
		curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);				

		// obtain response

		$response = curl_exec($session);
        
		curl_close($session);	
        //die($response);
		/*
		curl --request POST \
			--data '
		{
			"sender": {
				"name": "Mary from MyShop",
				"email": "no-reply@myshop.com",
				"id": 2
			},
			"to": [
				{
					"email": "jimmy98@example.com",
					"name": "Jimmy"
				}
			],
			"bcc": [
				{
					"email": "helen9766@example.com",
					"name": "Helen"
				}
			],
			"cc": [
				{
					"email": "ann6533@example.com",
					"name": "Ann"
				}
			],
			"htmlContent": "<!DOCTYPE html> <html> <body> <h1>Confirm you email</h1> <p>Please confirm your email address by clicking on the link below</p> </body> </html>",
			"textContent": "Please confirm your email address by clicking on the link https://text.domain.com",
			"subject": "Login Email confirmation",
			"replyTo": {
				"email": "ann6533@example.com",
				"name": "Ann"
			},
			"attachment": [
				{
					"url": "https://attachment.domain.com/myAttachmentFromUrl.jpg",
					"content": "b3JkZXIucGRm",
					"name": "myAttachment.png"
				}
			],
			"headers": {
				"sender.ip": "1.2.3.4",
				"X-Mailin-custom": "some_custom_header",
				"idempotencyKey": "abc-123"
			},
			"templateId": 2,
			"params": {
				"FNAME": "Joe",
				"LNAME": "Doe"
			},
			"messageVersions": [
				{
					"to": [
							{
								"email": "jimmy98@example.com",
								"name": "Jimmy"
							}
					],
					"params": {
							"FNAME": "Joe",
							"LNAME": "Doe"
					},
					"bcc": [
							{
								"email": "helen9766@example.com",
								"name": "Helen"
							}
					],
					"cc": [
							{
								"email": "ann6533@example.com",
								"name": "Ann"
							}
					],
					"replyTo": {
							"email": "ann6533@example.com",
							"name": "Ann"
					},
					"subject": "Login Email confirmation"
				}
			],
			"tags": [
				"tag1"
			],
			"scheduledAt": "2022-04-05T12:30:00+02:00",
			"batchId": "5c6cfa04-eed9-42c2-8b5c-6d470d978e9d"
		}

		*/
    }
	
	public function sendTemplateEmailWithCurl($to, $templateId, $subject = "", $params = [], $headers = ""){
		$headers .= "api-key: ".self::KEY."\r\n";			
		$headers .= "accept: application/json\r\n";			
		$headers .= "content-type: application/json";			
		//$url = "https://api.sendinblue.com/v3/smtp/email";
		$url = "https://api.brevo.com/v3/smtp/email";
		$params = array(

			//'sender'  => [
			//	"email" => self::$campaingEmail
			//],

			'bcc'        => [["email"=>self::$campaingEmail]],
			'to' => [
				[
					"email" =>$to
				],
			],

			'subject'   => $subject,

			'templateId'      => $templateId,
			"params" => $params,

		);      
		$session = curl_init($url);

		// Tell curl to use HTTP POST

		curl_setopt($session, CURLOPT_POST, true);

		// Tell curl that this is the body of the POST

		curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($params));

		// Tell curl not to return headers, but do return the response

		curl_setopt($session, CURLOPT_HEADER, false);
		//dd($this->explodeHeaders($headers));
        curl_setopt($session, CURLOPT_HTTPHEADER, explode("\r\n", $headers));

		// Tell PHP not to use SSLv3 (instead opting for TLS)
        
		curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);				

		// obtain response
		$httpcode = curl_getinfo($session, CURLINFO_HTTP_CODE);
		$response = curl_exec($session);
		curl_close($session);	
        return ($response);
	}
	private static function explodeHeaders($headers){
		$headerLines = explode("\n", trim($headers));
		$headersArray = [];
		foreach ($headerLines as $value) {
			$kV = explode(":", $value);
			$headersArray[trim($kV[0])] = trim($kV[1]);
		}
		return $headersArray;
	}
}
