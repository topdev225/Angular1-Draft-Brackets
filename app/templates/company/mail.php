<?php 
$topic = $POST['topic'];
$username = $_POST['username'];
$email = $_POST['email'];
$subject = $_POST['subject'];
$message = $_POST['message'];
$formcontent=" From: $username \n Topic: $topic \n Message: $message";
$recipient = "babiizhee@gmail.com";
$mailheader = "From: $email \r\n";

mail($recipient, $subject, $formcontent, $mailheader);

// header('Location: /contact-us');
?>