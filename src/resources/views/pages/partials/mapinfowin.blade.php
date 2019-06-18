<div id="mappage-infowin-info">
	<table class="table table-condensed">

		@foreach($inps as $fnr=>$inp)
			@if ($fnr==0)
				<thead><tr>
					<th colspan="2" style="position:relative">
						<div style="position:absolute;left:-9px;top:-14px;">
							<button class="btn xbtn-sm btn-circle btn-warning" id="selectbox-add1" data-sid="{{$id}}" style="left:-6px;">
								<i class="fa fa-plus-square"></i>
							</button>
						</div>
						@if (isset($rowurl) && $rowurl)
							<a class="btn xbtn-sm xbtn-primary" href="{!!\Rvwoens\Gompie\vars::v($rowurl,false,true)!!}">{{$inp}}</a>
						@else
							{{$inp}}
						@endif
					</th>
				</tr></thead>
			@else
				<tr>
					<td style=""><small><strong>{{\Rvwoens\Gompie\Cos::lang($fields[$fnr]->title())}}</strong></small></td>
					<td style="">{!!$inp!!}</td>
				</tr>
			@endif
		@endforeach
	</table>
</div>
