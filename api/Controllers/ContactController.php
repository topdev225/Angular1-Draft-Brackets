<?php

namespace PhpDraft\Controllers;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Util\StringUtils;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;

class ContactController {
	private $mailer;

	public function MailMessage(Application $app,Request $request) {
	// public function __construct(Application $app,Request $request) {


		$response = $app['phpdraft.ResponseFactory'](true, array());

		$this->mailer = new \PHPMailer();

	    //Uncomment this line to help debug issues with your SMTP server
	    //Watch the response from the API when you register/start lost pwd to see the output.
	    //$this->mailer->SMTPDebug = 2;                               // Enable verbose debug output
		try {
		    $this->mailer->isSMTP();
		    $this->mailer->Host = MAIL_SERVER;
		    $this->mailer->Port = MAIL_PORT;
			
		    if(MAIL_DEVELOPMENT != true) {
		      $this->mailer->SMTPAuth = true; 
		      $this->mailer->Username = MAIL_USER;
		      $this->mailer->Password = MAIL_PASS;

		      if(MAIL_USE_ENCRYPTION == true) {
		        $this->mailer->SMTPSecure = MAIL_ENCRYPTION;
				
		      }
			  $this->mailer->SMTPAutoTLS = false;
		    }

		    $this->mailer->From = MAIL_USER;
			//Trying a real email address to see if fix not sending problem
		    $this->mailer->FromName = 'DraftBrackets System';

			$params = $request->request->all();

			$topic       = ! empty( $params['topic'] ) ? $params['topic'] : '';

			$username    = ! empty( $params['username'] ) ? $params['username'] : '';

			$email       = ! empty( $params['email'] ) ? $params['email'] : '';

			$subject     = ! empty( $params['subject'] ) ? $params['subject'] : '';

			$message_c   = ! empty( $params['message'] ) ? $params['message'] : '';

			$recipient = "brownexandrae@gmail.com";
			// $recipient = "babiizhee@gmail.com";

			$this->mailer->addAddress( $recipient, 'Admin');
			 //Content
		    $this->mailer->isHTML(true);                                  // Set email format to HTML
		    $this->mailer->Subject = $subject;
		    $this->mailer->Body    = sprintf("Email Address: %s <br/> Username: %s <br/> Topic: %s <br/><br/> Message: %s", $email, $username, $topic, $message_c );

	    	$this->mailer->send();
	    } catch (Exception $e) {
		    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
		}
		return true;

		// remove below
	    echo 'Message has been sent';
		//print_r( 124 );
		try {

		$recipient = "brownexandrae@gmail.com";
		// $recipient = "babiizhee@gmail.com";


		$topic       = $request->query->get('topic');

		$username    = $request->query->get('username');

		$email       = $request->query->get('email');

		$subject     = $request->query->get('subject');

		$message_c   = $request->query->get('message');


		$message = new MailMessage();

		$message->to_addresses = array(
			$recipient => $username
		);
		
		$message->subject = $subject;
		$message->is_html = true;
		$message->body    = sprintf("Email Address: %s <br/> Username: %s <br/> Topic: %s <br/><br/> Message: %s", $email, $username, $topic, $message_c );

		$app['phpdraft.EmailService']->SendMail( $message );

		// Tested if PHP default mail function works
		//mail( $recipient, $message->subject, $message->body );
		$response->message = "I guess it went through idk";


		} catch (\Exception $e) {

		  $response->success = false;

		  $response->errors[] = $e->getMessage();

		  return $app->json($response, $response->responseType());

		}

		return $app->json($response, $response->responseType());

	}

	

}