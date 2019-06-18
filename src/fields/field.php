<?php namespace Rvwoens\Gompie\fields;

use Illuminate\Support\Facades\Input;
use Laravel;
use Bootstrapper;
use	Rvwoens\Gompie\Cos;
use	Rvwoens\Gompie\vars;
use	\Session;
use	\Messages;
use	\Log;
use	\Form;
use	\DB;
use	\Exception;

// base field class
abstract class field {

	/**
	 * Field definition
	 *
	 * @var array
	 */
	protected $def=[];

	/**
	 * Field name
	 *
	 * @var mixed|string
	 */
	public	$name="";			// base fieldname required!

	/**
	 * Form field name, equals query column name most of the time
	 *
	 * @var mixed|string
	 */
	public	$formfieldname="";	// formfieldname normally equals querycolomname

	/**
	 * Raw field value
	 *
	 * @var mixed|string
	 */
	protected $val="";

	/**
	 * Field title
	 *
	 * @var string
	 */
	protected $title="";


	/**
	 * @var Former\rows\row
	 */
	protected $owner=null;

	//******************************************************************************************************
	//	def: 
	// 		name 		- fieldname = row-fieldname!
	// 		val 		- current value (not from record yet)
	// 		title 		- humanreadable title  
	// 		placeholder - placeholder value (helptext)
	// 		help 		- helptext outside field
	//******************************************************************************************************
	public function __construct($def=[],$owner) {

		//if definition was not array, throw exception and abort
		if (!is_array($def)) {
			throw new exception("former\field: def is not an array ".print_r($def, true));
		}

		$this->val=array_get($def,'default','');
		$this->name=array_get($def,'name','namerequired');
		$this->formfieldname=$this->name;
		$this->def=$def;
		$this->owner=$owner;
	}

	/**
	 * Static constructor creation the correct field type
	 *
	 * @param array $def
	 * @param $owner
	 * @return field
	 * @throws exception
	 */
	static public function make($def=[],$owner) {

		//infer field type from definition, default to 'text' type
		$type=array_get($def,'type','text');

		//build field class name and check for class existence
		if (class_exists($fieldClass = (__NAMESPACE__ . '\\' . strtolower($type).'field'))) {

			//if exists, instantiate
			return new $fieldClass($def, $owner);
		}

		throw new Exception("former\field: Fieldtype \"$type\" not known");
	}

	/**
	 * Basic methods
	 */
	// connect a field to its row data
	public function connect($row) {
	}
	// actual plain DATABASE value, default, old, row etc
	// First: FIXED, then OLD, than ROW, than DEFAULT
	public function val($row=null) {
		if (isset($this->def['fixed']))
			return vars::v($this->def['fixed']);
		if ($row && property_exists($row,$this->name)) {
			$value=$row->{$this->name};
			if ((is_null($value) || $value=='') && isset($this->def['emptydefault'])) {
				$value=vars::v($this->def['emptydefault']);	// flawed: If you do not save, the field is not updated with this value..
			}
		}
		else
			$value= vars::v($this->val);
		return Input::old($this->name, $value );
	}
	// display value defaults to Db value
	// for DISPLAYING it, so HTML-escaped
	public function display($row=null) {
		if (isset($this->def['html']))
			return vars::v($this->def['html']);	// do NOT escape HTML-definitions from the yaml. They are safe
		return e($this->val($row));
	}

	/**
	 * Return field definition
	 * @return array
	 */
	public function def() {
		return $this->def;
	}

	public function title() {
		// return field title
		return vars::v(array_get($this->def,'title',ucfirst($this->name)));
	}
	public function name() {
		return $this->name;
	}
	public function fieldtype() {
		if (isset($this->def['type']))
			return $this->def['type'];
		return 'text';
	}
	public function wrap() {
		if (isset($this->def['wrap']))
			return $this->def['wrap']=='Y';
		return true;	// default we WRAP
	}
	public function listalign() {
		if (isset($this->def['listalign']))
			return $this->def['listalign'];	// C/R/L
		return 'L';	// left
	}
	public function listTdStyle() {
		if (isset($this->def['liststyle']))
			return $this->def['liststyle'];
		return '';	// default no extra <td> styles
	}

	public function listTdClass() {
		if (isset($this->def['listclass']))
			return $this->def['listclass'];
		return '';	// default no extra <td> styles
	}

	public function formfieldname() {
		return $this->formfieldname;
	}
	public function setvar($row) {
		// set my value in vars
		vars::setvar($this->name,$this->val($row));
	}
	// show current value
	public function __toString() {
		return $this->val();
	}
	//return true if the fields does a fileupload
	public function hasFileUpload() {
		return false;
	}
	// true when adding a card instead of editing
	public function isAdding() {
		return !vars::getvar('id');	// id not set -> we are adding
	}
	// is readonly (or we dont have the token)
	public function isReadOnly() {
		$ro=COS::ifset($this->def['readonly'],'N');
		if (is_bool($ro)) {
			$ro= ($ro?'Y':'N');
		}
		if (vars::v($ro)=='Y')
			return true;
		if ($ro=='A')	// readonly when ADDING
			if ($this->isAdding())
				return true;
		if ($ro=='E')	// readonly when EDITING
			if (!$this->isAdding())
				return true;
		if (isset($this->def['edittoken'])) {
			$token=vars::v($this->def['edittoken']);
			// we need the token!! readonly if we NOT have it
			if ($token)
				return !$this->owner->hasTokenValue($token);	// call the callback tokenfunc
		}
		return false;
	}
	public function forceHidden() {
		if (isset($this->def['viewtoken'])) {
			$token=vars::v($this->def['viewtoken']);
			// we need the token!! hidden if we NOT have it
			if ($token)
				return !$this->owner->hasTokenValue($token);	// call the callback tokenfunc
		}
		return false;
	}

	//********************************************************************************
	// Default NON-editable - Derive from EditableField to get an updateable field
	//********************************************************************************
	public function postval($row=null) {
		return '';
	}
	public function errtext() {
		return '';
	}
	public function getValidateRule() {
		return null;	// no validation
	}
	// Def-overridden update and insertsql. Even for non-editable fields!
	public function getDefUpdateSql($do) {
		$defval=$do=='insert'?'insertsql':'updatesql';
		if (isset($this->def[$defval])) {
			//echo "def: $defval";
			if (trim($this->def[$defval])=='')
				return [];	// do nothing if the def is empty
			return [$this->name=>DB::Raw(vars::v($this->def[$defval]))];
		}
		// works always
		if (isset($this->def['sql'])) {
			if (trim($this->def['sql'])=='') {
				return [];	// do nothing if the def is empty
			}
			log::info("former/field: SQLDEF for field {$this->name}: ".$this->def['sql']." => ".vars::v($this->def['sql']) );
			return [$this->name=>DB::Raw(vars::v($this->def['sql']))];
		}
		return null;
	}

	public function getUpdateSql($do) {
		// even for NON-updateable fields there can be an updatesql in the def
		if ( ($rv=$this->getDefUpdateSql($do))!==null) {
			return $rv ? $rv : [];    // overridden! (if empty-> do not update)
		}
		return [];
	}
	public function eventSaveAfter($id,$did) {
		// called AFTER the card is updated/inserted. For insert, the ID is now known
	}
	public function allowDel() {
		return true;	// by default deleting is allowed
	}

	//*********************************************************************************
	// default BOOTSTRAP card method
	//*********************************************************************************
	// for LIST
	public function getListColumn($row) {
		$disp=$this->display($row);
		$urlbutton=$this->getUrlButton('L',$row);
		$disp.=$urlbutton['html'];
		return $disp;
	}

	public function testShowCondition(&$formgroupattr,$row=null) {
		if (isset($this->def['showcondition']) || isset($this->def['visibility'])) {
			$showc=$this->def[isset($this->def['showcondition']) ? 'showcondition':'visibility'];
			if (is_array($showc)) {
				// showcondition: {..complex..}
				$fldname=vars::v($showc['field']);
				if (isset($showc['expr'])) {
					// dynamic showc  {field:..., expr: "javascript eval"}
					$formgroupattr = 'data-dynvisfield="'.$fldname.'" data-dynvisexpr="'.e($showc['expr']).'"';
				}
				else {
					//showcondition: {field: ctype, isequal/isnotequal: ... }
					if ($row && isset($row->$fldname)) {
						// non-javascript simple tests on row
						$fieldval = $row->$fldname;
						if (isset($showc['isequal'])) {
							return $fieldval==$showc['isequal'];
						}
						if (isset($showc['isempty'])) {
							if ($showc['isempty']) {
								// test if emopty
								return trim($fieldval)=='';
							}
						}
					}
				}
			}
			else {
				// conditional show
				switch($this->def['showcondition']) {
				case 'notempty':
					if ($this->val($this->owner->row)=='')
						return false;	// invisible when empty
				case 'list':
					return false;	// not visible on card
				case 'notadd':
					if (!vars::getvar('id'))
						return false;
				}
			}
		}
		return true;
	}

	public function getUrlButton($cardlist='C',$row=null) {
		$url=COS::ifset($this->def['url'],null);
		if ($url) {
			//<span class="badge badge-info"><i class="icon-play icon-white"></i></span>
			$url=$this->def['url'];
			if (!is_array($url)) {
				$url = ($cardlist=='L') ?
					['url' => $url, 'cardlist' => 'CL', 'style' => 'icon', 'title' => 'click', 'rawurl'=>'N'] :
					['url' => $url, 'cardlist' => 'CL', 'type' => 'button', 	'opentype' => 'href', 'title' => '>>','rawurl'=>'N'];
			}
			if (strpos(COS::ifset($url['cardlist'],'CL'),$cardlist)===false)
				return ['html'=>'','append'=>''];
			if ($cardlist=='C' && !vars::getvar('id') && COS::ifset($url['showcondition'],'')=='notadd')
				return ['html'=>'','append'=>''];	// we are in ADD mode (no id) with showcondition notadd on the card
			if (COS::ifset($url['showcondition'],'')=='notempty' && $row && !$this->val($row))
				return ['html'=>'','append'=>''];	// only show when value is not empty

			// url: {type: button, cardlist: C, title: "Recalculate >>", url: /backoffice/recalc/$!id}
			// >> is replaced by PLAY icon
			$uurl=vars::v($url['url'],false, COS::ifset($url['rawurl'],'N')!='Y' );	// urlencodevars if rawurl not N
			if (Cos::ifset($url['backurl'],'N')=='Y') {
				// store current url in backurl session var
				Session::put('backurl',\Request::getRequestUri());
			}
			// On the list we use STYLE, on the card we use TYPE
			if ($cardlist=='L') {
				if (vars::v(COS::ifset($url['show'],''))=='N')
					return ['html' => '', 'append' => ''];	// do not show
				switch (COS::ifset($url['opentype'], 'href')) {
				case 'external':
					$extraAnchor = 'target="_blank"';
					break;
				default:
					$extraAnchor='';
				}
				switch(COS::ifset($url['style'],'icon')) {
				default:
				case 'icon':
					$disp='&nbsp;<a style="text-decoration:none" '.$extraAnchor.' href="'.$uurl.'">&#x25b6;</a>';
					break;
				case 'javascript':
					$disp = "<a href='#' onclick='(function () { ".vars::v($url['script'], false, false)." })();'>".vars::v($url['title'])."</a>";
					break;
				case 'button':
					$bclass=COS::ifset($url['listclass'],'btn-danger');
					$disp='&nbsp;<a class="btn '.$bclass.'" href="'.vars::v($url['url'],false,true).'"> '.COS::lang(vars::v($url['title'])).'</a>';
					break;
				case 'minibutton':
					$bclass=COS::ifset($url['listclass'],'btn-default btn-xs');
					// url: encode HTML, not URL!
					$disp='&nbsp;<a class="btn '.$bclass.'" style="font-size:11px;float:right" cc="badge alert-info" href="'.vars::v($url['url'],true,false).'"><span class="glyphicon glyphicon-play"></span> '.COS::lang(vars::v($url['title'])).' </a>';
					break;
				case 'colbutton':
					$bclass=COS::ifset($url['listclass'],'btn-default btn-xs');
					$disp='<a class="btn '.$bclass.'" style="font-size:11px;" cc="badge alert-info" href="'.vars::v($url['url'],true,false).'"><span class="glyphicon glyphicon-play"></span> '.COS::lang(vars::v($url['title'])).' </a>';
					break;
				}
				return ['html' => $disp, 'append' => ''];
			}
			else {
				// url on the CARD
				$utitle = COS::ifset($url['title'], ' >>');
				$utitle = str_replace('>>', ' <span class="glyphicon glyphicon-play"></span>', $utitle);
				$style='';
				switch (COS::ifset($url['type'], 'button')) {
				default:
				case 'link':
					$btype = 'button';
					$class = '';
					break;
				case 'button':
					$btype = 'button';
					$class = 'btn btn-info btn-xs';
					$style = "right:0px;top:4px;";
				case 'smbutton':
					$btype = 'button';
					$class = 'btn btn-info btn-sm';
					break;
				case 'lgbutton':
					$btype = 'button';
					$class = 'btn btn-info';
					break;
				case 'colbutton':
				case 'minibutton':
					$btype = 'button';
					$class = 'btn btn-danger btn-sm';
					break;
				case 'badge':
					$btype= 'button';
					$class = 'badge alert-info';
					break;
				case 'append':
					$btype = 'append';
					$class = 'input-group-addon btn btn-danger';
					break;
				}
				switch (COS::ifset($url['opentype'], 'href')) {
				default:
				case 'href':
					$anchor = "<a class=\"$class\" style=\"$style\" href=\"$uurl\">".COS::lang($utitle)."</a>";
					break;
				case 'external':
					$anchor = "<a class=\"$class\" target='_blank' style=\"$style\" href=\"$uurl\">".COS::lang($utitle)."</a>";
					break;
				case 'openwin':
					$anchor = "<span class=\"$class\" data-urlopenwin=\"$uurl\" data-width=\"".
							  COS::ifset($url['winwidth'], '320')."\" data-height=\"".
							  COS::ifset($url['winheight'], '500')."\">".COS::lang($utitle)."</span>";
					break;
				case 'ajaxvalue':
					// do an ajaxcall and put the returning value in the field with name=formfieldname
					$dataExtrafield=isset($url['extrafield']) ? "data-extrafieldselector=\"[name='".$url['extrafield']."']\"" : "";
					$anchor =
						"<span class=\"$class\" style=\"$style\" data-ajaxvalue=\"$uurl\" data-ajaxtargetselector=\"[name='{$this->formfieldname}']\" $dataExtrafield >".
						COS::lang($utitle).'</span>';
					break;
				case 'modalwin':
					// open modal window using data-toggle="modal" and show the contents from url in a modal window
					// the url should return something like:   <div class="modal-dialog"> ... </div>
					// tabindex=-1 allows the ESC key!
					$nam = $this->def['name'];
					$anchor = "<span class=\"$class\" data-toggle='modal' data-target=\"#modal_$nam\" id=\"btn_$nam\">".COS::lang($utitle)."</span>
								<div id=\"modal_$nam\" class='modal fade' tabindex='-1'>
									<div class='modal-dialog' role='document' style='width:1024px;background-color:#fff;'>
										<div class='modal-content'>
											<iframe id='modal_if_$nam' src='$uurl' style='width:1024px;height:600px;overflow:hidden' scrolling='no'></iframe>
										</div>
										<!--div class='modal-footer'>
       										 <button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
										</div-->
									</div>
								</div>";

					//$anchor .= "\n<script type=\"text/javascript\">jQuery(document).ready(function(){ var url = \"$uurl\";
					//           jQuery('#btn_$nam').click(function(e) { $('#modal_if_$nam').attr('src',url); }); });</script>\n";
					break;
				}
				if ($btype=='append')
					return ['html' => '', 'append' => $anchor];
			}
			return ['html'=>'&nbsp'.$anchor,'append'=>''];
		}
		return ['html'=>'','append'=>''];
	}

	// for CARD
	// generate this form's input field based on our BASEinputfield
	// this handles connected CARDROW's etc
	public function getinput($row) {
		$formgroupattr='';
		if (!$this->testShowCondition($formgroupattr))
			return '';
		if ($this->forceHidden())
			return Form::hidden($this->formfieldname,$this->val($row));

		$urlbutton=$this->getUrlButton();
		$gridsize=array_get($this->def,'size','6');
		$inps=$this->getbaseinput($row,$gridsize,$urlbutton['append']);
		if (isset($this->def['cardrow'])) {
			// cardrow "combination" definition with other fields
			$cardrow=$this->def['cardrow'];
			// format: cardrow: {with: [..]}  or cardrow: [...]
			if (!isset($cardrow['with'])) {
				$with=$cardrow;
				$title=$this->title();
			}
			else {
				$title = COS::ifset($cardrow['title'], $this->title());
				$with = $cardrow['with'];
			}
			foreach($with as $efield) {
				// use our owner (container) to find our connected field.
				$oefield=$this->owner->findField($efield);
				if (!isset($cardrow['title']))
					$inps.='<label class="col-md-2 control-label" for="'.$oefield->name.'">'.Cos::lang($oefield->title()).'</label>';
				$inps.=$oefield->getbaseinput($row,$egridsize);

				$gridsize+=$egridsize;
				$this->owner->skipfield($efield);	// no need to show it again
			}
		}
		else {
			$title=$this->title();
			//$inps=$this->getbaseinput($row, $gridsize); // already have it..
		}
		$inps.=$urlbutton['html'];

		if (isset($this->def['helptext'])) {
			if ($gridsize>=12)
				$hbsize=12;	// full width UNDER the field
			else
				$hbsize=12-$gridsize;	// fill available space right of the field
			$inps.="<div class='col-md-$hbsize'><span class='help-block help-block-inline'>".COS::lang($this->def['helptext']).'</span></div>';
		}
		$inps=str_replace('##addonmarker##','',$inps);	// remove remaining addonmarker
		$err=$this->errtext();
		$rv='<div class="form-group" '.$formgroupattr.' >'.
			'<label class="col-md-2 control-label" for="'.$this->name.'">'.Cos::lang($title).'</label>'.
			'<div class="col-md-10"><div class="row">'.
			$inps.
			($err?'<p class="help-block">'.$err.'</p>':'').
			'</div></div>'.
			'</div>';
		// $rv=Bootstrapper\Form::control_group(
		// 	Bootstrapper\Form::label($this->name,$title),
		// 	$inps,'',
		// 	Bootstrapper\Form::block_help($this->errtext())
		// );
		return $rv;	}

	/**
	 * Retrieve a single definition key value
	 *
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function getDefinition($key, $default = null) {

		if (array_key_exists($key, $this->def)) {
			return $this->def[$key];
		}

		return $default;
	}

}	
