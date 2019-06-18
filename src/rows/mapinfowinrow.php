<?php
namespace Rvwoens\Former\rows;

use \Input,\Validator,\Session,\Redirect,\URL,\DB,\View,Bootstrapper,Rvwoens\Former\FormerObject,Rvwoens\Former\vars,\HTML;

// A listrow shows the card in a table <TR>
class mapinfowinrow extends row {
	public $oShowfields=null;


	public function show($row) {
 		vars::setvar('id',$row->id);
		vars::setvar('cid',FormerObject::encodeId($row->id));
		if (!$this->oShowfields)
			$this->oShowfields=$this->fields;
		foreach($this->fields as $field) {
			/** @var \Rvwoens\Former\fields\field $field */
			$field->setvar($row);	// first process ALL fields (not ONLY showfields)
		}
		foreach($this->oShowfields as $field) {
			//if (isset($this->def['listfields']) && array_search($field->name(),$this->def['listfields'])===false)
			//	continue;	// NOT in the list!
			/** @var \Rvwoens\Former\fields\field $field */
			$inps[]=$field->getListColumn($row);
			$fields[]=$field;
			//$field->setvar($row);
		}
		$cardurl=$this->getdef('options.edit','Y')=='Y'?$this->getdef('pages.edit.url'):null;
		$rowurl=$this->getdef('pages.list.rowurl',	$cardurl);
		// click whole row or just icon?
		if ($this->getdef('pages.list.rowicon','')=='eye')
			$rowurl=null;
		else
			$cardurl=null;
		if (!$this->hasToken('pages.edit.token')) {
			$rowurl=$cardurl=null;	// no token. No edit!
		}
		return View::make('former::pages.partials.mapinfowin', [
				'row' => $row,
				'inps'=>$inps,
				'fields'=>$fields,
				'id'=>$row->id,
				'rowurl'=>$rowurl,
			]
		);
//		return View::make('former::rows/list',
//			array(
//				'inps'=>$inps,
//				'fields'=>$fields,
//				'row'=>$row,
//				'cardurl'=>$cardurl,
//				'rowurl'=>$rowurl,
//				 // $this->def['cardurl']
//			)
//		);
	}


}