<?php 
namespace Rvwoens\Former\rows;

use \Input,\Validator,\Session,\Redirect,\URL,\DB,\View,Bootstrapper,Rvwoens\Former\FormerObject,Rvwoens\Former\vars,\HTML;

// A listrow shows the card in a table <TR>
class excelrow extends row {
	public $oShowfields=null;	
	
	
	public function show($row) {	
 		vars::setvar('id',$row->id);
		vars::setvar('cid',FormerObject::encodeId($row->id));
		if (!$this->oShowfields)
			$this->oShowfields=$this->fields;
		foreach($this->oShowfields as $field) {
			//if (isset($this->def['listfields']) && array_search($field->name(),$this->def['listfields'])===false)
			//	continue;	// NOT in the list!
			$fd=$field->display($row);
			$fd=str_replace('<br>',"\n",$fd);
			$inps[]=$fd;
		} 
		return $inps;
	}


}