<?php defined('APPLICATION') or die; ?>
<?php if ($this->data('Help', false)): ?>
<div class="Help Aside"><?= $this->data('Help') ?></div>
<?php endif ?>
<h1><?= $this->data('Title') ?></h1>
<?php if ($this->data('Description', false)): ?>
<div class="Info"><?= $this->data('Description') ?></div>
<?php endif ?>
<div class="FormWrapper">
<?= $this->Form->open(), $this->Form->errors() ?>
<?= $this->Form->simple($this->data('FormSchema')) ?>
<?= $this->Form->close('Save') ?>
</div>
