<?php
	use Rvwoens\Former\vars,Rvwoens\Former\COS;
?>
@section('pageTitle')
	{{ vars::v(cos::ifset($title,'card')) }}
@endsection
<!-- Content Header (Page header) -->
{!!Form::open(array('url'=>URL::full(),'method'=>'POST','class'=>$formclass,'id'=>'card-form'))!!}
	<section class="content-header">
		<div class="pull-right">
			<div style="float:right">
				@if(is_array($extrabuttons) && count($extrabuttons))
					@foreach($extrabuttons as $ebut)
						{!!Form::button(COS::lang($ebut['title']),['class'=>'btn btn-default','data-url'=>vars::v($ebut['url'])])!!}
					@endforeach
				@endif
				@if($delcardurl)
						{!! Form::button('Delete',array('data-toggle'=>"modal",'data-target'=>'#modalDel','class'=>'btn btn-default')) !!}
					<div id="modalDel"  class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
									<h3 id="myModalLabel">Delete the card</h3>
								</div>
								<div class="modal-body">
									<h4>Are you sure you want to delete this card?</h4>
								</div>
								<div class="modal-footer">
									<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
								<!-- <?=$delcardurl?> -->
									<a class="btn btn-primary" href="<?=vars::v($delcardurl,false,true)?>">DELETE</a>
								</div>
							</div>
						</div>
					</div>
				@endif
				@if (!$readonly)
					{!!Form::submit("save",array('class'=>'btn btn-primary'))!!}
				@endif
			</div>
		</div>
		<h1>
			@if ( strtolower($listurl)!='hidden')
				{!!$listurl ? '<a href="'.vars::v($listurl,false,true).'" class="btn" style="display:inline"><span class="glyphicon glyphicon-backward"></span></a>':''!!}
			@endif
			<span id="idtitle"><?=\Rvwoens\Former\vars::v(\Rvwoens\Former\cos::ifset($title,'list'))?></span>
			@if (isset($subtitle) && $subtitle)
				<small><?=e($subtitle)?></small>
			@endif

		</h1>
	</section>
	<section class="content container-fluid">
		<fieldset class="box box-info">  {{-- box-solid removes top-border box-info shows blue top --}}
			<div class="box-body">

					{{-- header grid --}}
					<div class="formhead">
						@if ($msg=Session::get('msg'))
							<script>
								jQuery(document).ready(function($){
									$("#idtitle").popover({
										content: '<?=$msg?>',
										html: true
									});
									$("#idtitle").popover('show');
									setTimeout(function(){
										$("#idtitle").popover('hide');
									},1000);
								});
							</script>
						@endif

					</div>
					<fieldset>
						@if($globalerr=Session::get('globalerror'))
							<div class="alert alert-danger" role="alert">{{$globalerr}}</div>
						@endif
						{!!$rowdata!!}
						@if(!$readonly)
							<div class="control-group">
								<label class="control-label" for=""></label>
								<div class="controls">
									{!!Form::submit('Save',array('class'=>'btn btn-primary'))!!}
									<div style='float:right;font-size:10px;'>{{$id /* do not use vars::id cause subformer will overwrite*/}}</div>
								</div>
							</div>
						@endif
					</fieldset>

			</div>
		</div>
	</section>
{!!Form::close()!!}