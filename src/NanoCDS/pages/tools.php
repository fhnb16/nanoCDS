<?php
/**
 * Nano CDS - repository statistics and the search forms.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$projects = nano_projects();
$counter  = nano_count(nano_root());
$total    = nano_size(nano_root());

$types = [
    'any' => 'ANY', 'css' => 'CSS', 'js' => 'JS', 'json' => 'JSON', 'xml' => 'XML',
    'svg' => 'SVG', 'png' => 'PNG', 'gif' => 'GIF', 'jpg' => 'JPG', 'ttf' => 'TTF',
    'zip' => 'ZIP', 'rar' => 'RAR', '7z' => '7Z', 'exe' => 'EXE', 'txt' => 'TXT',
    'html' => 'HTML',
];

nano_header('Tools');
?>
<div class="group">
<?php nano_crumb('<span class="uppertext">Tools</span>'); ?>
  <p>Projects in repository: <?= e((string) count($projects)) ?> and size is <?= e(nano_format_bytes($total)) ?>.</p>
  <p>Total Files in repository: <?= e((string) $counter['files']) ?> in <?= e((string) $counter['dirs']) ?> folders.</p>

  <p>Find latest library or framework version:</p>
  <form action="<?= e(nano_base()) ?>" method="GET" class="form-inline">
    <input type="hidden" name="page" value="latest" />
    <div class="form-group">
      <input type="text" class="form-control" name="asset" placeholder="Asset Name" required>
    </div>
    <div class="form-group">
      <select name="type" class="form-control">
<?php foreach ($types as $value => $label): ?>
        <option value="<?= e($value) ?>"><?= e($label) ?></option>
<?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <select name="size" class="form-control">
        <option selected value="0">ANY</option>
        <option value="1">MIN</option>
        <option value="2">FULL</option>
      </select>
    </div>
    <div class="form-group">
      <select name="auto" class="form-control">
        <option value="1">OPEN</option>
        <option selected value="0">VIEW</option>
        <option value="-1">LINK</option>
      </select>
    </div>
    <div class="form-group">
      <button type="submit" class="btn btn-default">Find Latest</button>
    </div>
  </form>

  <p>Search by File Name:</p>
  <form action="<?= e(nano_base()) ?>" method="GET" class="form-inline">
    <input type="hidden" name="page" value="search" />
    <div class="form-group">
      <input type="text" class="form-control" placeholder="File Name" name="query" required>
    </div>
    <div class="form-group">
      <button type="submit" class="btn btn-default">Find File</button>
    </div>
  </form>

  <p>Query the JSON API:</p>
  <form action="<?= e(nano_base()) ?>" method="GET" class="form-inline">
    <input type="hidden" name="page" value="api" />
    <div class="form-group">
      <input type="text" class="form-control" placeholder="Asset Name" name="q" required>
    </div>
    <div class="form-group">
      <select name="type" class="form-control">
<?php foreach ($types as $value => $label): ?>
        <option value="<?= e($value) ?>"><?= e($label) ?></option>
<?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <button type="submit" class="btn btn-default">Open JSON</button>
    </div>
  </form>

  <p title="Example: `b*r?p` - bootstrap, gr[ae]y - gray/grey, `[0-9]`.">You can use `*`, `?` or `[...]` in search query.</p>
</div>
<?php
nano_footer();
