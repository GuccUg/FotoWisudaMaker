<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mahasiswa extends CI_Model {

	function __construct()
	{
		parent::__construct();
	}

	function getMhs()
	{
		$r = $this->db
			->select('npm, nama, tglsidang')
			->where('tglsidang', '2011-04-02')
			->where('MID(npm,3,1)', '1')
			->order_by('npm')
			->get('photos1')
			->result();
		return $r;
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */