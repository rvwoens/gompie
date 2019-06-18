<?php namespace Rvwoens\Gompie\fields;
use Rvwoens\Gompie\Cos, Rvwoens\Gompie\Vars;
use Illuminate\Support\Facades\Input;
use Form;

// base field class
class datefield extends editablefield {

	public function getbaseinput($row,&$gridsize,$addon='') {
		$gridsize=array_get($this->def,'size','6');

		if (preg_match('/[0-9]+px/',$gridsize,$match)) {
			$divattr=' style="width:'.$gridsize.'" ';	// size=20px format
		}
		else
			$divattr=' class="col-md-'.$gridsize.'" ';	// class="col-md-2" format

		if ($this->isReadOnly()) {
			return "<div $divattr><p class=\"form-control-static \">".$this->display($row)."</p></div>";	// READONLY
		}


		$style=array('style'=>'margin-right:5px;', 'class'=>'form-control');

		if (isset($this->def['popover'])) {
			$style['data-toggle']='popover';
			if (is_array($this->def['popover'])) {
				$style['data-title']=COS::lang($this->def['popover']['title']);
				$style['data-content']=COS::lang($this->def['popover']['content']);
				$style['data-placement']=COS::ifset($this->def['popover']['placement'],'top');
			}
			else {
				$style['data-title']=COS::lang("nl::Informatie over dit veld|en::Information about this field");
				$style['data-content']=COS::lang($this->def['popover']);
			}
			$style['data-trigger']='focus';
		}

		if (isset($this->def['placeholder']))
			$style['placeholder']=array_get($this->def,'placeholder');

		$txtfields="<div class=\"col-md-$gridsize\"><div class=\"input-group date\" id='i_".$this->formfieldname."'>".
			\Form::text($this->formfieldname,
						$this->display($row),
						$style
			)."<span class=\"input-group-addon\"><span class=\"glyphicon glyphicon-calendar\"></span></span></div></div>";
		//		$txtfields="<div $divattr>".
		//			\Form::text($this->formfieldname,
		//						$this->display($row),
		//						$style
		//			)."</div>";
		return $txtfields;
	}

	//	// add datepicker to datefield
	//	public function getinput($row) {
	//		$rv=parent::getinput($row);
	//		if ($this->isReadOnly())
	//			return $rv;
	//		$rv.="<script>
	//				jQuery(function($){
	//					 var dialog = new mdDateTimePicker.default({
	//              			type: 'date'
	//					});
	//					dialog.trigger=$('input[name=\"".$this->name."\"]');
	//				});
	//			</script>";
	//		return $rv;
	//	}
	// add datepicker to datefield

	public function getinput($row) {
		$rv=parent::getinput($row);
		if ($this->isReadOnly())
			return $rv;
		/* old datepicker - changed BM 11 feb 2018
				$rv.="<script>jQuery(function($){
						$('input[name=\"".$this->name."\"]').datepicker({
							format: 'dd-mm-yyyy',todayBtn: 'linked'
						});
					});
					</script>";
		*/
		// new datetime picker from https://github.com/Eonasdan/bootstrap-datetimepicker
		// figure out the right format. Please note that format strings are not php but from moment.js
		switch (COS::ifset($this->def['format'],'date')) {
		case 'datetime':
			$f = 'DD-MM-YYYY HH:mm'; break;
		case 'time':
			$f = 'HH:mm'; break;
		case 'timeinterval':	// timeinterval disables datetimepicker
			return $rv;
		case 'date':
		default:
			// if we supply a format string we asume the datetimepicker still only requires a date
			$f = 'DD-MM-YYYY'; break;
		}
		$rv.="<script>$(function(){
				$('#i_".$this->name."').datetimepicker({
					format: '".$f."'
				});
			});
			</script>";
		return $rv;
	}

	// for date it is the formmated value!
	public function display($row=null) {
		$val=e($this->val($row));	// mysql date
		//echo "Date ".$this->name."=[$val]";
		if ($val=='' || $val=='0000-00-00' || $val=='0000-00-00 00:00:00')
			return '';	// empty (null) date
 		// format: date, datetime, time, timeinterval
		switch (COS::ifset($this->def['format'],'date')) {
		default:
			$fmt=$this->def['format'];break;	// default any format
		case 'date':	$fmt='d-m-Y';break;
		case 'datetime':$fmt='d-m-Y H:i';break;
		case 'time':	$fmt='H:i';break;
		case 'timeinterval':	
				$vals=explode(':',$val);
				if ($vals[0]>23) 
					return sprintf("%d %02d:%02d",floor($vals[0]/24),$vals[0]%24,$vals[1]);
				return sprintf("%02d:%02d",$vals[0],$vals[1]);
		}
		return date($fmt,strtotime($val));
	}
	
	public function getUpdateSql($do) {
		if ($rv=$this->getDefUpdateSql($do)) {
			//echo "updatesql: $rv";exit;
			return $rv; 	// overridden
		}
		if ($this->isReadOnly()) {
			if ($do=='insert' && isset($this->def['default']))
				return array($this->name=>vars::v($this->def['default']));		
			return array();	// do not update readonly fields
		}
		$postval=$this->postval();
		if ($postval!='') {
			// our postval is formatted! De-format to mysql
			switch (COS::ifset($this->def['format'],'date')) {
			default:
			case 'date':	$postval=date('Y-m-d',strtotime($postval));	break;
			case 'datetime':$postval=date('Y-m-d H:i',strtotime($postval));break;
			case 'time':	$postval=date('H:i',strtotime($postval));break;
			case 'timeinterval':	
				//echo "Postval=$postval";exit;
				//$postval=strtr($postval,'Dd','  ');
				return array($this->name=>$postval);	// mysql understands D h:m format
			}
		}
		else {
			if (COS::ifset($this->def['nullable'],'N')=='Y') 
				$postval=\Db::raw('null');
			else
				$postval='0000-00-00';	// not nullable. Use 0-date as null value
		}
		return array($this->name=>$postval);
	}

}