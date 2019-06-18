<?php
	use Rvwoens\Gompie\Cos;
	use Rvwoens\Gompie\Vars;
	$name='';$card='';
	$pg=$pagination['page'];		// current page
	$limit=$pagination['limit'];	// sql limit
	$cnt=$pagination['count'];		// select count(*) = nr rows
	$nrpages=$pagination['nrpages'];	// basically count/limit

?>
@section('pageTitle')
	<!-- this is used in the TOP bar of the page -->
	{{ \Rvwoens\Gompie\vars::v(\Rvwoens\Gompie\cos::ifset($title,'list')) }}
@endsection

@if(array_get($layout, 'title_icon')!='none')
	<i class="icon-th"></i>
@endif
<script>
	var reloadOnWindowHeightChange=true;	// if we keep track of windowheight via Session('windowHeight');
</script>
@if ($title!='invisible')
	<!-- Content Header (Page header) -->
	<section class="content-header">

		@if (isset($data['tabs']))
			<div class="nav-tabs-custom" style="background: none;margin-bottom: -15px;border-bottom: 3px solid #d2d6de;">
				<ul class="nav nav-tabs xpull-right ui-sortable-handle">
					<li class="pull-left header">
						@include('gompie::pages.partials.list_title')
						@if (isset($subtitle) && $subtitle)
							<small><?=e($subtitle)?></h4></small>
						@endif
					</li>
					@foreach($data['tabs'] as $tab)
						<li role="presentation" class="{{$tab['active']?'active':''}}" style="margin-bottom: -6px">
							<a href="{{$tab['url']}}">{{$tab['title']}}</a>
					@endforeach
					<li class="pull-right">
						<div>
						{!!\Rvwoens\Gompie\cos::ifset($data['newhtml'],'')!!}
						</div>
					</li>
				</ul>
			</div>
		@else
			<div class="pull-right">
				{!!\Rvwoens\Gompie\cos::ifset($data['newhtml'],'')!!}
			</div>
			<h1>
				@include('gompie::pages.partials.list_title')
				@if (isset($subtitle) && $subtitle)
					<small><?=e($subtitle)?></h4></small>
				@endif

			</h1>
		@endif
	</section>

@endif

<form {{-- style="overflow:hidden;" --}} method="GET" action="" class="submitspinner">
	<div class="content container-fluid">
		<div class="box"  @if (isset($data['tabs'])) style="border-top: 0px" @endif >
			<div class="box-body">
				<!-- /.content -->
				<div class="row">
					<div class="col-md-3">
						<h3>Filters
							@if ($title=='invisible')
								<div class="pull-right">
									{!!\Rvwoens\Gompie\cos::ifset($data['newhtml'],'')!!}
								</div>
							@endif
						</h3>
						<div class="form-group">
							{!!\Rvwoens\Gompie\cos::ifset($data['inputhtml'],'')!!}
						</div>
						@if (isset($data['switchhtml']))
							@foreach($data['switchhtml'] as $switch)
								<div class="form-group">
									{!!$switch!!}
								</div>
							@endforeach
						@endif
						<input type="hidden" name="cm" value="L">
						<div class="form-group">
							<div id="outlet-filter-actions">
								<button class="btn btn-primary"
										type="submit"><i class="fa fa-search"></i> Filter</button>
								<a class="btn btn-default submitspinner"
										href="?reset=Y"><i class="fa fa-refresh"></i> Reset</a>
								@if(count($extrabuttons))
									<div style="padding-top:10px">
										@foreach($extrabuttons as $ebut)
											<a class="btn btn-default" style="width:100%" href="{{vars::v($ebut['url'])}}">
												@if (isset($ebut['icon']))
													<i class="fa fa-{{vars::v($ebut['icon'])}}"></i> &nbsp;
												@endif
												{{COS::lang($ebut['title'])}}</a>
										@endforeach
									</div>
								@endif
							</div>
						</div>
						<!-- ?=\Rvwoens\Gompie\cos::ifset($data['excelhtml'],'')? -->
					</div>
					<div class="col-md-9" >
						<div style="margin:50px"></div>
						@include('gompie::pages.partials.selectionbox')
						@if (isset($alerttitle) && $alerttitle)
							<div class="row"><div class="col-md-12"><div class="alert alert-warning"><?=$alerttitle?></div></div> </div>
						@endif
						<table class="table table-striped table-hover table-condensed"
							   {!!  (isset($horizontalscroll) && $horizontalscroll) ? ' style="display:block;overflow-x: auto;overflow-y: hidden;white-space: nowrap;" ':'' !!}
						>
							<thead><tr>
								<?php if (isset($cardurl) && $cardurl)
									echo "<th>Action</th>";
								foreach($heads as $head) {
									echo "<th>".$head."</th>";
								}
								?>
							</tr></thead>
							<tbody>
							<?php
								foreach ($rows as $drow) {
									//						if ($cnt<$offset || $cnt>=$offset+$limit) {
									//							$cnt++;
									//							continue;
									//						}
									//						$cnt++;

									echo $row->show($drow);
								}
							?>
							</tbody>
						</table>
						<nav aria-label="Page navigation">
							<ul class="pagination">
								<li class="{{$pg==0?'disabled':''}}">
									<a href="{{Cos::fullUrlWithQuery(['pg'=>$pg-1])}}" aria-label="Previous">
										<span aria-hidden="true">&laquo;</span>
									</a>
								</li>
								<?php
								for ($p=0;$p<$nrpages;$p++) {
									if ($p==0 || abs($p-$pg)<4 || $p==($nrpages-1)) { ?>
										<li class="{{$pg==$p?'active':''}}"><a href="{{Cos::fullUrlWithQuery(['pg'=>$p])}}">{{$p+1}}</a></li>
										<?php
									}
									else if ( ($p==1 && $pg>4) || ($p==($nrpages-2) && $pg<($nrpages-4)) ) { ?>
										<li class="disabled" style="border:none" ><span>...</span></li>
										<?php
									}
								}
								?>
								<li>
									<a href="{{Cos::fullUrlWithQuery(['pg'=>$pg+1])}}" aria-label="Next">
										<span aria-hidden="true">&raquo;</span>
									</a>
								</li>
								<div style="float:left;margin:6px 12px;">Total: {{$cnt}} rows</div>
							</ul>
						</nav>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
@include('gompie::pages.partials.selectionboxdialog')
<?php if ($refresh) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			setInterval(function() {
				location.reload(true);
			},<?=1000*$refresh?>);
		});
	</script>
<?php } ?>