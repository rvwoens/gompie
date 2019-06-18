<?php namespace Rvwoens\Former\Pages;

use Illuminate\Database\QueryException;
use Cache;
use Input;
use Validator;
use Session;
use Redirect;
use URL;
use Response;
use Log;
use DB;
use View;
use HTML;
use Rvwoens\Former\Cos;
use Rvwoens\Former\Vars;
use Exception;
use Form;

// a page is a viewable page on the screen with cards, lists and other elements
class simplelistpage extends page {
	public $data=array();
	protected $pagination = [ 'limit'=>30, 'count'=>0, 'offset'=>0, 'page'=>0 ];
	protected $cacheTime  = 720;

	public function show($forceid=0,&$directresponse=null, &$httpCode=0, $formgroupattr='') {
		$row=\Rvwoens\Former\rows\row::make($this->def,'list');	// create a listrow
		// do we need to set sessionvars?
		$this->setsessionvars('list');
		//$fields=$row->getFields();
		$this->populateSearchblock();
		$deflistsql="select * from ".$this->def['table']." where 1=1 ".COS::ifset($this->def['search'],'').' $!switch ';
		$this->cacheTime=$this->getdef('pages.list.cache', 'N');	// chache in minutes or N to disable cache. default OFF

		// pages: list: sql:.... OR main sql:... OR the default list sql
		$sql=vars::v($rawsql=$this->getdef('pages.list.sql',$this->getdef('sql',$deflistsql)),false);	// no DoEscape as this also escapes switches. Use ${search:e} to escape search
		if (substr(vars::v('$debug'),0,1)=='Y' ) {
			echo "<pre>$sql</pre><br><pre>$rawsql</pre><br>\n";
		}
		$this->rowsAndPagination($sql);
		// now we know offset and limit
		$sql.=" limit {$this->pagination['offset']},{$this->pagination['limit']} ";

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
			if ($this->hasToken('pages.new.token'))
				//$this->data['newhtml']=HTML::link($this->getdef('pages.new.url'),$this->getdef('pages.new.button','New'),array('class'=>'btn btn-default btn-xs'));
				$this->data[ 'newhtml' ] = '<a href="'.$this->getdef('pages.new.url').'" class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"><i class="material-icons">add</i></a>';

		}
		if ($this->getdef('options.excel','N')=='Y') {
			if ($this->hasToken('pages.excel.token'))
				$this->data['excelhtml']=HTML::link($this->getdef('pages.excel.url'),$this->getdef('pages.excel.button','Excel'),array('class'=>'btn btn-success spinnerdl'));
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
		vars::storevars();	// remember!
		$cardurl=null;
		if ($this->getdef('pages.list.rowicon','')=='eye')
			$cardurl= ($this->getdef('options.edit','Y')=='Y' && $this->hasToken('pages.edit.token')) ?
				$this->getdef('pages.edit.url',''):
				null;
		$refresh=$this->getdef('pages.list.refresh',0);

		$vw=View::make('former::pages/simplelist',
			array(
				'layout' => $this->getdef('layout') ?: [],
				'heads'=>$heads,
				'rows'=>$rows,
				'row'=>$row,
				'cardurl'=>$cardurl, // $this->def['cardurl'],
				'data'=>$this->data,
				'title'=>COS::lang( vars::v(COS::nvl($this->def['title'],'lijst')) ),
				'subtitle'=>COS::lang( vars::v(COS::nvl($this->def['subtitle'],'')) ),
				'alerttitle'=>isset($queryerror)?$queryerror:COS::lang( vars::v(COS::nvl($this->def['alerttitle'],'')) ),
				'refresh'=>$refresh,
				'pagination'=>$this->pagination,
				'formgroupattr'=>$formgroupattr,
			)
		);
		if (isset($queryerror)) {
			$httpCode=500;
		}
		return $vw;
	}

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
				$avars=array();
				foreach($switch as $var=>$vardef) {
					if ($var=='sql' || $var=='title')
						continue;	// all other values are extra switch variables
					$avars[$var]=is_array($vardef) ? $vardef : explode(';',vars::v($vardef));	// new variable
				}
				$autosubmit='';
				if (COS::a2s($switch['autosubmit'],0,1))
					$autosubmit='onchange="submit();"';
				$rv.='<select class="form-control selectpicker" '.$autosubmit.' name="sw'.$swc.'">';
				$itemnr=1;
				$swselected=COS::def(vars::v('$sw'.$swc),1);
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
			vars::setvar('search',vars::v('$search'));
			// store page
			if (isset($_GET['search'])) {
				// searchform posted, reset page
				vars::setvar('pg',0);
			}
			else
				vars::setvar('pg',COS::def(vars::v('$pg'),0));	// keep current page
			//$inputhtml='<div class="input-group" >';
			$inputhtml='<input type="text" name="search" placeholder="'.COS::lang(COS::ifset($this->def['searchplaceholder'],'nl::zoek in de lijst|en::search in the list')).'" class="form-control" style="width:100%" value="'.HTML::entities(vars::v('$search')).'"> ';
			//$inputhtml.='<span class="input-group-btn"><button type="submit" class="btn btn-primary">'.COS::lang('nl::zoeken|en::search').'</button></span>';
			//$inputhtml.='</div>';

			$resethtml.='<a class="btn btn-default" href="?reset=Y"><i class="icon-remove"></i> reset </a> ';

			// store switchvar
			vars::setvar('switch',$switchvar);	// full concatenated switch sql. Use: select * from table $!switch


			$this->data['switchhtml']=$switchhtml;
			$this->data['inputhtml']=$inputhtml;
			$this->data['resethtml']=$resethtml;
			//$this->data['list']=$list;
		//}
		return '';	// NO searchblock
	}


	protected function rowsAndPagination($sql) {
		$this->rowsCounter($sql);
		$limit=30;// fixed for now..
		$this->pagination['limit']=$limit;
		$nrpages =  ceil($this->pagination['count']  / $limit);
		// pg = 0..nrpages-1
		$pg = Input::get('subpg',0);
		if ($pg>=$nrpages)
			$pg=$nrpages-1;
		if ($pg<0)
			$pg=0;
		$this->pagination['offset']=$pg*$limit;
		$this->pagination['page']=$pg;
	}
	protected function rowsCounter($sql) {
		if ($countsql=$this->getdef('pages.list.countsql','')) {
			$countsql = vars::v($countsql, false);
		}
		else {
			// auto-generate count sql
			if (preg_match('/select(.*?)from(.*)/i',$sql,$matches) && count($matches)>=3) {
				$countsql = 'select count(*) count from '.$matches[2];
			}
			else {
				Log::error("listpage: Rowscounter no select/from matched in sql: $sql");
				return;
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
		// cache = N or false to disable (yaml N results in false value) or minutes
		if ($this->cacheTime == 'N' || $this->cacheTime === false)
			$rows = DB::select($sql);
		else {
			$rows = Cache::remember($sql, intval($this->cacheTime), function() use ($sql) {
				Log::info("Not cached Query: $sql");
				$rv = DB::select($sql);

				return $rv;
			});
		}
		return $rows;
	}
}