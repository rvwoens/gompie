<?php namespace Rvwoens\Gompie\fields;

use Bootstrapper;
use DB;
use Rvwoens\Gompie\Cos;
use Illuminate\Support\Facades\Input;
use Rvwoens\Gompie\vars;
use \Session;
use \Messages;
use \Log;

// editable field has arrangements for updating/editing the field
abstract class editablefield extends field {

	/**
	 * Enables the field to notice when it has been overridden by forceSetValue()
	 *
	 * @var bool
	 */
	protected $valueForced = false;

	/**
	 * postval - get fields value when posted
	 */
	public function postval($row=null) {
		return Input::get($this->name, ($row? $row->{$this->name} : $this->val) );
	}

	/**
	 * errtext - obtain formatted error text showing the field after validation fails
	 */
	public function errtext() {
		if ( Session::has('errors')){
			$errors = Session::get('errors');
		}
		else
			return '';	// no errors
		return $errors->first($this->name,'<span style="color:red">:message</span>');	
	}
	/**
	 * get default field edit rules, if any
	 */
	public function getValidateRule() {
		if (isset($this->def['validate'])) {
			if (is_array($this->def['validate'])) {
				// todo? vars::v on each element
				$d1=array();
				foreach($this->def['validate'] as $validaterule) {
					$d1[]=vars::v($validaterule);
				}
				$dd=array($this->formfieldname=>$d1);
			}
			else {
				$dd = [$this->formfieldname => vars::v($this->def['validate'])];
			}
			//print_r($dd);		
			return $dd;
		}
		return null;	// no validation
	}
	/**
	 * Get the update or insert sql in the form field=>value
	 */
	public function getUpdateSql($do) {
		
		// return a default update array like 'field'=>value
		if ( ($rv=$this->getDefUpdateSql($do)) !==null) {
			return $rv ? $rv : [];    // overridden! (if empty-> do not update) -> even for READONLY=Y fields!!!
		}

		if ($this->isReadOnly()) {
			if ($do=='insert' && isset($this->def['default']))
				return array($this->name=>vars::v($this->def['default']));		
			return array();	// do not update readonly fields
		}

		$pv = $this->valueForced ? $this->val : $this->postval();
		// 	Not needed as the ConvertEmptyStringsToNull middleware is gone.
		// 	if an input is specified, it can never be null.
		//		if (is_null($pv)) {
		//			// https://laravel.com/docs/5.4/requests#input-trimming-and-normalization
		//			// due to ConvertEmptyStringsToNull
		//			$pv="";
		//		}
		if ($pv=='NULL') {
			return [$this->name => DB::raw('null')];    // set to NULL not 'null'
		}

		return array($this->name=>$pv);
	}


	/**
	 * Force setter for field value
	 *
	 * @param $val
	 */
	public function forceSetValue($val) {
		$this->valueForced = true;
		$this->val = $val;
	}
}	
