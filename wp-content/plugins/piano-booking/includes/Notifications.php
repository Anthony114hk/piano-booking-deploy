<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Notifications
{
    public const EVENTS = [
        'booking_pending',
        'booking_confirmed',
        'booking_reminder',
        'booking_rejected',
        'booking_auto_cancelled',
        'package_pending',
        'package_active',
        'package_expiring_soon',
        'package_expired',
    ];

    public static function dispatch(string $event, array $context): bool
    {
        if (!in_array($event, self::EVENTS, true)) return false;
        $bookingOrPurchaseId = $context['booking_id'] ?? $context['purchase_id'] ?? 0;
        $to = $context['to'] ?? '';
        if (!$to) return false;

        $template = PB_PLUGIN_DIR . "templates/emails/{$event}.html";
        if (!file_exists($template)) return false;
        $body = file_get_contents($template);
        $body = self::render($body, $context);

        $subjects = [
            'booking_pending'        => '您的預約已收到 — {{ref}}',
            'booking_confirmed'      => '預約確認 — {{ref}}',
            'booking_reminder'       => '預約提醒 — 明天 {{start}}',
            'booking_rejected'       => '預約付款未通過 — {{ref}}',
            'booking_auto_cancelled' => '預約已自動取消 — {{ref}}',
            'package_pending'        => '套票申請已收到 — {{ref}}',
            'package_active'         => '套票已啟用 — {{ref}}',
            'package_expiring_soon'  => '套票即將到期 — 剩餘 {{remaining}} 小時',
            'package_expired'        => '套票已到期',
        ];
        $subject = self::render($subjects[$event] ?? $event, $context);

        $sent = wp_mail($to, $subject, wp_strip_all_tags($body), ['Content-Type: text/html; charset=UTF-8']);
        if (!$sent && $bookingOrPurchaseId) {
            update_post_meta($bookingOrPurchaseId, 'email_bounced_at', current_time('mysql', true));
            do_action('pb_email_failure', $event, $to, $context);
        }
        return $sent;
    }

    private static function render(string $tpl, array $ctx): string
    {
        foreach ($ctx as $k => $v) {
            $tpl = str_replace('{{' . $k . '}}', (string) $v, $tpl);
        }
        return $tpl;
    }
}