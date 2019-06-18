<?php 
namespace Rvwoens\Former\rows;

use \Input,\Validator,\Session,\Redirect,\URL,\DB,\View,Bootstrapper,Former,Rvwoens\Former\vars, \exception;

// base field class
class cardrow extends row {
	private $cardfields=array();
	
	public function page($id=0) {
		// page needs an ID to get the current card. 
		// If ID not given, we show a defautl form for NEW cards
		//echo "id=$id. Inputget=".Input::get('id')." ingetdef=".Input::get('id',$id);
		$cid=\Former\former::decodeId(Input::get('id',$id));
		if ($cid) 
			return $this->showid($cid);
		return View::make('former::error')->with('msg',"Sorry, card $id can not be found");
		// no ID. show new card
		return $this->show(null);
	}
	
	public function showid($id) {
		$row=DB::first("select * from former_demo where id=?",$id);
		if ($row==null)
			return View::make('former::error')->with('msg',"Sorry, card $id can not be found");
		return $this->show($row);
	}
	
	public function skipfield($name) {
		foreach($this->cardfields as $k=>$fld) {
			if ($fld == $name) {
				//echo "SKIPFIELD: $name<br>";
				unset($this->cardfields[$k]);
				return;
			}
		}
		return ;
	}
	
	public function show($row) {
		$this->setrowvars($row);
		//echo "cardrow! show()";
		//vars::dump();
		$this->cardfields=$this->getFieldNames('pages.card.fields' /* 'cardfields' */, 'pages.card.skip');	// default: all fieldnames
		//dd($this->cardfields);
		$inps=array();
		if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
			// php7 - do not use foreach on changing arrays. $this->cardfields can change during the loop (skipfield etc)
			//echo "VERSION 7<br>";
			for (reset($this->cardfields); $colname = current($this->cardfields); next($this->cardfields)) {
				//echo "COLNAME: $colname<br>";
				$col = $this->findfield($colname);
				if (!$col)
					throw new exception("former\cardrow: Field $colname not found");
				$inps[] = $col->getinput($row);    // might call skipfield!

			}
		}
		else {
			// php5 - use foreach on changing arrays. $this->cardfields can change during the loop (skipfield etc)
			//echo "VERSION 5<br>";
			foreach ($this->cardfields as  &$colname) {
				//echo "COLNAME: $colname<br>";
				$col = $this->findfield($colname);
				if (!$col)
					throw new exception("former\cardrow: Field $colname not found");
				$inps[] = $col->getinput($row);    // might call skipfield!

			}
		}
		$id=$row?$row->id:0;
		return View::make('former::rows/card',
			array(
				'inps'=>$inps,
				'id'=>$id
			)
		);
	}

}