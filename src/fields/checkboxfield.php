<?php

namespace Rvwoens\Former\fields;

use Laravel, Rvwoens\Former\Cos, Rvwoens\Former\Vars, \Hash,\Input;

// custom field attribute for list view or non editable field:
//  display - Not provided: show a html disabled checkbox with or without checkmark
//  display - single value: only show this text value when checkbox is checked
//  display - multiple values: show first value when not checked, second value when checked

// base field class
class checkboxfield extends editablefield {

	public function getbaseinput($row, &$gridsize) {
		$gridsize=array_get($this->def,'size','1');

		if (preg_match('/[0-9]+px/',$gridsize,$match)) {
			$divattr=' style="width:'.$gridsize.'" ';	// size=20px format
			$restattr=' ';
		}
		else {
			$divattr = ' class="col-md-'.$gridsize.'" ';    // class="col-md-2" format
			$restattr = ' class="mdl-cell mdl-cell--'.(8-intval($gridsize)).'-col" ';
		}

		$txtfields = "<div class='col-md-$gridsize'>".
			"<input style='margin:1px;' class=' form-control' type='checkbox' name='".
			$this->formfieldname."' value='Y' ". ($this->val($row)==$this->getOnOffValue(true) ? 'checked' : '').
			($this->isReadOnly() ? " disabled='disabled'": '').">".
			"</div>";
		return $txtfields;
	}
	public function postval($row=null) {
		return $this->getOnOffValue( Input::get($this->name, $this->val)==$this->getOnOffValue(true) );
 	}

	public function display($row=null) {
		// display its VALUE, based on display attribute or a disabled check box
		$val=($this->val($row)==$this->getOnOffValue(true));
		if (isset($this->def['display'])) {
			$v=$this->def['display'];

			if (!is_array($v)) { // if we only provide one value, we return empty string when checkbox not checked
				return ($val ? $v : "");
			}
			if (count($v)>1) { // return first or second attribute value
				return ($val ? $v[1] : $v[0]);
			}
		}
		// if no display attribute is provided, return disabled checkbox html
		return "<input type='checkbox' disabled='disabled' ".($val ? "checked":"").">";
	}

	public function getOnOffValue($on=true) {
		// default Y/N
		return isset($this->def[$on?'on':'off'])? $this->def[$on?'on':'off'] : ($on?'Y':'N');
	}
}