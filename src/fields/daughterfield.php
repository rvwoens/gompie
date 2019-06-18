<?php 
namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Former,Rvwoens\Former\Cos, Rvwoens\Former\Vars, \DB, \Input, Rvwoens\Former\FormerObject;

// base field class
class daughterfield extends field {

	/**
	 * @var array
	 */
	protected $fields=array();		// our fields
	
	public function __construct($def=array(),$owner) {
		parent::__construct($def,$owner);
		foreach($def['fields'] as $fld) {
			$newfield=field::make($fld,$this);	// make the field with ourselve as owner and our name as prefix
			$newfield->formfieldname=$this->name.'_'.$newfield->formfieldname.'[]';
			$this->fields[]=$newfield;
		}
	}
	
	public function getbaseinput($row) {
		// daughter needs SQL
		$rv='';
		$sql=vars::v($this->def['sql']);
		//$rv.=$sql;
		$drows=DB::Select($sql);
		$readonly=$this->isReadOnly();
		$rowmanage=(vars::v(COS::ifset($this->def['rowmanage'],'Y'))!='N');
		$shownumbers=(vars::v(COS::ifset($this->def['shownumbers'],'N'))!='N');
		//echo "--------<br>";print_r($this->def);
		$rv.='<table id="tfd_'.$this->name.'">
				<thead style="'.(count($drows)==0?'display:none':'').'"><tr>
					<th></th>';			
		foreach($this->fields as $field) {
			if ($field->fieldtype()!='hidden')
				$rv.="<th><div style='text-align:left'>".COS::lang($field->title())."</div></th>";
		}
		$rv.='</tr></thead>
				<tbody id="'.$this->name.'_odrows">';
		
		$rc=0;
		$rownumber=1;
		foreach($drows as $drow) { 
			vars::setvar('rownumber',$rownumber);	// set the fields in vars with the name of the field only
			if (isset($drow->id))
				vars::setvar('dcid',FormerObject::encodeId($drow->id));		// special daughter CID variable for daughterfields
			//$rv.=print_r($drow);continue;
			//$inps=array();
			//foreach($this->fields as $field) {
			//	$gridsize=0;
			//	$inps[]=$field->getbaseinput($drow,$gridsize);
			//}
			$rv.='<tr class="drow'.$rownumber.'"><td style="white-space:nowrap">';
			if (!$readonly && $rowmanage) 
				$rv.='<span class="smallbutton" id="pdelrow"><span class="glyphicon glyphicon-minus-sign"></span></span>';
			if ($shownumbers) {
				$rv.='<span class="badge badge-info" id="rownumber" style="margin:0 5px">'.$rownumber.'</span>';
			}
			$rv.='</td>';
			// foreach($inps as $inp) {
			// 	$rv.='<td>'.$inp.'</td>';
			// }
			foreach($this->fields as $field) {
				$gridsize=0;
				/** @var \Rvwoens\Former\fields\field $field */
				if ($field->fieldtype()!='hidden') {
					$formgroupattr='';
					$field->testShowCondition($formgroupattr);	// becomes visible/invisible or dynamic visible
					$urlbut=$field->getUrlButton('L');	// list-type
					$rv .= "<td ><div $formgroupattr data-dynvisparent='.drow".$rownumber."' data-dynvistable='#tfd_{$this->name}'>";
					$rv .= $field->getbaseinput($drow, $gridsize, $urlbut['html']);
					$rv .= '</div></td>';
				}
				else
					$rv.=$field->getbaseinput($drow,$gridsize);
			}
			$rv.='</tr>';
			$rc++;$rownumber++;
		} 
		if (!$readonly && $rowmanage) 
			$rv.='<tr id="'.$this->name.'_plrow"><td colspan="6"><span class="smallbutton" id="'.$this->name.'_paddrow"><span class="glyphicon glyphicon-plus-sign"></span></span></td></tr>';
		$rv.='</tbody></table>';

		// now scripts for adding and deleting rows
		$rv.="<script>
			$('#".$this->name."_paddrow').click(function() {
				// determin new rowid
				var newrowid=(new Date()).getTime();
				$('#".$this->name."_plrow').before('<tr class=\"drow'+newrowid+'\"><td><span class=\"smallbutton\" id=\"pdelrow\"><span class=\"glyphicon glyphicon-minus-sign\"></span></span>'+\n";
		if ($shownumbers) 
			$rv.="'<span class=\"badge badge-info\" id=\"rownumber\" style=\"margin:0 5px\">$rownumber</span>'+\n";
				
		$rv.=   "'</td>'+\n";		
		foreach($this->fields as $field) {
			$gridsize=0;
			if ($field->fieldtype()!='hidden') {
				$formgroupattr='';
				$field->testShowCondition($formgroupattr);	// becomes visible/invisible or dynamic visible
				$rv .= " '<td><div $formgroupattr data-dynvisparent=\".drow'+newrowid+'\" data-dynvistable=\"#tfd_{$this->name}\">".$field->getbaseinput(null, $gridsize)."</div></td>'+ \n";
			}
			else
				$rv.=" '".$field->getbaseinput(null,$gridsize)."'+ \n";
		}
		// need initialise_datas otherwise the new data-xxx will not work
		$rv.=" '</tr>'); 
				coslib.renumber_daughterblock('".$this->name."_odrows');
				coslib.initialise_datas();
				formerlib.initialise_datas(true);	// true=updateonly. Dont reinitialize things
				$('#tfd_{$this->name} thead').show();
				return false;
			});";
		// delegated click, otherwise the newly generated addrow rows will not get the event
		$rv.="	$('#".$this->name."_odrows').on('click','#pdelrow',function() {
				$(this).parent().parent().remove();
				coslib.renumber_daughterblock('".$this->name."_odrows');
				return false;
			});
		</script>";

		return $rv;
	}
	
	public function getinput($row) {
		if (isset($this->def['title'])) {
			return parent::getinput($row);
		}
		return $this->getbaseinput($row);	// raw
	}
	
	// no value for this field from the original database
	public function val($row=null,$pagetype='') {
		return '';
	}
	// after save we know the NEW id!! (its in vars)
	public function eventSaveAfter($id,$do) {
		if ($this->isReadOnly())
			return;	// no updates if readonly!
			
		// if inserted, $id is our NEW id
		if (isset($this->def['beforeupdsql'])) {
			$sql=vars::v($this->def['beforeupdsql'],true);	// true = doEscapeits a
			//echo "bforeupdate: $sql <br>";
			DB::statement($sql);	// no select, as that fetches rows!
		}
		// check daughterfields	
		foreach($this->fields as $field) {
			$firstfield=$this->name.'_'.$field->name;	// daughter_field without []
			//echo "firstfield= $firstfield <br>";
			break;
		}
		// use idfield or not.
		// if idfield is set
		// 		it must be the ID of the row table
		// 		then it is used to update if exists or insert if it is not.
		// if it is NOT set
		//		delete all rows on "beforeupdsql"
		//		insert all new rows using "updsql"
		$idfield='';
		if (isset($this->def['idfield'])) {
			$idfield=$this->def['idfield'];	// use idfield for updating
		}
		// just to be sure: our inputfields must be arrays!
		if (is_array(Input::get($firstfield))) {
			// use the first field to loop through the rows. $rnr will contain the row id
			foreach(Input::get($firstfield) as $rnr=>$v) {
				// just to get the right $rnr index!
				$idval=0;	// if not found all are inserts
				foreach($this->fields as $field) {
					$farr=Input::get($this->name.'_'.$field->name);
					if ($field->name==$idfield) {
						$idval=$farr[$rnr];
					}
					vars::setvar($field->name,$farr[$rnr]);	// set the fields in vars with the name of the field only
				}
				vars::setvar($this->name.'__row',$rnr+1);	// set to row number value  $FIELD__row  (1..nrrows)
				// call update for the row..
				if ($idfield ) {
					if ($idval>0) {
						// update existing row
						//echo "updatesql=".$this->def['updsql']."<br>";
						$sql=vars::v($this->def['updsql'],true);	// true = doEscape
					}
					else
						$sql=vars::v($this->def['inssql'],true);	// true = doEscape
				}
				else {
					//echo "updatesql=".$this->def['updsql']."<br>";
					$sql = vars::v($this->def['updsql'],true);	// true = doEscape
				}
				//echo "updsql= $sql <br>";vars::dump();
				DB::statement($sql);
			}
		}
		// $idlist contains (id,id,id) of existing values
		if (isset($this->def['afterupdsql'])) {
			$sql=vars::v($this->def['afterupdsql'],true);	// true = doEscape
			//echo "bforeupdate: $sql <br>";
			DB::statement($sql);
		}
	}
	public function getUpdateSql($do) {
		return array();
	}

	public function getValidateRule() {

		//// LARAVEL VALIDATOR FOR ARRAY INPUTS IS BROKEN!!!
		// ... $rules=[];
		//		// check how many rows we have
		//		foreach($this->fields as $field) {
		//			$firstfield=$this->name.'_'.$field->name;	// daughter_field without []
		//			//echo "firstfield= $firstfield <br>";
		//			break;
		//		}
		//		if (is_array(Input::get($firstfield))) {
		//			// use the first field to loop through the rows. $rnr will contain the row id
		//			foreach (Input::get($firstfield) as $rnr => $v) {
		//				// this can return an array of validation rules of the form  field=>rule
		//				// so we can just iterate all fields
		//				foreach($this->fields as $field) {
		//					$fieldrules=$field->getValidateRule();
		//					if (is_array($fieldrules)) {
		//						// should be of form [name => [rules...]]
		//						// but the name is wrong.. Use its current row variant
		//						$rules[$this->name.'_'.$field->name."[]"]=$fieldrules[$field->formfieldname];
		//					}
		//				}
		//				break;	// only for the first row
		//			}
		//		}
		//return $rules;

		return null;
	}
}