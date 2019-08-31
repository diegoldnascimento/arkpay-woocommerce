<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://arkpay.io
 * @since      1.0.0
 *
 * @package    arkpay
 * @subpackage arkpay/includes
 */

if (!function_exists('get_custom_template_part')) {
    function get_custom_template_part($path) {
        $args = func_get_args();
        $args = isset($args[1]) ? $args[1] : $args;

        foreach($args as $arg => $value) {
            $$arg = $value;
        }

        if (locate_template($path . '.php')) {
            include(locate_template($path . '.php'));
        } else {
            echo '<hr/>';
            echo 'Template File ' . $path . ' not found';
            echo '<hr/>';
        }
    }
}