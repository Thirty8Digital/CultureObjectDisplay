<?php

namespace CultureObject\Display;

class COD  {
    
    function __construct() {
        $this->plugin_directory = realpath(__DIR__.'/../../');
        $this->plugin_url = plugins_url('/', __DIR__);
        
        add_action('init', array($this, 'add_global_supports'), 11);
        add_action('admin_menu', array($this,'add_menu_page'), 11);
        add_action('admin_init', array($this,'register_settings'));
        add_filter('the_content', array($this, 'provide_content'), 1);
    }
    
    function add_global_supports() {
        add_post_type_support('object', array('editor'));
        add_theme_support('cos-remaps');
    }
    
    function provide_content($prev) {
        if (get_post_type() != "object") return $prev;
        
        if (is_archive()) {
            //We're in an archive page.
            $prev = $this->text_substitute(get_option('cod_archive'));
        } else {
            //We're in an single
            $prev = $this->text_substitute(get_option('cod_single'));
        }
        
        return $prev;
    }
    
    function text_substitute($html) {
        $object_id = get_the_ID();
        
        $html = preg_replace_callback(
            '/{{cos.field_name.([\w-_]+)}}/',
            function ($matches) {
                return cos_get_remapped_field_name($matches[1]);
            },
            $html
        );
        
        $html = preg_replace_callback(
            '/{{cos.field_value.([\w-_]+)}}/',
            function ($matches) {
                return cos_get_field($matches[1]);
            },
            $html
        );
        
        remove_filter('the_content', array($this, 'provide_content'), 1);
        $html = apply_filters('the_content', $html);
        add_filter('the_content', array($this, 'provide_content'), 1);
        
        return $html;
        
    }
    
    function print_filters_for( $hook = '' ) {
        global $wp_filter;
        if( empty( $hook ) || !isset( $wp_filter[$hook] ) )
            return;
    
        print '<pre>';
        print_r( $wp_filter[$hook] );
        print '</pre>';
    }
    
    function generate_settings_group_content($group) {
        $group_id = $group['id'];
        switch ($group_id) {
            case 'cos_display_settings':
                $message = __('These settings relate to the overall display system and how it works.', 'culture-object-display');
                break;
            default:
                $message = '';
        }
        echo $message;
    }
    
    
    function register_settings() {
        
        add_settings_section('cos_display_settings', __('Main Settings', 'culture-object-display'), array($this,'generate_settings_group_content'), 'cos_display_settings');
        
        register_setting('cos_display_settings', 'cod_archive');
        register_setting('cos_display_settings', 'cod_single');
        
        add_settings_field('cod_archive', __('Archive and Search Results Content', 'culture-object-display'), array($this,'generate_settings_wysiwyg'), 'cos_display_settings', 'cos_display_settings', array('field'=>'cod_archive'));
        
        add_settings_field('cod_single', __('Single Object Content', 'culture-object-display'), array($this,'generate_settings_wysiwyg'), 'cos_display_settings', 'cos_display_settings', array('field'=>'cod_single'));
        
    }
    
    function generate_settings_wysiwyg($args) {
        wp_editor(get_option($args['field']), $args['field']);
    }

    
    function generate_display_page() {        
        include($this->plugin_directory.'/views/display.php');
    }
    
    function add_admin_assets($page) {
        wp_register_style('cod_admin_css', $this->plugin_url . '/css/culture-object-display.css?nc='.time(), false, '1.0.0');
        wp_enqueue_style('cod_admin_css');
        wp_register_script('cod_admin_js', $this->plugin_url . '/js/culture-object-display.js?nc='.time(), array('jquery'), '1.0.0', true);
        wp_enqueue_script('cod_admin_js');
    }
    
    function add_menu_page() {
        $display_page = add_submenu_page('cos_settings', __('Display Settings', 'culture-object-display'), __('Display Settings', 'culture-object-display'), 'administrator', 'cos_display_settings', array($this,'generate_display_page'));
        add_action('load-'.$display_page, array($this, 'add_admin_assets'));
    }
    
    static function check_versions() {
        global $wp_version;
        $wp = '4.5';
        $php = '5.5';
        
        if (version_compare(PHP_VERSION, $php, '<')) {
            $flag = 'PHP';
        } elseif (version_compare($wp_version, $wp, '<')) {
            $flag = 'WordPress';
        } else return;
        $version = 'PHP' == $flag ? $php : $wp;
        deactivate_plugins(basename( __FILE__ ));
        
        $error_type = __('Plugin Activation Error', 'culture-object-display');
        $error_string = sprintf(
            /* Translators: 1: Either WordPress or PHP, depending on the version mismatch 2: Required version number */
            __('Culture Object requires %1$s version %2$s or greater.', 'culture-object-display'),
            $flag,
            $version
        );
        
        wp_die('<p>'.$error_string.'</p>', $error_type,  array('response'=>200, 'back_link'=>TRUE));
    }

    
    static function regenerate_permalinks() {
        flush_rewrite_rules();
    }

}