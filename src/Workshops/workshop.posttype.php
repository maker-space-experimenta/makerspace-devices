<?php


class WorkshopPostType
{

    private $slug;
    private $labels;

    protected static $instance;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->slug = "workshop";

        $this->labels = array(
            'name'          => __('Workshops'),
            'singular_name' => __('Workshop'),
            'edit_item'     => __('Workshop bearbeiten'),
        );
    }


    public function render_shortcode_workshops_list($atts)
    {
        ob_start();
        require dirname(__FILE__) . '/partials/shortcode-workshops-list.partial.php';
        $ReturnString = ob_get_contents();
        ob_end_clean();
        return $ReturnString;
    }

    public function register_shortcodes()
    {
        add_shortcode('makerspace_workshops_list', array($this, "render_shortcode_workshops_list"));
    }


    public function metabox_date_template()
    {
        require(plugin_dir_path(__FILE__) . 'partials/metabox-date.php');
    }
    public function metabox_options_template()
    {
        require(plugin_dir_path(__FILE__) . 'partials/metabox-options.php');
    }

    public function add_metaboxes()
    {

        add_meta_box(
            'metabox_workshop_date',
            'Datum',
            array($this, 'metabox_date_template'),
            $this->slug,
            'side',
            'default'
        );

        add_meta_box(
            'metabox_workshop_options',
            'Optionen',
            array($this, 'metabox_options_template'),
            $this->slug,
            'side',
            'default'
        );
    }

    public function register_posttype()
    {

        $args = array(
            'labels'      => $this->labels,
            'public'      => true,
            'has_archive' => true,
            'menu_icon'          => plugin_dir_url(__FILE__) . '../../menu-icon.png',
            'supports'    => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'taxonomies'  => array(),
            'capabilities' => array(
                'edit_post'          => 'makerspace_calendar_edit_workshops',
                'read_post'          => 'makerspace_calendar_read_workshops',
                'delete_post'        => 'makerspace_calendar_delete_workshops',
                'edit_posts'         => 'makerspace_calendar_edit_workshops',
                'edit_others_posts'  => 'makerspace_calendar_edit_workshops',
                'publish_posts'      => 'makerspace_calendar_publish_workshops',
                'read_private_posts' => 'makerspace_calendar_read_workshops',
                'create_posts'       => 'makerspace_calendar_edit_workshops',
            ),
        );

        register_post_type($this->slug, $args);
    }

    public function save_custom_meta_box()
    {
        if (!isset($_POST["post_ID"]))
            return;

        $pid = $_POST["post_ID"];

        if ($pid == NULL)
            return;


        if (!current_user_can("edit_post", $pid)) {
            return $pid;
        }

        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            return $pid;
        }

        if (isset($_POST["workshop_start_date"])) {
            $workshop_start_date = $_POST["workshop_start_date"];
            $workshop_start_time = $_POST["workshop_start_time"];

            $start = new DateTime($workshop_start_date . ' ' . $workshop_start_time);

            update_post_meta($pid, "workshop_start", $start);
        }

        if (isset($_POST["workshop_end_date"])) {
            $workshop_end_date = $_POST["workshop_end_date"];
            $workshop_end_time = $_POST["workshop_end_time"];

            $end = new DateTime($workshop_end_date . ' ' . $workshop_end_time);

            update_post_meta($pid, "workshop_end", $end);
        }

        if (isset($_POST["workshop_option_highlight"])) {
            update_post_meta($pid, "workshop_option_highlight", true);
        } else {
            update_post_meta($pid, "workshop_option_highlight", false);
        }

        if (isset($_POST["workshop_option_free_seats"])) {
            update_post_meta($pid, "workshop_option_free_seats", $_POST["workshop_option_free_seats"]);
        }

        if (isset($_POST["workshop_option_registration_url"])) {
            update_post_meta($pid, "workshop_option_registration_url", $_POST["workshop_option_registration_url"]);
        }
    }

    public function list_columns_head($defaults)
    {
        global $post_ID;
        $post = get_post($post_ID);
        if ($post && get_post_type($post) == $this->slug) {
            $defaults['ms_workshop_start'] = __('Beginn');
            $defaults['ms_workshop_end'] = __('Ende');
        }
        return $defaults;
    }

    // SHOW THE FEATURED IMAGE
    public function list_columns_content($column_name, $post_ID)
    {
        $post = get_post($post_ID);
        if ($post && get_post_type($post) == $this->slug) {

            echo $column_name;

            if ($column_name == 'ms_workshop_start') {
                $workshop_start_date = get_post_meta($post->ID, 'workshop_start_date', true);
                $workshop_start_time = get_post_meta($post->ID, 'workshop_start_time', true);

                echo $workshop_start_date . " " . $workshop_start_time;
            }

            if ($column_name == 'ms_workshop_end') {
                $workshop_end_date = get_post_meta($post->ID, 'workshop_end_date', true);
                $workshop_end_time = get_post_meta($post->ID, 'workshop_end_time', true);

                echo $workshop_end_date . " " . $workshop_end_time;
            }
        }
    }

    public function render_page_registrations()
    {
        require(plugin_dir_path(__FILE__) . 'partials/registration-list.php');
    }

    public function add_menu()
    {
        add_submenu_page(
            'edit.php?post_type=' . $this->slug,
            'Anmeldungen',
            'Anmeldungen',
            'makerspace_calendar_read_registrations',
            'ms_events_registrations',
            array($this, 'render_page_registrations')
        );
    }

    public function add_caps()
    {
        $role = get_role('editor');
        $role->add_cap('makerspace_calendar_read_workshops', true);
        $role->add_cap('makerspace_calendar_edit_workshops', true);
        $role->add_cap('makerspace_calendar_delete_workshops', true);
        $role->add_cap('makerspace_calendar_publish_workshops', true);
        $role->add_cap('makerspace_calendar_read_registrations', true);

        // $role = add_role('foobar', 'Foo Bar', array());
        // $role->add_cap('foo_bar_cap');
    }

    public function register()
    {
        add_action('init', array($this, 'register_posttype'));
        add_action('init', array($this, 'add_caps'));

        add_action('add_meta_boxes', array($this, 'add_metaboxes'));
        add_action('init', array($this, 'save_custom_meta_box'));

        add_filter('manage_posts_columns', array($this, 'list_columns_head'));
        add_action('manage_posts_custom_column',  array($this, 'list_columns_content'), 10, 2);

        add_action('init', array($this, 'register_Shortcodes'));

        // subpages
        add_action('admin_menu', array($this, 'add_menu'));
    }

    public function activate() {
        global $wpdb;

        $sql = "
            CREATE TABLE IF NOT EXISTS makerspace_calendar_workshop_registrations (
              mse_cal_workshop_registration_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
              mse_cal_workshop_post_id INT NOT NULL,
              mse_cal_workshop_registration_email VARCHAR(255) NOT NULL,
              mse_cal_workshop_registration_firstname VARCHAR(255) NOT NULL,
              mse_cal_workshop_registration_lastname VARCHAR(255) NOT NULL,
              mse_cal_workshop_registration_count INT NOT NULL
            )
        ";

        $wpdb->get_results( $sql );
    }

    public function deactivate( $network_deactivating ) {

    }
}
