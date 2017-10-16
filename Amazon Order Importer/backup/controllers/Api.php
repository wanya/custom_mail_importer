<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');

		$this->load->model('Orders');
		$this->load->model('Items');
	}
	
	public function index()
	{
		echo "index";
	}

	public function orders()
	{
		$res = $this->Orders->getAllOrders();
		foreach( $res['data'] as $order )
			$order->items = $this->Items->getItemsByOrder($order->OrderID);
		
		echo json_encode( $res );
	}

	public function items()
	{
		$res = $this->Items->getAllItems();
		
		echo json_encode( $res );
	}
	
	public function order( $id )
	{
		if( !$id ){
			echo "error";
			return;
		}
		$res = $this->Orders->getOrderByID( $id );
		$res->items = $this->Items->getItemsByOrder( $id );
		echo json_encode( $res );
	}

	public function item( $id )
	{
		if( !$id ){
			echo "error";
			return;
		}
		$res = $this->Items->getItemByID( $id );
		echo json_encode( $res );
	}
	
	public function delete()
	{
		$res = $this->Items->deleteAll();
		$res = $this->Orders->deleteAll();
		
		echo "success";
	}
}