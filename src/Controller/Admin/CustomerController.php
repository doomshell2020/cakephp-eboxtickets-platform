<?php

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\View\Helper\PaginatorHelper;
use Cake\View\Exception\MissingTemplateException;
use PHPMailer\PHPMailer\PHPMailer;

include(ROOT . DS . "vendor" . DS  . "PHPMailer/" . DS . "PHPMailerAutoload.php");

class CustomerController extends AppController
{

	//$this->loadcomponent('Session');
	public function initialize()
	{
		//load all models
		parent::initialize();
		$this->loadComponent('Email');
	}

	public function index()
	{
		$this->loadModel('Users');
		$this->viewBuilder()->layout('admin');
		$ticketcustomer = $this->Users->find('all')
			->contain(['Countries'])
			->where(['Users.role_id' => CUSTOMERROLE])
			->order(['Users.id' => 'DESC']);
		// pr($ticketcustomer);die;
		$this->set('ticketcustomer', $this->paginate($ticketcustomer));
		// $this->set('ticketcustomer', $ticketcustomer);
	}

	public function add() {}

	//function for delete Event organiser 
	public function delete($id = null)
	{
		$this->loadModel('Users');
		$this->loadModel('Ticket');
		$customer_data = $this->Users->get($id);
		$ticketTable = TableRegistry::get('Ticket');
		$exists = $ticketTable->exists(['cust_id' => $customer_data['id']]);
		if ($exists) {
			$this->Flash->error(__('' . ucwords($customer_data['name']) . ' has not been deleted because customer have entry in some Manager'));
			return $this->redirect(['action' => 'index']);
		} else {
			if ($this->Users->delete($customer_data)) {
				$this->Flash->success(__('' . ucwords($customer_data['name']) . ' has been deleted Successfully.'));
				return $this->redirect(['action' => 'index']);
			}
		}
	}

	public function resendverificationemail($id = null)
	{
		$this->loadModel('Users');
		$customer_data = $this->Users->get($id);
		/*sending email start */
		$this->loadmodel('Templates');
		$profile = $this->Templates->find('all')->where(['Templates.id' => 13])->first();

		$subject = $profile['subject'];
		$from = $profile['from'];
		$fromname = $profile['fromname'];
		$name = $customer_data['name'] . ' ' . $customer_data['lname'];
		$email = $customer_data['email'];
		$activation_code = $customer_data['activation_code'];
		$buttonname = "VERIFY YOUR EMAIL";
		$activation = 'logins/activation/' . $activation_code;
		$to  = $email;
		$formats = $profile['description'];
		$site_url = SITE_URL;
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

		$resendverifcationemail = 'Y';
		$ticketcustomer = $this->Users->get($id);
		$ticketcustomer->resendverifcationemail = $resendverifcationemail;
		$this->Users->save($ticketcustomer);

		$mail = $this->Email->send($to, $subject, $message);

		$profess = $this->Users->find('all')->where(['id' => $res['id']])->first();
		// $this->Auth->setUser($profess);
		// $uty = $this->request->session()->read('Auth.User.role_id');
		// if (ORGANISERROLE == '2') {
		$this->Flash->success(__('A verification email has been sent to your inbox. Please check your email.'));
		return $this->redirect(['action' => 'index']);
	}

	public function status($id, $status)
	{
		$this->loadModel('Users');
		//pr($status);die;
		if (isset($id) && !empty($id)) {
			if ($status == 'N') {

				$status = 'Y';
				//status update
				$ticketcustomer = $this->Users->get($id);
				$ticketcustomer->status = $status;
				//pr($event_org);die;
				if ($this->Users->save($ticketcustomer)) {
					$this->Flash->success(__('' . ucwords($ticketcustomer['name']) . ' status has been updated.'));
					return $this->redirect(['action' => 'index']);
				}
			} else {

				$status = 'N';
				//status update
				$ticketcustomer = $this->Users->get($id);
				$ticketcustomer->status = $status;
				//pr($event_org);die;
				if ($this->Users->save($ticketcustomer)) {
					$this->Flash->success(__('' . ucwords($ticketcustomer['name']) . ' status has been updated.'));
					return $this->redirect(['action' => 'index']);
				}
			}
		}
	}
}
