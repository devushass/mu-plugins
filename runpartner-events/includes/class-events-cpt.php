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
        'course_record'      => '_rp_event_course_record',
        'course_record_holder' => '_rp_event_course_record_holder',
        'history'            => '_rp_event_history',
        'past_edition'       => '_rp_event_past_edition',
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

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
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
                'date'                => ['type' => 'string', 'format' => 'date'],
                'course_record'       => ['type' => 'string'],
                'course_record_holder' => ['type' => 'string'],
                'history'             => ['type' => 'string'],
                'past_edition'        => ['type' => 'integer', 'minimum' => 0],
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

        $this->render_field($post->ID, 'course_record', [
            'label'       => __('Course Record', 'runpartner'),
            'type'        => 'text',
            'placeholder' => __('e.g., 2:01:09', 'runpartner'),
        ]);

        $this->render_field($post->ID, 'course_record_holder', [
            'label'       => __('Course Record Holder', 'runpartner'),
            'type'        => 'text',
            'placeholder' => __('Athlete name', 'runpartner'),
        ]);

        $this->render_field($post->ID, 'history', [
            'label' => __('Event History', 'runpartner'),
            'type'  => 'textarea',
        ]);

        $this->render_past_edition_dropdown($post->ID);

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
            'year', 'past_edition'      => absint($value),
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
            'course_record' => [
                'type'        => 'string',
                'description' => 'Course record time',
                'default'     => '',
            ],
            'course_record_holder' => [
                'type'        => 'string',
                'description' => 'Course record holder name',
                'default'     => '',
            ],
            'history' => [
                'type'        => 'string',
                'description' => 'Event history narrative',
                'default'     => '',
            ],
            'past_edition' => [
                'type'        => 'integer',
                'description' => 'Past edition post ID',
                'minimum'     => 0,
                'default'     => 0,
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

    private function render_past_edition_dropdown(int $post_id): void {
        $meta_key   = self::META_FIELDS['past_edition'];
        $saved      = (int) get_post_meta($post_id, $meta_key, true);
        $editions   = get_posts([
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__not_in'   => [$post_id],
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        ?>
        <p>
            <label for="<?php echo esc_attr($meta_key); ?>"><?php esc_html_e('Past Edition', 'runpartner'); ?></label>
            <select
                id="<?php echo esc_attr($meta_key); ?>"
                name="<?php echo esc_attr($meta_key); ?>"
                class="regular-text"
            >
                <option value=""><?php esc_html_e('None', 'runpartner'); ?></option>
                <?php foreach ($editions as $edition) : ?>
                    <option value="<?php echo esc_attr($edition->ID); ?>" <?php selected($saved, $edition->ID); ?>>
                        <?php echo esc_html(get_the_title($edition->ID)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
}
