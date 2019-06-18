<?php namespace Rvwoens\Gompie;

use Cache;
use Illuminate\Support\Facades\Input;
use Request;
use Symfony\Component\Yaml\Yaml;
use View;
use Log;
use URL;
use File;
use Redirect;
use Response;
use Crypt;
use Rvwoens\Gompie\rows\row;
use Rvwoens\Gompie\Pages\page;

/**
 * Class GompieObject
 * @package Rvwoens\Gompie
 * @version 1.0
 * @Author Ronald vanWoensel <rvw@cosninix.com>
 */

class GompieObject {

	public  $form     = NULL;
	public  $response = NULL;
	public  $httpCode = 0;
	private $opts     = array();
	private $data     = array();
	private $debugstr = '';
	private $formUrl  = '';

	// new yaml based web-db
	public function __construct($form = array()) {
		$this->form = $form;    // can load immediate withoud yaml
		if (!isset($this->form['formurl']))
			$this->formUrl = URL::current();
		else
			$this->formUrl = $this->form['formurl'];
		Log::warning("Loading for formurl = ".$this->formUrl);
		$this->setDefaults();
	}

	public function loadForm($form) {    // load from array()
		$this->form = $form;
		$this->setDefaults();
	}

	public function setDefaults() {
		if (!is_array($this->form))
			$this->form=[];

		if (!isset($this->form['pages']) || !is_array($this->form['pages']))
			$this->form['pages'] = array();    // new/edit/list/show

		if (!isset($this->form['pages']['new']))
			$this->form['pages']['new'] = array();
		if (!isset($this->form['pages']['edit']) || !is_array($this->form['pages']['edit']))
			$this->form['pages']['edit'] = array();
		if (!isset($this->form['pages']['list']) || !is_array($this->form['pages']['list']))
			$this->form['pages']['list'] = array();
		if (!isset($this->form['pages']['show']))
			$this->form['pages']['show'] = array();
		if (!isset($this->form['pages']['del']))
			$this->form['pages']['del'] = array();

		if (!isset($this->form['pages']['edit']['url']))
			$this->form['pages']['edit']['url'] = $this->formUrl.'?cm=E&cid=$+cid';    // $+ internal var first, then get/post
		if (!isset($this->form['pages']['del']['url']))
			$this->form['pages']['del']['url'] = $this->formUrl.'?cm=D&cid=$+cid';
		if (!isset($this->form['pages']['list']['url']))
			$this->form['pages']['list']['url'] = $this->formUrl."?cm=L";
		if (!isset($this->form['pages']['map']['url']))
			$this->form['pages']['map']['url'] = $this->formUrl."?cm=M";
		if (!isset($this->form['pages']['new']['url']))
			$this->form['pages']['new']['url'] = $this->formUrl."?cm=E";
		if (!isset($this->form['pages']['excel']['url']))
			$this->form['pages']['excel']['url'] = $this->formUrl."?cm=XLS";
		$this->form['callbacks'] = array('tokenfunc' => NULL);
	}

	public function loadFile($f) {        // load from yaml
		if (preg_match('#^\/(.*)#', $f, $match))
			$fullf = base_path($match[1].'.yaml');
		else
			$fullf = app_path('Forms/').$f.'.yaml';
		if (!File::exists($fullf))
			throw new Exception("gompie: cant load $fullf");
		$this->form = Yaml::parseFile($fullf);
		$this->setDefaults();

		vars::setID($f);    // store vars under our form name
		if (cos::nvl($_GET['reset'], '') == 'Y') {
			Cache::flush();
			vars::storevars();    // empty
			$cm = cos::nvl($_GET['cm'], 'L');    // can overrule the page we go to after reset
			$this->response = Redirect::to($this->form['pages'][$cm == 'M' ? 'map' : 'list']['url']);

			return false;
		}
		else {
			vars::loadvars();    // load vars from session
		}

		return true;
	}

	/**
	 * func($token) -> true = pass false = no pass
	 *
	 * @param $func
	 */
	public function setTokenFunc($func) {
		$this->form['callbacks']['tokenfunc'] = $func;
	}

	/**
	 * Sets options, with the ability to merge with existing options
	 *
	 * @param array $opts
	 * @param bool $merge
	 */
	public function setopts($opts = array(), $merge = true) {
		$this->opts = $merge ? array_merge($this->opts, $opts) : $opts;
	}

	public function getCardmode() {
		$cardmode = Input::get('cm', '');
		if (!$cardmode && vars::getvar('maplist') == 'map')
			$cardmode = 'M';    // remember Map if no cardmode given
		switch ($cardmode) {
		case 'E':
		case 'Y':
			if ($this->getdef('options.edit', 'Y') != 'Y')
				$cardmode = 'list';
			else
				$cardmode = 'card';
			vars::setvar('carddesc', 'wijzigen');
			break;
		case 'A':
			$cardmode = 'A';
			vars::setvar('carddesc', 'toevoegen');
			break;
		case 'D':
			$cardmode = 'D';
			vars::setvar('carddesc', 'verwijderen');
			break;
		case 'card':
		case 'C':
			$cardmode = 'card';
			vars::setvar('carddesc', 'details');
			break;
		case 'xls':
		case 'XLS':
			$cardmode = 'xls';
			vars::setvar('carddesc', 'excel');
			break;
		case 'M':
			if ($this->getdef('options.map', 'Y') != 'Y')
				$cardmode = 'list';    // listoption "N" so no list
			else
				$cardmode = 'map';
			vars::setvar('carddesc', 'map');
			break;
		case 'L':
		default:
			if ($this->getdef('options.list', 'Y') != 'Y')
				$cardmode = 'card';    // listoption "N" so no list
			else
				$cardmode = 'list';
			vars::setvar('carddesc', $cardmode);
		}
		vars::setvar('cardmode', $cardmode);    // store as cardmode, this remembers the last cm

		return $cardmode;
	}

	public function doAll($loadfile = '', $template = '') {
		//Log::info('Former: doAll');
		if ($this->response)
			return NULL;    // return immediate: we already have a response
		if ($loadfile)
			$this->loadFile($loadfile);

		$cm = $this->getCardmode();

		if (Request::method() == 'POST') {
			// we show NOTHING.. just create a row for posting
			$row = \Rvwoens\Gompie\rows\row::make($this->form, 'post');    // a basic postrow is enough
			$response = $row->post(static::decodeId()); // Input::get('id'));
			if (is_object($response)) {
				$this->response = $response;

				return NULL;    // if null, then need a directresponse
				// $response->send(); // $response->finalize();
				// \Session::save();
				// exit;	// SEND the response object and EXIT
			}

			return $response; // a text response
		}
		if ($cm == 'D') {
			// delete row
			$row = row::make($this->form, 'del');    // a basic delrow is enough
			$response = $row->del(static::decodeId()); // Input::get('id');
			if (is_object($response)) {
				$this->response = $response;

				return NULL;
				// $response->send(); // $response->finalize();
				// exit;	// SEND the response object and EXIT
			}

			return $response;
		}
		$page = Pages\page::make($this->form, $cm);
		if ($template)
			return View::make($template)->with('content', $page->show());
		$page = $page->show(0, $directresponse, $this->httpCode);
		if ($directresponse) {
			$this->response = $directresponse;

			return NULL;
		}

		return $page;
	}

	// card ONLY
	public function doCard($id = 0) {
		if ($this->response)
			return NULL;    // return immediate: we already have a response
		if (Request::method() == 'POST') {
			// we show NOTHING.. just create a row for posting
			$row = rows\row::make($this->form, 'post');    // a basic postrow is enough
			$response = $row->post($id);
			if (is_object($response)) {
				$this->response = $response;

				return NULL;
				// $response->send(); // $response->finalize();
				// \Session::save();
				// exit;	// SEND the response object and EXIT
			}

			return $response;
		}
		$page = page::make($this->form, 'C');

		return $page->show($id, $directresponse);
		if ($directresponse) {
			$this->response = $directresponse;

			return NULL;
		}

		return $page;
	}

	public function response($view) {
		if ($this->response) {
			return $this->response;    // redirect or something else
		}
		if ($this->httpCode)
			return Response::make($view,
								  $this->httpCode);     // so we can give a 500 if we want to..(queryerrors etc) without the user noticing

		return $view;    // make the view
	}

	// get def item in the form:  main.sub.sub.sub
	// example:	newcard.url
	public function getdef($elm, $def = NULL) {
		$elms = explode('.', $elm);
		$dd = $this->form;
		foreach ($elms as $e) {
			if (isset($dd[$e]))
				$dd = $dd[$e];
			else
				return $def;
		}

		return $dd;
	}

	// change the def of the field
	public function setfielddef($field, $option, $value) {
		foreach ($this->form['fields'] as &$fld) {
			if ($fld['name'] == $field) {
				$fld[$option] = $value;
			}
		}
		//echo "<pre>";print_r($this->form);exit;
	}

	public function getFieldDef($field) {
		foreach ($this->form['fields'] as &$fld) {
			if ($fld['name'] == $field) {
				return $fld;
			}
		}

		return NULL;
	}

	// encode a numeric id to a checked hash string
	public static function encodeId($id) {
		$rv = Crypt::encrypt('checkme'.$id);

		//$rv= COS::dec2any($id);	// COS::alphaID(bcmul($id,83117),false);
		return $rv;
	}

	// decode hash string cid to id or FALSE
	public static function decodeId($cid = NULL) {
		if (is_null($cid)) {
			$cid = Input::get('cid', 0);
		}
		if ($cid === 0)
			return 0;
		try {
			$id = @Crypt::decrypt($cid);
		} catch (\Exception $e) {
			Log::info("decodeID: decode error, check not found!");

			return false;
		}
		if (substr($id, 0, 7) != 'checkme') {
			Log::info("decodeID: decode error, check not found!");

			return false;
		}

		return substr($id, 7);
		//return $id;
		// $id=COS::any2dec($cid);	//COS::alphaID($cid,true);
		// if (is_null($id))
		// 	return false;	// error
		// return $id;
	}
}