<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Items extends CI_Model
{
	function __construct(){

		parent::__construct();
		$this->db = $this->load->database('default', true);
	}

	function insertItem( $rowData )
	{
		if (empty($rowData) || !count($rowData)) {
			return 0;
		}

		if ( $this->db->insert( 'order_items', $rowData) ) {
			return $this->db->insert_id();
		} else {
			return 0;
		}
	}
	
	function getItemsByOrder( $OrderID )
	{
		$this->db->from( "order_items" );
		$this->db->where( "OrderID='".$OrderID."'" );
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$rows = $query->result();
				return $rows;
		}

		return NULL;
	}

	function getAllItems()
	{
		$this->db->select( "*");
		$this->db->from('order_items');
		$this->db->order_by('AddDate');
		
		$query = $this->db->get();
		
		if($query->num_rows() > 0)
		{
			$rows = $query->result();

			return array( "data"=> $rows );
		}
	}

	function getItemByID( $itemID )
	{
		$this->db->from( "order_items" );
		$this->db->where( "id={$itemID}" );
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$rows = $query->result();
				return $rows[0];
		}

		return NULL;
	}

	function deleteAll()
	{
		$this->db->empty_table('order_items');
		return true;
	}

}

?>
