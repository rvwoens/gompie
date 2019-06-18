<?php
namespace Rvwoens\Former\fields;

use DB;
use Laravel, Bootstrapper, Rvwoens\Former, Rvwoens\Former\Cos, Rvwoens\Former\Vars, \exception;
use stdClass;
use xcos;

/**
 * Class jsonkv
 *
 * Collects separate key's for a single column
 *
 * @package Rvwoens\Former\fields
 */
class jsonkvfield extends textfield {

	const COLUMN_DEFINITION = 'json_column';

	const DEFAULT_COLUMN = 'extra_data';

	/**
	 * Retrieves the value of a single key from the json column
	 *
	 * @param stdClass|null $row
	 * @return mixed
	 */
	public function val($row = null) {

		//determine which column is our json column
		$jsonColumn = $this->getDefinition(self::COLUMN_DEFINITION, self::DEFAULT_COLUMN);

		//retrieve the json decoded content of the column
		$data = xcos::parseJson(object_get($row ?: new stdClass(), $jsonColumn)) ?: [];

		//return the value inside the de-serialized json corresponding to the form field name
		return array_get($data, $this->formfieldname());
	}

	/**
	 * Disable update/insert sql for this column, for it is a virtual column
	 *
	 * @param $do
	 * @return string
	 */
	public function getDefUpdateSql($do) {

		//get column defined to store our json to
		$column = $this->getDefinition(self::COLUMN_DEFINITION, self::DEFAULT_COLUMN);

		//retrieve fields with the same field-type and column definition
		$fields = array_filter($this->owner->getFields(), function (field $field) use ($column) {

			//check if field type is the same as ours
			if ($field->fieldtype()!=$this->fieldtype()) {
				return false;
			}

			//check if json column is same as ours
			return $field->getDefinition(self::COLUMN_DEFINITION, self::DEFAULT_COLUMN)==$column;

		});

		//rebuild array for json serialization [$fieldname => $postaval]
		$data = array_reduce($fields, function ($carry, field $field) {
			$carry[$field->formfieldname()] = $field->postval();
			return $carry;
		}, []);

		//if the json column is defined in an other manner, merge data
		$this->mergeFormFieldData($data);

		//["extra_data" => "{\"$fieldname\": \"$fieldvalue\"}"]
		return [$column => json_encode($data)];
	}

	/**
	 * Json columns can be referenced by other field types (e.g. jsonfield, etc)
	 * make sure we merge with those if they exist in this form and use them as merge base
	 *
	 * @param $data
	 */
	private function mergeFormFieldData(&$data) {

		//get column defined as our json column
		$column = $this->getDefinition(self::COLUMN_DEFINITION, self::DEFAULT_COLUMN);

		//find fields matching the formfieldname with our json column
		$fields = array_filter($this->owner->getFields(), function (field $field) use ($column) {
			return $field->formfieldname()==$column;
		});

		//retrieve the first field available
		$field = reset($fields);

		//if available, merge its data with our gathered data
		if ($field&&$field instanceof editablefield) {

			//decode form field data
			$formFieldData = xcos::parseJson($field->postval()) ?: [];

			//merge with our data, using the form field data as base
			$data = array_replace_recursive($formFieldData, $data);

			//hard set the value
			$field->forceSetValue(json_encode($data));
		}
		elseif ($id = vars::getvar('id')) {

			//we dont have the actual json_column in our fields definition
			//make sure we retrieve existing values from the table by querying
			$query = sprintf('SELECT %s FROM %s WHERE `id` = ?;', $column, $this->owner->getdef('table'));

			//retrieve the json column from the database
			$formFieldData = xcos::parseJson(object_get(DB::selectOne($query, [$id]), $column, '{}')) ?: [];

			//recursively replace the json column with our added jsonkv data
			$data = array_replace_recursive($formFieldData, $data);
		}
	}

}
