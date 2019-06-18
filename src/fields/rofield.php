<?php 
namespace Rvwoens\Former\fields;
//use Former;
// base field class
class rofield extends field {

	// generate this form's input field
	public function getbaseinput($row=null,&$gridsize) {
		// base class is readonly field
		$formgroupattr='';
		if (!$this->testShowCondition($formgroupattr,$row))
			return '';	// invisible
		$gridsize=array_get($this->def,'size','6');

		$style=array('style'=>'margin-right:5px;');
		if (isset($this->def['html']))
			return "<span style='display: inline-block; margin-top:5px;' $formgroupattr>".$this->display($row)."</span>";
		return "<div class='col-md-$gridsize' $formgroupattr><p class=\"form-control-static \">".$this->display($row)."</p></div>";
	}	


}