<?php
/**
 * @package Uniplace client
 * @version 1.0.23
 */
/*
Plugin Name: Uniplace client
Plugin URI: http://www.uniplace.ru/
Description: Uniplace client
Author: uniplace.ru
Version: 1.0.23


Codestyle: https://codex.wordpress.org/Стандарты_кодирования_PHP
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	// we're not here
	header( $_SERVER["SERVER_PROTOCOL"]." 404 Not Found" );
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class.uniplace-installer.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class.uniplace-widget.php' );


// действия при активации плагина
register_activation_hook( __FILE__ , array( 'Uniplace_Installer', 'plugin_activation' ) );
// действия при деактивации плагина
register_deactivation_hook( __FILE__ , array( 'Uniplace_Installer', 'plugin_deactivation' ) );

// инициализация при заходе на сайт
add_action( 'init', array( 'Uniplace_Installer', 'init' ) );

// регистрация виджета
add_action( 'widgets_init', array( 'Uniplace_Installer', 'register_widget' ) );

// инициализация при заходе в админку
if ( is_admin() ) {
	add_action( 'init', array( 'Uniplace_Installer', 'initAdmin' ) );
}

