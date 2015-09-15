<?php

require_once(dirname(dirname(dirname(dirname(__DIR__)))) . "/vendor/autoload.php");

/**
 * create a swift message
 */
try{
	$swiftMessage = Swift_Message::newInstance();

	// attach the sender to the message
	// this takes the form of an associative array where the Email is the key for the real name
	$swiftMessage->setFrom(["example@cnm.edu" => "Example Email"]);

	/**
	 * attach the recipients to the message
	 * notice this an array that can include or omit the the recipient's real name
	 * use the recipients' real name where possible; this reduces the probability of the Email being marked as spam
	 **/
	$recipients = ["example@gmail.com" => "Name Name"];
	$swiftMessage->setTo($recipients);

	// attach the subject line to the message
	$swiftMessage->setSubject("Testing Email Delivery and anti spam");

	/**
	 * attach the actual message to the message
	 * here, we set two versions of the message: the HTML formatted message and a special filter_var()ed
	 * version of the message that generates a plain text version of the HTML content
	 * notice one tactic used is to display the entire $confirmLink to plain text; this lets users
	 * who aren't viewing HTML content in Emails still access your links
	 **/
	$confirmLink = "https://" . $_SERVER["SERVER_NAME"] . "/important-link/confirm.php?confirmationCode=abc123";
	$message = <<< EOF
<h1>Inventory Report</h1>
<p>Dear Customer This is your inventory report for the week of blah</p>
<p>To certify this email has reached its intended recipient, please comfirm by visiting: <a href="$confirmLink">$confirmLink</a></p>
EOF;
	$swiftMessage->setBody($message, "text/html");
	$swiftMessage->addPart(html_entity_decode(filter_var($message, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)), "text/plain");

	/**
	 * send the Email via SMTP; the SMTP server here is configured to relay everything upstream via CNM
	 * this default may or may not be available on all web hosts; consult their documentation/support for details
	 * SwiftMailer supports many different transport methods; SMTP was chosen because it's the most compatible and has the best error handling
	 * @see http://swiftmailer.org/docs/sending.html Sending Messages - Documentation - SwitftMailer
	 **/
	$smtp = Swift_SmtpTransport::newInstance("localhost", 25);
	$mailer = Swift_Mailer::newInstance($smtp);
	$numSent = $mailer->send($swiftMessage, $failedRecipients);

	/**
	 * the send method returns the number of recipients that accepted the Email
	 * so, if the number attempted is not the number accepted, this is an Exception
	 **/
	if($numSent !== count($recipients)) {
		// the $failedRecipients parameter passed in the send() method now contains contains an array of the Emails that failed
		throw(new RuntimeException("unable to send email"));
	}

	// report a successful send
	echo "<div class=\"alert alert-success\" role=\"alert\">Email successfully sent.</div>";
} catch(Exception $exception) {
	echo "<div class=\"alert alert-danger\" role=\"alert\"><strong>Oh snap!</strong> Unable to send email: " . $exception->getMessage() . "</div>";
}