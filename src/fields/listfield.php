<?php 	
namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Former,\Form,\DB,Rvwoens\Former\COS,Rvwoens\Former\vars;
// base field class
class listfield extends editablefield {
	private $list=array();
	
	private function getlist() {
		if (count($this->list)==0) {
			$list=array();
			if (isset($this->def['list'])) 
				$this->list=COS::array_v_to_kv($this->def['list']);
			if (isset($this->def['listkv']))
				$this->list=array_merge($this->list,$this->def['listkv']);
			if (isset($this->def['listsql'])) {
				$q=DB::select(vars::v($this->def['listsql']));
				foreach($q as $rw) {
					$arw=array_values((array) $rw);	// 0=>code, 1=>val
					$this->list[$arw[0]]=strtr($arw[1],"\n\r","  ");
					//echo "Added list: ".$arw[0].'/'.$arw[1];
				}
				//dd($list);
			}
			foreach($this->list as &$lv)
				$lv=COS::lang($lv);
			//echo "<pre>".$this->name.":".print_r($this->list,true)."</pre>";
		}
		return $this->list;		
	}

	// generate this form's input field
	public function getbaseinput($row=null,&$gridsize, $addon='') {
		// use list [val1,val2]  eq. to listkv [vak1=>val1,val2=>val2]
		// or listkv [code1=>val1,code2=>val2]
		// or listsql 'select code,val from table'
		// if MORE than one is listed, the arrays are merged! in order: list, listkv, listsql
		// ex. 
		// 		list:  	[NL:NL - nederland]
		// 		listsql:select code,name from country
		//
		// Create SELECT
		$gridsize=array_get($this->def,'size','6');
		if (preg_match('/[0-9]+px/',$gridsize,$match)) {
			$divattr=' style="width:'.$gridsize.'" ';	// size=20px format
		}
		else
			$divattr=' class="col-md-'.$gridsize.'" ';	// class="col-md-2" format  //margin-left:-8px;why???

		//$fsize='col-md-'.$gridsize;

		if ($this->isReadOnly()) {
			if (cos::ifset($this->def['readonlyselect'],'N')!='Y') {
				return "<div $divattr><p class=\"form-control-static \">".$this->display($row).
					"</p></div>";    // READONLY
			}
			// else fall through and show a read-only dropdown with its values
		}
		// old style laravel3 sizing $field= ($fsize && $fsize!='span6') ? $fsize.'_select':'select';
		$list=$this->getlist();
		$val=$this->val($row);
		if (isset($this->def['notfound']) && !isset($list[$val]))
			$list[$val]=$this->def['notfound'];
		//$txtfields=\Form::select($this->formfieldname, $list,$val,array('class'=>$fsize)).'&nbsp;';
		$usehtml=cos::ifset($this->def['htmllist'],'N')=='Y';
		$selpicker=$usehtml || cos::ifset($this->def['selpicker'],'Y')=='Y';	// default is YES
		//
		// select picker! see http://silviomoreto.github.io/bootstrap-select
		//
		$txtfields="<div $divattr><select name=\"{$this->formfieldname}\" class=\" form-control ".($selpicker?'selectpicker':'')."\">";
		foreach($list as $opt=>$disp) {
			$selected= ($val==$opt ? "selected=selected":"");
			if ($this->isReadOnly())
				$selected.=" disabled ";
			$opt=e($opt);
			$disp=e($disp);
			if ($usehtml)
				$txtfields.="<option value=\"{$opt}\" $selected data-content=\"{$disp}\">-</option>";
			else
				$txtfields.="<option value=\"{$opt}\" $selected >{$disp}</option>";
		}
		$txtfields.="</select>$addon</div>";
		//	\Form::select($this->formfieldname, $list,$val, array('class'=>'form-control selectpicker')).

		if (isset($this->def['placeholder']))
			$txtfields.=Form::block_help($this->def['placeholder'],array('class'=>'help-block-inline'));
		return $txtfields;
	}

	private function getColor($arr,$val) {
		$colors=['#acc5f1','#a2eeb7','#f1c997','#ff8a8f','#e49040','#dcf01e','#e4aeef','#abeddb','#fff7cd','#dbd8e4','#8078ec','#ee666e'];
		$cnt=0;
		foreach($arr as $k=>$v) {
			if ($k==$val)
				return $colors[$cnt % count($colors)];
			$cnt++;
		}
		return '#eeeeee';	// grey=not found
	}

	public function display($row=null) {
		// Don't display the KEY, display its VALUE
		$list=$this->getlist();
		// \Log::info('listfield: '.print_r($list,true).' row='.$this->val($row));
		// magic NULL value
		$val=$this->val($row);
		if ($val===null)
			$val='NULL';
		if (isset($list[$val]))
			$rv=COS::lang($list[$val]);
		else
			$rv=e($val);	// fallback to original key if value not found
		if (COS::ifset($this->def['colors'],'N')=='Y') {
			// use COLORS
			return ' <span class="label label-as-badge"  style="color:#333;background-color:'.$this->getColor($list,$val).';position:relative;top:-2px;">'.$rv.'</span>';
			return '<span style="background-color:'.$this->getColor($list,$val).';padding:3px 9px">'.$rv.'</span>';
		}
		return $rv;
	}
}