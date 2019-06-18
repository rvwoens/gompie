<?php
namespace Rvwoens\Former\rows;


use Event;
use Input;
use Validator;
use Session;
use Redirect;
use URL;
use DB;
use View;
use Bootstrapper;
use Former;
use Rvwoens\Former\FormerObject;
use Rvwoens\Former\vars;
use Routing\Filter;
use Response;
use Log;
use Illuminate\Support\Facades\Cache;
use Rvwoens\Former\cos;

class postrow extends row {

	static private $loginfo=false;	// handmatig op true/false zetten hier

	// default POST stores the fields from input
	public function post() {

		//get id from request and decode
		$id=FormerObject::decodeId();

		//set it as a variable available
		vars::setvar('id',$id);

		//if logging requested, add logging
		if (self::$loginfo) {
			log::info("postrow: posting for id=$id cid=".Input::get('cid'));
		}

		//determine if the request is an ajax request
		$isxhr=Input::get('_xhr');

		//if logging requested and xhr request
		if (self::$loginfo && $isxhr) {
			Log::info('files:'.print_r($_FILES, true));
		}

		Validator::extend('uploaderror', function($attribute, $value, $parameters) {
			//  Log::info("post: upload validate: ".print_r($attribute,true).print_r($value,true). print_r($parameters,true));
			if ($test=Input::file($attribute)) {
				// Log::info("post: test=".print_r($test,true));
				// return TRUE if we validate, false on error
				return $test['error']==0;
			}
		    return true;
		});

		//validate form by iterating validators for each field
		$valid=$this->validate();

		//if validation fails
		if ($valid->fails()) {
			//report errors to session if xhr
			if ($isxhr) {
				Input::flash();
				Session::flash('errors', ($valid instanceof Validator) ? $valid->errors : $valid);
				return Response::json(array('redirect'=>URL::full()));
			}

			log::info("postrow: validation $id fails: ".$valid->messages());

			//redirect with errors if not xhr
	        return Redirect::to(URL::full())->withErrors($valid)->withInput();	//
		}

		//if pages.card.validator is set, start handling
		if ($this->getdef('pages.card.validator','')) {

			$error=vars::v($this->getdef('pages.card.validator',''));
			log::info("postrow: pages.card.validator ".$this->getdef('pages.card.validator','')." -> $error");

			//if error was thrown, set to session
			if ($error) {
				log::info("postrow: card.validator fails: ".$error);
				Session::flash('globalerror',$error);	// set the global error
				return Redirect::to(URL::full())->withInput();	//
			}
		}

		//save and return the new id
		$nid=$this->save($id);
		Cache::tags($this->def['table'])->flush();	// flush our cache for list-caches (see listpage)  Note: file/database cache does not support tagging
		//Cache::flush();		// for file/dataase caches do not support tagging

		//if data was updated, report so via session flash message
		Session::flash('msg',$nid==$id?trans('former::elements.datachanged'):trans('former::elements.dataadded'));
		
		//If data was added updated flash to a var for interpretation with intercom see former intercom.js / form.blade.php
		Session::flash(($nid==$id) ? 'dataWasUpdated' : 'dataWasAdded', true);


		//set the newly available id to the vars
		vars::setvar('id',$nid);

		//handle post event
		$this->handlePostEvent($nid, $id);

		//set encoded id to the vars
		vars::setvar('cid',FormerObject::encodeId($nid));

		$backurl = $this->getdef('pages.edit.back')=='list' ? $this->getdef('pages.list.url') : $this->getdef('pages.edit.url');

		if ($isxhr) {
			return Response::json(array('redirect'=>vars::v($backurl) ));
		}

		return Redirect::to( vars::v($backurl,false,true) );

	}

	/**
	 * Validate all fields in the current form
	 *
	 * @return \Illuminate\Validation\Validator
	 */
	public function validate() {
		$rules=array();

		foreach($this->fields as $col) {
			if ($col->isReadonly())
				continue;
			if ($rule=$col->getValidateRule()) {
				$rules = array_merge($rules, $rule);
			}
		}

		return Validator::make(Input::all(), $rules);
	}

	// update record
	public function save($id) {
		$sqls=array();
		$do=($id?"update":"insert");

		foreach($this->fields as $col) {
			// get the array in form 'field'=>'value'
			$sqls=array_merge($sqls, $col->getUpdateSql($do));
		}

		if (self::$loginfo) {
			Log::info("postrow: save ".print_r($sqls,true)." for id=$id");
		}

		if ($id) {
			//echo "id=$id.";dd($sqls);
			// use fluent builder
			if ($this->getdef('pages.card.updsql','')) {
				DB::query($sql=vars::v($this->getdef('pages.card.updsql','')));
				if (self::$loginfo) Log::info("postrow: UPDSQL=$sql");
			}
			else {
				if (count($sqls)>0)
					DB::table($this->def['table'])->where('id', '=', $id)->update($sqls);
			}
		}
		else {
			// Inserting!!!! 
			if ($this->getdef('pages.card.inssql','')) {
				DB::query(vars::v($this->getdef('pages.card.inssql','')));
			}
			else {
				$id=DB::table($this->def['table'])->insertGetId($sqls);	// NEW id!!
			}
			//echo "New id: $id!";
			vars::setvar('id',$id);
			vars::setvar('cid',FormerObject::encodeId($id));
			//$_POST['id']=$id;	// overrule! DOES NOT WORK -> laravel input does not work with _POST
		}
		// now run an AFTER event on the fields (for daughters to get the right ID)
		foreach($this->fields as $col) {
			$col->eventSaveAfter($id,$do);
		}
		//echo "Saved";exit;
		return $id;
	}

	/**
	 * fire a laravel event (optionally) after saving
	 * @param $nid - "new" id. This is the id after save, so always contains row id
	 * @param $id - "old" id, so 0 when inserting
	 * @throws \exception
	 */
	protected function handlePostEvent($nid, $id) {
		$events = $this->getdef('events');

		if (!is_null($events)&&isset($events['post'])) {
			$event = !is_string($events['post']) ? $events['post']['event'] : $events['post'];
			$arg = !is_string($events['post']) ? cos::ifset(vars::v($events['post']['arg']), $nid) : $nid;
			Event::fire($event, [$arg, (bool)$id]);
		}
	}

}