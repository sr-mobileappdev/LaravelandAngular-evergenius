<?php
namespace App\Repositories\EmailMarketing;
/**
* 
*/
use App\Repositories\EmailMarketing\EmailMarketingRepositoryInterface;

use App\EmNewsletterContacts;

class EloquentEmailMarketing implements EmailMarketingRepositoryInterface
{
	private $model;

	function __construct(EmNewsletterContacts $model)
	{
		$this->model = $model;
	}

	public function getAll(){
		return $this->model->all();
	}
	
	public function getById($id){
		$this->model->find($id);
	}
	
	public function create($data){
		$this->model->create($data);
	}
	
	public function update($id ,array $data){
		$conatct = $this->model->find($id);
		$conatct->update($data);
		return $conatct;
	}
	
	public function delete($id){
		$conatct = $this->model->find($id);
		$conatct->delete();
		return true;
	}

}