<?php namespace Rvwoens\Former\fields;

use Laravel, Bootstrapper, Rvwoens\Former, Rvwoens\Former\Cos, Rvwoens\Former\Vars;
use Exception;
use Form;
use Log;

// base field class
class jsonfield extends editablefield
{
	/**
	 * @htmlOutput for a field
	 * @var string
	 */
	private $output = '';

	/**
	 * @param null $row
	 * @return string
	 */
	public function postval($row=null) {
		$keys = (\Input::get($this->name . '_key',[]));
		$values = (\Input::get($this->name . '_value',[]));

		$combinedArray = array_combine($keys,$values);

		$emptyIndex = 0;

		foreach($combinedArray as $key => $value) {
			if(trim($key) == '') {
				unset($combinedArray[$key]);
				if (trim($value)!='') {
					$key = COS::lang('nl::sleutelLeeg|en::emptyIndex'). "_" . $emptyIndex;
					while(array_key_exists($key, $combinedArray)) {
						$emptyIndex++;
						$key = COS::lang('nl::sleutelLeeg|en::emptyIndex'). "_" . $emptyIndex;
					}
					$combinedArray[$key] = $value;
				}
			}

			//AS PER http://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php
			if($value != '') {
				json_decode($value);
				if (json_last_error()===JSON_ERROR_NONE) {
					$value = json_decode($value);
					$combinedArray[$key] = $value;
				}
			}
		}

		$this->val = json_encode($combinedArray);
		return $this->val;
	}

	public function getinput($row) {

		$attributes = ['class' => 'form-control'];
		$data = $this->decodeJsonRow($row);
		$metaData = $this->extractFieldMetaData();

		//fields defined are added to the data
		$this->amendDataFromMetaData($metaData, $data);

		if ($this->isReadOnly()) {
			return $this->generateReadOnlyHtml($data, $metaData);
		}

		if (isset($this->def['placeholder'])) {
			$attributes['placeholder'] = array_get($this->def, 'placeholder');
		}

		return $this->generateHtml($data, $attributes, $metaData);
	}

	/**
	 * @author Daniel
	 * @param $data
	 * @param $metaData
	 * @return string
	 */
	private function generateReadOnlyHtml($data, $metaData)  {

		$index=0;

		foreach ($data as $key => $value) {
			$this->output .= $this->generateStaticRow($index,$key,$value,[], $metaData);
			$index++;
		}

		return $this->output;
	}

	/**
	 * @param array $data
	 * @param array $attributes
	 * @param array $metaData
	 * @return string
	 */
	private function generateHtml(array $data, array $attributes, array $metaData) {
		try {
			$this->output .= "<div id='jsonfieldwrapper_{$this->formfieldname}'>";
			$index = 0;
			foreach ($data as $key => $value) {
				$value = !is_string($value) ? json_encode($value) : $value;
				$this->output .= $this->generateRow($index, $key, $value, $attributes, array_get($metaData, $key, []));
				$index++;
			}
			$this->generateFooter($index);
		} catch(Exception $e) {
			Log::error("JSONFIELD error: ".  $e->getCode() . ' ' .  $e->getMessage() . ' ' . $e->getFile() .  ' ' .   $e->getLine());
		} finally {
			return $this->output;
		}
	}

	private function generateFooter($index) {
		$this->output .= '</div>';
		if($this->output == '') {
			$this->output .= "<div class='form-group'>";
			$this->output .= "<label class='col-md-2 control-label' for='" . strtolower($this->formfieldname)."'>".$this->formfieldname."</label>";
		} else {
			$this->output .= "<div class='form-group'>";
			$this->output .= "<div class='col-md-2'></div>";
		}
		$this->output .= Form::hidden($this->formfieldname . '_index',$index,['Id' => $this->formfieldname . '_index']);
		//Plusje er altijd in
		$this->output .= "<div class='col-md-8'><div class='row'><span class='smallbutton' data-add-row-to-jsonfield='jsonfieldwrapper_{$this->formfieldname}'><span class='glyphicon glyphicon-plus-sign'></span></span></div></div>";
		$this->output .= "</div>"; // </ div.formgroup >
	}

	private function generateStaticRow($index, $key, $value, $attributes, $meta) {

		$returnHtmlString = "<div class='form-group'  id=\"' . $this->formfieldname . '_row_' . $index . '\">";
		$returnHtmlString .= '<label class="col-md-2 control-label" for="' . e(strtolower($key)).'">'.xcos::lang(array_get($meta, 'title', e($key))).'</label>';
		$returnHtmlString .= '<div class="col-md-10"><div class="row"><div class="col-md-10"><p class="form-control-static">' . $value .  '</p></div></div></div>';
		$returnHtmlString .= '</div>';

		return $returnHtmlString;
	}

	private function generateRow($index, $key, $value, $attributes, $meta) {
		$classes = array_get($meta, 'hidden', false) ? 'hidden' : '';
		$returnHtmlString = "<div class='form-group $classes'  id='{$this->formfieldname}_row_{$index}'>";
		//if a title was defined in the meta for this field, show it, default to the "raw" key of the field
		$returnHtmlString .= '<label class="col-md-2 control-label" for="'.e(strtolower($key)).'">'.cos::lang(array_get($meta, 'title', e($key))).'</label>';
		$returnHtmlString .= Form::hidden($this->formfieldname.'_key['.$index.']', $key, $attributes);
		$returnHtmlString .= '<div class="col-md-10"><div class="row">';

		$returnHtmlString .= '<div class="col-md-10">' . Form::text($this->formfieldname.'_value['.$index.']', $value, $attributes) . '</div>';

		//if meta says it is removable, include the ui to do so
		if (!count($meta) || array_get($meta, 'removable', false)) {
			$returnHtmlString .= '<div class="col-md-2">';
			$returnHtmlString .= "<a data-remove-row='{$this->formfieldname}_row_{$index}'><i style='font-size:24px;' class='icon-trash'></i></a>";
			$returnHtmlString .= '</div>';
		}

		$returnHtmlString .= '</div></div>';
		$returnHtmlString .= '</div>';

		return $returnHtmlString;
	}

	/**
	 * Decode json row content
	 *
	 * @param string $row
	 * @return array
	 */
	private function decodeJsonRow($row) {
		try {
			//try to parse json, if null is returned, force array
			return cos::parseJson($this->val($row), $err) ?: [];
		} catch (Exception $e) {
			//if exception is caught, force array
			return [];
		}
	}

	/**
	 * @return array
	 */
	protected function extractFieldMetaData() {
		$metaData = [];

		//unset the predefined columns from our data
		if ($columns = array_get($this->def, 'fields', [])) {
			foreach ($columns as $key => $def) {
				//if our definition is simple, assume field title was supplied as "value"
				$metaData[$key] = is_array($def) ? $def : ['title' => $def];
			}
		}

		return $metaData;
	}

	private function amendDataFromMetaData($metaData, &$data) {
		foreach($metaData as $key => $def) {
			if (!isset($data[$key])) {
				$data[$key] = '';
			}
		}
	}

}