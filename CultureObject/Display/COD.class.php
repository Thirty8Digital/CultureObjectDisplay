<?php

namespace CultureObject\Display;

class COD  {
    
    function __construct() {
        
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
        
        $error_type = __('Plugin Activation Error', 'culture-object');
        $error_string = sprintf(
            /* Translators: 1: Either WordPress or PHP, depending on the version mismatch 2: Required version number */
            __('Culture Object requires %1$s version %2$s or greater.', 'culture-object'),
            $flag,
            $version
        );
        
        wp_die('<p>'.$error_string.'</p>', $error_type,  array('response'=>200, 'back_link'=>TRUE));
    }

    
    static function regenerate_permalinks() {
        flush_rewrite_rules();
    }

}