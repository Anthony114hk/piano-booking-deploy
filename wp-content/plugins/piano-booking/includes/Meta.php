<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Meta
{
    public static function register_all(): void
    {
        self::register(CPT::LOCATION, [
            'address'        => ['type' => 'string'],
            'phone'          => ['type' => 'string'],
            'opening_hours'  => ['type' => 'string'], // JSON
            'map_embed_url'  => ['type' => 'string'],
        ]);

        self::register(CPT::ROOM, [
            'location_id'  => ['type' => 'integer'],
            'capacity'     => ['type' => 'integer'],
            'equipment'    => ['type' => 'string'], // JSON array
            'hourly_rate'  => ['type' => 'string'], // decimal as string
            'is_active'    => ['type' => 'boolean'],
        ]);

        self::register(CPT::PACKAGE, [
            'hours_included' => ['type' => 'string'], // decimal
            'price_hkd'      => ['type' => 'string'],
            'validity_days'  => ['type' => 'integer'],
            'is_active'      => ['type' => 'boolean'],
        ]);

        self::register(CPT::BOOKING, [
            'room_id'              => ['type' => 'integer'],
            'location_id'          => ['type' => 'integer'],
            'start_at'             => ['type' => 'string'], // ISO datetime UTC
            'end_at'               => ['type' => 'string'],
            'paid_via'             => ['type' => 'string'], // qr|package|cash
            'package_purchase_id'  => ['type' => 'integer'],
            'amount_hkd'           => ['type' => 'string'],
            'amount_credits'       => ['type' => 'string'],
            'customer_name'        => ['type' => 'string'],
            'customer_phone'       => ['type' => 'string'],
            'customer_email'       => ['type' => 'string'],
            'payment_reference'    => ['type' => 'string'],
            'payment_proof_url'    => ['type' => 'string'],
            'staff_verified_by'    => ['type' => 'integer'],
            'verified_at'          => ['type' => 'string'],
            'cancellation_reason'  => ['type' => 'string'],
            'email_bounced_at'     => ['type' => 'string'],
        ]);

        self::register(CPT::PACKAGE_PURCHASE, [
            'package_id'          => ['type' => 'integer'],
            'hours_included'      => ['type' => 'string'],
            'hours_remaining'     => ['type' => 'string'],
            'amount_hkd'          => ['type' => 'string'],
            'payment_reference'   => ['type' => 'string'],
            'payment_proof_url'   => ['type' => 'string'],
            'activated_at'        => ['type' => 'string'],
            'expires_at'          => ['type' => 'string'],
            'staff_verified_by'   => ['type' => 'integer'],
        ]);
    }

    private static function register(string $cpt, array $fields): void
    {
        foreach ($fields as $key => $args) {
            register_post_meta($cpt, $key, [
                'type'         => $args['type'],
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => fn() => current_user_can('edit_posts'),
                'sanitize_callback' => match ($args['type']) {
                    'integer' => 'absint',
                    'boolean' => fn($v) => (bool) $v,
                    default   => 'sanitize_text_field',
                },
            ]);
        }
    }
}