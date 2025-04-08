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
        if (!in_the_loop() || !is_main_query() || get_post_type() != "object") return $prev;
        
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
            '/{{cos.field_name.([\w\-_\ ]+)}}/',
            function ($matches) {
                return cos_get_remapped_field_name($matches[1]);
            },
            $html
        );
        
        $html = preg_replace_callback(
            '/{{cos.field_value.([\w\-_\ ]+)}}/',
            function ($matches) {
                return cos_get_field($matches[1]);
            },
            $html
        );
        
        remove_filter('the_content', array($this, 'provide_content'), 1);
        $html = apply_filters('the_content', $html);
        add_filter('the_content', array($this, 'provide_content'), 1);
        
        
        $all_meta = get_post_meta(get_the_ID());
        
        if (isset($_GET['displayallmeta'])) {
            // Add the styles for the <pre> element
            $html .= '<style>               
                .allmeta { padding:20px; background-color:#f9f9f9;border:1px dotted #000000}
                pre {
                    background-color: #f4f4f4; 
                    border: 1px solid #ddd;   
                    padding: 10px;             
                    font-family: "Courier New", Courier, monospace;
                    font-size: 14px;           
                    color: #333;               
                    overflow-x: auto;          
                    white-space: pre-wrap;     
                    word-wrap: break-word;     
                    border-radius: 5px;
                }
                
            </style>';
        
            // Add heading and metadata dump to the HTML
            $html .= '<div class="allmeta">';
            $html .= '<h3>All object metadata</h3>';
            $html .= '<pre>';
            $html .= print_r($all_meta, true);  
            $html .= '</pre>';
            $html .= '</div>';
        }
        
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
        /*wp_register_style('cod_admin_css', $this->plugin_url . '/css/culture-object-display.css?nc='.time(), false, '1.0.0');
        wp_enqueue_style('cod_admin_css');
        wp_register_script('cod_admin_js', $this->plugin_url . '/js/culture-object-display.js?nc='.time(), array('jquery'), '1.0.0', true);
        wp_enqueue_script('cod_admin_js');*/
        
		add_action('media_buttons', array($this, 'add_cod_button'), 20);
		add_action('admin_print_footer_scripts', array($this, 'add_mce_popup'));
    }
    
    function add_mce_popup() {
        add_thickbox();
        $cos = cos_get_instance();
        
        $provider = $cos->get_sync_provider();
        if (!$provider) {
            ?>
            <div id="cod_media_link" style="display:none;">
                <p>Sorry, your provider doesn't provide a list of fields it uses. We're working on automatically detecting this in the immediate future.</p>
            </div>
            <?php
        } else {
            if (!class_exists($provider['class'])) include_once($provider['file']);
            $provider_class = new $provider['class'];
            $fields = $provider_class->register_remappable_fields();

		?>
		<script>
	        jQuery(document).on('click', '.add_field_name', function() {
    	        val = '{{cos.field_name.'+jQuery('#add_field').val()+'}}';
				window.send_to_editor(val);
				self.parent.tb_remove();
            });
	        jQuery(document).on('click', '.add_field_value', function() {
    	        val = '{{cos.field_value.'+jQuery('#add_field').val()+'}}';
				window.send_to_editor(val);
				self.parent.tb_remove();
            });
		</script>
		
		<div id="cod_media_link" style="display:none;">
            <h2><?php esc_attr_e('Add Field', 'culture-object-display'); ?></h2>

            <select name="add_field" id="add_field">
                <?php foreach($fields as $key => $field) { ?>
            	<option value="<?php echo $key; ?>"><?php echo $field; ?></option>
            	<?php } ?>
            </select><br />
            
            <a style="margin: 10px 10px 0 0;" href="#" class="button add_field_name" title="<?php esc_attr_e( 'Add Field Name', 'culture-object-display'); ?>"><div><?php esc_html_e('Add Field Name', 'culture-object-display'); ?></div></a>
            <a style="margin-top: 10px" href="#" class="button add_field_value" title="<?php esc_attr_e( 'Add Field Value', 'culture-object-display'); ?>"><div><?php esc_html_e('Add Field Value', 'culture-object-display'); ?></div></a>
        </div>

	<?php
        }
	}
    
    function add_cod_button() {
		echo '<a href="#TB_inline?width=600&height=300&inlineId=cod_media_link" class="button thickbox cod_media_link" id="add_cod" title="'.esc_attr__( 'Add Culture Object Field', 'culture-object-display' ).'"><div>'.esc_html__( 'Add Culture Object Field', 'culture-object-display' ).'</div></a>';
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