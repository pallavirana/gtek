<?php
	require_once '../contact/lib/validation/validation.php';

	global $CONFIG;
	$CONFIG = array(
		/* Mail Options */
		'mail_send_to' =>'youremail@gmail.com', 
		'mail_contents'=>'mail-content.php', 
		'mail_subject' => 'Appointment Reminders',

		/* Messages */
		'messages'=>array(
			'mail_failed' =>'An unknown error has occured while sending your message', 
			'form_error'  =>'<strong>The following errors were encountered</strong><br><ul><li>%s</li></ul>', 
			'form_success'=>'<strong>Thank you!</strong><br>Your message has been sent, we\'ll get back to you shortly :)', 
			'form_fields' =>array(
				'dev_name'=>array(
					'required'=>'Name is required'
				), 
				'dev_surname'=>array(
					'required'=>'Surname is required'
				),
				'dev_email'=>array(
					'required'=>'Email is required', 
					'email'=>'Email is invalid'
				), 
				'dev_url'=>array(
					'url'=>'URL is invalid'
				), 
				'dev_from_date' => array(
					'required' => 'From Date is not valide'
				),
				'dev_from_time' => array(
					'required' => 'From Time is not valide'
				),
				'dev_to_date' => array(
					'required' => 'To Date is not valide'
				),
				'dev_to_time' => array(
					'required' => 'To Time is not valide'
				),
				'dev_phone'=>array(
					'required'=>'Phone is invalid'
				), 
				'dev_message'=>array(
					'required'=>'Message is required'
				), 
				'honeypot'=>array(
					'invalid'=>'You\'re not a human aren\'t you?'
				)
			)
		)
	);
	
	function createFormMessage( $formdata )
	{
		global $CONFIG;
		
		ob_start();
		
		extract($formdata);
		include $CONFIG['mail_contents'];
		
		return ob_get_clean();
	}

	function validate_honeypot( $array, $field ) {
		if( '' !== $array[ $field ] ) {
			$array->add_error( $field, 'invalid' );
		}
	}

	function cleanInput($input) {
		$search = array(
			'@<script[^>]*?>.*?</script>@si',   // Strip out javascript
			'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
			'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
			'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
		);

		$output = preg_replace($search, '', $input);
		return $output;
	}

	function sanitize($input) {
		if (is_array($input)) {
			foreach($input as $var=>$val) {
				$output[$var] = sanitize($val);
			}
		}
		else {
			if (get_magic_quotes_gpc()) {
				$input = stripslashes($input);
			}
			$input  = cleanInput($input);
			$output = $input;
		}
		return $output;
	}
	
	$response = array();
	$validator = new Validation( sanitize( $_POST[ 'dev' ] ) );
	$validator
		->pre_filter('trim')
		->add_rules('dev_name', 'required')
		->add_rules('dev_surname', 'required')
		->add_rules('dev_email', 'required', 'email')
		->add_rules('dev_phone', 'required')
		->add_rules('dev_from_date', 'date')
		->add_rules('dev_from_time', 'date')
		->add_rules('dev_to_date', 'date')
		->add_rules('dev_to_time', 'date')
		->add_callbacks('honeypot', 'validate_honeypot');
	
	if( $validator->validate() )
	{
		require_once( '../contact/lib/swiftmail/swift_required.php' );
		
		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);
		
		$formdata = $validator->as_array();
		$body = createFormMessage($formdata);
		
		$message = Swift_Message::newInstance();
		$message
			->setSubject($CONFIG['mail_subject'])
			->setFrom($formdata['dev_email'])
			->setTo($CONFIG['mail_send_to'])
			->setBody($body, 'text/html');
			
		if( !$mailer->send($message) ) {
			$response['success'] = false;
			$response['message'] = $CONFIG['messages']['mail_failed'];
		} else {
			$response['success'] = true;
			$response['message'] = $CONFIG['messages']['form_success'];
		}
	} else {
		$response = array(
			'success'=>false, 
			'message'=>sprintf($CONFIG['messages']['form_error'], implode('</li><li>', $validator->errors($CONFIG['messages']['form_fields']) ) )
		);
	}
	
	echo json_encode($response);

	exit();