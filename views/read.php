<?php defined('APPLICATION') or die;
$schema = $this->data('Schema');
$data = $this->data('Data');
$blacklist = $this->data('Blacklist', []);
$primaryKey = $this->data('PrimaryKey', 'ID');
$link = $this->data('ActionLink');
$transientKey = $this->data('TransientKey');
decho($this->Form->formData());
?>
<?php if ($this->data('Help', false)): ?>
<div class="Help Aside"><?= $this->data('Help') ?></div>
<?php endif ?>
<h1><?= $this->data('Title') ?></h1>
<?php if ($this->data('Description', false)): ?>
<div class="Info"><?= $this->data('Description') ?></div>
<?php endif ?>
<div class="P"><?= anchor(t('Add'), $link.'/add', ['class' => 'Button']) ?></div>
<table>
<thead>
    <tr>
        <?php foreach ($schema as $column): ?>
        <?php if (!in_array($column->Name, $blacklist)): ?>
        <th><?= $this->Form->labelCode($column->Name) ?></th>
        <?php endif ?>
        <?php endforeach ?>
        <td><?= t('Actions') ?></td>
    </tr>
</thead>
<tbody>
<?php foreach ($data as $row): ?>
    <tr>
        <?php foreach ($row as $field => $value): ?>
        <?php if (!in_array($field, $blacklist)): ?>
        <td><?= htmlspecialchars($value) ?></td>
        <?php endif ?>
        <?php endforeach ?>
        <td>
            <?= anchor(t('Edit'), $link.'/edit/'.$row[$primaryKey], ['class' => 'Button SmallButton Edit']) ?>
            <?= anchor(t('Delete'), $link.'/delete/'.$row[$primaryKey].'&tk='.$transientKey, ['class' => 'Button SmallButton Danger PopConfirm']) ?>
        </td>
    </tr>
<?php endforeach ?>
</tbody>

</table>
