<?php
/**
 * Plugin Name: EmpowerED Individual Subscription Waitlist
 * Description: Adds the approved homepage waitlist section after Key Features and submits to the EmpowerED Laravel backend.
 * Version: 1.0.1
 * Author: EmpowerED
 */

defined('ABSPATH') || exit;

function empowered_individual_waitlist_enabled() {
    return (bool) get_option('empowered_waitlist_enabled', false);
}

add_action('wp_enqueue_scripts', function () {
    if (!is_front_page() || !empowered_individual_waitlist_enabled()) {
        return;
    }
    $base = plugin_dir_url(__FILE__);
    wp_enqueue_style('empowered-individual-waitlist', $base . 'assets/waitlist.css', array(), '1.0.1');
    wp_enqueue_script('empowered-individual-waitlist', $base . 'assets/waitlist.js', array(), '1.0.1', true);
});

add_action('elementor/frontend/after_render', function ($element) {
    static $rendered = false;
    if ($rendered || !is_front_page() || !empowered_individual_waitlist_enabled() || $element->get_id() !== 'cb707a0') {
        return;
    }
    $rendered = true;
    $waitlist_registration_open = (bool) get_option('empowered_waitlist_registration_open', false);
    include __DIR__ . '/section.php';
});

add_action('admin_menu', function () {
    add_options_page('Individual Subscription Waitlist', 'Subscription Waitlist', 'manage_options', 'empowered-waitlist', function () {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap"><h1>Individual Subscription Waitlist</h1>';
        echo '<p>The homepage section can be shown before the backend deployment. Keep registrations closed until the Laravel migration and endpoint have been deployed and tested.</p>';
        echo '<form action="options.php" method="post">';
        settings_fields('empowered_waitlist');
        echo '<p><label><input type="checkbox" name="empowered_waitlist_enabled" value="1" ' . checked(empowered_individual_waitlist_enabled(), true, false) . '> Show the homepage waitlist section after Key Features</label></p>';
        echo '<p><label><input type="checkbox" name="empowered_waitlist_registration_open" value="1" ' . checked((bool) get_option('empowered_waitlist_registration_open', false), true, false) . '> Accept registrations (enable only after backend deployment and verification)</label></p>';
        echo '<p>Backend: <code>https://admin.empoweredhealth.asia/api/individual-subscription-waitlist</code></p>';
        echo '<p>No confirmation emails or launch notifications are sent. View registrations in the Laravel admin panel.</p>';
        submit_button();
        echo '</form></div>';
    });
});

add_action('admin_init', function () {
    register_setting('empowered_waitlist', 'empowered_waitlist_enabled', array(
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ));
    register_setting('empowered_waitlist', 'empowered_waitlist_registration_open', array(
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ));
});
