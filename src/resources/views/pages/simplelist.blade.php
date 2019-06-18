<?php
	use \Rvwoens\Former\Cos, \Rvwoens\Former\Vars;
	$name='';$card='';
	$pg=$pagination['page'];
	$limit=$pagination['limit'];
	$cnt=$pagination['count'];
	// a simplelist is used in a subformer field within a card
?>
<div class='mdl-cell mdl-cell--12-col ' {!!$formgroupattr!!} style='margin-top:20px;margin-bottom:12px;'>
	<legend class="col-md-12" style='font-weight:300;font-size:18px;margin-bottom:-10px;'>
		@include('former::pages.partials.list_title')
		@if (isset($subtitle) && $subtitle)
			<small><?=e($subtitle)?></h4></small>
		@endif
		<span class="xpull-right">
			{!!\Rvwoens\Former\cos::ifset($data['newhtml'],'')!!}
		</span>
	</legend>
</div>
<!-- Main content -->
<!-- form method="GET" action="" -->

				<!-- /.content -->
				<div class="mdl-cell mdl-cell--12-col" {!!$formgroupattr!!} style="margin-bottom:12px;padding:1px;overflow-x:auto">

						<?php if (isset($alerttitle) && $alerttitle) {?>
							<div class="row"><div class="col-md-12"><div class="alert alert-warning"><?=$alerttitle?></div></div> </div>
						<?php } ?>
						<table class="mmp-data-table mdl-data-table mdl-js-data-table mdl-shadow--2dp mdl-cell--12-col">
							<thead><tr>
								<?php if (isset($cardurl) && $cardurl)
									echo "<th>Action</th>";
								foreach($heads as $head) {
									echo "<th class='mdl-data-table__cell--non-numeric'>".$head."</th>";
								}
								?>
							</tr></thead>
							<tbody>
							<?php foreach ($rows as $drow) {
								echo $row->show($drow);
							}
							?>
							</tbody>
						</table>
						<div class="former-pagination">
							@if (false && $pg>0)
								<a href="{{Cos::fullUrlWithQuery(['pg'=>0])}}">First</a>
								<a href="{{Cos::fullUrlWithQuery(['pg'=>$pg-1])}}">&lt Prev</a>
							@endif
							Rows {{$pg*$limit+1}} - {{min($cnt,($pg+1)*$limit)}} of {{$cnt}}
							@if ( false && ($pg+1)*$limit<$cnt )
								<a href="{{Cos::fullUrlWithQuery(['pg'=>$pg+1])}}">Next &gt;</a>
								<a href={{Cos::fullUrlWithQuery(['pg'=>99999])}}>Last</a>
							@endif
						</div>
				</div>


<!-- /form -->

<?php if ($refresh) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			setInterval(function() {
				location.reload(true);
			},<?=1000*$refresh?>);
		});
	</script>
<?php } ?>