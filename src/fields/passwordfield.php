<?php

namespace Rvwoens\Former\fields;

use Laravel, Rvwoens\Former\Cos, Rvwoens\Former\Vars, \Hash;

// base field class
class passwordfield extends editablefield {

	public function getbaseinput($row, &$gridsize) {

		$gridsize = array_get($this->def, 'size', '6');

		$bullets = (COS::ifset($this->def['bullets'], 'Y')=='Y');
		if ($this->isReadOnly()) {
			return "<div class='col-md-$gridsize'><p class=\"form-control-static \">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</p></div>";    // READONLY
		}
		// $style=array('style'=>'margin-right:5px;','class'=>' form-control','type'=>'password');
		// if (isset($this->def['placeholder']))
		// 	$style['placeholder']=array_get($this->def,'placeholder');

		//on empty record, do not set the DONOTUPDATE value because it overrides the old input (on form feedback)
		$old = is_null($row) ? $this->display($row) : 'DOnotUPDATE47!';	// use a value that will not fail on STRONG password values

		$txtfields = "<div class='col-md-$gridsize'>".
					 "<input style='margin-right:5px;' class=' form-control' type='password' name='".
					 $this->formfieldname."' value='".($bullets ? $old : '')."'>".
					 // \Form::password($this->formfieldname,
					 // 		//$bullets?array('value'=>'DONOTUPDATE'):'', // $this->display($row),
					 // 		$style
					 // ).
					 "</div>";
		return $txtfields;
	}

	/**
	 * Get the update or insert sql in the form field=>value
	 */
	public function getUpdateSql($do) {
		// return a default update array like 'field'=>value
		if (($rv = $this->getDefUpdateSql($do))!==null)
			return $rv ? $rv : array();    // overridden! (if empty-> do not update) -> even for READONLY=Y fields!!!
		if ($this->isReadOnly())
			return array();    // do not update readonly fields
		if ($do=='update'&&trim($this->postval())=='DOnotUPDATE47!')
			return array();    // do NOT update an empty value or the special Do-not-update key
		//echo $this->name." wordt gewijzigd in ".$this->postval();exit;
		return array($this->name => Hash::make($this->postval()));    // use HASH value for passwords
	}
}