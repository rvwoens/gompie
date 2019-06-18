<?php namespace Rvwoens\Former\fields;

use Laravel,Bootstrapper,Former,Rvwoens\Former\Cos, Rvwoens\Former\Vars, \DB, \Input, Rvwoens\Former\FormerObject, Rvwoens\Former\Pages\page;

/**
 * Class subformer - like a daughter field but showing a list of another former with link to cards
 * @package Rvwoens\Former\fields
 * @version 1.0
 * @Author Ronald van Woensel <rvw@cosninix.com>
 */
class subformerfield extends field {

	public function getbaseinput($row) {
		if (!isset($this->def['former']) || !isset($this->def['formurl']) || !isset($this->def['name']) || !isset($this->def['prefix'])) {
			return "Subformer field ERROR: former/formurl/name/prefix not set";
		}
		$name=$this->def['name'];
		$prefix=$this->def['prefix'];
		$subformer = new FormerObject(['formurl'=>$this->def['formurl']]);
		$subformer->loadFile($this->def['former']);	// also loads vars from session..

		if (is_object($row)) {
			vars::setvar('parent.'.$prefix.'.id', $row->id);
			vars::setvar('parent.'.$prefix.'.cid', FormerObject::encodeId($row->id));
			if (isset($this->def[ 'subdescriptionfield' ])) {
				$descfield = $this->def[ 'subdescriptionfield' ];
				vars::setvar('parent.'.$prefix.'.desc', $row->$descfield);
			}
		}
		else {
			vars::setvar('parent.'.$prefix.'.id', 0);
			vars::setvar('parent.'.$prefix.'.cid', FormerObject::encodeId(0));
		}

		$page=page::make($subformer->form, "SL");	// simplelist

		$formgroupattr='';
		$this->testShowCondition($formgroupattr);
		$directresponse=null;$httpCode=0;
		$page=$page->show(0,$directresponse, $httpCode, $formgroupattr);
		return $page;
	}

	/**
	 * Subformer displays on full width area in the card.
	 * The title is shown with a separatorfield-like style (and can be omitted)
	 * @param $row
	 * @return string
	 */
	public function getinput($row) {
		$formgroupattr='';
		if (!$this->testShowCondition($formgroupattr))
			return '';	// invisible
		$rv='';
		if (isset($this->def['title'])) {
			//			$urlbut=$this->getUrlButton('C');
			//			$rv="<div class='form-group' $formgroupattr ><legend class=\"col-md-12\" style='font-weight:300;font-size:20px;'>";
			//			$rv.= COS::lang($this->title());
			//			$rv.= $urlbut['html'] ? '<span style="position:relative; bottom: 3px;">'.$urlbut['html'].'</span>' : '';
			//			if (isset($this->def['helptext'])) {
			//				$hbsize=12;	// full width UNDER the field
			//				$rv.="<div class='pull-right'><span class='help-block help-block-inline' style='font-size:14px'>".COS::lang($this->def['helptext']).'</span></div>';
			//			}
			//			$rv.= "</legend>";
			//			$rv.="</div>";
		}
		$rv.=$this->getbaseinput($row);
		return $rv;
	}
	public function display($row=null) {
		if (isset($this->def['html']))
			return vars::v($this->def['html']);	// do NOT escape HTML-definitions from the yaml. They are safe
		return "subformerdisplay";
	}

	// no value for this field from the original database
	public function val($row=null,$pagetype='') {
		return '';
	}
	public function getUpdateSql($do) {
		return array();
	}

	public function getValidateRule() {
		return null;
	}
}