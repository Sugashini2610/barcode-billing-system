<?php
// alerts partial — shows session flash messages
$successMsg = Session::flash('success');
$errorMsg = Session::flash('error');
$warnMsg = Session::flash('warning');
?>
<?php if ($successMsg): ?>
    <div class="alert alert-success" style="margin:12px 24px 0" id="flashAlert">
        <i class="bi bi-check-circle-fill"></i>
        <?php echo htmlspecialchars($successMsg); ?>
        <button class="alert-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="alert alert-error" style="margin:12px 24px 0" id="flashAlert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?php echo htmlspecialchars($errorMsg); ?>
        <button class="alert-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
<?php endif; ?>

<?php if ($warnMsg): ?>
    <div class="alert alert-warning" style="margin:12px 24px 0" id="flashAlert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?php echo htmlspecialchars($warnMsg); ?>
        <button class="alert-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
<?php endif; ?>