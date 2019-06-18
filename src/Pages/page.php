<?php namespace Rvwoens\Gompie\Pages;

use Input;
use Validator;
use Session;
use Redirect;
use URL;
use Log;
use Rvwoens\Former\Cos;

// a page is a viewable page on the screen with cards, lists and other elements
// a page does the query and uses ROW object(s) to show them
abstract class page {
	protected $def=array();
	
	public function __construct($def=array()) {
		$this->def=$def;
	}
	
	static public function make($def=array(),$typ='card') {
		switch (strtolower($typ)) { // array_get($def,'type','card'))) {
		case 'card':
		case 'c':
		case 'e':
			return new cardpage($def);
		case 'list':
		case 'l':
			//echo "row-make LIST <br>";return;
			return new listpage($def);
		case 'map':
		case 'm':
			return new mappage($def);
		case 'sl':
			// simple-list for subformers
			return new simplelistpage($def);
		case 'xls':
			return new excelpage($def);
		default:
			throw new \exception("former\page Type $typ not known");
		}
	}

	public function show($forceid=0,&$directresponse=null, &$httpCode=0) {
		return "No page defined. Extend the page class";
	}

	// get def item in the form:  main.sub.sub.sub
	// example:	newcard.url
	public function getdef($elm,$def=null) {
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
	public function hasdef($elm) {
		return $this->getdef($elm)!==null;
	}

	public function hasToken($def) {
		if (!$this->getdef($def))
			return true;	// no token neeeded
		$token=$this->getdef($def);
		if (!$this->def['callbacks']['tokenfunc'])
			return true;	// no tokenchecker defined. Allow all
		return $this->def['callbacks']['tokenfunc']($token);	// call the callback tokenfunc
	}

	public function setsessionvars($cl) {
		$setvars=$this->getdef("pages.$cl.setsessionvars");
		if ($setvars && is_array($setvars)) {
			foreach($setvars as $var=>$val) {
				if ($val)
					Session::put($var, $val);
				else
					Session::forget($var);
			}
		}
	}
}

