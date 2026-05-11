<?php
/**
 * RunPartner Athletes
 *
 * @package RunPartner
 * @pluginURI https://runpartner.com
 * @Description Athletes CPT with Discipline taxonomy for the RunPartner running community.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/runpartner-athletes/includes/class-athletes-cpt.php';

// Boot the plugin
add_action('plugins_loaded', function () {
    new RunPartner_Athletes_CPT();
});
