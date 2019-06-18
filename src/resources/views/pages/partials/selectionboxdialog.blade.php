{{-- you cant nest forms, so separate blade--}}
 <dialog id="selectbox-savedialog" class="mdl-dialog" style="width:fit-content">
    <h5 class="mdl-dialog__title">Save selection</h5>
	<form id="selectbox-saveddialog-form" action="/" method="GET">
		<div class="mdl-dialog__content">
				<div class="mdl-textfield mdl-js-textfield">
					<input class="mdl-textfield__input" type="text" id="selectbox-saveselection-name" required="">
					<label class="mdl-textfield__label" for="selectbox-saveselection-name">Give your selection a name</label>
				</div>
		</div>
		<div class="mdl-dialog__actions">
			<button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent confirm">Save</button>
			<button type="button" class="mdl-button mdl-js-button close" >Cancel</button>
		</div>
	</form>
</dialog>
