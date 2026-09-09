<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Payment
{
    // Configurable handles (would come from settings in v2; constants for v1).
    public const PAYME_HANDLE = 'pianohk';
    public const FPS_ID       = '1234567';
    public const WECHAT_ID    = 'pianohk';

    /**
     * Attach payment proof (file attachment + reference string) to a booking.
     */
    public static function attach_proof(int $bookingId, int $attachmentId, string $reference = ''): bool
    {
        if (get_post_type($bookingId) !== CPT::BOOKING) return false;
        if ($attachmentId > 0) {
            update_post_meta($bookingId, 'payment_proof_url', wp_get_attachment_url($attachmentId));
        }
        if ($reference !== '') {
            update_post_meta($bookingId, 'payment_reference', sanitize_text_field($reference));
        }
        return true;
    }

    /**
     * Get payment instructions shown to customer after booking.
     */
    public static function instructions(int $bookingId): array
    {
        return [
            'ref'        => get_post_field('post_title', $bookingId),
            'amount'     => get_post_meta($bookingId, 'amount_hkd', true),
            'payme_url'  => self::payme_url($bookingId),
            'fps_id'     => self::FPS_ID,
            'wechat_id'  => self::WECHAT_ID,
            'upload_url' => self::proof_upload_url($bookingId),
        ];
    }

    /**
     * Generate PayMe deeplink with amount + message (booking ref).
     */
    public static function payme_url(int $bookingId): string
    {
        $ref    = get_post_field('post_title', $bookingId);
        $amount = get_post_meta($bookingId, 'amount_hkd', true);
        return sprintf(
            'https://payme.com.hs/%s?amount=%s&message=%s',
            rawurlencode(self::PAYME_HANDLE),
            rawurlencode($amount),
            rawurlencode($ref)
        );
    }

    /**
     * URL where customer can upload their payment proof screenshot.
     */
    public static function proof_upload_url(int $bookingId): string
    {
        return add_query_arg(
            ['booking_id' => $bookingId],
            home_url('/upload-proof')
        );
    }
}