<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;
use Cake\Datasource\ConnectionManager;
use Cake\View\Helper;
use Cake\ORM\TableRegistry;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Event\Event;
use Cake\Network\Email\Email;
use PHPMailer\PHPMailer\PHPMailer;

include(ROOT . DS . "vendor" . DS  . "PHPMailer/" . DS . "PHPMailerAutoload.php");
include(ROOT . DS . "vendor" . DS  . "phpqrcode" . DS . "qrlib.php");

class MobileController extends AppController
{
	public function beforeFilter(Event $event)
	{

		$this->loadmodel('Event');
		$this->loadmodel('Users');
		$this->loadComponent('Email');
		parent::beforeFilter($event);
		$this->Auth->allow(['registerUser', 'restorePassword', 'changePassword', 'getProfile', 'resetImei', 'qrcodeproticket', 'qrcodeproticketnew', 'registerImeiVisitor', 'getEvents', 'buyTicket', 'registration', 'mpesaPayment', 'myevents', 'postedEvents', 'scanTicket', 'viewticketdetail', 'myTickets', 'getTicketName', 'showshareTicket', 'showEventsDetails', 'shareTicket', 'userLogin', 'updateProfile', 'confirmTicket', 'getEventsNew', 'uploadToken', 'eventDetails', 'addToCart', 'addPackageToCart', 'cartDetails', 'deleteCartItem', 'getAddons', 'addonAddToCart', 'getCommittee', 'getAttendees', 'getCommTicketDetails', 'getQuestions', 'cartCount', 'finalcheckout', 'sendverfiycode', 'verfiycode', 'updateTicketName', 'getCountry', 'getEventList', 'eventDashboard', 'postedEventDetails', 'organizerdashboardapi', 'getSoldTickets', 'organizerdashboardapinew', 'createGUID', 'paymentProcessing', 'paymentProcessingchecking', 'getCommitteenotifications', 'committeeissuecomplimentary', 'committeecashpayment', 'committeeignored', 'committeeapproved', 'accountDelete', 'getScannedTicket', 'pastTickets', 'getMyStaff', 'staffScannedTickets', 'getEventForScanner', 'enablersvp', 'selfregistration', 'reminderemail', 'packageAssigned', 'versionCheck', 'createCompany', 'getCompanyList', 'postNewEvent', 'currencyTicketLimitApproval','getTicketDetailsByEventId','ticketDetailUpdateStatus','addTicket','getAddTicketDetail','updateAddedTicket','addAddons','getAddonsDetail','editAddonDetail','addQuestions','getQuestionDetail','editQuestionDetail','linkQuesWithTicket']);
	}

	public function initialize()
	{
		parent::initialize();
		$this->loadComponent('Email');
	}

	// password creator 
	public function _setPassword($password)
	{
		return (new DefaultPasswordHasher)->hash($password);
	}

	// company list 
	public function getCompanyList()
	{
		$this->loadModel('Company');

		$response = array();
		$response['Data'] = array();

		if ($this->request->is(['post', 'put'])) {
			$user_id = $this->request->data['userId'];
			$companies = $this->Company->find('all')
				->where(['status' => 'Y', 'user_id' => $user_id])
				->toArray();

			if (!empty($companies)) {
				$response['success'] = true;
				$response['status'] = null;
				foreach ($companies as $companyDetails) {
					$output['Id'] = $companyDetails['id'];
					$output['Name'] = $companyDetails['name'];
					$response['Data'][] = $output;
				}
			} else {
				$response['success'] = false;
				$response['status'] = 'No companies available';
				$response['message'] = 'No companies available';
			}

			echo json_encode($response);
			die;
		}
	}

	// create company 
	public function createCompany()
	{
		$this->loadModel('Company');

		$response = array();

		if ($this->request->is(['post', 'put'])) {
			$user_id = $this->request->data['userId'];
			$company_name = $this->request->data['companyName'];

			$existingCompany = $this->Company->find()
				->where(['user_id' => $user_id, 'name' => $company_name])
				->first();

			if ($existingCompany) {
				$response['success'] = false;
				$response['status'] = 'Company name already exists for the user';
			} else {
				$company = $this->Company->newEntity();
				$company->user_id = $user_id;
				$company->name = $company_name;

				if ($this->Company->save($company)) {
					$response['success'] = true;
					$response['status'] = 'Company created successfully';
				} else {
					$response['success'] = false;
					$response['status'] = 'Error creating company';
				}
			}

			echo json_encode($response);
			die;
		}
	}

	// Create post Event 
	public function postNewEvent()
	{

		$this->loadModel('Event');
		$this->loadModel('Users');
		$this->loadModel('Eventdetail');
		$this->loadModel('Countries');
		$this->loadModel('Company');
		$this->loadModel('Currency');
		$response = array();


		if ($this->request->is(['post', 'put'])) {
			$user_id = $this->request->data['userId'];
			$admin_info = $this->Users->get(1);
			$addevent = $this->Event->newEntity();

			// for admin approval 
			$forFreeEvent = ($admin_info['forFreeEvent'] == 'N') ? 'Y' : 'N';
			$forPaidEvent = ($admin_info['forPaidEvent'] == 'N') ? 'Y' : 'N';

			if (empty($this->request->data['name']) || empty($this->request->data['company_id']) || empty($this->request->data['country_id']) || empty($this->request->data['location']) || empty($this->request->data['event_image']) || empty($this->request->data['slug']) || empty($this->request->data['date_from']) || empty($this->request->data['date_to']) || empty($this->request->data['desp'])) {

				$response['success'] = false;
				$response['status'] = 'Please complete all required fields before submitting the form.';
				echo json_encode($response);
				die;
			}

			$imagefilename = $this->request->data['event_image']['name'];
			list($width, $height) = getimagesize($this->request->data['event_image']['tmp_name']);

			if ($width < 200 || $height < 200) {

				$response['success'] = false;
				$response['status'] = 'Image dimensions are too small. Minimum (Size 200*200). Uploaded image (Size ' . $width . '*' . $height . ')';
				echo json_encode($response);
				die;
			}
			$ext = pathinfo($this->request->data['event_image']['name'], PATHINFO_EXTENSION);

			if ($ext != "png" && $ext != "jpeg" && $ext != "jpg") {
				$response['success'] = false;
				$response['status'] = 'Uploaded file is not a valid image. Only JPG, PNG, and JPEG files are allowed.';
				echo json_encode($response);
				die;
			}


			//For free event
			if ($this->request->data['is_free'] == 'Y') {

				$request_rsvp = date('Y-m-d H:i:s', strtotime($this->request->data['request_rsvp']));
				if (empty($request_rsvp)) {
					$this->Flash->error(__('Please choose Date Time for request RSVP.'));
					return $this->redirect(['controller' => 'event', 'action' => 'postevent']);

					$response['success'] = false;
					$response['status'] = 'Please choose Date Time for request RSVP.';
					echo json_encode($response);
					die;
				}

				if ($imagefilename) {
					$itemww = $this->request->data['event_image']['tmp_name'];
					$ext = pathinfo($imagefilename, PATHINFO_EXTENSION);
					$name = time() . md5($imagefilename);
					$imagename = $name . '.' . $ext;
					if (move_uploaded_file($itemww, "images/eventimages/" . $imagename)) {
						$free_event['feat_image'] = $imagename;
					}
				}

				$free_event['event_org_id'] = $user_id;
				$free_event['company_id'] = $this->request->data['company_id'];
				$free_event['name'] = $this->request->data['name'];
				$free_event['country_id'] = $this->request->data['country_id'];
				$free_event['location'] = $this->request->data['location'];
				$free_event['video_url'] = $this->request->data['video_url'];
				$free_event['slug'] = $this->request->data['slug'];
				$free_event['desp'] = $this->request->data['desp'];
				$free_event['date_from'] = date('Y-m-d H:i:s', strtotime($this->request->data['date_from']));
				$free_event['date_to'] = date('Y-m-d H:i:s', strtotime($this->request->data['date_to']));
				$free_event['fee_assign'] = "user";
				$free_event['is_free'] = "Y";
				$free_event['allow_register'] = ($this->request->data['allow_register'] == 'Y') ? 'Y' : 'N';
				$free_event['admineventstatus'] = $forFreeEvent;
				$free_event['request_rsvp'] = date('Y-m-d H:i:s', strtotime($this->request->data['request_rsvp']));
				$addeventdata = $this->Event->patchEntity($addevent, $free_event);
				// pr($addeventdata);exit;

				if ($addeventsave = $this->Event->save($addeventdata)) {
					// Create comp ticket default
					$checkTicket = $this->Eventdetail->find('all')->where(['eventid' => $addeventsave['id']])->first();
					if (empty($checkTicket)) {
						$newcomps = $this->Eventdetail->newEntity();
						$comps['title'] = 'Comps';
						$comps['price'] = 0;
						$comps['eventid'] = $addeventsave['id'];
						$comps['userid'] = $user_id;
						$comps['count'] = 0;
						$comps['type'] = 'comps';
						$comps['hidden'] = 'Y';
						$compsupdate = $this->Eventdetail->patchEntity($newcomps, $comps);
						$this->Eventdetail->save($compsupdate);
					}
					//user role id change
					$user_data = $this->Users->get($user_id);
					$user_data->role_id = '2';
					$this->Users->save($user_data);
					// return $this->redirect(['controller' => 'event', 'action' => 'settings/' . $addeventsave['id']]);

					$response['success'] = true;
					$response['status'] = 'Event has been create successfully create setting details';
					echo json_encode($response);
					die;
				} else {
					$response['success'] = false;
					$response['status'] = 'Something went Wrong ! Try after some time.';
					echo json_encode($response);
					die;
				}
			} else {
				// pr($this->request->data);exit;

				if ($imagefilename) {
					$itemww = $this->request->data['event_image']['tmp_name'];
					$ext = pathinfo($imagefilename, PATHINFO_EXTENSION);
					$name = time() . md5($imagefilename);
					$imagename = $name . '.' . $ext;
					if (move_uploaded_file($itemww, "images/eventimages/" . $imagename)) {
						$paid_event['feat_image'] = $imagename;
					}
				}
				$paid_event['event_org_id'] = $user_id;
				$paid_event['company_id'] = $this->request->data['company_id'];
				$paid_event['name'] = trim($this->request->data['name']);
				$paid_event['country_id'] = $this->request->data['country_id'];
				$paid_event['location'] = $this->request->data['location'];
				$paid_event['video_url'] = $this->request->data['video_url'];
				$paid_event['payment_currency'] = $this->request->data['payment_currency'];
				$paid_event['date_from'] = date('Y-m-d H:i:s', strtotime($this->request->data['date_from']));
				$paid_event['date_to'] = date('Y-m-d H:i:s', strtotime($this->request->data['date_to']));
				$paid_event['sale_start'] = date('Y-m-d H:i:s', strtotime($this->request->data['sale_start']));
				$paid_event['sale_end'] = date('Y-m-d H:i:s', strtotime($this->request->data['sale_end']));
				$paid_event['ticket_limit'] = $this->request->data['ticket_limit'];
				$paid_event['slug'] = $this->request->data['slug'];
				$paid_event['approve_timer'] = $this->request->data['approve_timer'];
				$paid_event['desp'] = trim($this->request->data['desp']);
				$paid_event['fee_assign'] = "user";
				$paid_event['admineventstatus'] = $forPaidEvent;
				$addeventdata = $this->Event->patchEntity($addevent, $paid_event);
				if ($addeventsave = $this->Event->save($addeventdata)) {
					//user role id change
					$user_data = $this->Users->get($user_id);
					$user_data->role_id = '2';
					$this->Users->save($user_data);
					// return $this->redirect(['controller' => 'event', 'action' => 'settings/' . $addeventsave['id']]);
					$response['success'] = true;
					$response['status'] = 'Event has been create successfully create setting details';
					echo json_encode($response);
					die;
				} else {
					$response['success'] = false;
					$response['status'] = 'Something went Wrong ! Try after some time.';
					echo json_encode($response);
					die;
				}
			}
			$response['success'] = false;
			$response['status'] = 'Something went Wrong ! Try after some time.';
			echo json_encode($response);
			die;
		}
	}

	function currencyTicketLimitApproval()
	{
		$this->loadModel('Currency');
		$response = array();
		$response['Data'] = array();

		$findCurrency = $this->Currency->find('all')->where(['status' => 'Y'])->toarray();

		foreach ($findCurrency as $key => $currencyDetails) {
			$output['Id'] = $currencyDetails['id'];
			$output['Name'] = $currencyDetails['name'];
			$response['Data']['Currency'][] = $output;
		}

		$ticketLimit = [
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => '9',
			'10' => '10',
			'11' => '11',
			'12' => '12',
			'13' => '13',
			'14' => '14',
			'15' => '15',
			'16' => '16',
			'17' => '17',
			'18' => '18',
			'19' => '19',
			'20' => '20',
			'25' => '25',
			'30' => '30',
			'35' => '35',
			'40' => '40',
			'45' => '45',
			'50' => '50'
		];

		foreach ($ticketLimit as $key => $ticketLimitDetails) {
			$output['Id'] = $ticketLimitDetails;
			$output['Name'] = $ticketLimitDetails;
			$response['Data']['ticketLimit'][] = $output;
		}

		$approveExpire = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7'];

		foreach ($approveExpire as $key => $approveExpireDetails) {
			$output['Id'] = $approveExpireDetails;
			$output['Name'] = $approveExpireDetails;
			$response['Data']['approveExpire'][] = $output;
		}
		echo json_encode($response);
		die;

	}

	// check email exists
	public function checkemail()
	{
		$this->autoRender = false;
		$this->loadModel('Users');
		$email = $this->request->data['email'];
		// $phone = $this->request->data['mobile'];
		if (!empty($email)) {
			$check_count = $this->Users->find('all')->where(['Users.email LIKE ' => $email])->first();
			return $check_count;
		}
		// if (!empty($phone)) {
		// 	$check_count = $this->Users->find('all')->where(['Users.mobile LIKE ' => $phone])->first();
		// 	return $check_count;
		// }
	}

	public function uploadToken()
	{

		$this->loadmodel('Users');
		$userid = $_REQUEST['userId'];
		$token = $_REQUEST['token'];

		$Pack = $this->Users->get($userid);
		$Pack->token = $token;

		if ($this->Users->save($Pack)) {
			$response["success"] = true;
		} else {
			$response["success"] = false;
		}
		echo json_encode($response);
		die;
	}


	// Register user
	public function registerUser($id = null)
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$response = array();

		$reqData = $this->request->data;
		// $passowr = $this->_setPassword(12345);
		$newuser = $this->Users->newEntity();

		if ($reqData) {
			$check = $this->checkemail();
			//check email
			if (empty($check['id'])) {
				$this->request->data['name'] = ucfirst(strtolower($reqData['name']));
				$this->request->data['lname'] = ucfirst(strtolower($reqData['lname']));
				$this->request->data['role_id'] = CUSTOMERROLE;
				$this->request->data['confirm_pass'] = $reqData['password'];
				$this->request->data['password'] = $this->_setPassword($reqData['password']);
				$this->request->data['gender'] = $reqData['gender'];
				$this->request->data['activation_code'] = strtoupper(strtolower(substr(md5(uniqid(rand(), true)), 10, 10)));
				$this->request->data['status'] = 'N';
				$this->request->data['dob'] = date('Y-m-d', strtotime($reqData['dob']));
				$customer = $this->Users->patchEntity($newuser, $this->request->data);
				// pr($customer);exit;
				$res = $this->Users->save($customer);
				if ($res) {
					/*sending email start */
					$this->loadmodel('Templates');
					$profile = $this->Templates->find('all')->where(['Templates.id' => 13])->first();

					$subject = $profile['subject'];
					$from = $profile['from'];
					$fromname = $profile['fromname'];
					$name = $res['name'] . ' ' . $res['lname'];
					$email = $res['email'];
					$activation_code = $res['activation_code'];
					$buttonname = "VERIFY YOUR EMAIL";
					$activation = 'logins/activation/' . $activation_code;
					$to  = $email;
					$formats = $profile['description'];
					$site_url = SITE_URL;
					// $message1 = str_replace(array('{Name}', '{Activation}', '{buttonname}'), array($name, $activation, $buttonname), $formats);
					$message1 = str_replace(array('{Name}', '{Activation}', '{buttonname}', '{SITE_URL}', '{From}', 'Fromname'), array($name, $activation, $buttonname, $site_url, $from, $fromname), $formats);

					$message = '
						<!DOCTYPE HTML>
						<html>					
						<head>
							<meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
							<title>Untitled Document</title>
							<style>
								p {
									margin: 9px 0px;
									line-height: 24px;
								}
							</style>					
						</head>					
						<body style="background:#d8dde4; padding:15px;">
						' . $message1 . '
						</body>
						</html>
						';
					$mail = $this->Email->send($to, $subject, $message);
					$response['success'] = true;
					$response['url'] = SITE_URL . $activation;
					$response['status'] = 'Successfully registered! A verification email has been sent to your inbox. Please check your email.';
					$response['message'] = 'Successfully registered! A verification email has been sent to your inbox. Please check your email.';
					echo json_encode($response);
					die;
				}
			} else {
				if ($check['status'] == 'N') {
					$response['success'] = false;
					$response['status'] = 'Account verification is pending. Please check your registered email for a verification link.';
					$response['message'] = 'Account verification is pending. Please check your registered email for a verification link.';
					echo json_encode($response);
					die;
				} else {
					$response['success'] = false;
					$response['status'] = 'Sorry, the email you provided is already registered. Please try again with a different email.';
					$response['message'] = 'Sorry, the email you provided is already registered. Please try again with a different email..';
					echo json_encode($response);
					die;
				}
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'invalide data please try again !';
			echo json_encode($response);
			die;
		}

		echo json_encode($response);
		die;
	}

	//Forget password
	public function restorePassword()
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$response = array();

		$email = $this->request->data['email'];
		//$imeiNo = $this->request->data['imeiNo'];
		$to = $email;
		$useremail = $this->Users->find('all')->where(['Users.email' => $email])->first();

		if (count($useremail) == 0) {
			$response['success'] = false;
			$response['status'] = 'Invalid email , try again';
			$response['message'] = 'Invalid email.';
			echo json_encode($response);
			die;
		} elseif ($useremail['status'] == 'N') {
			$response['success'] = false;
			$response['status'] = 'Email verification is pending you have recieved your verification email';
			$response['message'] = 'Email not verified.';
			echo json_encode($response);
			die;
		} else {
			$userid = $useremail['id'];
			$name = $useremail['name'];
			$site_url = SITE_URL;
			$fkey = rand(1, 10000);

			$Pack = $this->Users->get($userid);
			$Pack->fkey = $fkey;
			//$Pack->imei = $imeiNo;
			$this->Users->save($Pack);
			$mid = base64_encode(base64_encode($userid . '/' . $fkey));
			$url = SITE_URL . "users/forgetcpass/" . $mid;
			$this->loadmodel('Templates');
			$profile = $this->Templates->find('all')->where(['Templates.id' => FORGOTPASSWORD])->first();
			$subject = $profile['subject'];
			$from = $profile['from'];
			$fromname = $profile['fromname'];
			$to  = $email;
			$formats = $profile['description'];
			$site_url = SITE_URL;

			$message1 = str_replace(array('{Name}', '{site_url}', '{url}'), array($name, $site_url, $url), $formats);
			$message = stripslashes($message1);
			$message = '
				<!DOCTYPE HTML>
				<html>
				<head>
				<meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
				<title>Untitled Document</title>
				</head>
				<style>
					button:focus {
						outline: none;
					}
				</style>
				<body style="background:#d8dde4; padding:15px;">
				' . $message1 . '</body></html>';
			// pr($message);exit;
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: ' . $fromname . ' <' . $from . '>' . "\r\n";
			$mail = $this->Email->send($to, $subject, $message);

			$response['success'] = true;
			$response['status'] = '';
			$response['url'] = $url;
			$response['message'] = 'Forgot passowrd link sent to Your Email';
			echo json_encode($response);
			die;
		}
		echo json_encode($response);
		die;
	}

	// changePassword
	public function changePassword($id = null)
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$response = array();

		$userId = $this->request->data['userId'];
		$password = $this->request->data['password'];
		$userData = $this->Users->get($userId);
		$userData->password = $this->_setPassword($password);
		$userData->confirm_pass = $password;
		// send email
		$email = $userData['email'];
		$name = $userData['name'];
		$password = $this->request->data['password'];
		$this->loadmodel('Templates');
		$profile = $this->Templates->find('all')->where(['Templates.id' => FORGOTPASSWORDCHANGED])->first();
		$subject = $profile['subject'];
		$from = $profile['from'];
		$fromname = $profile['fromname'];
		$to  = $email;
		$formats = $profile['description'];
		$site_url = SITE_URL;
		$message1 = str_replace(array('{Name}', '{Email}', '{Password}', '{site_url}'), array($name, $email, $password, $site_url), $formats);
		$message = stripslashes($message1);
		$message = '<!DOCTYPE HTML><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Mail</title></head><body style="padding:0px; margin:0px;font-family:Arial,Helvetica,sans-serif; font-size:13px;">' . $message1 . '</body></html>';
		$headers = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: ' . $fromname . ' <' . $from . '>' . "\r\n";
		// $emailcheck = mail($to, $subject, $message, $headers);
		$useremail = $this->Email->send($to, $subject, $message);

		if ($this->Users->save($userData)) {
			$response['success'] = true;
			$response['status'] = 'Password change successfully';
			$response['message'] = 'Changed password.';
		} else {
			$response['success'] = false;
			$response['status'] = 'Password not change complete';
			$response['message'] = 'Something went wrong.';
		}
		echo json_encode($response);
		die;
	}

	public function organizerdashboardapi()
	{

		$this->autoRender = false;

		$this->loadModel('Eventdetail');
		$this->loadModel('Committeeassignticket');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Attendeeslist');
		$this->loadModel('Ticket');
		$this->loadModel('Cart');


		if ($this->request->is(['post', 'put'])) {

			$dates = array();
			$user_id = $_REQUEST['userId'];
			$event_id = $_REQUEST['eventId'];

			$singleevent_detail = $this->Event->find('all')->contain(['Currency'])->where(['Event.id' => $event_id])->first();
			$isFree = ($singleevent_detail['is_free'] == 'Y') ? true : false;

			if ($singleevent_detail['submit_count']) {
				$totalInvitation = $this->Ticket->find('all')->contain(['Users'])->where(['Ticket.event_id' => $event_id])->count();
				$noOfRsvps = $this->Ticketdetail->find('all')->contain(['Ticket', 'Users'])->where(['Ticket.event_id' => $event_id, 'Ticketdetail.is_rsvp' => 'Y'])->count();
				$totalScannedAttendees = $this->Ticketdetail->find('all')->contain(['Ticket', 'Users'])->where(['Ticket.event_id' => $event_id, 'Ticketdetail.status' => 1])->count();
			} else {
				$totalInvitation = $this->Attendeeslist->find('all')->where(['Attendeeslist.event_id' => $event_id])->count();
				$noOfRsvps = $this->Attendeeslist->find('all')->where(['event_id' => $event_id, 'is_rsvp' => 'Y'])->count();
			}


			$totalopen_sales_ticket = $this->Eventdetail->find('all')->select(['sum' => 'SUM(Eventdetail.count)'])->where(['Eventdetail.eventid' => $event_id])->first();

			$totalcommitee_sales_ticket = $this->Committeeassignticket->find('all')->select(['sum' => 'SUM(Committeeassignticket.count)'])->where(['Committeeassignticket.event_id' => $event_id])->first();

			$totalticket = $totalopen_sales_ticket['sum'] + $totalcommitee_sales_ticket['sum'];
			if ($totalticket) {
				$totalticket_count = $totalticket;
			} else {
				$totalticket_count = 0;
			}

			$totalticket_sold = $this->Ticket->find('all')->select(['sum' => 'SUM(Ticket.ticket_buy)'])->where(['Ticket.event_id' => $event_id])->first();

			if ($totalticket_sold['sum']) {
				$totalticketsold_count = $totalticket_sold['sum'];
			} else {
				$totalticketsold_count = 0;
			}


			$totalticket_sold_revenue = $this->Ticket->find('all')->select(['sumamount' => 'SUM(Ticket.amount)'])->where(['Ticket.event_id' => $event_id])->first();
			//pr($totalticket_sold_revenue);
			if ($totalticket_sold_revenue['sumamount']) {
				$totalticketrevenue_count = $totalticket_sold_revenue['sumamount'];
			} else {
				$totalticketrevenue_count = 0;
			}
			//pr($totalticket_payment_online); die;

			//$payment_method =[];

			$totalticket_payment_online = $this->Ticket->find('all')->contain(['Orders'])->select(['onlineamount' => 'SUM(Ticket.amount)'])->where(['Ticket.event_id' => $event_id, 'Orders.paymenttype' => 'Online'])->first();

			if ($totalticket_payment_online['onlineamount']) {
				$payment_method['type'] = "Online";
				$payment_method['count'] = (int) $totalticket_payment_online['onlineamount'];
			} else {
				$payment_method['type'] = "Online";
				$payment_method['count'] = 0;
			}

			$totalticket_payment_offline = $this->Ticket->find('all')->contain(['Orders'])->select(['offlineamount' => 'SUM(Ticket.amount)'])->where(['Ticket.event_id' => $event_id, 'Orders.paymenttype IN' => ['Cash', 'EventOffice', 'Comps']])->first();

			if ($totalticket_payment_offline['offlineamount']) {
				$payment_method_offline['type'] = "Cash";
				$payment_method_offline['count'] = (int) $totalticket_payment_offline['offlineamount'];
			} else {
				$payment_method_offline['type'] = "Cash";
				$payment_method_offline['count'] = 0;
			}

			$paymentbreak[] = $payment_method;
			$paymentbreak[] = $payment_method_offline;

			$total_ticket_sale = array();
			$ticket_types = $this->Eventdetail->find('all')->where(['Eventdetail.eventid' => $event_id])->toarray();
			foreach ($ticket_types as $key => $value) {

				//$total_ticket_sale_buy = array();
				//$total_ticket_sale_all = array();
				$ticket_id = $value['id'];
				$ticket_types_amount = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(amount)'])->where(['Ticket.event_id' => $event_id, 'Ticket.event_ticket_id' => $ticket_id])->first();
				//pr($ticket_types_amount);
				$ticket_types_sale = $this->Ticket->find('all')->Select(['ticket_buy' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $event_id, 'Ticket.event_ticket_id' => $ticket_id])->first();

				$data_ticket['ticket'] = $value['title'];
				$data_ticket['count'] = (int)$ticket_types_sale['ticket_buy'];
				$total_ticket_sale[] = $data_ticket;
			}
			//pr($total_ticket_sale_all);

			$comitee_approved = $this->Cart->find('all')->select(['no_tickets' => 'SUM(no_tickets)'])->where(['Cart.event_id' => $event_id, 'Cart.ticket_type' => 'committesale', 'Cart.status' => 'Y'])->order(['Cart.user_id' => 'ASC'])->first();
			$total_approved_ticket = $comitee_approved['no_tickets'];
			if ($total_approved_ticket) {
				$total_approved_ticket = $total_approved_ticket;
			} else {
				$total_approved_ticket = 0;
			}
			$total_ticket['type'] = "Total";
			$total_ticket['count'] = $totalticket_count;

			$pendingcountt = $totalticket_count - $totalticketsold_count;
			$total_pending_ticket['type'] = "Pending";
			$total_pending_ticket['count'] = $pendingcountt;

			$total_appoved_ticket['type'] = "Approved";
			$total_appoved_ticket['count'] = $total_approved_ticket;

			$total_completed_ticket['type'] = "Completed";
			$total_completed_ticket['count'] = (int)$totalticketsold_count;

			$request_breakout[] = $total_ticket;
			$request_breakout[] = $total_pending_ticket;
			$request_breakout[] = $total_appoved_ticket;
			$request_breakout[] = $total_completed_ticket;

			$myResult = $this->Ticket->find('all', [
				'fields' => [
					'minDate' => 'MIN(created)',
					'maxDate' => 'MAX(created)'
				],
				'conditions' => ['Ticket.event_id' => $event_id]
			])->first();

			$minDate = $myResult->minDate;
			$maxDate = $myResult->maxDate;

			$date1 = date('Y-m-d', strtotime($minDate));
			$date2 = date('Y-m-d', strtotime($maxDate));


			$date1_ts = strtotime($date1);
			$date2_ts = strtotime($date2);
			$diff = $date2_ts - $date1_ts;
			$total_days = round($diff / 86400) + 1;


			$current = strtotime($date1);
			$date2 = strtotime($date2);
			if ($total_days <= 10) {
				$stepVal = '+1 day';
			} else {
				$stepVal = '+5 day';
			}

			$format = 'd-m-Y';

			while ($current <= $date2) {
				$dates[] = date($format, $current);
				$current = strtotime($stepVal, $current);
			}

			//sales
			foreach ($dates as $value) {
				$previousdates = date('Y-m-d', strtotime($value . ' - 4 days'));
				$dates_data['date'] = date('d M', strtotime($value));
				//$dates_data['startdate'] = date('Y-m-d', strtotime($value));
				//$dates_data['previousdate'] = $previousdates;
				$ticketsold_date  = $this->Ticket->find('all')->where(['Ticket.event_id' => $event_id, 'DATE(Ticket.created) >=' => $previousdates, 'DATE(Ticket.created) <=' => date('Y-m-d', strtotime($value))])->count();
				if ($ticketsold_date == 0) {
					continue;
				}
				$dates_data['count'] = $ticketsold_date;

				$dates_data_all[] = $dates_data;
			}


			$data = array(
				'isFree' => $isFree,
				'totalInvitation' => $totalInvitation,
				'noOfRsvps' => $noOfRsvps,
				'totalScannedAttendees' => $totalScannedAttendees,
				'totalTicket' => $totalticket_count,
				'ticketsold' => $totalticketsold_count,
				'revenue' => number_format($totalticketrevenue_count),
				'currency' => "$",
				'sales' => $dates_data_all,
				'paymentMethod' => $paymentbreak,
				'ticketTypes' => $total_ticket_sale,
				'requestBreakout' => $request_breakout,
			);

			$response['success'] = true;
			$response['data'] = $data;
		} else {
			$response['success'] = false;
			$response['status'] = 'Invalid method';
		}
		echo json_encode($response);
		die;
	}

	public function organizerdashboardapinew()
	{

		$this->autoRender = false;

		$this->loadModel('Eventdetail');
		$this->loadModel('Committeeassignticket');
		$this->loadModel('Ticket');
		$this->loadModel('Cart');
		if ($this->request->is(['post', 'put'])) {

			$user_id = $_REQUEST['userId'];
			$event_id = $_REQUEST['eventId'];

			$totalopen_sales_ticket = $this->Eventdetail->find('all')->select(['sum' => 'SUM(Eventdetail.count)'])->where(['Eventdetail.eventid' => $event_id])->first();

			$totalcommitee_sales_ticket = $this->Committeeassignticket->find('all')->select(['sum' => 'SUM(Committeeassignticket.count)'])->where(['Committeeassignticket.event_id' => $event_id])->first();

			$totalticket = $totalopen_sales_ticket['sum'] + $totalcommitee_sales_ticket['sum'];
			if ($totalticket) {
				$totalticket_count = $totalticket;
			} else {
				$totalticket_count = 0;
			}

			$totalticket_sold = $this->Ticket->find('all')->select(['sum' => 'SUM(Ticket.ticket_buy)'])->where(['Ticket.event_id' => $event_id])->first();

			if ($totalticket_sold['sum']) {
				$totalticketsold_count = $totalticket_sold['sum'];
			} else {
				$totalticketsold_count = 0;
			}

			$totalticket_sold_revenue = $this->Ticket->find('all')->select(['sumamount' => 'SUM(Ticket.amount)'])->where(['Ticket.event_id' => $event_id])->first();
			//pr($totalticket_sold_revenue);
			if ($totalticket_sold_revenue['sumamount']) {
				$totalticketrevenue_count = $totalticket_sold_revenue['sumamount'];
			} else {
				$totalticketrevenue_count = 0;
			}

			//pr($totalticket_payment_online); die;
			//$payment_method =[];

			$totalticket_payment_online = $this->Ticket->find('all')->contain(['Orders'])->select(['onlineamount' => 'SUM(Ticket.amount)'])->where(['Ticket.event_id' => $event_id, 'Orders.paymenttype' => 'Online'])->first();

			if ($totalticket_payment_online['onlineamount']) {
				$payment_method['type'] = "Online";
				$payment_method['count'] = (int)$totalticket_payment_online['onlineamount'];
			} else {
				$payment_method['type'] = "Online";
				$payment_method['count'] = 0;
			}

			$totalticket_payment_offline = $this->Ticket->find('all')->contain(['Orders'])->select(['offlineamount' => 'SUM(Ticket.amount)'])->where(['Ticket.event_id' => $event_id, 'Orders.paymenttype IN' => ['Cash', 'EventOffice', 'Comps']])->first();

			if ($totalticket_payment_offline['offlineamount']) {
				$payment_method_offline['type'] = "Offline";
				$payment_method_offline['count'] = (int) $totalticket_payment_offline['offlineamount'];
			} else {
				$payment_method_offline['type'] = "Offline";
				$payment_method_offline['count'] = 0;
			}

			$paymentbreak[] = $payment_method;
			$paymentbreak[] = $payment_method_offline;

			$total_ticket_sale = array();
			$ticket_types = $this->Eventdetail->find('all')->where(['Eventdetail.eventid' => $event_id])->toarray();
			foreach ($ticket_types as $key => $value) {

				$ticket_id = $value['id'];
				$ticket_types_amount = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(amount)'])->where(['Ticket.event_id' => $event_id, 'Ticket.event_ticket_id' => $ticket_id])->first();
				$ticket_types_sale = $this->Ticket->find('all')->Select(['ticket_buy' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $event_id, 'Ticket.event_ticket_id' => $ticket_id])->first();
				$data_ticket['ticket'] = $value['title'];
				$data_ticket['count'] = (int)$ticket_types_sale['ticket_buy'];
				$total_ticket_sale[] = $data_ticket;
			}

			$comitee_approved = $this->Cart->find('all')->select(['no_tickets' => 'SUM(no_tickets)'])->where(['Cart.event_id' => $event_id, 'Cart.ticket_type' => 'committesale', 'Cart.status' => 'Y'])->order(['Cart.user_id' => 'ASC'])->first();
			$total_approved_ticket = $comitee_approved['no_tickets'];
			if ($total_approved_ticket) {
				$total_approved_ticket = $total_approved_ticket;
			} else {
				$total_approved_ticket = 0;
			}
			$total_ticket['type'] = "Total";
			$total_ticket['count'] = $totalticket_count;

			$pendingcountt = $totalticket_count - $totalticketsold_count;
			$total_pending_ticket['type'] = "Pending";
			$total_pending_ticket['count'] = $pendingcountt;

			$total_appoved_ticket['type'] = "Approved";
			$total_appoved_ticket['count'] = $total_approved_ticket;

			$total_completed_ticket['type'] = "Completed";
			$total_completed_ticket['count'] = (int)$totalticketsold_count;

			$request_breakout[] = $total_ticket;
			$request_breakout[] = $total_pending_ticket;
			$request_breakout[] = $total_appoved_ticket;
			$request_breakout[] = $total_completed_ticket;

			$singleevent_detail = $this->Event->find('all')->contain(['Currency'])->where(['Event.id' => $event_id])->first();
			$dates = array();

			$date1 = date('Y-m-d', strtotime($singleevent_detail['sale_start']));
			$date2 = date('Y-m-d', strtotime($singleevent_detail['sale_end']));

			$stepVal = '+1 day';
			$format = 'd-m-Y';

			while ($current <= $date2) {
				$dates[] = date($format, $current);
				$current = strtotime($stepVal, $current);
			}

			foreach ($dates as $value) {
				$dates_data['date'] = $value;

				$ticketsold_date  = $this->Ticket->find('all')->where(['Ticket.event_id' => $event_id, 'DATE(Ticket.created)' => date('Y-m-d', strtotime($value))])->count();
				$dates_data['count'] = $ticketsold_date;

				$dates_data_all[] = $dates_data;
			}

			$data = array(
				'totalTicket' => $totalticket_count,
				'ticketsold' => $totalticketsold_count,
				'revenue' => number_format($totalticketrevenue_count),
				'currency' => "$",
				'sales' => $dates_data_all,
				'paymentMethod' => $paymentbreak,
				'ticketTypes' => $total_ticket_sale,
				'requestBreakout' => $request_breakout,
			);

			$response['success'] = true;
			$response['data'] = $data;
		} else {
			$response['success'] = false;
			$response['status'] = 'Invalid method';
		}
		echo json_encode($response);
		die;
	}

	public function getProfile()
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$response = array();

		$user_id = $_REQUEST['userId'];
		$user = $this->Users->find('all')->where(['Users.id' => $user_id])->first();
		if ($user) {
			$response["success"] = true;
			$response["status"] = null;
			$output['userId'] = $user['id'];
			$output['fName'] = $user['name'];
			$output['lName'] = $user['lname'];
			$output['registerdon'] = date('d-m-Y', strtotime($user['created']));
			$output['dob'] = date('d-m-Y', strtotime($user['dob']));
			$output['gender'] = $user['gender'];
			$output['mobileNumber'] = $user['mobile'];
			$output['emailId'] = $user['email'];
			$output['emailRelatedEvents'] = $user['emailRelatedEvents'];
			$output['emailNewsLetter'] = $user['emailNewsLetter'];
			$output['ismobverify'] = $user['is_mob_verify'];
			$output['isProfileVerify'] = ($user['profile_image'] == '') ? 'N' : 'Y';
			$imagename =  $user['profile_image'];
			if ($imagename) {
				$image =  $imagename;
			} else {
				$image =  'noimage.jpg';
			}
			$output['image'] = IMAGE_PATH . 'Usersprofile/' . $image;

			if ($user['role_id'] == '2') {
				$output['roleId'] = 'ORGANISER';
			} else if ($user['role_id'] == '3') {
				$output['roleId'] = 'USER';
			}
			$response["profile"] = $output;
		} else {
			$response["success"] = false;
			$response["status"] = "User not exist!";
		}

		echo json_encode($response);
		die;
	}

	public function sendverfiycode()
	{
		$this->loadModel('Countries');
		$this->loadmodel('Users');

		$userid = $_REQUEST['userId'];
		$mob = $_REQUEST['mobile'];
		$country_id = $_REQUEST['country'];
		$service = $_REQUEST['service'];

		if ($this->request->is('post')) {

			// pr($this->request->data);exit;

			$country = $this->Countries->get($country_id);
			$mobilewithcode = $country['words'] . preg_replace('/[^0-9]/', '', $mob);
			$user = $this->Users->get($userid);

			$mobile_check = $this->Users->find('all')->where(['mobile' => $mobilewithcode])->first();


			if (!empty($mobile_check)) {
				$response["success"] = false;
				$response['status'] = false;
				$response["message"] = 'Mobile number already verified with other account.';
				echo json_encode($response);
				die;
			}

			$mob_verify_code = strtoupper(strtolower(substr(md5(uniqid(rand(), true)), 6, 6)));
			$userdata['mob_verify_code'] = $mob_verify_code;
			$userdata['mobileverifynumber'] = $mobilewithcode;
			$userdata['country'] = $country['CountryName'];
			$userdata['service'] = $service;
			$message = "*Eboxtickets Verification Code*%0AYour mobile verfication code is *" . $mob_verify_code . "*%0A%0ALogin to your profile to Verify your phone number.%0A%0ARegards,%0AEboxtickets.com";

			$users = $this->Users->patchEntity($user, $userdata);

			if ($this->Users->save($users)) {
				$sendMessage = $this->whatsappmsg($mobilewithcode, $message);
				$response["success"] = true;
				$response['status'] = true;
				$response["message"] = "You have received your verication code in your mobile number.";
				echo json_encode($response);
				die;
			}
		}
	}

	public function verfiycode()
	{
		$this->loadmodel('Users');
		$this->loadmodel('Ticket');
		$userid = $_REQUEST['userId'];
		$mobverifycode = $_REQUEST['mobverifycode'];

		if ($this->request->is('post')) {

			$user = $this->Users->get($userid);

			$mobile_check = $this->Users->find('all')->where(['mobile' => $user['mobileverifynumber']])->first();

			if (!empty($mobile_check)) {
				$response["success"] = false;
				$response["status"] = false;
				$response["message"] = 'Mobile number already verified with other account';
				echo json_encode($response);
				die;
			}
			if ($mobverifycode == $user['mob_verify_code']) {
				$userdata['is_mob_verify'] = 'Y';
				$userdata['mobile'] =  $user['mobileverifynumber'];
				$users = $this->Users->patchEntity($user, $userdata);
				if ($savedata = $this->Users->save($users)) {

					// Update number on the all tickets
					$find_tickets = $this->Ticket->find('all')->where(['cust_id' => $user['id']])->toArray();
					if (!empty($find_tickets)) {
						foreach ($find_tickets as $ticket) {
							$ticketEntity = $this->Ticket->get($ticket['id']);
							$ticketEntity->mobile = $savedata['mobile'];
							$this->Ticket->save($ticketEntity);
						}
					}

					$message = "*Eboxtickets: Mobile Verification*%0A%0AYour mobile number is verified successfully.%0A%0ARegards,%0A%0AEboxtickets.com";
					$this->whatsappmsg($user['mobileverifynumber'], $message);
					$response["success"] = true;
					$response["status"] = true;
					$response["message"] = "Mobile number has been verified successfully.";
					echo json_encode($response);
					die;
				}
			} else {
				$response["success"] = false;
				$response["status"] = false;
				$response["message"] = "verification code is invalid.";
				echo json_encode($response);
				die;
			}
		}
	}

	// public function resetImei()
	// {
	// 	$response = array();
	// 	$mob = $_REQUEST['mobileNumber'];
	// 	$this->loadmodel('Users');
	// 	$user = $this->Users->find('all')->where(['Users.mobile' => $mob])->first();

	// 	$Pack = $this->Users->get($user['id']);
	// 	$fkey = rand(1, 10000);
	// 	$Pack->fkey = $fkey;
	// 	$this->Users->save($Pack);
	// 	$mid = base64_encode(base64_encode($user['id'] . '/' . $fkey));
	// 	$url = SITE_URL . "users/resetImei/" . $mid;
	// 	/*sending email start */
	// 	$this->loadmodel('Templates');
	// 	$profile = $this->Templates->find('all')->where(['Templates.id' => 23])->first();

	// 	$subject = $profile['subject'];
	// 	$from = $profile['from'];
	// 	$fromname = $profile['fromname'];
	// 	$name = $user['name'];
	// 	$email = $user['email'];
	// 	$user_id = $user['id'];
	// 	$to  = $email;
	// 	$formats = $profile['description'];
	// 	$site_url = SITE_URL;
	// 	$message1 = str_replace(array('{Name}', '{Email}', '{Userid}', '{site_url}', '{url}'), array($name, $email, $user_id, $site_url, $url), $formats);
	// 	$message = stripslashes($message1);
	// 	$message = '
	// 				<!DOCTYPE HTML>
	// 				<html>
	// 				<head>
	// 				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	// 				<title>Mail</title>
	// 				</head>
	// 				<body style="padding:0px; margin:0px;font-family:Arial,Helvetica,sans-serif; font-size:13px;">
	// 				' . $message1 . '
	// 				</body>
	// 				</html>
	// 				';	//die;
	// 	//	echo $message; die;
	// 	$headers = 'MIME-Version: 1.0' . "\r\n";
	// 	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	// 	//$headers .= 'To: <'.$to.'>' . "\r\n";
	// 	$headers .= 'From: ' . $fromname . ' <' . $from . '>' . "\r\n";
	// 	$emailcheck = mail($to, $subject, $message, $headers);
	// 	/*   sending email end */

	// 	if ($emailcheck) {
	// 		$response["success"] = 1;
	// 	} else {
	// 		$response["success"] = 0;
	// 	}
	// 	echo json_encode($response);
	// 	die;
	// }

	// to fetch registerImeiVisitor
	public function userLogin()
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$response = array();

		//$imei = trim($this->request->data['imeiId']);
		$email = trim($this->request->data['email']);
		$password = trim($this->request->data['password']);

		$data['email'] = $email;
		$data['password'] = $password;
		$user = $this->Auth->identify($data);

		if ($user) {
			$userData = array();
			// pr($user);die;
			if (isset($email) && !empty($email)) {

				$userexistsfirst = $this->Users->find('all')->where(['Users.email' => $email])->first();

				if ($userexistsfirst['status'] == 'N' || $userexistsfirst['is_suspend'] == 'Y') {

					if ($userexistsfirst['is_suspend'] == 'Y') {
						$response['success'] = false;
						$response['status'] = 'Your account is deleted';
						$response['message'] = 'Your account is deleted';
						echo json_encode($response);
						die;
					} else {
						$response['success'] = false;
						$response['status'] = 'Email verification is not completed you have recieved email your register email';
						$response['message'] = 'Email not verify.';
						echo json_encode($response);
						die;
					}
				}

				// if (!empty($userexistsfirst['imei'])) {

				// 	if ($imei != $userexistsfirst['imei']) {
				// 		$response['success'] = false;
				// 		$response['status'] = 'You are trying to access with a different device!';
				// 		echo json_encode($response);
				// 		die;
				// 	}
				// }

				if (!empty($userexistsfirst)) {
					if ($userexistsfirst) {
						$response['success'] = true;
						$response['status'] = true;
						$response['message'] = 'successfully logged in';
						$userData['userId'] = $userexistsfirst['id'];
						$userData['userName'] = $userexistsfirst['name'];
						$userData['mobileNumber'] = $userexistsfirst['mobile'];
						$userData['emailId'] = $userexistsfirst['email'];

						if ($userexistsfirst['role_id'] == '2') {
							$userData['roleId'] = 'ORGANISER';
						} else if ($userexistsfirst['role_id'] == '3') {
							$userData['roleId'] = 'USER';
						} elseif ($userexistsfirst['role_id'] == '4') {
							$userData['roleId'] =  'TICKETSCANNER';
						}
						$response['profile'] = $userData;
					} else {
						//$query->update()->set(['imei' => $imei])->where(['id' => $userexistsfirst['id']])->execute();
						$response['success'] = true;
						$response['status'] = true;
						$response['message'] = 'successfully logged in';
						$userData['userId'] = $userexistsfirst['id'];
						$userData['userName'] = $userexistsfirst['name'];
						$userData['mobileNumber'] = $userexistsfirst['mobile'];
						$userData['emailId'] = $userexistsfirst['email'];

						if ($userexistsfirst['role_id'] == '2' || $userexistsfirst['role_id'] == '4') {
							$userData['roleId'] = 'ORGANISER';
						} else if ($userexistsfirst['role_id'] == '3') {
							$userData['roleId'] = 'USER';
						}
						$response['profile'] = $userData;
					}
				} else {
					$response['success'] = false;
					$response['status'] = 'Your email and password are invalid.';
					$response['message'] = 'Your email and password are invalid.';
					echo json_encode($response);
					die;

					// $userdata = $this->Users->newEntity();
					// $this->request->data['imei'] = $imei;
					// $this->request->data['email'] = $email;
					// $this->request->data['role_id'] = CUSTOMERROLE;
					// $userdata = $this->Users->patchEntity($userdata, $this->request->data);
					// $userdata = $this->Users->save($userdata);
					// $last_id = $userdata->id;
					// $userdata = $this->Users->get($last_id);

					// $response['success'] = true;
					// $response['status'] = null;

					// $userData['userId'] = $userdata['id'];
					// $userData['userName'] = $userdata['name'];
					// $userData['mobileNumber'] = $userdata['mobile'];
					// $userData['emailId'] = $userdata['email'];


					// if ($userdata['role_id'] == '2') {
					// 	$userData['roleId'] = 'ORGANISER';
					// } else if ($userdata['role_id'] == '3') {
					// 	$userData['roleId'] = 'USER';
					// }
					// $response['profile'] = $userData;
				}
			} else {
				if (empty($email)) {
					$response['success'] = false;
					$response['status'] = 'Your email and password are invalid.';
				} else {
					$userss = $this->Users->find('all')->where(['Users.email' => $email, 'Users.status' => 'Y'])->first();
					if (!empty($userss)) {
						$response['success'] = true;
						$response['status'] = true;
						$response['message'] = 'successfully logged in';
						$userData['userId'] = $userss['id'];
						$userData['userName'] = $userss['name'];
						$userData['mobileNumber'] = $userss['mobile'];
						$userData['emailId'] = $userss['email'];
						if ($userss['role_id'] == '2') {
							$userData['roleId'] = 'ORGANISER';
						} else if ($userss['role_id'] == '3') {
							$userData['roleId'] = 'USER';
						}
						$response['profile'] = $userData;
					} else {
						$response['success'] = false;
						$response['status'] = 'Email verification is not completed you have recieved email your register email';
						$response['message'] = 'Email not verify.';
						echo json_encode($response);
						die;
					}
				}
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'Your email and password are invalid.';
			$response['message'] = 'Your email and password are invalid.';
		}

		echo json_encode($response);
		die;
	}


	// to fetch registerImeiVisitor
	// public function registerImeiVisitor()
	// {
	// 	$this->autoRender = false;
	// 	$this->loadmodel('Users');
	// 	$response = array();
	// 	if (isset($_REQUEST['imei']) && !empty($_REQUEST['imei'])) {

	// 		if ($exists) {
	// 			$response["success"] = 0;
	// 		} else {
	// 			if (!empty($imei)) {
	// 				$userdata = $this->Users->newEntity();
	// 				$this->request->data['imei'] = $imei;
	// 				$userdata = $this->Users->patchEntity($userdata, $this->request->data);
	// 				$userdata = $this->Users->save($userdata);
	// 				$response["success"] = 1;
	// 			}
	// 		}
	// 	}
	// 	echo json_encode($response);
	// }

	//to scan ticket
	public function scanTicket()
	{

		$this->autoRender = false;
		$this->loadmodel('Users');
		$this->loadmodel('Ticketdetail');
		$this->loadmodel('Ticketdetail');
		$this->loadmodel('Event');
		$this->loadmodel('Ticket');
		$this->loadmodel('Ticket');
		$this->loadModel('Package');
		$this->loadModel('Packagedetails');
		$response = array();
		// We have recieved from app side in the request data 
		// ["userId", "1250"]
		// ["ticketId", "T4420"]
		// ["eventOrganizerId", "142"]
		// ["scannerId", 142]
		// ["packageId", "12"]

		// $response["success"] = false;
		// $response["status"] = $_REQUEST['userId'].','.$_REQUEST['ticketId'] .','.$_REQUEST['eventOrganizerId'] .','.$_REQUEST['scannerId'];
		// echo json_encode($response);
		// die;

		$tick_details = $this->Ticketdetail->find('all')->where(['Ticketdetail.ticket_num' => $_REQUEST['ticketId']])->first();
		$ticket_check = $this->Ticket->find('all')->contain(['Event'])->where(['Ticket.id' => $tick_details['tid']])->first();


		$usercheck = $this->Users->find('all')->where(['Users.id' => $_REQUEST['scannerId'], 'Users.is_suspend' => 'N'])->first();

		$multiple_eventid = (explode(",", $usercheck['eventId']));

		if (empty($usercheck)) {
			$response["success"] = false;
			$response["status"] = "You are Suspend for scan the QR code !!";
			echo json_encode($response);
			die;
		}


		if (!in_array($ticket_check['event_id'], $multiple_eventid) && $_REQUEST['eventOrganizerId'] != $usercheck['id']) {
			$response["success"] = false;
			$response["status"] = "You are not authorized to scan this QR code !!";
			echo json_encode($response);
			die;
		}

		if ($usercheck['parent_id'] == $_REQUEST['eventOrganizerId'] || $_REQUEST['eventOrganizerId'] == $usercheck['id']) {
			if ($_REQUEST['userId'] && $_REQUEST['ticketId']) {
				$tick_details = $this->Ticketdetail->find('all')->contain(['Users'])->where(['Ticketdetail.ticket_num' => $_REQUEST['ticketId']])->first();

				$$userProfile =  $tick_details['user']['profile_image'];
				if ($$userProfile) {
					$image =  $$userProfile;
				} else {
					$image =  'noimage.jpg';
				}

				$response['userImage'] = IMAGE_PATH . 'Usersprofile/' . $image;
				$response['ticketNumber'] = $tick_details['ticket_num'];
				$response['usedBy'] = $tick_details['name'];
				$response['isPackage'] = ($tick_details['package_id']) ? true : false;


				// $packDetails = $this->Package->find()
				// ->contain(['Packagedetails' =>function ($q) {
				// 	return $q->where(['Packagedetails.addon_id IS NOT' => NULL]);
				// }])
				// ->where(['Package.id' => $_REQUEST['packageId']])
				// ->first();

				if ($tick_details['package_id']) {

					$packDetails = $this->Package->find()
						->contain([
							'Packagedetails' => [
								'Addons' // Include the Addons association
							]
						])
						->where(['Package.id' => $_REQUEST['packageId']])
						->matching('Packagedetails', function ($q) {
							return $q->where(['Packagedetails.addon_id IS NOT' => NULL]);
						})
						->first();

					$addonsNames = collection($packDetails->packagedetails)
						->extract('addon.name')
						->toArray();

					if ($response['isPackage']) {
						$response['packageName'] = $packDetails['name'];
						$response['addons'] = $addonsNames;
					}
				}

				if ($tick_details['status'] == '1') {
					$response["success"] = false;
					// $response["status"] = "Ticket already used! Name: " . $tick_details['name'] . " | Ticket Number: " . $tick_details['ticket_num'] . ' || Date & Time:' . date('d-m-Y h:i:s A', strtotime($tick_details['usedate']));

					$response["status"] = "Unsuccessful Scan";
					$response['usedTime'] = date('D, d M Y & h:i A', strtotime($tick_details['usedate']));
				} else {

					$Pack = $this->Ticketdetail->get($tick_details['id']);
					$Pack->status = '1';
					$Pack->usedate = date('Y-m-d h:i:s');
					$Pack->scanner_id = $_REQUEST['scannerId'];
					$userlist = $this->Users->find('all')->where(['Users.id' => $_REQUEST['userId'], 'Users.status' => 'Y'])->first();
					$Pack->usedby = $userlist['mobile'];
					$this->Ticketdetail->save($Pack);
					$response["success"] = true;
					$response["status"] = "Successful Scan";
				}
			}
		} else {
			$response["success"] = false;
			$response["status"] = "You are not authorized to scan this QR code !!";
		}

		echo json_encode($response);
		die;
	}

	public function qrcodeproticketnew($userid, $namess, $event_org_id)
	{

		while (1) {
			$dirname = 'temp';
			$PNG_TEMP_DIR = WWW_ROOT . 'qrimages' . DS . $dirname . DS;
			//$PNG_WEB_DIR = 'temp/';
			if (!file_exists($PNG_TEMP_DIR))
				mkdir($PNG_TEMP_DIR);
			$filename = $PNG_TEMP_DIR . 'EBX.png';
			$name = $userid . "," . $namess . "," . $event_org_id;
			//$name=$name;
			$errorCorrectionLevel = 'M';
			$matrixPointSize = 4;

			$filename = $PNG_TEMP_DIR . 'EBX' . md5($name . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
			\QRcode::png($name, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
			//display generated file
			$qrimagename = basename($filename);
			return $qrimagename;
		}
	}

	public function getSoldTickets()
	{
		$this->autoRender = false;
		$this->loadmodel('Ticket');
		//$userId = $this->request->data['userId'];
		$eventId = $this->request->data['eventId'];
		$totalticket_sold = $this->Ticket->find('all')->contain(['Orders' => ['Users']])->where(['Ticket.event_id' => $eventId])->group('order_id')->order(['Ticket.id' => 'desc'])->toarray();

		$evemt_detail = $this->Event->find('all')->contain(['Currency'])->where(['Event.id' => $eventId])->first();
		//pr($evemt_detail);
		foreach ($totalticket_sold as $key => $value) {
			$orders = $this->Ticket->find('all')->contain(['Ticketdetail', 'Orders' => ['Users'], 'Eventdetail'])->where(['Ticket.event_id' => $eventId, 'Ticket.order_id' => $value['order']['id']])->toarray();

			//pr($orders); die;
			$output['date'] = date('d-m-Y', strtotime($value['order']['created']));
			$output['name'] = $value['order']['user']['name'] . " " . $value['order']['user']['lname'];
			$output['totalticket'] = (int) count($orders);
			$output['currency'] = $evemt_detail['currency']['Currency_symbol'];
			$output['totalamount'] = $value['order']['total_amount'];
			$output['paymentType'] = $value['order']['paymenttype'];
			$output['tickets'] = array();

			foreach ($orders as $keyid => $tickename) {
				$ticket_detail['ticketname'] = $tickename['eventdetail']['title'];
				$ticket_detail['qty'] = (int) $tickename['ticket_buy'];
				$ticket_detail['currency'] = $evemt_detail['currency']['Currency_symbol'];
				$ticket_detail['amount'] = $tickename['amount'];
				//$ticket_detail['status'] = $ticket_count;
				$output['tickets'][] = $ticket_detail;
			}


			$output_data[]  = $output;
		}
		$response['success'] = true;
		$response['soldtickets'] = $output_data;
		echo json_encode($response);
		die;
	}

	public function getCommTicketDetails()
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$this->loadmodel('Eventdetail');
		$this->loadmodel('Event');
		$this->loadmodel('Committeeassignticket');
		$this->loadmodel('Ticket');
		$this->loadmodel('Committe');


		//$userId = $this->request->data['userId'];
		$eventId = $this->request->data['eventId'];
		$eventlist = $this->Committe->find('all')->contain(['Event' => 'Eventdetail', 'Users'])->where(['Committe.event_id' => $eventId])->toArray();


		foreach ($eventlist as $key => $value) {
			//$output['tickets'] = [];
			$getdetail = $this->Committeeassignticket->find('all')->contain(['Eventdetail'])->where(['Committeeassignticket.user_id' => $value['user']['id'], 'Committeeassignticket.event_id' => $eventId])->toArray();

			//$output['eventName'] = $value['event']['name'];
			$output['name'] = $value['user']['name'] . " " . $value['user']['lname'];
			$output['mobile'] = $value['user']['mobile'];
			$output['email'] = $value['user']['email'];
			if ($value['user']['profile_image']) {
				$output['profileImage'] = IMAGE_PATH . 'Usersprofile/' . $value['user']['profile_image'];
			} else {
				$output['profileImage'] = SITE_URL . "images/Usersprofile/noimage.jpg";
			}

			$output['tickets'] = array();
			foreach ($getdetail as $keyid => $tickename) {
				$ticket_count = $this->Ticket->find('all')->where(['Ticket.committee_user_id' => $value['user']['id'], 'Ticket.event_id' => $eventId, 'Ticket.event_ticket_id' => $tickename['eventdetail']['id'], 'Ticket.status' => 'Y'])->count();

				$ticket_detail['id'] = $tickename['eventdetail']['id'];
				$ticket_detail['name'] = $tickename['eventdetail']['title'];
				$ticket_detail['totalTickets'] = $tickename['count'];
				$ticket_detail['soldTickets'] = $ticket_count;
				$ticket_detail['Remaining'] = $tickename['count'] - $ticket_count;
				$output['tickets'][] = $ticket_detail;
			}

			$output_data[]  = $output;
		}

		$response['success'] = true;
		$response['committee'] = $output_data;
		echo json_encode($response);
		die;
	}

	public function soldTickets()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Ticket');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Company');
		$this->loadModel('Countries');
		$userId = $_REQUEST['userId'];
		$output = array();
	}
	 
	//get committee list 
	public function getCommittee()
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$this->loadmodel('Committe');
		$this->loadmodel('Event');
		$this->loadmodel('Eventdetail');


		$response = array();
		$response['success'] = true;
		$response['status'] = null;
		$response['Committee'] = array();
		$response['Tickets'] = array();
		$userId = $this->request->data['userId'];

		$eventlist = $this->Committe->find('all')->contain(['Event', 'Users'])->toArray();

		if ($eventlist) {
			foreach ($eventlist as $key => $value) {
				// pr($value);	die;	
				if ($value['event']['event_org_id'] != $userId) {
					continue;
				}

				if ($value['user']['profile_image']) {
					$image =  $value['user']['profile_image'];
				} else {
					$image =  'noimage.jpg';
				}

				// foreach ($commitee as $key => $comValue) {
				$output['eventName'] = $value['event']['name'];
				$output['id'] = $value['id'];
				$output['name'] = $value['user']['name'];
				$output['value'] = $value['user']['name'];
				$output['email'] = $value['user']['email'];
				$output['mobile'] = $value['user']['mobile'];
				$output['profileImage'] = IMAGE_PATH . 'Usersprofile/' . $image;
				// $output['Tickets']['id'] = 11;
				// $output['Tickets']['name'] = 'Early';
				// $output['Tickets']['totalTickets'] = 17;
				// $output['Tickets']['soldTickets'] = 12;
				// $output['Tickets']['Remaining'] = 5;
				array_push($response['Committee'], $output);
				// }
			}
		}

		echo json_encode(array_unique($response));
		die;
	}

	//get attendees
	public function getAttendees()
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$this->loadmodel('Event');
		$this->loadmodel('Eventdetail');
		$this->loadmodel('Ticket');
		$this->loadmodel('Ticketdetail');
		$this->loadModel('Attendeeslist');

		$response = array();

		$response['attendeesData'] = array();
		$userId = $this->request->data['userId'];
		$eventId = $this->request->data['eventId'];
		$evetn = $this->Event->get($eventId);

		if (empty($evetn['submit_count'])) {
			$attendees = $this->Attendeeslist->find('all')->contain(['Users'])->where(['Attendeeslist.event_id' => $eventId])->order(['Ticketdetail.id' => 'DESC'])->toarray();
		} else {
			$attendees = $this->Ticketdetail->find('all')->contain(['Users', 'Ticket'])->where(['Ticket.event_id' => $eventId])->order(['Ticketdetail.id' => 'DESC'])->toarray();
		}

		if ($attendees) {
			$response['success'] = true;
			$response['status'] = null;
			foreach ($attendees as $key => $value) {

				if ($value['user']['profile_image']) {
					$image =  $value['user']['profile_image'];
				} else {
					$image =  'noimage.jpg';
				}
				$output['ticketId'] = $value['id'];
				$output['userId'] = $value['user']['id'];
				$output['name'] = $value['user']['name'];
				$output['email'] = $value['user']['email'];
				$output['mobile'] = $value['user']['mobile'];
				$output['profileImage'] = IMAGE_PATH . 'Usersprofile/' . $image;
				$output['rsvp'] = $value['is_rsvp'];
				array_push($response['attendeesData'], $output);
			}
		} else {
			$response['success'] = false;
			$response["message"] = 'No invitations uploaded !!';
			$response['status'] = 'No invitations uploaded !!';
		}

		echo json_encode(array_unique($response));
		die;
	}

	// get questions and its items 
	public function getQuestions()
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$this->loadModel('Question');
		$this->loadModel('Questionitems');
		$this->loadModel('Eventdetail');
		$this->loadModel('Event');
		$this->loadModel('Ticket');

		$response = array();
		$output = array();
		$output1 = array();

		$eventId = $this->request->data['eventId'];
		$ticketId = $this->request->data['ticketId'];
		$count = $this->request->data['count'];
		$userId = $this->request->data['userId'];

		$questionlist = $this->Question->find('all')->contain('Questionitems')->where(['event_id' => $eventId, 'status' => 'Y'])->toArray();

		$userbuytickets = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.cust_id' => $userId, 'Ticket.event_id' => $eventId])->first();

		$ticketdetail = $this->Eventdetail->find('all')->where(['Eventdetail.id' => $ticketId])->first();
		$event = $this->Event->find('all')->where(['Event.id' => $eventId])->first();
		$available =  $event['ticket_limit'] - $userbuytickets['ticketsold'];

		if (!empty($questionlist[0]['id'])) {

			if ($available <= 0) {
				$response['success'] = false;
				$response['status'] = null;
				$response["message"] = 'Your tickets limits has been full !';
			} else {

				$response['Questions'] = array();
				foreach ($questionlist as $keyid => $value) {
					$checkticketquestion = explode(',', $value['ticket_type_id']);
					if (in_array($ticketId, $checkticketquestion)) {
					} else {
						continue;
					}

					if (empty($value['questionitems'])) {
						$output1 = null;
					} else {

						foreach ($value['questionitems'] as $key => $itemname) {
							$output1[$key]['id'] = $itemname['id'];
							$output1[$key]['itemname'] = $itemname['items'];
							$output1[$key]['t_id'] = $value['id'];
						}
					}

					$output['id'] = $value['id'];
					$output['type'] = $value['type'];
					$output['question'] = $value['question'];
					$output['name'] = $value['name'];
					$output['status'] = $value['status'];
					$output['items'] = $output1;

					array_push($response['Questions'], $output);
				}
				$response['success'] = true;
				$response['status'] = null;
				$response['leftTickets'] = $ticketdetail['count'];
				$response['userLeftTickets'] = $available;
			}
		} else {
			$response["success"] = false;
			$response["status"] = 'Question not found';
			$response["message"] = 'Question not found';
		}
		echo json_encode($response);
	}

	// to fetch postedEvents
	public function postedEvents()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Ticket');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Company');
		$this->loadModel('Countries');
		$this->loadModel('Committeeassignticket');
		$userId = $_REQUEST['userId'];
		$output = array();
		$totticket_all = 0;
		$eventlist = $this->Event->find('all')->contain(['Eventdetail', 'Ticket' => 'Ticketdetail', 'Company', 'Countries'])->where(['Event.event_org_id' => $userId])->order(['Event.id' => 'DESC'])->toArray();

		// $eventlist = $this->Event->find('all')->contain(['Eventdetail', 'Ticket' => 'Ticketdetail', 'Company', 'Countries'])->where(['Event.status' => 'Y', 'Event.event_org_id' => $userId])->order(['Event.id' => 'DESC'])->toArray();
		// pr($eventlist);exit;

		if (count($eventlist) > 0) {
			$response['success'] = true;
			$response['status'] = null;
			$response['postedEvents'] = array();
			foreach ($eventlist as $value) { //pr($value); die;
				$totalcommitee_sales_ticket = $this->Committeeassignticket->find('all')->select(['sum' => 'SUM(Committeeassignticket.count)'])->where(['Committeeassignticket.event_id' => $value['id']])->first();

				$tic_det = 0;
				$output['eventId'] = $value['id'];
				$output['eventName'] = $value['name'];
				$output['eventLocation'] = $value['location'];
				$output['eventOrgBy'] = $value['company']['name'];
				$output['country'] = $value['country']['CountryName'];
				$output['eventStartDateTime'] = date('D, d M Y & h:i A', strtotime($value['date_from']));
				$output['eventEndDateTime'] = date('D, d M Y & h:i A', strtotime($value['date_to']));
				$output['is_free'] = ($value['is_free'] == 'Y') ? true : false;
				foreach ($value['eventdetail'] as $totticket) { //pr($totticket);
					$totticket_all += $totticket['count'];
				}
				$totticket_all = $totticket_all + $totalcommitee_sales_ticket['sum'];

				$output['totalTickets'] = $totticket_all;
				$datass = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $value['id']])->toArray();

				foreach ($value['ticket'] as $data) {
					$tic_det += $this->Ticketdetail->find('all')->where(['Ticketdetail.tid' => $data['id'], 'Ticketdetail.status' => 1])->count();
				}
				//$output['totalTickets'] = $value['no_of_seats'];
				if ($datass['0']['ticketsold']) {
					$output['soldTickets'] = $datass['0']['ticketsold'];
				} else {
					$output['soldTickets'] = 0;
				}
				$output['scannedTickets'] = $tic_det;
				$output['pricePerTicket'] = $value['amount'];
				$output['eventImageURL'] = SITE_URL . 'images/eventimages/' . $value['feat_image'];

				array_push($response['postedEvents'], $output);
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'No Posted Events';
		}
		echo json_encode($response);
	}

	//To fetch scanned ticket
	public function getScannedTicket()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Ticket');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Company');
		$this->loadModel('Countries');
		$this->loadModel('Currency');
		$this->loadModel('Committeeassignticket');
		$eventId = $_REQUEST['eventId'];
		$output = array();
		$response['scannedTickets'] = array();
		$tic_det = 0;
		$eventlist = $this->Event->find('all')->contain(['Eventdetail', 'Currency', 'Ticket' => 'Ticketdetail'])->where(['Event.status' => 'Y', 'Event.id' => $eventId])->first();

		foreach ($eventlist['ticket'] as $data) {

			$tic_det = $this->Ticketdetail->find('all')->contain(['Users'])->where(['Ticketdetail.tid' => $data['id'], 'Ticketdetail.status' => 1])->first();
			if ($tic_det) {
				$scannerName = $this->Users->get($tic_det['scanner_id']);
				$output['scannedDate'] = date('d-m-Y', strtotime($tic_det['usedate']));
				$output['userName'] = $tic_det['user']['name'] . " " . $tic_det['user']['lname'];
				$output['ticketNumber'] = $tic_det['ticket_num'];
				$output['price'] = $eventlist['currency']['Currency_symbol'] . number_format($data['amount']) . ' ' . $eventlist['currency']['Currency'];
				$output['ticketNumber'] = $tic_det['ticket_num'];
				$output['scannedBy'] = $scannerName['name'] . " " . $scannerName['lname'];
				array_push($response['scannedTickets'], $output);
			}
		}
		// pr($eventlist);exit;
		if (count($response['scannedTickets']) == 0) {
			$response['status'] = 'No any scanned tickets';
			$response['message'] = 'No any scanned tickets';
			$response['success'] = false;
		} else {
			$response['status'] = null;
			$response['success'] = true;
		}
		echo json_encode($response);
		die;
	}

	// fetch my staff 
	public function getMyStaff()
	{
		$this->autoRender = false;
		$response = array();
		$output = array();
		$response['userData'] = array();
		$this->loadModel('Ticket');
		$this->loadModel('Eventdetail');
		$this->loadModel('Users');
		$orgniserId = $this->request->data['organiserId'];
		$getemployee = $this->Users->find('all')->where(['Users.parent_id' => $orgniserId])->order(['Users.id' => 'DESC'])->toarray();

		if ($getemployee) {
			$response['success'] = true;
			$response['status'] = null;
			foreach ($getemployee as $key => $data) {
				$output['id'] = $data['id'];
				$output['name'] = $data['name'] . ' ' . $data['lname'];
				$output['email'] = $data['email'];
				$output['mobile'] = $data['mobile'];
				$output['status'] = $data['is_suspend'];
				$retVal = ($data['profile_image'] == null) ? 'male2s.png' : $data['profile_image'];
				$output['profileImage'] = IMAGE_PATH . 'Usersprofile/' . $retVal;
				array_push($response['userData'], $output);
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'Staff not available';
			$response['message'] = 'Staff not available';
		}
		echo json_encode($response);
		die;
	}

	//fetch my staff scanned tickets
	public function staffScannedTickets()
	{
		$this->autoRender = false;
		$response = array();
		$output = array();
		$response['scannedTickets'] = array();
		$this->loadModel('Ticketdetail');
		$this->loadModel('Eventdetail');
		$this->loadModel('Currency');
		$this->loadModel('Ticket');
		$this->loadModel('Event');
		$this->loadModel('Orders');
		$eventId = $this->request->data['eventId'];
		$userId = $this->request->data['userId'];

		$ticketarray = $this->Ticketdetail->find('all')->where(['Ticketdetail.scanner_id' => $userId])->contain(['Ticket' => ['Event']])->order(['Ticketdetail.name' => 'DESC'])->toarray();

		// pr($ticketdata);exit;
		if ($ticketarray) {
			$response['success'] = true;
			$response['status'] = null;
			foreach ($ticketarray as $key => $ticketdata) {
				if ($ticketdata['ticket']['event_id'] != $eventId) {
					continue;
				}
				$eventlist = $this->Event->find('all')->contain(['Currency',])->where(['Event.id' => $ticketdata['ticket']['event_id']])->first();
				$output['scannedDate'] = date('d-m-Y', strtotime($ticketdata['usedate']));
				$retVal = ($ticketdata['name'] == null) ? 'N/A' : $ticketdata['name'];
				$output['userName'] = $retVal;
				$output['ticketNumber'] = $ticketdata['ticket_num'];
				$output['price'] = $eventlist['currency']['Currency_symbol'] . number_format($ticketdata['ticket']['amount']) . ' ' . $eventlist['currency']['Currency'];
				$output['ticketNumber'] = $ticketdata['ticket_num'];
				array_push($response['scannedTickets'], $output);
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'No tickets scanned';
			$response['message'] = 'No tickets scanned';
		}
		echo json_encode($response);
		die;
	}

	//get event list for scanner
	public function getEventForScanner()
	{
		$this->autoRender = false;
		$response = array();
		$response['Data'] = array();
		$output = array();
		$this->loadModel('Event');
		$this->loadModel('Eventdetail');
		$this->loadModel('Company');
		$userId = $this->request->data['userId'];
		$user = $this->Users->get($userId);
		$eventid = explode(",", $user['eventId']);

		if (!empty($user)) {
			$response['success'] = true;
			$response['status'] = null;
			$getevents = $this->Event->find('all')->where(['id IN' => $eventid])->order(['Event.id' => 'DESC'])->toArray();
			foreach ($getevents as $key => $evetdetails) {
				$output['Id'] = $evetdetails['id'];
				$output['Name'] = $evetdetails['name'];
				array_push($response['Data'], $output);
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'No any event available';
			$response['message'] = 'No any event available';
		}
		echo json_encode($response);
	}

	// To fetch totalEventsList
	// public function getEvents()
	// {
	// 	$this->autoRender = false;
	// 	$cudate = date("Y-m-d H:i:s");
	// 	$response = array();
	// 	$this->loadModel('Eventdetail');
	// 	$this->loadModel('Company');
	// 	$output = array();
	// 	$userId = $this->request->data['userId'];
	// 	$userInfo = $this->Users->get($userId);

	// 	// pr($userId);exit;
	// 	$eventlist = $this->Event->find('all')->contain(['Eventdetail', 'Company'])->where(['Event.date_to >=' => $cudate, 'Event.status' => 'Y', 'Event.admineventstatus' => 'Y'])->order(['Event.date_to' => 'ASC'])->toArray();

	// 	// pr($eventlist[0]['eventdetail']); die;		
	// 	// $output1=array();   
	// 	// $evntdetail =  $this->Eventdetail->find('all')->order(['Eventdetail.id'=>DESC])->toarray();
	// 	// pr($eventlist); die;

	// 	// $eventlist = $this->Event->find('all')->contain(['Eventdetail'])->where(['Event.event_org_id'=>$userId,'Event.status' => 'Y'])->order(['Event.id' => 'DESC'])->toArray();
	// 	// pr($eventlist);exit;

	// 	if (count($eventlist) > 0) {
	// 		$response['success'] = true;
	// 		$response['status'] = null;
	// 		$response['events'] = array();
	// 		$pattern = "/<[^\/>]*>([\s]?)*<\/[^>]*>/";
	// 		foreach ($eventlist as $value) {
	// 			if ($value['is_free'] == 'Y') {
	// 				$this->loadModel('Ticket');

	// 				$findone = $this->Ticket
	// 					->find('all')
	// 					->where([
	// 						'Ticket.event_id' => $value['id'],
	// 						'OR' => [
	// 							'Ticket.cust_id' => $userId,
	// 							'Ticket.mobile' => $userInfo['mobile']
	// 						]
	// 					])
	// 					->first();

	// 				$output['isAlreadyRegister'] =  ($findone) ? 'Y' : 'N';
	// 				// pr($output);exit;
	// 			}
	// 			$output['eventId'] = $value['id'];
	// 			$output['eventName'] = ucwords(strtolower($value['name']));
	// 			$output['eventDisc'] =  preg_replace($pattern, '', $value['desp']);
	// 			$output['isFree'] = ($value['is_free'] == 'Y') ? true : false;
	// 			$output['allowRegister'] = ($value['allow_register'] == 'Y') ? true : false;
	// 			$output['eventLocation'] = ucwords(strtolower($value['location']));
	// 			$output['eventCompany'] = $value['company']['name'];
	// 			$output['eventVideoUrl'] = isset($value['video_url']) ? $value['video_url'] : null;
	// 			$output['eventStartDateTime'] = date('D, d M Y & h:i A', strtotime($value['date_from']));
	// 			$output['eventEndDateTime'] = date('D, d M Y & h:i A', strtotime($value['date_to']));
	// 			// $event_end = date('F d , Y h:i A', strtotime($findevent['date_to']));
	// 			$output['eventImageURL'] = SITE_URL . 'images/eventimages/' . $value['feat_image'];
	// 			$share_url_exp = explode(".", $value['feat_image']);
	// 			$output['shareImageURL'] = SITE_URL . 'images/eventimages/' . $value['feat_image'];
	// 			$output['socialShare']['eventShortDisc'] = substr($value['desp'], 0, 30);
	// 			$output['socialShare']['weblink'] = $value['slug'];
	// 			// $data = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $value['id']])->toArray();
	// 			// $ticketsoldout = $data['0']['ticketsold'];
	// 			$output['ticketStartDate'] = date('d-m-Y', strtotime($value['sale_start']));

	// 			// $output['eventsDetail'] = array();
	// 			$allvalue = array();
	// 			foreach ($value['eventdetail'] as $value1) {
	// 				if ($value1['type'] == 'comps') {
	// 					continue;
	// 				}
	// 				$allvalue[] = $value1['price'];

	// 				if ($value1['sold_out'] == 'N') {
	// 					$output['isSoldOut'] = false;
	// 				} else {
	// 					$output['isSoldOut'] = true;
	// 				}


	// 				// if($min > $value1['price']){
	// 				// }
	// 				// $output1['ticketType'] = $value1['title'];
	// 				// $output['noOfTickets']=$value['no_of_seats']-$ticketsoldout;
	// 				// $output1['pricePerTicket'] = $value1['price'];
	// 				// $output1['ticketEndDate'] = $value1['sale_end'];
	// 				// $output1['isShouldOut'] = $value1['sold_out']=='N'?true:false;
	// 				// $output1['ticketSaleType'] = $value1['type'];

	// 				// array_push($output['eventsDetail'], $output1);
	// 			}
	// 			// die;
	// 			$min = min($allvalue);
	// 			$output['minTicketPrice'] = $min;
	// 			array_push($response['events'], $output);
	// 		}
	// 	} else {
	// 		$response['success'] = false;
	// 		$response['status'] = 'There are no events to show here';
	// 	}
	// 	echo json_encode($response);
	// }


	// Rupam 18-09-2023
	public function getEvents()
	{
		$this->autoRender = false;
		$cudate = date("Y-m-d H:i:s");
		$this->loadModel('Eventdetail');
		$this->loadModel('Company');
		$response = array();
		$response['events'] = array();
		$output = array();

		if (isset($this->request->data['userId'])) {
			$userId = $this->request->data['userId'];
			$userInfo = $this->Users->get($userId);

			$eventlist = $this->Event->find('all')->contain(['Eventdetail', 'Company'])
				->where([
					'Event.date_to >=' => $cudate,
					'Event.status' => 'Y',
					'Event.admineventstatus' => 'Y'
				])
				->order(['Event.date_to' => 'ASC'])
				->toArray();

			if (!empty($eventlist)) {
				$response['success'] = true;
				$response['status'] = null;
				$pattern = "/<[^\/>]*>([\s]?)*<\/[^>]*>/";

				foreach ($eventlist as $value) {
					$descriptions = preg_replace($pattern, '', $value['desp']);
					$output['eventId'] = $value['id'];
					$output['eventName'] = ucwords(strtolower($value['name']));
					$output['eventDisc'] = $descriptions;
					$output['isFree'] = ($value['is_free'] == 'Y');
					$output['allowRegister'] = ($value['allow_register'] == 'Y');
					$output['eventLocation'] = ucwords(strtolower($value['location']));
					$output['eventCompany'] = $value['company']['name'];
					$output['eventVideoUrl'] = isset($value['video_url']) ? $value['video_url'] : null;
					$output['eventStartDateTime'] = date('D, d M Y & h:i A', strtotime($value['date_from']));
					$output['eventEndDateTime'] = date('D, d M Y & h:i A', strtotime($value['date_to']));
					$output['eventImageURL'] = SITE_URL . 'images/eventimages/' . $value['feat_image'];
					$share_url_exp = explode(".", $value['feat_image']);
					$output['shareImageURL'] = SITE_URL . 'images/eventimages/' . $value['feat_image'];
					$output['socialShare']['eventShortDisc'] = $descriptions;
					$output['socialShare']['weblink'] = $value['slug'];
					$output['ticketStartDate'] = date('d-m-Y', strtotime($value['sale_start']));

					$allvalue = array();
					foreach ($value['eventdetail'] as $value1) {
						if ($value1['type'] == 'comps') {
							continue;
						}
						$allvalue[] = $value1['price'];

						if ($value1['sold_out'] == 'N') {
							$output['isSoldOut'] = false;
						} else {
							$output['isSoldOut'] = true;
						}
					}
					$min = min($allvalue);
					$output['minTicketPrice'] = $min;
					array_push($response['events'], $output);
				}

				ob_end_clean();
				header('Content-Type: application/json');
				echo json_encode($response);
				die;
			} else {
				$response['success'] = false;
				$response['status'] = 'There are no events to show here';
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'User ID not provided';
		}

		// Set the JSON content type header and output the response as JSON
		header('Content-Type: application/json');
		echo json_encode($response);
	}



	// Get event list userid based 
	public function getEventList()
	{
		$this->autoRender = false;
		$response = array();
		$response['Data'] = array();
		$output = array();
		$this->loadModel('Event');
		$this->loadModel('Eventdetail');
		$this->loadModel('Company');

		$userId = $this->request->data['userId'];

		$getevets = $this->Event->find('all')->where(['Event.event_org_id' => $userId])->order(['Event.id' => 'DESC'])->toArray();

		if (!empty($getevets)) {
			$response['success'] = true;
			$response['status'] = null;
			foreach ($getevets as $key => $evetdetails) {
				$output['Id'] = $evetdetails['id'];
				$output['Name'] = $evetdetails['name'];

				if ($evetdetails['id'] == '38') {
					$output['selected'] = 1;
				} else {
					$output['selected'] = 0;
				}

				array_push($response['Data'], $output);
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'No any event available';
			$response['message'] = 'No any event available';
		}
		echo json_encode($response);
	}

	public function eventDashboard()
	{
		$this->autoRender = false;
		$this->loadModel('Ticket');
		$this->loadModel('Event');
		$this->loadModel('Eventdetail');
		$this->loadModel('Company');
		$this->loadModel('Question');
		$this->loadModel('Committe');
		$this->loadModel('Addons');
		$this->loadModel('Users');
		$this->loadModel('Currency');

		$response = array();
		$output = array();
		$total_ticket_sale = 0;
		$noof = 0;
		$eventId = $this->request->data['eventId'];
		$userId = $this->request->data['userId'];
		$eventdeatail = $this->Event->find('all')->contain(['Eventdetail', 'Company', 'Currency'])->where(['Event.status' => 'Y', 'Event.id' => $eventId])->first();
		$sale_end = date('Y-m-d h:i:s a', strtotime($eventdeatail['sale_end']));
		foreach ($eventdeatail['eventdetail'] as $key => $tickedetials) {

			// pr($tickedetials);die;

			$total_ticket_sale = array();
			$ticket_id = $tickedetials['id'];
			$ticket_types_amount = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(amount)'])->where(['Ticket.event_id' => $eventId, 'Ticket.event_ticket_id' => $ticket_id])->first();
			$ticket_types_sale = $this->Ticket->find('all')->Select(['ticket_buy' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $eventId, 'Ticket.event_ticket_id' => $ticket_id])->first();

			$data_ticket['ticket'] = $tickedetials['title'];
			$data_ticket['amount'] = "$" . sprintf('%.2f', $ticket_types_amount['ticketsold']);
			$data_ticket_all[] = $data_ticket;

			// $total_ticket_sale[] = $tickedetials['title'];
			// $total_ticket_sale[] = (int)$ticket_types_sale['ticket_buy'];
			if ($ticket_types_sale['ticket_buy']) {
				$total_ticket_sale = $total_ticket_sale + $noof;
			}
		}

		$ticket_sales_amount = json_encode($data_ticket_all);
		pr($total_ticket_sale);
		die;

		// $output['ticketSold'] = $evetdetails['id'];
		// $output['totalTickets'] = $evetdetails['name'];
		// $output['revenue'] = $evetdetails['name'];
		// $output['totalTickets'] = $evetdetails['name'];

		array_push($response['Data'], $output);
	}

	// posted event details for organiser
	public function postedEventDetails()
	{
		$this->autoRender = false;
		$this->loadModel('Ticket');
		$this->loadModel('Event');
		$this->loadModel('Eventdetail');
		$this->loadModel('Company');
		$this->loadModel('Committe');
		$this->loadModel('Users');
		$this->loadModel('Currency');

		$response = array();
		$output = array();
		$output1 = array();
		$getadmin = $this->Users->get(1);

		$eventId = $this->request->data['eventId'];
		$userId = $this->request->data['userId'];
		// $date = date("Y-m-d h:i:s a");

		$eventlist = $this->Event->find('all')->contain(['Eventdetail', 'Company', 'Currency'])->where(['Event.id' => $eventId])->first();

		// $eventlist = $this->Event->find('all')->contain(['Eventdetail', 'Company', 'Currency'])->where(['Event.status' => 'Y', 'Event.id' => $eventId])->first();

		$committe_user = $this->Committe->find('all')->contain(['Users'])->where(['Committe.event_id' => $eventId])->order(['Committe.id' => 'DESC'])->count();
		if (count($eventlist) > 0) {
			$response['success'] = true;
			$response['status'] = null;
			$response['Setting'] = array();
			$response['Tickets'] = array();
			$response['Timing'] = array();

			foreach ($eventlist['eventdetail'] as $value) {

				$output['id'] = $value['id'];
				$output['name'] = $value['title'];
				$output['price'] = $eventlist['currency']['Currency_symbol'] . sprintf('%.2f', $value['price']) . ' ' . $eventlist['currency']['Currency'];
				$output['isCommittee'] = $value['type'] == 'committee_sales' ? true : false;
				array_push($response['Tickets'], $output);
			}

			$response['Timing']['startDate'] = date('Y-m-d h:i:s a', strtotime($eventlist['date_from']));
			$response['Timing']['endDate'] = date('Y-m-d h:i:s a', strtotime($eventlist['date_to']));
			$response['Timing']['saleStart'] = ($eventlist['is_free'] == 'Y') ? 'N/A' : date('Y-m-d h:i:s a', strtotime($eventlist['sale_start']));
			$response['Timing']['saleEnd'] = ($eventlist['is_free'] == 'Y') ? 'N/A' : date('Y-m-d h:i:s a', strtotime($eventlist['sale_end']));
			// array_push($response['Timing'], $output1);

			if ($committe_user) {
				$response['Committee']['count'] = $committe_user;
			}
			$pattern = "/<[^\/>]*>([\s]?)*<\/[^>]*>/";
			$response['eventImage'] =  SITE_URL . 'images/eventimages/' . $eventlist['feat_image'];
			$response['eventName'] = $eventlist['name'];
			$response['hostedBy'] = $eventlist['company']['name'];
			$response['Location'] = $eventlist['location'];
			$response['isFree'] = ($eventlist['is_free'] == 'Y') ? true : false;
			$response['Setting']['ticketLimitesPerPersion'] = $eventlist['ticket_limit'];
			$response['Setting']['approvalExpiry'] = $eventlist['approve_timer'];
			$response['Setting']['feeAssignment'] = $eventlist['fee_assign'];
			$response['Setting']['feePercent'] = $getadmin['feeassignment'];
			$response['Setting']['Currency'] = $eventlist['currency']['Currency'];
			$response['Description'][] = preg_replace($pattern, '', $eventlist['desp']);
		} else {
			$response['success'] = false;
			$response['status'] = 'Event Id not match !';
		}
		echo json_encode($response);
	}

	// Tickets details 
	public function eventDetails()
	{
		$this->autoRender = false;
		$this->loadModel('Ticket');
		$this->loadModel('Event');
		$this->loadModel('Eventdetail');
		$this->loadModel('Company');
		$this->loadModel('Question');
		$this->loadModel('Committe');
		$this->loadModel('Addons');
		$this->loadModel('Users');
		$this->loadModel('Currency');
		$this->loadModel('Package');
		$this->loadModel('Packagedetails');

		$response = array();
		$output = array();
		$output1 = array();
		$eventId = $this->request->data['eventId'];
		$userId = $this->request->data['userId'];
		$date = date("Y-m-d H:i:s");

		$eventlist = $this->Event->find('all')->contain(['Eventdetail', 'Company', 'Currency'])->where(['Event.status' => 'Y', 'Event.id' => $eventId])->first();
		$sale_end = date('Y-m-d H:i:s', strtotime($eventlist['sale_end']));
		if (strtotime($sale_end) >= strtotime($date)) {
			$response['isSaleClosed'] = false;
		} else {
			$response['success'] = false;
			$response['isSaleClosed'] = true;
			if ($eventlist['is_free'] == 'Y') {
				$response['message'] = 'This is an invite only Event.';
			} else {
				$response['message'] = 'Ticket sales for ' . $eventlist['name'] . ' event are currently closed.';
			}
			echo json_encode($response);
			die;
		}

		// $event_ticket_type = $this->Eventdetail->find('all')->where(['Eventdetail.eventid' => $id, 'Eventdetail.hidden' => 'Y', 'Eventdetail.type !=' => 'comps'])->order(['Eventdetail.id' => 'ASC'])->toarray();

		$userbuytickets = 0;
		if (count($eventlist) > 0) {
			$response['success'] = true;
			$response['status'] = null;
			$response['ticketDetails'] = array();
			$response['committeeDetails'] = array();
			$response['addonsDetails'] = array();
			$response['packageDetails'] = array();

			foreach ($eventlist['eventdetail'] as $value) {

				if ($value['type'] == 'comps' || $value['title'] == 'Comps' || $value['hidden'] == 'N') {
					continue;
				}
				$questions = $this->Question->find('all')->where(['Question.event_id' => $eventId, 'FIND_IN_SET(\'' . $value['id'] . '\',Question.ticket_type_id)'])->toarray();

				$data = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $eventId, 'event_ticket_id' => $value['id']])->first();
				$ticketsoldout = $data['ticketsold'];

				$userbuytickets = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.cust_id' => $userId, 'Ticket.event_id' => $eventId])->first();
				// pr($userbuytickets);die;

				if ($value['type'] == 'open_sales' && $value['count'] == $ticketsoldout) {
					$soldout = true;
				} else {
					$soldout = false;
				}

				$output['id'] = $value['id'];
				$output['ticketType'] = $value['title'];
				$output['noOfTickets'] = $value['count'] - $ticketsoldout;
				$output['pricePerTicket'] = $eventlist['currency']['Currency_symbol'] . sprintf('%.2f', $value['price']) . ' ' . $eventlist['currency']['Currency'];
				$output['isCommittee'] = $value['type'] == 'committee_sales' ? true : false;
				$output['isDirectSale'] = $value['type'] == 'open_sales' ? true : false;
				$output['isQuestion'] = isset($questions[0]['id']) ? true : false;
				$output['isSoldout'] = ($value['sold_out'] == 'Y' ? true : false);
				$output['isOutOfStock'] = $soldout;

				// if (isset($value['question_id'])) {
				// 	// $question = $this->Question->find('all')->where(['id' => $value['question_id'], 'status' => 'Y'])->first();

				// 	if (!empty($questions['ticket_type_id'])) {
				// 		$allitems = explode(",", $questions['ticket_type_id']);
				// 		// foreach ($allitems as $key => $itemvalue) {							
				// 		// 	$outputs3['name'] = $itemvalue;
				// 		// 	$outputs3['values']= $itemvalue;
				// 		// 	array_push($response['itemsDetails'], $outputs3);

				// 		// }
				// 		// $rrr = json_encode($response['itemsDetails']);

				// 	} else {
				// 		$allitems = null;
				// 	}

				// 	$output['question']['id'] = $questions['id'];
				// 	$output['question']['name'] = $questions['name'];
				// 	$output['question']['question'] = $questions['question'];
				// 	$output['question']['items'] = $allitems;
				// 	$output['question']['status'] = $questions['status'];
				// }

				array_push($response['ticketDetails'], $output);
			}

			$committe_user = $this->Committe->find('all')->contain(['Users'])->where(['Committe.event_id' => $eventId])->order(['Committe.id' => 'DESC'])->toarray();
			if ($committe_user[0]) {
				// $customer_id = explode(",", $eventlist['committee_memmberId']);
				// $commitee = $this->Committe->find('all')->contain('Users')->where(['Committe.id IN' => $customer_id])->toarray();
				foreach ($committe_user as $key => $comValue) {
					$output1['id'] = $comValue['user_id'];
					$output1['name'] = $comValue['user']['name'] . ' ' . $comValue['user']['lname'] . ' (' . $comValue['user']['email'] . ' )';
					$output1['value'] = $comValue['user']['name'];
					array_push($response['committeeDetails'], $output1);
				}
			}


			// Addons 
			// $addons = $this->Addons->find('all')->where(['Addons.status' => 'Y', 'Addons.event_id' => $eventId])->toarray();
			// if ($addons) {
			// 	foreach ($addons as $key => $addonsvalue) {
			// 		$output2['id'] = $addonsvalue['id'];
			// 		$output2['name'] = $addonsvalue['name'];
			// 		$output2['price'] = $addonsvalue['price'];
			// 		$output2['description'] = $addonsvalue['description'];
			// 		$output2['count'] = $addonsvalue['count'];
			// 		array_push($response['addonsDetails'], $output2);
			// 	}
			// }
			// pr($userbuytickets);exit;

			// For Package start

			$package_type = $this->Package->find('all')
				->contain(['Packagedetails' => ['Eventdetail']])
				->where(['Package.event_id' => $eventId, 'Package.hidden' => 'N'])
				->order(['Package.id' => 'ASC'])
				->toArray();

			if (!empty($package_type)) {
				$response['isPackage'] = true;
				foreach ($package_type as $key => $pack_detls) {
					$packTickDets = $this->Package->find()
						->contain('Packagedetails')
						->where(['Package.id' => $pack_detls['id']])
						->matching('Packagedetails', function ($q) {
							return $q->where(['Packagedetails.addon_id IS NULL']);
						})
						->select(['PackagedetailsCount' => 'SUM(Packagedetails.qty)', 'Package.package_limit'])
						->first();

					$totalSale = $this->Ticket->find()
						->where(['package_id' => $pack_detls['id']])
						->count() / $packTickDets['PackagedetailsCount'];

					$addonDetail = $this->Packagedetails->find('all')->where([
						'Packagedetails.addon_id IS NOT' => null,
						'Packagedetails.package_id' => $pack_detls['id']
					])->contain(['Addons'])->toArray();

					if (!empty($addonDetail)) {
						$addonTypes = [];
						foreach ($addonDetail as $addon_det) {
							$addonTypes[] = $addon_det['qty'] . ' ' . $addon_det['addon']['name'] . '(' . $addon_det['addon']['description'] . ')';
						}
						// $packOutput['addonTypes'] = implode(', ', $addonTypes);
						$packOutput['addonTypes'] = $addonTypes;
					}

					$packOutput['id'] = $pack_detls['id'];
					$packOutput['isSoldOut'] = (0 >= $pack_detls['package_limit'] - $totalSale) ? true : false;
					$packOutput['packageName'] = $pack_detls['name'];
					$packOutput['packagePrice'] = $eventlist['currency']['Currency_symbol'] . sprintf('%.2f', $pack_detls['grandtotal']) . ' ' . $eventlist['currency']['Currency'];

					$ticketTypes = [];
					foreach ($pack_detls['packagedetails'] as $pack_detail) {
						$ticketTypes[] = $pack_detail['eventdetail']['title'] . '(' . $pack_detail['qty'] . ' Ticket)';
					}
					// $packOutput['ticketTypes'] = implode(', ', $ticketTypes);
					$packOutput['ticketTypes'] = $ticketTypes;
					$packOutput['packageLimites'] = 1;

					array_push($response['packageDetails'], $packOutput);
				}
			} else {
				$response['isPackage'] = false;
			}

			// For Package end

			$response['ticketLimites'] = $eventlist['ticket_limit'];
			$response['userLeftTickets'] = $eventlist['ticket_limit'] - $userbuytickets['ticketsold'];
			$response['currencySymbol'] = $eventlist['currency']['Currency_symbol'];
			$response['currency'] = $eventlist['currency']['Currency'];
		} else {
			$response['success'] = false;
			$response['status'] = 'There are no events to show here';
		}
		echo json_encode($response);
	}

	// Add tickets in cart 
	public function addToCart()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Ticket');
		$this->loadModel('Users');
		$this->loadModel('Event');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Eventdetail');
		$this->loadModel('Cartquestiondetail');
		$this->loadModel('Templates');
		$this->loadModel('Cart');


		$reqData = $this->request->data;
		// pr($reqData);exit;
		$userfind = $this->Users->find('all')->where(['Users.id' => $reqData['userId']])->first();

		// check user verify or not 
		if ($userfind['is_mob_verify'] == 'Y' && $userfind['profile_image'] != '') {
			// if (true) {

			$digits = 10;
			$randomnumber = str_pad(rand(0, pow(10, $digits) - 1), $digits, '0', STR_PAD_LEFT);


			$ticketsDetails = json_decode($reqData['ticketsDetails'], true);
			$questionDetails = json_decode($reqData['questionDetails'], true);
			$packageDetails = json_decode($reqData['packageDetails'], true);

			if (!empty($packageDetails)) {
				$this->addPackageToCart($reqData);
				exit;
			} else {
				$deltepackage = $this->Cart->find('all')->where(['user_id' => $reqData['userId'], 'package_id IS NOT NULL'])->count();
				if ($deltepackage > 0) {
					$this->Cart->deleteAll(['Cart.user_id' => $reqData['userId'], 'package_id IS NOT NULL']);
				}
			}

			foreach ($ticketsDetails as $ticketid) {
				$checkticket = $this->Eventdetail->get($ticketid['ticketid']);
				if ($checkticket['sold_out'] == 'Y') {
					$response["success"] = false;
					$response["status"] = $checkticket['title'] . ' ticket has Sold Out';
					$response["message"] = $checkticket['title'] . ' ticket has Sold Out';
					echo json_encode($response);
					die;
				}
			}

			if (!empty($questionDetails)) {
				foreach ($questionDetails as $quest) {
					$newquestiondetails = $this->Cartquestiondetail->newEntity();
					$questiondata['user_id'] = $reqData['userId'];
					$questiondata['question_id'] = $quest['questionId'];
					$questiondata['event_id'] = $reqData['eventId'];
					$questiondata['ticket_id'] = $quest['ticketId'];
					$questiondata['user_reply'] = $quest['userReply'];
					$questiondata['serial_no'] = $randomnumber;
					$addquestionnew = $this->Cartquestiondetail->patchEntity($newquestiondetails, $questiondata);
					$this->Cartquestiondetail->save($addquestionnew);
				}
			}

			if ($ticketsDetails) {

				foreach ($ticketsDetails as $value) {

					$i = 1;
					for ($i; $i <= $value['noofTicket']; $i++) {

						$gettickets = $this->Eventdetail->get($value['ticketid']);
						if ($gettickets['type'] == 'open_sales') {
							$type = 'opensale';
							$status = 'Y';
							$commitee_user_id = null;
						} else {
							$type = 'committesale';
							$commitee_user_id = $value['commitee_user_id'];
							$status = 'N';
						}

						$newcart = $this->Cart->newEntity();
						$newinsert['user_id'] = $reqData['userId'];
						$newinsert['event_id'] = $reqData['eventId'];
						$newinsert['ticket_id'] = $value['ticketid'];
						$newinsert['no_tickets'] = 1;
						$newinsert['ticket_type'] = $type;
						$newinsert['description'] = $reqData['description'];
						$newinsert['commitee_user_id'] = $commitee_user_id;
						$newinsert['status'] = $status;
						$newinsert['serial_no'] = $randomnumber;
						$insertdata = $this->Cart->patchEntity($newcart, $newinsert);
						$this->Cart->save($insertdata);
					}
				}

				// send mail for ecommittee user start	
				if (!empty($commitee_user_id)) {

					$com_name = $this->Users->get($commitee_user_id);
					$getevent = $this->Event->get($reqData['eventId']);
					$committeename = $com_name['name'] . ' ' . $com_name['lname'];
					$requestername = $userfind['name'] . ' ' . $userfind['lname'];
					$eventname = ucwords(strtolower($getevent['name']));
					$url = SITE_URL . 'committee/pending';
					$site_url = SITE_URL;
					$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 26])->first();
					$from = $emailtemplate['fromemail'];
					$to = $com_name['email'];
					$subject = $emailtemplate['subject'] . ': ' . $requestername . ' for ' . $eventname;
					$formats = $emailtemplate['description'];

					$message1 = str_replace(array('{EventName}', '{RequesterName}', '{CommitteeName}', '{URL}', '{SITE_URL}'), array($eventname, $requestername, $committeename, $url, $site_url), $formats);
					$message = stripslashes($message1);
					$message = '<!DOCTYPE HTML>
					<html>			
					<head>
						<meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
						<title>Untitled Document</title>
						<style>
							p {
								margin: 9px 0px;
								line-height: 24px;
							}
						</style>			
					</head>			
					<body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
					$headers = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers .= 'From: <' . $from . '>' . "\r\n";
					$mail = $this->Email->send($to, $subject, $message);
					//  send mail complete

					// send watsappmessage start 
					$message = "*Eboxtickets: Incoming Request*%0AHi $committeename,%0A%0AYou just received a request from *" . $requestername . "* for *" . $eventname . '* Event.' . "%0A%0ARegards,%0AEboxtickets.com";
					$numwithcode = $com_name['mobile'];
					$this->whatsappmsg($numwithcode, $message);
					// send watsappmessage start 
				}

				$response["success"] = true;
				$response["status"] = 'Tickets add in to cart Successfully!!!.';
			} else {
				$response["success"] = false;
				$response["status"] = 'Select your tickets now.';
			}
		} else {
			$response["success"] = false;
			if ($userfind['is_mob_verify'] == 'N') {
				$response['isMobile'] = true;
				$response['message'] = "Your mobile number is not verify !";
			} 
			// else {
			// 	$response['isProfileImage'] = true;
			// 	$response['message'] = "Your profile image is not uploaded kindly upload !";
			// }
		}


		echo json_encode($response);
	}

	// addPackageToCart  
	public function addPackageToCart($reqData)
	{
		$this->autoRender = false;
		$response = array();

		$this->loadModel('Cart');
		$this->loadModel('Event');
		$this->loadModel('Eventdetail');
		$this->loadModel('Cartquestiondetail');
		$this->loadModel('Users');
		$this->loadModel('Ticket');
		$this->loadModel('Templates');
		$this->loadModel('Cartaddons');
		$this->loadModel('Orders');
		$this->loadModel('Package');
		$this->loadModel('Currency');
		$this->loadModel('Packagedetails');

		$packageDetails = json_decode($reqData['packageDetails'], true);
		$user_id = $reqData['userId'];
		$event_id = $reqData['eventId'];
		$getevent = $this->Event->get($event_id);
		// Check package availability and sold-out status
		$soldOutPackages = [];
		foreach ($packageDetails as $package) {
			$packDetails = $this->Package->find()
				->contain('Packagedetails')
				->matching('Packagedetails', function ($q) {
					return $q->where(['Packagedetails.addon_id IS NULL']);
				})
				->select(['PackagedetailsCount' => 'SUM(Packagedetails.qty)', 'Package.id', 'Package.name', 'Package.package_limit'])
				->where(['Package.id' => $package['packageId']])
				->first();

			$totalSale = $this->Ticket->find()
				->where(['package_id' => $package['packageId']])
				->count() / $packDetails['PackagedetailsCount'];

			$requestedPackages = $package['noofPackage'];
			$availablePackages = $packDetails['package_limit'] - $totalSale;

			if ($availablePackages < $requestedPackages) {
				$response["success"] = false;
				$response["status"] = 'Insufficient quantity available for package "' . $packDetails['name'] . '". Only ' . $availablePackages . ' package(s) are available.';
				echo json_encode($response);
				return;
			}

			if ($packDetails['PackagedetailsCount'] == 0 || $availablePackages <= 0) {
				$soldOutPackages[] = $packDetails['name'];
			}
		}

		if (!empty($soldOutPackages)) {
			$packageNamesString = implode(', ', $soldOutPackages);
			$response["success"] = false;
			$response["status"] = 'The following packages are currently sold out and cannot be added to the cart: ' . $packageNamesString;
			echo json_encode($response);
			return;
		}


		// Clear existing cart data
		$this->Cart->deleteAll(['Cart.user_id' => $user_id, 'Cart.ticket_type !=' => 'committesale']);
		$this->Cartquestiondetail->deleteAll(['user_id' => $user_id]);
		$this->Cartaddons->deleteAll(['Cartaddons.user_id' => $user_id]);

		$packageTicketCount = $this->Ticket->find('all')
			->where(['cust_id' => $user_id, 'event_id' => $event_id])
			->group(['package_id', 'order_id'])
			->count();

		// Check user buy limit
		if ($packageTicketCount !== false && $packageTicketCount >= $getevent['ticket_limit']) {
			$response["success"] = false;
			$response["status"] = 'You have reached the Package limit for this event.';
			echo json_encode($response);
			return;
		}


		$digits = 10;
		$left = 0;
		foreach ($packageDetails as $packages) {
			$packageId = $packages['packageId'];
			$packageCount = $packages['noofPackage'];

			if (!empty($packageCount)) {
				for ($p = 0; $p < $packageCount; $p++) {
					$randomnumber = str_pad(rand(0, pow(10, $digits) - 1), $digits, '0', STR_PAD_LEFT);
					$package_data_set['user_id'] = $user_id;
					$package_data_set['event_id'] = $event_id;
					$package_data_set['package_id'] = $packageId;
					$package_data_set['no_tickets'] = '1'; //$ticketcount;
					$package_data_set['ticket_type'] = 'package';
					$package_data_set['status'] = 'Y';
					$package_data_set['serial_no'] = $randomnumber;
					$package_data_set['description'] = $reqData['package_descriptions'];
					$insertdata = $this->Cart->patchEntity($this->Cart->newEntity(), $package_data_set);
					$this->Cart->save($insertdata);
				}
			}
		}

		$response["success"] = true;
		$response["status"] = 'Package added to the cart successfully!';
		echo json_encode($response);
	}

	// Addons add to cart 
	public function addonAddToCart()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Cart');
		$this->loadModel('Cartaddons');
		$this->loadModel('Addonsbook');
		$this->loadModel('Addons');
		$this->loadModel('Cartaddons');


		$addons_id = $this->request->data['addonsId'];
		$user_id = $this->request->data['userId'];
		$event_id = $this->request->data['eventId'];

		// for addons booking check availability start
		$addon_cart_count = $this->Cartaddons->find('all')->where(['Cartaddons.user_id' => $user_id, 'Cartaddons.addon_id' => $addons_id])->count();
		$addons_booked = $this->Addonsbook->find('all')->where(['Addonsbook.addons_id' => $addons_id])->count();
		$addon_limit = $this->Addons->find('all')->where(['Addons.id' => $addons_id])->first();
		$remaining = $addon_limit['count'] - $addons_booked;
		// pr($addon_cart_count);exit;
		if ($remaining <= $addon_cart_count) {
			$response['success'] = false;
			$response['status'] = "You can add only " . $remaining . ' Addons';
			$response['message'] = "You can add only " . $remaining . ' Addons';
			echo json_encode($response);
			die;
		}
		if ($addon_limit['count'] > $addons_booked) {
		} else {
			$response['success'] = false;
			$response['status'] = "All addons Sold Out!";
			$response['message'] = "All addons Sold Out!";
			echo json_encode($response);
			die;
		}
		// for addons booking check availability start
		if ($addons_id) {
			$response["success"] = true;
			$response["status"] = 'Addons add in to cart Successfully!!!.';
			// $newcart = $this->Cart->newEntity();
			// $cartdetails['addons_id'] = $addons_id;
			// $cartdetails['user_id'] = $user_id;
			// $cartdetails['no_tickets'] = 1;
			// $cartdetails['event_id'] = $event_id;
			// $ticketcart = $this->Cart->patchEntity($newcart, $cartdetails);
			// $ticketcart = $this->Cart->save($ticketcart);

			$data['addon_id'] = $addons_id;
			$data['user_id'] = $user_id;
			$data['event_id'] = $event_id;
			$addnewaddons = $this->Cartaddons->patchEntity($this->Cartaddons->newEntity(), $data);
			$this->Cartaddons->save($addnewaddons);
		} else {
			$response["success"] = false;
			$response["status"] = 'Addons not add in to the cart please select addons';
		}
		echo json_encode($response);
	}

	// defining function fee calculate
	public function cal_fee_percentage($admin_fees, $total_amount)
	{
		$count1 = $total_amount * $admin_fees / 100;
		$count = number_format($count1, 2);
		return $count;
	}

	// show cart details 
	// public function cartDetails()
	// {
	// 	$this->autoRender = false;
	// 	$response = array();
	// 	$this->loadModel('Ticket');
	// 	$this->loadModel('Users');
	// 	$this->loadModel('Event');
	// 	$this->loadModel('Ticketdetail');
	// 	$this->loadModel('Eventdetail');
	// 	$this->loadModel('Cart');
	// 	$this->loadModel('Addons');
	// 	$this->loadModel('Cartaddons');
	// 	$this->loadModel('Currency');
	// 	$this->loadModel('Addonsbook');
	// 	$this->loadModel('Package');
	// 	$this->loadModel('Packagedetails');

	// 	$fee = $this->Users->find()->where(['role_id' => 1])->first();
	// 	$reqData = $this->request->data;

	// 	if ($reqData['userId']) {

	// 		$findCart = $this->Cart->find('all')->contain(['Eventdetail', 'Event' => 'Currency'])->where(['user_id' => $reqData['userId']])->toarray();
	// 		// pr($findCart);exit;

	// 		$cart_data_packages = $this->Cart->find()
	// 			->contain([
	// 				'Event' => ['Currency'],
	// 				'Package' => ['Packagedetails' => 'Eventdetail']
	// 			])
	// 			->where([
	// 				'Cart.user_id' => $reqData['userId'],
	// 				'Cart.package_id IS NOT NULL',
	// 				'Cart.status' => 'Y'
	// 			])
	// 			->order(['Cart.user_id' => 'ASC'])
	// 			->toArray();

	// 		// pr($cart_data_packages);exit;

	// 		// $findaddonCart = $this->Cart->find('all')->contain(['Addons'])->where(['user_id' => $reqData['userId'], 'addons_id IS NOT NULL', 'Cart.status' => 'Y'])->toarray();
	// 		// pr($findaddonCart);exit;

	// 		if ($findCart) {
	// 			$response['success'] = true;
	// 			$response['status'] = null;
	// 			$response['cartDetails'] = array();
	// 			$response['AddonsDetails'] = array();
	// 			$eventId = array();
	// 			$totalAmt = 0;
	// 			$conversion_rate_ticket_amt = 0;
	// 			$conversion_rate_addon_amt = 0;

	// 			// Tickets details 
	// 			foreach ($findCart as $key => $value) {

	// 				$conversion_rate_ticket_amt = $value['eventdetail']['price'] * $value['event']['currency']['conversion_rate'];

	// 				array_push($eventId, $value['event_id']);
	// 				if (empty($value['ticket_id'])) {
	// 					continue;
	// 				}

	// 				$output['cartItemId'] = $value['id'];
	// 				$output['eventId'] = $value['event_id'];
	// 				$output['eventName'] = $value['event']['name'];
	// 				$output['ticketId'] = $value['eventdetail']['id'];
	// 				$output['ticketName'] = $value['eventdetail']['title'];
	// 				$output['ticketPrice'] = '$' . sprintf('%.2f', $conversion_rate_ticket_amt) . ' TTD';
	// 				$output['selectdTicket'] = $value['no_tickets'];
	// 				$output['isCommittee'] = $value['eventdetail']['type'] == 'committee_sales' ? true : false;
	// 				if ($value['eventdetail']['type'] == 'committee_sales') {
	// 					$committee_name = $this->Users->find()->where(['id' => $value['commitee_user_id']])->first();
	// 					$output['requestedFrom'] = $committee_name['name'];
	// 				}
	// 				$output['isQuestion'] = false;
	// 				$output['isDirectSale'] = $value['eventdetail']['type'] == 'committee_sales' ? false : true;
	// 				$output['status'] = $value['status'];


	// 				$output['fee'] = '$' . sprintf('%.2f', $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_ticket_amt)) . ' TTD';
	// 				array_push($response['cartDetails'], $output);

	// 				if ($value['eventdetail']['type'] == 'open_sales') {
	// 					$totalAmt += $conversion_rate_ticket_amt * $value['no_tickets'];
	// 					$totalAmt +=  $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_ticket_amt);
	// 				}
	// 				if ($value['ticket_type'] == 'committesale' && $value['status'] == 'Y') {
	// 					$totalAmt += $conversion_rate_ticket_amt * $value['no_tickets'];
	// 					$totalAmt +=  $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_ticket_amt);
	// 				}
	// 			}

	// 			$find = $this->Cartaddons->find('all')->contain(['Addons', 'Event' => 'Currency'])->where(['Cartaddons.user_id' => $reqData['userId'], 'Cartaddons.status' => 'Y'])->toarray();

	// 			// Cart Addons 
	// 			foreach ($find as $key => $addoncartvalue) {

	// 				$conversion_rate_addon_amt = $addoncartvalue['addon']['price'] * $addoncartvalue['event']['currency']['conversion_rate'];

	// 				$output1['cartItemId'] = $addoncartvalue['id'];
	// 				$output1['eventId'] = $addoncartvalue['addon']['event_id'];
	// 				$output1['addonsName'] = $addoncartvalue['addon']['name'];
	// 				$output1['addonsPrice'] = '$' . sprintf('%.2f', $conversion_rate_addon_amt) . ' TTD';
	// 				$output1['isAddons'] = true;
	// 				$output1['fee'] = $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_addon_amt);
	// 				array_push($response['cartDetails'], $output1);
	// 				$totalAmt += $conversion_rate_addon_amt;
	// 				$totalAmt +=  $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_addon_amt);
	// 			}

	// 			// Addons 
	// 			$arraEventId = array_unique($eventId);
	// 			// if ($findCart[0]['event_id']) {

	// 			foreach ($arraEventId as $key => $addondataget) {
	// 				$addons = $this->Addons->find('all')->contain(['Event' => 'Currency'])->where(['event_id' => $addondataget, 'hidden' => 'N'])->toarray();
	// 				if ($addons) {
	// 					foreach ($addons as $key => $addonsvalue) {
	// 						$checkbookaddon = $this->Addonsbook->find('all')->where(['Addonsbook.addons_id' => $addonsvalue['id']])->count();

	// 						$conversion_rate_addon_amt = $addonsvalue['price'] * $addonsvalue['event']['currency']['conversion_rate'];

	// 						if ($checkbookaddon >= $addonsvalue['count']) {
	// 							continue;
	// 						}
	// 						$output2['id'] = $addonsvalue['id'];
	// 						$output2['name'] = $addonsvalue['name'];
	// 						$output2['price'] = '$' . sprintf('%.2f', $conversion_rate_addon_amt) . ' TTD';
	// 						$output2['description'] = $addonsvalue['description'];
	// 						$output2['count'] = $addonsvalue['count'];
	// 						array_push($response['AddonsDetails'], $output2);
	// 					}
	// 				}
	// 			}
	// 			// }

	// 			$response['currencySymbol'] = '$';
	// 			$response['currency'] = 'TTD';
	// 			$response['TotalAmount'] =  '$' . sprintf('%.2f', $totalAmt) . ' TTD';
	// 		} else {
	// 			$response['success'] = false;
	// 			$response['status'] = 'There are no tickets in your Cart';
	// 		}
	// 	} else {
	// 		$response['success'] = false;
	// 		$response['status'] = 'Sorry something went wrong please try again !';
	// 	}
	// 	echo json_encode($response);
	// }


	public function cartDetails()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Ticket');
		$this->loadModel('Users');
		$this->loadModel('Event');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Eventdetail');
		$this->loadModel('Cart');
		$this->loadModel('Addons');
		$this->loadModel('Cartaddons');
		$this->loadModel('Currency');
		$this->loadModel('Addonsbook');
		$this->loadModel('Package');
		$this->loadModel('Packagedetails');

		$fee = $this->Users->find()->where(['role_id' => 1])->first();
		$reqData = $this->request->data;

		if ($reqData['userId']) {

			$findCart = $this->Cart->find('all')->contain(['Eventdetail', 'Event' => 'Currency'])->where(['user_id' => $reqData['userId'], 'Cart.package_id IS NULL'])->toarray();

			$cart_data_packages = $this->Cart->find()
				->contain([
					'Event' => ['Currency'],
					'Package' => ['Packagedetails' => 'Eventdetail']
				])
				->where([
					'Cart.user_id' => $reqData['userId'],
					'Cart.package_id IS NOT NULL',
					'Cart.status' => 'Y'
				])
				->order(['Cart.user_id' => 'ASC'])
				->toArray();

			// pr($cart_data_packages);exit;

			if ($findCart || $cart_data_packages) {
				$response['success'] = true;
				$response['status'] = null;
				$response['cartDetails'] = [];
				$response['AddonsDetails'] = [];
				$response['packageDetails'] = [];
				$eventId = [];
				$totalAmt = 0;
				$conversion_rate_ticket_amt = 0;
				$conversion_rate_addon_amt = 0;

				// Tickets details 

				foreach ($findCart as $key => $value) {

					$conversion_rate_ticket_amt = $value['eventdetail']['price'] * $value['event']['currency']['conversion_rate'];


					array_push($eventId, $value['event_id']);
					if (empty($value['ticket_id'])) {
						continue;
					}

					$output['cartItemId'] = $value['id'];
					$output['eventId'] = $value['event_id'];
					$output['eventName'] = $value['event']['name'];
					$output['ticketId'] = $value['eventdetail']['id'];
					$output['ticketName'] = $value['eventdetail']['title'];
					$output['ticketPrice'] = '$' . sprintf('%.2f', $conversion_rate_ticket_amt) . ' TTD';
					$output['selectdTicket'] = $value['no_tickets'];
					$output['isCommittee'] = $value['eventdetail']['type'] == 'committee_sales' ? true : false;
					if ($value['eventdetail']['type'] == 'committee_sales') {
						$committee_name = $this->Users->find()->where(['id' => $value['commitee_user_id']])->first();
						$output['requestedFrom'] = $committee_name['name'];
					}
					$output['isQuestion'] = false;
					$output['isDirectSale'] = $value['eventdetail']['type'] == 'committee_sales' ? false : true;
					$output['status'] = $value['status'];


					$output['fee'] = '$' . sprintf('%.2f', $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_ticket_amt)) . ' TTD';
					array_push($response['cartDetails'], $output);

					if ($value['eventdetail']['type'] == 'open_sales') {
						$totalAmt += $conversion_rate_ticket_amt * $value['no_tickets'];
						$totalAmt +=  $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_ticket_amt);
					}
					if ($value['ticket_type'] == 'committesale' && $value['status'] == 'Y') {
						$totalAmt += $conversion_rate_ticket_amt * $value['no_tickets'];
						$totalAmt +=  $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_ticket_amt);
					}
				}

				// Package details 
				if (!empty($cart_data_packages)) {

					$addonDetails = $this->Packagedetails->find('all')
						->where([
							'Packagedetails.addon_id IS NOT' => null,
							'Packagedetails.package_id IN' => array_column($cart_data_packages, 'package_id')
						])
						->contain(['Addons'])
						->toArray();

					$packageDetails = [];

					foreach ($cart_data_packages as $keyId => $package_dts) {

						$packOutput = [
							'cartItemId' => $package_dts['id'],
							'packageId' => $package_dts['package']['id'],
							'eventId' => $package_dts['event_id'],
							'eventName' => $package_dts['event']['name'],
							'packageName' => $package_dts['package']['name'],
							'packagePrice' => $package_dts['package']['grandtotal'] * $package_dts['event']['currency']['conversion_rate'],
							'addonTypes' => [],
							'ticketTypes' => []
						];


						$totalAmt += $package_dts['package']['grandtotal'] * $package_dts['event']['currency']['conversion_rate'];
						$totalAmt +=  $this->cal_fee_percentage($fee['feeassignment'], $package_dts['package']['grandtotal'] * $package_dts['event']['currency']['conversion_rate']);

						$packOutput['fee'] = '$' . sprintf('%.2f', $this->cal_fee_percentage($fee['feeassignment'], $package_dts['package']['grandtotal'] * $package_dts['event']['currency']['conversion_rate'])) . ' TTD';

						// Get addon details
						$addonTypes = array_filter($addonDetails, function ($addon_det) use ($package_dts) {
							return $addon_det['package_id'] == $package_dts['package_id'];
						});

						if (!empty($addonTypes)) {
							$packOutput['addonTypes'] = array_map(function ($addon_det) {
								return $addon_det['qty'] . ' ' . $addon_det['addon']['name'] . '(' . $addon_det['addon']['description'] . ')';
							}, $addonTypes);
						}

						// Get ticket types
						$ticketTypes = array_map(function ($pack_detail) {
							return $pack_detail['eventdetail']['title'] . '(' . $pack_detail['qty'] . ' Ticket)';
						}, $package_dts['package']['packagedetails']);

						$packOutput['ticketTypes'] = $ticketTypes;

						$response['packageDetails'][] = $packOutput;
					}
				}


				// foreach ($cart_data_packages as $keyId => $package_dts) {

				// 	$packOutput['cartItemId'] = $package_dts['id'];
				// 	$packOutput['packageId'] = $package_dts['package']['id'];
				// 	$packOutput['eventId'] = $package_dts['event_id'];
				// 	$packOutput['eventName'] = $package_dts['event']['name'];
				// 	$packOutput['packageName'] = $package_dts['package']['name'];

				// 	$addonDetail = $this->Packagedetails->find('all')->where([
				// 		'Packagedetails.addon_id IS NOT' => null,
				// 		'Packagedetails.package_id' => $package_dts['package_id']
				// 	])->contain(['Addons'])->toArray();

				// 	if (!empty($addonDetail)) {
				// 		$addonTypes = [];
				// 		foreach ($addonDetail as $addon_det) {
				// 			$addonTypes[] = $addon_det['qty'] . ' ' . $addon_det['addon']['name'] . '(' . $addon_det['addon']['description'] . ')';
				// 		}
				// 		$packOutput['addonTypes'] = $addonTypes;
				// 	}

				// 	$ticketTypes = [];
				// 	foreach ($package_dts['package']['packagedetails'] as $pack_detail) {
				// 		$ticketTypes[] = $pack_detail['eventdetail']['title'] . '(' . $pack_detail['qty'] . ' Ticket)';
				// 	}

				// 	$packOutput['ticketTypes'] = $ticketTypes;


				// }
				// array_push($response['packageDetails'], $packOutput);

				$find = $this->Cartaddons->find('all')->contain(['Addons', 'Event' => 'Currency'])->where(['Cartaddons.user_id' => $reqData['userId'], 'Cartaddons.status' => 'Y'])->toarray();

				// Cart Addons 
				foreach ($find as $key => $addoncartvalue) {

					$conversion_rate_addon_amt = $addoncartvalue['addon']['price'] * $addoncartvalue['event']['currency']['conversion_rate'];

					$output1['cartItemId'] = $addoncartvalue['id'];
					$output1['eventId'] = $addoncartvalue['addon']['event_id'];
					$output1['addonsName'] = $addoncartvalue['addon']['name'];
					$output1['addonsPrice'] = '$' . sprintf('%.2f', $conversion_rate_addon_amt) . ' TTD';
					$output1['isAddons'] = true;
					$output1['fee'] = $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_addon_amt);
					array_push($response['cartDetails'], $output1);
					$totalAmt += $conversion_rate_addon_amt;
					$totalAmt +=  $this->cal_fee_percentage($fee['feeassignment'], $conversion_rate_addon_amt);
				}

				// Addons 
				$arraEventId = array_unique($eventId);
				foreach ($arraEventId as $key => $addondataget) {
					$addons = $this->Addons->find('all')->contain(['Event' => 'Currency'])->where(['event_id' => $addondataget, 'hidden' => 'N'])->toarray();
					if ($addons) {
						foreach ($addons as $key => $addonsvalue) {
							$checkbookaddon = $this->Addonsbook->find('all')->where(['Addonsbook.addons_id' => $addonsvalue['id']])->count();

							$conversion_rate_addon_amt = $addonsvalue['price'] * $addonsvalue['event']['currency']['conversion_rate'];

							// pr($addonsvalue);exit;
							if ($checkbookaddon >= $addonsvalue['count']) {
								continue;
							}
							$output2['id'] = $addonsvalue['id'];
							$output2['name'] = $addonsvalue['name'];
							$output2['price'] = '$' . sprintf('%.2f', $conversion_rate_addon_amt) . ' TTD';
							$output2['description'] = $addonsvalue['description'];
							$output2['count'] = $addonsvalue['count'];
							array_push($response['AddonsDetails'], $output2);
						}
					}
				}

				$response['currencySymbol'] = '$';
				$response['currency'] = 'TTD';
				$response['TotalAmount'] =  '$' . sprintf('%.2f', $totalAmt) . ' TTD';
			} else {
				$response['success'] = false;
				$response['status'] = 'There are no tickets in your Cart';
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'Sorry something went wrong please try again !';
		}
		echo json_encode($response);
	}

	// Cart count 
	public function cartCount()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Users');
		$this->loadModel('Cart');

		$reqData = $this->request->data;

		$findcount = $this->Cart->find('all')
			->where([
				'Cart.user_id' => $reqData['userId'],
				'Cart.status' => 'Y'
				// 'Cart.ticket_type IN' => ['opensale', 'package']
			])
			->count();


		$cart_data_comitee = $this->Cart->find('all')->where(['Cart.commitee_user_id' => $reqData['userId'], 'Cart.ticket_type' => 'committesale', 'Cart.status' => 'N'])->order(['Cart.user_id' => 'ASC'])->count();

		if (!empty($reqData['userId'] && $findcount)) {
			$response['success'] = true;
			$response['status'] = null;
			$response['cartCount'] = $findcount;
			$response['committeePending'] = $cart_data_comitee;
		} else {
			$response['success'] = false;
			$response['cartCount'] = $findcount;
			$response['committeePending'] = $cart_data_comitee;
			$response['status'] = 'null';
		}
		echo json_encode($response);
	}

	// Final paymen and buy tickets
	// public function finalcheckout()
	// {
	// 	$this->autoRender = false;
	// 	$response = array();
	// 	$this->loadModel('Orders');
	// 	$this->loadModel('Ticket'); // tblticket_book
	// 	$this->loadModel('Cartaddons');
	// 	$this->loadModel('Addons');
	// 	$this->loadModel('Addonsbook');
	// 	$this->loadModel('Cart');
	// 	$this->loadModel('Eventdetail');
	// 	$this->loadModel('Event');
	// 	$this->loadModel('Users');
	// 	$this->loadModel('Payment');
	// 	$this->loadModel('Ticketdetail');

	// 	$date = date("Y-m-d H:i:s");

	// 	if (!empty($this->request->data['holderName']) && !empty($this->request->data['cardNumber']) && !empty($this->request->data['monthYear']) && !empty($this->request->data['totalAmount']) && !empty($this->request->data['userId'])) {

	// 		$profess = $this->Users->find('all')->where(['id' => $this->request->data['userId']])->first();

	// 		// For outof stock start
	// 		$cart_data = $this->Cart->find('all')->contain(['Event', 'Eventdetail'])->where(['Cart.user_id' => $profess['id'], 'Cart.ticket_type' => 'opensale'])->order(['Cart.id' => 'ASC'])->toarray();

	// 		if (!empty($cart_data)) {

	// 			// for addons booking check availability start
	// 			$cart_addons = $this->Cartaddons->find('all')->where(['Cartaddons.user_id' => $this->request->data['userId']])->order(['Cartaddons.id' => 'ASC'])->toarray();

	// 			foreach ($cart_addons as $findaddon_value) {

	// 				$addons_booked = $this->Addonsbook->find('all')->where(['Addonsbook.addons_id' => $findaddon_value['addon_id']])->count();

	// 				$addon_limit = $this->Addons->find('all')->where(['Addons.id' => $findaddon_value['addon_id']])->first();

	// 				$addon_cart_count = $this->Cartaddons->find('all')->where(['Cartaddons.user_id' => $this->request->data['userId'], 'Cartaddons.addon_id' => $findaddon_value['addon_id']])->count();

	// 				$total_addon_cart_count = $addon_cart_count + $addons_booked;

	// 				if ($total_addon_cart_count <= $addon_limit['count']) {
	// 				} else {
	// 					$remaiingaddons  = $addon_limit['count'] - $addons_booked;
	// 					if ($remaiingaddons <= 0) {
	// 						$response['success'] = false;
	// 						$response['status'] = "Sorry !! All addons Sold Out!";
	// 						echo json_encode($response);
	// 						die;
	// 					} else {
	// 						$response['success'] = false;
	// 						$response['status'] = "You can add only " . $remaiingaddons . " addons.";
	// 						echo json_encode($response);
	// 						die;
	// 					}
	// 				}
	// 			}
	// 			// for addons booking check availability end

	// 			foreach ($cart_data as $cartdetails) {
	// 				$ticket_count = $cartdetails['eventdetail']['count'];
	// 				$getevent = $this->Event->get($cartdetails['event_id']);

	// 				if ($cartdetails['eventdetail']['sold_out'] == 'Y') {
	// 					$response['success'] = false;
	// 					$response['status'] = $cartdetails['eventdetail']['title'] . " ticket is Sold Out";
	// 					echo json_encode($response);
	// 					die;
	// 				}

	// 				$sale_end = date('Y-m-d H:i:s', strtotime($getevent['sale_end']));
	// 				if (strtotime($sale_end) >= strtotime($date)) {
	// 				} else {
	// 					$response['success'] = false;
	// 					$response['message'] = "Ticket sales for " . $getevent['name'] . "event are currently closed.";
	// 					echo json_encode($response);
	// 					die;
	// 				}

	// 				$check = $this->Cart->find('all')->where(['user_id' => $this->request->data['userId'], 'event_id' => $cartdetails['event_id']])->count();
	// 				$totalticket_purchased = $this->Ticket->find('all')->select(['sum' => 'SUM(Ticket.ticket_buy)'])->where(['Ticket.event_id' => $cartdetails['event_id'], 'Ticket.cust_id' => $this->request->data['userId']])->first();

	// 				$total_ticket_purchase = $totalticket_purchased['sum'] + $check;
	// 				// pr($total_ticket_purchase);exit;

	// 				if ($total_ticket_purchase <= $cartdetails['event']['ticket_limit']) {
	// 				} elseif ($total_ticket_purchase >= $cartdetails['event']['ticket_limit']) {
	// 					$response['success'] = false;
	// 					$response['status'] = "You have requested more tickets than your ticket limit for this event.";
	// 					echo json_encode($response);
	// 					die;
	// 				} else {
	// 					$response['success'] = false;
	// 					$response['status'] = "You have completed your limit for " . $cartdetails['eventdetail']['title'] . " ticket";
	// 					echo json_encode($response);
	// 					die;
	// 				}

	// 				//find booking indivisual tickets left
	// 				$total_purchased_individual = $this->Ticket->find('all')->select(['sum' => 'SUM(Ticket.ticket_buy)'])->where(['Ticket.event_id' => $cartdetails['event_id'], 'Ticket.event_ticket_id' => $cartdetails['ticket_id']])->first();
	// 				$sell_tickets = $total_purchased_individual['sum'];

	// 				if ($sell_tickets < $ticket_count) {
	// 				} else {
	// 					$response['success'] = false;
	// 					$response['status'] = $cartdetails['eventdetail']['title'] . " ticket has Sold Out";
	// 					echo json_encode($response);
	// 					die;
	// 				}
	// 			}

	// 			// >>>>>>>>>>>>>>>>>>>>>>>>>>Payment Getway Start >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	// 			$header = [
	// 				'Accept:application/json',
	// 				'PowerTranz-PowerTranzId:' . POWERTRANZID,
	// 				'PowerTranz-PowerTranzPassword:' . POWERTRANZPASSWORD,
	// 				'Content-Type:application/json'
	// 			];

	// 			$card_number = $this->request->data['cardNumber'];
	// 			$cardcvv = $this->request->data['cvv'];
	// 			$card_monthyear = $this->request->data['monthYear'];
	// 			$card_holder_name = $this->request->data['holderName'];
	// 			$cart_total_checkout = $this->request->data['totalAmount'];


	// 			$guid  =  $this->createGUID();
	// 			$user_id = $this->request->data['userId'];
	// 			$user_check = $this->Users->get($user_id);

	// 			$request_data = [
	// 				"TransactionIdentifier" => $guid,
	// 				"TotalAmount" => $cart_total_checkout,
	// 				"CurrencyCode" => CURRENCYCODE,
	// 				"ThreeDSecure" => true,
	// 				"Source" => [
	// 					"CardPan" => $card_number,
	// 					"CardCvv" => $cardcvv, //$cardcvv,
	// 					"CardExpiration" => $card_monthyear,
	// 					"CardholderName" => $card_holder_name
	// 				],
	// 				"OrderIdentifier" => $guid,
	// 				"BillingAddress" => [
	// 					"FirstName" => $user_check['name'],
	// 					"LastName" => $user_check['lname'],
	// 					"Line1" => '',
	// 					"Line2" => '',
	// 					"City" => '',
	// 					"State" => '',
	// 					"PostalCode" => '',
	// 					"CountryCode" => '',
	// 					"EmailAddress" => $user_check['email'],
	// 					"PhoneNumber" => $user_check['mobile'],
	// 				],
	// 				"AddressMatch" => false,
	// 				"ExtendedData" => [
	// 					"ThreeDSecure" => [
	// 						"ChallengeWindowSize" => 4,
	// 						"ChallengeIndicator" => "02"
	// 					],
	// 					"MerchantResponseUrl" => SITE_URL . "mobile/paymentProcessing"
	// 				]
	// 			];

	// 			$request_json_data =   json_encode($request_data);
	// 			$url = PAYMENTURL . "spi/auth";
	// 			$curl = curl_init();
	// 			curl_setopt_array($curl, array(
	// 				CURLOPT_URL => $url,
	// 				CURLOPT_RETURNTRANSFER => true,
	// 				CURLOPT_FOLLOWLOCATION => 0,
	// 				CURLOPT_ENCODING => "",
	// 				CURLOPT_SSL_VERIFYPEER => false,
	// 				CURLOPT_MAXREDIRS => 10,
	// 				CURLOPT_TIMEOUT => 40,
	// 				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	// 				CURLOPT_CUSTOMREQUEST => "POST",
	// 				CURLOPT_POSTFIELDS => $request_json_data,
	// 				CURLOPT_HTTPHEADER => $header
	// 			));
	// 			$response = curl_exec($curl);
	// 			$err = curl_error($curl);
	// 			curl_close($curl);

	// 			$fn['user_id'] = $user_id;
	// 			$fn['TransactionIdentifier'] = $guid;
	// 			$fn['amount'] = $cart_total_checkout;
	// 			$fn['status'] = 'N';
	// 			$payment = $this->Payment->patchEntity($this->Payment->newEntity(), $fn);
	// 			$this->Payment->save($payment);


	// 			$file_path = "/var/www/html/eboxtickets.com/webroot/logs/powertranz.txt";
	// 			file_put_contents($file_path, $response, FILE_APPEND | LOCK_EX);
	// 			// >>>>>>>>>>>>>>>>>>>>>>>>>>Payment Getway End >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>


	// 			echo json_encode($response);
	// 			die;
	// 		}
	// 	} else {
	// 		$response['success'] = false;
	// 		$response['status'] = 'Sorry invalid transaction';
	// 	}
	// }

	// ^^^^^^^^^^^^^^^^^^^^^^
	// finalcheckout Checkout step -1
	public function finalcheckout()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Orders');
		$this->loadModel('Ticket'); // tblticket_book
		$this->loadModel('Cartaddons');
		$this->loadModel('Addons');
		$this->loadModel('Addonsbook');
		$this->loadModel('Cart');
		$this->loadModel('Eventdetail');
		$this->loadModel('Event');
		$this->loadModel('Users');
		$this->loadModel('Payment');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Currency');
		$this->loadModel('Package');
		$this->loadModel('Packagedetails');

		$date = date("Y-m-d H:i:s");
		$card_holder_name = $this->request->data['holderName'];
		$card_number = $this->request->data['cardNumber'];
		$card_monthyear = $this->request->data['monthYear'];
		$cart_total_checkout = $this->request->data['totalAmount'];
		$cardcvv = $this->request->data['cvv'];
		$userId = $this->request->data['userId'];

		// pr($this->request->data);exit;

		if (!empty($card_holder_name) && !empty($card_number) && !empty($card_monthyear) && !empty($cart_total_checkout) && !empty($userId)) {

			$userInfo = $this->Users->find('all')->where(['id' => $userId])->first();

			$cart_data = $this->Cart->find('all')->contain(['Event' => ['Currency'], 'Eventdetail'])->where(['Cart.user_id' => $userInfo['id'], 'Cart.status' => 'Y', 'Cart.package_id IS NULL'])->order(['Cart.user_id' => 'ASC'])->toarray();

			$cart_data_packages = $this->Cart->find()
				->contain([
					'Event' => ['Currency'],
					'Package' => ['Packagedetails' => 'Eventdetail']
				])
				->where([
					'Cart.user_id' => $userInfo['id'],
					'Cart.package_id IS NOT NULL',
					'Cart.status' => 'Y'
				])
				->order(['Cart.user_id' => 'ASC'])
				->toArray();


			if (!empty($cart_data[0]['id']) && !empty($cart_data_packages[0]['id'])) {
				$response['success'] = false;
				$response['message'] = "You cannot buy both a package and a ticket at the same time. Please delete any one and proceed.";
				echo json_encode($response);
				die;
			}

			if (!empty($cart_data) || !empty($cart_data_packages)) {
				$errorMessages = [];

				//^^^^^^^^^^^^^^^^^^^^^^^^ Addons availavility start ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
				$cart_addons = $this->Cartaddons->find('all')
					->where(['Cartaddons.user_id' => $userInfo['id']])
					->order(['Cartaddons.id' => 'ASC'])
					->toArray();


				if (!empty($cart_addons)) {

					$addonIds = array_column($cart_addons, 'addon_id');

					$addons_booked = $this->Addonsbook->find('all')
						->where(['Addonsbook.addons_id IN' => $addonIds])
						->count();

					$addon_limits = $this->Addons->find('list', [
						'conditions' => ['Addons.id IN' => $addonIds],
						'keyField' => 'id',
						'valueField' => 'count'
					])->toArray();

					foreach ($cart_addons as $findaddon_value) {

						$addon_id = $findaddon_value['addon_id'];
						$addon_cart_count = $this->Cartaddons->find('all')->where(['Cartaddons.user_id' => $userInfo['id'], 'Cartaddons.addon_id' => $addon_id])->count();

						$total_addon_cart_count = $addon_cart_count + $addons_booked;

						if ($total_addon_cart_count <= $addon_limits[$addon_id]) {
							continue;
						} else {
							$remaining_addons = $addon_limits[$addon_id] - $total_addon_cart_count;
							if ($remaining_addons <= 0) {
								$response['success'] = false;
								$response['status'] = "Sorry !! All addons Sold Out!";
								echo json_encode($response);
								die;
							} else {
								$response['success'] = false;
								$response['status'] = "You can add only " . $remaining_addons . " addons.";
								echo json_encode($response);
								die;
							}
						}
					}
				}
				// End 


				//^^^^^^^^^^^^^^^^^^^^^^^^ Package availavility start ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
				if ($cart_data_packages) {
					$cart_total_checkout = 1;
				}
				foreach ($cart_data_packages as $packId => $cart_packages_det) {
					// pr($cart_packages_det);

					$ticketLimitReached = false;
					$soldOutPackages = [];

					$packDetails = $this->Package->find()
						->contain('Packagedetails')
						->matching('Packagedetails', function ($q) {
							return $q->where(['Packagedetails.addon_id IS NULL']);
						})
						->select(['PackagedetailsCount' => 'SUM(Packagedetails.qty)', 'Package.name', 'Package.package_limit'])
						->where(['Package.id' => $cart_packages_det['package_id']])
						->first();

					$totalSale = $this->Ticket->find()
						->where(['package_id' => $cart_packages_det['package_id']])
						->count() / $packDetails['PackagedetailsCount'];

					if ($packDetails['PackagedetailsCount'] == 0 || $packDetails['package_limit'] - $totalSale <= 0) {
						$soldOutPackages[] = $packDetails['name'];
					}

					$cust_pack_buy = $this->Ticket->find('all')
						->where([
							'cust_id' => $user_id,
							'event_id' => $cart_packages_det['event_id'],
						])
						->group(['order_id', 'package_id'])
						->count();

					if ($cust_pack_buy >= $cart_packages_det['event']['ticket_limit']) {
						$ticketLimitReached = true;
						continue;
					}

					// Check if ticket limit reached
					if ($ticketLimitReached) {
						$errorMessage[] = "You have reached the Package limit for this event. Please remove them from your cart or choose other packages.";
					}

					if (!empty($errorMessage)) {
						// implode("<br>", $errorMessage);
						$response['success'] = false;
						$response['status'] = implode(" ", $errorMessages);
						echo json_encode($response);
						die;
					}

					if (!empty($soldOutPackages)) {
						$packageNamesString = implode(', ', $soldOutPackages);
						$response['success'] = false;
						$response['status'] = 'The following packages are currently sold out and cannot be added to the cart: ' . $packageNamesString;
						echo json_encode($response);
						die;
					}
				}
				// End

				//^^^^^^^^^^^^^^^^^^^^^^^^ Ticket availavility start ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
				foreach ($cart_data as $cartItem) {
					$eventDetail = $cartItem['eventdetail'];

					if ($eventDetail['sold_out'] == 'Y') {
						$errorMessages[] = $eventDetail['title'] . " ticket is Sold Out";
					}

					$event = $this->Event->get($cartItem['event_id']);
					$saleEnd = $event['sale_end'];

					if (strtotime($saleEnd) < strtotime($date)) {
						$errorMessages[] = "Ticket sales for " . $event['name'] . " event are currently closed.";
					}


					$checkCount = $this->Cart->find()
						->where(['user_id' => $userId, 'event_id' => $cartItem['event_id']])
						->count();

					$ticketPurchasedSum = $this->Ticket->find()
						->select(['sum' => 'SUM(Ticket.ticket_buy)'])
						->where([
							'Ticket.event_id' => $cartItem['event_id'],
							'Ticket.cust_id' => $userId,
							'Ticket.package_id IS' => null
						])
						->first();

					$totalTicketPurchase = $ticketPurchasedSum['sum'] + $checkCount;

					$remainingTickets = $cartItem['event']['ticket_limit'] - $ticketPurchasedSum['sum'];

					if ($totalTicketPurchase > $cartItem['event']['ticket_limit']) {
						$errorMessages[] = "You have requested more tickets than your ticket limit for this event. Only {$remainingTickets} {$eventDetail['title']} tickets left.";
					} elseif ($totalTicketPurchase > $cartItem['event']['ticket_limit']) {
						$errorMessages[] = "You have completed your limit for " . $eventDetail['title'] . " ticket.";
					}


					$totalPurchasedIndividual = $this->Ticket->find()
						->select(['sum' => 'SUM(Ticket.ticket_buy)'])
						->where([
							'Ticket.event_id' => $cartItem['event_id'],
							'Ticket.event_ticket_id' => $cartItem['ticket_id'],
							'Ticket.package_id IS' => null
						])
						->first();

					$sellTickets = $totalPurchasedIndividual['sum'];

					if ($sellTickets > $eventDetail['count']) {
						$errorMessages[] = $eventDetail['title'] . " ticket has Sold Out";
					}
				}
				// End 


				// If there are any error messages
				if (!empty($errorMessages)) {
					$response['success'] = false;
					$response['status'] = implode(" ", $errorMessages);
					echo json_encode($response);
					die;
				}


				// <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<Payment getway request set >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
				// Set the payment gateway request headers
				$headers = [
					'Accept: application/json',
					'PowerTranz-PowerTranzId: ' . POWERTRANZID,
					'PowerTranz-PowerTranzPassword: ' . POWERTRANZPASSWORD,
					'Content-Type: application/json'
				];

				// Generate a unique transaction identifier
				$guid = $this->createGUID();

				// Prepare the request data
				$requestData = [
					"TransactionIdentifier" => $guid,
					"TotalAmount" => $cart_total_checkout,
					"CurrencyCode" => CURRENCYCODE,
					"ThreeDSecure" => true,
					"Source" => [
						"CardPan" => $card_number,
						"CardCvv" => $cardcvv,
						"CardExpiration" => $card_monthyear,
						"CardholderName" => $card_holder_name
					],
					"OrderIdentifier" => $guid,
					"BillingAddress" => [
						"FirstName" => $userInfo['name'],
						"LastName" => $userInfo['lname'],
						"Line1" => '',
						"Line2" => '',
						"City" => '',
						"State" => '',
						"PostalCode" => '',
						"CountryCode" => '',
						"EmailAddress" => $userInfo['email'],
						"PhoneNumber" => $userInfo['mobile'],
					],
					"AddressMatch" => false,
					"ExtendedData" => [
						"ThreeDSecure" => [
							"ChallengeWindowSize" => 4,
							"ChallengeIndicator" => "02"
						],
						"MerchantResponseUrl" => SITE_URL . "mobile/paymentProcessing"
					]
				];
				// pr($requestData);exit;

				// Convert request data to JSON
				$requestJsonData = json_encode($requestData);
				$url = PAYMENTURL . "spi/auth";

				// Initialize curl
				$curl = curl_init();
				curl_setopt_array($curl, array(
					CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => 0,
					CURLOPT_ENCODING => "",
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 40,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => $requestJsonData,
					CURLOPT_HTTPHEADER => $headers
				));

				// Execute the curl request
				$response = curl_exec($curl);
				$err = curl_error($curl);

				// Handle or log the errors
				if (isset($responseData['Errors'])) {
					$errorMessage = '';
					foreach ($responseData['Errors'] as $error) {
						$errorMessage .= $error['Message'] . ' '; // Concatenate error messages
					}
					$response['success'] = false;
					$response['status'] = 'Sorry, the transaction could not be processed: ' . trim($errorMessage);
				}

				// Close the curl request
				curl_close($curl);

				// Save payment details
				$fn['user_id'] = $userId;
				$fn['TransactionIdentifier'] = $guid;
				$fn['amount'] = $cart_total_checkout;
				$fn['status'] = 'N';
				$payment = $this->Payment->patchEntity($this->Payment->newEntity(), $fn);
				$this->Payment->save($payment);

				// Log the response to a file
				$file_path = "/var/www/html/eboxtickets.com/webroot/logs/powertranz.txt";
				$name = "\n\n=============finalcheckout(" . date('d-m-Y h:i:s A') . ")=====================";
				$log = $name . "\n\n";
				$log .= 'Request Data (' . json_encode($this->request->data) . ") \n\n";
				$log .= json_encode($response);
				file_put_contents($file_path, $log, FILE_APPEND | LOCK_EX);

				// Send the response
				echo $response;
				die;
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'Sorry invalid transaction';
		}
	}
	// paymentProcessing Checkout step -2
	public function paymentProcessing()
	{
		$this->loadModel('Orders');
		$this->loadModel('Ticket');
		$this->loadModel('Cartaddons');
		$this->loadModel('Addons');
		$this->loadModel('Addonsbook');
		$this->loadModel('Cart');
		$this->loadModel('Eventdetail');
		$this->loadModel('Event');
		$this->loadModel('Users');
		$this->loadModel('Payment');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Questionbook');
		$this->loadModel('Cartquestiondetail');
		$this->loadModel('Currency');
		$this->loadModel('Templates');

		$response_data = json_decode($this->request->data['Response']);
		$transaction_identifier = $response_data->TransactionIdentifier;
		// pr($transaction_identifier);
		// pr($this->request->data);exit;

		$file_path = "/var/www/html/eboxtickets.com/webroot/logs/powertranz.txt";
		$name = "\n\n==========paymentProcessing(" . date('d-m-Y h:i:s A') . ")=====================";
		$log = $name . "\n\n";
		$log .= json_encode($response_data);
		file_put_contents($file_path, $log, FILE_APPEND | LOCK_EX);
		$transaction_identifier_check = $this->Payment->find()->where(['TransactionIdentifier' => $transaction_identifier])->first();

		if ($transaction_identifier_check) {
			$date = date("Y-m-d H:i:s");
			$total_amount = $response_data->TotalAmount;
			$currency_code = $response_data->CurrencyCode;
			$cardbrand = $response_data->CardBrand;
			$iso_response_code = $response_data->IsoResponseCode;
			$repsonse_message = $response_data->ResponseMessage;
			$order_identifier  = $response_data->OrderIdentifier;
			$spi_token  = $response_data->SpiToken;
			$header = [
				'Content-Type:application/json'
			];
			$savedata_payment['total_amount'] = $total_amount;
			$savedata_payment['currency_code'] = $currency_code;
			$savedata_payment['cardbrand'] = $cardbrand;
			$savedata_payment['iso_response_code_spiprocessing'] = $iso_response_code;
			$savedata_payment['repsonse_message_processing'] = $repsonse_message;
			$savedata_payment['order_identifier'] = $order_identifier;
			$savedata_payment['spi_token'] = $spi_token;
			$payment_patch = $this->Payment->patchEntity($transaction_identifier_check, $savedata_payment);
			$this->Payment->save($payment_patch);

			if ($iso_response_code  == "3D0") {
				$request_data = $spi_token;
				$request_json_data = json_encode($request_data);

				$url = PAYMENTURL . "spi/payment";
				$curl = curl_init();
				curl_setopt_array($curl, array(
					CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => 0,
					CURLOPT_ENCODING => "",
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 40,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_POSTFIELDS => $request_json_data,
					CURLOPT_HTTPHEADER => $header
				));
				$paymentresponse = curl_exec($curl);
				$err = curl_error($curl);
				curl_close($curl);

				$logpayment_response .= json_encode($paymentresponse) . "\n\n\n";
				file_put_contents($file_path, $logpayment_response, FILE_APPEND | LOCK_EX);

				$payment_response =  json_decode($paymentresponse);


				$file_path = "/var/www/html/eboxtickets.com/webroot/logs/powertranz.txt";
				$name = "\n\n==========paymentchecking(" . date('d-m-Y h:i:s A') . ")=====================";
				$log = $name . "\n\n";
				$log .= json_encode($payment_response);
				file_put_contents($file_path, $log, FILE_APPEND | LOCK_EX);

				$savedata_payment_again['iso_response_code_spipayment'] = $payment_response->IsoResponseCode;
				$savedata_payment_again['repsonse_message_spipayment'] = $payment_response->ResponseMessage;
				$payment_patch_again = $this->Payment->patchEntity($transaction_identifier_check, $savedata_payment_again);
				$this->Payment->save($payment_patch_again);


				if ($payment_response->IsoResponseCode == "00") {

					$header_capture = [
						'Accept:application/json',
						'PowerTranz-PowerTranzId:' . POWERTRANZID,
						'PowerTranz-PowerTranzPassword:' . POWERTRANZPASSWORD,
						'Content-Type:application/json'
					];

					$request_data = [
						"TransactionIdentifier" => $payment_response->TransactionIdentifier,
						"TotalAmount" => $payment_response->TotalAmount,
						"ExternalIdentifier" => null,
					];

					$request_json_data_capture =   json_encode($request_data);

					$url = PAYMENTURL . "capture";
					$curl = curl_init();
					curl_setopt_array($curl, array(
						CURLOPT_URL => $url,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_FOLLOWLOCATION => 0,
						CURLOPT_ENCODING => "",
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 40,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "POST",
						CURLOPT_POSTFIELDS => $request_json_data_capture,
						CURLOPT_HTTPHEADER => $header_capture
					));
					$response_capture = curl_exec($curl);
					$err = curl_error($curl);
					curl_close($curl);


					$logpayment_capture = $response_capture . "\n\n";
					$logpayment_capture .= "===========Line no 3595 Mobile Controller====================";
					file_put_contents($file_path, $logpayment_capture, FILE_APPEND | LOCK_EX);

					$checkout_response_capture = json_decode($response_capture);

					$savedata_payment_again['iso_response_code_spicheckout'] = $checkout_response_capture->IsoResponseCode;
					$savedata_payment_again['repsonse_message_spicheckout'] = $checkout_response_capture->ResponseMessage;
					$savedata_payment_again['RRN'] =  $checkout_response_capture->RRN;
					$savedata_payment_again['OrderIdentifiercheckout'] = $checkout_response_capture->OrderIdentifier;
					$savedata_payment_again['OriginalTrxnIdentifiercheckout'] = $checkout_response_capture->OriginalTrxnIdentifier;
					$savedata_payment_again['TransactionIdentifiercheckout'] = $checkout_response_capture->TransactionIdentifier;
					$savedata_payment_again['TransactionTypecheckout'] = $checkout_response_capture->TransactionType;
					$savedata_payment_again['Approvedcheckout'] = $checkout_response_capture->Approved;

					$payment_patch_again = $this->Payment->patchEntity($transaction_identifier_check, $savedata_payment_again);
					$this->Payment->save($payment_patch_again);

					if ($checkout_response_capture->IsoResponseCode == 0) {
						$response['success'] = true;
						$response['status'] = 'Ticket has been approved successfully';
						echo json_encode($response);
						die;
					} else {
						$response['success'] = false;
						$response['status'] = 'Invalid Details';
						echo json_encode($response);
						die;
					}
				} else {

					$response['success'] = false;
					$response['status'] = 'Something went wrong please try again';
					echo json_encode($response);
					die;
				}
			} else {
				// $this->Payment->deleteAll(['Payment.TransactionIdentifier' => $transaction_identifier]);
				$response['success'] = false;
				$response['status'] = 'Invalid card details';
				echo json_encode($response);
				die;
			}
		} else {
			// $this->Payment->deleteAll(['Payment.TransactionIdentifier' => $transaction_identifier]);
			$response['success'] = false;
			$response['status'] = 'Invalid Transaction';
			echo json_encode($response);
			die;
		}
	}

	// Last step processing
	public function paymentProcessingchecking()
	{
		$this->loadModel('Orders');
		$this->loadModel('Ticket'); // tblticket_book
		$this->loadModel('Cartaddons');
		$this->loadModel('Addons');
		$this->loadModel('Addonsbook');
		$this->loadModel('Cart');
		$this->loadModel('Eventdetail');
		$this->loadModel('Event');
		$this->loadModel('Users');
		$this->loadModel('Payment');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Questionbook');
		$this->loadModel('Cartquestiondetail');
		$this->loadModel('Currency');
		$this->loadModel('Templates');

		$transaction_identifier = $this->request->data['TransactionIdentifier'];
		$transaction_identifier_check = $this->Payment->find()->where(['TransactionIdentifier' => $transaction_identifier])->first();

		// pr($transaction_identifier_check);die;

		if ($transaction_identifier_check) {
			// echo $transaction_identifier_check->iso_response_code_spipayment; die;
			if ($transaction_identifier_check['iso_response_code_spiprocessing']  == "3D0" && $transaction_identifier_check->iso_response_code_spipayment != '' && $transaction_identifier_check->iso_response_code_spipayment == "00") {

				$user_id =  $transaction_identifier_check['user_id'];
				$user_check = $this->Users->get($user_id);
				$admin_fee = $this->Users->find()->where(['role_id' => 1])->first();
				$fees = $admin_fee['feeassignment'];
				$orderdata['adminfee'] = $fees;
				$orderdata['user_id'] = $user_id;
				$orderdata['total_amount'] = sprintf('%.2f', $transaction_identifier_check['total_amount']);
				$orderdata['RRN'] = $transaction_identifier_check['RRN'];
				$orderdata['IsoResponseCode'] = $transaction_identifier_check['iso_response_code_spicheckout'];
				$orderdata['OrderIdentifier'] = $transaction_identifier_check['OrderIdentifiercheckout'];
				$orderdata['OriginalTrxnIdentifier'] = $transaction_identifier_check['OriginalTrxnIdentifiercheckout'];
				$orderdata['TransactionIdentifier'] = $transaction_identifier_check['TransactionIdentifiercheckout'];
				$orderdata['TransactionType'] = $transaction_identifier_check['TransactionTypecheckout'];
				$orderdata['Approved'] = $transaction_identifier_check['Approvedcheckout'];
				$orderdata['paymenttype'] = "Online";
				// pr($orderdata);exit;
				$insertdata = $this->Orders->patchEntity($this->Orders->newEntity(), $orderdata);

				if ($saveorders = $this->Orders->save($insertdata)) {

					$cart_data = $this->Cart->find('all')->contain(['Event' => ['Currency'], 'Eventdetail'])->where(['Cart.user_id' => $user_id, 'Cart.status' => 'Y', 'Cart.package_id IS NULL'])->order(['Cart.id' => 'ASC'])->toarray();

					$cart_packages_data = $this->Cart->find()
						->contain([
							'Event' => ['Currency'],
							'Package' => ['Packagedetails' => 'Eventdetail']
						])
						->where([
							'Cart.user_id' => $user_id,
							'Cart.package_id IS NOT NULL',
							'Cart.status' => 'Y'
						])
						->order(['Cart.id' => 'ASC'])
						->toArray();

					// pr($cart_data);die;
					// pr($cart_packages_data);exit;

					// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% For Package Start %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
					if (!empty($cart_packages_data)) {
						$this->packageAssigned($user_check, $saveorders, $fees, $cart_packages_data);
					}
					// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%% For Package End %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


					// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% For Ticket Start %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
					$TotalAmount = 0;
					$TotalAmountIncludefee = 0;
					$conversion_rate = 0;
					$ordersummary = '';
					$ordersummarywtsapp = '';

					if (!empty($cart_data)) {

						foreach ($cart_data as $key => $value) {

							$currenny = $this->Currency->get($value['event']['payment_currency']);
							$conversion_rate = $value['eventdetail']['price'] * $currenny['conversion_rate'];

							$fn['user_id'] = $user_id;
							$fn['event_id'] =  $value['event_id'];
							$fn['mpesa'] = null;
							$fn['amount'] =  $value['eventdetail']['price'];
							$payment = $this->Payment->patchEntity($this->Payment->newEntity(), $fn);
							$this->Payment->save($payment);

							$ticketbook['order_id'] = $saveorders->id;
							$ticketbook['event_id'] = $value['event_id'];
							$ticketbook['event_ticket_id'] = $value['ticket_id'];
							$ticketbook['cust_id'] = $user_id;
							$ticketbook['ticket_buy'] = 1;
							$ticketbook['currency_rate'] = $currenny['conversion_rate'];
							$ticketbook['adminfee'] = $fees;
							$ticketbook['user_desc'] = 'Ticket book from Mobile App';
							$ticketbook['amount'] = $conversion_rate;
							$ticketbook['mobile'] = $user_check['mobile'];
							$ticketbook['committee_user_id'] = $value['commitee_user_id'];
							$insertticketbook = $this->Ticket->patchEntity($this->Ticket->newEntity(), $ticketbook);
							$lastinsetid = $this->Ticket->save($insertticketbook);

							$ticketdetaildata['tid'] = $lastinsetid['id'];
							$ticketdetaildata['user_id'] = $user_id;
							$ticketdetail = $this->Ticketdetail->patchEntity($this->Ticketdetail->newEntity(), $ticketdetaildata);
							$ticketdetailvvv = $this->Ticketdetail->save($ticketdetail);

							$Packff = $this->Ticketdetail->get($ticketdetailvvv['id']);
							$Packff->ticket_num = 'T' . $ticketdetailvvv['id'];
							$ticketdetail = $this->Ticketdetail->save($Packff);

							$ticketqrimages = $this->qrcodepro($user_id, $ticketdetail['ticket_num'], $value['event']['event_org_id']);
							$Pack = $this->Ticketdetail->get($ticketdetail['id']);
							$Pack->qrcode = $ticketqrimages;
							$this->Ticketdetail->save($Pack);

							$questiondetail = $this->Cartquestiondetail->find('all')->where(['Cartquestiondetail.serial_no' => $value['serial_no']])->toarray();

							foreach ($questiondetail as $keyid => $questionreply) {
								$bookquestion['order_id'] = $saveorders['id'];
								$bookquestion['ticketdetail_id'] = $ticketdetail['id'];
								$bookquestion['question_id'] = $questionreply['question_id'];
								$bookquestion['event_id'] = $questionreply['event_id'];
								$bookquestion['user_id'] = $questionreply['user_id'];
								$bookquestion['user_reply'] = $questionreply['user_reply'];
								$savequestionbook = $this->Questionbook->patchEntity($this->Questionbook->newEntity(), $bookquestion);
								$this->Questionbook->save($savequestionbook);
							}

							$TotalAmountIncludefee += $conversion_rate;
							$TotalAmount = '$' . sprintf('%0.2f', $conversion_rate) . ' TTD';
							$eventname = ucwords(strtolower($value['event']['name']));
							$ticket_name = $value['eventdetail']['title'];

							// $ordersummary .= '<p> <strong style="display: flex;"><span style="width: 60%; display:inline-block;font-size: 14px;font-weight: 400;">' . $eventname . ' (' . $ticket_name . ')</span><span style="width: 10%; display:inline-block;font-weight: 400;font-size: 14px;">:</span><span style="width: 30%; color:#464646; font-size:14px;font-weight: 400;">' . $TotalAmount . '</span></strong></p>';

							$ordersummary .= '<tr>
											<td width="60%">' . $eventname . ' (' . $ticket_name . ')</td>
											<td width="5%" align="center">:</td>
											<td width="35%">' . $TotalAmount . '</td>
											</tr>';

							$ordersummarywtsapp .= '%0A %0A' . $eventname . ' (' . $ticket_name . ')' . $TotalAmount . '  %0A';
							// delete from cart
							$this->Cartquestiondetail->deleteAll(['Cartquestiondetail.serial_no' => $value['serial_no']]);
							$this->Cart->deleteAll(['Cart.id' => $value['id']]);
						}
						// pr($TotalAmountIncludefee);exit;



						// send email to admin and event organiser 

						$requestername = $user_check['name'] . ' ' . $user_check['lname'];
						$url = SITE_URL . 'tickets/myticket';
						$site_url = SITE_URL;
						$paymentType = 'Online';
						$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 30])->first();
						$from = $emailtemplate['fromemail'];
						$to = $user_check['email'];
						$GrandTotalAmount = '$' . sprintf('%0.2f', $transaction_identifier_check->total_amount) . ' TTD';
						// $cc = $from;
						$subject = $emailtemplate['subject'] . ': ' . $eventname;
						$formats = $emailtemplate['description'];

						$message1 = str_replace(array('{EventName}', '{RequesterName}', '{URL}', '{SITE_URL}', '{PaymentType}', '{TotalAmount}', '{OrderSummary}'), array($eventname, $requestername, $url, $site_url, $paymentType, $GrandTotalAmount, $ordersummary), $formats);

						$message = stripslashes($message1);
						$message = '<!DOCTYPE HTML>
									 <html>                
									 <head>
										 <meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
										 <title>Untitled Document</title>
										 <style>
											 p {
												 margin: 9px 0px;
											 }
										 </style>                
									 </head>                
									 <body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
						$headers = 'MIME-Version: 1.0' . "\r\n";
						$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
						$headers .= 'From: <' . $from . '>' . "\r\n";
						$mail = $this->Email->send($to, $subject, $message);
						// send mail complete 

						// send watsappmessage start 
						$message = "*Eboxtickets: Payment Complete*%0AHi $requestername,%0A%0AYour payment was received for " . $ordersummarywtsapp . " ticket.%0ANo payment details were required.%0A%0ARegards,%0AEboxtickets.com";
						$numwithcode = $user_check['mobile'];
						$this->whatsappmsg($numwithcode, $message);
						// send watsappmessage start 

					}

					$findaddon = $this->Cartaddons->find('all')->contain(['Addons', 'Event' => 'Currency'])->where(['Cartaddons.user_id' => $user_id, 'Cartaddons.status' => 'Y'])->order(['Cartaddons.id' => 'ASC'])->toarray();

					if (!empty($findaddon)) {

						foreach ($findaddon as $key => $addondetail) {

							$conversion_rate_addon_amt = $addondetail['addon']['price'] * $addondetail['event']['currency']['conversion_rate'];

							$addondata['addons_id'] = $addondetail['addon_id'];
							$addondata['order_id'] = $saveorders->id;
							$addondata['price'] = $conversion_rate_addon_amt;
							$insertaddondata = $this->Addonsbook->patchEntity($this->Addonsbook->newEntity(), $addondata);
							$this->Addonsbook->save($insertaddondata);
							$this->Cartaddons->deleteAll(['Cart.id' => $addondetail['id']]);
						}
					}
					// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% For Ticket End %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

				}

				$response['success'] = true;
				$response['transactionIde'] = json_decode($transaction_identifier_check);
				$response['status'] = 'Ticket has been approved successfully';
				echo json_encode($response);
				die;
			} else {
				$response['success'] = false;
				$response['transactionIde'] = json_decode($transaction_identifier_check);

				$response['status'] = 'Invalid card details';
				echo json_encode($response);
				die;
			}
		} else {
			$response['success'] = false;
			$response['transactionIde'] = json_decode($transaction_identifier_check);
			$response['status'] = 'Invalid Transaction';
			echo json_encode($response);
			die;
		}
	}

	// Checkout step -2
	// public function paymentProcessing()
	// {
	// 	$this->loadModel('Orders');
	// 	$this->loadModel('Ticket'); // tblticket_book
	// 	$this->loadModel('Cartaddons');
	// 	$this->loadModel('Addons');
	// 	$this->loadModel('Addonsbook');
	// 	$this->loadModel('Cart');
	// 	$this->loadModel('Eventdetail');
	// 	$this->loadModel('Event');
	// 	$this->loadModel('Users');
	// 	$this->loadModel('Payment');
	// 	$this->loadModel('Ticketdetail');
	// 	$this->loadModel('Questionbook');
	// 	$this->loadModel('Cartquestiondetail');
	// 	$this->loadModel('Currency');
	// 	$this->loadModel('Templates');

	// 	$response_data = json_decode($this->request->data['Response']);
	// 	$transaction_identifier = $response_data->TransactionIdentifier;

	// 	$file_path = "/var/www/html/eboxtickets.com/webroot/logs/powertranz.txt";
	// 	$log .= json_encode($this->request->data) . "\n\n";
	// 	file_put_contents($file_path, $log, FILE_APPEND | LOCK_EX);


	// 	$transaction_identifier_check = $this->Payment->find()->where(['TransactionIdentifier' => $transaction_identifier])->first();
	// 	if ($transaction_identifier_check) {
	// 		$user_id = $transaction_identifier_check['user_id'];
	// 		$date = date("Y-m-d H:i:s");
	// 		//pr($response_data); die;
	// 		$total_amount = $response_data->TotalAmount;
	// 		$currency_code = $response_data->CurrencyCode;
	// 		$cardbrand = $response_data->CardBrand;
	// 		$iso_response_code = $response_data->IsoResponseCode;
	// 		$repsonse_message = $response_data->ResponseMessage;
	// 		$order_identifier  = $response_data->OrderIdentifier;
	// 		$spi_token  = $response_data->SpiToken;
	// 		$header = [
	// 			'Content-Type:application/json'
	// 		];
	// 		//pr($response_data); die;
	// 		//echo $iso_response_code;
	// 		$savedata_payment['total_amount'] = $total_amount;
	// 		$savedata_payment['currency_code'] = $currency_code;
	// 		$savedata_payment['cardbrand'] = $cardbrand;
	// 		$savedata_payment['iso_response_code_spiprocessing'] = $iso_response_code;
	// 		$savedata_payment['repsonse_message_processing'] = $repsonse_message;
	// 		$savedata_payment['order_identifier'] = $order_identifier;
	// 		$savedata_payment['spi_token'] = $spi_token;
	// 		$payment_patch = $this->Payment->patchEntity($transaction_identifier_check, $savedata_payment);
	// 		$this->Payment->save($payment_patch);

	// 		if ($iso_response_code  == "3D0") {
	// 			$request_data = $spi_token;
	// 			$request_json_data =   json_encode($request_data); //die;
	// 			//echo $request_json_data; die;

	// 			$url = PAYMENTURL . "spi/payment";
	// 			$curl = curl_init();
	// 			curl_setopt_array($curl, array(
	// 				CURLOPT_URL => $url,
	// 				CURLOPT_RETURNTRANSFER => true,
	// 				CURLOPT_FOLLOWLOCATION => 0,
	// 				CURLOPT_ENCODING => "",
	// 				CURLOPT_SSL_VERIFYPEER => false,
	// 				CURLOPT_MAXREDIRS => 10,
	// 				CURLOPT_TIMEOUT => 40,
	// 				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	// 				CURLOPT_CUSTOMREQUEST => "POST",
	// 				CURLOPT_POSTFIELDS => $request_json_data,
	// 				CURLOPT_HTTPHEADER => $header
	// 			));
	// 			$paymentresponse = curl_exec($curl);
	// 			$err = curl_error($curl);
	// 			curl_close($curl);
	// 			//}

	// 			$logpayment_response .= json_encode($paymentresponse) . "\n\n";
	// 			file_put_contents($file_path, $logpayment_response, FILE_APPEND | LOCK_EX);

	// 			$payment_response =  json_decode($paymentresponse);
	// 			// pr($payment_response); die;


	// 			$savedata_payment_again['iso_response_code_spipayment'] = $payment_response->IsoResponseCode;
	// 			$savedata_payment_again['repsonse_message_spipayment'] = $payment_response->ResponseMessage;
	// 			$payment_patch_again = $this->Payment->patchEntity($transaction_identifier_check, $savedata_payment_again);
	// 			$this->Payment->save($payment_patch_again);


	// 			if ($payment_response->IsoResponseCode == "00") {

	// 				$header_capture = [
	// 					'Accept:application/json',
	// 					'PowerTranz-PowerTranzId:' . POWERTRANZID,
	// 					'PowerTranz-PowerTranzPassword:' . POWERTRANZPASSWORD,
	// 					'Content-Type:application/json'
	// 				];

	// 				$request_data = [
	// 					"TransactionIdentifier" => $payment_response->TransactionIdentifier,
	// 					"TotalAmount" => $payment_response->TotalAmount,
	// 					"ExternalIdentifier" => null,
	// 				];

	// 				$request_json_data_capture =   json_encode($request_data);

	// 				$url = PAYMENTURL . "capture";
	// 				$curl = curl_init();
	// 				curl_setopt_array($curl, array(
	// 					CURLOPT_URL => $url,
	// 					CURLOPT_RETURNTRANSFER => true,
	// 					CURLOPT_FOLLOWLOCATION => 0,
	// 					CURLOPT_ENCODING => "",
	// 					CURLOPT_SSL_VERIFYPEER => false,
	// 					CURLOPT_MAXREDIRS => 10,
	// 					CURLOPT_TIMEOUT => 40,
	// 					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	// 					CURLOPT_CUSTOMREQUEST => "POST",
	// 					CURLOPT_POSTFIELDS => $request_json_data_capture,
	// 					CURLOPT_HTTPHEADER => $header_capture
	// 				));
	// 				$response_capture = curl_exec($curl);
	// 				$err = curl_error($curl);
	// 				curl_close($curl);


	// 				$logpayment_capture = $response_capture . "\n\n";
	// 				$logpayment_capture .= "===============================";
	// 				file_put_contents($file_path, $logpayment_capture, FILE_APPEND | LOCK_EX);

	// 				$checkout_response_capture = json_decode($response_capture);

	// 				$savedata_payment_again['iso_response_code_spicheckout'] = $checkout_response_capture->IsoResponseCode;
	// 				$savedata_payment_again['repsonse_message_spicheckout'] = $checkout_response_capture->ResponseMessage;
	// 				$savedata_payment_again['RRN'] =  $checkout_response_capture->RRN;
	// 				$savedata_payment_again['OrderIdentifiercheckout'] = $checkout_response_capture->OrderIdentifier;
	// 				$savedata_payment_again['OriginalTrxnIdentifiercheckout'] = $checkout_response_capture->OriginalTrxnIdentifier;
	// 				$savedata_payment_again['TransactionIdentifiercheckout'] = $checkout_response_capture->TransactionIdentifier;
	// 				$savedata_payment_again['TransactionTypecheckout'] = $checkout_response_capture->TransactionType;
	// 				$savedata_payment_again['Approvedcheckout'] = $checkout_response_capture->Approved;

	// 				$payment_patch_again = $this->Payment->patchEntity($transaction_identifier_check, $savedata_payment_again);
	// 				$this->Payment->save($payment_patch_again);

	// 				if ($checkout_response_capture->IsoResponseCode == 0) {
	// 					$response['success'] = true;
	// 					$response['status'] = 'Ticket has been approved successfully';
	// 					echo json_encode($response);
	// 					die;
	// 				} else {
	// 					$response['success'] = false;
	// 					$response['status'] = 'Invalid Details';
	// 					echo json_encode($response);
	// 					die;
	// 				}
	// 			} else {

	// 				$response['success'] = false;
	// 				$response['status'] = 'Something went wrong please try again';
	// 				echo json_encode($response);
	// 				die;
	// 			}
	// 		} else {
	// 			$response['success'] = false;
	// 			$response['status'] = 'Invalid card details';
	// 			echo json_encode($response);
	// 			die;
	// 		}
	// 	} else {
	// 		$response['success'] = false;
	// 		$response['status'] = 'Invalid Transaction';
	// 		echo json_encode($response);
	// 		die;
	// 	}
	// }

	// Last step processing
	// public function paymentProcessingchecking()
	// {
	// 	$this->loadModel('Orders');
	// 	$this->loadModel('Ticket'); // tblticket_book
	// 	$this->loadModel('Cartaddons');
	// 	$this->loadModel('Addons');
	// 	$this->loadModel('Addonsbook');
	// 	$this->loadModel('Cart');
	// 	$this->loadModel('Eventdetail');
	// 	$this->loadModel('Event');
	// 	$this->loadModel('Users');
	// 	$this->loadModel('Payment');
	// 	$this->loadModel('Ticketdetail');
	// 	$this->loadModel('Questionbook');
	// 	$this->loadModel('Cartquestiondetail');
	// 	$this->loadModel('Currency');
	// 	$this->loadModel('Templates');

	// 	$transaction_identifier = $this->request->data['TransactionIdentifier'];
	// 	$transaction_identifier_check = $this->Payment->find()->where(['TransactionIdentifier' => $transaction_identifier])->first();

	// 	if ($transaction_identifier_check) {

	// 		if ($transaction_identifier_check['iso_response_code_spiprocessing']  == "3D0") {

	// 			if ($transaction_identifier_check->iso_response_code_spipayment != '' && $transaction_identifier_check->iso_response_code_spipayment == "00") {

	// 				if ($transaction_identifier_check->iso_response_code_spicheckout != '' && $transaction_identifier_check->iso_response_code_spicheckout == "00") {

	// 					$user_id =  $transaction_identifier_check['user_id'];
	// 					$user_check = $this->Users->get($user_id);
	// 					$admin_fee = $this->Users->find()->where(['role_id' => 1])->first();
	// 					$fees = $admin_fee['feeassignment'];
	// 					$orderdata['adminfee'] = $fees;
	// 					$orderdata['user_id'] = $user_id;
	// 					$orderdata['total_amount'] = sprintf('%.2f', $transaction_identifier_check['total_amount']);
	// 					$orderdata['RRN'] = $transaction_identifier_check['RRN'];
	// 					$orderdata['IsoResponseCode'] = $transaction_identifier_check['iso_response_code_spicheckout'];
	// 					$orderdata['OrderIdentifier'] = $transaction_identifier_check['OrderIdentifiercheckout'];
	// 					$orderdata['OriginalTrxnIdentifier'] = $transaction_identifier_check['OriginalTrxnIdentifiercheckout'];
	// 					$orderdata['TransactionIdentifier'] = $transaction_identifier_check['TransactionIdentifiercheckout'];
	// 					$orderdata['TransactionType'] = $transaction_identifier_check['TransactionTypecheckout'];
	// 					$orderdata['Approved'] = $transaction_identifier_check['Approvedcheckout'];
	// 					//$orderdata['card_holder_name'] = $this->request->data['holdername'];
	// 					//$orderdata['card_number'] = $this->request->data['cardnumber'];
	// 					//$orderdata['month_year'] = $this->request->data['monthyear'];
	// 					$orderdata['paymenttype'] = "Online";
	// 					$insertdata = $this->Orders->patchEntity($this->Orders->newEntity(), $orderdata);

	// 					if ($saveorders = $this->Orders->save($insertdata)) {

	// 						// $cart_data = $this->Cart->find('all')->contain(['Event', 'Eventdetail'])->where(['Cart.user_id' => $user_id, 'Cart.status' => 'Y'])->order(['Cart.id' => 'ASC'])->toarray();

	// 						$cart_data = $this->Cart->find('all')->contain(['Event' => ['Currency'], 'Eventdetail'])->where(['Cart.user_id' => $user_id, 'Cart.status' => 'Y', 'Cart.package_id IS NULL'])->order(['Cart.user_id' => 'ASC'])->toarray();

	// 						$TotalAmount = 0;
	// 						$conversion_rate = 0;
	// 						$ordersummary = '';
	// 						$ordersummarywtsapp = '';
	// 						if (!empty($cart_data)) {
	// 							foreach ($cart_data as $key => $value) {

	// 								$currenny = $this->Currency->get($value['event']['payment_currency']);
	// 								$conversion_rate = $value['eventdetail']['price'] * $currenny['conversion_rate'];

	// 								$fn['user_id'] = $user_id;
	// 								$fn['event_id'] =  $value['event_id'];
	// 								$fn['mpesa'] = null;
	// 								$fn['amount'] =  $value['eventdetail']['price'];
	// 								$payment = $this->Payment->patchEntity($this->Payment->newEntity(), $fn);
	// 								$this->Payment->save($payment);

	// 								$ticketbook['order_id'] = $saveorders->id;
	// 								$ticketbook['event_id'] = $value['event_id'];
	// 								$ticketbook['event_ticket_id'] = $value['ticket_id'];
	// 								$ticketbook['cust_id'] = $user_id;
	// 								$ticketbook['ticket_buy'] = 1;
	// 								$ticketbook['currency_rate'] = $currenny['conversion_rate'];
	// 								$ticketbook['amount'] = $conversion_rate;
	// 								$ticketbook['mobile'] = $user_check['mobile'];
	// 								$ticketbook['committee_user_id'] = $value['commitee_user_id'];
	// 								$insertticketbook = $this->Ticket->patchEntity($this->Ticket->newEntity(), $ticketbook);
	// 								$lastinsetid = $this->Ticket->save($insertticketbook);

	// 								$ticketdetaildata['tid'] = $lastinsetid['id'];
	// 								$ticketdetaildata['user_id'] = $user_id;
	// 								$ticketdetail = $this->Ticketdetail->patchEntity($this->Ticketdetail->newEntity(), $ticketdetaildata);
	// 								$ticketdetailvvv = $this->Ticketdetail->save($ticketdetail);

	// 								$Packff = $this->Ticketdetail->get($ticketdetailvvv['id']);
	// 								$Packff->ticket_num = 'T' . $ticketdetailvvv['id'];
	// 								$ticketdetail = $this->Ticketdetail->save($Packff);

	// 								$ticketqrimages = $this->qrcodepro($user_id, $ticketdetail['ticket_num'], $value['event']['event_org_id']);
	// 								$Pack = $this->Ticketdetail->get($ticketdetail['id']);
	// 								$Pack->qrcode = $ticketqrimages;
	// 								$this->Ticketdetail->save($Pack);

	// 								$questiondetail = $this->Cartquestiondetail->find('all')->where(['Cartquestiondetail.serial_no' => $value['serial_no']])->toarray();

	// 								foreach ($questiondetail as $keyid => $questionreply) {
	// 									$bookquestion['order_id'] = $saveorders['id'];
	// 									$bookquestion['ticketdetail_id'] = $ticketdetail['id'];
	// 									$bookquestion['question_id'] = $questionreply['question_id'];
	// 									$bookquestion['event_id'] = $questionreply['event_id'];
	// 									$bookquestion['user_id'] = $questionreply['user_id'];
	// 									$bookquestion['user_reply'] = $questionreply['user_reply'];
	// 									$savequestionbook = $this->Questionbook->patchEntity($this->Questionbook->newEntity(), $bookquestion);
	// 									$this->Questionbook->save($savequestionbook);
	// 								}

	// 								$TotalAmount = '$' . sprintf('%0.2f', $conversion_rate) . ' TTD';
	// 								$eventname = ucwords(strtolower($value['event']['name']));
	// 								$ticket_name = $value['eventdetail']['title'];

	// 								// $ordersummary .= '<p> <strong style="display: flex;"><span style="width: 60%; display:inline-block;font-size: 14px;font-weight: 400;">' . $eventname . ' (' . $ticket_name . ')</span><span style="width: 10%; display:inline-block;font-weight: 400;font-size: 14px;">:</span><span style="width: 30%; color:#464646; font-size:14px;font-weight: 400;">' . $TotalAmount . '</span></strong></p>';

	// 								$ordersummary .= '<tr>
	// 								<td width="45%">' . $eventname . ' (' . $ticket_name . ')</td>
	// 								<td width="10%" align="center">:</td>
	// 								<td width="45%">' . $TotalAmount . '</td>
	// 								</tr>';

	// 								$ordersummarywtsapp .= '%0A %0A' . $eventname . ' (' . $ticket_name . ')' . $TotalAmount . '  %0A';
	// 								// delete from cart
	// 								$this->Cartquestiondetail->deleteAll(['Cartquestiondetail.serial_no' => $value['serial_no']]);
	// 								$this->Cart->deleteAll(['Cart.id' => $value['id']]);
	// 							}

	// 							// send email to admin and event organiser 

	// 							$requestername = $user_check['name'] . ' ' . $user_check['lname'];
	// 							$url = SITE_URL . 'tickets/myticket';
	// 							$site_url = SITE_URL;
	// 							$paymentType = 'Online';
	// 							$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 30])->first();
	// 							$from = $emailtemplate['fromemail'];
	// 							$to = $user_check['email'];
	// 							$GrandTotalAmount = '$' . sprintf('%0.2f', $checkout_response_capture->TotalAmount) . ' TTD';
	// 							// $cc = $from;
	// 							$subject = $emailtemplate['subject'] . ': ' . $eventname;
	// 							$formats = $emailtemplate['description'];

	// 							$message1 = str_replace(array('{EventName}', '{RequesterName}', '{URL}', '{SITE_URL}', '{PaymentType}', '{TotalAmount}', '{OrderSummary}'), array($eventname, $requestername, $url, $site_url, $paymentType, $GrandTotalAmount, $ordersummary), $formats);

	// 							$message = stripslashes($message1);
	// 							$message = '<!DOCTYPE HTML>
	// 						 <html>                
	// 						 <head>
	// 							 <meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
	// 							 <title>Untitled Document</title>
	// 							 <style>
	// 								 p {
	// 									 margin: 9px 0px;
	// 								 }
	// 							 </style>                
	// 						 </head>                
	// 						 <body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
	// 							$headers = 'MIME-Version: 1.0' . "\r\n";
	// 							$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	// 							$headers .= 'From: <' . $from . '>' . "\r\n";
	// 							$mail = $this->Email->send($to, $subject, $message);
	// 							// send mail complete 

	// 							// send watsappmessage start 
	// 							$message = "*Eboxtickets: Payment Complete*%0AHi $requestername,%0A%0AYour payment was received for " . $ordersummarywtsapp . " ticket.%0ANo payment details were required.%0A%0ARegards,%0AEboxtickets.com";
	// 							$numwithcode = $user_check['mobile'];
	// 							$this->whatsappmsg($numwithcode, $message);
	// 							// send watsappmessage start 

	// 						}

	// 						$findaddon = $this->Cartaddons->find('all')->contain(['Addons', 'Event' => 'Currency'])->where(['Cartaddons.user_id' => $user_id, 'Cartaddons.status' => 'Y'])->order(['Cartaddons.id' => 'ASC'])->toarray();

	// 						if (!empty($findaddon)) {

	// 							foreach ($findaddon as $key => $addondetail) {

	// 								$conversion_rate_addon_amt = $addondetail['addon']['price'] * $addondetail['event']['currency']['conversion_rate'];

	// 								$addondata['addons_id'] = $addondetail['addon_id'];
	// 								$addondata['order_id'] = $saveorders->id;
	// 								$addondata['price'] = $conversion_rate_addon_amt;
	// 								$insertaddondata = $this->Addonsbook->patchEntity($this->Addonsbook->newEntity(), $addondata);
	// 								$this->Addonsbook->save($insertaddondata);
	// 								$this->Cartaddons->deleteAll(['Cart.id' => $addondetail['id']]);
	// 							}
	// 						}
	// 					}
	// 					// $this->Flash->success(__('Your Ticket has been booked'));
	// 					// return $this->redirect(['controller' => 'Tickets', 'action' => 'myticket']);

	// 					$response['success'] = true;
	// 					$response['status'] = 'Ticket has been approved successfully';
	// 					echo json_encode($response);
	// 					die;
	// 				} else {

	// 					$response['success'] = false;
	// 					$response['status'] = 'Invalid Details';
	// 					echo json_encode($response);
	// 					die;
	// 				}
	// 			} else {

	// 				$response['success'] = false;
	// 				$response['status'] = 'Something went wrong please try again';
	// 				echo json_encode($response);
	// 				die;
	// 			}
	// 		} else {
	// 			$response['success'] = false;
	// 			$response['status'] = 'Invalid card details';
	// 			echo json_encode($response);
	// 			die;
	// 		}
	// 	} else {
	// 		$response['success'] = false;
	// 		$response['status'] = 'Invalid Transaction';
	// 		echo json_encode($response);
	// 		die;
	// 	}
	// }

	// for package purchase
	public function packageAssigned($userInfo, $saveorders, $fees, $cart_packages_data)
	{
		$this->loadModel('Orders');
		$this->loadModel('Ticket'); // tblticket_book
		$this->loadModel('Cartaddons');
		$this->loadModel('Addons');
		$this->loadModel('Addonsbook');
		$this->loadModel('Cart');
		$this->loadModel('Eventdetail');
		$this->loadModel('Event');
		$this->loadModel('Users');
		$this->loadModel('Payment');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Questionbook');
		$this->loadModel('Cartquestiondetail');
		$this->loadModel('Package');
		$this->loadModel('Packagedetails');
		$this->loadModel('Currency');
		$this->loadModel('Templates');

		$user_id = $userInfo['id'];
		$totalPackageAmount = $saveorders['total_amount'];
		$orderId = $saveorders['id'];
		$date = date("Y-m-d H:i:s");

		// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% For Package Start %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

		if (!empty($cart_packages_data[0])) {

			$TotalAmount = 0;
			$ordersummary = '';
			$ordersummarywtsapp = '';
			$ordersummary .= '<tbody>';
			$pacAmt = null;
			$Gamt = null;

			foreach ($cart_packages_data as $cart_pac_id => $cart_packages_details) {

				$addons = $this->Packagedetails->find('all')
					->contain(['Addons'])
					->where([
						'Packagedetails.package_id' => $cart_packages_details['package_id'],
						'Packagedetails.addon_id IS NOT' => NULL
					])
					->toArray();

				$packageDetails = $cart_packages_details->package->packagedetails;

				$pacAmt = ($cart_packages_details['package']['grandtotal'] * $cart_packages_details['event']['currency']['conversion_rate'] + ($cart_packages_details['package']['grandtotal'] * $cart_packages_details['event']['currency']['conversion_rate'] * $fees / 100));

				$Gamt += $pacAmt;

				$ordersummary .= '<tr style="background-color: #e62d56; color: #fff">';
				$ordersummary .= '<td width="60%" style="font-weight: 700">' . $cart_packages_details['package']['name'] . '</td>';
				$ordersummary .= '<td width="10%" align="center">:</td>';
				$ordersummary .= '<td width="30%">$' . $pacAmt . ' TTD</td>';
				$ordersummary .= '</tr>';

				$ordersummary .= '<tr colspan="3">';
				$ordersummary .= '<td style="font-weight: 700">Tickets:</td>';
				$ordersummary .= '</tr>';

				foreach ($packageDetails as $packageDetail) {
					$ordersummary .= '<tr>';
					$ordersummary .= '<td width="45%">' . $packageDetail['eventdetail']['title'] . '</td>';
					$ordersummary .= '<td width="10%" align="center">:</td>';
					$ordersummary .= '<td width="45%">' . $packageDetail['qty'] . '</td>';
					$ordersummary .= '</tr>';

					for ($i = 0; $i < $packageDetail['qty']; $i++) {
						$currenny = $this->Currency->get($cart_packages_details['event']['currency']['id']);
						// $fn['user_id'] = $user_id;
						// $fn['event_id'] = $cart_packages_details['event_id'];
						// $fn['amount'] =  $packageDetail['eventdetail']['price'];
						// $fn['created'] = $date;
						// $payment = $this->Payment->patchEntity($this->Payment->newEntity(), $fn);
						// $this->Payment->save($payment);
						// pr($payment);


						$ticketbook['order_id'] = $orderId;
						$ticketbook['event_id'] = $cart_packages_details['event_id'];
						$ticketbook['event_ticket_id'] = $packageDetail['eventdetail']['id'];
						$ticketbook['cust_id'] = $user_id;
						$ticketbook['ticket_buy'] = 1;
						$ticketbook['currency_rate'] = $currenny['conversion_rate'];
						$ticketbook['amount'] = $packageDetail['eventdetail']['price'];
						$ticketbook['mobile'] = ($userInfo['mobile']) ? $userInfo['mobile'] : $userInfo['id'];
						$ticketbook['created'] = $date;
						$ticketbook['adminfee'] = $fees;
						$ticketbook['package_id'] = $packageDetail['package_id'];

						$insertticketbook = $this->Ticket->patchEntity($this->Ticket->newEntity(), $ticketbook);
						$lastinsetid = $this->Ticket->save($insertticketbook);

						$ticketdetaildata['tid'] = $lastinsetid['id'];
						$ticketdetaildata['user_id'] = $user_id;
						$ticketdetaildata['package_id'] = $packageDetail['package_id'];
						$ticketdetaildata['created'] = $date;
						$ticketdetail = $this->Ticketdetail->patchEntity($this->Ticketdetail->newEntity(), $ticketdetaildata);
						$ticketdetailvvv = $this->Ticketdetail->save($ticketdetail);

						$Packff = $this->Ticketdetail->get($ticketdetailvvv['id']);
						$Packff->ticket_num = 'T' . $ticketdetailvvv['id'];
						$ticketdetail = $this->Ticketdetail->save($Packff);

						// // Here QR Code generate with the ticket number evenId organiser id and packageId
						$ticketqrimages = $this->qrcodepro($user_id, $ticketdetail['ticket_num'], $cart_packages_details['event']['event_org_id'], $packageDetail['package_id']);
						$Pack = $this->Ticketdetail->get($ticketdetail['id']);
						$Pack->qrcode = $ticketqrimages;
						$this->Ticketdetail->save($Pack);
						// pr($insertticketbook);
					}
				}

				$ordersummary .= '<tr colspan="3">';
				$ordersummary .= '<td style="font-weight: 700">Addons:</td>';
				$ordersummary .= '</tr>';

				foreach ($addons as $addon) {
					$ordersummary .= '<tr>';
					$ordersummary .= '<td width="50%">' . $addon['addon']['name'] . ' (' . $addon['addon']['description'] . ' )</td>';
					$ordersummary .= '<td width="10%" align="center">:</td>';
					$ordersummary .= '<td width="40%">' . $addon['qty'] . '</td>';
					$ordersummary .= '</tr>';
				}
			}
			// pr($Gamt);exit;
			$ordersummary .= '</tbody>';
			// send email to admin and event organiser 
			$requestername = $userInfo['name'] . ' ' . $userInfo['lname'];
			$url = SITE_URL . 'tickets/myticket';
			$site_url = SITE_URL;
			$paymentType = 'Online';
			$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 36])->first();
			$from = $emailtemplate['fromemail'];
			$eventname = $cart_packages_data[0]['event']['name'];
			$to = $userInfo['email'];
			// $to = 'rupam@doomshell.com';
			$TotalAmount = '$' . $Gamt . ' TTD';
			$subject = $emailtemplate['subject'] . ': ' . $eventname;
			$formats = $emailtemplate['description'];

			$message1 = str_replace(array('{EventName}', '{RequesterName}', '{URL}', '{SITE_URL}', '{PaymentType}', '{TotalAmount}', '{OrderSummary}'), array($eventname, $requestername, $url, $site_url, $paymentType, $TotalAmount, $ordersummary), $formats);

			$message = stripslashes($message1);
			$message = '<!DOCTYPE HTML>
				<html>                
				<head>
					<meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
					<title>Untitled Document</title>               
				</head>                
				<body style="background:#d8dde4;">' . $message1 . '</body></html>';
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: <' . $from . '>' . "\r\n";
			// send mail complete 
			$mail = $this->Email->send($to, $subject, $message);
			// pr($mail);exit;
			$watsmessage = "*Eboxtickets: Payment Complete*%0AHi $requestername,%0A%0AYour payment was received for " . $ordersummarywtsapp . " ticket.%0ANo payment details were required.%0A%0ARegards,%0AEboxtickets.com";
			$numwithcode = $userInfo['mobile'];
			$this->whatsappmsg($numwithcode, $watsmessage);
			// send watsappmessage start 


			$response['success'] = true;
			$response['status'] = 'Your Package has been booked successfully';
			$this->Cart->deleteAll(['Cart.user_id' => $user_id, 'Cart.ticket_type !=' => 'committesale']);
			echo json_encode($response);
			die;
		}
		// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%% For Package End %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

	}

	public function createGUID()
	{
		if (function_exists('com_create_guid')) {
			return com_create_guid();
		} else {
			mt_srand((float)microtime() * 10000);
			//optional for php 4.2.0 and up.
			$set_charid = strtoupper(md5(uniqid(rand(), true)));
			$set_hyphen = chr(45);
			$set_uuid = substr($set_charid, 0, 8) . $set_hyphen . substr($set_charid, 8, 4) . $set_hyphen . substr($set_charid, 12, 4) . $set_hyphen . substr($set_charid, 16, 4) . $set_hyphen . substr($set_charid, 20, 12);
			return $set_uuid;
		}
	}

	// Delete cart item from cart 
	public function deleteCartItem()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Cart');
		$this->loadModel('Cartaddons');
		$this->loadModel('Cartquestiondetail');

		$reqData = $this->request->data['cartItemId'];

		if (!empty($reqData)) {

			$find = $this->Cart->find('all')->where(['Cart.id' => $reqData])->first();
			$event_id = $find['event_id'];
			$user_id = $find['user_id'];
			$cart_data = $this->Cart->find('all')->where(['Cart.event_id' => $event_id, 'Cart.user_id' => $user_id])->count();
			if ($cart_data == 1) {
				$this->Cartaddons->deleteAll(['Cartaddons.user_id' => $user_id, 'Cartaddons.event_id' => $event_id]);
			}
			if (!empty($find['id'])) {
				$this->Cartquestiondetail->deleteAll(['Cartquestiondetail.serial_no' => $find['serial_no']]);
				$this->Cart->delete($find);
				$response['success'] = true;
				$response['status'] = 'Cart item deleted Successfully!!!.';
			} else {
				$findaddons = $this->Cartaddons->get($reqData);
				if ($findaddons['id']) {
					$this->Cartaddons->delete($findaddons);
					$response['success'] = true;
					$response['status'] = 'Cart item deleted Successfully!!!.';
				} else {
					$response['success'] = false;
					$response['status'] = 'Cart item not deleted wrong cart id';
				}
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'Sorry cart id no availble';
		}
		echo json_encode($response);
	}

	public function getAddons()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Addons');
		$this->loadModel('Cartaddons');

		$reqData = $this->request->data['eventId'];
		$userId = $this->request->data['userId'];

		if (!empty($userId)) {

			$find = $this->Cartaddons->find('all')->contain(['Addons'])->where(['Cartaddons.user_id' => $userId, 'Cartaddons.status' => 'Y'])->toarray();
			// pr($find);exit;

			// $find = $this->Addons->find('all')->where(['event_id' => $reqData, 'status' => 'Y'])->toarray();

			if ($find[0]) {
				$response['success'] = true;
				$response['status'] = null;
				$response['Addons'] = array();
				foreach ($find as $key => $value) {
					$output['id'] = $value['id'];
					$output['name'] = $value['addon']['name'];
					$output['price'] = $value['addon']['price'];
					$output['conut'] = $value['addon']['count'];
					$output['description'] =  $value['addon']['description'];
					array_push($response['Addons'], $output);
				}
			} else {
				$response['success'] = false;
				$response['status'] = 'Sorry No addons available this event';
			}
		} else {
			$response['success'] = false;
			$response['status'] = 'Sorry No addons available';
		}
		echo json_encode($response);
	}

	// get event new
	public function getEventsNew()
	{
		$this->autoRender = false;
		$page_number = $_REQUEST['page'];
		$cudate = date("Y-m-d H:i:s");
		$response = array();
		$this->loadModel('Ticket');
		$output = array();
		//$eventlist = $this->Event->find('all')->where(['Event.status' =>'Y'])->order(['Event.id' => 'DESC'])->toArray();     
		$eventlist = $this->Event->find('all')->where(['Event.status' => 'Y', 'Event.date_to >=' => $cudate])->order(['Event.id' => 'DESC'])->limit('2')->page($page_number)->toArray();

		if (count($eventlist) > 0) {
			$response['success'] = true;
			$response['status'] = null;
			$response['events'] = array();
			$pattern = "/<[^\/>]*>([\s]?)*<\/[^>]*>/";
			foreach ($eventlist as $value) {
				$descriptions = preg_replace($pattern, '', $value['desp']);
				$output['eventId'] = $value['id'];
				$output['eventName'] = $value['name'];
				$output['eventLocation'] = $value['location'];
				$output['eventStartDateTime'] = date('D, d M Y & h:i', strtotime($value['date_from']));
				$output['eventEndDateTime'] = date('D, d M Y & h:i', strtotime($value['date_to']));
				$data = $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $value['id']])->toArray();
				$ticketsoldout = $data['0']['ticketsold'];
				$output['noOfTickets'] = $value['no_of_seats'] - $ticketsoldout;
				$output['pricePerTicket'] = $value['amount'];
				$output['eventImageURL'] = SITE_URL . 'images/eventimages' . $value['feat_image'];

				if ($value['video_url'] != '') {
					$output['eventVideoURL'] = $value['video_url'];
				} else {
					$output['eventVideoURL'] = null;
				}

				$output['eventDescription'] = $descriptions;
				array_push($response['events'], $output);
			}
			//	PR($response); DIE;

		} else {
			$response['success'] = false;
			$response['status'] = 'There are no events to show here';
		}
		echo json_encode($response);
	}

	public function showEventsDetails()
	{
		$this->autoRender = false;
		$response = array();
		$eventid = $_REQUEST['event_id'];
		$userid = $_REQUEST['userid'];
		$event = array();
		$eventlist = $this->Event->find('all')->where(['Event.id' => $eventid, 'Event.event_org_id' => $userid, 'Event.status' => 'Y'])->order(['Event.id' => 'DESC'])->first();
		//pr($eventlist); die;     
		if (count($eventlist) > 0) {
			$response['success'] = 1;
			$response["output"] = array();

			$event['id'] = $eventlist['id'];
			$event['eventname'] = $eventlist['name'];
			$event['eventdate'] = strftime('%d %b', strtotime($eventlist['date_from']));
			$event['eventday'] = strftime('%A', strtotime($eventlist['date_from']));
			$event['location'] = $eventlist['location'];
			$event['eventimage'] = SITE_URL . 'images/eventimages' . $eventlist['thumbnail'];
			array_push($response['output'], $event);
		} else {
			$response["success"] = 0;
		}
		echo json_encode($response);
	}

	public function updateProfile()
	{
		$this->autoRender = false;
		$this->loadmodel('Users');
		$response = array();
		$userId = $_REQUEST['userId'];
		$fName = ucfirst(strtolower(trim($_REQUEST['fName'])));
		$lName = ucfirst(strtolower(trim($_REQUEST['lName'])));
		$gender = ucfirst(strtolower(trim($_REQUEST['gender'])));
		$dob = date('Y-m-d', strtotime(trim($_REQUEST['dob'])));
		$userprofile = $_FILES['image'];
		$emailRelatedEvents = $_REQUEST['emailRelatedEvents'];
		$emailNewsLetter = $_REQUEST['emailNewsLetter'];

		$event = array();
		$eventlist = $this->Users->find('all')->where(['Users.id' => $userId, 'Users.status' => 'Y'])->order(['Users.id' => 'DESC'])->first();
		if (count($eventlist) > 0) {

			// for image 
			$imagefilename = $userprofile['name'];
			if (!empty($imagefilename)) {
				$item = $userprofile['tmp_name'];
				$ext =  end(explode('.', $imagefilename));
				$name = md5(time() . $item);
				$imagename = $name . '.' . $ext;
				if (move_uploaded_file($userprofile['tmp_name'], 'images/Usersprofile/' . $imagename)) {
					unlink('images/Usersprofile/' . $eventlist['profile_image']);
					$image['profile_image'] = $imagename;
					$users = $this->Users->patchEntity($eventlist, $image);
					if ($this->Users->save($users)) {
						$response["success"] = true;
						$response["status"] = true;
						$response['message'] = 'Profile image update successfully !';
					}
				} else {
					$response["success"] = false;
					$response["status"] = false;
					$response['message'] = 'Profile image not update !';
					echo json_encode($response);
					die;
				}
			}

			$userProfile['name'] = $fName;
			$userProfile['lname'] = $lName;
			$userProfile['gender'] = $gender;
			$userProfile['dob'] = $dob;
			$userProfile['emailNewsLetter'] = $emailNewsLetter;
			$userProfile['emailRelatedEvents'] = $emailRelatedEvents;

			$users = $this->Users->patchEntity($eventlist, $userProfile);

			if ($this->Users->save($users)) {

				$response['success'] = true;
				$response['status'] = true;
				$response['message'] = "Profile has been update successfully !";

				$event['userId'] = $eventlist['id'];
				$event['fName'] = $eventlist['name'];
				$event['lName'] = $eventlist['lname'];
				$event['mobileNumber'] = $eventlist['mobile'];
				$event['emailId'] = $eventlist['email'];
				$event['dob'] = date('d-m-Y', strtotime($eventlist['dob']));
				$event['profile_image'] = IMAGE_PATH . 'Usersprofile/' . $eventlist['profile_image'];

				if ($eventlist['role_id'] == '2') {
					$event['roleId'] = 'ORGANISER';
				} else if ($eventlist['role_id'] == '3') {
					$event['roleId'] = 'USER';
				}

				$response['profile'] = $event;
			}
		} else {
			$response["success"] = false;
			$response["status"] = false;
			$response['message'] = 'Unable to update profile info!';
		}
		echo json_encode($response);
	}

	/*{			
		// to fetch buyTicket
			//~ public function buyTicket(){
			//~ $this->autoRender=false;
			//~ $response=array();

			//~ $event=array();   
			//~ $eventid=$_REQUEST['event_id'];
			//~ $userid=$_REQUEST['user_id'];
			//~ $eventdetail = $this->Event->find('all')->where(['Event.id' =>$eventid])->first();   
			//~ if(count($eventdetail) > 0)
			//~ { 
			//~ $response["success"] = 1;
			//~ $response["output"]=array();	
			//~ $event['date']=strftime('%d %b',strtotime($eventdetail['date_from']));
			//~ $event['day']=strftime('%A',strtotime($eventdetail['date_from']));
			//~ //$event['time']=date('h:i:A',strtotime($eventdetail['date_from']));
			//~ $event['time']=date("g:i A", strtotime($eventdetail['date_from']));
			//~ $event['eventname']=$eventdetail['name'];
			//~ $event['price']=$eventdetail['amount'];
			//~ $event['location']=$eventdetail['location'];
			//~ $event['datefrom']=strftime('%d %b',strtotime($eventdetail['date_from']))." ".$event['time'];
			//~ $event['dateto']=strftime('%d %b',strtotime($eventdetail['date_to']))." ".$event['time'];
			//~ $event['dayfrom']=strftime('%A',strtotime($eventdetail['date_from']));
			//~ $event['dayto']=strftime('%A',strtotime($eventdetail['date_to']));
			//~ $event['thumbnail']=SITE_URL.'imagess'.$eventdetail['thumbnail'];
			//~ array_push($response["output"], $event);
			//~ }
			//~ else{
			//~ $response["success"] = 0;
			//~ }
			//~ echo json_encode($response);
			//~ }


			// to fetch registration
			// public function registration()
			// {
			// 	$this->autoRender = false;
			// 	$this->loadmodel('Users');
			// 	$response = array();
			// 	$register_imei = $_REQUEST['imei'];
			// 	$name = $_REQUEST['name'];
			// 	$email = $_REQUEST['email'];
			// 	$mobile = $_REQUEST['mobile'];
			// 	if (!empty($_REQUEST['imei']) && !empty($_REQUEST['name']) && !empty($_REQUEST['email']) && !empty($_REQUEST['mobile'])) {
			// 		$userTable = TableRegistry::get('Users');
			// 		$exists = $userTable->exists(['imei' => $register_imei]);
			// 		if ($exists) {
			// 			$user_register = $this->Users->find('all')->where(['imei' => $register_imei])->first();
			// 			//pr($userdata);die;
			// 			$this->request->data['name'] = $name;
			// 			$this->request->data['email'] = $email;
			// 			$this->request->data['mobile'] = $mobile;
			// 			$user_register = $this->Users->patchEntity($user_register, $this->request->data);
			// 			$user_register = $this->Users->save($user_register);
			// 		} else {
			// 			$user_register = $this->Users->newEntity();
			// 			$this->request->data['imei'] = $register_imei;
			// 			$this->request->data['name'] = $name;
			// 			$this->request->data['email'] = $email;
			// 			$this->request->data['mobile'] = $mobile;
			// 			$user_register = $this->Users->patchEntity($user_register, $this->request->data);
			// 			$user_register = $this->Users->save($user_register);
			// 		}
			// 		$response["success"] = 1;
			// 		$response["message"] = 'Registration Successfull';
			// 	} else {
			// 		$response["success"] = 0;
			// 	}
			// 	echo json_encode($response, JSON_UNESCAPED_SLASHES);
			// }
	}*/

	public function confirmTicket()
	{
		$CheckoutRequestID = $_REQUEST['CheckoutRequestID'];
		$business_code = '372222';
		/*
		//$CheckoutRequestID ='ws_CO_DMZ_343903402_15052019170132050'; // this value should be the last transction entered for
		$curl_post_data = array(								//  for that event for that user from tblticket_book
		  //Fill in the request parameters with valid values
		  'CheckoutRequestID' => ''.$CheckoutRequestID.'',
		  'business_code' => ''.$business_code.''
		);

		$data_string = json_encode($curl_post_data);
		//echo $data_string; echo "<br>"
		$username="MPesaSTK";
		$password="STK@2019!#";	 
		$url = 'https://198.154.230.163/~coopself/mpesastkapi/stkpush_query.php';
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $password);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // ask for results to be returned
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		$curl_response = curl_exec($curl);
		$decodedText = html_entity_decode($curl_response);
		$myArray = json_decode($decodedText, true);
		$requestId = @$myArray['requestId'];
		$errorCode = @$myArray['errorCode'];
		$errorMessage = @$myArray['errorMessage'];
		$ResultDesc = @$myArray['ResultDesc'];
		*/
		$ResultDesc = 'The service request is processed successfully.';
		if ($ResultDesc == 'The service request is processed successfully.') //Payment confiramtion Successfull
		{
			$response["success"] = true;
			$response["status"] = $ResultDesc;
		} else // Paymenent didnt go through
		{
			$response["success"] = false;
			$response["status"] = $ResultDesc; //Request cancelled by user,-	Invalid Credentials,Insufficient Balance
		}

		echo json_encode($response, JSON_UNESCAPED_SLASHES);
		die;
	}

	// to fetch mpesaPayment
	public function buyTicket()
	{
		$this->autoRender = false;
		$response = array();
		$this->loadModel('Ticket');
		$this->loadModel('Users');
		$this->loadModel('Event');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Payment');

		//$profess = $this->Users->find('all')->where(['id' =>$userid])->first(); 
		//$mobile = $profess['mobile'];

		$userid = $_REQUEST['userId'];
		$mobile = $_REQUEST['mobile'];
		$quantity = $_REQUEST['noOfTickets'];
		$eventid = $_REQUEST['eventId'];
		$amount = $_REQUEST['amount'];

		$data_event_qr = $this->Event->find('all')->where(['Event.id' => $eventid])->first();
		if (isset($_POST) && !empty($_POST)) {
			$eve = $this->Event->find('all')->where(['Event.id' => $eventid, 'Event.status' => 'Y'])->contain(['Users'])->first();
			$payment = $this->Payment->newEntity();

			$fn['user_id'] = $userid;
			$fn['event_id'] = $eventid;
			$fn['mpesa'] = $mpesaid;
			$fn['amount'] = $amount;
			$payment = $this->Payment->patchEntity($payment, $fn);
			$payment = $this->Payment->save($payment);

			$ticketbook = $this->Ticket->newEntity();
			$tn['event_id'] = $eventid;
			$tn['cust_id'] = $userid;
			$tn['ticket_buy'] = $quantity;
			$tn['amount'] = $amount;
			$customer_phone = '254' . substr($mobile, 1);
			$transactiondesc = "eboxticket Payment Transaction";
			$business_code = '372222';
			/*
						$curl_post_data = array(
						  //Fill in the request parameters with valid values
						  'customer_phone' => ''.$customer_phone.'',
						  'accountno' => ''.$mobile.'',
						  'transactiondesc' => ''.$transactiondesc.'',
						  'amount' => ''.$amount.'',
						  'business_code' => ''.$business_code.''
						);

						$data_string = json_encode($curl_post_data);
						//echo $data_string; echo "<br>";

						$username="MPesaSTK";
						$password="STK@2019!#";	 
						$url = 'https://198.154.230.163/~coopself/mpesastkapi/index.php';
						$curl = curl_init();
						curl_setopt($curl, CURLOPT_URL, $url);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curl, CURLOPT_POST, true);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
						curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $password);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // ask for results to be returned
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
						$curl_response = curl_exec($curl);
						
						$decodedText = html_entity_decode($curl_response);
						$myArray = json_decode($decodedText, true);
						//print_r($myArray); die;
						$MerchantRequestID = @$myArray['MerchantRequestID'];
						$CheckoutRequestID = @$myArray['CheckoutRequestID'];
						$ResponseCode = @$myArray['ResponseCode'];
						$ResponseDescription = @$myArray['ResponseDescription'];
                        */
			$ResponseCode = 0;
			//Confirm if the phone has really received STK Push
			if ($ResponseCode == '0') //success, Send STK Push
			{

				$response["success"] = true;
				$response["CheckoutRequestID"] = '12345'; //$CheckoutRequestID;
				$response["status"] = 'Your request to pay KES' . $amount . ' for FlashTicket event for ' . $eve['name'] . ' has been successfully received. You will be taken to MPESA Shortly on your phone.';

				$tn['mobile'] = $customer_phone;
				$tn['CheckoutRequestID'] =  '12345';  //$CheckoutRequestID;
				$ticketbook = $this->Ticket->patchEntity($ticketbook, $tn);
				$ticketbook = $this->Ticket->save($ticketbook);

				$lastticketid = $ticketbook->id;
				if ($lastticketid) {
					for ($i = 1; $i <= $quantity; $i++) {
						$ticketdetail = $this->Ticketdetail->newEntity();
						$ticketdata = $this->Ticket->find('all')->where(['id' => $lastticketid])->first();
						$pn['tid'] = $lastticketid;
						$pn['user_id'] = $userid;
						$ticketdetail = $this->Ticketdetail->patchEntity($ticketdetail, $pn);
						$ticketdetail = $this->Ticketdetail->save($ticketdetail);

						$Packff = $this->Ticketdetail->get($ticketdetail['id']);
						$Packff->ticket_num = 'T' . $ticketdetail['id'];
						$Packff = $this->Ticketdetail->save($Packff);
						$ticketqrimages = $this->qrcodepro($userid, $Packff['ticket_num'], $data_event_qr['event_org_id']);
						$Pack = $this->Ticketdetail->get($Packff['id']);
						//pr($Pack);die;
						$Pack->qrcode = $ticketqrimages;
						$this->Ticketdetail->save($Pack);

						//$response["success"] = true;	
						//$response["status"] ='Your Ticket is Booked Successfully!!!.';

					}
				}
			} else //Stk Push Error
			{
				$requestId = @$myArray['requestId'];
				$errorCode = @$myArray['errorCode'];
				$errorMessage = @$myArray['errorMessage'];

				if ($errorMessage == '[CBS - ] No ICCID found') {
					@$myArray['errorMessage'] = $mobile . ' is not a mpesa line.';
				}

				$response["success"] = false;
				$response["status"] = @$myArray['errorMessage'] . "\n\n1. Press OK and a pop up will appear\n2. Enter a mpesa phone no. to buy ticket\n\nA Pop up will appear on mpesa line. Input password and you will have your tickets.";
				//', enter your mpesa line below. Add text box when the number is not Safaricom'; 


			}
			//}

			echo json_encode($response, JSON_UNESCAPED_SLASHES);
		}
	}

	/*
			public function buyTicket(){

				$this->autoRender=false;
				$response=array();
				$this->loadModel('Ticket');
				$this->loadModel('Users'); 
				$this->loadModel('Event'); 
				$this->loadModel('Ticketdetail');
				$this->loadModel('Payment');
				if(isset($_POST) && !empty($_POST))
				{
					$userid=$_REQUEST['userId'];
					$mpesaid=$_REQUEST['mpesaId'];
					$quantity=$_REQUEST['noOfTickets'];
					$eventid=$_REQUEST['eventId'];
					
					$data_event_qr = $this->Event->find('all')->where(['Event.id' => $eventid])->first();
					$connection = ConnectionManager::get('db2'); 
					$connect =$connection->execute("SELECT count(*) as res FROM `transactions_573314` WHERE MPESA_REF_ID='".$mpesaid."'")->fetch('assoc');
					if($connect['res'] > 0)
					{
						
						$userTable = TableRegistry::get('Payment');
						$exists = $userTable->exists(['mpesa' => $mpesaid]);

						if($exists){
							$response["success"] = false;	
							$response["status"] ='Mpesa ID is already Used.';
						} else {

							$payment = $this->Payment->newEntity();
							$eve= $this->Event->find('all')->where(['Event.id'=>$eventid,'Event.status'=>'Y'])->contain(['Users'])->first();
					// pr($eve); die;
							$amount=$eve['amount'] * $quantity;
							$fn['user_id']=$userid;
							$fn['event_id']=$eventid;
							$fn['mpesa']=$mpesaid;
							$fn['amount']=$amount;
							$payment = $this->Payment->patchEntity($payment,$fn);
							$payment = $this->Payment->save($payment);

							$ticketbook = $this->Ticket->newEntity();
							$tn['event_id']=$eventid;
							$tn['cust_id']=$userid;
							$tn['ticket_buy']=$quantity;
							$tn['amount']=$amount;

							$ticketbook = $this->Ticket->patchEntity($ticketbook,$tn);
							$ticketbook = $this->Ticket->save($ticketbook);

							$lastticketid=$ticketbook->id;
							if($lastticketid){
								for($i=1;$i<=$quantity;$i++)
								{
									$ticketdetail = $this->Ticketdetail->newEntity();
									$ticketdata = $this->Ticket->find('all')->where(['id' => $lastticketid])->first();
									$pn['tid']=$lastticketid;
									$pn['user_id']=$userid;
									$ticketdetail = $this->Ticketdetail->patchEntity($ticketdetail,$pn);
									$ticketdetail = $this->Ticketdetail->save($ticketdetail);

									$Packff = $this->Ticketdetail->get($ticketdetail['id']);
									$Packff->ticket_num = 'T'.$ticketdetail['id'];
									$Packff = $this->Ticketdetail->save($Packff);
									$ticketqrimages = $this->qrcodepro($userid,$Packff['ticket_num'],$data_event_qr['event_org_id']);
									$Pack = $this->Ticketdetail->get($Packff['id']);
									//pr($Pack);die;
									$Pack->qrcode = $ticketqrimages;
									$this->Ticketdetail->save($Pack);
									
									$response["success"] = true;	
									$response["status"] ='Your Ticket is Booked Successfully!!!.';
									
								}



							}
						}
					} else {
						$response["success"] = false;	
						$response["status"] ='No Mpesa ID Exist!!!.';
						
					}
				}
				echo json_encode($response, JSON_UNESCAPED_SLASHES);
			} 

	*/

	public function myevents()
	{
		$this->autoRender = false;
		$userid = $_REQUEST['userid'];
		$response = array();

		$event = array();
		$pevent = array();
		$this->loadModel('Users');
		$this->loadModel('Event');
		$date = date("Y-m-d H:i:s");
		//echo $date; die;
		$upcoming_event1 = $this->Event->find('all')->where(['Event.date_from >=' => $date, 'Event.event_org_id' => $userid, 'Event.status' => 'Y'])->contain(['Users', 'Currency'])->order(['Event.id' => 'DESC'])->toarray();

		$past_event1 = $this->Event->find('all')->where(['Event.date_from <=' => $date, 'Event.event_org_id' => $userid, 'Event.status' => 'Y'])->contain(['Users', 'Currency'])->order(['Event.id' => 'DESC'])->toarray();
		// pr($upcoming_event1);exit;
		// pr($past_event1);
		// die;
		if (count($upcoming_event1) > 0 || count($past_event1) > 0) {
			$response["output"] = array();

			if ($upcoming_event1 > 0) {

				foreach ($upcoming_event1 as $value) { //pr($value);die;
					$event['eventid'] = $value['id'];
					$event['date'] = strftime('%d %b', strtotime($value['date_from']));
					$event['day'] = strftime('%A', strtotime($value['date_from']));
					$event['time'] = date("g:i A", strtotime($value['date_from']));
					$event['location'] = $value['location'];
					$event['datefrom'] = strftime('%d %b', strtotime($value['date_from'])) . " " . $event['time'];
					$event['dateto'] = strftime('%d %b', strtotime($value['date_to'])) . " " . $event['time'];
					$event['dayfrom'] = strftime('%A', strtotime($value['date_from']));
					$event['dayto'] = strftime('%A', strtotime($value['date_to']));
					$event['price'] = $value['amount'];

					$upcoming_event[] = $event;
				}
			} else {
				$upcoming_event[] = array();
			}

			if ($past_event1 > 0) {

				foreach ($past_event1 as $value2) { //pr($value);die;
					$pevent['eventid'] = $value2['id'];
					$pevent['date'] = strftime('%d %b', strtotime($value2['date_from']));
					$pevent['day'] = strftime('%A', strtotime($value2['date_from']));
					$pevent['time'] = date("g:i A", strtotime($value2['date_from']));
					$pevent['location'] = $value2['location'];
					$pevent['datefrom'] = strftime('%d %b', strtotime($value2['date_from'])) . " " . $event['time'];
					$pevent['dateto'] = strftime('%d %b', strtotime($value2['date_to'])) . " " . $event['time'];
					$pevent['dayfrom'] = strftime('%A', strtotime($value2['date_from']));
					$pevent['dayto'] = strftime('%A', strtotime($value2['date_to']));
					$pevent['price'] = $value2['amount'];

					$past_event[] = $pevent;
				}
			} else {
				$past_event[] = array();
			}
			//pr($upcoming_event); die;
			$data = array(
				'upcoming_event' => $upcoming_event,
				'past_event' => $past_event
			);
			if ($data != '') {
				$response["success"] = 1;
				$response['body'] = $data;
			} else {
				$response = array();
			}
		} else {
			$response["success"] = 0;
		}
		echo json_encode($response);
	}

	public function showshareTicket()
	{
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticket');
		$this->autoRender = false;
		$response = array();
		$userid = $_REQUEST['userId'];
		$mob = $_REQUEST['mobileNumber'];
		$eventid = $_REQUEST['eventId'];
		$ticket_num = $_REQUEST['ticketId'];
		$tick_detail = $this->Ticketdetail->find('all')->contain(['Ticket' => ['Event']])->where(['Ticketdetail.id' => $ticket, 'Ticketdetail.user_id' => $userid])->first();
		// pr($tick_detail); die;
		if (count($tick_detail) > 0) {
			$response["success"] = 1;
			$response["output"] = array();
			$event['ticketid'] = $tick_detail['id'];
			$event['userid'] = $tick_detail['user_id'];
			$event['event_name'] = $tick_detail['ticket']['event']['name'];
			$event['ticket_number'] = $tick_detail['ticket_num'];
			$event['event_time'] = date("l,F j,Y", strtotime($tick_detail['ticket']['event']['date_from']));
			array_push($response["output"], $event);
		} else {
			$response["success"] = 0;
		}
		echo json_encode($response);
	}

	//For share ticket
	public function shareTicket()
	{
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticket');
		$this->loadModel('Ticketshare');
		$this->loadModel('Users');
		$this->autoRender = false;
		$response = array();
		$userid = $_REQUEST['userId'];
		$mob = $_REQUEST['mobileNumber'];
		$eventid = $_REQUEST['eventId'];
		$ticket_num = $_REQUEST['ticketId'];


		$ticketnum = explode(',', $ticket_num);
		$cou_share = count($ticketnum);
		$data_event_qr = $this->Event->find('all')->where(['Event.id' => $eventid])->first();
		$userTable12s3 = TableRegistry::get('Users');
		$exist1s23 = $userTable12s3->exists(['id' => $userid, 'mobile' => trim($mob)]);
		if ($exist1s23) {
			$response["success"] = false;
			$response["status"] = "You can't share ticket to yourself!";
		} else {
			$userTable = TableRegistry::get('Users');
			$exists = $userTable->exists(['mobile' => $mob]);
			if ($exists) {
				//$uernew=$this->Users->find('all')->select(['id','mobile','name'])->where(['mobile LIKE'=>$mobile.'%'])->first();
				//pr($uernew); die;
				// $new_user = $uernew['id'];

				$tic_det = $this->Ticketdetail->find('all')->where(['ticket_num IN' => $ticketnum])->toarray();
				//pr($tic_det); die;
				if ($cou_share == '1') {
					$userTable123 = TableRegistry::get('Ticketshare');
					$exist123 = $userTable123->exists(['ticket_num' => $tic_det[0]['id']]);
					if ($exist123) {
						$response["success"] = false;
						$response["status"] = 'This Ticket is already shared to user.';
					} else {
						$ticketshare = $this->Ticketshare->newEntity();
						$fn['tid'] = $tic_det[0]['tid'];
						$fn['user_id'] = $userid;
						$fn['ticket_num'] = $tic_det[0]['id'];
						$fn['share_mobile'] = $mob;
						$uernewshare = $this->Users->find('all')->select(['id',])->where(['mobile' => $mob])->first();
						$ticketshare = $this->Ticketshare->patchEntity($ticketshare, $fn);
						$ticketshare = $this->Ticketshare->save($ticketshare);
						$ticketqrimages = $this->qrcodeproticket($uernewshare['id'], "T" . $tic_det[0]['id'], $data_event_qr['event_org_id']);
						$Pack = $this->Ticketshare->get($ticketshare['id']);
						$Pack->qrcode = $ticketqrimages;
						$this->Ticketshare->save($Pack);
						$response["success"] = true;
						$response["status"] = 'You have shared ' . $cou_share . ' ticket to ' . $mob . '.';
					}
				} else {
					foreach ($tic_det as $df) {
						//pr($df);
						$tic_share = $this->Ticketshare->find('all')->where(['tid' => $df['tid'], 'ticket_num' => $df['id'], 'user_id' => trim($userid)])->first();
						if (!empty($tic_share)) {
							$ticketshare = $this->Ticketshare->get($tic_share['id']);
							$fn['share_mobile'] = $mob;
							$ticketshare = $this->Ticketshare->patchEntity($ticketshare, $fn);
							$ticketshare = $this->Ticketshare->save($ticketshare);
							$response["success"] = true;
							$response["status"] = 'You have shared ' . $cou_share . ' tickets to ' . $mob . '.';
						} else {
							$ticketshare = $this->Ticketshare->newEntity();
							$fn['tid'] = $df['tid'];
							$fn['user_id'] = $userid;
							$fn['ticket_num'] = $df['id'];
							$fn['share_mobile'] = $mob;
							$uernewshare = $this->Users->find('all')->select(['id',])->where(['mobile' => $mob])->first();
							$ticketshare = $this->Ticketshare->patchEntity($ticketshare, $fn);
							$ticketshare = $this->Ticketshare->save($ticketshare);
							$ticketqrimages = $this->qrcodeproticket($uernewshare['id'], "T" . $df['id'], $data_event_qr['event_org_id']);
							$Pack = $this->Ticketshare->get($ticketshare['id']);
							$Pack->qrcode = $ticketqrimages;
							$this->Ticketshare->save($Pack);

							$response["success"] = true;
							$response["status"] = 'You have shared ' . $cou_share . ' tickets to ' . $mob . '.';
						}
					}
				}
			} else {
				$response["success"] = false;
				$response["status"] = 'Sorry, this mobile number is not registered!';
			}
		}


		echo json_encode($response);
	}

	public function qrcodeproticket($userid, $namess, $event_org_id)
	{
		// pr($userid);exit;
		$dirname = 'temp';
		$PNG_TEMP_DIR = WWW_ROOT . 'qrimages' . DS . $dirname . DS;
		//$PNG_WEB_DIR = 'temp/';
		if (!file_exists($PNG_TEMP_DIR))
			mkdir($PNG_TEMP_DIR);
		$filename = $PNG_TEMP_DIR . 'EBX.png';
		$name = $userid . "," . $namess . "," . $event_org_id;
		//$name=$name;
		$errorCorrectionLevel = 'M';
		$matrixPointSize = 4;

		$filename = $PNG_TEMP_DIR . 'EBX' . md5($name . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
		\QRcode::png($name, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
		//display generated file
		$qrimagename = basename($filename);
		return $qrimagename;
	}

	public function qrcodepro($user_id, $name, $event_org_id, $packageId = null)
	{
		$dirname = 'temp';
		$PNG_TEMP_DIR = WWW_ROOT . 'qrimages' . DS . $dirname . DS;

		if (!file_exists($PNG_TEMP_DIR))
			mkdir($PNG_TEMP_DIR);
		$filename = $PNG_TEMP_DIR . 'EBX.png';
		if ($packageId) {
			$name = $user_id . "," . $name . "," . $event_org_id . "," . $packageId;
		} else {
			$name = $user_id . "," . $name . "," . $event_org_id;
		}
		$errorCorrectionLevel = 'M';
		$matrixPointSize = 4;

		$filename = $PNG_TEMP_DIR . 'EBX' . md5($name . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
		\QRcode::png($name, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
		//display generated file
		$qrimagename = basename($filename);
		return $qrimagename;
	}

	// for upcomming tickets 
	public function myTickets()
	{
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticket');
		$this->loadModel('Users');
		$this->loadModel('Ticketshare');
		$this->loadModel('Eventdetail');
		$this->autoRender = false;
		$response = array();
		$cudate = date("Y-m-d H:i:s");
		$userid = $this->request->data('userId');

		$uernew = $this->Users->find('all')->select(['email', 'mobile', 'id', 'profile_image'])->where(['id' => $userid])->first();

		// $tick1 = $this->Ticket->find('all')->contain(['Event', 'Eventdetail'])->where(['Ticket.cust_id' => $userid, 'Event.date_to >=' => $cudate])->order(['Event.date_from' => 'DESC'])->toarray();
		$mobile = (empty($uernew['mobile'])) ? $uernew['id'] : $uernew['mobile'];
		$isProfileVerify = (!empty($uernew['profile_image'])) ? 'Y' : 'N';
		$response["isProfileVerify"] = $isProfileVerify;


		$tick1 = $this->Ticket->find('all')->contain(['Event', 'Eventdetail'])->where(['Ticket.mobile' => $mobile, 'Event.date_to >=' => $cudate])->order(['Event.date_from' => 'Asc'])->toarray();

		$tick12 = $this->Ticketshare->find('all')->order(['Ticketshare.id' => 'DESC'])->where(['Ticketshare.share_mobile' => $mobile])->group('tid')->toarray();

		if ($tick1 || $tick12) {
			$output = array();
			$response["success"] = true;
			$response["status"] = null;
			$response["myTickets"] = array();

			foreach ($tick1 as $value) { //pr($value);die;
				$output['eventId'] = $value['event']['id'];
				$output['rsvpDeadLine'] = date('Y-m-d H:i:s', strtotime(($value['event']['is_free'] == 'Y') ? $value['event']['request_rsvp'] : $value['event']['date_from']));
				$output['isPackage'] = ($value['package_id']) ? true : false;
				$output['eventName'] = ucwords(strtolower($value['event']['name']));
				$output['eventLocation'] = ucwords(strtolower($value['event']['location']));
				$output['eventStartDateTime'] = date('D, d M Y & h:i A', strtotime($value['event']['date_from']));
				$output['eventEndDateTime'] = date('D, d M Y & h:i A', strtotime($value['event']['date_to']));
				$output['eventImageURL'] = SITE_URL . 'images/eventimages/' . $value['event']['feat_image'];
				$output['pricePerTicket'] = $value['event']['amount'];
				$output['purchaseDate'] = date('D, d M Y & h:i A', strtotime($value['created']));

				if ($value['event_admin'] == 0) {
					$output['ticketType'] = ($value['event']['is_free'] == 'Y') ? 'Invitation' : 'Purchased';
					$output['isFree'] = ($value['event']['is_free'] == 'Y') ? true : false;
					$output['ticketTypeName'] = $value['eventdetail']['title'];
				} else {
					$output['ticketType'] = ($value['event']['is_free'] == 'Y') ? 'Invitation' : 'Complementary';
					$output['ticketTypeName'] = $value['eventdetail']['title'];
					$output['isFree'] = ($value['event']['is_free'] == 'Y') ? true : false;
				}
				$event_date_check = strtotime($value['event']['date_to']);
				$current_date_check = strtotime('d M Y h:i');
				if ($current_date_check > $event_date_check) {
					$output['eventExpired'] = true;
				} else {
					$output['eventExpired'] = false;
				}

				$output["tickets"] = array();
				$tick_details = $this->Ticketdetail->find('all')->where(['Ticketdetail.tid' => $value['id']])->toarray();
				//pr($tick_details); die;
				$output2 = array();

				foreach ($tick_details as $val) {
					$ticknumb = ltrim($val['ticket_num'], "T");
					$tick12s = $this->Ticketshare->find('all')->where(['Ticketshare.user_id' => $userid, 'Ticketshare.ticket_num' => $ticknumb])->first();
					if ($tick12s['id']) {
						$output2['isShared'] = true;
					} else {
						$output2['isShared'] = false;
					}
					if ($val['usedby']) {
						$output2['usedBy'] = $val['usedby'];
					} else {
						$output2['usedBy'] = null;
					}
					if (!empty($val['name'])) {
						$output2['name'] = $val['name'];
					} else {
						$output2['name'] = false;
					}

					$output2['ticketId'] = $val['ticket_num'];
					$output2['qrCodeImageURL'] = SITE_URL . 'qrimages/temp/' . $val['qrcode'];
					array_push($output["tickets"], $output2);
				}
				array_push($response["myTickets"], $output);
			}

			foreach ($tick12 as $values) { //pr($values); die;
				$tick = $this->Ticket->find('all')->contain(['Event'])->where(['Ticket.id' => $values['tid']])->first();

				$output['eventId'] = $tick['event']['id'];
				$output['isPackage'] = ($values['package_id']) ? true : false;
				$output['rsvpDeadLine'] = ($values['event']['is_free'] == 'Y') ? $values['event']['request_rsvp'] : $values['event']['date_from'];
				$output['eventName'] = ucwords(strtolower($tick['event']['name']));
				$output['eventLocation'] = ucwords(strtolower($tick['event']['location']));
				$output['eventStartDateTime'] = date('D, d M Y & h:i A', strtotime($tick['event']['date_from']));
				$output['eventEndDateTime'] = date('D, d M Y & h:i A', strtotime($tick['event']['date_to']));
				$output['eventImageURL'] = SITE_URL . 'images/eventimages' . $tick['event']['feat_image'];
				$output['pricePerTicket'] = $tick['event']['amount'];
				$output['ticketType'] = ($tick['event']['is_free'] == 'Y') ? 'Invitation' : 'Received';
				$output['isFree'] = ($tick['event']['is_free'] == 'Y') ? true : false;
				$output['purchaseDate'] = date('D, d M Y & h:i A', strtotime($values['created']));

				$event_date_check = strtotime($tick['event']['date_to']);
				$current_date_check = strtotime(date('d M Y h:i'));

				if ($current_date_check > $event_date_check) {
					$output['eventExpired'] = true;
				} else {
					$output['eventExpired'] = false;
				}
				$output["tickets"] = array();

				$tick_detailsf = $this->Ticketdetail->find('all')->where(['Ticketdetail.tid' => $tick['id']])->toarray();

				//	$tick_detailsf = $this->Ticketshare->find('all')->where(['Ticketshare.share_mobile'=>$uernew['mobile']])->toarray();

				//pr($tick_detailsf);
				$output2 = array();

				foreach ($tick_detailsf as $val) {
					$ticknumb = ltrim($val['ticket_num'], "T");
					$tick12s = $this->Ticketshare->find('all')->where(['Ticketshare.share_mobile' => $mobile, 'Ticketshare.ticket_num' => $ticknumb])->first();
					if ($tick12s) {
						$output2['ticketId'] = $tick12s['ticket_num'];
						$output2['qrCodeImageURL'] = SITE_URL . 'qrimages/temp/' . $tick12s['qrcode'];
						array_push($output["tickets"], $output2);
					}
				}
				array_push($response["myTickets"], $output);

				//foreach($tick_detailsf as $val){
				//$output2['ticketId']=$val['ticket_num'];	
				//$output2['qrCodeImageURL']=SITE_URL.'qrimages/temp/'.$val['qrcode'];
				//array_push($output["tickets"], $output2);

				//}

				// $output2['ticketId']="T".$values['ticket_num'];	
				//$output2['qrCodeImageURL']=SITE_URL.'qrimages/temp/'.$values['qrcode'];
				//  array_push($output["tickets"], $output2);
				//array_push($response["myTickets"], $output);
			}
		} else {
			$response["success"] = false;
			$response["status"] = "No Tickets";
		}
		echo json_encode($response);
	}

	// for past tickets 
	public function pastTickets()
	{
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticket');
		$this->loadModel('Users');
		$this->loadModel('Ticketshare');
		$this->loadModel('Eventdetail');
		$this->autoRender = false;
		$cudate = date("Y-m-d H:i:s");
		$response = array();
		$userid = $this->request->data('userId');

		$uernew = $this->Users->find('all')->select(['email', 'mobile', 'id'])->where(['id' => $userid])->first();
		$mobile = (empty($uernew['mobile'])) ? $uernew['id'] : $uernew['mobile'];
		// $tick1 = $this->Ticket->find('all')->contain(['Event', 'Eventdetail'])->where(['Ticket.cust_id' => $userid])->order(['Ticket.id' => 'DESC'])->toarray();

		$tick1 = $this->Ticket->find('all')->contain(['Event', 'Eventdetail'])->where(['Ticket.mobile' => $mobile, 'Event.date_to <=' => $cudate])->order(['Event.date_from' => 'DESC'])->toarray();
		// pr($cudate);	
		// pr($tick1);exit;	


		$tick12 = $this->Ticketshare->find('all')->order(['Ticketshare.id' => 'DESC'])->where(['Ticketshare.share_mobile' => $mobile])->group('tid')->toarray();

		if ($tick1 || $tick12) {
			$output = array();
			$response["success"] = true;
			$response["status"] = null;
			$response["myTickets"] = array();


			foreach ($tick1 as $value) { //pr($value);die;
				$output['eventId'] = $value['event']['id'];
				// $output['isFree'] = ($value['event']['is_free']=='Y')?true:false;
				$output['rsvpDeadLine'] = date('Y-m-d H:i:s', strtotime(($value['event']['is_free'] == 'Y') ? $value['event']['request_rsvp'] : $value['event']['date_from']));
				$output['eventName'] = ucwords(strtolower($value['event']['name']));
				$output['eventLocation'] = ucwords(strtolower($value['event']['location']));
				$output['eventStartDateTime'] = date('D, d M Y & h:i A', strtotime($value['event']['date_from']));
				$output['eventEndDateTime'] = date('D, d M Y & h:i A', strtotime($value['event']['date_to']));
				$output['eventImageURL'] = SITE_URL . 'images/eventimages/' . $value['event']['feat_image'];
				$output['pricePerTicket'] = $value['event']['amount'];
				$output['purchaseDate'] = date('D, d M Y & h:i A', strtotime($value['created']));

				if ($value['event_admin'] == 0) {
					$output['ticketType'] = ($value['event']['is_free'] == 'Y') ? true : 'Purchased';
					$output['isFree'] = ($value['event']['is_free'] == 'Y') ? true : false;
					$output['ticketTypeName'] = $value['eventdetail']['title'];
				} else {
					$output['isFree'] = ($value['event']['is_free'] == 'Y') ? true : false;
					$output['ticketType'] = ($value['event']['is_free'] == 'Y') ? 'Invitation' : 'Complementary';
					$output['ticketTypeName'] = $value['eventdetail']['title'];
				}
				$event_date_check = strtotime($value['event']['date_to']);
				$current_date_check = strtotime('d M Y h:i');
				if ($current_date_check > $event_date_check) {
					$output['eventExpired'] = true;
				} else {
					$output['eventExpired'] = false;
				}

				$output["tickets"] = array();
				$tick_details = $this->Ticketdetail->find('all')->where(['Ticketdetail.tid' => $value['id']])->toarray();
				//pr($tick_details); die;
				$output2 = array();

				foreach ($tick_details as $val) {
					$ticknumb = ltrim($val['ticket_num'], "T");
					$tick12s = $this->Ticketshare->find('all')->where(['Ticketshare.user_id' => $userid, 'Ticketshare.ticket_num' => $ticknumb])->first();
					if ($tick12s['id']) {
						$output2['isShared'] = true;
					} else {
						$output2['isShared'] = false;
					}
					if ($val['usedby']) {
						$output2['usedBy'] = $val['usedby'];
					} else {
						$output2['usedBy'] = null;
					}
					if (!empty($val['name'])) {
						$output2['name'] = $val['name'];
					} else {
						$output2['name'] = false;
					}

					$output2['ticketId'] = $val['ticket_num'];
					$output2['qrCodeImageURL'] = SITE_URL . 'qrimages/temp/' . $val['qrcode'];
					array_push($output["tickets"], $output2);
				}
				array_push($response["myTickets"], $output);
			}

			foreach ($tick12 as $values) { //pr($values); die;
				$tick = $this->Ticket->find('all')->contain(['Event'])->where(['Ticket.id' => $values['tid']])->first();

				$output['eventId'] = $tick['event']['id'];
				// $output['is_free'] = ($tick['event']['is_free']=='Y')?true:false;
				$output['rsvpDeadLine'] = ($values['event']['is_free'] == 'Y') ? $values['event']['request_rsvp'] : $values['event']['date_from'];
				$output['eventId'] = $tick['event']['id'];
				$output['eventName'] = $tick['event']['name'];
				$output['eventLocation'] = $tick['event']['location'];
				$output['eventStartDateTime'] = date('D, d M Y & h:i A', strtotime($tick['event']['date_from']));
				$output['eventEndDateTime'] = date('D, d M Y & h:i A', strtotime($tick['event']['date_to']));
				$output['eventImageURL'] = SITE_URL . 'images/eventimages' . $tick['event']['feat_image'];
				$output['pricePerTicket'] = $tick['event']['amount'];
				$output['ticketType'] = ($tick['event']['is_free'] == 'Y') ? 'Invitation' : 'Received';
				$output['isFree'] = ($tick['event']['is_free'] == 'Y') ? true : false;
				$output['purchaseDate'] = date('D, d M Y & h:i A', strtotime($values['created']));

				$event_date_check = strtotime($tick['event']['date_to']);
				$current_date_check = strtotime(date('d M Y h:i'));

				if ($current_date_check > $event_date_check) {
					$output['eventExpired'] = true;
				} else {
					$output['eventExpired'] = false;
				}
				$output["tickets"] = array();

				$tick_detailsf = $this->Ticketdetail->find('all')->where(['Ticketdetail.tid' => $tick['id']])->toarray();
				$output2 = array();

				foreach ($tick_detailsf as $val) {
					$ticknumb = ltrim($val['ticket_num'], "T");
					$tick12s = $this->Ticketshare->find('all')->where(['Ticketshare.share_mobile' => $mobile, 'Ticketshare.ticket_num' => $ticknumb])->first();
					if ($tick12s) {
						$output2['ticketId'] = $tick12s['ticket_num'];
						$output2['qrCodeImageURL'] = SITE_URL . 'qrimages/temp/' . $tick12s['qrcode'];
						array_push($output["tickets"], $output2);
					}
				}
				array_push($response["myTickets"], $output);

				//foreach($tick_detailsf as $val){
				//$output2['ticketId']=$val['ticket_num'];	
				//$output2['qrCodeImageURL']=SITE_URL.'qrimages/temp/'.$val['qrcode'];
				//array_push($output["tickets"], $output2);

				//}

				// $output2['ticketId']="T".$values['ticket_num'];	
				//$output2['qrCodeImageURL']=SITE_URL.'qrimages/temp/'.$values['qrcode'];
				//  array_push($output["tickets"], $output2);
				//array_push($response["myTickets"], $output);
			}
		} else {
			$response["success"] = false;
			$response["status"] = "No Tickets";
		}
		echo json_encode($response);
	}

	public function getTicketName()
	{
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticketshare');
		$this->loadModel('Event');
		$this->loadModel('Users');

		$this->autoRender = false;
		$cudate = date("Y-m-d H:i:s");
		$response = array();
		$output = array();
		$response["myTickets"] = array();
		$ticket_num = $_REQUEST['ticketId'];
		$eventId = $_REQUEST['eventId'];

		$event = $this->Event->get($eventId);
		// $tick_details = $this->Ticketdetail->find('all')->where(['Ticketdetail.ticket_num' => $ticket_num])->first();

		$tick_details = $this->Ticketdetail->find('all')->contain(['Ticket' => 'Event', 'Users'])->where(['Ticketdetail.ticket_num' => $ticket_num])->first();
		// pr($tick_details);exit;
		// pr($tick_details['ticket']['event']['request_rsvp']);exit;

		$tick12s = $this->Ticketshare->find('all')->where(['Ticketshare.user_id' => $tick_details['user_id'], 'Ticketshare.ticket_num' => $ticket_num])->first();

		// $start = strtotime(date('d-m-Y h:i:s', strtotime($cudate)));
		// $end = strtotime(date('d-m-Y h:i:s', strtotime($event['request_rsvp'])));
		// $diff = ($end - $start);
		// pr(date('d-m-Y h:i:s',strtotime($diff)));exit;

		if ($tick_details) {
			$response["success"] = true;
			$response["status"] = null;
			$output['isFree'] = ($event['is_free'] == 'Y') ? true : false;
			if ($tick12s['id']) {
				$output["isShared"] = true;
			} else {
				$output["isShared"] = false;
			}
			if ($tick_details['usedby']) {
				$output['usedBy'] = $tick_details['usedby'];
			} else {
				$output['usedBy'] = null;
			}
			if (!empty($tick_details['name'])) {
				$output["name"] = $tick_details['name'];
			} else {
				$output["name"] = false;
			}
			$output["qrCodeImageURL"] = SITE_URL . 'qrimages/temp/' . $tick_details['qrcode'];
			$output["ticketId"] = $ticket_num;
			$output["isRSVP"] = ($tick_details['is_rsvp'] == 'N') ? 'Y' : 'N';
			$output['rsvpDeadline'] = ($event['is_free'] == 'Y') ? date('D, d M Y & h:i A', strtotime($event['request_rsvp'])) : false;
			$isProfileVerify = (empty($tick_details['user']['profile_image'])) ? false : true;
			$output["isProfileVerify"] = $isProfileVerify;
		} else {
			$response["success"] = false;
			$response["status"] = 'Ticket name not found';
			$response["message"] = 'Ticket name not found';
		}
		array_push($response["myTickets"], $output);
		echo json_encode($response);
		die;
	}

	public function updateTicketName()
	{
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticket');
		$this->loadModel('Users');
		$this->autoRender = false;
		$response = array();
		$ticketId = $_REQUEST['ticketId'];
		$name = trim(ucwords(strtolower($_REQUEST['name'])));
		$output["tickets"] = array();

		$tick_details = $this->Ticketdetail->find('all')->where(['Ticketdetail.ticket_num' => $ticketId])->first();
		if (!empty($tick_details)) {
			$response["success"] = true;
			$response["message"] = $name . " has been update on Ticket";
			$ticketbook['name'] = $name;
			$updatename = $this->Ticketdetail->patchEntity($tick_details, $ticketbook);
			$this->Ticketdetail->save($updatename);
		} else {
			$response["success"] = false;
			$response["message"] = "Invalid ticket id !";
		}
		echo json_encode($response);
		die;
	}

	public function enablersvp()
	{
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticket');
		$this->loadModel('Users');
		$this->loadModel('Event');
		$this->autoRender = false;
		$response = array();
		$ticketId = $_REQUEST['ticketId'];
		$tick_details = $this->Ticketdetail->find('all')->contain(['Ticket' => 'Event'])->where(['Ticketdetail.ticket_num' => $ticketId])->first();
		$currentTime = date("Y-m-d H:i:s");
		$request_rsvp = date('Y-m-d H:i:s', strtotime($tick_details['ticket']['event']['request_rsvp']));


		if ($currentTime >= $request_rsvp) {
			$response["success"] = false;
			$response["message"] = "The time has elapsed to Accept the RSVP Invitation";
			echo json_encode($response);
			die;
		}

		if (!empty($tick_details)) {
			$status = ($tick_details['is_rsvp'] == 'Y') ? 'N' : 'Y';
			$ticketbook['is_rsvp'] = $status;
			$response["success"] = true;
			$response["isRSVP"] = $status;
			$response["message"] = ($tick_details['is_rsvp'] == 'N') ? 'RSVP has been Accepted Successfully' : 'RSVP has been Declined Successfully';
			$updatestatus = $this->Ticketdetail->patchEntity($tick_details, $ticketbook);
			$this->Ticketdetail->save($updatestatus);
		} else {
			$response["success"] = false;
			$response["message"] = "Invalid ticket id !";
		}
		echo json_encode($response);
		die;
	}

	public function viewticketdetail()
	{
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticket');
		$this->autoRender = false;
		$response = array();

		$event = array();
		$ticketid = $_REQUEST['ticketid'];
		$userid = $_REQUEST['userid'];
		$tick = $this->Ticketdetail->find('all')->where(['Ticketdetail.tid' => $ticketid])->contain(['Ticket' => ['Event']])->toarray();
		if (count($tick) > 0) {
			$response["success"] = 1;
			$response["output"] = array();
			foreach ($tick as $value) {
				$event['id'] = $value['id'];
				$event['ticket_num'] = $value['ticket_num'];
				$event['date'] = strftime('%d %b', strtotime($value['created']));
				$event['day'] = strftime('%A', strtotime($value['created']));
				$event['eventname'] = $value['ticket']['event']['name'];
				$event['price'] = $value['ticket']['event']['amount'];
				$event['location'] = $value['ticket']['event']['location'];
				$event['time'] = date("g:i A", strtotime($value['ticket']['event']['date_from']));
				$event['datefrom'] = strftime('%d %b', strtotime($value['ticket']['event']['date_from'])) . " " . $event['time'];
				$event['dateto'] = strftime('%d %b', strtotime($value['ticket']['event']['date_to'])) . " " . $event['time'];
				$event['dayfrom'] = strftime('%A', strtotime($value['ticket']['event']['date_from']));
				$event['dayto'] = strftime('%A', strtotime($value['ticket']['event']['date_to']));
				$event['barcode'] = $value['ticket']['event']['qrcode'];
				array_push($response["output"], $event);
			}
		} else {
			$response["success"] = 0;
		}
		echo json_encode($response);
	}

	// Get all Country list 
	public function getCountry()
	{
		$this->loadModel('Countries');
		$response = array();
		$output = array();
		$response['country'] = array();
		$response["success"] = true;
		$response["status"] = "List all Countries !";
		$country = $this->Countries->find('all')->where(['status' => 'Y'])->order(['CountryName' => 'ASC'])->toArray();
		foreach ($country as $key => $value) {
			$output['id'] =  $value['id'];
			$output['countryName'] = $value['CountryName'] . ' (' . $value['words'] . ')';
			array_push($response['country'], $output);
		}
		echo json_encode($response);
		die;
	}

	public function getCommitteenotifications()
	{
		$user_id = $_POST['userId'];
		$status = $_POST['status'];
		$this->loadModel('Cart');
		$this->loadModel('Ticket');

		//pending
		if ($status == "pending") {

			$cart_data_comitee_pending = $this->Cart->find('all')->contain(['Event' => ['Currency'], 'Eventdetail', 'Users'])->where(['Cart.commitee_user_id' => $user_id, 'Cart.ticket_type' => 'committesale', 'Cart.status' => 'N'])->order(['Cart.user_id' => 'ASC'])->toarray();

			foreach ($cart_data_comitee_pending as $value_pending) {
				$comittee['id'] = 	$value_pending['id'];

				if ($value_pending['user']['profile_image']) {
					$comittee['image'] =  IMAGE_PATH . 'Usersprofile/' . $value_pending['user']['profile_image'];
				} else {
					$comittee['image'] = IMAGE_PATH . 'Usersprofile/noimage.jpg';
				}
				$comittee['name'] = 	$value_pending['user']['name'] . ' ' . $value_pending['user']['lname'];
				$comittee['eventname'] = ucwords(strtolower($value_pending['event']['name']));
				$comittee['eventtitle'] = 	$value_pending['eventdetail']['title'];
				$comittee['description'] = 	$value_pending['description'];
				$comittee['currency'] = 	$value_pending['event']['currency']['Currency_symbol'];
				$comittee['price'] = 	sprintf('%0.2f', $value_pending['eventdetail']['price']) . ' ' . $value_pending['event']['currency']['Currency'];
				$cart_data_comitee_pending_data[] = $comittee;
			}


			if ($cart_data_comitee_pending_data) {
				$response['success'] = true;
				$response['output'] = $cart_data_comitee_pending_data;
				echo json_encode($response);
				die;
			} else {
				$response['success'] = false;
				$response['message'] = "No Data";
				echo json_encode($response);
				die;
			}
		}

		//approved
		if ($status == "approved") {
			$cart_data_comitee_approved =  $this->Cart->find('all')->contain(['Event' => ['Currency'], 'Eventdetail', 'Users'])->where(['Cart.commitee_user_id' => $user_id, 'Cart.ticket_type' => 'committesale', 'Cart.status' => 'Y'])->order(['Cart.user_id' => 'ASC'])->toarray();

			foreach ($cart_data_comitee_approved as $value_approved) {
				$comittee['id'] = 	$value_approved['id'];

				if ($value_approved['user']['profile_image']) {
					$comittee['image'] =  IMAGE_PATH . 'Usersprofile/' . $value_approved['user']['profile_image'];
				} else {
					$comittee['image'] = IMAGE_PATH . 'Usersprofile/noimage.jpg';
				}
				$comittee['name'] = 	$value_approved['user']['name'] . ' ' . $value_approved['user']['lname'];
				$comittee['eventname'] = ucwords(strtolower($value_approved['event']['name']));
				$comittee['eventtitle'] = 	$value_approved['eventdetail']['title'];
				$comittee['description'] = 	$value_approved['description'];
				$comittee['currency'] = 	$value_approved['event']['currency']['Currency_symbol'];
				$comittee['price'] = 	sprintf('%0.2f', $value_approved['eventdetail']['price']) . ' ' . $value_approved['event']['currency']['Currency'];

				$cart_data_comitee_approved_data[] = $comittee;
			}

			if ($cart_data_comitee_approved_data) {
				$response['success'] = true;
				$response['output'] = $cart_data_comitee_approved_data;
				echo json_encode($response);
				die;
			} else {
				$response['success'] = false;
				$response['message'] = "No Data";
				echo json_encode($response);
				die;
			}
		}

		//Ignored
		if ($status == "ignored") {
			$cart_data_comitee_ignored =  $this->Cart->find('all')->contain(['Event' => ['Currency'], 'Eventdetail', 'Users'])->where(['Cart.commitee_user_id' => $user_id, 'Cart.ticket_type' => 'committesale', 'Cart.status' => 'I'])->order(['Cart.user_id' => 'ASC'])->toarray();

			foreach ($cart_data_comitee_ignored as $value_ignored) {
				$comittee['id'] = 	$value_ignored['id'];

				if ($value_ignored['user']['profile_image']) {
					$comittee['image'] =  IMAGE_PATH . 'Usersprofile/' . $value_ignored['user']['profile_image'];
				} else {
					$comittee['image'] = IMAGE_PATH . 'Usersprofile/noimage.jpg';
				}
				$comittee['name'] = 	$value_ignored['user']['name'] . ' ' . $value_ignored['user']['lname'];
				$comittee['eventname'] = ucwords(strtolower($value_ignored['event']['name']));
				$comittee['eventtitle'] = 	$value_ignored['eventdetail']['title'];
				$comittee['description'] = 	$value_ignored['description'];
				$comittee['currency'] = 	$value_ignored['event']['currency']['Currency_symbol'];
				$comittee['price'] = 	sprintf('%0.2f', $value_ignored['eventdetail']['price']) . ' ' . $value_ignored['event']['currency']['Currency'];
				$cart_data_comitee_ignored_data[] = $comittee;
			}


			if ($cart_data_comitee_ignored_data) {
				$response['success'] = true;
				$response['output'] = $cart_data_comitee_ignored_data;
				echo json_encode($response);
				die;
			} else {
				$response['success'] = false;
				$response['message'] = "No Data";
				echo json_encode($response);
				die;
			}
		}

		//}

		//Completed
		if ($status == "completed") {
			$cart_data_comitee_completed = $this->Ticket->find('all')->contain(['Event' => ['Currency'], 'Users', 'Eventdetail', 'Orders'])->where(['Ticket.committee_user_id' => $user_id, 'Ticket.status' => 'Y'])->order(['Ticket.id' => 'ASC'])->toarray();

			foreach ($cart_data_comitee_completed as $value_completed) {
				$comittee['id'] = 	$value_completed['id'];

				if ($value_completed['user']['profile_image']) {
					$comittee['image'] =  IMAGE_PATH . 'Usersprofile/' . $value_completed['user']['profile_image'];
				} else {
					$comittee['image'] = IMAGE_PATH . 'Usersprofile/noimage.jpg';
				}
				$comittee['name'] = 	$value_completed['user']['name'] . ' ' . $value_completed['user']['lname'];
				$comittee['eventname'] = ucwords(strtolower($value_completed['event']['name']));
				$comittee['eventtitle'] = 	$value_completed['eventdetail']['title'];

				if ($value_completed['user_desc']) {

					$comittee['description'] = 	$value_completed['user_desc'];
				} else {

					$comittee['description'] = null;
				}
				$comittee['currency'] = 	$value_completed['event']['currency']['Currency_symbol'];
				$comittee['price'] = 	sprintf('%0.2f', $value_completed['eventdetail']['price']) . ' ' . $value_completed['event']['currency']['Currency'];
				$cart_data_comitee_completed_data[] = $comittee;
			}


			if ($cart_data_comitee_completed_data) {
				$response['success'] = true;
				$response['output'] = $cart_data_comitee_completed_data;
				echo json_encode($response);
				die;
			} else {
				$response['success'] = false;
				$response['message'] = "No Data";
				echo json_encode($response);
				die;
			}
		}
	}

	public function getTicketDetailsByEventId() {
		$this->loadModel('Ticketdetail');
		$this->loadModel('Ticket');
		
		// pr($this->request->data);exit;
		$ticketdetalss = $this->Ticket->find('all')->where(['Ticket.event_id' => $this->request->data['eventId']])->contain('Ticketdetail')->toarray();

		$response['ticketdetails'] = array();
		foreach ($ticketdetalss as $tcktdetail){
			$ticketdetails[] =  $tcktdetail['ticketdetail'];
			
		}
		foreach($ticketdetails as $key => $ticket){
			$output['id'] =  $ticket[0]['id'];
			$output['tid'] = $ticket[0]['tid'];
			$output['ticket_num'] = $ticket[0]['ticket_num'];
			$output['user_id'] = $ticket[0]['user_id'];
			$output['package_id'] = $ticket[0]['package_id'];
			$output['name'] = $ticket[0]['name'];
			$output['usedby'] = $ticket[0]['usedby'];
			$output['created'] = $ticket[0]['created'];
			$output['usedate'] = $ticket[0]['usedate'];
			$output['qrcode'] = $ticket[0]['qrcode'];
			$output['scanner_id'] = $ticket[0]['scanner_id'];
			$output['status'] = $ticket[0]['status'];
			$output['is_rsvp'] = $ticket[0]['is_rsvp'];
			$output['position'] = $ticket[0]['position'];
			$output['divisions'] = $ticket[0]['divisions'];
			$output['timeslot'] = $ticket[0]['timeslot'];
			$output['choosedate'] = $ticket[0]['choosedate'];
		
			
			array_push($response['ticketdetails'], $output);
		}

		echo json_encode($response);
		die;
		

	}

	public function ticketDetailUpdateStatus(){
		$this->loadModel('Ticketdetail');
		$id = $this->request->data['id'];
		$idss= $this->Ticketdetail->get($id);
		$status=  $this->request->data['status'];
		$stat['status']=$status;
		$updatestatus= $this->Ticketdetail->patchEntity($idss,$stat);
		$this->Ticketdetail->save($updatestatus);
		die;
		

	}


	public function committeeissuecomplimentary()
	{

		$this->loadModel('Orders');
		$this->loadModel('Users');
		$this->loadModel('Event');
		$this->loadModel('Eventdetail');
		$this->loadModel('Cart');
		$this->loadModel('Payment');
		$this->loadModel('Ticket');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Committeeassignticket');
		$this->loadModel('Currency');
		$this->loadModel('Templates');
		$id  = $_POST['id'];

		$cart_data_comitee = $this->Cart->find('all')->where(['Cart.id' => $id])->first();
		if ($cart_data_comitee) {
			$user_id = $cart_data_comitee['commitee_user_id'];

			$eventdetails_all_ticket = $this->Eventdetail->find('all')->where(['Eventdetail.eventid' => $cart_data_comitee['event_id'], 'Eventdetail.type' => 'comps'])->first();

			$checkticket = $this->Committeeassignticket->find('all')->where(['Committeeassignticket.event_id' => $cart_data_comitee['event_id'], 'Committeeassignticket.user_id' => $user_id, 'Committeeassignticket.ticket_id' => $eventdetails_all_ticket['id'], 'Committeeassignticket.status' => 'Y'])->first();
			//pr($checkticket); die; 
			if ($checkticket['count'] <= 0) {

				$response['success'] = false;
				$response['message'] = "Sorry ! You have 0 tickets assigned";
				echo json_encode($response);
				die;
			}

			//sold complimentary and open sale ticket start
			$ticket_total_sold =  $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $cart_data_comitee['event_id'], 'Ticket.event_ticket_id' => $eventdetails_all_ticket['id'], 'Ticket.committee_user_id' => $user_id])->first();

			$total_sold_ticket   =   $ticket_total_sold['ticketsold'] + 1;
			if ($total_sold_ticket  <= $checkticket['count']) {
			} else {
				$response['success'] = false;
				$response['message'] = "Sorry ! You have assigned all tickets";
				echo json_encode($response);
				die;
			}

			// $response['success'] = false;
			// $response['message'] = "Sorfdfsdafdtickets";
			// echo json_encode($response); die;

			$this->loadModel('Orders');
			$this->loadModel('Users');
			$eventdetails = $this->Event->get($cart_data_comitee['event_id']);
			$user_detail = $this->Users->get($cart_data_comitee['user_id']);
			$currenny = $this->Currency->get($eventdetails['payment_currency']);
			$orderdata['user_id'] = $cart_data_comitee['user_id'];
			$orderdata['total_amount'] = 0;
			$orderdata['paymenttype'] = "Comps";
			$insertdata = $this->Orders->patchEntity($this->Orders->newEntity(), $orderdata);

			if ($saveorders = $this->Orders->save($insertdata)) {

				// $fn['user_id'] = $cart_data_comitee['user_id'];
				// $fn['event_id'] =  $cart_data_comitee['event_id'];
				// $fn['mpesa'] = null;
				// $fn['amount'] =  0;
				// $payment = $this->Payment->patchEntity($this->Payment->newEntity(), $fn);
				// $this->Payment->save($payment);

				$ticketbook['order_id'] = $saveorders->id;
				$ticketbook['event_id'] =  $cart_data_comitee['event_id'];
				$ticketbook['event_ticket_id'] = $eventdetails_all_ticket['id'];
				$ticketbook['cust_id'] = $cart_data_comitee['user_id'];
				$ticketbook['ticket_buy'] = 1;
				$ticketbook['amount'] = 0;
				$ticketbook['mobile'] =  $user_detail['mobile'];
				$ticketbook['committee_user_id'] = $cart_data_comitee['commitee_user_id'];
				$ticketbook['user_desc'] = $cart_data_comitee['description'];
				$insertticketbook = $this->Ticket->patchEntity($this->Ticket->newEntity(), $ticketbook);
				$lastinsetid = $this->Ticket->save($insertticketbook);

				$ticketdetaildata['tid'] = $lastinsetid['id'];
				$ticketdetaildata['user_id'] = $cart_data_comitee['user_id'];
				$ticketdetail = $this->Ticketdetail->patchEntity($this->Ticketdetail->newEntity(), $ticketdetaildata);
				$ticketdetailvvv = $this->Ticketdetail->save($ticketdetail);

				$Packff = $this->Ticketdetail->get($ticketdetailvvv['id']);
				$Packff->ticket_num = 'T' . $ticketdetailvvv['id'];
				$ticketdetail = $this->Ticketdetail->save($Packff);

				$ticketqrimages = $this->qrcodepro($cart_data_comitee['user_id'], $ticketdetail['ticket_num'], $eventdetails['event_org_id']);
				$Pack = $this->Ticketdetail->get($ticketdetail['id']);
				$Pack->qrcode = $ticketqrimages;
				$this->Ticketdetail->save($Pack);
			}

			// send email to admin and event organiser 
			$eventname = ucwords(strtolower($eventdetails['name']));
			$requestername = $user_detail['name'] . ' ' . $user_detail['lname'];
			$url = SITE_URL . 'tickets/myticket';
			$site_url = SITE_URL;
			$currenny_sign = $currenny['Currency'];
			$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 29])->first();
			$from = $emailtemplate['fromemail'];
			$to = $user_detail['email'];
			// $cc = $from;
			$subject = $emailtemplate['subject'] . ': ' . $eventname;
			$formats = $emailtemplate['description'];

			$message1 = str_replace(array('{EventName}', '{RequesterName}', '{URL}', '{SITE_URL}', '{CurrencySign}'), array($eventname, $requestername, $url, $site_url, $currenny_sign), $formats);
			$message = stripslashes($message1);
			$message = '<!DOCTYPE HTML>
        <html>                
        <head>
            <meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
            <title>Untitled Document</title>
            <style>
                p {
                    margin: 9px 0px;
                }
            </style>                
        </head>                
        <body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: <' . $from . '>' . "\r\n";
			if ($eventdetails['id'] != 49) {
				$mail = $this->Email->send($to, $subject, $message);
				// pr($mail);exit;
				// send mail complete 

				// send watsappmessage start 
				$message = "*Eboxtickets: Complimentary Ticket Issued*%0AHi $requestername,%0A%0AYou Complimentary Ticket has been issued.This ticket was FREE.%0ANo Payment details were required.%0A%0ARegards,%0AEboxtickets.com";
				$numwithcode = $user_detail['mobile'];
				$this->whatsappmsg($numwithcode, $message);
				// send watsappmessage start 
			}

			$this->Cart->deleteAll(['Cart.id' => $cart_data_comitee['id']]);
			$response['success'] = true;
			$response['message'] = 'Ticket assigned successfully';
			echo json_encode($response);
			die;
		} else {
			$response['success'] = false;
			$response['message'] = "No Data";
			echo json_encode($response);
			die;
		}
	}

	public function committeecashpayment()
	{
		$this->loadModel('Orders');
		$this->loadModel('Users');
		$this->loadModel('Event');
		$this->loadModel('Eventdetail');
		$this->loadModel('Cart');
		$this->loadModel('Payment');
		$this->loadModel('Ticket');
		$this->loadModel('Currency');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Templates');
		$this->loadModel('Committeeassignticket');
		$id  = $_POST['id'];
		$cart_data_comitee = $this->Cart->find('all')->where(['Cart.id' => $id])->first();

		if ($cart_data_comitee) {

			$eventdetails = $this->Event->get($cart_data_comitee['event_id']);
			$currenny = $this->Currency->get($eventdetails['payment_currency']);
			$eventdetails_all = $this->Eventdetail->get($cart_data_comitee['ticket_id']);
			$user_detail = $this->Users->get($cart_data_comitee['user_id']);

			$user_id = $cart_data_comitee['commitee_user_id'];

			$eventdetails_all_ticket = $this->Eventdetail->find('all')->where(['Eventdetail.eventid' => $cart_data_comitee['event_id'], 'Eventdetail.id' => $cart_data_comitee['ticket_id']])->first();

			$checkticket = $this->Committeeassignticket->find('all')->where(['Committeeassignticket.event_id' => $cart_data_comitee['event_id'], 'Committeeassignticket.user_id' => $user_id, 'Committeeassignticket.ticket_id' => $eventdetails_all_ticket['id'], 'Committeeassignticket.status' => 'Y'])->first();
			//pr($checkticket); die; 
			if ($checkticket['count'] <= 0) {

				$response['success'] = false;
				$response['message'] = "Sorry ! You have 0 tickets assigned";
				echo json_encode($response);
				die;
			}


			//sold complimentary and open sale ticket start
			$ticket_total_sold =  $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $cart_data_comitee['event_id'], 'Ticket.event_ticket_id' => $eventdetails_all_ticket['id'], 'Ticket.committee_user_id' => $user_id])->first();

			$total_sold_ticket   =   $ticket_total_sold['ticketsold'] + 1;
			if ($total_sold_ticket  <= $checkticket['count']) {
			} else {
				$response['success'] = false;
				$response['message'] = "Sorry ! You have assigned all tickets";
				echo json_encode($response);
				die;
			}

			$orderdata['user_id'] = $cart_data_comitee['user_id'];
			$orderdata['total_amount'] = $eventdetails_all['price'];
			$orderdata['paymenttype'] = "Cash";

			// pr($eventdetails_all); die;
			$insertdata = $this->Orders->patchEntity($this->Orders->newEntity(), $orderdata);

			if ($saveorders = $this->Orders->save($insertdata)) {

				$fn['user_id'] = $cart_data_comitee['user_id'];
				$fn['event_id'] =  $cart_data_comitee['event_id'];
				$fn['mpesa'] = null;
				$fn['amount'] =  $eventdetails_all['price'];
				$payment = $this->Payment->patchEntity($this->Payment->newEntity(), $fn);
				$this->Payment->save($payment);

				$ticketbook['order_id'] = $saveorders->id;
				$ticketbook['event_id'] =  $cart_data_comitee['event_id'];
				$ticketbook['event_ticket_id'] = $cart_data_comitee['ticket_id'];
				$ticketbook['cust_id'] = $cart_data_comitee['user_id'];
				$ticketbook['ticket_buy'] = 1;
				$ticketbook['amount'] = $eventdetails_all['price'];
				$ticketbook['mobile'] =  $user_detail['mobile'];
				$ticketbook['committee_user_id'] = $cart_data_comitee['commitee_user_id'];
				$ticketbook['user_desc'] = $cart_data_comitee['description'];
				$insertticketbook = $this->Ticket->patchEntity($this->Ticket->newEntity(), $ticketbook);
				$lastinsetid = $this->Ticket->save($insertticketbook);

				$ticketdetaildata['tid'] = $lastinsetid['id'];
				$ticketdetaildata['user_id'] = $cart_data_comitee['user_id'];
				$ticketdetail = $this->Ticketdetail->patchEntity($this->Ticketdetail->newEntity(), $ticketdetaildata);
				$ticketdetailvvv = $this->Ticketdetail->save($ticketdetail);

				$Packff = $this->Ticketdetail->get($ticketdetailvvv['id']);
				$Packff->ticket_num = 'T' . $ticketdetailvvv['id'];
				$ticketdetail = $this->Ticketdetail->save($Packff);

				$ticketqrimages = $this->qrcodepro($cart_data_comitee['user_id'], $ticketdetail['ticket_num'], $eventdetails['event_org_id']);
				$Pack = $this->Ticketdetail->get($ticketdetail['id']);
				$Pack->qrcode = $ticketqrimages;
				$this->Ticketdetail->save($Pack);
			}

			// send email to admin and event organiser 
			$eventname = ucwords(strtolower($eventdetails['name']));
			$requestername = $user_detail['name'] . ' ' . $user_detail['lname'];
			$url = SITE_URL . 'tickets/myticket';
			$site_url = SITE_URL;
			$ticket_name = $eventdetails_all['title'];
			$paymnet_type = 'Cash';
			$total_amount = sprintf('%.2f', $eventdetails_all['price']) . ' ' . $currenny['Currency'];
			$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 30])->first();
			$from = $emailtemplate['fromemail'];
			$to = $user_detail['email'];
			// $cc = $from;
			$subject = $emailtemplate['subject'] . ': ' . $eventname;
			$formats = $emailtemplate['description'];

			$ordersummary .= '<tr>
			<td width="45%">' . $eventname . ' (' . $ticket_name . ')</td>
			<td width="10%" align="center">:</td>
			<td width="45%">' . $TotalAmount . '</td>
			</tr>';

			$message1 = str_replace(array('{EventName}', '{RequesterName}', '{URL}', '{SITE_URL}', '{TicketName}', '{PaymentType}', '{TotalAmount}', '{OrderSummary}'), array($eventname, $requestername, $url, $site_url, $ticket_name, $paymnet_type, $total_amount, $ordersummary), $formats);

			$message = stripslashes($message1);
			$message = '<!DOCTYPE HTML>
			<html>                
			<head>
				<meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
				<title>Untitled Document</title>
				<style>
					p {
						margin: 9px 0px;
					}
				</style>                
			</head>                
			<body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: <' . $from . '>' . "\r\n";
			if ($eventdetails['id'] != 49) {
				$mail = $this->Email->send($to, $subject, $message);
				// send mail complete 

				// send watsappmessage start 
				$message = "*Eboxtickets: Payment Complete*%0AHi $requestername,%0A%0AYour payment was received for *$ticket_name* type ticket.%0A%0ARegards,%0AEboxtickets.com";
				$numwithcode = $user_detail['mobile'];
				$this->whatsappmsg($numwithcode, $message);
				// send watsappmessage start 
			}
			$this->Cart->deleteAll(['Cart.id' => $cart_data_comitee['id']]);
			//$this->Flash->success(__('Ticket assigned successfully'));
			//return $this->redirect(['action' => 'approved']);

			$response['success'] = true;
			$response['message'] = 'Ticket assigned successfully';
			echo json_encode($response);
			die;
		} else {
			$response['success'] = false;
			$response['message'] = "No Data";
			echo json_encode($response);
			die;
		}
	}

	public function committeeignored()
	{
		$this->loadModel('Cart');
		$this->loadModel('Templates');

		$cart_id = $_POST['id'];
		$user_id = $_POST['userId'];
		$cart_data_comitee_data = $this->Cart->find('all')->contain(['Users', 'Eventdetail', 'Event' => ['Currency']])->where(['Cart.id' => $cart_id])->first();
		$event_id  = $cart_data_comitee_data['event_id'];
		$ticket_id  = $cart_data_comitee_data['ticket_id'];


		$cart_data_comitee = $this->Cart->find('all')->where(['Cart.event_id' => $event_id, 'Cart.commitee_user_id' => $user_id, 'Cart.ticket_type' => 'committesale', 'Cart.status' => 'N', 'Cart.ticket_id' => $ticket_id])->first();

		// pr($cart_data_comitee);exit;
		if ($cart_data_comitee == '') {
			$response['success'] = false;
			$response['message'] = "No Tickets";
			echo json_encode($response);
			die;
		}

		// send email to admin and event organiser 
		$eventname = ucwords(strtolower($cart_data_comitee_data['event']['name']));
		$requestername = $cart_data_comitee_data['user']['name'] . ' ' . $cart_data_comitee_data['user']['lname'];
		$site_url = SITE_URL;
		$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 28])->first();
		$from = $emailtemplate['fromemail'];
		$to = $cart_data_comitee_data['user']['email'];
		$subject = $emailtemplate['subject'] . ': ' . $eventname;
		$formats = $emailtemplate['description'];

		$message1 = str_replace(array('{EventName}', '{RequesterName}', '{SITE_URL}'), array($eventname, $requestername, $site_url), $formats);
		$message = stripslashes($message1);
		$message = '<!DOCTYPE HTML>
             <html>			
             <head>
                 <meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
                 <title>Untitled Document</title>
                 <style>
                     p {
                         margin: 9px 0px;
                         line-height: 24px;
                     }
                 </style>			
             </head>			
             <body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
		$headers = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: <' . $from . '>' . "\r\n";
		$mail = $this->Email->send($to, $subject, $message);
		// send mail complete 

		// send watsappmessage start 
		$message = "*Eboxtickets: Request Rejected*%0AHi $requestername,%0A%0AYour request to attend *" . $eventname . "* was Rejected from committee member.%0A%0ARegards,%0AEboxtickets.com";

		$numwithcode = $cart_data_comitee_data['user']['mobile'];
		$this->whatsappmsg($numwithcode, $message);
		// send watsappmessage start 

		$cart_data_comitee_data->status = 'I';
		$this->Cart->save($cart_data_comitee_data);
		$response['success'] = true;
		$response['message'] = "Ticket has been ignored";
		echo json_encode($response);
		die;
	}

	public function committeeapproved()
	{
		$this->loadModel('Cart');
		$this->loadModel('Templates');
		$this->loadModel('Committeeassignticket');
		$this->loadModel('Eventdetail');
		$this->loadModel('Ticket');
		//$this->loadModel('Eventdetail');
		$id = $_POST['id'];


		$cart_data_comitee = $this->Cart->find('all')->where(['Cart.id' => $id])->first();

		if ($cart_data_comitee) {

			$user_id = $cart_data_comitee['commitee_user_id'];

			$eventdetails_all_ticket = $this->Eventdetail->find('all')->where(['Eventdetail.eventid' => $cart_data_comitee['event_id'], 'Eventdetail.id' => $cart_data_comitee['ticket_id']])->first();

			$checkticket = $this->Committeeassignticket->find('all')->where(['Committeeassignticket.event_id' => $cart_data_comitee['event_id'], 'Committeeassignticket.user_id' => $user_id, 'Committeeassignticket.ticket_id' => $eventdetails_all_ticket['id'], 'Committeeassignticket.status' => 'Y'])->first();
			//pr($checkticket); die; 
			if ($checkticket['count'] <= 0) {

				$response['success'] = false;
				$response['message'] = "Sorry ! You have 0 tickets assigned";
				echo json_encode($response);
				die;
			}


			//sold complimentary and open sale ticket start
			$ticket_total_sold =  $this->Ticket->find('all')->Select(['ticketsold' => 'SUM(ticket_buy)'])->where(['Ticket.event_id' => $cart_data_comitee['event_id'], 'Ticket.event_ticket_id' => $eventdetails_all_ticket['id'], 'Ticket.committee_user_id' => $user_id])->first();

			$total_sold_ticket   =   $ticket_total_sold['ticketsold'] + 1;
			if ($total_sold_ticket  <= $checkticket['count']) {
			} else {
				$response['success'] = false;
				$response['message'] = "Sorry ! You have assigned all tickets";
				echo json_encode($response);
				die;
			}



			$findcartdata = $this->Cart->find('all')->contain(['Users', 'Eventdetail', 'Event' => ['Currency']])->where(['Cart.id' => $id])->first();

			// pr($findcartdata);exit;                
			$cart_data_comitee_approve = $this->Cart->find('all')->where(['Cart.id' => $id])->first();
			$cart_data_comitee_approve->status = 'Y';
			$this->Cart->save($cart_data_comitee_approve);

			// send email to admin and event organiser 
			$eventname = ucwords(strtolower($findcartdata['event']['name']));
			$requestername = $findcartdata['user']['name'] . ' ' . $findcartdata['user']['lname'];
			$url = SITE_URL . 'cart';
			$site_url = SITE_URL;
			$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 27])->first();
			$from = $emailtemplate['fromemail'];
			$to = $findcartdata['user']['email'];
			// $cc = $from;
			$subject = $emailtemplate['subject'] . ': ' . $eventname;
			$formats = $emailtemplate['description'];

			$message1 = str_replace(array('{EventName}', '{RequesterName}', '{URL}', '{SITE_URL}'), array($eventname, $requestername, $url, $site_url), $formats);
			$message = stripslashes($message1);
			$message = '<!DOCTYPE HTML>
				<html>			
				<head>
					<meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
					<title>Untitled Document</title>
					<style>
						p {
							margin: 9px 0px;
							line-height: 24px;
						}
					</style>			
				</head>			
				<body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: <' . $from . '>' . "\r\n";
			$mail = $this->Email->send($to, $subject, $message);
			// send mail complete 

			// send watsappmessage start 
			$message = "*Eboxtickets: Request Approved*%0AHi $requestername,%0A%0AYour request to attend *" . $eventname . "* was approved.%0A%0ARegards,%0AEboxtickets.com";
			$numwithcode = $findcartdata['user']['mobile'];
			$this->whatsappmsg($numwithcode, $message);
			// send watsappmessage start 

			$response['success'] = true;
			$response['message'] = "Ticket has been approved";
			echo json_encode($response);
			die;
		} else {
			$response['success'] = false;
			$response['message'] = "No Data";
			echo json_encode($response);
			die;
		}
	}

	public function accountDelete()
	{
		$this->loadModel('Users');
		$id  = $_POST['userId'];
		$response = array();
		if ($id) {
			// $getUser = $this->Users->get($id);

			// $fn['is_suspend'] =  'Y';
			// $data = $this->Users->patchEntity($getUser, $fn);
			// $okay = $this->Users->save($data);
			$response['success'] = true;
			$response['message'] = "Account has been deleted successfully";
			echo json_encode($response);
			die;
		}
		$response['success'] = false;
		$response['message'] = "User id not match sorry !";
		echo json_encode($response);
		die;
	}

	// send reminder for attendees to accept rsvp
	public function reminderemail()
	{
		$this->loadModel('Users');
		$this->loadModel('Templates');
		$this->loadModel('Event');
		$this->loadModel('Ticket');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Attendeeslist');
		$this->autoRender = false;

		$startTime = date("Y-m-d H:i:s");
		$convertedTime = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($startTime)));
		// pr($startTime);
		// pr($convertedTime);exit;
		$allevents = $this->Event->find('all')->where(['request_rsvp >=' => $startTime, 'request_rsvp <=' => $convertedTime, 'status' => 'Y', 'admineventstatus' => 'Y', 'is_free' => 'Y'])->toarray();
		if (!empty($allevents[0])) {
			foreach ($allevents as $key => $eventdetails) {

				$getUsers = $this->Ticketdetail->find('all')->contain(['Users', 'Ticket'])->where(['Ticket.event_id' => $eventdetails['id'], 'Ticketdetail.is_rsvp' => 'N'])->toarray();

				foreach ($getUsers as $tiketid => $ticketdetails) {
					$eventname = ucwords(strtolower($eventdetails['name']));
					$requestername = $ticketdetails['user']['name'] . ' ' . $ticketdetails['user']['lname'];
					$url = SITE_URL . 'tickets/myticket';
					$site_url = SITE_URL;
					$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 32])->first();
					$from = $emailtemplate['fromemail'];
					$to = $ticketdetails['user']['email'];
					$subject = $emailtemplate['subject'] . ': ' . $eventname;
					$formats = $emailtemplate['description'];

					$message1 = str_replace(array('{EventName}', '{RequesterName}', '{URL}', '{SITE_URL}', '{Email}', '{Password}'), array($eventname, $requestername, $url, $site_url, $ticketdetails['user']['email'], $ticketdetails['user']['confirm_pass']), $formats);
					$message = stripslashes($message1);
					$message = '<!DOCTYPE HTML>
								<html>                
								<head>
									<meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
									<title>Untitled Document</title>
									<style>
										p {
											margin: 9px 0px;
										}
									</style>                
								</head>                
								<body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
					$headers = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers .= 'From: <' . $from . '>' . "\r\n";
					$mail = $this->Email->send($to, $subject, $message);

					$message = "*Eboxtickets: RSVP Reminder Alert: 1 hour*%0AHello $requestername,%0A%0AKindly be reminded to Accept the RSVP invitation for *$eventname* Event.%0A%0ARegards,%0AEboxtickets.com";
					$numwithcode = $ticketdetails['user']['mobile'];
					$this->whatsappmsg($numwithcode, $message);
					// send watsappmessage start 
				}
			}
		}
	}

	// public function selfregistration()
	// {

	// 	$this->loadModel('Users');
	// 	$id  = $_POST['userId'];
	// 	$response = array();
	// 	if (!empty($id)) {
	// 		$response['success'] = true;
	// 		$response['message'] = 'You are send ' . $id . ' user id';
	// 	} else {
	// 		$response['success'] = false;
	// 		$response['message'] = "User id not match sorry !";
	// 	}
	// 	echo json_encode($response);
	// 	die;
	// }

	public function selfregistration()
	{
		$this->loadModel('Event');
		$this->loadModel('Users');
		$this->loadModel('Eventdetail');
		$this->loadModel('Countries');
		$this->loadModel('Company');
		$this->loadModel('Ticket');
		$this->loadModel('Cart');
		$this->loadModel('Payment');
		$this->loadModel('Ticketdetail');
		$this->loadModel('Committeeassignticket');
		$this->loadModel('Currency');
		$this->loadModel('Templates');
		$this->loadModel('Orders');
		$this->loadModel('Attendeeslist');
		$this->autoRender = false;
		$current_datetime = date('Y-m-d H:i:s');
		$user_id  = $_POST['userId'];
		$id = $_POST['eventId'];
		$response = array();
		$eventdetails = $this->Event->get($id);

		if ($eventdetails && $eventdetails['allow_register'] == 'Y') {
			$finduser = $this->Users->get($user_id);
			$checkTicket = $this->Eventdetail->find('all')->where(['eventid' => $id])->first();
			// if already exist user 
			if ($finduser) {
				$findone = $this->Ticket->find('all')->where(['event_id' => $id, 'cust_id' => $finduser['id']])->first();

				if (empty($findone)) {
					//save order data
					$orderdata['user_id'] = $finduser['id'];
					$orderdata['total_amount'] = 0;
					$orderdata['paymenttype'] = "Comps";
					$orderdata['created'] = $current_datetime;
					$insertdata = $this->Orders->patchEntity($this->Orders->newEntity(), $orderdata);
					$saveorders = $this->Orders->save($insertdata);
					$order_id = $saveorders['id'];

					$fn['user_id'] = $user_id;
					$fn['event_id'] = $id;
					$fn['mpesa'] = null;
					$fn['amount'] =  0;
					$fn['created'] =  $current_datetime;
					$payment = $this->Payment->patchEntity($this->Payment->newEntity(), $fn);
					$this->Payment->save($payment);

					$ticketbook['order_id'] = $saveorders->id;
					$ticketbook['event_id'] =  $id;
					$ticketbook['event_ticket_id'] = $checkTicket['id'];
					$ticketbook['cust_id'] = $user_id;
					$ticketbook['ticket_buy'] = 1;
					$ticketbook['amount'] = 0;
					$ticketbook['mobile'] =  ($finduser['mobile']) ? $finduser['mobile'] : $user_id;
					$ticketbook['committee_user_id'] = $eventdetails['event_org_id'];
					$ticketbook['user_desc'] = 'Free Ticket from mobile side';
					$ticketbook['created'] = $current_datetime;
					$insertticketbook = $this->Ticket->patchEntity($this->Ticket->newEntity(), $ticketbook);
					$lastinsetid = $this->Ticket->save($insertticketbook);

					$ticketdetaildata['tid'] = $lastinsetid['id'];
					$ticketdetaildata['user_id'] = $user_id;
					$ticketdetaildata['created'] = $current_datetime;
					$ticketdetail = $this->Ticketdetail->patchEntity($this->Ticketdetail->newEntity(), $ticketdetaildata);
					$ticketdetailvvv = $this->Ticketdetail->save($ticketdetail);

					$Packff = $this->Ticketdetail->get($ticketdetailvvv['id']);
					$Packff->ticket_num = 'T' . $ticketdetailvvv['id'];
					$Packff->name = $finduser['name'] . ' ' . $finduser['lname'];
					$Packff->is_rsvp = 'N';
					$ticketdetail = $this->Ticketdetail->save($Packff);

					$ticketqrimages = $this->qrcodepro($user_id, $ticketdetail['ticket_num'], $eventdetails['event_org_id']);
					$Pack = $this->Ticketdetail->get($ticketdetail['id']);
					$Pack->qrcode = $ticketqrimages;
					$this->Ticketdetail->save($Pack);

					// send email to admin and event organiser 
					$eventname = ucwords(strtolower($eventdetails['name']));
					$requestername = $finduser['name'] . ' ' . $finduser['lname'];
					$url = SITE_URL . 'tickets/myticket';
					$site_url = SITE_URL;
					// $currenny_sign = $currenny['Currency'];
					$emailtemplate = $this->Templates->find('all')->where(['Templates.id' => 31])->first();
					$from = $emailtemplate['fromemail'];
					$to = $finduser['email'];
					// $cc = $from;
					$subject = $emailtemplate['subject'] . ': ' . $eventname;
					$formats = $emailtemplate['description'];

					$message1 = str_replace(array('{EventName}', '{RequesterName}', '{URL}', '{SITE_URL}', '{Email}', '{Password}'), array($eventname, $requestername, $url, $site_url, $finduser['email'], $finduser['confirm_pass']), $formats);
					$message = stripslashes($message1);
					$message = '<!DOCTYPE HTML>
					<html>                
					<head>
						<meta http-equiv="Content-Type " content="text/html; charset=utf-8 ">
						<title>Untitled Document</title>
						<style>
							p {
								margin: 9px 0px;
							}
						</style>                
					</head>                
					<body style="background:#d8dde4; padding:15px;">' . $message1 . '</body></html>';
					$headers = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers .= 'From: <' . $from . '>' . "\r\n";
					$mail = $this->Email->send($to, $subject, $message);

					// send watsappmessage start 
					$message = "*Eboxtickets: Event Invitation*%0AHello $requestername,%0A%0AYou have received an Invitation to attend *$eventname* Event. This ticket is FREE.%0A%0AKindly download the eboxtickets App to access your ticket.%0A%0ARegards,%0AEboxtickets.com";
					$numwithcode = $finduser['mobile'];
					if ($numwithcode) {
						$this->whatsappmsg($numwithcode, $message);
					}
					// send watsappmessage start 
					$response['success'] = true;
					$response['message'] = 'You have recieved a ticket for this event. You can check ticket in My Tickets section.';
				} else {
					$response['success'] = false;
					$response['message'] = 'You have already recieved a ticket for this event. You can check ticket in My Tickets section.';
				}
			} else {
				$response['success'] = false;
				$response['message'] = 'You are not valid user.';
			}
		} else {
			$response['success'] = false;
			$response['message'] = 'This Event not allow to self Registration feature.';
		}
		echo json_encode($response);
		die;
	}

	function versionCheck()
	{
		$this->autoRender = false;
		$this->loadModel('Users');
		if ($this->request->is('post')) {
			$findbuild = $this->Users->find()->where(['role_id' => 1])->first();
			$build_no = $this->request->data['build_no'];
			$response = array();
			if ($findbuild['latest_apk_build_no'] == $build_no) {
				$response['success'] = true;
				$response['app_url'] = null;
				$response['message'] = 'Build No. Match';
			} else {
				$response['success'] = false;
				$response['app_url'] = 'https://play.google.com/store/apps/details?id=com.doomshell.eboxticket&hl=in-en';
				$response['message'] = 'A crucial update is now ready for installation. To maintain optimal functionality, kindly proceed with the update';
			}

			$this->response->type('application/json');
			$this->response->body(json_encode($response));
			return $this->response;
		}
	}

	public function addTicket() {
		$this->loadModel('Eventdetail');
		$this->loadModel('Event');
		
		if ($this->request->is(['post', 'put'])) {

				$addticket = $this->Eventdetail->newEntity();
				$this->request->data['eventid'] = $this->request->data['eventid'];
				$this->request->data['userid'] = $this->request->data['userid'];
				$this->request->data['title'] = $this->request->data['title'];
				$this->request->data['type'] = $this->request->data['type'];
				$this->request->data['price'] = $this->request->data['price'];
				$this->request->data['count'] = $this->request->data['count'];
				$this->request->data['hidden'] = $this->request->data['hidden'];
				$updateticket = $this->Eventdetail->patchEntity($addticket, $this->request->data);
				$ticktsave = $this->Eventdetail->save($updateticket);
			}

			die;

	}

	public function getAddTicketDetail() {
		$this->loadModel('Eventdetail');
		$this->loadModel('Ticket');
		$addtcktdetail = $this->Eventdetail->find('all')->where(['Eventdetail.eventid' => $this->request->data['eventid']])->contain(['Ticket'])->toArray();
	
		$response['addedticketdetail'] = array();
		$eventtcktcount = array(); 
		foreach ($addtcktdetail as $key => $tcktdetail) {
			$output['id'] = $tcktdetail['id'];
			$output['eventid'] = $tcktdetail['eventid'];
			$output['userid'] = $tcktdetail['userid'];
			$output['title'] = $tcktdetail['title'];
			$output['price'] = $tcktdetail['price'];
			$output['count'] = $tcktdetail['count'];
			$output['type'] = $tcktdetail['type'];
	
			if ($tcktdetail['hidden'] == 'N') {
				$output['visibility'] = "Hidden";
			} else {
				$output['visibility'] = "Visible";
			}
	
			if ($tcktdetail['sold_out'] == 'N') {
				$output['sale_status'] = "On-Sale";
			} else {
				$output['sale_status'] = "Sold-Out";
			}

			array_push($response['addedticketdetail'], $output);
	
			$event_ticket = $tcktdetail['event_ticket_id'];
			if (!isset($eventtcktcount[$event_ticket])) {
				$eventtcktcount[$event_ticket] = 1;
			} else {
				$eventtcktcount[$event_ticket]++;
			}

		}
	
		$total_ticket_added = array_sum($eventtcktcount);
		$response['total_ticket_added'] = $total_ticket_added;
	
		echo json_encode($response);
		die;
	}

	public function updateAddedTicket() {
		$this->loadModel('Eventdetail');
		$id = $this->request->data['id'];
		$getid = $this->Eventdetail->get($id);
		$title =  $this->request->data['title'];
		$type =  $this->request->data['type'];
		$price =  $this->request->data['price'];
		$count =  $this->request->data['count'];
		$hidden =  $this->request->data['hidden'];
		$sold_out =  $this->request->data['sold_out'];
		$input['title']=$title;
		$input['type']=$type;
		$input['price']=$price;
		$input['count']=$count;
		$input['hidden']=$hidden;
		$input['sold_out']=$sold_out;
		$updatetcktdetail= $this->Eventdetail->patchEntity($getid,$input);
		$this->Eventdetail->save($updatetcktdetail);
		die;

	}
	
	
	public function addAddons() {
		$this->loadModel('Addons');

		if ($this->request->is(['post', 'put'])) {

			$addadons = $this->Addons->newEntity();
			$this->request->data['event_id'] = $this->request->data['event_id'];
			$this->request->data['name'] = $this->request->data['name'];
			$this->request->data['price'] = $this->request->data['price'];
			$this->request->data['count'] = $this->request->data['count'];
			$this->request->data['hidden'] = $this->request->data['hidden'];
			$this->request->data['description'] = $this->request->data['description'];
			$updateadons = $this->Addons->patchEntity($addadons, $this->request->data);
			$addonsave = $this->Addons->save($updateadons);
			
		}

		die;
	}

	public function getAddonsDetail() {
		$this->loadModel('Addons');
		$addonsdetails = $this->Addons->find('all')->where(['Addons.event_id' => $this->request->data['event_id']])->toarray();
		
		$response['addons_details'] = array();
		foreach ($addonsdetails as $key =>  $addondata) {
			$output['id'] = $addondata['id'];
			$output['event_id'] = $addondata['event_id'];
			$output['name'] = $addondata['name'];
			$output['price'] = $addondata['price'];
			$output['count'] = $addondata['count'];
			if ($addondata['hidden'] == 'Y') {
				$output['visibility'] = 'Hidden';
			} else {
				$output['visibility'] = 'Visible';
			}
			
			$output['description'] = $addondata['description'];
			
			array_push($response['addons_details'], $output);
		}
		echo json_encode($response);
		die;

	}

	public function editAddonDetail() {
		$this->loadModel('Addons');
		$id = $this->request->data['id'];
		$getid = $this->Addons->get($id);
		$name =  $this->request->data['name'];
		$price =  $this->request->data['price'];
		$count =  $this->request->data['count'];
		$hidden =  $this->request->data['hidden'];
		$description =  $this->request->data['description'];
		$input['name']= $name;
		$input['price']= $price;
		$input['count']= $count;
		$input['hidden']= $hidden;
		$input['description']= $description;
		$updateaddondetail= $this->Addons->patchEntity($getid,$input);
		$this->Addons->save($updateaddondetail);
		die;
	}

	public function addQuestions() {
		$this->loadModel('Question');
		$this->loadModel('Questionitems');
		if ($this->request->is(['post', 'put'])) {

			if ($this->request->data['type']=='Select') {

			$addselectques = $this->Question->newEntity();
			$this->request->data['event_id'] = $this->request->data['event_id'];
			$this->request->data['type'] = $this->request->data['type'];
			$this->request->data['name'] = $this->request->data['name'];
			$this->request->data['question'] = $this->request->data['question'];
			$addselectquestion = $this->Question->patchEntity($addselectques, $this->request->data);
			$quesselectsave = $this->Question->save($addselectquestion);

			$ids = $quesselectsave['id'];
			foreach ($this->request->data['items'] as $item) {
			$questionitem['items'] = $item;
			$questionitem['question_id'] =  $ids;
			$quesitem = $this->Questionitems->newEntity();
			$quesitemsave = $this->Questionitems->patchEntity($quesitem, $questionitem);
			$itemssave = $this->Questionitems->save($quesitemsave);
			}
		} elseif ($this->request->data['type']== 'Text') {
			$addtextques = $this->Question->newEntity();
			$this->request->data['event_id'] = $this->request->data['event_id'];
			$this->request->data['type'] = $this->request->data['type'];
			$this->request->data['name'] = $this->request->data['name'];
			$this->request->data['question'] = $this->request->data['question'];
			$addtextquestion = $this->Question->patchEntity($addtextques, $this->request->data);
			$questextsave = $this->Question->save($addtextquestion);
		} else {
			$addagreeques = $this->Question->newEntity();
			$this->request->data['event_id'] = $this->request->data['event_id'];
			$this->request->data['type'] = $this->request->data['type'];
			$this->request->data['name'] = $this->request->data['name'];
			$this->request->data['question'] = $this->request->data['question'];
			$addagreequestion = $this->Question->patchEntity($addagreeques, $this->request->data);
			$questextsave = $this->Question->save($addagreequestion);
		}
	}
	die;
}

public function getQuestionDetail() {
    $this->loadModel('Question');
    $this->loadModel('Questionitems');

    $questiodetails = $this->Question->find('all')
        ->contain(['Questionitems'])
        ->where(['Question.event_id' => $this->request->data['event_id']])
        ->toArray();

    $response['question_details'] = array();

    foreach ($questiodetails as $key =>  $quesdetail) {
        $output = array(); 
        $items = array();  

        if ($quesdetail['type'] == 'Select') {
            $output['id'] = $quesdetail['id'];
            $output['event_id'] = $quesdetail['event_id'];
            $output['type'] = $quesdetail['type'];
            $output['name'] = $quesdetail['name'];
            $output['question'] = $quesdetail['question'];
			foreach ($quesdetail['questionitems'] as $item) {
                $items[] = $item['items'];
            }
            $output['items'] = implode(",", $items);
            $output['ticket_type_id'] = $quesdetail['ticket_type_id'];

            
        } else {
            $output['id'] = $quesdetail['id'];
            $output['event_id'] = $quesdetail['event_id'];
            $output['type'] = $quesdetail['type'];
            $output['name'] = $quesdetail['name'];
            $output['question'] = $quesdetail['question'];
            $output['ticket_type_id'] = $quesdetail['ticket_type_id'];
        }

        array_push($response['question_details'], $output);
    }

    echo json_encode($response);
    die;
}



public function editQuestionDetail() {
    $this->loadModel('Question');
    $this->loadModel('Questionitems');

    $id = $this->request->data['id'];
    $getid = $this->Question->get($id);
    $type = $this->request->data['type'];
    $name = $this->request->data['name'];
    $question = $this->request->data['question'];
    $ticket_type_id = $this->request->data['ticket_type_id'];

    $input['type'] = $type;
    $input['name'] = $name;
    $input['question'] = $question;
    $input['ticket_type_id'] = $ticket_type_id;

    $updatequesdetail = $this->Question->patchEntity($getid, $input);
    $this->Question->save($updatequesdetail);

    if ($type == 'Select') {
        $questionId = $getid->id;
        $questionItems = $this->Questionitems->find()->where(['question_id' => $questionId])->all();

		$this->Questionitems->deleteAll(['Questionitems.question_id' => $questionId]);
				foreach ($this->request->data['items'] as $key => $value) {
					$newitems = $this->Questionitems->newEntity();
					$items['question_id'] = $questionId;
					$items['items']	= ucfirst(strtolower($value));
					$newitemadd = $this->Questionitems->patchEntity($newitems, $items);
					$this->Questionitems->save($newitemadd);
				}
    }

    die;
}

public function linkQuesWithTicket() {
	$this->loadModel('Question');

	if ($this->request->is(['post', 'put'])) {

		$linktckt = $this->Question->newEntity();
		$this->request->data['id'] = $this->request->data['id'];
		$this->request->data['ticket_type_id'] = implode(",",$this->request->data['ticket_type_id']);
		$updatelinktckt = $this->Question->patchEntity($linktckt, $this->request->data);
		$linkticket = $this->Question->save($updatelinktckt);
		
	}

	die;
}

// public function addPackageDetail() {
// 	$this->loadModel('Package');
// 	$this->loadModel('Packagedetails');
// 	$this->loadModel('Eventdetail');
// 	$this->loadModel('Addons');
	
	
// 	if ($this->request->is(['post', 'put'])) {

		
// 		$addpack = $this->Package->newEntity();
// 		$this->request->data['event_id'] = $this->request->data['event_id'];
// 		$this->request->data['name'] = $this->request->data['name'];
// 		$this->request->data['package_limit'] = $this->request->data['package_limit'];
// 		$this->request->data['hidden'] = $this->request->data['hidden'];
// 		$updatepack = $this->Package->patchEntity($addpack, $this->request->data);
// 		$packsave = $this->Package->save($updatepack);
// 		$package_id = $packsave['id'];
		
// 		$addpackgg = $this->Packagedetails->newEntity();
// 		$package['package_id'] = $package_id;
// 		$package['ticket_type_id'] =  $this->request->data['ticket_type_id'];
// 		$package['addon_id'] =  $this->request->data['addon_id'];
// 		$package['qty'] =  $this->request->data['qty'];
// 		$package['price'] = $this->request->data['price'];
// 		$updatepackgg = $this->Packagedetails->patchEntity($addpackgg, $package);
// 		$packsaves = $this->Packagedetails->save($updatepackgg);


// 	}

// 	die;
// }




}
