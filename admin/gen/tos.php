<?php

if (!permissions::has("tos")) {
    die(lang('no_perm'));
}

if (isset($_POST['tos_submit']) && $_POST['tos_text'] != '') {
    if (!csrf_check()) {
        return util::error("Invalid CSRF token!");
    }

    page::edit('tos', $_POST['tos_text']);

    $date = new DateTime();
    $date = $date->format('Y-m-d H:i:s');

    setSetting($date, 'tos_lastedited', 'value3');
    $message->add('success', 'Successfully updated Terms of Service!');
}

?>

<h2>Terms of Service</h2>
<span id="message-location">
	<?php $message->Display(); ?>
</span>
Modify the information displayed in the Terms of Service<br><br>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
    <textarea id="tos" name="tos_text" class="tinymce"><?php echo page::get('tos', true); ?></textarea><br>
    <input type="hidden" name="tos_submit" value="true">
    <input type="submit" name="tos_submit" value="<?= lang('submit'); ?>" class="btn btn-success">
</form>