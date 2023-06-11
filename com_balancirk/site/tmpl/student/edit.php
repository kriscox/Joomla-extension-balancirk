<div>
	<div class="row">
		<div class="col-md-6">
			<?= $this->form->renderField('firstname'); ?>
		</div>
		<div class="col-md-6">
			<?= $this->form->renderField('name'); ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= $this->form->renderField('street'); ?>
		</div>
		<div class="col-md-3">
			<?= $this->form->renderField('number'); ?>
		</div>
		<div class="col-md-3">
			<?= $this->form->renderField('bus'); ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= $this->form->renderField('postcode'); ?>
		</div>
		<div class="col-md-6">
			<?= $this->form->renderField('city'); ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= $this->form->renderField('email'); ?>
		</div>
		<div class="col-md-6">
			<?= $this->form->renderField('phone'); ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= $this->form->renderField('birthdate'); ?>
		</div>
		<div class="col-md-6">
			<?= $this->form->renderField('uitpas'); ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<?= $this->form->renderField('allow_photo'); ?>
		</div>
	</div>
</div>
<input type="hidden" name="jform[id]" id="jform_id" value="<?= $this->item->id ?>">
<input type="hidden" name="task" value="">
<?= HTMLHelper::_('form.token'); ?>
<div class="row title-alias form-vertical mb-3">
	<div class="col-12 col-md-6">
		<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('student.save')">
			<span class="icon-save"> <?= Text::_('JSAVE') ?> </span>
		</button>
	</div>
	<div class="col-12 col-md-6">
		<button type="button" class="balancirk_button" onclick="Joomla.submitbutton('student.cancel')">
			<span class="icon-cancel"> <?= Text::_('JCANCEL') ?></span>
		</button>
	</div>
</div>
</form>