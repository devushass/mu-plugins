<?php
/**
 * Events CPT and Event Type Taxonomy
 *
 * @package RunPartner
 */

if (!defined('ABSPATH')) {
    exit;
}

final class RunPartner_Events_CPT {
    private const POST_TYPE = 'events';
    private const TAXONOMY  = 'event_type';
    private const EVENT_REGION_TAXONOMY = 'event_region';

    private const META_FIELDS = [
        'subtitle'           => '_rp_event_subtitle',
        'location'           => '_rp_event_location',
        'country'            => '_rp_event_country',
        'month'              => '_rp_event_month',
        'website'            => '_rp_event_website',
        'registration'       => '_rp_event_registration',
        'year'               => '_rp_event_year',
        'distances'          => '_rp_event_distances',
        'date'               => '_rp_event_date',
        'records'            => '_rp_event_records',
        'categories'         => '_rp_event_categories',
        'history'            => '_rp_event_history',
        'course_overview'    => '_rp_event_course_overview',
        'editions'           => '_rp_event_editions',
        'featured'           => '_rp_event_featured',
    ];

    private const ALLOWED_DISTANCES = [
        '5K',
        '10K',
        'Half Marathon',
        'Marathon',
        '50K',
        '100K',
        'Ultra',
        '100-miler',
    ];

    private const ALLOWED_CATEGORIES = [
        'men',
        'women',
        'wheelchair',
        'Masters 40+',
        'Masters 50+',
        'mixed',
        'open',
    ];

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('init', [$this, 'register_event_region_taxonomy']);
        add_action('init', [$this, 'seed_event_regions']);
        add_action('rest_api_init', [$this, 'register_meta']);
        add_action('rest_api_init', [$this, 'register_rest_event_fields']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta'], 10, 2);
        add_filter('rest_' . self::POST_TYPE . '_collection_params', [$this, 'add_rest_collection_params']);
        add_filter('rest_' . self::POST_TYPE . '_query', [$this, 'filter_rest_query'], 10, 2);
    }

    public function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels'        => $this->get_labels(),
            'public'        => true,
            'has_archive'   => true,
            'show_in_rest'  => true,
            'rest_base'     => 'events',
            'rest_namespace' => 'wp/v2',
            'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'     => 'dashicons-calendar',
            'rewrite'       => ['slug' => 'events', 'with_front' => false],
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ]);
    }

    public function register_taxonomy(): void {
        register_taxonomy(self::TAXONOMY, self::POST_TYPE, [
            'hierarchical'      => true,
            'public'            => true,
            'show_in_rest'      => true,
            'rest_base'         => 'event_type',
            'rest_namespace'    => 'wp/v2',
            'show_admin_column' => true,
            'labels'            => [
                'name'              => _x('Event Types', 'taxonomy general name', 'runpartner'),
                'singular_name'     => _x('Event Type', 'taxonomy singular name', 'runpartner'),
                'search_items'      => __('Search Event Types', 'runpartner'),
                'all_items'         => __('All Event Types', 'runpartner'),
                'parent_item'       => __('Parent Event Type', 'runpartner'),
                'parent_item_colon' => __('Parent Event Type:', 'runpartner'),
                'edit_item'         => __('Edit Event Type', 'runpartner'),
                'update_item'       => __('Update Event Type', 'runpartner'),
                'add_new_item'      => __('Add New Event Type', 'runpartner'),
                'new_item_name'     => __('New Event Type Name', 'runpartner'),
                'menu_name'         => __('Event Types', 'runpartner'),
            ],
        ]);
    }

    public function register_event_region_taxonomy(): void {
        register_taxonomy(self::EVENT_REGION_TAXONOMY, self::POST_TYPE, [
            'hierarchical'      => true,
            'public'            => true,
            'show_in_rest'      => true,
            'rest_base'         => 'event_regions',
            'rest_namespace'    => 'wp/v2',
            'show_admin_column' => true,
            'labels'            => [
                'name'              => _x('Event Regions', 'taxonomy general name', 'runpartner'),
                'singular_name'     => _x('Event Region', 'taxonomy singular name', 'runpartner'),
                'search_items'      => __('Search Event Regions', 'runpartner'),
                'all_items'         => __('All Event Regions', 'runpartner'),
                'parent_item'       => __('Parent Region', 'runpartner'),
                'parent_item_colon' => __('Parent Region:', 'runpartner'),
                'edit_item'         => __('Edit Region', 'runpartner'),
                'update_item'       => __('Update Region', 'runpartner'),
                'add_new_item'      => __('Add New Region', 'runpartner'),
                'new_item_name'     => __('New Region Name', 'runpartner'),
                'menu_name'         => __('Regions', 'runpartner'),
            ],
        ]);
    }

    public function seed_event_regions(): void {
        $regions = [
            'india' => [
                'name'     => 'India',
                'children' => [
                    'andhra-pradesh'      => 'Andhra Pradesh',
                    'arunachal-pradesh'   => 'Arunachal Pradesh',
                    'assam'               => 'Assam',
                    'bihar'               => 'Bihar',
                    'chhattisgarh'        => 'Chhattisgarh',
                    'goa'                 => 'Goa',
                    'gujarat'             => 'Gujarat',
                    'haryana'             => 'Haryana',
                    'himachal-pradesh'    => 'Himachal Pradesh',
                    'jharkhand'           => 'Jharkhand',
                    'karnataka'           => 'Karnataka',
                    'kerala'              => 'Kerala',
                    'madhya-pradesh'      => 'Madhya Pradesh',
                    'maharashtra'         => 'Maharashtra',
                    'manipur'             => 'Manipur',
                    'meghalaya'           => 'Meghalaya',
                    'mizoram'             => 'Mizoram',
                    'nagaland'            => 'Nagaland',
                    'odisha'              => 'Odisha',
                    'punjab'              => 'Punjab',
                    'rajasthan'           => 'Rajasthan',
                    'sikkim'              => 'Sikkim',
                    'tamil-nadu'          => 'Tamil Nadu',
                    'telangana'           => 'Telangana',
                    'tripura'             => 'Tripura',
                    'uttar-pradesh'       => 'Uttar Pradesh',
                    'uttarakhand'         => 'Uttarakhand',
                    'west-bengal'         => 'West Bengal',
                    'andaman-nicobar'           => 'Andaman & Nicobar',
                    'chandigarh'                => 'Chandigarh',
                    'dadra-nagar-haveli-daman-diu' => 'Dadra & Nagar Haveli and Daman & Diu',
                    'delhi'                     => 'Delhi',
                    'jammu-kashmir'             => 'Jammu & Kashmir',
                    'ladakh'                    => 'Ladakh',
                    'lakshadweep'               => 'Lakshadweep',
                    'puducherry'                => 'Puducherry',
                ],
            ],
            'american-continent' => [
                'name'     => 'American Continent',
                'children' => [
                    'united-states'  => 'United States',
                    'canada'         => 'Canada',
                    'mexico'         => 'Mexico',
                    'brazil'         => 'Brazil',
                    'argentina'      => 'Argentina',
                    'colombia'       => 'Colombia',
                    'chile'          => 'Chile',
                    'peru'           => 'Peru',
                    'costa-rica'     => 'Costa Rica',
                ],
            ],
            'europe' => [
                'name'     => 'Europe',
                'children' => [
                    'united-kingdom' => 'United Kingdom',
                    'france'         => 'France',
                    'germany'        => 'Germany',
                    'spain'          => 'Spain',
                    'italy'          => 'Italy',
                    'netherlands'    => 'Netherlands',
                    'switzerland'    => 'Switzerland',
                    'sweden'         => 'Sweden',
                    'norway'         => 'Norway',
                    'denmark'        => 'Denmark',
                    'belgium'        => 'Belgium',
                    'ireland'        => 'Ireland',
                    'portugal'       => 'Portugal',
                    'austria'        => 'Austria',
                    'poland'         => 'Poland',
                    'czech-republic' => 'Czech Republic',
                    'greece'         => 'Greece',
                ],
            ],
            'africa-continent' => [
                'name'     => 'Africa Continent',
                'children' => [
                    'kenya'          => 'Kenya',
                    'ethiopia'       => 'Ethiopia',
                    'south-africa'   => 'South Africa',
                    'uganda'         => 'Uganda',
                    'morocco'        => 'Morocco',
                    'nigeria'        => 'Nigeria',
                    'tanzania'       => 'Tanzania',
                    'ghana'          => 'Ghana',
                ],
            ],
            'asia' => [
                'name'     => 'Asia',
                'children' => [
                    'japan'          => 'Japan',
                    'china'          => 'China',
                    'uae'            => 'UAE',
                    'singapore'      => 'Singapore',
                    'thailand'       => 'Thailand',
                    'south-korea'    => 'South Korea',
                    'malaysia'       => 'Malaysia',
                    'vietnam'        => 'Vietnam',
                    'philippines'    => 'Philippines',
                    'indonesia'      => 'Indonesia',
                    'taiwan'         => 'Taiwan',
                    'hong-kong'      => 'Hong Kong',
                    'qatar'          => 'Qatar',
                    'bahrain'        => 'Bahrain',
                    'israel'         => 'Israel',
                    'turkey'         => 'Turkey',
                    'sri-lanka'      => 'Sri Lanka',
                    'nepal'          => 'Nepal',
                ],
            ],
            'oceania' => [
                'name'     => 'Oceania',
                'children' => [
                    'australia'          => 'Australia',
                    'new-zealand'        => 'New Zealand',
                    'fiji'               => 'Fiji',
                    'papua-new-guinea'   => 'Papua New Guinea',
                    'samoa'              => 'Samoa',
                    'tonga'              => 'Tonga',
                ],
            ],
        ];

        foreach ($regions as $parent_slug => $region) {
            $existing_parent = get_term_by('slug', $parent_slug, self::EVENT_REGION_TAXONOMY);

            if ($existing_parent) {
                $parent_id = (int) $existing_parent->term_id;
            } else {
                $result = wp_insert_term($region['name'], self::EVENT_REGION_TAXONOMY, ['slug' => $parent_slug]);

                if (is_wp_error($result) || empty($region['children'])) {
                    continue;
                }

                $parent_id = (int) $result['term_id'];
            }

            foreach ($region['children'] as $child_slug => $child_name) {
                $existing_child = get_term_by('slug', $child_slug, self::EVENT_REGION_TAXONOMY);

                if ($existing_child) {
                    continue;
                }

                wp_insert_term($child_name, self::EVENT_REGION_TAXONOMY, [
                    'slug'   => $child_slug,
                    'parent' => $parent_id,
                ]);
            }
        }
    }

    public function register_meta(): void {
        $meta_schema = $this->get_meta_schema();

        foreach (self::META_FIELDS as $field_key => $meta_key) {
            $schema = $meta_schema[$field_key];

            register_post_meta(self::POST_TYPE, $meta_key, [
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => $schema['type'],
                'description'   => $schema['description'] ?? '',
                'default'       => $schema['default'] ?? '',
                'auth_callback' => [$this, 'meta_auth_callback'],
            ]);
        }
    }

    public function meta_auth_callback(bool $allowed, string $meta_key, int $post_id, int $user_id, string $cap, array $caps): bool {
        return $post_id ? current_user_can('edit_post', $post_id) : current_user_can('edit_posts');
    }

    public function register_rest_event_fields(): void {
        register_rest_field(self::POST_TYPE, 'event_data', [
            'get_callback' => function (array $post): array {
                $data = [];
                foreach (self::META_FIELDS as $field_key => $meta_key) {
                    $value = get_post_meta($post['id'], $meta_key, true);

                    if ($field_key === 'year') {
                        $value = $value !== '' ? (int) $value : 0;
                    } elseif ($field_key === 'distances') {
                        $value = is_array($value) ? $value : ($value !== '' ? explode(',', $value) : []);
                    } elseif ($field_key === 'featured') {
                        $value = (bool) $value;
                    } elseif ($field_key === 'records') {
                        $value = is_array($value) ? $value : [];
                    } elseif ($field_key === 'categories') {
                        $value = is_array($value) ? $value : [];
                    } else {
                        $value = $value !== '' ? $value : '';
                    }

                    $data[$field_key] = $value;
                }
                return $data;
            },
            'update_callback' => function ($value, \WP_Post $post): void {
                if (!is_array($value)) {
                    return;
                }

                foreach (self::META_FIELDS as $field_key => $meta_key) {
                    if (!array_key_exists($field_key, $value)) {
                        continue;
                    }

                    $input = $value[$field_key];

                    if ($field_key === 'distances') {
                        $this->save_distances_from_rest($post->ID, $input);
                        continue;
                    }

                    if ($input === null || $input === '') {
                        delete_post_meta($post->ID, $meta_key);
                        continue;
                    }

                    $sanitized = $this->sanitize_meta_value($field_key, $input);
                    update_post_meta($post->ID, $meta_key, $sanitized);
                }
            },
            'schema' => $this->get_rest_event_schema(),
        ]);
    }

    private function save_distances_from_rest(int $post_id, mixed $input): void {
        if (!is_array($input) || empty($input)) {
            delete_post_meta($post_id, self::META_FIELDS['distances']);
            return;
        }

        $distances = array_map('sanitize_text_field', $input);
        $distances = array_values(array_intersect($distances, self::ALLOWED_DISTANCES));

        if (empty($distances)) {
            delete_post_meta($post_id, self::META_FIELDS['distances']);
            return;
        }

        update_post_meta($post_id, self::META_FIELDS['distances'], $distances);
    }

    private function get_rest_event_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'subtitle'     => ['type' => 'string'],
                'location'     => ['type' => 'string'],
                'country'      => ['type' => 'string'],
                'month'        => ['type' => 'string'],
                'website'      => ['type' => 'string', 'format' => 'uri'],
                'registration' => ['type' => 'string', 'format' => 'uri'],
                'year'         => ['type' => 'integer', 'minimum' => 1900, 'maximum' => (int) date('Y') + 5],
                'distances'    => [
                    'type'  => 'array',
                    'items' => ['type' => 'string', 'enum' => self::ALLOWED_DISTANCES],
                ],
                'date'       => ['type' => 'string', 'format' => 'date'],
                'records'    => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'category'    => ['type' => 'string'],
                            'distance'    => ['type' => 'string'],
                            'time'        => ['type' => 'string'],
                            'holder'      => ['type' => 'string'],
                            'nationality' => ['type' => 'string'],
                            'year'        => ['type' => 'string'],
                        ],
                    ],
                ],
                'categories' => [
                    'type'  => 'array',
                    'items' => ['type' => 'string'],
                ],
                'history'             => ['type' => 'string'],
                'editions'            => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'year'   => ['type' => 'string'],
                            'report' => ['type' => 'string'],
                        ],
                    ],
                ],
                'featured'            => ['type' => 'boolean'],
            ],
        ];
    }

    public function add_meta_box(): void {
        add_meta_box(
            'rp_events_details',
            __('Event Details', 'runpartner'),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box(WP_Post $post): void {
        wp_nonce_field('rp_events_meta', 'rp_events_nonce');

        echo '<div class="rp-events-meta-box">';

        $this->render_field($post->ID, 'subtitle', [
            'label' => __('Subtitle', 'runpartner'),
            'type'  => 'text',
        ]);

        $this->render_field($post->ID, 'location', [
            'label' => __('Location', 'runpartner'),
            'type'  => 'text',
        ]);

        $this->render_field($post->ID, 'country', [
            'label' => __('Country', 'runpartner'),
            'type'  => 'text',
        ]);

        $this->render_field($post->ID, 'month', [
            'label'       => __('Conducted Month', 'runpartner'),
            'type'        => 'text',
            'placeholder' => __('e.g., October', 'runpartner'),
        ]);

        $this->render_field($post->ID, 'year', [
            'label' => __('First Edition Year', 'runpartner'),
            'type'  => 'number',
            'min'   => 1900,
            'max'   => (int) date('Y') + 5,
        ]);

        $this->render_field($post->ID, 'website', [
            'label' => __('Official Website', 'runpartner'),
            'type'  => 'url',
        ]);

        $this->render_field($post->ID, 'registration', [
            'label' => __('Registration Link', 'runpartner'),
            'type'  => 'url',
        ]);

        $this->render_distance_checkboxes($post->ID);

        $this->render_field($post->ID, 'date', [
            'label'       => __('Event Date', 'runpartner'),
            'type'        => 'date',
        ]);

        $this->render_category_checkboxes($post->ID);
        $this->render_records_repeater($post->ID);

        $this->render_field($post->ID, 'history', [
            'label' => __('Event History', 'runpartner'),
            'type'  => 'textarea',
        ]);

        $this->render_field($post->ID, 'course_overview', [
            'label' => __('Course Overview', 'runpartner'),
            'type'  => 'textarea',
        ]);

        $this->render_editions_field($post->ID);

        $this->render_featured_checkbox($post->ID);

        echo '</div>';
    }

    public function save_meta(int $post_id, WP_Post $post): void {
        if (!isset($_POST['rp_events_nonce']) || !wp_verify_nonce($_POST['rp_events_nonce'], 'rp_events_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $schema = $this->get_meta_schema();

        foreach (self::META_FIELDS as $key => $meta_key) {
            $field_schema = $schema[$key];

            if ($key === 'distances') {
                $this->save_distances($post_id);
                continue;
            }

            if ($key === 'editions') {
                $this->save_editions($post_id);
                continue;
            }

            if ($key === 'records') {
                $this->save_records($post_id);
                continue;
            }

            if ($key === 'categories') {
                $this->save_categories($post_id);
                continue;
            }

            if (!isset($_POST[$meta_key])) {
                delete_post_meta($post_id, $meta_key);
                continue;
            }

            $value = $this->sanitize_meta_value($key, wp_unslash($_POST[$meta_key]));

            if ($value === '' || $value === null) {
                delete_post_meta($post_id, $meta_key);
            } else {
                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }

    private function save_distances(int $post_id): void {
        $field_name = self::META_FIELDS['distances'];

        if (empty($_POST[$field_name]) || !is_array($_POST[$field_name])) {
            delete_post_meta($post_id, $field_name);
            return;
        }

        $distances = array_map('sanitize_text_field', array_map('wp_unslash', $_POST[$field_name]));
        $distances = array_values(array_intersect($distances, self::ALLOWED_DISTANCES));

        if (empty($distances)) {
            delete_post_meta($post_id, $field_name);
            return;
        }

        update_post_meta($post_id, $field_name, $distances);
    }

    private function sanitize_meta_value(string $key, mixed $value): mixed {
        return match ($key) {
            'website', 'registration'   => esc_url_raw($value),
            'year'                      => absint($value),
            'editions'                  => $value,
            'records'                   => $value,
            'categories'                => $value,
            'featured'                  => absint($value),
            'distances'                 => $value,
            'history'                   => sanitize_textarea_field($value),
            default                     => sanitize_text_field($value),
        };
    }

    public function add_rest_collection_params(array $query_params): array {
        $query_params['location'] = [
            'description' => __('Filter by location', 'runpartner'),
            'type'        => 'string',
        ];

        $query_params['country'] = [
            'description' => __('Filter by country', 'runpartner'),
            'type'        => 'string',
        ];

        $query_params['distance'] = [
            'description' => __('Filter by distance type', 'runpartner'),
            'type'        => 'string',
            'enum'        => self::ALLOWED_DISTANCES,
        ];

        $query_params['year'] = [
            'description' => __('Filter by year', 'runpartner'),
            'type'        => 'integer',
        ];

        $query_params['month'] = [
            'description' => __('Filter by month', 'runpartner'),
            'type'        => 'string',
        ];

        $query_params['event_date'] = [
            'description' => __('Filter by event date (YYYY-MM-DD)', 'runpartner'),
            'type'        => 'string',
            'format'      => 'date',
        ];

        return $query_params;
    }

    public function filter_rest_query(array $args, WP_REST_Request $request): array {
        $meta_query = [];

        if ($request->get_param('location')) {
            $meta_query[] = [
                'key'     => '_rp_event_location',
                'value'   => sanitize_text_field($request->get_param('location')),
                'compare' => 'LIKE',
            ];
        }

        if ($request->get_param('country')) {
            $meta_query[] = [
                'key'     => '_rp_event_country',
                'value'   => sanitize_text_field($request->get_param('country')),
            ];
        }

        if ($request->get_param('distance')) {
            $meta_query[] = [
                'key'     => '_rp_event_distances',
                'value'   => sanitize_text_field($request->get_param('distance')),
                'compare' => 'LIKE',
            ];
        }

        if ($request->get_param('year')) {
            $meta_query[] = [
                'key'     => '_rp_event_year',
                'value'   => absint($request->get_param('year')),
            ];
        }

        if ($request->get_param('month')) {
            $meta_query[] = [
                'key'     => '_rp_event_month',
                'value'   => sanitize_text_field($request->get_param('month')),
            ];
        }

        if ($request->get_param('event_date')) {
            $meta_query[] = [
                'key'     => '_rp_event_date',
                'value'   => sanitize_text_field($request->get_param('event_date')),
            ];
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = isset($args['meta_query']) ? array_merge($args['meta_query'], $meta_query) : $meta_query;
        }

        return $args;
    }

    private function get_meta_schema(): array {
        return [
            'subtitle' => [
                'type'        => 'string',
                'description' => 'Event subtitle',
                'default'     => '',
            ],
            'location' => [
                'type'        => 'string',
                'description' => 'Event location',
                'default'     => '',
            ],
            'country' => [
                'type'        => 'string',
                'description' => 'Event country',
                'default'     => '',
            ],
            'month' => [
                'type'        => 'string',
                'description' => 'Conducted month',
                'default'     => '',
            ],
            'website' => [
                'type'        => 'string',
                'format'      => 'uri',
                'description' => 'Official website URL',
                'default'     => '',
            ],
            'registration' => [
                'type'        => 'string',
                'format'      => 'uri',
                'description' => 'Registration URL',
                'default'     => '',
            ],
            'year' => [
                'type'        => 'integer',
                'description' => 'First edition year',
                'minimum'     => 1900,
                'maximum'     => (int) date('Y') + 5,
                'default'     => 0,
            ],
            'distances' => [
                'type'        => 'array',
                'description' => 'Available race distances',
                'items'       => [
                    'type' => 'string',
                    'enum' => self::ALLOWED_DISTANCES,
                ],
                'default'     => [],
            ],
            'date' => [
                'type'        => 'string',
                'format'      => 'date',
                'description' => 'Event date (YYYY-MM-DD)',
                'default'     => '',
            ],
            'records' => [
                'type'        => 'array',
                'description' => 'Course records per category',
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'category'    => ['type' => 'string'],
                        'distance'    => ['type' => 'string'],
                        'time'        => ['type' => 'string'],
                        'holder'      => ['type' => 'string'],
                        'nationality' => ['type' => 'string'],
                        'year'        => ['type' => 'string'],
                    ],
                ],
                'default'     => [],
            ],
            'categories' => [
                'type'        => 'array',
                'description' => 'Selected record categories',
                'items'       => [
                    'type' => 'string',
                ],
                'default'     => ['men', 'women'],
            ],
            'history' => [
                'type'        => 'string',
                'description' => 'Event history narrative',
                'default'     => '',
            ],
            'course_overview' => [
                'type'        => 'string',
                'description' => 'Short description of the race course (terrain, elevation, route type)',
                'default'     => '',
            ],
            'editions' => [
                'type'        => 'array',
                'description' => 'Past edition reports (year + text)',
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'year'   => ['type' => 'string'],
                        'report' => ['type' => 'string'],
                    ],
                ],
                'default'     => [],
            ],
            'featured' => [
                'type'        => 'boolean',
                'description' => 'Mark as featured event',
                'default'     => false,
            ],
        ];
    }

    private function get_labels(): array {
        return [
            'name'               => _x('Events', 'post type general name', 'runpartner'),
            'singular_name'      => _x('Event', 'post type singular name', 'runpartner'),
            'menu_name'          => _x('Events', 'admin menu name', 'runpartner'),
            'name_admin_bar'     => _x('Event', 'add new on admin bar', 'runpartner'),
            'add_new'            => __('Add New', 'runpartner'),
            'add_new_item'       => __('Add New Event', 'runpartner'),
            'new_item'           => __('New Event', 'runpartner'),
            'edit_item'          => __('Edit Event', 'runpartner'),
            'view_item'          => __('View Event', 'runpartner'),
            'view_items'         => __('View Events', 'runpartner'),
            'search_items'       => __('Search Events', 'runpartner'),
            'not_found'          => __('No events found', 'runpartner'),
            'not_found_in_trash' => __('No events found in trash', 'runpartner'),
            'parent_item_colon'  => __('Parent Event:', 'runpartner'),
            'all_items'          => __('All Events', 'runpartner'),
        ];
    }

    private function render_field(int $post_id, string $field_key, array $args): void {
        $meta_key = self::META_FIELDS[$field_key];
        $value    = get_post_meta($post_id, $meta_key, true);

        if ($field_key === 'distances') {
            return;
        }

        $is_textarea = isset($args['type']) && 'textarea' === $args['type'];
        $is_url      = isset($args['type']) && 'url' === $args['type'];
        ?>
        <p>
            <label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($args['label']); ?></label>
            <?php if ($is_textarea) : ?>
                <textarea
                    id="<?php echo esc_attr($meta_key); ?>"
                    name="<?php echo esc_attr($meta_key); ?>"
                    class="large-text"
                    rows="5"
                ><?php echo esc_textarea($value); ?></textarea>
            <?php else : ?>
                <input
                    type="<?php echo esc_attr($args['type']); ?>"
                    id="<?php echo esc_attr($meta_key); ?>"
                    name="<?php echo esc_attr($meta_key); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    class="<?php echo $is_url ? 'large-text' : 'regular-text'; ?>"
                    <?php if (isset($args['placeholder'])) : ?>
                        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                    <?php endif; ?>
                    <?php if (isset($args['min'])) : ?>
                        min="<?php echo esc_attr($args['min']); ?>"
                    <?php endif; ?>
                    <?php if (isset($args['max'])) : ?>
                        max="<?php echo esc_attr($args['max']); ?>"
                    <?php endif; ?>
                />
            <?php endif; ?>
        </p>
        <?php
    }

    private function render_distance_checkboxes(int $post_id): void {
        $meta_key = self::META_FIELDS['distances'];
        $saved    = get_post_meta($post_id, $meta_key, true);
        $selected = is_array($saved) ? $saved : ($saved ? explode(',', $saved) : []);
        ?>
        <fieldset>
            <legend><?php esc_html_e('Distance Types', 'runpartner'); ?></legend>
            <div class="rp-checkboxes-row">
                <?php foreach (self::ALLOWED_DISTANCES as $distance) : ?>
                    <label class="rp-checkbox-label">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($meta_key); ?>[]"
                            value="<?php echo esc_attr($distance); ?>"
                            <?php checked(in_array($distance, $selected, true)); ?>
                        />
                        <?php echo esc_html($distance); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php
    }

    private function render_featured_checkbox(int $post_id): void {
        $meta_key = self::META_FIELDS['featured'];
        $checked  = (bool) get_post_meta($post_id, $meta_key, true);
        ?>
        <p>
            <label>
                <input
                    type="checkbox"
                    name="<?php echo esc_attr($meta_key); ?>"
                    value="1"
                    <?php checked($checked); ?>
                />
                <?php esc_html_e('Mark as featured event', 'runpartner'); ?>
            </label>
        </p>
        <?php
    }

    public function get_allowed_categories(): array {
        return apply_filters('rp_event_categories', self::ALLOWED_CATEGORIES);
    }

    private function render_category_checkboxes(int $post_id): void {
        $meta_key = self::META_FIELDS['categories'];
        $saved    = get_post_meta($post_id, $meta_key, true);
        $selected = is_array($saved) ? $saved : ['men', 'women'];
        $categories = $this->get_allowed_categories();
        ?>
        <fieldset>
            <legend><?php esc_html_e('Record Categories', 'runpartner'); ?></legend>
            <p class="description"><?php esc_html_e('Check categories to enable course record entries for this event.', 'runpartner'); ?></p>
            <div class="rp-checkboxes-row">
                <?php foreach ($categories as $cat) : ?>
                    <label class="rp-checkbox-label">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($meta_key); ?>[]"
                            value="<?php echo esc_attr($cat); ?>"
                            <?php checked(in_array($cat, $selected, true)); ?>
                        />
                        <?php echo esc_html(ucfirst($cat)); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php
    }

    private function render_records_repeater(int $post_id): void {
        $records_key   = self::META_FIELDS['records'];
        $categories_key = self::META_FIELDS['categories'];
        $all_records   = get_post_meta($post_id, $records_key, true);
        $all_records   = is_array($all_records) ? $all_records : [];
        $saved_cats    = get_post_meta($post_id, $categories_key, true);
        $active_cats   = is_array($saved_cats) ? $saved_cats : ['men', 'women'];
        $allowed_cats  = $this->get_allowed_categories();
        $allowed_dists = self::ALLOWED_DISTANCES;
        ?>
        <style>
        .rp-record-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 8px; padding: 8px; background: #f6f7f7; border-radius: 4px; }
        .rp-record-row select,
        .rp-record-row input { margin: 0 !important; }
        .rp-record-time { width: 110px !important; }
        .rp-record-holder { width: 140px !important; }
        .rp-record-nationality { width: 80px !important; }
        .rp-record-year { width: 70px !important; }
        .rp-record-remove { white-space: nowrap; color: #b32d2e; font-size: 12px; }
        .rp-record-remove input { margin-right: 3px; }
        .rp-records-category { margin-top: 12px; }
        .rp-records-category legend { font-weight: 600; padding: 0 4px; }
        .rp-add-record { margin-top: 4px !important; }
        </style>
        <?php
        foreach ($active_cats as $cat) :
            if (!in_array($cat, $allowed_cats, true)) continue;
            $cat_records = array_values(array_filter($all_records, fn($r) => ($r['category'] ?? '') === $cat));
        ?>
        <fieldset class="rp-records-category" data-category="<?php echo esc_attr($cat); ?>">
            <legend><?php echo esc_html(ucfirst($cat) . ' ' . __('Records', 'runpartner')); ?></legend>
            <div class="rp-records-rows" data-category="<?php echo esc_attr($cat); ?>">
                <?php foreach ($cat_records as $index => $record) : ?>
                <div class="rp-record-row">
                    <input type="hidden" name="<?php echo esc_attr($records_key); ?>[<?php echo (int) $index; ?>][category]" value="<?php echo esc_attr($cat); ?>" />
                    <select name="<?php echo esc_attr($records_key); ?>[<?php echo (int) $index; ?>][distance]">
                        <option value=""><?php esc_html_e('Select distance', 'runpartner'); ?></option>
                        <?php foreach ($allowed_dists as $dist) : ?>
                            <option value="<?php echo esc_attr($dist); ?>" <?php selected($record['distance'] ?? '', $dist); ?>><?php echo esc_html($dist); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="<?php echo esc_attr($records_key); ?>[<?php echo (int) $index; ?>][time]" value="<?php echo esc_attr($record['time'] ?? ''); ?>" placeholder="<?php esc_attr_e('Time (e.g. 2:01:09)', 'runpartner'); ?>" class="rp-record-time" />
                    <input type="text" name="<?php echo esc_attr($records_key); ?>[<?php echo (int) $index; ?>][holder]" value="<?php echo esc_attr($record['holder'] ?? ''); ?>" placeholder="<?php esc_attr_e('Athlete name', 'runpartner'); ?>" class="rp-record-holder" />
                    <input type="text" name="<?php echo esc_attr($records_key); ?>[<?php echo (int) $index; ?>][nationality]" value="<?php echo esc_attr($record['nationality'] ?? ''); ?>" placeholder="<?php esc_attr_e('Nat.', 'runpartner'); ?>" class="rp-record-nationality" />
                    <input type="number" name="<?php echo esc_attr($records_key); ?>[<?php echo (int) $index; ?>][year]" value="<?php echo esc_attr($record['year'] ?? ''); ?>" placeholder="<?php esc_attr_e('Year', 'runpartner'); ?>" class="rp-record-year" min="1900" max="<?php echo (int) date('Y'); ?>" />
                    <label class="rp-record-remove">
                        <input type="checkbox" name="<?php echo esc_attr($records_key); ?>[<?php echo (int) $index; ?>][remove]" value="1" />
                        <?php esc_html_e('Remove', 'runpartner'); ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button rp-add-record" data-category="<?php echo esc_attr($cat); ?>">
                <?php echo esc_html(sprintf(__('Add %s Record', 'runpartner'), ucfirst($cat))); ?>
            </button>
        </fieldset>
        <?php endforeach; ?>
        <script>
        (function() {
            const recordsKey = '<?php echo esc_js($records_key); ?>';
            document.querySelectorAll('.rp-add-record').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const category = this.dataset.category;
                    const rowsContainer = this.parentElement.querySelector('.rp-records-rows');
                    if (!rowsContainer) return;
                    const count = document.querySelectorAll('.rp-record-row').length;
                    const row = document.createElement('div');
                    row.className = 'rp-record-row';
                    row.innerHTML = [
                        '<input type="hidden" name="' + recordsKey + '[' + count + '][category]" value="' + category + '" />',
                        '<select name="' + recordsKey + '[' + count + '][distance]"><option value=""><?php echo esc_js(__('Select distance', 'runpartner')); ?></option><?php foreach (self::ALLOWED_DISTANCES as $dist) : ?><option value="<?php echo esc_js($dist); ?>"><?php echo esc_js($dist); ?></option><?php endforeach; ?></select>',
                        '<input type="text" name="' + recordsKey + '[' + count + '][time]" placeholder="<?php echo esc_js(__('Time (e.g. 2:01:09)', 'runpartner')); ?>" class="rp-record-time" />',
                        '<input type="text" name="' + recordsKey + '[' + count + '][holder]" placeholder="<?php echo esc_js(__('Athlete name', 'runpartner')); ?>" class="rp-record-holder" />',
                        '<input type="text" name="' + recordsKey + '[' + count + '][nationality]" placeholder="<?php echo esc_js(__('Nat.', 'runpartner')); ?>" class="rp-record-nationality" />',
                        '<input type="number" name="' + recordsKey + '[' + count + '][year]" placeholder="<?php echo esc_js(__('Year', 'runpartner')); ?>" class="rp-record-year" min="1900" max="<?php echo (int) date('Y'); ?>" />',
                        '<label class="rp-record-remove"><input type="checkbox" name="' + recordsKey + '[' + count + '][remove]" value="1" /> <?php echo esc_js(__('Remove', 'runpartner')); ?></label>'
                    ].join('');
                    rowsContainer.appendChild(row);
                });
            });
        })();
        </script>
        <?php
    }

    private function save_records(int $post_id): void {
        $meta_key = self::META_FIELDS['records'];

        if (!isset($_POST[$meta_key]) || !is_array($_POST[$meta_key])) {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        $records = [];
        foreach ($_POST[$meta_key] as $entry) {
            if (isset($entry['remove']) && $entry['remove']) {
                continue;
            }
            if (empty($entry['category']) || empty($entry['distance'])) {
                continue;
            }
            $records[] = [
                'category'    => sanitize_text_field(wp_unslash($entry['category'])),
                'distance'    => sanitize_text_field(wp_unslash($entry['distance'])),
                'time'        => sanitize_text_field(wp_unslash($entry['time'] ?? '')),
                'holder'      => sanitize_text_field(wp_unslash($entry['holder'] ?? '')),
                'nationality' => sanitize_text_field(wp_unslash($entry['nationality'] ?? '')),
                'year'        => sanitize_text_field(wp_unslash($entry['year'] ?? '')),
            ];
        }

        if (empty($records)) {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $records);
        }
    }

    private function save_categories(int $post_id): void {
        $meta_key = self::META_FIELDS['categories'];

        if (empty($_POST[$meta_key]) || !is_array($_POST[$meta_key])) {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        $categories = array_map('sanitize_text_field', array_map('wp_unslash', $_POST[$meta_key]));
        $categories = array_values(array_intersect($categories, $this->get_allowed_categories()));

        if (empty($categories)) {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $categories);
        }
    }

    private function render_editions_field(int $post_id): void {
        $meta_key = self::META_FIELDS['editions'];
        $editions = get_post_meta($post_id, $meta_key, true);
        $editions = is_array($editions) ? $editions : [];
        ?>
        <fieldset>
            <legend><?php esc_html_e('Past Editions (yearly reports)', 'runpartner'); ?></legend>
            <div id="rp-editions-wrapper">
                <?php foreach ($editions as $index => $entry) : ?>
                    <div class="rp-edition-row">
                        <p>
                            <label>
                                <?php esc_html_e('Year', 'runpartner'); ?>
                                <input
                                    type="text"
                                    name="<?php echo esc_attr($meta_key); ?>[<?php echo (int) $index; ?>][year]"
                                    value="<?php echo esc_attr($entry['year'] ?? ''); ?>"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e('e.g., 2025', 'runpartner'); ?>"
                                />
                            </label>
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('Report', 'runpartner'); ?>
                                <textarea
                                    name="<?php echo esc_attr($meta_key); ?>[<?php echo (int) $index; ?>][report]"
                                    class="large-text"
                                    rows="4"
                                ><?php echo esc_textarea($entry['report'] ?? ''); ?></textarea>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox"
                                    name="<?php echo esc_attr($meta_key); ?>[<?php echo (int) $index; ?>][remove]"
                                    value="1"
                                />
                                <?php esc_html_e('Remove this edition', 'runpartner'); ?>
                            </label>
                        </p>
                        <hr />
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="rp-add-edition" class="button">
                <?php esc_html_e('Add Edition', 'runpartner'); ?>
            </button>
        </fieldset>
        <script>
        document.getElementById('rp-add-edition')?.addEventListener('click', function() {
            const wrapper = document.getElementById('rp-editions-wrapper');
            if (!wrapper) return;
            const count = wrapper.querySelectorAll('.rp-edition-row').length;
            const html = [
                '<div class="rp-edition-row">',
                '<p><label><?php echo esc_js(__('Year', 'runpartner')); ?> <input type="text" name="<?php echo esc_js($meta_key); ?>[' + count + '][year]" class="regular-text" placeholder="<?php echo esc_js(__('e.g., 2025', 'runpartner')); ?>" /></label></p>',
                '<p><label><?php echo esc_js(__('Report', 'runpartner')); ?> <textarea name="<?php echo esc_js($meta_key); ?>[' + count + '][report]" class="large-text" rows="4"></textarea></label></p>',
                '<p><label><input type="checkbox" name="<?php echo esc_js($meta_key); ?>[' + count + '][remove]" value="1" /> <?php echo esc_js(__('Remove this edition', 'runpartner')); ?></label></p>',
                '<hr /></div>'
            ].join('');
            wrapper.insertAdjacentHTML('beforeend', html);
        });
        </script>
        <?php
    }

    private function save_editions(int $post_id): void {
        $meta_key = self::META_FIELDS['editions'];

        if (!isset($_POST[$meta_key]) || !is_array($_POST[$meta_key])) {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        $editions = [];
        foreach ($_POST[$meta_key] as $entry) {
            if (isset($entry['remove']) && $entry['remove']) {
                continue;
            }
            if (empty($entry['year'])) {
                continue;
            }
            $editions[] = [
                'year'   => sanitize_text_field(wp_unslash($entry['year'])),
                'report' => isset($entry['report']) ? sanitize_textarea_field(wp_unslash($entry['report'])) : '',
            ];
        }

        if (empty($editions)) {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $editions);
        }
    }
}
