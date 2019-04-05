<?php
// print_r( $_POST );


namespace PhpDraft\Controllers;

use Symfony\Component\HttpFoundation\Request;

use Silex\Application;


class ConfirmationController {

	// public function confirmation() {
	// 	print_r( $_POST ); exit;
	// }

	public function index(Application $app,Request $request) {
  
  dump( 'Test 1' );

  $topic       = $request->query->get('topic');

  $username    = $request->query->get('username');

  $email       = $request->query->get('email');

  $subject     = $request->query->get('subject');

  $message     = $request->query->get('message');

  $formcontent = " From: $username \n Topic: $topic \n Message: $message";

  $recipient   = "babiizhee@gmail.com";

  $mailheader  = "From: $email \r\n";

  $mailed = mail( $recipient, $subject, $formcontent, $mailheader );

  die();
 }

 public function confirmation(Application $app,Request $request) {
  
  dump( 'Test 3' );

  $topic       = $request->query->get('topic');

  $username    = $request->query->get('username');

  $email       = $request->query->get('email');

  $subject     = $request->query->get('subject');

  $message     = $request->query->get('message');

  $formcontent = " From: $username \n Topic: $topic \n Message: $message";

  $recipient   = "babiizhee@gmail.com";

  $mailheader  = "From: $email \r\n";

  $mailed = mail( $recipient, $subject, $formcontent, $mailheader );

  die();
 }


	public function MailMessage(Application $app,Request $request) {
	// public function __construct(Application $app,Request $request) {

		
		$response = $app['phpdraft.ResponseFactory'](true, array());

		try {

		$topic = $POST['topic'];

		$username = $_POST['username'];

		$email = $_POST['email'];

		$subject = $_POST['subject'];

		$message = $_POST['message'];

		$formcontent=" From: $username \n Topic: $topic \n Message: $message";

		// $recipient = "brownexandrae@gmail.com";
		$recipient = "babiizhee@gmail.com";

		$mailheader = "From: $email \r\n";



		$mailed = mail($recipient, $subject, $formcontent, $mailheader);


		$response->message = "I guess it went through idk";

		

			

		

		} catch (\Exception $e) {

		  $response->success = false;

		  $response->errors[] = $e->getMessage();



		  return $app->json($response, $response->responseType());

		}

		return $app->json($response, $response->responseType());

	

	}

	

}