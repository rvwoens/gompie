<?php

namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Former,Rvwoens\Former\Cos, Former\vars;

// An info field just shows a DIV-ALERT text. Special case of separator
class infofield extends field {

	// public function getbaseinput($row) {
	// 	return '';
	// }
	// this field has no value
	public function val($row=null) {
		return '';
	}
	// overrules getinput: Not your normal card field.
	public function getinput($row) {
		$formgroupattr='';
		if (!$this->testShowCondition($formgroupattr))
			return '';
		$gridsize=array_get($this->def,'size','12');
		if (preg_match('/[0-9]+px/',$gridsize,$match)) {
			$divattr=' style="width:'.$gridsize.'" ';	// size=20px format
		}
		else
			$divattr=' class="col-md-'.$gridsize.'" ';	// class="col-md-2" format

		$infotxt=Cos::lang(array_get($this->def,'info',''));
		switch(array_get($this->def,'style','wide')) {
		case 'normal':
			$info='<label class="col-md-2 control-label" for="'.$this->name.'">'.Cos::lang($this->title()).'</label>'.
				'<div class="col-md-10"><div class="row">
					<div '.$divattr.'><div class="alert alert-warning">'.$infotxt."</div></div>".
				"</div></div>";
			break;
		case 'wide':
		default:
			$info="<div class=\"alert alert-warning\">".$infotxt."</div>";	// full width title in alert
		}

		return "<div class=\"form-group\" $formgroupattr >".$info."</div>";

	}
}