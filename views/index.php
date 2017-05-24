<?php defined('APPLICATION') or die;
$schema = $this->data('Schema');
$data = $this->data('Data');
$blacklist = $this->data('Blacklist', []);
$primaryKey = $this->data('PrimaryKey', 'ID');
$link = $this->data('IndexLink');
$transientKey = $this->data('TransientKey');
?>
<h1><?= $this->data('Title') ?></h1>
<?php if ($this->data('Description', false)): ?>
<div class="Info"><?= $this->data('Description') ?></div>
<?php endif ?>
<div class="Info"><?= anchor(t('Add Item'), $link.'/add', ['class' => 'Button Primary']) ?></div>
<?php if (count($data) == 0): ?>
<div class="Info"><?= t(sprintf('The table "%s" is empty.', $this->data('TableName'))) ?></div>
<?php else: ?>
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
<?php endif ?>