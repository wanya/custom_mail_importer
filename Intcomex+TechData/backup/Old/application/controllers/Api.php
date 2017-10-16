<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');

		$this->load->model('invoices');
		$this->load->model('items');
	}
	
	public function invoices()
	{
		$res = $this->invoices->getAllInvoices();
		foreach( $res['data'] as $invoice )
			$invoice->items = $this->items->getItemsByInvoice($invoice->invoiceID);
		
		echo json_encode( $res );
	}
	
	public function items()
	{
		$res = $this->items->getAllItems();
		
		echo json_encode( $res );
	}
	
	public function invoice( $id )
	{
		if( !$id ){
			echo "error";
			return;
		}
		$res = $this->invoices->getInvoiceByID( $id );
		$res->items = $this->items->getItemsByInvoice( $id );
		echo json_encode( $res );
	}
	
	public function item( $id )
	{
		if( !$id ){
			echo "error";
			return;
		}
		$res = $this->items->getItemByID( $id );
		echo json_encode( $res );
	}
	
	public function delete()
	{
		$res = $this->items->deleteAll();
		$res = $this->invoices->deleteAll();
		
		echo "success";
	}
	
	public function supplier( $supplier, $date = '')
	{
		if( $supplier != "intcomex" && $supplier != "techdata" && $supplier != "amazon" )
		{
			echo " Unsupported supplier"; return;
		}

		$res = $this->invoices->getInvoiceBySupplier($supplier, $date);
		foreach( $res['data'] as $invoice )
			$invoice->items = $this->items->getItemsByInvoice($invoice->invoiceID);

		echo json_encode($res);
	}
}