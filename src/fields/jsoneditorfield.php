<?php namespace Rvwoens\Former\fields;

use Rvwoens\Former\cos;

class jsoneditorfield extends \Rvwoens\Former\fields\htmleditorfield {

	public function getbaseinput($row) {
		$gridsize = array_get($this->def, 'size', '10');
		if (preg_match('/([0-9]+)px/', $gridsize, $match)) {
			$divattr = ' class="htmleditor col-md-10" style="width:'.($match[1]+30).
					   'px" ';    // size=20px format (col-md-.. adds padding 2x 15px, so add 30 to width)
		}
		else
			$divattr = ' class="htmleditor col-md-'.$gridsize.'" ';    // class="col-md-2" format

		// if (COS::ifset($this->def['readonly'],'N')=='Y') {
		// 	return "<span class=\"input-$fsize uneditable-input\">".$this->display($row)."</span>";	// READONLY
		// }
		$style = array('style' => 'margin-right:5px;display: none;',
					   'class' => ' form-control');        // generate TRUMBOWYG field

		if (isset($this->def['popover'])) {
			$style['data-toggle'] = 'popover';
			if (is_array($this->def['popover'])) {
				$style['data-title'] = $this->def['popover']['title'];
				$style['data-content'] = $this->def['popover']['content'];
				$style['data-placement'] = COS::ifset($this->def['popover']['placement'], 'top');
			}
			else {
				$style['data-content'] = $this->def['popover'];
			}
			$style['data-trigger'] = 'focus';
		}

		if (isset($this->def['placeholder']))
			$style['placeholder'] = array_get($this->def, 'placeholder');

		if (isset($this->def['editwidth']))
			$divattr .= ' data-editwidth='.$this->def['editwidth'];

		$style['rows'] = cos::ifset($this->def['rows'], 8);

		//define data-html="editor" so the editor will load
		$style['contenteditable'] = 'true';

		$style['id'] = 'jsoneditor_source_' . $this->formfieldname;

		$txtfields = "<div $divattr>".
					 \Form::textarea($this->formfieldname, $this->display($row), $style).
					 "<div id='jsoneditor_".$this->formfieldname."' style='height: 300px;'></div>".
					 "</div>";

		$txtfields .= '
			<script>
				jQuery(document).ready(function($) {
					var container = document.getElementById("jsoneditor_'.$this->formfieldname.'"),
						editor,
						options = {
						mode: \'code\',
						ace: window.ace,
						onChange: function () {

							try {

								var value = editor.get();

								jQuery("#jsoneditor_source_'.$this->formfieldname.'").val(JSON.stringify(value));

							} catch (e) {

							}
						}
					};

					editor = new JSONEditor(container, options);
					// set it with a json object, not a string!
					editor.set('.($this->val($row) ?: '{}').');
				});
			</script>
			';

		return $txtfields;
	}

}