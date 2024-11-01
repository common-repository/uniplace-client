<?php

class Uniplace_Installer {
	/**
	 * @var VERSION string   			версия плагина
	 * @var NONCE string   				строка для формирования защитного токена для форм ( nonce - "number used once" )
	 * @var DEF_CHARSET string   		кодировка вывода ссылок по умолчанию
	 * @var PATH_TO_CODE string   	путь к папке с кодом php-клиента uniplace
	 * @var CONFIG_FILE_NAME string  имя файла конфига // uniplacer_config.php
	 * @var TRANSLATE_DOMAIN string  уникальное имя для локализации ( "домен" в терминах WP )
	 * @var  string  уникальное имя для локализации ( "домен" в терминах WP )
	 * @var $initiatedAdmin bool   	признак инициализациии плагина в админ. панели WP
	 */
	const VERSION = '0.4.1a';
	const NONCE = 'uniplace-key';
	const DEF_CHARSET = 'utf-8';
	const PATH_TO_CODE = 'codes/php/uniplace_id/';
	const CONFIG_FILE_NAME = 'uniplacer_config.php';
	const TRANSLATE_DOMAIN = 'uniplace';
	const ADMIN_CONFIG_PAGE = 'uniplace-installer-config';
	
	private static $initiatedAdmin = false;
	
	/**
	 * Действия при активации плагина:
	 * нажали кнопку "Активировать"
	 * 
	 */
	public static function plugin_activation() {
		add_option( 'uniplace_charset', 'utf-8' ); // set default encoding value
	}
	
	/**
	 * Действия при активации плагина:
	 * нажали кнопку "Деактивировать"
	 * 
	 */
	public static function plugin_deactivation() {
		delete_option( 'uniplace_site_hash' );
		delete_option( 'uniplace_charset' );
	}
	
	/**
	 * Действия при инициализации движка WP
	 * 
	 * @link: https://codex.wordpress.org/Plugin_API/Action_Reference/init
	 * Fires after WordPress has finished loading but before any headers are sent.
	 */
	public static function init() {
		// выводим ссылки на странице шорткодом [uniplace_links]
		add_shortcode( 'uniplace_links', array( __CLASS__, 'shortcode' ) );
	}
	
	
	/**
	 * Действия при инициализации движка WP админ. части
	 * 
	 */
	public static function initAdmin() {
		if ( !self::$initiatedAdmin ) {
			self::init_admin_hooks();
		}
	}
	
	/**
	 * инициализирует плагин для работы в админ. панели
	 * 
	 */
	public static function init_admin_hooks() {
		self::$initiatedAdmin = true;
		
		// хук при формировании админского меню
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		// фильтр при формировании блока ссылок
		add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( __FILE__ ) . 'uniplace-installer.php' ), array( 'Uniplace_Installer', 'admin_plugin_settings_link' ) );
		// загружаем файлы локализации
		load_plugin_textdomain(self::TRANSLATE_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}
	
	/**
	 * добавляет страницу настроек плагина в пункт меню "Настройки"
	 * 
	 * @link: https://codex.wordpress.org/Function_Reference/add_options_page
	 */
	public static function admin_menu() {
		$hook = add_options_page( 
			 __( 'Uniplace config page', self::TRANSLATE_DOMAIN ),
			 __( 'Uniplace', self::TRANSLATE_DOMAIN ),
			 'manage_options',
			 self::ADMIN_CONFIG_PAGE,
			 array( __CLASS__, 'display_admin_page' )
		 );
	}
	
	/**
	 * добавляет ссылку на страницу настроек плагина
	 * в блоке ссылок плагина на странице списка плагинов /wp-admin/plugins.php
	 * 
	 * @link: https://codex.wordpress.org/Function_Reference/add_options_page
	 */
	public static function admin_plugin_settings_link( $links ) { 
		$settings_link = '<a href="options-general.php?page=' . self::ADMIN_CONFIG_PAGE . '">' .__( 'Settings', self::TRANSLATE_DOMAIN ).'</a>';
		array_unshift( $links, $settings_link );
		return $links; 
	}
	
	/**
	 * работает со страницей плагина в админке
	 * 
	 */
	public static function display_admin_page() {
		if ( isset( $_POST['uniplace_base_setup_btn'] ) ) {
			self::form_config_process();
		}
		
		self::form_config_display();
	}
	
	/**
	 * обрабатывает сохранение формы страницы настроек
	 * создан для будущего расширения функциональности
	 * 
	 */
	public static function form_config_process() {
		self::form_config_save();
	}
	
	/**
	 * сохраняет форму страницы настроек
	 * 
	 */
	public static function form_config_save() {
		$ret = false;
		
		// hacker check
		if ( function_exists( 'current_user_can' )
			&& !current_user_can( 'manage_options' )
		 ) {
			die( _e( 'Hacker?', self::TRANSLATE_DOMAIN ) );
		}
		
		// hacker check
		if ( function_exists( 'check_admin_referer' ) ) {
			check_admin_referer( self::NONCE );
		}
				
		$uniplace_site_hash = trim( sanitize_text_field($_POST['uniplace_site_hash'] ));
		$uniplace_charset = trim( sanitize_text_field($_POST['uniplace_charset'] ));
		$uniplace_charset = ( $uniplace_charset != '' ) ? $uniplace_charset : self::DEF_CHARSET;
		
		update_option( 'uniplace_charset', $uniplace_charset );
		
		if ( $uniplace_site_hash != '' ) {
			update_option( 'uniplace_site_hash', $uniplace_site_hash );
			$ret = self::code_files_install( $uniplace_site_hash );
		}
		
		return $ret;
	}
	
	/**
	 * обработка шорткода [uniplace_links]
	 * сюда перенесен, с небольшими изменениями, код показа ссылок
	 * добавлена обработка подключения:
	 * не упадет в Fatal Error при отсутствии папки/файлов
	 * 
	 * вызов в php-файлах: <?php echo do_shortcode( '[uniplace_links]' );?>
	 * вызов в визуальном редакторе при правке статьи: [uniplace_links]
	 * 
	 * @param  string $uniplace_site_hash хеш сайта в системе uniplace
	 * @return bool
	 */
	public static function shortcode() {
		@include_once( $_SERVER["DOCUMENT_ROOT"] . "/uniplacer_config.php" );
		@include_once( $_SERVER["DOCUMENT_ROOT"] . "/" . _UNIPLACE_USER_ . "/uniplacer.php" ); 

		$res = '';
		
		if ( class_exists( 'Uniplacer' ) && defined( '_UNIPLACE_USER_' ) ) {
			$Uniplacer = new Uniplacer( _UNIPLACE_USER_ );
			$res .= $Uniplacer->get_code( true );
			$links = $Uniplacer->get_links();
			
			if ( $links ) {
				foreach ( $links as $link ) {
					$res .=  $link.'<br>';
				}
			}
		}
		
		return $res;
	}
	
	/**
	 * инсталлирует файлы php-клиента системы uniplace с $uniplace_site_hash в корень сайта
	 * создан для будущего расширения функциональности
	 * 
	 * @param  string $uniplace_site_hash хеш сайта в системе uniplace
	 * @return bool
	 */
	public static function code_files_install( $uniplace_site_hash ) {
		$ret = false;
		$type_mess = '';
		$txt = '';
		
		if ( $uniplace_site_hash != '' ) {
			$ret = self::code_files_copy( $uniplace_site_hash );
			if ( $ret ) {
				$type_mess = 'notice';
				$txt = esc_html__( 'Success: code files installed', self::TRANSLATE_DOMAIN );
			} else {
				$type_mess = 'error';
				$txt = esc_html__( 'Error: code files not installed', self::TRANSLATE_DOMAIN );
			}
		} else {
			$type_mess = 'error';
			$txt = esc_html__( 'Error: Uniplace site hash is empty. Please enter the Uniplace site hash.', self::TRANSLATE_DOMAIN );
		}

		self::show_admin_message( $txt, $type_mess );
		return $ret;
	}
	
	/**
	 * создает папку с именем $uniplace_site_hash в корне сайта с правами 0755
	 * копирует файлы php-клиента системы uniplace в папку с именем $uniplace_site_hash 
	 * 
	 * @param  string $uniplace_site_hash хеш сайта в системе uniplace
	 * @return bool
	 */
	public static function code_files_copy( $uniplace_site_hash ) {
		$uniplace_site_hash = trim( $uniplace_site_hash );
		$ret = false;
		$type_mess = 'error';
		$err = '';
		
		if ( $uniplace_site_hash != '' ) {
			$res = self::create_config_file( $uniplace_site_hash );
			
			if ( $res ) {
				$path = $_SERVER['DOCUMENT_ROOT'] . '/' . $uniplace_site_hash;
				$path = self::prepare_path( $path );	// windows
				
				if ( !file_exists( $path ) ) {
					$res = mkdir( $path, 0755 );
					
					if ( true === $res ) {
						$res = chmod( $path, 0755 );
						
						if ( !$res ) {
							$err = sprintf( esc_html__( 'Warning: Can\'t change folder permissions to 0755 and work may be incorrect. Please change folder permissions to 0755 %s', self::TRANSLATE_DOMAIN ), $path );
						}
						
						$path_to_code = plugin_dir_path( __FILE__ ) . self::PATH_TO_CODE;
						$path_to_code = self::prepare_path( $path_to_code );	// windows
					
						$files = scandir( $path_to_code );
						foreach ( $files as $file ) {
							if ( $file != "." && $file != ".." )							{
								copy( "$path_to_code/$file", "$path/$file" ); 
							}
						}
					} else {
						$err = esc_html__( 'Error: Can\'t create directory with Uniplace client. Please check parent folder permissions', self::TRANSLATE_DOMAIN );
					}
				} else {
					$res = chmod( $path, 0755 );
					
					if ( !$res ) {
						$err = sprintf( esc_html__( 'Warning: Can\'t change folder permissions to 0755 and work may be incorrect. Please change folder permissions to 0755 %s', self::TRANSLATE_DOMAIN ), $path );
					}
					
					$path_to_code = plugin_dir_path( __FILE__ ) . self::PATH_TO_CODE;
					$path_to_code = self::prepare_path( $path_to_code );	// windows
				
					$files = scandir( $path_to_code );
					foreach ( $files as $file ) {
						if ( $file != "." && $file != ".." )							{
							copy( "$path_to_code/$file", "$path/$file" ); 
						}
					}
				}
			} else {
				$err = sprintf( esc_html__( 'Error: Can\'t create config file - %s Please check permissions for folder or this file if this file exists already.', self::TRANSLATE_DOMAIN ), $_SERVER['DOCUMENT_ROOT'] . '/' . self::CONFIG_FILE_NAME );
			}
		} else {
			$err = esc_html__( 'Error: Uniplace site hash is empty. Please enter the Uniplace site hash.', self::TRANSLATE_DOMAIN );
		}
		
		if ( $err == '' ) {
			$ret = true;
			$type_mess = 'notice';
		}
		
		self::show_admin_message( $err, $type_mess );

		return $ret;
	}
	
	/**
	 * заменяет в строке пути $path обратные слеши на прямые
	 * для окружения на платфоме windows
	 * 
	 * @return string
	 */
	public static function prepare_path( $path ) {
		return str_replace( '\\', '/', $path );	// windows
	}
	
	/**
	 * формирует и создает файл конфигурации self::CONFIG_FILE_NAME в корне сайта
	 * 
	 * @param  string $uniplace_site_hash хеш сайта в системе uniplace
	 * @return bool
	 */
	public static function create_config_file( $uniplace_site_hash ) {
		$uniplace_site_hash = trim( $uniplace_site_hash );
		$res = false;
		
		if ( $uniplace_site_hash != '' ) {
			$uniplace_charset = get_option( 'uniplace_charset' );
			$uniplace_charset = ( $uniplace_charset != '' ) ? $uniplace_charset : self::DEF_CHARSET;
			
			$content_config_file = "<?php" . PHP_EOL;
			$content_config_file .= "\tdefine( '_UNIPLACE_USER_', '$uniplace_site_hash' ); // Site hash" . PHP_EOL;
			$content_config_file .= "\tdefine( '_UNIPLACE_CHARSET_', '$uniplace_charset' ); // Anchor text encoding" . PHP_EOL;
			$content_config_file .= "?>";
			
			$config_file = $_SERVER['DOCUMENT_ROOT'] . '/' . self::CONFIG_FILE_NAME;
			
			$res = file_put_contents( $config_file, $content_config_file );
			
			if ( $res ) {
				$res_permissions = chmod( $config_file, 0755 );
				if ( ! $res_permissions ) {
					$err = sprintf( esc_html__( 'Warning: Can\'t change config file permissions to 0755 and work may be incorrect. Please change config file permissions to 0755 %s', self::TRANSLATE_DOMAIN ), $config_file );
				}
			}
		}
		else {
			$err = esc_html__( 'Error: Uniplace site hash is empty. Please enter the Uniplace site hash.', self::TRANSLATE_DOMAIN );
			self::show_admin_message( $err, 'error' );
		}
		
		return ( ( false === $res ) ? false : true );
	}
	
	/**
	 * выводит страницу плагина в админке - html
	 * 
	 */
	public static function form_config_display() {
		?>
		
		<h2><?php esc_html_e('Uniplace settings.', self::TRANSLATE_DOMAIN); ?></h2>
				
		<form name="uniplace_base_setup" method="post">
		
			<?php wp_nonce_field( self::NONCE ); ?>
		
			<table>
				<tr>
					<td style="text-align:right;"><?php esc_html_e('Site hash ( in Uniplace system ):', self::TRANSLATE_DOMAIN); ?></td>
					<td><input style="width: 280px;" type="text" name="uniplace_site_hash" value="<?php echo get_option( 'uniplace_site_hash' ); ?>"/></td>
				</tr>
				<tr>
					<td style="text-align:right;"><?php esc_html_e('Anchor text encoding:', self::TRANSLATE_DOMAIN); ?></td>
					<td><input style="width: 280px;" type="text" name="uniplace_charset" value="<?php echo get_option( 'uniplace_charset' ); ?>"/></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td style="text-align:center">
						<input type="submit" name="uniplace_base_setup_btn" value="<?php esc_html_e('Save', self::TRANSLATE_DOMAIN); ?>" style="width:140px; height:25px"/>
					</td>
					<td>&nbsp;</td>
				</tr>
			</table>
		</form>
		
		<hr>

		<p><?php printf( esc_html__( 'After setting up, please, add %s"Uniplace Widget"%s in sidebar your active theme on "Appearance" - "Widgets".' ,  self::TRANSLATE_DOMAIN ), '<strong>', '</strong>'); ?></p>
		<p><?php printf( esc_html__( 'If your active theme has not sidebar, you can add following %scode%s to your template. For example in footer.php before closing tag &lt;/body&gt;:' , self::TRANSLATE_DOMAIN ), '<strong>', '</strong>'); ?></p>
		<p><strong>&lt;?php echo do_shortcode( "[uniplace_links]" ); ?&gt;</strong></p>		
		<p><?php esc_html_e('You can also add links to any article using the following shortcode:', self::TRANSLATE_DOMAIN); ?></p>
		<p><strong>[uniplace_links]</strong></p>
	
	<?php
	}
	
	/**
	 * выводит сообщение плагина в админке - html
	 * 
	 * @param  string $str	текст выводимого сообщения
	 * @param  string $type тип выводимого сообщения - зависит цвет текста
	 */
	public static function show_admin_message( $str, $type = 'notice' ) {
		$str = trim( $str );
		$type = trim( $type );
		
		if ( $str != '' ) {
			switch ( $type ) {
				case 'error':
					$color = 'red';
					break;
				default:
					$color = 'green';
					break;
			}
		
			echo "
				<div class='updated $type is-dismissible' style='margin-top: 15px;'>
					<p style='color: $color;'>$str</p>
				</div>";
		}
	}
	
	/**
	 * регистрирует виджет в админке
	 * 
	 */
	public static function register_widget() {
		register_widget( 'Uniplace_Widget' );
	}	
	
}
