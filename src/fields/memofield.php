<?php 

namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Rvwoens\Former,Rvwoens\Former\Cos, Rvwoens\Former\Vars,\exception;

// base field class
class memofield extends editablefield {

	public function getbaseinput($row) {
		$gridsize=array_get($this->def,'size','6');
		$fsize=array_get($this->def,'size','span6');

		// if (COS::ifset($this->def['readonly'],'N')=='Y') {
		// 	return "<span class=\"input-$fsize uneditable-input\">".$this->display($row)."</span>";	// READONLY
		// }
		$style=array('style'=>'margin-right:5px;','class'=>' form-control');
		if ($this->isReadOnly()) {
			// NO SINGLE QUOTES!! 
			return "<div class=\"col-md-$gridsize\"><p class=\"form-control-static \">".$this->display($row)."</p></div>";	// READONLY
		}
		if (isset($this->def['popover'])) {
			$style['data-toggle']='popover';
			if (is_array($this->def['popover'])) {
				$style['data-title']=$this->def['popover']['title'];
				$style['data-content']=$this->def['popover']['content'];
				$style['data-placement']=COS::ifset($this->def['popover']['placement'],'top');
			}
			else {
				$style['data-content']=$this->def['popover'];	
			}	
			$style['data-trigger']='focus';				
		}
		
		if (isset($this->def['placeholder']))
			$style['placeholder']=array_get($this->def,'placeholder');

		$style['rows']=COS::ifset($this->def['rows'],3);
		$txtfields="<div class=\"col-md-$gridsize\">".
			\Form::textarea($this->formfieldname,
				$this->display($row),
				$style
		)."</div>";

		return $txtfields;		
	}
	
}