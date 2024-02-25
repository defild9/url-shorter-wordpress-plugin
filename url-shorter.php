<?php
/*
Plugin Name: Url Shorter
Description: Plugin for create short link.
Version: 0.1
Author: Yevhenii Biletskiy
*/

add_action( 'admin_head', 'true_colored_admin_bar_72aee6' );


function create_short_urls_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'short_urls';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        unique_id varchar(50) NOT NULL,
        url text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

register_activation_hook( __FILE__, 'create_short_urls_table' );

function shorten_url($url) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'short_urls';
    $unique_id = uniqid();
    $wpdb->insert(
        $table_name,
        array(
            'unique_id' => $unique_id,
            'url' => $url
        )
    );
    return home_url('/') . $unique_id;
}

function display_shorten_url_form() {
    ?>
    <form method="post" class="wp-block-search__button-outside wp-block-search__text-button wp-block-search">
        <label for="long_url" class="wp-block-search__label">Enter URL:</label>
        <input type="text" class="wp-block-search__input" name="long_url" id="long_url" required>
        <input type="submit" class="wp-block-search__button wp-element-button" value="Shorten">
    </form>
    <?php
}

add_shortcode('shorten_url_form', 'display_shorten_url_form');

function shorten_url_form_handler() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['long_url'])) {
        $long_url = esc_url_raw($_POST['long_url']);
        $short_url = shorten_url($long_url);
        echo 'Shortened URL: <a href="' . $short_url . '">' . $short_url . '</a>';
    }
}

add_action('init', 'shorten_url_form_handler');

function display_shortened_urls() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'short_urls';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    if ($results) {
        ?>
        <table class="wp-block-table">
            <tr>
                <th>Shortened URL</th>
                <th>Original URL</th>
            </tr>
            <?php foreach ($results as $result): ?>
                <tr>
                    <td><a href="<?php echo home_url('/') . $result->unique_id; ?>"><?php echo home_url('/') . $result->unique_id; ?></a></td>
                    <td><?php echo $result->url; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    } else {
        echo 'No shortened URLs yet.';
    }
}


add_shortcode('shortened_urls', 'display_shortened_urls');

function redirect_to_long_url() {
    global $wpdb;
    $request_uri = trim($_SERVER['REQUEST_URI'], '/');
    if ($request_uri != '') {
        $table_name = $wpdb->prefix . 'short_urls';
        $long_url = $wpdb->get_var($wpdb->prepare("SELECT url FROM $table_name WHERE unique_id = %s", $request_uri));
        if ($long_url) {
            wp_redirect($long_url);
            exit;
        } else {
            echo 'URL not found.';
        }
    }
}

add_action('init', 'redirect_to_long_url');

function add_shortened_urls_admin_page() {
    add_menu_page(
        'Shortened URLs',
        'Shortened URLs',
        'manage_options',
        'shortened_urls_admin_page',
        'display_shortened_urls_admin_page'
    );
}

add_action('admin_menu', 'add_shortened_urls_admin_page');

function display_shortened_urls_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'short_urls';

    if (isset($_POST['update_url'])) {
        $edited_url = $_POST['edited_url'];
        $unique_id = $_POST['unique_id'];

        $wpdb->update(
            $table_name,
            array('url' => $edited_url),
            array('unique_id' => $unique_id)
        );
    }

$results = $wpdb->get_results("SELECT * FROM $table_name");
?>
<div class="wrap">
    <h1>Shortened URLs</h1>
    <?php if ($results): ?>
        <table class="wp-list-table widefat striped">
            <thead>
            <tr>
                <th>Short URL</th>
                <th>Original URL</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $result): ?>
                <tr>
                    <form method="post">
                        <td><input type="text" style="width: 600px" value="<?php echo esc_attr(home_url('/') . $result->unique_id); ?>" readonly></td>
                        <td><input type="text" style="width: 600px" name="edited_url" value="<?php echo esc_attr($result->url); ?>"></td>
                        <td><input type="hidden"   name="unique_id" value="<?php echo esc_attr($result->unique_id); ?>"></td>
                        <td><input type="submit" name="update_url" class="button button-primary" value="Update"></td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No shortened URLs yet.</p>
    <?php endif; ?>
</div>
    <?php
}

