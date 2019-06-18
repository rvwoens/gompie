<?
	use \Rvwoens\Former\Cos, \Rvwoens\Former\Vars;
	$name='';$card='';
?>
@section('pageTitle')
	{{ \Rvwoens\Former\vars::v(\Rvwoens\Former\cos::ifset($title,'list')) }}
@endsection

@if(array_get($layout, 'title_icon')!='none')
	<i class="icon-th"></i>
@endif
@if ($title!='invisible')
	<!-- Content Header (Page header) -->
	<section class="content-header">

		@if (isset($data['tabs']))
			<div class="nav-tabs-custom" style="background: none;margin-bottom: -15px;border-bottom: 3px solid #d2d6de;">
				<ul class="nav nav-tabs xpull-right ui-sortable-handle">
					<li class="pull-left header">
						@include('former::pages.partials.list_title')
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
							{!!\Rvwoens\Former\cos::ifset($data['newhtml'],'')!!}
						</div>
					</li>
				</ul>
			</div>
		@else
			<div class="pull-right">
				{!!\Rvwoens\Former\cos::ifset($data['newhtml'],'')!!}
			</div>
			<h1>
				@include('former::pages.partials.list_title')
				@if (isset($subtitle) && $subtitle)
					<small><?=e($subtitle)?></h4></small>
				@endif

			</h1>
		@endif
	</section>

@endif
<form {{--style="overflow:hidden;" --}} method="GET" action="" class="submitspinner">
	<div class="content container-fluid">
		<div class="box"  @if (isset($data['tabs'])) style="border-top: 0px" @endif >
			<div class="box-body">
				<!-- /.content -->
				<div class="row">
					<div class="col-md-3">
						<h3>Filters
							@if ($title=='invisible')
								<div class="pull-right">
									{!!\Rvwoens\Former\cos::ifset($data['newhtml'],'')!!}
								</div>
							@endif
						</h3>
						<div class="form-group">
							{!!\Rvwoens\Former\cos::ifset($data['inputhtml'],'')!!}
						</div>
						@if (isset($data['switchhtml']))
							@foreach($data['switchhtml'] as $switch)
								<div class="form-group">
								{!!$switch!!}
								</div>
							@endforeach
						@endif
						<input type="hidden" name="cm" value="M">
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
			<!-- ?=\Rvwoens\Former\cos::ifset($data['excelhtml'],'')? -->
					</div>
					<div class="col-md-9" >
						<div style="margin:55px"></div>
						@include('former::pages.partials.selectionbox')

						<div id="mappage-wrapper">
							<div id="mappage" class="map" style="height:100%"></div>
							<div id="mappage-layer" class="" style="display:none">
							</div>
							<div id="mappage-legend" style="display:none">
							</div>
						</div>
						<script type="text/javascript" src="/build/js/gmappage.js"></script>

					</div>
				</div>
			</div>
		</div>
	</div>
</form>
@include('former::pages.partials.selectionboxdialog')
<script type='text/javascript'>
		jQuery(function($) {

			//gmappage.setMarkerfields('$latfield','$lngfield');
			//gmappage.setAddress('$address');
			gmappage.init({!!$initpos['lat']!!},{!!$initpos['lng']!!},{!!$initpos['zoom']!!},'{!! $mapAjaxUrl !!}'); //$lat,$lng,$initzoom,callbackurl);

			//gmap.searchbar(jQuery,$lat,$lng);
			//gmap.addRadiusCircle('$radiusfield');

			// make sure map is full window height
			$('#mappage-wrapper').height(function(index, height) {
				return (window.innerHeight - $("#mappage-wrapper").offset().top-20)-4;
			});
			$( window ).resize(function() {
				$('#mappage-wrapper').height(function(index, height) {
					return (window.innerHeight - $("#mappage-wrapper").offset().top-20)-4;
				});
			});
		});
</script>
