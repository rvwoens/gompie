<?php namespace Rvwoens\Gompie\fields;
use Laravel,Bootstrapper,Former,Rvwoens\Former\COS, Former\vars;

// A separator just shows a text on the screen, not database related
class separatorfield extends field {

	// public function getbaseinput($row) {
	// 	return '';
	// }
	// this field has no value
	public function val($row=null) {
		return '';
	}
	// overrules getinput: Not your normal card field.
	public function getinput($row) {
		if (!$this->testShowCondition($formgroupattr))
			return '';	// invisible
		//
		//
		//if (isset($this->def['showcondition']) || isset($this->def['visibility'])) {
		//	$showc=$this->def[isset($this->def['showcondition']) ? 'showcondition':'visibility'];
		//	if ( is_array($showc) && isset($showc['field']) && isset($showc['expr']) ) {
		//		// dynamic showc  {field:..., expr: "javascript eval"}
		//		$formgroupattr='data-dynvisfield="'.$showc['field'].'" data-dynvisexpr="'.$showc['expr'].'"';
		//	}
		//}
		$urlbut=$this->getUrlButton('C');
		$rv="<div class='mdl-cell mdl-cell--12-col' style='margin-top:20px;margin-bottom:12px;'>";
			$rv.="<legend class=\"mdl-cell--12-col\" style='font-weight:300;font-size:18px;border-bottom:1px solid #ccc;padding-bottom: 4px;'>";
			$rv.= COS::lang($this->title());
			$rv.= $urlbut['html'] ? '<span style="position:relative; bottom: 3px;">'.$urlbut['html'].'</span>' : '';
			if (isset($this->def['helptext'])) {
				$hbsize=12;	// full width UNDER the field
				$rv.="<div class='pull-right'><span class='help-block help-block-inline'>".COS::lang($this->def['helptext']).'</span></div>';
			}
			$rv.= "</legend>";
		$rv.="</div>";
		return $rv;
	}
}