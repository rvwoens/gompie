<?php namespace Rvwoens\Former\Pages;

use Input;
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
use Rvwoens\Former\Cos;
use Rvwoens\Former\Vars;
use Exception;
use Form;
/**
 * Class mappage
 * @package Rvwoens\Former\Pages
 * @version 1.0
 * @Author Ronald van Woensel <rvw@cosninix.com>
 */
class mappage extends listpage {
	private $options;

	public function show($forceid=0,&$directresponse=null, &$httpCode=0) {
		$this->setOptions();
		// ajax-supported page. Check for ajax calls
		if ($this->handleAjax($directresponse,$httpCode))
			return null;	// handled
		$row=\Rvwoens\Former\rows\row::make($this->def,'list');	// create a listrow

		$this->setsessionvars('map');	// optional. stores pages.map.setsessionvars variables
		//$fields=$row->getFields();
		$this->populateSearchblock();
		$this->populateTabBlock('map');		// in $data
		$this->populateSelectionBox();			// in $data
		$mapAjaxUrl=$this->getdef('pages.map.url','');

		// keep last position of map when re-entering
		if (vars::v('$map.clat') && vars::v('$map.clng') && vars::v('$map.zoom'))
			$initpos= ['lat'=>vars::v('$map.clat'),'lng'=> vars::v('$map.clng'), 'zoom'=>vars::v('$map.zoom')];
		else
			$initpos= $this->getOption('init',[]);

		vars::setvar('maplist','map');	// remember we are on map
		vars::storevars();	// remember filters etc
		$vw=View::make('former::pages/map',
			array(
				'initpos'=>$initpos,
				'mapAjaxUrl'=>$mapAjaxUrl,
				'myAjaxUrl'=>$mapAjaxUrl,
				'layout' => $this->getdef('layout') ?: [],
				'data'=>$this->data,
				'title'=>COS::lang( vars::v(COS::nvl($this->def['title'],'lijst')) ),
				'extrabuttons'=>$this->getdef('extrabuttons'),
				'subtitle'=>COS::lang( vars::v(COS::nvl($this->def['subtitle'],'')) ),
				'alerttitle'=>isset($queryerror)?$queryerror:COS::lang( vars::v(COS::nvl($this->def['alerttitle'],'')) ),
			)
		);
		if (isset($queryerror)) {
			$httpCode=500;
		}
		return $vw;
	}

	private function mapBoundPoints() {

		vars::setvar('map.clat',Input::get('clat',0));
		vars::setvar('map.clng',Input::get('clng',0));
		vars::setvar('map.zoom',Input::get('z',0));
		vars::storevars();	// remember filters etc
		//
		// default contains lat/lng column
		$deflistsql="select id,lat,lng from ".$this->def['table']." where 1=1 ".COS::ifset($this->def['search'],'').' $!switch ';
		$this->cacheTime=$this->getdef('pages.map.cache', '720');	// chache in minutes or N to disable cache
		$limit=intval($this->getOption('limit',800));
		// pages: list: sql:.... OR main sql:... OR the default list sql
		$sql=vars::v($rawsql=$this->getdef('pages.map.sql',$this->getdef('sql',$deflistsql)),false);	// no DoEscape as this also escapes switches. Use ${search:e} to escape search

		// now we know offset and limit
		$sql.=" limit 0,$limit ";

		try {
			$rows = $this->selectCached($sql);

		}
		catch (QueryException $qe) {
			Log::error($qe->getMessage());
			return ['result'=>'error','error'=> $qe->getMessage() ];
		}
		// icons
		$icons=[];$legend=[];
		if ($this->getdef('pages.map.icons',null)) {
			foreach($this->getdef('pages.map.icons') as $icon) {
				$icondef=['url'=>$icon['url']];
				if (isset($icon['anchor']))
					$icondef['anchor']=$icon['anchor'];
				if (isset($icon['urlselected']))
					$icondef['urlselected']=$icon['urlselected'];
				$icons[$icon['value']]=$icondef;	// icons: { beacon: {url: xxx, anchor: xxx, urlselected: xxx}, other:  ..}
				if (isset($icon['legend']))
					$legend[] = ['p'=>$icon['value'], 'title'=>$icon['legend']];
			}
		}
		$rv=[];
		$gotAll=(count($rows)<$limit);	// did not reach limit, so we have all

		if (!$gotAll) {
			$legend=[];	// hide legend
		}
		foreach($rows as $row) {
			// c= coordinate p=pincolor i=ID
			if (isset($icons['partial']) && !$gotAll)
				$icon='partial';
			else
				$icon= isset($icons[$row->icon]) ? $row->icon : 'default';
			$rv[]=['c'=>[$row->lat,$row->lng],'p'=>$icon,'i'=>$row->id];
		}
		return ['result'=>'OK','all'=>(count($rows)<800),'icons'=>$icons, 'markers'=>$rv, 'legend'=>$legend];
		// c= coordinate p=pincolor i=ID
		//return [ ['c'=>[53.3,3.4],'p'=>'blue','i'=>1233 ]];
	}

	private function showAccountInInfoWindow() {
		$id=Input::get('id',0);
		if (!$id)
			return '';
		vars::setvar('id',$id);	// store! so we can use it in yaml
		// \Log::info("showAccountInInfoWindow: showing card for id=$id");
		$defmapinfosql="select * from ".$this->def['table']." where id=?";
		if ($this->getdef('pages.map.infowinsql',''))
			$rows=DB::select($sql=vars::v($this->getdef('pages.map.infowinsql')));
		else
			$rows=DB::select($sql=vars::v($defmapinfosql),array($id));
		if ($rows==null)
			return View::make('former::pages.partials.mapinfowinerror', [
				'error'=>'Card with id $id not found'
			]);
		else
			$row=$rows[0];
		$maprow=\Rvwoens\Former\rows\row::make($this->def,'mapinfowin');
		$listfields=$maprow->getFieldNames('pages.map.infowinfields','pages.map.skipinfowinfields');
		$showfields=array();
		foreach($listfields as &$colname) {	// colname by reference as this allows $this->cardfields to be modified
			$field=$maprow->findfield($colname,true);	// true->create field if not exists
			if ($field==null)
				throw new exception("former\field: Field $colname not found");
			$showfields[]=$field;
			$heads[]=COS::lang($field->title());
		}
		$maprow->oShowfields=$showfields;
		return $maprow->show($row);

	}

	private function setOptions() {
		//     options: {limit: 900, deflat: 52.205692, deflng: 5.447477, defzoom: 7}
		$this->options=$this->getdef('pages.map.options', ['limit'=>900,'init'=> ['lat'=>52.205692, 'lng'=>5.447477, 'zoom'=>7]]);

	}
	private function getOption($option,$def) {
		if (isset($this->options[$option]))
			return $this->options[$option];
		return $def;
	}
	/**
	 * ajax switchboard
	 * @param null $directresponse
	 * @param int $httpCode
	 * @return bool
	 */
	protected function handleAjax(&$directresponse=null, &$httpCode=0) {
		switch(Input::get('ajax', '')) {
		case 'bounds':
			$directresponse = $this->mapBoundPoints();
			return true;
		case 'markerclick':
			$directresponse = $this->showAccountInInfoWindow();
			return true;
		case 'addtoselection':
			$directresponse = $this->addToSelection();	// see listpage
			return true;
		}
		return parent::handleAjax($directresponse, $httpCode);
	}
}