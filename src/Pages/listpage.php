<?php namespace Rvwoens\Gompie\Pages;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Input;

use Validator;
use Session;
use Cache;
use MultiCache;
use Redirect;
use URL;
use Response;
use Log;
use DB;
use View;
use HTML;

use Rvwoens\Gompie\Cos;
use Rvwoens\Gompie\Vars;
use Rvwoens\Gompie\rows\row;
use Exception;
use Form;

// a page is a viewable page on the screen with cards, lists and other elements
class listpage extends page {
	public  $data       =array();
	protected $pagination = [ 'limit'=>30, 'count'=>0, 'offset'=>0, 'page'=>0, 'nrpages'=>1 ];
	protected $cacheTime  = 720;
	protected $isBigQuery = false;

	
	public function show($forceid=0,&$directresponse=null, &$httpCode=0) {
		$row=row::make($this->def,'list');	// create a listrow
		// ajax-supported page. Check for ajax calls
		if ($this->handleAjax($directresponse,$httpCode))
			return null;	// handled
		// do we need to set sessionvars?
		$this->setsessionvars('list');
		//$fields=$row->getFields();
		$this->populateSearchblock();			// in $data
		$this->populateTabBlock('list');		// in $data
		$this->populateSelectionBox();			// in $data
		$deflistsql="select * from ".$this->def['table']." where 1=1 ".COS::ifset($this->def['search'],'').' $!switch ';

		$this->isBigQuery = !! $this->getdef('bigquery', false) ;	// cast to bool
		$this->cacheTime=$this->getdef('pages.list.cache', 'N');	// chache in minutes or N to disable cache. default OFF

		// pages: list: sql:.... OR main sql:... OR the default list sql
		$sql=vars::v($rawsql=$this->getdef('pages.list.sql',$this->getdef('sql',$deflistsql)),false);	// no DoEscape as this also escapes switches. Use ${search:e} to escape search


		$this->rowsAndPagination($sql);
		// now we know offset and limit
		$sql=$this->addPaginationToSql($sql);

		vars::setvar('debug',vars::v('$debug'));	// remember debug value
		if (substr(vars::v('$debug'),0,1)=='Y' ) {
			echo "<pre>$sql</pre><br><pre>$rawsql</pre><br>\n";
		}

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
		//Log::info('Listpage: sql='.$sql);	
		if (vars::v('$debug')=='YY')
			dd($rows);

		//$rows=DB::query(vars::v("select * from ".$this->def['table']." where 1=1 ".COS::ifset($this->def['search'],'').' $!switch '));


		if ($this->getdef('options.new','Y')=='Y') {
			if ($this->hasToken('pages.new.token')) {
				$this->data[ 'newhtml' ] = HTML::link($this->getdef('pages.new.url'),$this->getdef('pages.new.button','New'),array('class'=>'btn btn-default'));
					//'<a href="'.$this->getdef('pages.new.url').'" class="btn btn-default"><i class="material-icons">add</i></a>';
					//HTML::link(, $this->getdef('pages.new.button', trans('former::elements.nieuw')),
					//								  array( 'class' => 'btn btn-default' ));
			}
		}
		if ($this->getdef('options.excel','N')=='Y') {
			if ($this->hasToken('pages.excel.token'))
				$this->data['excelhtml']=HTML::link(
					$this->getdef('pages.excel.url'),
					$this->getdef('pages.excel.button', 'Excel download'),
					[	'class'=>'mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent mdl-button--xs submitspinner',
						 'data-url-spinner'=>';12000',
					]);
		}
		
		$listfields=$row->getFieldNames('pages.list.fields','pages.skip.fields'); // 'listfields');	// default: all fieldnames
		$showfields=array();
		foreach($listfields as &$colname) {	// colname by reference as this allows $this->cardfields to be modified
			$field=$row->findfield($colname,true);	// true->create field if not exists
			if ($field==null) 
				throw new exception("former\field: Field $colname not found");
			$showfields[]=$field;
			$heads[]=COS::lang($field->title());
		}
		$row->oShowfields=$showfields;

		// foreach($fields as $field) {
		// 	if (isset($this->def['listfields']) && array_search($field->name(),$this->def['listfields'])===false)
		// 		continue;	// NOT in the list!
		// 	$heads[]=$field->title();
		// }
		vars::setvar('maplist','list');	// remember we are on map
		vars::storevars();	// remember!
		$cardurl=null;
		if ($this->getdef('pages.list.rowicon','')=='eye')
			$cardurl= ($this->getdef('options.edit','Y')=='Y' && $this->hasToken('pages.edit.token')) ?
				$this->getdef('pages.edit.url',''):
				null;
		$refresh=$this->getdef('pages.list.refresh',0);

		$listAjaxUrl=$this->getdef('pages.list.url','');
		$horizontalscroll=!! $this->getdef('pages.list.horizontalscroll', false);
		$vw=View::make('gompie::pages/list',
			array(
				'layout' => $this->getdef('layout') ?: [],
				'heads'=>$heads,
				'rows'=>$rows,
				'row'=>$row,
				'cardurl'=>$cardurl, // $this->def['cardurl'],
				'data'=>$this->data,
				'title'=>COS::lang( vars::v(COS::nvl($this->def['title'],'lijst')) ),
				'extrabuttons'=>$this->getdef('extrabuttons'),
				'subtitle'=>COS::lang( vars::v(COS::nvl($this->def['subtitle'],'')) ),
				'alerttitle'=>isset($queryerror)?$queryerror:COS::lang( vars::v(COS::nvl($this->def['alerttitle'],'')) ),
				'refresh'=>$refresh,
				'pagination'=>$this->pagination,
				'myAjaxUrl'=>$listAjaxUrl,
				'horizontalscroll'=>$horizontalscroll,
			)
		);
		if (isset($queryerror)) {
			$httpCode=500;
		}
		return $vw;
	}
	
	protected function populateSearchblock() {
		$switchhtml=[];
		$inputhtml='';$resethtml='';
		$inputtitle=[];

		//if (isset($this->def['switch'])) {
			$switches=COS::ifset($this->def['switch'],array());
			$switchvar='';
			$swc=1;
			//dd($switches);
			foreach($switches as $switch) {
				$rv='';
				$asql=is_array($switch['sql']) ? $switch['sql'] : explode(';',vars::v($switch['sql']));
				$atitle=is_array($switch['title']) ? $switch['title'] : explode(';',vars::v($switch['title']));
				$avars=array();
				foreach($switch as $var=>$vardef) {
					if ($var=='sql' || $var=='title')
						continue;	// all other values are extra switch variables
					$avars[$var]=is_array($vardef) ? $vardef : explode(';',vars::v($vardef));	// new variable
				}
				$autosubmit='';
				if (COS::a2s($switch['autosubmit'],0,0))
					$autosubmit='onchange="submit();"';

				$itemnr=1;
				$swselected=COS::def(vars::v('$sw'.$swc),1);
				$selectedstyle= $swselected>1 ? ' style="background-color: #d7e0f5" ' : '';
				$rv.='<select class="form-control selectpicker" '.$autosubmit.' name="sw'.$swc.'" id="sw'.$swc.'" '.$selectedstyle.'>';
				//Log::info("Item $swc selected=".$swselected."\n");
				foreach ($atitle as $k=>$title) {
					// $k is index number of selected item in dropdown (0..n)
					if ($title) {
						$selected='';
						vars::setvar('sw'.$swc,$swselected);	// STORE CURRENT CHOSEN SELECTION
						//Log::info("setvar sw$swc to $swselected");
						if (vars::v('$sw'.$swc)==$itemnr) {
							$selected=' selected=selected ';
							if (!isset($asql[$k])) {
								// just ignore empty
								//throw new exception("former\pages\listpage: switch $k '".$title."' does not have an sql:".print_r($asql,true));
							}
							else
								$switchvar.=' '.$asql[$k];
							foreach($avars as $var=>$vardef) {
								// do addslashes in case we use the string somewhere in an SQL statement...
								//log::info("set swvar_$var to ".vars::v($vardef[$k]) );
								vars::setvar('swvar_'.$var,vars::v($vardef[$k]));	// set all extra variables as swvar_xxx
							}
						}
						$rv.='<option value="'.$itemnr.'" '.$selected.'>'.COS::lang($title).'</option>';
						$itemnr++;
					}
				}
				$rv.="</select>";	// note: space is important for spacing
				$switchhtml[]=$rv;
				//echo "<pre>";var_dump($switchhtml);echo "</pre>";
				$swc++;
			}
			// store switchvar
			vars::setvar('switch',$switchvar);	// full concatenated switch sql. Use: select * from table $!switch


			// store page
			//			if (isset($_GET['search'])) {
			//				// searchform posted, reset page
			//				vars::setvar('pg',0);
			//			}
			//			else
			vars::setvar('pg',COS::def(vars::v('$pg'),0));	// keep current page

			if (isset($this->def['searches'])) {
				$inputhtml='';$searchsql='';
				// new: allow searches to be defined
				foreach($this->def['searches'] as $search) {
					$id = $search['id'];
					$curvalue=vars::v('$'.$id);
					$sdefault = Cos::ifset($search['default'],'');
					if (!$curvalue)
						$curvalue=vars::v($sdefault);
					vars::setvar($id,$curvalue);	// remeber _GET value
					$title = Cos::ifset($search['title'],'nl::zoek in de lijst|en::search in the list');
					$sql = vars::v($search['sql']);
					$stype = Cos::ifset($search['type'],'text');	// text, date, number
					//$inputhtml .= '<div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label mdl-filter">';

					switch($stype) {
					case 'date':
						$html = '<input type="text" class="form-control" name="'.$id.'" data-datepicker="date" value="'.HTML::entities($curvalue).'"> ';
						break;
					default:
						$html = '<input type="text" class="form-control" name="'.$id.'" placeholder="'.COS::lang($title).'" value="'.HTML::entities($curvalue).'"> ';
						break;
					}
					$inputhtml .= '<div class="form-group">'.$html.'</div>';
					//$inputhtml .= '<label class="mdl-textfield__label" for="'.$id.'">'.COS::lang($title).'</label>';
					//$inputhtml .= '</div>';
					if ($curvalue) {
						// filled something. Use sql
						$searchsql.=" ".$sql." ";
					}
				}
				vars::setvar('searches',$searchsql);	// like $switch we have $searches
			}
			else {
				// remember _GET search value
				vars::setvar('search',vars::v('$search'));
				// the default search
				$placehldr=COS::lang(COS::ifset($this->def[ 'searchplaceholder' ], 'nl::zoek in de lijst|en::search in the list'));
				//$inputhtml = '<div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label mdl-filter">';
				$inputhtml .= '<input type="text" class="form-control" name="search" placeholder="'.$placehldr.'" value="'.HTML::entities(vars::v('$search')).'"> ';
				//$inputhtml .= '<label class="mdl-textfield__label" for="search">'..'</label>';
				// $inputhtml.='<span class="input-group-btn"><button type="submit" class="btn btn-primary">'.COS::lang('nl::zoeken|en::search').'</button></span>';
				//$inputhtml .= '</div>';
			}

			$this->data['switchhtml']=$switchhtml;
			$this->data['inputhtml']=$inputhtml;
			//$this->data['list']=$list;
		//}
		return '';	// NO searchblock
	}

	protected function populateTabBlock($active='list') {
		$this->data['tabs']=null;
		$tabs=COS::ifset($this->def['tabs'],[]);
		if (count($tabs)) {
			foreach($tabs as $k=>$tab) {
				switch($k) {
				case 'map':
					$this->data['tabs'][]=[ 'title'=>$tab, 'url'=> $this->getdef('pages.map.url',''), 'active' => ($active=='map')  ];
					break;
				case 'list':
					$this->data['tabs'][]=[ 'title'=>$tab, 'url'=> $this->getdef('pages.list.url',''), 'active' => ($active=='list')  ];
					break;
				default:
					$this->data['tabs'][]=[ 'title'=>$tab['title'], 'url'=> $tab['url'], 'active' => false  ];
					break;
				}
			}
		}

	}


	protected function addPaginationToSql($sql) {
		if ($this->isBigQuery)
			return $sql." limit {$this->pagination['limit']} offset {$this->pagination['offset']}";
		return $sql." limit {$this->pagination['offset']},{$this->pagination['limit']} ";
	}
	protected function rowsAndPagination($sql) {
		$this->rowsCounter($sql);

		$winheight=session('windowheight');
		$limit=30;
		if ($winheight) {
			// dynamic limit. Use inspector to get rowheight (32) than adjust remaining space to subtract from winheight
			$limit = floor(($winheight-295)/32);
			if ($limit<2)
				$limit=2;	// a bare minimum
			// \Log::info("winheight: $winheight so limit: $limit");
		}
		$this->pagination['limit']=$limit;
		$nrpages =  ceil($this->pagination['count']  / $limit);
		// pg = 0..nrpages-1
		$pg = Input::get('pg',0);
		if ($pg>=$nrpages)
			$pg=$nrpages-1;
		if ($pg<0)
			$pg=0;
		$this->pagination['offset']=$pg*$limit;
		$this->pagination['page']=$pg;
		$this->pagination['nrpages']=$nrpages;
	}

	protected function rowsCounter($sql) {
		// format: select .. as count from ..
		if ($countsql=$this->getdef('pages.list.countsql','')) {
			$countsql = vars::v($countsql, false);
		}
		else {
			if ($this->isBigQuery) {
				// auto-generate count sql
				if (preg_match('/select(.*?)from(.*)/i', $sql, $matches) && count($matches) >= 3) {
					$countsql = 'select count(*) count from '.$matches[2];
				}
				else {
					Log::error("listpage: Rowscounter no select/from matched in sql: $sql");
					return;
				}
			}
			else {
				// auto-generate count sql
				if (preg_match('/select(.*?)from(.*)/i', $sql, $matches) && count($matches) >= 3) {
					$countsql = 'select count(*) count from '.$matches[2];
				}
				else {
					Log::error("listpage: Rowscounter no select/from matched in sql: $sql");
					return;
				}
			}
		}
		// must have a "count" column
		$rows=$this->selectCached($countsql);
		if ($rows) {
			$firstrow=$rows[0];
			if (isset($firstrow->count))
				$this->pagination['count'] =$firstrow->count;
		}
		else
			Log::error("listpage: Rowscounter no rows or count column for sql: $countsql");
		//Log::info("listpage: RowsCounter: ".$this->pagination['count']." rows");
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
				'maxResults' => 1000,
				'useLegacySql' => false,
				'timeoutMs' => 180000,
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

	protected function addIdsToSelection($ids) {
		$curIds = $this->getSelection();
		$ids = array_values(array_unique(array_merge($ids, $curIds)));    // [1,2,3] & [3,4,5] => [1,2,3,4,5]
		$ids = array_slice($ids,0,20000);	// absolute max nr items
		$json_selection=json_encode($ids);					// format [1,2]
		$sql_selection = strtr($json_selection,"[]","()");	// format (1,2)
		vars::setvar('currentSelection',$json_selection);
		vars::setvar('currentSelectionSql',$sql_selection);
		vars::storevars();	// remember!
		return count($ids);
	}

	protected function addToSelection() {
		$jsonIds=Input::get('ids','[]');
		// add these ID's to current selection
		$ids=json_decode($jsonIds,true);
		$cnt=$this->addIdsToSelection($ids);
		return ['result'=>'OK','badgecount'=>$cnt];
	}

	protected function addAllToSelection() {
		$deflistsql="select id from ".$this->def['table']." where 1=1 ".COS::ifset($this->def['search'],'').' $!switch ';
		$this->cacheTime=$this->getdef('pages.list.cache', 'N');	// chache in minutes or N to disable cache. default OFF

		// select ID's sql:
		// 	pages.list.selectidsql, then the normal pages.list.sql than main sql then deflist
		$sql=vars::v($rawsql=$this->getdef('pages.list.selectidsql',$this->getdef('pages.list.sql',$this->getdef('sql',$deflistsql))),false);	// no DoEscape as this also escapes switches. Use ${search:e} to escape search
		$sql.=' limit 20000';	// absolute max
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
		$ids=[];
		foreach($rows as $row) {
			$ids[]=$row->id;
		}
		$cnt=$this->addIdsToSelection($ids);
		return ['result'=>'OK','badgecount'=>$cnt];
	}

	protected function deleteSelection() {
		vars::setvar('currentSelection','[]');
		vars::setvar('currentSelectionSql','()');
		vars::storevars();	// remember!
		return ['result'=>'OK','badgecount'=>0];
	}

	protected function saveSelection() {
		$saveName=Input::get('name','No-name');
		$sql=$this->getdef('selection.saveselection.sql',"");
		if ($sql) {
			$sql=vars::v($sql);
			Log::warning("saving selection: $sql");
			DB::insert($sql);

		}
		return ['result'=>'OK', 'reload'=>($this->getdef('selection.saveselection.reload',"N")=='Y')];
	}

	protected function goIconSelection() {
		$extrabuttonId=Input::get('extrabutton',0);
		$extraButtons=$this->getdef('selection.extrabuttons',[]);
		if (isset($extraButtons[$extrabuttonId])) {
			$extraButton=$extraButtons[$extrabuttonId];
			if (isset($extraButton['sql'])) {
				// run query first..
				$sql=$extraButton['sql'];
				$sql=vars::v($sql);
				Log::warning("Execute extrabutton selection sql: $sql");
				DB::insert($sql);	// might also be an update or whatever..
				$lastid = DB::getPdo()->lastInsertId();
				vars::setvar('lastinsertID',$lastid);
			}
			if (isset($extraButton['after']) ) {
				switch ($extraButton['after']) {
				case 'clear':
					$this->deleteSelection();
					break;
				}
			}
			if (isset($extraButton['url'])) {
				return redirect(vars::v($extraButton['url']));    // can use $!lastinsertID
			}
			return redirect()->back();
		}
		return null;
	}

	protected function getSelection() {
		if (vars::hasvar('currentSelection')) {
			return json_decode(vars::getvar("currentSelection"), true);
		}
		return [];
	}

	protected function populateSelectionBox() {
		$this->data['selectedBadgeCnt']=count($this->getSelection());
		//dd($this->getdef('selection',[]));
		$this->data['selectedExtraButtons']= $this->getdef('selection.extrabuttons',[]);
		//			['url'=>'http://mmp/dashboard?sel=active%20selection', 'icon'=>'dashboard', 'title'=>'dash'],
		//			['url'=>'http://mmp/campaigns/addcampaign?sel=active%20selection', 'icon'=>'mobile_screen_share', 'title'=>'campaign'],
		//		];
	}
	/**
	 * ajax switchboard
	 * @param null $directresponse
	 * @param int $httpCode
	 * @return bool
	 */
	protected function handleAjax(&$directresponse=null, &$httpCode=0) {
		switch(Input::get('ajax', '')) {
		case 'addtoselection':
			$directresponse = $this->addToSelection();
			return true;
		case 'addalltoselection':
			$directresponse = $this->addAllToSelection();
			return true;
		case 'deleteselection':
			$directresponse = $this->deleteSelection();
			return true;
		case 'saveselection':
			$directresponse = $this->saveSelection();
			return true;
		case 'goiconselection':
			$directresponse = $this->goIconSelection();
			return true;
		}
		return false;
	}
}