<?php 
namespace Rvwoens\Former\rows;

use Event;
use Illuminate\Database\QueryException;
use \Input,\Validator,\Session,\Redirect,\URL,\DB,\View,Bootstrapper;
use Rvwoens\Former\FormerObject;
use Rvwoens\Former\vars;
use Routing\Filter;
use Rvwoens\Former\cos;

class delrow extends row {
	
	// default POST stores the fields from input
	public function del() {
		$flashWasDeleted = false;
		//  \Routing\Filter::run('csrf');
		$id=FormerObject::decodeId(); // Input::get('id');	
		if ($id) {
			foreach($this->fields as $col) {
				if (!$col->allowDel()) {
					// no delete allowed says column
					Session::flash('msg','Verwijderen niet toegestaan');
					vars::setvar('id',$id);
					vars::setvar('cid',FormerObject::encodeId($id));
					return Redirect::to(vars::v($this->getdef('pages.edit.url')));
				}
			}
			vars::setvar('id',$id);
			$flashWasDeleted = true;
			try {

				//delete events are fired before actual deletion, so eloquent models can still act upon them
				$this->handleDeleteEvent($id);

				if ($this->getdef('pages.del.query'))
					DB::statement(vars::v($this->getdef('pages.del.query')));
				elseif ($this->getdef('pages.del.sql'))
					DB::statement(vars::v($this->getdef('pages.del.sql')));
				else
					DB::table($this->def['table'])->where('id', '=', $id)->delete();
			}
			catch(QueryException $e) {
				// no delete allowed says column
				$gerr=cos::lang($this->getdef('exceptions.'.$e->getCode(), 'nl::Kan kaart niet verwijderen|en::Cant remove card'));
				Session::flash('globalerror',$gerr.' (code '.$e->getCode().')');
				vars::setvar('id',$id);
				vars::setvar('cid',FormerObject::encodeId($id));
				return Redirect::to(vars::v($this->getdef('pages.edit.url')));
			}
			//If data was added updated flash to a var for interpretation with intercom see former intercom.js / form.blade.php
			if($flashWasDeleted) {
				Session::flash('dataWasDeleted', true);
			}
		}
		return Redirect::to(vars::v($this->getdef('pages.list.url')));
	}

	/**
	 * @param $id
	 * @throws \exception
	 */
	protected function handleDeleteEvent($id) {
		$events = $this->getdef('events');

		if (!is_null($events)&&isset($events['delete'])) {
			$event = !is_string($events['delete']) ? $events['delete']['event'] : $events['delete'];
			$arg = !is_string($events['delete']) ? cos::ifset(vars::v($events['delete']['arg']), $id) : $id;
			Event::fire($event, [$arg, (bool)$id]);
		}
	}
	
}