<?php
/*
Plugin Name: wp-w3-validation
Plugin URI: http://www.haveyougotanypets.com/wp-w3-validation/
Version: 0.1
Author: zigon
Author URI: http://www.haveyougotanypets.com
Description: Places an image on an entry showing its validity (only visible to the person editing the entry - just like the "Edit this entry" link). To use just add wp_w3_validation() to your template file, preferably right next to the function call edit_post_link('Edit this entry.').
 */

/*
 * ACKNOWLEDGEMENTS
 *
 * I would like to use thank Roland Rust (http://wordpress.designpraxis.at)
 * for the Batch Validator plugin which i used as a basic template for this one.
 *
 * As well as Ronald Huereca at devlounge.net
 * (http://www.devlounge.net/extras/how-to-write-a-wordpress-plugin)
 * for the How to Write a Wordpress Plugin guide which i also used as a basic
 * template for this one.
 *
 */

/*
 * INSTALLATION
 *
 *
 * This plugin shows a page or post author(**only**) the xhtml and css validity
 * of an entry by using the:
 * http://validator.w3.org and http://jigsaw.w3.org/css-validator/validator
 * APIs to validate the entry, then places images corresponding to the result
 * on the page or post.
 *
 * To use add:
 *
 * <?php if(function_exists('wp_w3_validation')) {wp_w3_validation();} ?>
 *
 * To your theme wherever you would most like the validity output,
 * preferably next to the `<?php edit_post_link(’Edit this entry.’ ” ‘ ‘); ?>`.
 *
 */


    /***********************************************************************
     *
     *  Plugin code
     *
     **********************************************************************/

// Check to see if another plugin has created this class already
if (!class_exists("wp_w3_validation") && !function_exists("wp_w3_validation_admin_panel")) {

    class wp_w3_validation {

        //private $html_doctype = '';

        private $css_doctype = 'css2'; // possible values css1 css2 css2.1 css3

        // ! need to change url('' ) in style sheet manualy
        private $plugin_url = '/wp-content/plugins/wp-w3-validation/';


        /*
         * Validators urls
         *
         * If you wish for faster loading times and higher reliability...
         * you may wish to install your own instances of these validators if so
         * change the url bellow.
         */
        private $validator_xhtml = 'http://validator.w3.org/check';
        private $validator_css = 'http://jigsaw.w3.org/css-validator/validator';
        private $validator_js = '';


        function wp_w3_validation()
        {

        }

        /**
         * Displays validity of W3s allowed
         *
         * @return <type>
         */
        public function display_validity()
        {
            // Check if user should see output
            if(!$this->can_view()){
                return;
            }

            // Get saved vars
            $plugin_options = get_option($this->admin_options_name);
            $valid = true;
            $output = '';

            // Run validation functions
            // XHTML validation
            if($plugin_options['display_html'] == 'true') {
                $html = $this->display_html_validity();
                if($html[0]) {
                    $output .= '<li class="valid">' . $html[1] . '</li>' . "\n";
                } else {
                    $output .= '<li class="invalid">' . $html[1] . '</li>' . "\n";
                }
                $valid = $valid && $html[0];
            }
            // CSS validation
            if ($plugin_options['display_css'] == 'true') {
                $css = $this->display_css_validity();
                if($css[0]) {
                    $output .= '<li class="valid">' . "\n" . $css[1] . "\n" . '</li>' . "\n";
                } else {
                    $output .= '<li class="invalid">' . "\n" . $css[1] . "\n" . '</li>' . "\n";
                }
                $valid = $valid && $css[0];
            }
            // JS validation (not yet implemented)
            if ($plugin_options['display_js'] == 'true') {
                $js = $this->display_js_validity();
                if($js[0]) {
                    $output .= '<li class="valid">' . "\n" . $js[1] . "\n" . '</li>' . "\n";
                } else {
                    $output .= '<li class="invalid">' . "\n" . $js[1] . "\n" . '</li>' . "\n";
                }
                $valid = $valid && $js[0];
            }

            if($output != ''){
                if($valid){
                    echo "\n" . '<div id="wp-w3-validation-drop-down">' . "\n\t" . '<img src="' . get_bloginfo("url") . $this->plugin_url . 'images/valid.png" alt="' . __('Valid', "wp_w3_validation") . '" />' . "\n\t" . '<ul>' . "\n\t\t" . $output . "\t" . '</ul>' . "\n" . '</div>';

                } else {
                    echo "\n" . '<div id="wp-w3-validation-drop-down">' . "\n\t" . '<img src="' . get_bloginfo("url") . $this->plugin_url . 'images/invalid.png" alt="' . __('Invalid', "wp_w3_validation") . '" />' . "\n\t" . '<ul>' . "\n\t\t" . $output . "\t" . '</ul>' . "\n" . '</div>';
                }
            }
        }

        /**
         * Adds html to show if page is/not valid HTML.
         */
        public function display_html_validity()
        {
            global $post;

            $output = '';
            $valid = false;
            $check_link = $this->validator_xhtml . '?uri=' . get_bloginfo("url") . '/?p=' . $post->ID;
            if($this->is_valid_html_pID($post->ID)) {
                $output = '<a href="' . $check_link . '"><img src="' . get_bloginfo("url") . $this->plugin_url . 'images/xhtml-valid.png" alt="' . __('Valid XHTML', "wp_w3_validation") . '" /></a>';
                $valid = true;
            } else {
                $output = '<a href="' . $check_link . '"><img src="' . get_bloginfo("url") . $this->plugin_url . 'images/xhtml-invalid.png" alt="' . __('Invalid XHTML', "wp_w3_validation") . '" /></a>';
            }
            return array($valid, $output);
        }

        /**
         * Adds html to show if page is/not valid css.
         */
        public function display_css_validity()
        {
            global $post;

            $output = '';
            $valid = false;
            $check_link = $this->validator_css . '?uri=' . get_bloginfo("url") . '/?p=' . $post->ID;
            if($this->is_valid_css_pID($post->ID)) {
                $output = '<a href="' . $check_link . '"><img src="' . get_bloginfo("url") . $this->plugin_url . 'images/css-valid.png" alt="' . __('Valid CSS', "wp_w3_validation") . '" /></a>';
                $valid = true;
            } else {
                $output = '<a href="' . $check_link . '"><img src="' . get_bloginfo("url") . $this->plugin_url . 'images/css-invalid.png" alt="' . __('Invalid CSS', "wp_w3_validation") . '" /></a>';
            }
            return array($valid, $output);
        }

        /**
         * Adds html to show if page is/not valid Javascript.
         *
         * NOT YET IMPLEMENTED
         */
        public function display_js_validity()
        {
            global $post;

            $output = '';
            $valid = false;
            $check_link = '';
            if($this->is_valid_js_pID($post->ID)) {
                $output = '<a href="' . $check_link . '"><img src="' . get_bloginfo("url") . $this->plugin_url . 'images/js-valid.png" alt="' . __('Valid JS', "wp_w3_validation") . '" /></a>';
                $valid = true;
            } else {
                $output = '<a href="' . $check_link . '"><img src="' . get_bloginfo("url") . $this->plugin_url . 'images/js-invalid.png" alt="' . __('Invalid JS', "wp_w3_validation") . '" /></a>';
            }
            return array($valid, $output);
        }


        /**
         * Checks HTML Validity of page or post based on id returns boolean
         *
         * Uses http://validator.w3.org api to return a soap responce via the
         * snoopy class, which is then parsed for the validity result.
         *
         * @param <type> $id
         * @return <type>
         */
        private function is_valid_html_pID($id)
        {
            require_once (ABSPATH . WPINC . '/class-snoopy.php');

            $wpurl = get_bloginfo("url") . '/?p=' . $id;
            $url = $this->validator_xhtml . '?uri=' . $wpurl  . '&output=soap12';

            $client = new Snoopy();
            @$client->fetch($url);
            $data = $client->results;
            $data = explode("\n", $data);
            foreach ($data as $buffer) {
                if (eregi("m:validity",$buffer)) {
                    if(trim(strip_tags($buffer)) == "true") {
                        return true;
                    }
                    break;
                }
            }
            return false;
        }

        /**
         * Checks CSS Validity of page or post based on id returns boolean
         *
         * Uses http://jigsaw.w3.org/css-validator/validator api to return a
         * soap responce via the snoopy class, which is then parsed for the
         * validity result.
         *
         * @param <type> $id
         * @return <type>
         */
        private function is_valid_css_pID($id)
        {
            require_once (ABSPATH . WPINC . '/class-snoopy.php');

            $wpurl = get_bloginfo("url") . '/?p=' . $id;
            $url = $this->validator_css . '?uri=' . $wpurl . '&output=soap12' . '&profile=' . $this->css_doctype;
            $client = new Snoopy();
            @$client->fetch($url);
            $data = $client->results;
            $data = explode("\n", $data);
            foreach ($data as $buffer) {
                if (eregi("m:validity",$buffer)) {
                    if(trim(strip_tags($buffer)) == "true") {
                        return true;
                    }
                    break;
                }
            }
            return false;
        }

        /**
         * Checks JS Validity of page or post based on id returns boolean
         *
         * Except it doesnt as its not valid
         *
         * @param <type> $id
         * @return <type>
         */
        private function is_valid_js_pID($id)
        {
            return true;
        }


        /**
         * Check and perform validation only page editors
         *
         * @global <type> $post
         * @return <type>
         */
        private function can_view()
        {
            global $post;

            if ($post->post_type == 'page') {
                if (!current_user_can('edit_page', $post->ID)){
                    return false;
                }
            } else {
                if (!current_user_can('edit_post', $post->ID)){
                    return false;
                }
            }

            return true;
        }

        /**
         * Link stylesheet in header
         */
        public function add_stylesheet()
        {
            if($this->can_view()){
                echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . $this->plugin_url . 'css/wp_w3_validation_main.css" />' . "\n";
            }
        }

        /**
         * Initalise plugin for first time run (adds stuff to db)
         */
        public function init()
        {
            $this->get_admin_options();
        }



        /***********************************************************************
         *
         *  Admin Panel
         *
         **********************************************************************/

        var $admin_options_name = "wp_w3_validation_admin_options";

        /**
         * Return admin options
         *
         * @return <type>
         */
        private function get_admin_options() {

            // admin options available updated incase bug
            $wp_w3_validation_admin_options = array(
                'display_html' => 'true',
                'display_css' => 'false',
                'display_js' => 'false'
            );

            $plugin_options = get_option($this->admin_options_name);

            // updates vars to be passed back from wp db or adds them if not already there.
            if (!empty($plugin_options)) {
                foreach ($plugin_options as $key => $option)
                $wp_w3_validation_admin_options[$key] = $option;
            } else {
                update_option($this->admin_options_name, $wp_w3_validation_admin_options);
            }

            return $wp_w3_validation_admin_options;
        }



        /**
         * Prints out Admin option page and deals with user updating it.
         */
        public function print_admin_page() {

            $plugin_options = $this->get_admin_options();

            // Set options in wp db after page update
            if (isset($_POST['wp_w3_validation_update_settings'])) {
                if (isset($_POST['wp_w3_validation_display_html'])) {
                    $plugin_options['display_html'] = 'true';
                } else {
                    $plugin_options['display_html'] = 'false';
                }

                if (isset($_POST['wp_w3_validation_display_css'])) {
                    $plugin_options['display_css'] = 'true';
                } else {
                    $plugin_options['display_css'] = 'false';
                }

                if (isset($_POST['wp_w3_validation_display_js'])) {
                    $plugin_options['display_js'] = 'true';
                } else {
                    $plugin_options['display_js'] = 'false';
                }

                update_option($this->admin_options_name, $plugin_options);

                ?>
<div class="updated">
    <p>
        <strong><?php _e("Settings Updated.", "wp_w3_validation");?></strong>
    </p>
</div>
<?php

}
?>

<div class="wrap">
    <div class="icon32" id="icon-options-general"></div>
    <h2><?php _e("W3 Validation Settings", "wp_w3_validation"); ?></h2>
    <p>
        <?php _e("Welcome to the W3 Validation. To use just add:", "wp_w3_validation"); ?>
    </p>
    <p>
        <strong>&#60;&#63;php wp_w3_validation(); &#63;&#62;</strong>
    </p>
    <p>
        <?php _e("To your Page and Post Theme Templates where you want this plugin to show you the page/post's validity.", "wp_w3_validation"); ?><br />
        <a href="#more_info"><?php _e("More Info", "wp_w3_validation"); ?></a>
        <span style="display: none;">
            <?php _e("Example go to Appearance -> Editor in your wp admin section then ", "wp_w3_validation"); ?>
        </span>
    </p>
    <p>
        <strong><?php _e("Note: ", "wp_w3_validation");?></strong><?php _e("The validation icons are only displayed to page people with page edit privlages.", "wp_w3_validation"); ?>
    </p>
    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <h3><?php _e("XHTML Validation", "wp_w3_validation"); ?></h3>
        <p>
            <label for="wp_w3_validation_display_hmtl">
                <input type="checkbox" id="wp_w3_validation_display_hmtl" name="wp_w3_validation_display_html" value="true" <?php if($plugin_options['display_html'] == 'true') { ?>checked="checked"<?php } ?> />
                <?php _e("Display on Page/Post", "wp_w3_validation"); ?>
            </label>
        </p>
        <h3><?php _e("CSS Validation", "wp_w3_validation"); ?></h3>
        <p>
            <label for="wp_w3_validation_display_css">
                <input type="checkbox" id="wp_w3_validation_display_css" name="wp_w3_validation_display_css" value="true" <?php if($plugin_options['display_css'] == 'true') { ?>checked="checked"<?php } ?> />
                <?php _e("Display on Page/Post", "wp_w3_validation"); ?>
            </label>
        </p>
        <h3><?php _e("Javascript Validation", "wp_w3_validation"); ?></h3>
        <p>
            <?php _e("Coming soon...", "wp_w3_validation"); ?>
        </p>
        <p>
            <label for="wp_w3_validation_display_js">
                <input disabled="disabled" type="checkbox" id="wp_w3_validation_display_js" name="wp_w3_validation_display_js" value="true" <?php if($plugin_options['display_js'] == 'true') { ?>checked="checked"<?php } ?> />
                <?php _e("Display on Page/Post", "wp_w3_validation"); ?>
            </label>
        </p>
        <div class="submit">
            <input type="submit" name="wp_w3_validation_update_settings" value="<?php _e("update", "wp_w3_validation"); ?>" />
        </div>
    </form>
</div>
<?php

}

//Initialize the admin panel
public function init_admin_panel()
{
global $wp_w3_validation;

if (function_exists('add_options_page')) {
    add_options_page('Validation W3', 'Validation W3', 9, basename(__FILE__), array(&$wp_w3_validation, 'print_admin_page'));
}

// End Class
}

}


/***********************************************************************
*
*  Start up plugin
*
**********************************************************************/

// create new validator
$wp_w3_validation = new wp_w3_validation();

//Actions and Filters
if (isset($wp_w3_validation)) {

// ACTIONS

// Add style sheet (only if page user can edit page otherwise no point)
add_action('wp_head', array(&$wp_w3_validation, 'add_stylesheet'));

// Call the init function to initialise sb options
add_action('wp_w3_validation/core.php',  array(&$wp_w3_validation, 'init'));

// add the admin panel
add_action('admin_menu', array(&$wp_w3_validation, 'init_admin_panel'));


// FILTERS


}


/***********************************************************************
*
*  End user function calls
*
**********************************************************************/

/**
* Function to call to display validitys selected in admin panel
*
* @global <type> $wp_w3_validation
*/
function wp_w3_validation()
{
global $wp_w3_validation;

// check if page is valid
$wp_w3_validation->display_validity();
}

}
?>