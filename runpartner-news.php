<?php
/**
 * RunPartner News
 *
 * @package RunPartner
 * @pluginURI https://runpartner.com
 * @Description Seeds news categories (Road running / Trail running with sub-regions) for the RunPartner running community.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/runpartner-news/includes/class-news-categories.php';

// Boot the plugin
add_action('plugins_loaded', function () {
    new RunPartner_News_Categories();
});
