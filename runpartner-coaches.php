<?php
/**
 * RunPartner Coaches
 *
 * @package RunPartner
 * @pluginURI https://runpartner.com
 * @Description Coaches CPT for historical long-distance running coaches.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/runpartner-coaches/includes/class-coaches-cpt.php';

// Boot the plugin
add_action('plugins_loaded', function () {
    new RunPartner_Coaches_CPT();
});
