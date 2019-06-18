<?php 

namespace Former\fields;
use Laravel,Bootstrapper,Former,Former\Cos, Former\vars, \Button;

// A separator just shows a text on the screen, not database related
class actionfield extends field {

	// public function getbaseinput($row) {
	// 	return '';
	// }
	// this field has no value
	public function val($row=null) {
		return '';
	}
	// overrules getinput: Not your normal card field.
	public function getinput($row) {
		return "";
	}
	public function listTdStyle() {
		return "padding: 4px 1px;";
	}
	public function getListColumn($row) {
		$disp='';$ays='';$extrastyle='';

		$url=COS::ifset($this->def['url'],'#');
		if (!is_array($url)) 
			$url=array('url'=>$url, 'cardlist'=>'CL', 'style'=>'icon', 'title'=>'click');
		$linkurl=vars::v($url['url']);
		if (isset($this->def['areyousure'])) {
			$ays=$this->def['areyousure'];
			$aysurl=$linkurl;
			$linkurl="#ays";
			$extrastyle=' data-toggle="modal" ';
		}
			
		if (strpos(COS::ifset($url['cardlist'],'CL'),'L')!=false) {	
			switch(COS::ifset($url['style'],'icon')) {
			default:
			case 'icon':
				$disp.='<a '.$extrastyle.' href="'.$linkurl.'"> <span class="glyphicon glyphicon-play"></span>';
				break;
			case 'button':
				$disp.='<a '.$extrastyle.' class="btn '.COS::ifset($url['class'],'').' btn-mini" href="'.$linkurl.'"> '.vars::v($url['title']).'</a>';
				break;					
			}
		}
		if ($ays) {
			$disp.='<div id="ays" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			  <div class="modal-header">
			    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			    <h3 id="myModalLabel">'.__('former::elements.areyousure').'</h3>
			  </div>
			  <div class="modal-body">
			    <p>'.$ays.'</p>
			  </div>
			  <div class="modal-footer">
			    <button class="btn" data-dismiss="modal" aria-hidden="true">'.__('former::elements.cancel').'</button>
			    '.Button::link($aysurl,__('former::elements.ok')).'
			  </div>
			</div>';
		}
		return $disp;
	}
}