<?php
/**
 * Uniplace php-client Widget
 * Description: A widget that displays uniplace links
 * Version: 0.1a
 * Author: http://www.uniplace.ru/
 * Author URI: http://www.uniplace.ru/
 */

/**
 * стандартное создание виджета WP
 * 
 */
class Uniplace_Widget extends WP_Widget {

	/**
	 * конструктор виджета
	 * 
	 */
	function __construct() {
		$widget_options = array( 'classname' => 'Uniplace_Widget', 'description' => __('A widget that displays Uniplace links', 'uniplace') );
		$control_options = array( 'id_base' => 'uniplace-widget' );
		$this->WP_Widget( 'uniplace-widget', __('Uniplace Widget', 'uniplace'), $widget_options, $control_options );
	}
	
	/**
	 * выводит данные виджета
	 * 
	 */
	function widget() {
		if (class_exists('Uniplace_Installer')) {
			echo Uniplace_Installer::shortcode();
		}
	}

	/**
	 * сохраняет позиции виджета
	 * 
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		return $instance;
	}

	/**
	 * выводит форму настроек виджета
	 * 
	 * @param  array $instance массив сохраненных настроек виджета
	 * @todo: добавить настройки хеша сайта сюда
	 */
	function form( $instance ) {

		$uniplace_site_hash = get_option( 'uniplace_site_hash' );
		
		if ($uniplace_site_hash == '') {
			echo '<p class="no-options-widget">' . __('Please set your Uniplace site hash on settings page: ')
			. '<a href="options-general.php?page=' . Uniplace_Installer::ADMIN_CONFIG_PAGE . '">Uniplace Settings</a></p>';
		}
		
		//@todo: убрать, как будут настройки здесь
		echo '<p class="no-options-widget">' . __('There are no options for this widget.') . '</p>';
		return 'noform';

	}
	
}
