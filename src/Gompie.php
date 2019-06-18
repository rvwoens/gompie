<?php namespace Rvwoens\Gompie;


class Gompie {
	// Gompie::load($file) -> load and return FormerObject
	public function load($f) {
		//		$former=new FormerObject();
		//		$former->loadFile($f);
		//		return $former;
	}
	// complete form processor with load, doall and generate view.
	// the complete package..
	public function make($f,$baseview) {
		//		$frm=new FormerObject();
		//		$frm->loadFile($f);
		//		$frmout=$frm->doAll();	// $resp in case doAll wants to redirect
		//		return $frm->response(\View::make($baseview)->with('content',$frmout));
	}
}