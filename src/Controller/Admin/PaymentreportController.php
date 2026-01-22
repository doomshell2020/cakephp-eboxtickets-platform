<?php

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\View\Exception\MissingTemplateException;


class PaymentreportController extends AppController
{

    //$this->loadcomponent('Session');
    public function initialize()
    {
        //load all models
        parent::initialize();
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
        $this->loadModel('Package');
        $this->loadModel('Packagedetails');
        $user_id = $this->request->session()->read('Auth.User.id');
        $date = date("Y-m-d H:i:s");
    }

    public function index()
    {
        $session = $this->request->session();
        $session->delete('cond');
        $this->viewBuilder()->layout('admin');
        $conditions = [];
        $this->paginate = [
            'limit' => 50 // Set the pagination limit here
        ];

        $req_data = $_GET;
        $customername = $req_data['customername'];
        $customermobile = $req_data['customermobile'];
        $date_from = $req_data['date_from'];
        $date_to = $req_data['date_to'];
        // $eventname = $req_data['eventname'];
        $eventId = $req_data['eventid'];

        if (!empty($customername)) {
            $conditions['UPPER(Users.name) LIKE'] = '%' . trim(strtoupper($customername)) . '%';
        }

        // if (!empty($eventname)) {
        //     $conditions['UPPER(Event.name) LIKE'] = '%' . trim(strtoupper($eventname)) . '%';
        // }

        if (!empty($customermobile)) {
            $conditions['Users.mobile ='] = trim($customermobile);
        }

        if (strtotime($date_from) !== false) {
            $conditions['Orders.created >='] = date('Y-m-d', strtotime($date_from));
        }

        if (strtotime($date_to) !== false) {
            $conditions['Orders.created <='] = date('Y-m-d', strtotime($date_to));
        }

        $conditions['Orders.total_amount >'] = 0;
        $conditions['Orders.IsoResponseCode ='] = '00';

        // pr($conditions);exit;

        $admin_info = $this->Users->get(1);

        $query = $this->Orders->find('all')
            ->contain(['Users' => 'Countries', 'Ticket' => ['Event' => ['Currency', 'Countries', 'Company']]])
            ->where($conditions)
            ->order(['Orders.id' => 'DESC']);

        if (!empty($eventId)) {
            $query->matching('Ticket.Event', function ($q) use ($eventId) {
                return $q->where(['Event.id' => $eventId]);
            });
        }

        $getAllOrderData = $this->paginate($query)->toArray();
        $this->set(compact('admin_info', 'getAllOrderData'));
    }

    public function search()
    {

        $session = $this->request->session();
        $req_data = $_GET;
        $customername = $req_data['customername'];
        $customermobile = $req_data['customermobile'];
        $date_from = $req_data['date_from'];
        $date_to = $req_data['date_to'];
        $eventname = $req_data['eventname'];
        $eventId = $req_data['eventid'];
        $this->paginate = [
            'limit' => 50
        ];

        // pr($req_data);exit;
        $conditions = [];

        if (!empty($customername)) {
            $conditions['UPPER(Users.name) LIKE'] = '%' . trim(strtoupper($customername)) . '%';
        }

        // if (!empty($customername)) {
        //     $conditions['UPPER(Users.lname) LIKE'] = '%' . trim(strtoupper($customername)) . '%';
        // }

        // if (!empty($customername)) {
        //     $conditions[] = [
        //         'OR' => [
        //             'UPPER(CONCAT(Users.name, " ", Users.lname)) LIKE' => '%' . trim(strtoupper($customername)) . '%',
        //             'UPPER(CONCAT(Users.lname, " ", Users.name)) LIKE' => '%' . trim(strtoupper($customername)) . '%',
        //         ]
        //     ];
        // }

        // if (!empty($eventname)) {
        //     $conditions['UPPER(Event.name) LIKE'] = '%' . trim(strtoupper($eventname)) . '%';
        // }

        // if (!empty($eventId)) {
        //     $conditions['UPPER(Event.name) LIKE'] = '%' . trim(strtoupper($eventname)) . '%';
        // }

        if (!empty($customermobile)) {
            $conditions['Users.mobile ='] = trim($customermobile);
        }

        if (strtotime($date_from) !== false) {
            $conditions['Orders.created >='] = date('Y-m-d', strtotime($date_from));
        }

        if (strtotime($date_to) !== false) {
            $conditions['Orders.created <='] = date('Y-m-d', strtotime($date_to));
        }

        $conditions['Orders.total_amount >'] = 0;
        $conditions['Orders.IsoResponseCode ='] = '00';
        // pr($conditions);exit;
        $session->delete('condition');
        $session->write('condition', $conditions);


        // $getAllOrderData = $this->Orders->find('all')
        //     ->contain(['Users', 'Ticket' => 'Event'])
        //     // ->matching('Ticket.Event', function ($q) use ($eventname) {
        //     //     return $q->where(['UPPER(Event.name) LIKE' => '%' . strtoupper(trim($eventname)) . '%']);
        //     // })
        //     ->matching('Ticket.Event', function ($q) use ($eventId) {
        //         return $q->where(['Event.id' => $eventId]);
        //     })
        //     ->where($conditions)
        //     ->order(['Orders.id' => 'DESC']);

        // pr($conditions);

        $query = $this->Orders->find('all')
            ->contain(['Users' => 'Countries', 'Ticket' => ['Event' => ['Currency']]])
            ->where($conditions)
            ->order(['Orders.id' => 'DESC']);

        if (!empty($eventId)) {
            $query->matching('Ticket.Event', function ($q) use ($eventId) {
                return $q->where(['Event.id' => $eventId]);
            });
        }

        $getAllOrderData = $this->paginate($query)->toArray();

        // pr($getAllOrderData);exit;
        $this->set('getAllOrderData', $getAllOrderData);
    }
}
