<?php 

namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Rvwoens\Former,Rvwoens\Former\Cos, Rvwoens\Former\Vars,\exception;

// HTML field - allow "format" that defines the output format. 
// This is a READONLY field 
class htmlfield extends field {
	public function getbaseinput($row) {
		return vars::v($this->def['format']);	
	}
	// display value defaults to Db value
	public function display($row=null) {
		if (isset($this->def['html']))
			return vars::v($this->def['html']);
		return vars::v($this->def['format']);
	}
}