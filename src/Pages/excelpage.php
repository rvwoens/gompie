<?php namespace Rvwoens\Former\Pages;

use Input;
use Validator;
use Session;
use Redirect;
use URL;
use Log;
use DB;
use View;
use HTML;
use Cache;
use Rvwoens\Former\Cos;
use Rvwoens\Former\Vars;
use Rvwoens\Former\rows\row;
use Exception;
use Button;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
// use PHPExcel;
// use PHPExcel_Writer_Excel5;
use Response;

// a page is a viewable page on the screen with cards, lists and other elements
class excelpage extends page {
	public $data=array();
	protected $cacheTime  = 720;
	protected $isBigQuery = false;
	
	public function show($forceid=0,&$directresponse=null, &$httpCode=0) {
		$row=row::make($this->def,'excel');	// create an excelrow object
		$fields=$row->getFields();
		
		$search=$this->populateSearchblock();
		$deflistsql="select * from ".$this->def['table']." where 1=1 ".COS::ifset($this->def['search'],'').' $!switch ';

		// sql in excel-page, fallback listsql, fallback MAIN sql or DEF listsql
		$this->isBigQuery = !! $this->getdef('bigquery', false) ;	// cast to bool
		$sql=vars::v($this->getdef('pages.list.sql',$this->getdef('sql',$deflistsql)));
		$sql=vars::v($this->getdef('pages.excel.sql',$sql));

		//		$rows=DB::select($sql);
		try {
			$rows = $this->selectCached($sql);
		}
		catch (QueryException $qe) {
			Log::error($qe->getMessage());
			if (vars::v('$debug')=='Y')
				echo $qe->getMessage();
			else
				$queryerror="Sorry, an error occurred. Please contact support.";
			$rows=[];
		}
		catch (Exception $e) {
			Log::error($e->getMessage());
			if (vars::v('$debug')=='Y')
				echo $e->getMessage();
			else {
				return "<h4>Sorry, an error occurred</h4> Please <a href='".\Request::fullUrl()."'>reload the page</a> to try again or contact support.";
			}
			$rows=[];
		}


		// fallback to default list definition
		$listfields=$row->getFieldNames(
			$this->getdef('pages.excel.fields')?'pages.excel.fields':'pages.list.fields',
			'pages.skip.fields'); 	// default: all fieldnames
		$showfields=array();
		foreach($listfields as &$colname) {	// colname by reference as this allows $this->cardfields to be modified
			$field=$row->findfield($colname,true);	// true->generate if not exists
			if ($field==null) 
				throw new exception("former\field: Field $colname not found");
			$showfields[]=$field;
			$heads[]=$field->title();
		}
		$row->oShowfields=$showfields;

		// foreach($fields as $field) {
		// 	if (isset($this->def['listfields']) && array_search($field->name(),$this->def['listfields'])===false)
		// 		continue;	// NOT in the list!
		// 	$heads[]=$field->title();
		// }
		vars::storevars();	// remember!

		$title=substr(COS::lang(vars::v($this->getdef('pages.excel.title',Cos::nvl($this->def['title'],'Excel download')))),0,30);
		$title=preg_replace('/[^a-zA-Z0-9 ]/',' ',$title);	// only letters/digits
		$filename = $this->getdef('pages.excel.filename',$title).'-'.date('Ymd').'.xls';
		$filename=preg_replace('/[^a-zA-Z0-9.]/','',$filename);	// only letters/digits	
		$filepath=storage_path().'/cache/'.$filename;	
		//******************************************************************************************************
		//	Info collected. Lets generate the EXCEL!
		//******************************************************************************************************	
	 	//$objPHPExcel = new PHPExcel();
		$objPHPExcel = new Spreadsheet();
		$objPHPExcel->getProperties()->setTitle($title);

	    $sheet = $objPHPExcel->getActiveSheet();
		//------------------- GENERAL SETTINGS -----------------------
		$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
		$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
		$sheet->getPageSetup()->setFitToWidth(1);	// fit columns
		$sheet->getPageSetup()->setFitToHeight(0);	// unlimited lines
		
		$sheet->setTitle($title);
		$shrow=1;
	    //$sheet->setCellValue('A'.$shrow,$title);
		//$shrow++;
		//------------------ HEADER ------------------
		$col=0;
		foreach($heads as $head) {
			$sheet->getColumnDimension( Coordinate::stringFromColumnIndex($col) )->setAutoSize(true);
			$head=str_replace('&nbsp;',' ',$head);
			$sheet->setCellValueByColumnAndRow($col++, $shrow, COS::lang($head));
		}
		$lastcol=Coordinate::stringFromColumnIndex($col-1);
		$sheet->getRowDimension($shrow)->setRowHeight(30);
		$styleArray = array('font' => array('bold' => false,'color'=>array('argb'=>'FFFFFFFF'),'size'=>14),
							'alignment' => array('horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,),
							'borders' => array('bottom' => array('style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,), ),
							'fill' => array(
		       					'type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
		       					'startcolor' => array('argb' => 'FF707ee5',),
								'endcolor' => array('argb' => 'FFFFFFFF',), ),
		);
		$sheet->getStyle('A1:'.$lastcol.'1')->applyFromArray($styleArray);
		//------------------ DATA ROWS ------------------
		$shrow++;
		foreach ($rows as $drow) {
			$cols=$row->show($drow);
			$colnr=0;
			foreach($cols as $col) {
				// enter in the colum. Make it a multi-row cell
				if (strpos($col,"\n")!==false)
					$sheet->getStyle(\PHPExcel_Cell::stringFromColumnIndex($colnr).$shrow)->getAlignment()->setWrapText(true);
				$sheet->setCellValueByColumnAndRow($colnr++, $shrow, $col);
			}
			$shrow++;
		}

		// old phpexcel
		//$writer = new PHPExcel_Writer_Excel5($objPHPExcel);
		$filename=$this->getdef('pages.excel.filename',"export");	// without extension
		if (strpos($filename,'.')!==false)
			$filename = pathinfo($filename, PATHINFO_FILENAME);		// remove extension if one is given

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
		//$writer->setOffice2003Compatibility(true);
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');

		//$writer=\PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, "Xls");		// Xlsx
		//header('Content-Type: application/vnd.ms-excel');
		//header('Content-Disposition: attachment;filename="'.$filename.'.xls"');

		header('Cache-Control: max-age=0');
		//$writer = new PHPExcel_Writer_Excel5($objPHPExcel);

		$writer->save('php://output');
		exit();	// no further processing. Needed for xlsx
		//
		//	    $writer->save($filepath);
		//	    $response= Response::download($filepath, $filename,array(
		//				'Set-Cookie'	=> 'fileDownload=true; path=/',		// geen laravelcookie want die bevat een hash. Use this for .filedownload Jquery plugin
		//	    	)
		//	    );	// download our EXCEL sheet
		//	    $directresponse=$response;
		//	    return null;

	}
	
	
	// excel has an invisible searchblock but we need the switchvalues!! 
	private function populateSearchblock() {
		$switchhtml=array();
		$inputhtml='';$resethtml='';
		$inputtitle=array();

		//if (isset($this->def['switch'])) {
			$switches=COS::ifset($this->def['switch'],array());
			$switchvar='';
			$swc=1;
			//dd($switches);
			foreach($switches as $switch) {
				$rv='';
				$asql=is_array($switch['sql']) ? $switch['sql'] : explode(';',vars::v($switch['sql']));
				$atitle=is_array($switch['title']) ? $switch['title'] : explode(';',vars::v($switch['title']));

				$autosubmit='';
				if (COS::a2s($switch['autosubmit'],0,1))
					$autosubmit='onchange="submit();"';
				$rv.='<select class="input-medium" '.$autosubmit.' name="sw'.$swc.'">';
				$itemnr=1;
				$swselected=COS::def(vars::v('$sw'.$swc),1);
				Log::info("Item $swc selected=".$swselected."\n");
				foreach ($atitle as $k=>$title) {
					if ($title) {
						$selected='';
						vars::setvar('sw'.$swc,$swselected);	// STORE CURRENT CHOSEN SELECTION
						Log::info("setvar sw$swc to $swselected");
						if (vars::v('$sw'.$swc)==$itemnr) {
							$selected=' selected=selected ';
							if (!isset($asql[$k])) {
								// just ignore emptyZwarteSpecht
								
								//throw new exception("former\pages\listpage: switch $k '".$title."' does not have an sql:".print_r($asql,true));
							}
							else
								$switchvar.=' '.$asql[$k];
						}
						$rv.='<option value="'.$itemnr.'" '.$selected.'>'.$title.'</option>';
						$itemnr++;
					}
				}
				$rv.="</select> ";	// note: space is important for spacing
				$switchhtml[]=$rv;
				//echo "<pre>";var_dump($switchhtml);echo "</pre>";
				$swc++;
			}
			vars::setvar('search',vars::v('$search'));
			// store page
			if (isset($_GET['search'])) {
				// searchform posted, reset page
				vars::setvar('pg',0);
			}
			else
				vars::setvar('pg',COS::def(vars::v('$pg'),0));	// keep current page
			$inputhtml='<input type="text" name="search" placeholder="search" class="search-query input-medium" value="'.HTML::entities(vars::v('$search')).'"> ';
			$inputhtml.='<button type="submit" class="btn btn-primary"><i class="icon-search"></i> search</button> ';
			
			$resethtml.='<a class="btn" href="?reset=Y"><i class="icon-remove"></i> reset </a> ';

			// store switchvar
			vars::setvar('switch',$switchvar);	// full concatenated switch sql. Use: select * from table $!switch
			
			
			$this->data['switchhtml']=$switchhtml;
			$this->data['inputhtml']=$inputhtml;
			$this->data['resethtml']=$resethtml;
			//$this->data['list']=$list;
		//}
		return '';	// NO searchblock
	}

	/**
	 * @param $sql
	 * @return mixed
	 */
	protected function selectCached($sql) {
		if ($this->isBigQuery) {
			// bigquery
			//echo $bqsql;
			$qr = \BigQuery::runQuery($sql, [
				'maxResults' => 6000,
				'useLegacySql' => false,
				'timeoutMs' => 180000,
				//testing 'timeoutMs' => 1000,
			]);
			$rows=[];
			foreach ($qr->rows() as $r) {
				$rows[]=(object) $r;	// make it a stdclass
			}
			return $rows;
		}

		// cache = N or false to disable (yaml N results in false value) or minutes
		if ($this->cacheTime == 'N' || $this->cacheTime === false)
			$rows = DB::select($sql);
		else {
			$rows = Cache::tags($this->def['table'])->remember($sql, intval($this->cacheTime), function() use ($sql) {
				Log::info("Not cached Query: $sql");
				$rv = DB::select($sql);

				return $rv;
			});
		}
		return $rows;
	}
}