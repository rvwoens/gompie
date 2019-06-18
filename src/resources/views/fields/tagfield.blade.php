{{-- for each tag, render a form-group --}}
<div class='form-group'>
	<label class='col-md-2 control-label'>
		{{array_get($def, 'title', $name)}}
	</label>
	<div class="col-md-8">
		@foreach($tags as $index => $tag)
		<div class="row">
			<div class="col-md-10">{{$tag}}<input type="hidden" name="{{$formfieldname}}_tag_{{$index}}" value="{{$tag}}" /></div>
			<div class="col-md-2"><a><i style='font-size:24px;' class='icon-trash'></i></a></div>
		</div>
		@endforeach
	</div>
</div>
{{-- add controls to add new tags at the bottom --}}
<div class='form-group'>
	<div class='col-md-push-2 col-md-10'>
		<div class='row'>
                <span class='smallbutton'>
                    <span class='glyphicon glyphicon-plus-sign'></span>
                </span>
		</div>
	</div>
</div>