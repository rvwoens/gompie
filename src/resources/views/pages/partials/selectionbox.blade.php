{{-- connected to selectbox field --}}
<div id="selectbox-bar2" style="height:45px;position:absolute;top:5px;left:14px;z-index:1000;xbackground-color:yellow;display:none;">
	<a class="btn btn-lg btn-circle btn-warning" id="selectbox-addall" data-toggle="tooltip" data-placement="top" title="add&nbsp;all&nbsp;rows" style="user-select:none;">
		<!-- span class='glyphicon glyphicon-plus-sign'></span -->
		<!-- fontawesome https://fontawesome.com/v4.7.0/icons/ -->
		<i class="fa fa-plus-square"></i>
	</a>
</div>
<div id="selectbox-bar" style="height:45px;position:absolute;top:5px;left:48px;z-index:1000;xbackground-color:yellow;{{$data['selectedBadgeCnt']==0?'display:none':''}}">
	<button id="selectbox-bar-button" class="btn btn-lg btn-circle btn-warning" >
		<i class="fa fa-shopping-basket"></i>
	</button>
	<span id="selectbox-bar-badge" class="badge badge-info" style="{{$data['selectedBadgeCnt']==0?'display:none':''}};position:relative;top:-11px;left:-15px;">{{$data['selectedBadgeCnt']}}</span>

	<span id="selectbox-subbar" style="display:none">
		<button id="selectbox-deleteselection" class="btn btn-lg btn-circle btn-danger" data-toggle="tooltip" data-placement="top" title="remove&nbsp;selection">
			<i class="fa fa-minus-square"></i>
		</button>
		<!-- button id="selectbox-saveselection" class="btn btn-lg btn-circle btn-success" data-toggle="tooltip" data-placement="top" title="save">
			<i class="fa fa-floppy-o"></i>
		</button -->

		@foreach ($data['selectedExtraButtons'] as $nr=>$extraButton)
			@if (isset($extraButton['icon']))
				{{-- note after ajax update the extrabutton['url']needs to be updated So use a separate url that links through --}}
				{{-- xhref=" \Rvwoens\former\vars::v($extraButton['url']) --}}
				<a id="selectbox-extrabutton-{{$nr}}" href="{!!$myAjaxUrl!!}&ajax=goiconselection&extrabutton={{$nr}}"
				   data-toggle="tooltip" data-placement="top" title="{{$extraButton['title']}}"
				   class="btn btn-lg btn-circle btn-info"><i class="fa {{$extraButton['icon']}}"></i></a>
			@else
				<a href="{{\Rvwoens\Gompie\vars::v($extraButton['url'])}}" class="btn btn-lg btn-circle btn-info">{{$extraButton['title']}}</a>
			@endif
		@endforeach

	</span>
</div>

{{-- dialog put in a separate blade: you can't nest forms --}}
<script type="text/javascript" src="/build/js/selections.js"></script>
<script>
	selections.callbackUrl='{!!$myAjaxUrl!!}';
</script>

