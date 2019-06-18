<?php namespace Rvwoens\Former\fields;

/**
 * Class selectboxfield
 * creating selections
 * @package Rvwoens\Former\fields
 * @version 1.0
 * @Author Ronald van Woensel <rvw@cosninix.com>
 */
class selectboxfield extends field {

	public function getinput($row) {
		return '';	// no info on CARD, list only.
	}

	public function val($row=null) {
		return '';
	}
	// display on LIST
	public function display($row=null) {
		// display a checkbox thats it prevent clicks to be bubbled
		return "<input type='checkbox' class='former-selectboxfield' data-sid='{$row->id}'>";
	}
	public function listTdStyle() {
		return "padding-left:10px;width:35px; text-align: left;";
	}
	public function listTdClass() {
		return 'nobubble';
	}
	public function title() {
		$rv="<div style='width:50px;padding-left:5px;'> ";
		// toggle all/none
		$rv.="<input type='checkbox' class='former-selectbox_toggleall'>&nbsp;";
		// add selected
		$rv.='<button class="btn btn-sm btn-circle btn-warning" id="selectbox-adder" title="Add selected" style="visibility:hidden;">
				  <i class="fa fa-plus"></i>
				</button>';
		// moved to pages.partials.selectionbox.blade.php.
		//				// add selected
		//		$rv.='<button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored" id="selectbox-addall" title="Add all rows on all pages" style="width:30px;height:30px;min-width:30px;left:2px;user-select:none;font-size:28px">
		//				  <i class="material-icons" style="font-size:24px">add_circle_outline</i>
		//				</button>';
		$rv.="<script>
			jQuery(function($) {
				$('#selectbox-bar2').show();	// show element in pages.partials.selectionbox.blade.php.
			});
			</script>";
		$rv.='</div>';
		return $rv;
	}
}
