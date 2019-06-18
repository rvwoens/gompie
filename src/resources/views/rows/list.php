<?php 	use Rvwoens\Gompie\vars;
?>
<tr <?=(isset($rowurl)&&$rowurl)?'class="clickable" data-url="'.vars::v($rowurl,false,true).'"':''?>>
<?php 	if (isset($cardurl) && $cardurl) { ?>
		<td><?=HTML::linkicon(vars::v($cardurl,false,true),'eye-open')?></td>
<?php 	}
	foreach($inps as $fnr=>$inp) {
		$style='';
		if (!$fields[$fnr]->wrap()) {
			$style='white-space:nowrap;';
		}
		switch($fields[$fnr]->listalign()) {
		case 'C':
			$style.='text-align: center;';break;
		case 'R':
			$style.='text-align: right;';break;
        default:
            $style.='text-align: left;';break;
		}
		$style.=$fields[$fnr]->listTdStyle();
		$class=$fields[$fnr]->listTdClass();
		echo "<td style='$style' class='$class'>".$inp."</td>";
	}
?>
</tr>
