<?php
require __DIR__.'/includes/bootstrap.php';
auth_require_login();
$pageant = pageant_get_current();
$pageTitle='Dashboard';
require __DIR__.'/includes/head.php';
?>
<div class='max-w-6xl mx-auto p-6 space-y-6'>
  <h1 class='text-2xl font-semibold'>Dashboard</h1>
  <?php if(!$pageant): ?>
    <div class='p-4 bg-yellow-100 border rounded'>No pageant selected.</div>
  <?php else: ?>
    <div class='p-4 bg-white shadow rounded'>
      <h2 class='text-lg font-medium'><?= esc($pageant['name'] ?? 'Pageant') ?> (<?= esc($pageant['year'] ?? '') ?>)</h2>
      <p class='text-sm text-gray-600 mt-1'>Code: <?= esc($pageant['code'] ?? '') ?></p>
    </div>
    <?php $rounds = pageant_list_rounds((int)$pageant['id']); ?>
    <div class='bg-white shadow rounded p-4'>
      <h3 class='font-medium mb-2'>Rounds</h3>
      <ul class='divide-y divide-gray-200'>
        <?php foreach($rounds as $r): ?>
          <li class='py-2 flex items-center justify-between'>
            <span><?= esc($r['name']) ?> (<?= esc($r['state']) ?>)</span>
            <div class='space-x-2 text-sm'>
              <?php if ($r['state']==='PENDING'): ?>
                <form method='post' action='api/api_new.php?action=open_round' class='inline'>
                  <input type='hidden' name='round_id' value='<?= (int)$r['id'] ?>'>
                  <button class='px-2 py-1 bg-blue-600 text-white rounded'>Open</button>
                </form>
              <?php elseif ($r['state']==='OPEN'): ?>
                <form method='post' action='api/api_new.php?action=close_round' class='inline'>
                  <input type='hidden' name='round_id' value='<?= (int)$r['id'] ?>'>
                  <button class='px-2 py-1 bg-red-600 text-white rounded'>Close</button>
                </form>
              <?php else: ?>
                <span class='text-gray-500'>Closed</span>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
