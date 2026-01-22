<?php

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;
use Cake\Datasource\ConnectionManager;
use \DateTime;

class SeomanagerController extends AppController
{
	public function initialize()
	{
		//load all models
		parent::initialize();
	}
	public function index()
	{
		$this->viewBuilder()->layout('admin');
		$this->loadModel('Seo');
		$seo = $this->Seo->find('all')->order(['Seo.id' => 'DESC']);
		$seo = $this->paginate($seo)->toarray();
		$this->set('seo', $seo);
	}
	public function add()
	{
		$this->viewBuilder()->layout('admin');
		$this->loadModel('Seo');
		if ($this->request->is(['post', 'put'])) {
			$addseo = $this->Seo->newEntity();
			$this->request->data['page'] = $this->request->data['page'];
			$this->request->data['orgid'] = 1;
			$this->request->data['location'] = $this->request->data['location'];
			$this->request->data['title'] = $this->request->data['title'];
			$this->request->data['keyword'] = $this->request->data['keyword'];
			$this->request->data['description'] = $this->request->data['description'];
			$addseo = $this->Seo->patchEntity($addseo, $this->request->data);
			if ($this->Seo->save($addseo)) {
				$this->Flash->success(__('Seo has been saved successfully'));
				return $this->redirect(['action' => 'index']);
			}
		}
	}
	public function status($id, $status)
	{
		$this->loadModel('Seo');
		if (isset($id) && !empty($id)) {
			$seo = $this->Seo->get($id);
			$seo->status = $status;
			if ($this->Seo->save($seo)) {
				$this->Flash->success(__('Seo status has been updated.'));
				return $this->redirect(['action' => 'index']);
			}
		}
	}
	public function edit($id = null)
	{
		$this->viewBuilder()->layout('admin');
		$this->loadModel('Seo');
		$addseo = $this->Seo->get($id);
		$this->set('addseo', $addseo);
		if ($this->request->is(['post', 'put'])) {
			$this->request->data['page'] = $this->request->data['page'];
			$this->request->data['orgid'] = 1;
			$this->request->data['location'] = $this->request->data['location'];
			$this->request->data['title'] = $this->request->data['title'];
			$this->request->data['keyword'] = $this->request->data['keyword'];
			$this->request->data['description'] = $this->request->data['description'];
			$addseo = $this->Seo->patchEntity($addseo, $this->request->data);
			if ($this->Seo->save($addseo)) {
				$this->Flash->success(__('Seo has been updated successfully'));
				return $this->redirect(['action' => 'index']);
			}
		}
	}
}
