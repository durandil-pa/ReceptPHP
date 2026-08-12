<?php if($flash = Flash::get()): ?>
<div class="flash <?= htmlspecialchars($flash['type']) ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>
