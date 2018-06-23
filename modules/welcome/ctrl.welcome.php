<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

	function __construct()
	{
		parent::__construct();

	}

	function lihat_json()
	{
		$data = $this->db->get('S1komunikasi')->result_array();
		echo json_encode($data);
	}

	function index() {
		//phpinfo(); die();
		$this->json_read();
	}

	function json_read() {
		$files = file_get_contents(APPPATH.'../json/S1komunikasi.json');
		$files = json_decode($files);
		$data = array();
		/*
		$a = (object)array();
		$a->npm = '30406771';
		$a->nama = 'Yuanita Ratna Permatasari';
		$a->tgl_lahir = 'Jakarta/29-05-1988';
		$a->alamat = array(
			'Komplek Candra Indah',
			'Jl. Sumatra E- 14 rt : 02/16',
			'Bekasi 174141,Tp.8489183',
		);
		$a->email = '';
		$a->sidang = '07-05-2011';
		$files->{$a->npm} = $a;

		file_put_contents(APPPATH.'../students.phase3.json', json_encode($files));
		die();
		//*/
		//print_r($files); die();
		$x = 0;
		foreach ($files as $k=>$v) {
			//if (!in_array($v->npm, array('17107256','11106829'))) { continue; }
			$f = 'S';
			$fak = $this->fakultas($v->npm[2]);
			$jur = $this->fakultas($v->npm[2], $v->npm[0]);
			//if ($s1d3===false) echo $v->npm." - ".substr($v->npm, 2, 1).".".substr($v->npm, 0, 1)."<br/>";
			if (substr($jur, 0, 2) == 'D3') {
				$fak = $this->d3($v->npm[2]);
				if (empty($fak)) echo $v->npm[2];
				$f = 'D';
			}
			@$data[$f][$v->npm[2]][$v->sidang]['A'.$v->npm] = $v;
			//@$data[$f][$v->npm[2]][$v->sidang]++;
			//@$data[$f][$v->npm[2]]++;
			$x++;
		}
		// print_r("<pre>");
		// print_r($data);
		// print_r("</pre>");

		// echo $x." mhs";
		$this->pdf_write2($data);
	}

	function index2() {
		$this->output->enable_profiler = true;
		$this->load->database();
		$mhs = $this->load->model('mahasiswa')->getMhs();

		$this->pdf_write($mhs, $this->dbf()); die();

		$i = 0;
		foreach ($mhs as $v) {
			$i++; if ($i > 24) break;
			echo $i.print_r($v, true);
		}
		$this->load->view('welcome_message');
	}

	function dbf() {
		$db = file_get_contents(APPPATH.'../dbf/S1komunikasi.txt');
		$lines = explode("\n", $db);
		$header = explode("\t", trim($lines[0]));
		unset($lines[0]);
		$data = array();
		foreach ($lines as $l) {
			$ar = array();
			$av = explode("\t", $l);
			foreach ($header as $k=>$v) {
				$ar[trim($v)] = trim($av[$k]);
			}
			// $data['N'.$ar['NPM']] = $ar;
			$data[$ar['NPM']] = $ar;
		}
		// print_r("<pre>");
		// print_r(json_encode($data));
		// print_r("</pre>");

		echo json_encode($data);

		die();
		// return $data;
	}

	function dbf2() {

		//convert dbf file to json file.
		//@author : Hengky Mulyono

		$dbf_file = APPPATH.'../dbf/S1komunikasi.DBF';
		$db = dbase_open($dbf_file, 0);
		if (!$db) { die('Open DBF failed'); }

		//get column info
		$column_info = dbase_get_header_info($db);
		$num_rec = dbase_numrecords($db);
		$num_fields = dbase_numfields($db);

		$fields = array();

		$j = 0;
		for ($i=1; $i<=$num_rec; $i++){
			$row = @dbase_get_record_with_names($db,$i);
			$record = array();
			$record['npm'] = trim($row['NPM']);
			$record['nama'] = trim($row['NAMA']);
			$record['tgl_lahir'] = trim($row['TMP_LAHIR']).', '.trim($row['TGL_LAHIR']);
			$record['alamat'] = array(
				trim($row['ALAMAT1']),
				trim($row['ALAMAT2']).' RT '.trim($row['RT']).'/'.trim($row['RW']),
				trim($row['KOTA']).', '.trim($row['KODE_POS']).'. Tp.'.trim($row['TELEPON']),
			);
			$record['email'] = trim($row['EMAIL']);
			$record['sidang'] = trim($row['TGL_LULUS']);
			$fields[$row['NPM']] = $record;
			$j++;
		}

		$fp = fopen(APPPATH."../json/S1komunikasi.json","wb");
		fwrite($fp,json_encode($fields));
		fclose($fp);

		echo "Jumlah data : ".$j." Mhs";

		// print_r("<pre>");
		// print_r($fields);
		// print_r("</pre>");

		// print("<pre>");
		// echo json_encode($fields);
		// print("</pre>");
	}

	function pdf_write2($data) {
		$itung_data = 0;
		$itung_photo = 0;
		require_once(APPPATH.'third_party/tcpdf/config/lang/eng.php');
		require_once(APPPATH.'third_party/tcpdf/tcpdf.php');

		// create new PDF document
		$page_width = 173;
		$pdf = new TCPDF('P', 'mm', array($page_width, 240), true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('GUCC');
		$pdf->SetTitle('Buku Wisuda');
		$pdf->SetSubject('');
		$pdf->SetKeywords('');

		// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// set default monospaced font
		//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//set margins
		//$pdf->SetMargins(PDF_MARGIN_LEFT, 0, 0);

		//set auto page breaks
		//$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		//set image scale factor
		//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		//set some language-dependent strings
		//$pdf->setLanguageArray($l);

		// ---------------------------------------------------------

		$univ = $this->fakultas();

		$jenjang = array('S', 'D');
		$huruf = array(
			'S' => array(1=>'A', 2=>'C', 4=>'E', 3=>'F', 5=>'G', 6=>'H',8=>'K'),
			'D' => array(1=>'B', 2=>'D', 7=>'D'),
		);
		$nopen_start = array(
			'S' => array(1=>35181, 2=>28179, 4=>16331, 3=>1209, 5=>3159, 6=>1889, 8=>245),
			'D' => array(1=>23738, 2=>8027, 7=>151),
		);
		//f.ikti:42-103--
		//f.e:104-146--
		//f.ti: 147-183--
		//f.tsp:184-184--
		//f.p:185-193--
		//f.s:194-199--
		//f.kom:200-200--
		//d.3ti:201-227
		//d.3bk:228-236--
		//d.3kb:ga ada-



		//$page = -1 + 52; //Page number start with page 38 ikom.
		//$page = -1 + 103; //Page number start with page 38 FE.
		//$page = -1 + 181; //Page number start with page 38 FTI.
		//$page = -1 + 231; //Page number start with page 38 FTSP.
		//$page = -1 + 237; //Page number start with page 38 FP.
		//$page = -1 + 246; //Page number start with page 38 FS.
		//Page  = -1 + 254 number start with page 38 Komunikasi.
		$page = -1 + 254;
		foreach ($jenjang as $jnjg) {
			//if (!isset($data[$jnjg])) continue;

			foreach ($huruf[$jnjg] as $kd_fak=>$kode_huruf) {
				if (!isset($data[$jnjg][$kd_fak])) continue;

				$firstpage = true;
				$i = 0; $no = 1; $border = 0; $firstpage = true;
				$nopen = $nopen_start[$jnjg][$kd_fak];

				$tgls = array_keys($data[$jnjg][$kd_fak]);
				$tgls2 = array();
				foreach ($tgls as $k=>$tgl) {
					$tgls2[date("Y-m-d", strtotime(str_replace('/','-',$tgl)))] = $tgl;
					//echo date("Y-m-d", strtotime($tgl)).' - '.$tgl."<br/>";
				}
				ksort($tgls2);

				foreach ($tgls2 as $tgl) {
					$data2 =& $data[$jnjg][$kd_fak][$tgl];
					$namae = array();
					foreach ($data2 as $k=>$v) $namae['A'.$v->npm] = $v->nama;
					asort($namae);
					//$itung_data += count($namae);
					$npme = array_keys($namae);
					$count_nama = count($npme);
					$count_nama2 = 0;
					foreach ($npme as $npm) {
						$itung_data += 1;
						$count_nama2++;
						//continue;
						$s = $data2[$npm];
						$this->repair_length($s, $pdf);
						//var_dump($s); die();
						//print_r($s); die();
						//continue;

						// ----------------------------------------------------------------------
						if ($i % 12 == 0) $page++;
						//if (!isset($data['N'.$v->npm])) continue;
						$right_side_adjustment = ($page % 2) ? 8 : 0;
						$x_start = ($page % 2) ? $page_width - 143 - 15 : 15 ;
						$data_width = 35;
						if ($i % 12 == 0) {
							$pdf->AddPage(); // add a page
							$pdf->setCellPaddings(0,0,0,0);
							$pdf->SetFont('times', '', 9);
							$pdf->SetFillColor(200, 200, 200);
							$pdf->MultiCell(143, 6, '',
								0, 'C', true, 0, $x_start, 20, true, 0, false, $autopadding = false, 0, 'M', false);
							//$pdf->MultiCell(143, 0, 'Dies Natalis XXXII. Wisuda Diploma Tiga, Sarjana, Magister dan Doktor Universitas Gunadarma',
							$pdf->MultiCell(143, 0, 'Wisuda Diploma Tiga, Sarjana, Magister dan Doktor Universitas Gunadarma',
								0, 'C', true, 0, $x_start, 21, true, 0, false, $autopadding = false, 0, 'M', false);
							$pdf->SetFillColor(0, 0, 0);
							$pdf->SetFont('times', '', 1);
							$pdf->MultiCell(143, 1, '',
								0, 'C', true, 0, $x_start, 26, true, 0, false, $autopadding = false, 0, 'M', true);
							//$pdf->Rect(20, 26, 143, 1, '', array(), array(255,0,0));
							$pdf->Line($x_start, 27.5, $x_start + 143, 27.5);
							$pdf->Line($x_start, 220, $x_start + 143, 220);
							$pdf->SetFont('times', 'I', 8);
							$pdf->SetAutoPageBreak(false, 0);
							$pdf->writeHTMLCell(143, 0, $x_start, 222,
								'Nama, No.Pokok Mahasiswa/No.Alumni, Tempat Tgl. Lahir, Alamat, Email, Tgl. Lulus, Jurusan',
								$border, 0, false, true, ($page % 2) ? 'L' : 'R', false);
							$pdf->SetFont('times', '', 8);
							$pdf->writeHTMLCell(143, 0, $x_start, 222,
								$page,
								$border, 0, false, true, ($page % 2) ? 'R' : 'L', false);

							$pdf->SetFont('times', '', 6.5);
						}
						if ($firstpage == true) {
							if ($i==0) {
								$pdf_jenjang = "WISUDAWAN JENJANG ".($jnjg == 'S' ? 'SARJANA (S-1)': 'DIPLOMA TIGA (D3)');
								$pdf_fakultas = ($jnjg == 'S' ? $this->fakultas($s->npm[2]) : $this->d3($s->npm[2]) );
								$pdf->SetFont('times', '', 12);
								$pdf->writeHTMLCell(143, 0, $x_start, (38 + (30 * ($i % 6))), $pdf_jenjang, $border, 0, false, true, 'C', false);
								$pdf->writeHTMLCell(143, 0, $x_start, (44 + (30 * ($i % 6))), $pdf_fakultas, $border, 0, false, true, 'C', false);
								$pdf->writeHTMLCell(143, 0, $x_start, (50 + (30 * ($i % 6))), "UNIVERSITAS GUNADARMA", $border, 0, false, true, 'C', false);
								$pdf->SetFont('times', '', 6.5);
								$i=1;
							}
							if ($i==6) {
								$i=7;
								$firstpage = false;
							}
						}

						$photo_file = APPPATH.'../photo/'.$s->npm.'.jpg';
						$photo_exist = file_exists($photo_file);
						if ($photo_exist) {
							$itung_photo++;
							$pdf->Image($photo_file,
								$x = $x_start + (($i%12)<6 ? 10 : 86),
								$y = (39 + (30 * ($i % 6))),
								$w = 17,
								$h = 24,
								$type = '',
								$link = '',
								$align = '',
								$resize = true,
								$dpi = 300,
								$palign = '',
								$ismask = false,
								$imgmask = false,
								$border = 0,
								$fitbox = false,
								$hidden = false,
								$fitonpage = false
							);
						} else {
							$y_ = isset($s->alamat[2]) ? 53 : 50;
							$pdf->writeHTMLCell(17, 0, $x_start + (($i%12)<6 ? 10 : 86), (38 + (30 * ($i % 6))), "Nama", $border, 0, false, true, 'L', false);
							$pdf->writeHTMLCell(17, 0, $x_start + (($i%12)<6 ? 10 : 86), (41 + (30 * ($i % 6))), "NPM", $border, 0, false, true, 'L', false);
							$pdf->writeHTMLCell(17, 0, $x_start + (($i%12)<6 ? 10 : 86), (44 + (30 * ($i % 6))), "TTL", $border, 0, false, true, 'L', false);
							$pdf->writeHTMLCell(17, 0, $x_start + (($i%12)<6 ? 10 : 86), (47 + (30 * ($i % 6))), "Alamat", $border, 0, false, true, 'L', false);
							if (!isset($s->alamat[1])) $y_ = $y_ - 3;

							if (empty($s->email)) {$y_ = $y_ - 3;} else {
								$pdf->writeHTMLCell(17, 0, $x_start + (($i%12)<6 ? 10 : 86), ($y_ + 3 + (30 * ($i % 6))), "Email", $border, 0, false, true, 'L', false);
							}
							$pdf->writeHTMLCell(17, 0, $x_start + (($i%12)<6 ? 10 : 86), ($y_ + 6 + (30 * ($i % 6))), "Lulus/Jurusan", $border, 0, false, true, 'L', false);
						}

						$jur = $s->npm[0];
						$fak = $s->npm[2];
						$pdf_jurusan = $univ['jur'][$fak][$jur];
						if (substr($pdf_jurusan, 0, 2)=='D3') $pdf_jurusan = substr($pdf_jurusan, 3);
						//$pdf->SetXY(($i<6 ? 20 : 97).'mm', (41 + (30 * ($i % 6))).'mm', true);
						//$pdf->Write($h=0, $no.'.', $link='', $fill=0, $align='R', $ln=false, $stretch=0, $firstline=false, $firstblock=false, $maxh=0);
						$border = 0;
						$pdf->writeHTMLCell(5, 0, $x_start + (($i%12)<6 ? 4 : 80), (38 + (30 * ($i % 6))), $no.'.', $border, 0, false, true, 'R', false);
						$pdf->writeHTMLCell($data_width, 0, $x_start + (($i%12)<6 ? 30 : 106), (38 + (30 * ($i % 6))), $s->nama, $border, 0, false, true, 'L', false);
						$pdf->writeHTMLCell($data_width, 0, $x_start + (($i%12)<6 ? 30 : 106), (41 + (30 * ($i % 6))), $s->npm."/$kode_huruf.$nopen", $border, 0, false, true, 'L', false);

						$y_ = isset($s->alamat[2]) ? 53 : 50;
						$pdf->writeHTMLCell($data_width, 0, $x_start + (($i%12)<6 ? 30 : 106), (44 + (30 * ($i % 6))), $s->tgl_lahir, $border, 0, false, true, 'L', false);
						$pdf->writeHTMLCell($data_width + 5, 0, $x_start + (($i%12)<6 ? 30 : 106), (47 + (30 * ($i % 6))), $s->alamat[0], $border, 0, false, true, 'L', false);
						if (isset($s->alamat[1]))
							$pdf->writeHTMLCell($data_width + 5, 0, $x_start + (($i%12)<6 ? 30 : 106), (50 + (30 * ($i % 6))), $s->alamat[1], $border, 0, false, true, 'L', false);
						if (!isset($s->alamat[1])) $y_ = $y_ - 3;
						if (isset($s->alamat[2]))
							$pdf->writeHTMLCell($data_width + 5, 0, $x_start + (($i%12)<6 ? 30 : 106), (53 + (30 * ($i % 6))), $s->alamat[2], $border, 0, false, true, 'L', false);

						if (empty($s->email)) {$y_ = $y_ - 3;} else {
							$pdf->writeHTMLCell($data_width, 0, $x_start + (($i%12)<6 ? 30 : 106), ($y_ + 3 + (30 * ($i % 6))), $s->email, $border, 0, false, true, 'L', false);
						}
						$pdf->writeHTMLCell($data_width, 0, $x_start + (($i%12)<6 ? 30 : 106), ($y_ + 6 + (30 * ($i % 6))), date("d-m-Y", strtotime(str_replace('/','-',$s->sidang)))."/".$pdf_jurusan, $border, 0, false, true, 'L', false);

						$no++; $nopen++;
						$i++; //if ($i == 28) break;
						// ----------------------------------------------------------------------
					}
					if ($count_nama != $count_nama2) {
						echo $count_nama ." vs ". $count_nama2;
						print_r($npme);
						echo "<hr/>";
					}
				}
			}
		}

		//Close and output PDF document
		$pdf->Output(APPPATH.'../result/S1komunikasi.pdf', 'F');
		echo $itung_photo.'/'.$itung_data;
		$this->load->view('welcome_message');
	}


	function pdf_write($data, $data2) {
		require_once(APPPATH.'third_party/tcpdf/config/lang/eng.php');
		require_once(APPPATH.'third_party/tcpdf/tcpdf.php');

		// create new PDF document
		$page_width = 175;
		$pdf = new TCPDF('P', 'mm', array($page_width, 240), true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Yakub Kristianto');
		$pdf->SetTitle('Dies Natalis');
		$pdf->SetSubject('');
		$pdf->SetKeywords('');

		// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// set default monospaced font
		//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//set margins
		//$pdf->SetMargins(PDF_MARGIN_LEFT, 0, 0);

		//set auto page breaks
		//$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		//set image scale factor
		//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		//set some language-dependent strings
		//$pdf->setLanguageArray($l);

		// ---------------------------------------------------------

		$univ = $this->fakultas();

		$i = 0;
		$page = 1; $no = 1; $border = 0; $kode_huruf = "C"; $nopen = 678; $firstpage = true;
		foreach ($data as $v) {
			if ($i % 12 == 0) $page++;
			//if (!isset($data['N'.$v->npm])) continue;
			$right_side_adjustment = ($page % 2) ? 8 : 0;
			$x_start = ($page % 2) ? $page_width - 17 - 143 : 17 ;
			if ($i % 12 == 0) {
				$pdf->AddPage(); // add a page
				$pdf->SetFont('times', '', 9);
				$pdf->SetFillColor(200, 200, 200);
				$pdf->MultiCell(143, 6, '',
					0, 'C', true, 0, $x_start, 20, true, 0, false, $autopadding = false, 0, 'M', false);
				$pdf->MultiCell(143, 0, 'Wisuda Diploma Tiga, Sarjana, Magister dan Doktor Universitas Gunadarma',
					0, 'C', true, 0, $x_start, 21, true, 0, false, $autopadding = false, 0, 'M', false);
				$pdf->SetFillColor(0, 0, 0);
				$pdf->SetFont('times', '', 1);
				$pdf->MultiCell(143, 1, '',
					0, 'C', true, 0, $x_start, 26, true, 0, false, $autopadding = false, 0, 'M', true);
				//$pdf->Rect(20, 26, 143, 1, '', array(), array(255,0,0));
				$pdf->Line($x_start, 27.5, $x_start + 143, 27.5);
				$pdf->Line($x_start, 220, $x_start + 143, 220);
				$pdf->SetFont('times', 'I', 8);
				$pdf->SetAutoPageBreak(false, 0);
				$pdf->writeHTMLCell(143, 0, $x_start, 222,
					'Nama, No.Pokok Mahasiswa/No.Alumni, Tempat Tgl. Lahir, Alamat, Email, Tgl. Lulus, Jurusan',
					$border, 0, false, true, ($page % 2) ? 'L' : 'R', false);
				$pdf->SetFont('times', '', 8);
				$pdf->writeHTMLCell(143, 0, $x_start, 222,
					$page,
					$border, 0, false, true, ($page % 2) ? 'R' : 'L', false);

				$pdf->SetFont('times', '', 6.5);
			}
			if ($firstpage == true) {
				if ($i==0) {
					$pdf->SetFont('times', '', 12);
					$pdf->writeHTMLCell(143, 0, $x_start, (38 + (30 * ($i % 6))), "WISUDAWAN JENJANG SARJANA (S-1)", $border, 0, false, true, 'C', false);
					$pdf->writeHTMLCell(143, 0, $x_start, (44 + (30 * ($i % 6))), "FAKULTAS ILMU KOMPUTER", $border, 0, false, true, 'C', false);
					$pdf->writeHTMLCell(143, 0, $x_start, (50 + (30 * ($i % 6))), "UNIVERSITAS GUNADARMA", $border, 0, false, true, 'C', false);
					$pdf->SetFont('times', '', 6.5);
					$i=1;
				}
				if ($i==6) {
					$i=7;
					$firstpage = false;
				}
			}

			$photo_file = APPPATH.'../photos/'.$v->npm.'.jpg';
			$photo_exist = file_exists($photo_file);
			if ($photo_exist) {
				$pdf->Image($photo_file,
					$x = $x_start + (($i%12)<6 ? 10 : 86),
					$y = (39 + (30 * ($i % 6))),
					$w = 17,
					$h = 0,
					$type = '',
					$link = '',
					$align = '',
					$resize = true,
					$dpi = 300,
					$palign = '',
					$ismask = false,
					$imgmask = false,
					$border = 0,
					$fitbox = false,
					$hidden = false,
					$fitonpage = false
				);
			}

			$jur = substr($v->npm, 0, 1);
			$fak = substr($v->npm, 2, 1);
			$pdf_jurusan = $univ['jur'][$fak][$jur];
			if (substr($pdf_jurusan, 0, 2)=='D3') $pdf_jurusan = substr($pdf_jurusan, 3);

			//$pdf->SetXY(($i<6 ? 20 : 97).'mm', (41 + (30 * ($i % 6))).'mm', true);
			//$pdf->Write($h=0, $no.'.', $link='', $fill=0, $align='R', $ln=false, $stretch=0, $firstline=false, $firstblock=false, $maxh=0);
			$border = 0;
			$pdf->writeHTMLCell(5, 0, $x_start + (($i%12)<6 ? 4 : 80), (38 + (30 * ($i % 6))), $no.'.', $border, 0, false, true, 'R', false);
			$pdf->writeHTMLCell(35, 0, $x_start + (($i%12)<6 ? 30 : 106), (38 + (30 * ($i % 6))), $v->nama, $border, 0, false, true, 'L', false);
			$pdf->writeHTMLCell(35, 0, $x_start + (($i%12)<6 ? 30 : 106), (41 + (30 * ($i % 6))), $v->npm."/$kode_huruf.$nopen", $border, 0, false, true, 'L', false);

			$pdf->writeHTMLCell(35, 0, $x_start + (($i%12)<6 ? 30 : 106), (44 + (30 * ($i % 6))), "Tempat/Tgl Lahir", $border, 0, false, true, 'L', false);
			$pdf->writeHTMLCell(35, 0, $x_start + (($i%12)<6 ? 30 : 106), (47 + (30 * ($i % 6))), "Alamat 1", $border, 0, false, true, 'L', false);
			$pdf->writeHTMLCell(35, 0, $x_start + (($i%12)<6 ? 30 : 106), (50 + (30 * ($i % 6))), "Alamat 2", $border, 0, false, true, 'L', false);
			$pdf->writeHTMLCell(35, 0, $x_start + (($i%12)<6 ? 30 : 106), (53 + (30 * ($i % 6))), "Alamat 3", $border, 0, false, true, 'L', false);

			$pdf->writeHTMLCell(35, 0, $x_start + (($i%12)<6 ? 30 : 106), (56 + (30 * ($i % 6))), "email@student.gunadarma.ac.id", $border, 0, false, true, 'L', false);
			$pdf->writeHTMLCell(35, 0, $x_start + (($i%12)<6 ? 30 : 106), (59 + (30 * ($i % 6))), date("d-m-Y", strtotime($v->tglsidang))."/".$pdf_jurusan, $border, 0, false, true, 'L', false);

			$no++; $nopen++;
			$i++; if ($i == 28) break;
		}



		//Close and output PDF document
		$pdf->Output('example_002.pdf', 'I');
	}

	function d3($f) {
		$fak = array(
			1=>'Teknologi Informasi',
			2=>'Bisnis dan Kewirausahaan',
			7=>'Kebidanan',
		);
		$fak = array(
			1=>'TEKNOLOGI INFORMASI',
			2=>'BISNIS dan KEWIRAUSAHAAN',
			7=>'KEBIDANAN',
		);
		return $fak[$f];
	}
	function fakultas($f = false, $j = false) {
		$fak = array(
			1=>"Fakultas Ilmu Komputer",
			2=>"Fakultas Ekonomi",
			3=>"Fakultas Teknik Sipil & Perencanaan",
			4=>"Fakultas Teknologi Industri",
			5=>"Fakultas Psikologi",
			6=>"Fakultas Sastra",
			7=>"Fakultas Kebidanan",
			8=>"Fakultas Komunikasi",
		);
		$fak = array(
			1=>"FAKULTAS ILMU KOMPUTER dan TEKNOLOGI INFORMASI",
			2=>"FAKULTAS EKONOMI",
			3=>"FAKULTAS TEKNIK SIPIL dan PERENCANAAN",
			4=>"FAKULTAS TEKNOLOGI INDUSTRI",
			5=>"FAKULTAS PSIKOLOGI",
			6=>"FAKULTAS SASTRA",
			7=>"FAKULTAS KEBIDANAN",
			8=>"FAKULTAS KOMUNIKASI",
		);

		$jur = array(
			1=>array(
				1=>"S.Informasi",
				2=>"S.Komputer",
				3=>"D3 Manj.Inf",
				4=>"D3 Tek.Komputer",
			),
			2=>array(
				1=>"Manajemen",
				2=>"Akuntansi",
				3=>"D3 Manajemen",
				4=>"D3 Akuntansi",
			),
			4=>array(
				1=>"T.Elektro",
				2=>"T.Mesin",
				3=>"T.Industri",
				5=>"T.Informatika",
			),
			3=>array(
				1=>"T.Sipil",
				2=>"T.Arsitektur",
			),
			5=>array(
				1=>"Psikologi",
			),
			6=>array(
				1=>"Sastra",
			),
			7=>array(
				3=>"D3 Kebidanan",
			),
			8=>array(
				1=>"Komunikasi",
			),
		);

		if ($f == false && $j == false) {
			return array('fak'=>$fak, 'jur'=>$jur);
		} elseif ($j == false) {
			return $fak[$f];
		} elseif ($j == 0) {
			return $jur[$f];
		} else {
			if (!isset($jur[$f][$j])) return false;
			return $jur[$f][$j];
		}
	}

	function repair_length(&$s, &$pdf, $l = 35) {
		$pdf->SetFont('times', '', 6.5);

		//if ($pdf->getStringWidth($s->email) > $l) { $email = '1';}
		while ($pdf->getStringWidth($s->email) > $l) {
			$s->email = substr($s->email, 0, -4).'...';
		}
		//if (@$email==='1') echo $s->email."<br/>";

		return;
		$a = isset($s->alamat[2]) ? $s->alamat[2] : $s->alamat[1];
		$b = isset($s->alamat[2]) ? $s->alamat[1] : $s->alamat[0];
		if (!empty($a) && $pdf->getStringWidth($a) > $l) {
			while ($pdf->getStringWidth($a) > $l) {
				$as = explode(' ', $a);
				if ($pdf->getStringWidth($b.' '.$as[0].' '.$as[1]) > $l) { echo $s->npm." - Broken<br/>"; break; }
				$b = $b.' '.$as[0].' '.$as[1];
				unset($as[0]); unset($as[1]);
				$a = implode(' ', $as);
			}
			if (isset($s->alamat[2])) {
				$s->alamat[1] = $b;
				$s->alamat[2] = $a;
			} else {
				$s->alamat[0] = $b;
				$s->alamat[1] = $a;
			}
			var_dump($s->alamat);
			echo ''."<br/>";
		}
	}


	function import_registrant_excel(){
			$this->load->view('import_excel');
	}
	function proses_import()
	{
		$this->load->library('Spreadsheet_Excel_Reader');
		$data = new Spreadsheet_Excel_Reader($_FILES['userfile']['tmp_name']);
		$baris = $data->rowcount($sheet_index=0);
		$i=2; //baris
		$sukses = 0;
		$gagal	= 0;
		$d		= 0;
		$k		= 0;
		while($data->val($i, 1)!="")
		{
			$hasil	=false;
			$record = array();
			$record['npm'] =$data->val($i, 1);// membaca data id (kolom ke-1)
		    $record['nama']		=$data->val($i, 2);
		    $record['tgl_lahir']	=$data->val($i, 5).', '.$data->val($i, 4);
		    $record['alamat'] = array(
				$data->val($i, 6),
				$data->val($i, 7).' RT '.$data->val($i, 8).'/'.$data->val($i, 9),
				$data->val($i, 10).', '.$data->val($i, 11).'. Tp.'.$data->val($i, 12),
			);
			$record['email'] = $data->val($i, 13);
			$record['sidang'] = $data->val($i, 3);
			$fields[$data->val($i, 1)] = $record;

		$i++;
	}
	/*KHUSUS D.3.T.I..*/

	/*while($data->val($i, 1)!="")
		{
			$hasil	=false;
			$record = array();
			$record['npm'] =$data->val($i, 1);// membaca data id (kolom ke-1)
		    $record['nama']		=$data->val($i, 2);
		    $record['tgl_lahir']	=$data->val($i, 26).', '.$data->val($i, 25);
		    $record['alamat'] = array(
				$data->val($i, 27),
				$data->val($i, 28).' RT '.$data->val($i, 30).'/'.$data->val($i, 31),
				$data->val($i, 32).', '.$data->val($i, 33).'. Tp.'.$data->val($i, 34),
			);
			$record['email'] = $data->val($i, 42);
			$record['sidang'] = $data->val($i, 23);
			$fields[$data->val($i, 1)] = $record;

		$i++;
	}*/

	$fp = fopen(APPPATH."../json/S1komunikasi.json","wb");
		fwrite($fp,json_encode($fields));
		fclose($fp);

		echo "Jumlah data : ".($i-2)." Mhs";

		// print_r("<pre>");
		// print_r($fields);
		// print_r("</pre>");

		// print("<pre>");
		// echo json_encode($fields);
		// print("</pre>");
}
}
/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
