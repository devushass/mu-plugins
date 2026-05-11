<?php
/**
 * Athletes CPT and Discipline Taxonomy
 *
 * @package RunPartner
 */

if (!defined('ABSPATH')) {
    exit;
}

final class RunPartner_Athletes_CPT {
    private const POST_TYPE = 'athlete';
    private const TAXONOMY  = 'discipline';

    private const META_FIELDS = [
        'subtitle'     => '_rp_athlete_subtitle',
        'nationality'  => '_rp_athlete_nationality',
        'birth_year'   => '_rp_athlete_birth_year',
        'death_year'   => '_rp_athlete_death_year',
        'achievements' => '_rp_athlete_achievements',
        'coach'        => '_rp_athlete_coach',
    ];

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('rest_api_init', [$this, 'register_meta']);
        add_action('rest_api_init', [$this, 'register_rest_athlete_fields']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta'], 10, 2);
        add_filter('rest_' . self::POST_TYPE . '_collection_params', [$this, 'add_rest_collection_params']);
        add_filter('rest_' . self::POST_TYPE . '_query', [$this, 'filter_rest_query'], 10, 2);
    }

    public function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels'          => $this->get_labels(),
            'public'          => true,
            'has_archive'     => true,
            'show_in_rest'    => true,
            'rest_base'       => 'athletes',
            'rest_namespace'  => 'wp/v2',
            'supports'        => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'       => 'dashicons-groups',
            'rewrite'         => ['slug' => 'athletes', 'with_front' => false],
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ]);
    }

    public function register_taxonomy(): void {
        register_taxonomy(self::TAXONOMY, self::POST_TYPE, [
            'hierarchical'      => true,
            'public'            => true,
            'show_in_rest'      => true,
            'rest_base'         => 'disciplines',
            'rest_namespace'    => 'wp/v2',
            'show_admin_column' => true,
            'labels'            => [
                'name'              => _x('Disciplines', 'taxonomy general name', 'runpartner'),
                'singular_name'     => _x('Discipline', 'taxonomy singular name', 'runpartner'),
                'search_items'      => __('Search Disciplines', 'runpartner'),
                'all_items'         => __('All Disciplines', 'runpartner'),
                'parent_item'       => __('Parent Discipline', 'runpartner'),
                'parent_item_colon' => __('Parent Discipline:', 'runpartner'),
                'edit_item'         => __('Edit Discipline', 'runpartner'),
                'update_item'       => __('Update Discipline', 'runpartner'),
                'add_new_item'      => __('Add New Discipline', 'runpartner'),
                'new_item_name'     => __('New Discipline Name', 'runpartner'),
                'menu_name'         => __('Disciplines', 'runpartner'),
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

    public function register_rest_athlete_fields(): void {
        register_rest_field(self::POST_TYPE, 'athlete_data', [
            'get_callback'    => function (array $post): array {
                $data = [];
                foreach (self::META_FIELDS as $field_key => $meta_key) {
                    $value = get_post_meta($post['id'], $meta_key, true);

                    if (in_array($field_key, ['birth_year', 'death_year', 'coach'], true)) {
                        $value = $value !== '' ? (int) $value : 0;
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

                    if ($input === null || $input === '') {
                        delete_post_meta($post->ID, $meta_key);
                        continue;
                    }

                    $sanitized = $this->sanitize_meta_value($field_key, $input);
                    update_post_meta($post->ID, $meta_key, $sanitized);
                }
            },
            'schema' => $this->get_rest_athlete_schema(),
        ]);
    }

    private function get_rest_athlete_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'subtitle'     => ['type' => 'string'],
                'nationality'  => ['type' => 'string'],
                'birth_year'   => ['type' => 'integer', 'minimum' => 1800, 'maximum' => (int) date('Y')],
                'death_year'   => ['type' => 'integer', 'minimum' => 0, 'maximum' => (int) date('Y')],
                'achievements' => ['type' => 'string'],
                'coach'        => ['type' => 'integer', 'minimum' => 0],
            ],
        ];
    }

    public function add_meta_box(): void {
        add_meta_box(
            'rp_athlete_details',
            __('Athlete Details', 'runpartner'),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box(\WP_Post $post): void {
        wp_nonce_field('rp_athlete_meta', 'rp_athlete_nonce');

        echo '<div class="rp-athlete-meta-box">';

        $this->render_field($post->ID, 'subtitle', [
            'label'       => __('Subtitle', 'runpartner'),
            'type'        => 'text',
            'placeholder' => __('e.g., Marathon World Record Holder', 'runpartner'),
        ]);

        $this->render_field($post->ID, 'nationality', [
            'label'       => __('Nationality', 'runpartner'),
            'type'        => 'text',
            'placeholder' => __('e.g., Kenyan', 'runpartner'),
        ]);

        $this->render_field($post->ID, 'birth_year', [
            'label' => __('Birth Year', 'runpartner'),
            'type'  => 'number',
            'min'   => 1800,
            'max'   => (int) date('Y'),
        ]);

        $this->render_field($post->ID, 'death_year', [
            'label'       => __('Death Year (if applicable)', 'runpartner'),
            'type'        => 'number',
            'min'         => 1800,
            'max'         => (int) date('Y'),
            'placeholder' => __('Leave blank if alive', 'runpartner'),
        ]);

        $this->render_field($post->ID, 'achievements', [
            'label'       => __('Key Achievements', 'runpartner'),
            'type'        => 'textarea',
            'placeholder' => __('One per line', 'runpartner'),
        ]);

        $this->render_coach_dropdown($post->ID);

        echo '</div>';

        $this->render_inline_styles();
    }

    private function render_coach_dropdown(int $post_id): void {
        $meta_key   = self::META_FIELDS['coach'];
        $saved_coach = (int) get_post_meta($post_id, $meta_key, true);

        $coaches = get_posts([
            'post_type'      => 'coach',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        ?>
        <p>
            <label for="<?php echo esc_attr($meta_key); ?>"><?php esc_html_e('Coach', 'runpartner'); ?></label>
            <select id="<?php echo esc_attr($meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>" class="regular-text">
                <option value=""><?php esc_html_e('— No Coach —', 'runpartner'); ?></option>
                <?php foreach ($coaches as $coach) : ?>
                    <option value="<?php echo esc_attr($coach->ID); ?>" <?php selected($saved_coach, $coach->ID); ?>>
                        <?php echo esc_html(get_the_title($coach->ID)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function save_meta(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['rp_athlete_nonce']) || !wp_verify_nonce($_POST['rp_athlete_nonce'], 'rp_athlete_meta')) {
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

            if (!isset($_POST[$meta_key])) {
                delete_post_meta($post_id, $meta_key);
                continue;
            }

            $value = $this->sanitize_meta_value($key, wp_unslash($_POST[$meta_key]));

            if ($value === '' || $value === null || $value === 0) {
                delete_post_meta($post_id, $meta_key);
            } else {
                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }

    private function sanitize_meta_value(string $key, mixed $value): mixed {
        return match ($key) {
            'birth_year', 'death_year', 'coach' => absint($value),
            default                       => sanitize_textarea_field($value),
        };
    }

    public function add_rest_collection_params(array $query_params): array {
        $query_params['nationality'] = [
            'description' => __('Filter by nationality', 'runpartner'),
            'type'        => 'string',
        ];

        $query_params['coach'] = [
            'description' => __('Filter by coach post ID', 'runpartner'),
            'type'        => 'integer',
        ];

        return $query_params;
    }

    public function filter_rest_query(array $args, \WP_REST_Request $request): array {
        $meta_query = [];

        if ($request->get_param('nationality')) {
            $meta_query[] = [
                'key'     => '_rp_athlete_nationality',
                'value'   => sanitize_text_field($request->get_param('nationality')),
                'compare' => 'LIKE',
            ];
        }

        if ($request->get_param('coach')) {
            $meta_query[] = [
                'key'   => '_rp_athlete_coach',
                'value' => absint($request->get_param('coach')),
            ];
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = isset($args['meta_query'])
                ? array_merge($args['meta_query'], $meta_query)
                : $meta_query;
        }

        return $args;
    }

    private function get_meta_schema(): array {
        return [
            'subtitle' => [
                'type'        => 'string',
                'description' => 'Athlete subtitle',
                'default'     => '',
            ],
            'nationality' => [
                'type'        => 'string',
                'description' => 'Athlete nationality',
                'default'     => '',
            ],
            'birth_year' => [
                'type'        => 'integer',
                'description' => 'Birth year',
                'minimum'     => 1800,
                'maximum'     => (int) date('Y'),
                'default'     => 0,
            ],
            'death_year' => [
                'type'        => 'integer',
                'description' => 'Death year (0 = alive)',
                'minimum'     => 0,
                'maximum'     => (int) date('Y'),
                'default'     => 0,
            ],
            'achievements' => [
                'type'        => 'string',
                'description' => 'Key achievements (one per line)',
                'default'     => '',
            ],
            'coach' => [
                'type'        => 'integer',
                'description' => 'Coach post ID',
                'minimum'     => 0,
                'default'     => 0,
            ],
        ];
    }

    private function get_labels(): array {
        return [
            'name'               => _x('Athletes', 'post type general name', 'runpartner'),
            'singular_name'      => _x('Athlete', 'post type singular name', 'runpartner'),
            'menu_name'          => _x('Athletes', 'admin menu name', 'runpartner'),
            'name_admin_bar'     => _x('Athlete', 'add new on admin bar', 'runpartner'),
            'add_new'            => __('Add New', 'runpartner'),
            'add_new_item'       => __('Add New Athlete', 'runpartner'),
            'new_item'           => __('New Athlete', 'runpartner'),
            'edit_item'          => __('Edit Athlete', 'runpartner'),
            'view_item'          => __('View Athlete', 'runpartner'),
            'view_items'         => __('View Athletes', 'runpartner'),
            'search_items'       => __('Search Athletes', 'runpartner'),
            'not_found'          => __('No athletes found', 'runpartner'),
            'not_found_in_trash' => __('No athletes found in trash', 'runpartner'),
            'parent_item_colon'  => __('Parent Athlete:', 'runpartner'),
            'all_items'          => __('All Athletes', 'runpartner'),
        ];
    }

    private function render_field(int $post_id, string $field_key, array $args): void {
        $meta_key   = self::META_FIELDS[$field_key];
        $value      = get_post_meta($post_id, $meta_key, true);
        $is_textarea = ($args['type'] ?? 'text') === 'textarea';
        ?>
        <p>
            <label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($args['label']); ?></label>
            <?php if ($is_textarea) : ?>
                <textarea
                    id="<?php echo esc_attr($meta_key); ?>"
                    name="<?php echo esc_attr($meta_key); ?>"
                    class="large-text"
                    rows="5"
                    <?php if (isset($args['placeholder'])) : ?>
                        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                    <?php endif; ?>
                ><?php echo esc_textarea($value); ?></textarea>
            <?php else : ?>
                <input
                    type="<?php echo esc_attr($args['type']); ?>"
                    id="<?php echo esc_attr($meta_key); ?>"
                    name="<?php echo esc_attr($meta_key); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    class="<?php echo isset($args['type']) && 'url' === $args['type'] ? 'large-text' : 'regular-text'; ?>"
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

    private function render_inline_styles(): void {
        ?>
        <style>
            .rp-athlete-meta-box textarea { width: 100%; }
            .rp-athlete-meta-box label { display: block; font-weight: 600; margin-bottom: 4px; }
            .rp-athlete-meta-box select { display: block; margin-top: 4px; }
        </style>
        <?php
    }
}
