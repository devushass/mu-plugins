<?php
/**
 * News Categories Seeder
 *
 * Seeds the built-in category taxonomy with a two-level hierarchy:
 *   Road running → India, World, Gears
 *   Trail running → India, World, Gears
 *
 * @package RunPartner
 */

if (!defined('ABSPATH')) {
    exit;
}

final class RunPartner_News_Categories {
    private const TAXONOMY = 'category';

    private const CATEGORIES = [
        'road-running' => [
            'name'     => 'Road running',
            'children' => [
                'road-india'  => 'India',
                'road-world'  => 'World',
                'road-gears'  => 'Gears',
            ],
        ],
        'trail-running' => [
            'name'     => 'Trail running',
            'children' => [
                'trail-india'  => 'India',
                'trail-world'  => 'World',
                'trail-gears'  => 'Gears',
            ],
        ],
    ];

    public function __construct() {
        add_action('init', [$this, 'seed_categories']);
    }

    public function seed_categories(): void {
        foreach (self::CATEGORIES as $parent_slug => $category) {
            $existing_parent = get_term_by('slug', $parent_slug, self::TAXONOMY);

            if ($existing_parent) {
                $parent_id = (int) $existing_parent->term_id;
            } else {
                $result = wp_insert_term($category['name'], self::TAXONOMY, ['slug' => $parent_slug]);

                if (is_wp_error($result)) {
                    continue;
                }

                $parent_id = (int) $result['term_id'];
            }

            foreach ($category['children'] as $child_slug => $child_name) {
                $existing_child = get_term_by('slug', $child_slug, self::TAXONOMY);

                if ($existing_child) {
                    continue;
                }

                wp_insert_term($child_name, self::TAXONOMY, [
                    'slug'   => $child_slug,
                    'parent' => $parent_id,
                ]);
            }
        }
    }
}
