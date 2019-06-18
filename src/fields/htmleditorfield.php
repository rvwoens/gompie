<?php
namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Rvwoens\Former,Rvwoens\Former\Cos, Rvwoens\Former\Vars,\exception;

// base field class
class htmleditorfield extends editablefield {

	public function getbaseinput($row) {
		$gridsize=array_get($this->def,'size','10');
		if (preg_match('/([0-9]+)px/',$gridsize,$match)) {
			$divattr=' class="htmleditor col-md-10" style="width:'.($match[1]+30).'px" ';	// size=20px format (col-md-.. adds padding 2x 15px, so add 30 to width)
		}
		else
			$divattr=' class="htmleditor col-md-'.$gridsize.'" ';	// class="col-md-2" format

		// if (COS::ifset($this->def['readonly'],'N')=='Y') {
		// 	return "<span class=\"input-$fsize uneditable-input\">".$this->display($row)."</span>";	// READONLY
		// }
		$attr=[
			'style'=>'margin-right:5px;',
			'class'=>' form-control trumbowyg',
			'data-options' => '{}' //add some options to decorate our html editor with by default
		];		// generate html editor field

		if ($this->isReadOnly()) {
			// NO SINGLE QUOTES!!
			return "<div class=\"col-md-$gridsize\"><p class=\"form-control-static \">".$this->display($row)."</p></div>";	// READONLY
		}
		if (isset($this->def['popover'])) {
			$attr['data-toggle']='popover';
			if (is_array($this->def['popover'])) {
				$attr['data-title']=$this->def['popover']['title'];
				$attr['data-content']=$this->def['popover']['content'];
				$attr['data-placement']=COS::ifset($this->def['popover']['placement'],'top');
			}
			else {
				$attr['data-content']=$this->def['popover'];
			}
			$attr['data-trigger']='focus';
		}

		if (isset($this->def['placeholder']))
			$attr['placeholder']=array_get($this->def,'placeholder');

		if (isset($this->def['editwidth']))
			$divattr.=' data-editwidth='.$this->def['editwidth'];

		$attr['rows']=COS::ifset($this->def['rows'],5);
		//define data-html="editor" so the editor will load
		$attr['contenteditable'] = 'true';

		$txtfields = "<div $divattr>".
					 \Form::textarea($this->formfieldname,
									$this->display($row),
									$attr
					 )."</div>";

		$txtfields.='
			<script>
				jQuery(document).ready(function($) {
					$(".trumbowyg-editor").css("width", 320);
				});
			</script>
			';

		return $txtfields;
	}

}