<?php
if (!defined('ABSPATH')) exit;

// If already logged in, skip ahead to confirm
if (is_user_logged_in()) {
    $back = add_query_arg([
        'pb_step'        => 'confirm',
        'pb_room'        => (int) ($_GET['pb_room'] ?? 0),
        'pb_date'        => sanitize_text_field($_GET['pb_date'] ?? ''),
        'pb_start_time'  => sanitize_text_field($_GET['pb_start_time'] ?? ''),
        'pb_end_time'    => sanitize_text_field($_GET['pb_end_time'] ?? ''),
    ], home_url('/booking'));
    echo '<script>window.location.replace(' . wp_json_encode($back) . ');</script>';
    return;
}

// Preserve booking context in hidden fields so we can come back to step-confirm after register
$ctx = [
    'pb_room'       => (int) ($_GET['pb_room'] ?? 0),
    'pb_date'       => sanitize_text_field($_GET['pb_date'] ?? ''),
    'pb_start_time' => sanitize_text_field($_GET['pb_start_time'] ?? ''),
    'pb_end_time'   => sanitize_text_field($_GET['pb_end_time'] ?? ''),
];
?>
<div class="pb-booking-step pb-register-step">
  <h2><?= esc_html__('Create an account to continue', 'piano-booking') ?></h2>
  <p class="pb-register-sub"><?= esc_html__('Pick a username and password — you\'ll be logged in straight away.', 'piano-booking') ?></p>

  <div id="pb-register-msg" class="pb-form-error" hidden></div>

  <form id="pb-register-form" class="pb-booking-form" autocomplete="on">
    <?php foreach ($ctx as $k => $v): ?>
      <input type="hidden" name="<?= esc_attr($k) ?>" value="<?= esc_attr((string) $v) ?>">
    <?php endforeach; ?>

    <label><?= esc_html__('Username', 'piano-booking') ?>
      <input type="text" name="pb_username" required minlength="3" maxlength="60"
             pattern="[A-Za-z0-9_.\-]+" autocomplete="username"
             placeholder="<?= esc_attr__('e.g. anthony114', 'piano-booking') ?>">
    </label>
    <small class="pb-hint"><?= esc_html__('Letters, numbers, underscore, dot, or dash.', 'piano-booking') ?></small>

    <label><?= esc_html__('Email', 'piano-booking') ?>
      <input type="email" name="pb_email" required autocomplete="email"
             placeholder="<?= esc_attr__('you@example.com', 'piano-booking') ?>">
    </label>

    <label><?= esc_html__('Password', 'piano-booking') ?>
      <input type="password" name="pb_password" required minlength="8" autocomplete="new-password"
             placeholder="<?= esc_attr__('At least 8 characters', 'piano-booking') ?>">
    </label>

    <button type="submit"><?= esc_html__('Create account & continue', 'piano-booking') ?></button>
  </form>

  <p class="pb-register-foot">
    <?= esc_html__('Already have an account?', 'piano-booking') ?>
    <a href="<?= esc_url(wp_login_url(add_query_arg($ctx + ['pb_step' => 'confirm'], home_url('/booking')))) ?>">
      <?= esc_html__('Login instead', 'piano-booking') ?>
    </a>
  </p>
</div>

<script>
(function () {
  var form    = document.getElementById('pb-register-form');
  var msgBox  = document.getElementById('pb-register-msg');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    msgBox.hidden = true;

    var fd = new FormData(form);
    fd.append('action', 'pb_register_and_login');
    fd.append('nonce', PB_AJAX.nonce);

    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '<?= esc_js(__('Creating account…', 'piano-booking')) ?>';

    fetch(PB_AJAX.url, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (r) {
        if (r && r.success) {
          window.location.href = r.data.redirect;
          return;
        }
        msgBox.hidden = false;
        msgBox.textContent = (r && r.data) ? r.data : 'Registration failed';
        btn.disabled = false;
        btn.textContent = '<?= esc_js(__('Create account & continue', 'piano-booking')) ?>';
      })
      .catch(function () {
        msgBox.hidden = false;
        msgBox.textContent = 'Network error';
        btn.disabled = false;
        btn.textContent = '<?= esc_js(__('Create account & continue', 'piano-booking')) ?>';
      });
  });
})();
</script>