<?php namespace Rvwoens\Former\Pages;

use \Input,\Validator,\Session,\Redirect,\URL,\Log,\DB,\View, Rvwoens\Former\Cos, Rvwoens\Former\vars, Rvwoens\Former\rows\row;

// a page is a viewable page on the screen with cards, lists and other elements
class cardpage extends page {
	
	public function show($forceid=0,&$directresponse=null, &$httpCode=0) {
		/** @var \Rvwoens\Former\Rows\cardrow $rowObj */
		$rowObj=row::make($this->def,'card');	// create a card
		$id=$forceid?$forceid:\Rvwoens\Former\FormerObject::decodeId();	//Input::get('id');
		vars::setvar('id',$id);	// store! so we can use it in yaml
		
		// \Log::info("cardpage: showing card for id=$id");
		if ($id) {
			$defcardsql="select * from ".$this->def['table']." where id=?";
			if ($this->getdef('pages.card.sql',''))
				$rows=DB::select($sql=vars::v($this->getdef('pages.card.sql')));
			else	
				$rows=DB::select($sql=vars::v($defcardsql),array($id));
			//Log::info("Cardpage: $sql -> ".($rows?"result!":"leeg"));
			//echo "Cardpage: $sql -> ".($rows?"result!":"leeg");
			//$row=DB::first("select * from ".$this->def['table']." where id=?",$id);
			if ($rows==null)
				return View::make('former::error')->with('msg',"Sorry, card $id can not be found");
			else
				$row=$rows[0];
		}
		else if ($id===0) { // false or null
			if ($this->getdef('options.new','N')=='Y')			
				$row=null;
			else
				return View::make('former::error')->with('msg',"Sorry, new cards not allowd");				
		}	
		else
			return View::make('former::error')->with('msg',"Sorry, card $id can not be found");
		// did we define a backurl?
		$listurl=vars::v($this->getdef('pages.edit.backurl'));	// can be 'hidden' value
		if (!$listurl) {
			if ($this->getdef('options.map', 'N')=='Y' && vars::getvar('maplist')=='map')
				$listurl=$this->getdef('pages.map.url');
			else
				$listurl = ($this->getdef('options.list', 'Y') == 'Y' ?
					$this->getdef('pages.list.url') :
					NULL);
		}
		return View::make('former::pages/card',
			array(
				'rowdata'=>$rowObj->show($row),		// this is the actual card html
				'row'=>$row,
				'listurl'=>$listurl,
				'delcardurl'=>$this->getdef('options.del','N')=='Y' && $row ? $this->getdef('pages.del.url') : null,
				'id'=>$id,
				'title'=>COS::lang(vars::v(COS::ifset($this->def['cardtitle'],COS::ifset($this->def['title'],"kaart ".($id?$id:'new'))))),
				'subtitle'=>COS::lang( vars::v(COS::nvl($this->def['cardsubtitle'],COS::nvl($this->def['subtitle'],''))) ),
				'extrabuttons'=>$this->getdef('pages.edit.extrabuttons'),
				'formclass'=>$this->getdef('pages.edit.formclass','form-horizontal form-condensed submitspinner'),
				'formtype'=>$rowObj->hasFileUpload() ? 'open_for_files' : 'open',	// multipart if there is a fileupload somewhere
				'readonly'=>($this->getdef('pages.card.readonly','N')=='Y')
			)
		);
		//return $row->page(Input::get('id'));
	}
	
}