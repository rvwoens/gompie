<?php

namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Rvwoens\Former,Rvwoens\Former\Cos, Rvwoens\Former\Vars,\exception;

class colorpickerfield extends editablefield {

	public function getbaseinput($row,&$gridsize) {
		$gridsize=array_get($this->def,'size','6');

		if ($this->isReadOnly()) {
			// NO SINGLE QUOTES!!
			return "<div class=\"col-md-$gridsize\"><p class=\"form-control-static \">".$this->display($row)."</p></div>";	// READONLY
		}

		$style=array('style'=>'margin-right:5px;','class'=>' form-control minicolors');

		if (isset($this->def['popover'])) {
			$style['data-toggle']='popover';
			if (is_array($this->def['popover'])) {
				$style['data-title']=COS::lang($this->def['popover']['title']);
				$style['data-content']=COS::lang($this->def['popover']['content']);
				$style['data-placement']=COS::ifset($this->def['popover']['placement'],'top');
			}
			else {
				$style['data-title']=COS::lang("nl::Informatie over dit veld|en::Information about this field");
				$style['data-content']=COS::lang($this->def['popover']);
			}
			$style['data-trigger']='focus';
		}

		if (isset($this->def['placeholder']))
			$style['placeholder']=array_get($this->def,'placeholder');

		$txtfields="<div class=\"col-md-$gridsize\">".
				   \Form::text($this->formfieldname,
							   $this->display($row),
							   $style
				   )."</div>";

		return $txtfields;
	}

}