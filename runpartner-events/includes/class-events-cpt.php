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
        'subtitle'      => '_rp_event_subtitle',
        'location'      => '_rp_event_location',
        'country'       => '_rp_event_country',
        'month'         => '_rp_event_month',
        'website'       => '_rp_event_website',
        'registration'  => '_rp_event_registration',
        'year'          => '_rp_event_year',
        'distances'     => '_rp_event_distances',
    ];

    private const DISTANCES = [
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
        add_action('init', [$this, 'register_meta']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta'], 10, 2);
        add_filter('rest_{self::POST_TYPE}_query', [$this, 'rest_query'], 10, 2);
    }

    public function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels'        => $this->get_labels(),
            'public'        => true,
            'has_archive'   => true,
            'show_in_rest'  => true,
            'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'     => 'dashicons-calendar',
            'rewrite'       => ['slug' => 'events', 'with_front' => false],
        ]);
    }

    public function register_taxonomy(): void {
        register_taxonomy(self::TAXONOMY, self::POST_TYPE, [
            'hierarchical' => true,
            'show_in_rest' => true,
            'labels'       => [
                'name'                       => _x('Event Types', 'taxonomy general name', 'runpartner'),
                'singular_name'              => _x('Event Type', 'taxonomy singular name', 'runpartner'),
                'search_items'               => __('Search Event Types', 'runpartner'),
                'all_items'                  => __('All Event Types', 'runpartner'),
                'parent_item'               => __('Parent Event Type', 'runpartner'),
                'parent_item_colon'         => __('Parent Event Type:', 'runpartner'),
                'edit_item'                 => __('Edit Event Type', 'runpartner'),
                'update_item'               => __('Update Event Type', 'runpartner'),
                'add_new_item'              => __('Add New Event Type', 'runpartner'),
                'new_item_name'             => __('New Event Type Name', 'runpartner'),
                'menu_name'                 => __('Event Types', 'runpartner'),
            ],
        ]);
    }

    public function register_meta(): void {
        foreach (self::META_FIELDS as $key => $meta_key) {
            register_post_meta(self::POST_TYPE, $meta_key, [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => $this->get_meta_type($key),
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ]);
        }
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

        // Subtitle
        $this->render_field($post->ID, 'subtitle', [
            'label' => __('Subtitle', 'runpartner'),
            'type'  => 'text',
        ]);

        // Location
        $this->render_field($post->ID, 'location', [
            'label' => __('Location', 'runpartner'),
            'type'  => 'text',
        ]);

        // Country
        $this->render_field($post->ID, 'country', [
            'label' => __('Country', 'runpartner'),
            'type'  => 'text',
        ]);

        // Conducted Month
        $this->render_field($post->ID, 'month', [
            'label' => __('Conducted Month', 'runpartner'),
            'type'  => 'text',
            'placeholder' => __('e.g., October', 'runpartner'),
        ]);

        // First Edition Year
        $this->render_field($post->ID, 'year', [
            'label' => __('First Edition Year', 'runpartner'),
            'type'  => 'number',
            'min'   => 1900,
            'max'   => date('Y') + 5,
        ]);

        // Official Website
        $this->render_field($post->ID, 'website', [
            'label' => __('Official Website', 'runpartner'),
            'type'  => 'url',
        ]);

        // Registration Link
        $this->render_field($post->ID, 'registration', [
            'label' => __('Registration Link', 'runpartner'),
            'type'  => 'url',
        ]);

        // Distance Types
        $this->render_distance_checkboxes($post->ID);

        echo '</div>';
    }

    public function save_meta(int $post_id, WP_Post $post): void {
        if (!isset($_POST['rp_events_nonce']) || !wp_verify_nonce($_POST['rp_events_nonce'], 'rp_events_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_posts', $post_id)) {
            return;
        }

        foreach (self::META_FIELDS as $key => $meta_key) {
            if ($key === 'distances') {
                continue;
            }
            if (isset($_POST[$meta_key])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$meta_key]));
            }
        }

        // Handle distances separately (checkboxes)
        if (isset($_POST[self::META_FIELDS['distances']])) {
            $distances = array_filter($_POST[self::META_FIELDS['distances']], 'sanitize_text_field');
            update_post_meta($post_id, self::META_FIELDS['distances'], implode(',', $distances));
        } else {
            delete_post_meta($post_id, self::META_FIELDS['distances']);
        }
    }

    public function rest_query(array $args, WP_REST_Request $request): array {
        return $args;
    }

    private function get_labels(): array {
        return [
            'name'                  => _x('Events', 'post type general name', 'runpartner'),
            'singular_name'         => _x('Event', 'post type singular name', 'runpartner'),
            'menu_name'             => _x('Events', 'admin menu name', 'runpartner'),
            'name_admin_bar'       => _x('Event', 'add new on admin bar', 'runpartner'),
            'add_new'              => __('Add New', 'runpartner'),
            'add_new_item'         => __('Add New Event', 'runpartner'),
            'new_item'             => __('New Event', 'runpartner'),
            'edit_item'            => __('Edit Event', 'runpartner'),
            'view_item'            => __('View Event', 'runpartner'),
            'view_items'           => __('View Events', 'runpartner'),
            'search_items'         => __('Search Events', 'runpartner'),
            'not_found'            => __('No events found', 'runpartner'),
            'not_found_in_trash'   => __('No events found in trash', 'runpartner'),
            'parent_item_colon'    => __('Parent Event:', 'runpartner'),
            'all_items'            => __('All Events', 'runpartner'),
        ];
    }

    private function get_meta_type(string $key): string {
        $types = [
            'website'      => 'url',
            'registration' => 'url',
            'year'        => 'integer',
            'distances'   => 'string',
        ];
        return $types[$key] ?? 'string';
    }

    private function render_field(int $post_id, string $field_key, array $args): void {
        $meta_key = self::META_FIELDS[$field_key];
        $value = get_post_meta($post_id, $meta_key, true);
        $name  = $meta_key;
        ?>
        <p>
            <label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($args['label']); ?></label>
            <input
                type="<?php echo esc_attr($args['type']); ?>"
                id="<?php echo esc_attr($meta_key); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo 'url' === $args['type'] ? 'large-text' : 'regular-text'; ?>"
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
        </p>
        <?php
    }

    private function render_distance_checkboxes(int $post_id): void {
        $meta_key  = self::META_FIELDS['distances'];
        $saved_val = get_post_meta($post_id, $meta_key, true);
        $selected  = $saved_val ? array_fill_keys(explode(',', $saved_val), true) : [];
        ?>
        <fieldset>
            <legend><?php esc_html_e('Distance Types', 'runpartner'); ?></legend>
            <div class="rp-checkboxes-row">
                <?php foreach (self::DISTANCES as $distance) : ?>
                    <label class="rp-checkbox-label">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($meta_key); ?>[]"
                            value="<?php echo esc_attr($distance); ?>"
                            <?php checked(isset($selected[$distance])); ?>
                        />
                        <?php echo esc_html($distance); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?php
    }
}
