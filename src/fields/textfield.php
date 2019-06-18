<?php namespace Rvwoens\Gompie\fields;

use Rvwoens\Gompie\Cos;
use Rvwoens\Gompie\Vars;
use Exception;

// base field class
class textfield extends editablefield {

	public function getbaseinput($row,&$gridsize, $addon='') {
		$gridsize=array_get($this->def,'size','6');

		if (preg_match('/[0-9]+px/',$gridsize,$match)) {
			$divattr=' style="width:'.$gridsize.'" ';	// size=20px format
			$restattr=' ';
		}
		else {
			$divattr = ' class="col-md-'.$gridsize.'" ';    // class="col-md-2" format
		}
		if ($this->isReadOnly()) {
			// NO SINGLE QUOTES!!
			// lets add addon for daughter!
			//// $inputfield= "<p class=\"former-cardfield former-card-readonly \">".$this->display($row)."</p>";
			return "<div $divattr><p class=\"form-control-static wrap-break \">".$this->display($row).$addon."</p></div>";	// READONLY

		}

		$style = array( 'style' => 'margin-right:5px;', 'class' => ' form-control' );

		if (isset($this->def[ 'popover' ])) {
			$style[ 'data-toggle' ] = 'popover';
			if (is_array($this->def[ 'popover' ])) {
				$style[ 'data-title' ] = COS::lang($this->def[ 'popover' ][ 'title' ]);
				$style[ 'data-content' ] = COS::lang($this->def[ 'popover' ][ 'content' ]);
				$style[ 'data-placement' ] = COS::ifset($this->def[ 'popover' ][ 'placement' ], 'top');
			}
			else {
				$style[ 'data-title' ] = COS::lang("nl::Informatie over dit veld|en::Information about this field");
				$style[ 'data-content' ] = COS::lang($this->def[ 'popover' ]);
			}
			$style[ 'data-trigger' ] = 'focus';
		}

		if (isset($this->def[ 'placeholder' ]))
			$style[ 'placeholder' ] = array_get($this->def, 'placeholder');

		// cant use this because $value sometimes is an array when name is like xxx[] .. bug in laravel
		//\Form::text($this->formfieldname,
		//			$disp,
		//			$style)
		$style[ 'name' ] = $this->formfieldname;
		$style[ 'value' ] = $this->display($row);
		$inputfield = "<input ".\HTML::attributes($style).">";

		if ($addon) {
			// stick an input-group-addon to the right
			$txtfields="<div $divattr><div class=\"input-group\">".
				'<input '.\HTML::attributes($style).">$addon</div></div>";
		}
		else {
			$txtfields="<div $divattr>".
				'<input '.\HTML::attributes($style)."></div>";

		}

		return $txtfields;
	}
	
}