<?php
/**
 * RunPartner Events
 *
 * @package RunPartner
 * @pluginURI https://runpartner.com
 * @Description Events CPT with Event Type taxonomy for the RunPartner running community.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/runpartner-events/includes/class-events-cpt.php';

// Boot the plugin
add_action('plugins_loaded', function () {
    new RunPartner_Events_CPT();
});
