<?php namespace Rvwoens\Gompie\rows;

use \Input,\Validator,\Session,\Redirect,\URL,\Log;
use Rvwoens\Gompie\Cos;
use Rvwoens\Gompie\fields\field;
use Exception;

// base field class
/*abstract*/ class row {
	protected $def=array();
	protected $fields=array();		// our fields

	
	//******************************************************************************************************
	//	def: array of fielddefs
	//  [ 	type = rowtype
	// 		table = database table (for "automatic" mode)
	//    	fields = flds
	//******************************************************************************************************
	public function __construct($def=array()) {
		$this->def=$def;
		foreach($def['fields'] as $fld) {
			$this->fields[]=field::make($fld,$this);	// make the field with ourselve as owner
		}
	}
	// get field objects
	public function getFields() {
		return $this->fields;
	}
	// get array of all fieldNAMES to show
	public function getFieldNames($deffields, $skipfields) {
		$cardfields=array();
		$skips=array();
		//if (isset($this->def[$deffields])) 
		//	return $this->def[$deffields];
		if ($this->getdef($deffields)) {
			//echo "Deffields: $deffields";
			return $this->getdef($deffields);
		}
		if ($this->getdef($skipfields)) {
			$skips=$this->getdef($skipfields);
		}

		//dd($this->fields);
		// default just all fields
		foreach($this->fields as $fld) {
			if (in_array($fld->name(),$skips)) {
				//echo "SKIP ".$fld->name();
				continue;	// skip field
			}
			$cardfields[]=$fld->name();
		}
		return $cardfields;
	}
	
	public function findField($name,$generate=false) {
		foreach($this->fields as $fld) {
			if ($fld->name == $name)
				return $fld;
		}
		if (!$generate)
			return null;
		// not found. Assume it is a text field (default)
		$newfield=field::make(array('name'=>$name),$this);	// make the field with ourselve as owner
		$this->fields[]=$newfield;
		return $newfield;
	}
	
	static public function make($def=array(),$typ='card') {
		switch (strtolower($typ)) { // array_get($def,'type','card'))) {
		case 'card':
		case 'c':
		case 'e':
			return new cardrow($def);
		case 'post':
			return new postrow($def);
		case 'del':
			return new delrow($def);
		case 'list':
		case 'l':
			//echo "row-make LIST <br>";return;
			return new listrow($def);
		case 'excel':
			return new excelrow($def);
		case 'mapinfowin':
			return new mapinfowinrow($def);
		default:
			return new row($def);	// ourself
		}
	}
	// copy row values to vars
	public function setrowvars($row) {
		foreach($this->fields as $col) {
			$col->setvar($row);
		}
	}
	
	// default show shows getinput's
	public function show($row) {
		// $rv='';
		// foreach($this->fields as $col) {
		// 	$col->setvar($row);
		// }
		// foreach($this->fields as $col) {
		// 	$rv.=$col->getinput($row);
		// } 
		// //echo "<pre>$rv";exit;
		// return $rv;
		return "";
	}
	
	public function page($id=0) {
		return $this->show(null);
	}
	
	public function post() {
		Session::flash('msg','Sorry, this is read-only');
		return Redirect::to(URL::full());
	}
	
	//return true if one of the fields does a fileupload
	public function hasFileUpload() {
		foreach($this->fields as $col) {
			if ($col->hasFileUpload())
				return true;
		}
		return false;
	}
	// get def item in the form:  main.sub.sub.sub
	// example:	newcard.url
	public function getdef($elm,$def=null) {
		if (!is_string($elm))
			throw new exception("row: getdef from ".print_r($elm,true)." not possible ");
		$elms=explode('.',$elm);
		$dd=$this->def;
		foreach ($elms as $e) {
			if (isset($dd[$e]))
				$dd=$dd[$e];
			else
				return $def;
		}
		return $dd;
	}
	public function hasToken($def) {
		if (!$this->getdef($def))
			return true;	// no token neeeded
		$token=$this->getdef($def);
		if (!$this->def['callbacks']['tokenfunc'])
			return true;	// no tokenchecker defined. Allow all
		return $this->def['callbacks']['tokenfunc']($token);	// call the callback tokenfunc
	}
	public function hasTokenValue($token) {
		if (!$this->def['callbacks']['tokenfunc'])
			return true;	// no tokenchecker defined. Allow all
		return $this->def['callbacks']['tokenfunc']($token);	// call the callback tokenfunc
	}
}	
