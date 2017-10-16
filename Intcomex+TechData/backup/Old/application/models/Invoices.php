<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends CI_Model
{
	function __construct(){

		parent::__construct();
		$this->db = $this->load->database('default', true);
	}

	function insertInvoice( $rowData )
	{
		if (empty($rowData) || !count($rowData)) {
			return 0;
		}
		
		if( !$rowData['invoiceID'] )
			return 0;

		if( $this->getInvoiceByID( $rowData['invoiceID']))
			return -1;

		if ($this->db->insert( 'invoices', $rowData)) {
			return $this->db->insert_id();
		} else {
			return 0;
		}
	}

	function getInvoiceByID( $invoiceID )
	{
		$this->db->from( "invoices" );
		$this->db->where( "invoiceID={$invoiceID}" );
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$rows = $query->result();
				return $rows[0];
		}

		return NULL;
	}

	function getAllInvoices()
	{
		$this->db->select( "*");
		$this->db->from('invoices');
		$this->db->order_by('add_date');
		
		$query = $this->db->get();
		
		if($query->num_rows() > 0)
		{
			$rows = $query->result();

			return array( "data"=> $rows );
		}
	}	
	
	function getInvoiceBySupplier( $supplier, $date)
	{
		$this->db->where('fromCom', $supplier);
		if( $date )
			$this->db->where('inv_date', $date);

		$this->db->from('invoices');
		$this->db->order_by('add_date');
		
		$query = $this->db->get();
		
		if($query->num_rows() > 0)
		{
			$rows = $query->result();

			return array( "data"=> $rows );
		}

	}
	
	function deleteAll()
	{
		$this->db->empty_table('invoices');
		return true;
	}
}

?>
