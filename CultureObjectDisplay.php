<?php
/**
 * Plugin Name: Culture Object Display
 * Plugin URI: http://cultureobject.co.uk
 * Description: An extension to Culture Object to provide an archive view of objects and single object view for all themes.
 * Version: 1.0.1-alpha3.002
 * Author: Liam Gladdy / Thirty8 Digital
 * Text Domain: culture-object-display
 * Author URI: https://github.com/lgladdy
 * GitHub Plugin URI: Thirty8Digital/CultureObjectDisplay
 * GitHub Branch: master
 * License: Apache 2 License
 */

require_once('CultureObject/Display/COD.class.php');
register_activation_hook(__FILE__, array('CultureObject\Display\COD', 'check_versions'));
register_activation_hook(__FILE__, array('CultureObject\Display\COD', 'regenerate_permalinks'));
register_deactivation_hook(__FILE__, array('CultureObject\Display\COD', 'regenerate_permalinks'));
$cod = new \CultureObject\Display\COD();