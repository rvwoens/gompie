<?php 

namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Former,Rvwoens\Former\Cos, Rvwoens\Former\vars, \Form;

// hiddenfield is a database field that is NOT shown on screen
// can have FIXED value or calculated value
class hiddenfield extends editablefield {

	// overrules getinput: Not your normal card field.
	public function getinput($row) {
		return Form::hidden($this->formfieldname,$this->val($row));
	}
	public function getbaseinput($row) {
		return Form::hidden($this->formfieldname,$this->val($row));
	}
}