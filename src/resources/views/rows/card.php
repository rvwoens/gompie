<?php use Rvwoens\Former\FormerObject;
// only supply id field if there IS an id
if ($id>0) {
	echo Form::hidden('id',$id);
	echo Form::hidden('cid',FormerObject::encodeId($id));
}
foreach($inps as $inp)
	echo $inp;