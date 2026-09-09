<?php
if (!defined('ABSPATH')) exit;
$id = (int) ($_GET['id'] ?? 0);
$ref = get_the_title($id);
$amount = get_post_meta($id, 'amount_hkd', true);
?>
<h2><?= esc_html__('Package purchase pending payment', 'piano-booking') ?></h2>
<p>Ref: <code><?= esc_html($ref) ?></code> · Amount: HK$ <?= esc_html($amount) ?></p>
<p>Pay via PayMe/FPS/WeChat with reference <code><?= esc_html($ref) ?></code>.</p>