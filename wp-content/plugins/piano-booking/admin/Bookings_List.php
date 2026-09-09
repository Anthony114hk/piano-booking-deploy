<?php
namespace PianoBooking\Admin;

use PianoBooking\CPT;
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class Bookings_List extends \WP_List_Table
{
    public static function register(): void {}

    public function __construct()
    {
        parent::__construct(['singular' => 'booking', 'plural' => 'bookings', 'ajax' => false]);
    }

    public static function render(): void
    {
        if (($_GET['action'] ?? '') === 'view' && !empty($_GET['id'])) {
            Booking_Detail::render();
            return;
        }
        $list = new self();
        $list->prepare_items();
        echo '<div class="wrap"><h1>' . esc_html__('Bookings', 'piano-booking') . '</h1>';
        echo '<form method="get"><input type="hidden" name="page" value="pb-bookings">';
        $list->display();
        echo '</form></div>';
    }

    public static function render_purchases(): void
    {
        $list = new self();
        $list->set_purchases_mode();
        $list->prepare_items();
        echo '<div class="wrap"><h1>' . esc_html__('Package Purchases', 'piano-booking') . '</h1>';
        echo '<form method="get"><input type="hidden" name="page" value="pb-package-purchases">';
        $list->display();
        echo '</form></div>';
    }

    private bool $purchasesMode = false;
    public function set_purchases_mode(): void { $this->purchasesMode = true; }

    public function get_columns(): array
    {
        if ($this->purchasesMode) {
            return [
                'ref'       => __('Ref', 'piano-booking'),
                'user'      => __('Customer', 'piano-booking'),
                'pkg'       => __('Package', 'piano-booking'),
                'remaining' => __('Remaining hrs', 'piano-booking'),
                'amount'    => __('Amount', 'piano-booking'),
                'status'    => __('Status', 'piano-booking'),
                'expires'   => __('Expires', 'piano-booking'),
                'actions'   => __('Actions', 'piano-booking'),
            ];
        }
        return [
            'ref'     => __('Ref', 'piano-booking'),
            'when'    => __('Date / Time', 'piano-booking'),
            'room'    => __('Room', 'piano-booking'),
            'user'    => __('Customer', 'piano-booking'),
            'paid'    => __('Paid via', 'piano-booking'),
            'status'  => __('Status', 'piano-booking'),
            'amount'  => __('Amount', 'piano-booking'),
            'actions' => __('Actions', 'piano-booking'),
        ];
    }

    /**
     * WP 7.x get_column_info() uses get_column_headers() which applies the
     * `manage_{screen->id}_columns` filter and returns [] if unhooked.
     * Our `get_columns()` override is therefore ignored — table renders empty.
     * Override here so the WP_List_Table instance falls back to `get_columns()`
     * when the filter returns nothing.
     */
    public function get_column_info(): array
    {
        $info = parent::get_column_info();
        if (empty($info[0])) {
            $columns    = $this->get_columns();
            $primary    = $this->get_default_primary_column_name();
            $info       = [$columns, [], [], $primary];
            $this->_column_headers = $info;
        }
        return $info;
    }

    public function prepare_items(): void
    {
        $postType = $this->purchasesMode ? CPT::PACKAGE_PURCHASE : CPT::BOOKING;
        $per_page = 20;
        $paged = $this->get_pagenum();
        $statusFilter = $_GET['post_status'] ?? '';
        $statuses = $statusFilter ? [$statusFilter] : ($this->purchasesMode
            ? ['pb_pending_payment', 'pb_active', 'pb_expired']
            : ['pb_pending', 'pb_paid', 'pb_completed', 'pb_cancelled']);

        $args = [
            'post_type'      => $postType,
            'post_status'    => $statuses,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        // Date filter (pb_date=YYYY-MM-DD) — used by dashboard calendar click-through
        $dateFilter = sanitize_text_field($_GET['pb_date'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
            $args['meta_query'] = [[
                'key'     => 'start_at',
                'value'   => [$dateFilter . ' 00:00:00', $dateFilter . ' 23:59:59'],
                'compare' => 'BETWEEN',
            ]];
        }
        $q = new \WP_Query($args);
        $this->items = $q->posts;
        $this->set_pagination_args(['total_items' => $q->found_posts, 'per_page' => $per_page]);
    }

    public function column_default($item, $column_name): string
    {
        return esc_html((string) get_post_meta($item->ID, $column_name, true));
    }

    public function column_ref($item): string
    {
        $url = admin_url('admin.php?page=pb-bookings&action=view&id=' . $item->ID);
        return '<a href="' . esc_url($url) . '"><code>' . esc_html($item->post_title) . '</code></a>';
    }

    public function column_when($item): string { return esc_html((string) get_post_meta($item->ID, 'start_at', true)); }
    public function column_room($item): string
    {
        $roomId = (int) get_post_meta($item->ID, 'room_id', true);
        return $roomId ? esc_html(get_the_title($roomId)) : '—';
    }
    public function column_user($item): string
    {
        $u = get_userdata($item->post_author);
        return $u ? esc_html($u->display_name) : '—';
    }
    public function column_paid($item): string { return esc_html(ucfirst((string) get_post_meta($item->ID, 'paid_via', true))); }
    public function column_status($item): string
    {
        $status = get_post_status_object($item->post_status);
        return $status ? '<span class="pb-status pb-status-' . esc_attr($item->post_status) . '">' . esc_html($status->label) . '</span>' : esc_html($item->post_status);
    }
    public function column_amount($item): string
    {
        $hkd = get_post_meta($item->ID, 'amount_hkd', true);
        $credits = get_post_meta($item->ID, 'amount_credits', true);
        if ($credits) return esc_html($credits . ' credits');
        return 'HK$ ' . esc_html($hkd ?: '0.00');
    }
    public function column_pkg($item): string
    {
        $pkgId = (int) get_post_meta($item->ID, 'package_id', true);
        return $pkgId ? esc_html(get_the_title($pkgId)) : '—';
    }
    public function column_remaining($item): string { return esc_html((string) get_post_meta($item->ID, 'hours_remaining', true)); }
    public function column_expires($item): string { return esc_html((string) get_post_meta($item->ID, 'expires_at', true)); }

    public function column_actions($item): string
    {
        if ($this->purchasesMode) {
            if ($item->post_status === 'pb_pending_payment') {
                return '<button type="button" class="button pb-admin-activate" data-id="' . esc_attr($item->ID) . '">' . esc_html__('Activate', 'piano-booking') . '</button>';
            }
            return '—';
        }
        if ($item->post_status === 'pb_pending') {
            // type="button" so clicking doesn't submit the surrounding GET form (which would
            // refresh the page before our AJAX handler can fire).
            return '<button type="button" class="button button-primary pb-admin-verify" data-id="' . esc_attr($item->ID) . '">' . esc_html__('Verify', 'piano-booking') . '</button> '
                 . '<button type="button" class="button pb-admin-reject" data-id="' . esc_attr($item->ID) . '">' . esc_html__('Reject', 'piano-booking') . '</button>';
        }
        if ($item->post_status === 'pb_paid') {
            return '<button type="button" class="button pb-admin-cancel" data-id="' . esc_attr($item->ID) . '">' . esc_html__('Cancel', 'piano-booking') . '</button> '
                 . '<a href="' . esc_url(admin_url('admin.php?page=pb-bookings&action=view&id=' . $item->ID)) . '" class="button">' . esc_html__('View', 'piano-booking') . '</a>';
        }
        $view = admin_url('admin.php?page=pb-bookings&action=view&id=' . $item->ID);
        return '<a href="' . esc_url($view) . '" class="button">' . esc_html__('View', 'piano-booking') . '</a>';
    }
}