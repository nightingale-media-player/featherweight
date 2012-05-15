<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Config fields for the Add-on backend.
 */

// Absolute prefix for add-on file names. Folders must exist and be writeable. Example: '/var/addons/addon-', resulting filename for first addon: /var/addons/addon-1.xpi
$config['addon-storage'] = '/var/addons/addon-';

// Absolute prefix for add-on image folder names. Folders must exist and be writeable. Example: '/var/addons/addon-', resulting folder for first addon: /var/addons/addon-1/
$config['addon-image-storage'] = '/var/addons/addon-';
?>
