<?php

/**
 * Bootstrap qcubed into drupal
 * This function duplicates the configuration.inc.php file found in Qcubed
 */
function qdrupal_bootstrap(&$app) {
  if (defined('__BOOTSTRAP_INCLUDED__')) {
    return;
  }

  define('__BOOTSTRAP_INCLUDED__', 1);

  if($app->is_module == 1) {
    $qdrupal_path = drupal_get_path('module',$app->aid);
  } else {
    $qdrupal_path = qdrupal_application_path($app);
  }

  //ob_clean(); echo '<pre>'; var_dump($qdrupal_path); print_r($node); exit;

  define('DS', DIRECTORY_SEPARATOR);
  define('PS', PATH_SEPARATOR);

  define ('__DOCROOT__', getenv('DOCUMENT_ROOT'));
  define ('__VIRTUAL_DIRECTORY__', '');
  define('QDRUPAL_ROOT',dirname(__FILE__));

  _qdrupal_check_qcubed_installed();

  if($app->is_module == 1) {
    define('APPLICATION_NAME', '');
  } else {
    define('APPLICATION_NAME', DS . $app->aid);
  }

  define('DRUPAL_ROOT', __DOCROOT__ . base_path());

  define('QDRUPAL_APPLICATION_PATH', DRUPAL_ROOT . $qdrupal_path);

  define('QCODO_DEFAULT_CODEGEN', QCODO_DIST . DS . 'wwwroot' . DS . '_devtools' . DS . 'codegen_settings.xml');
  define('QCODO_DEFAULT_CONFIGURATION', QCODO_DIST . DS . 'wwwroot' .  DS . 'includes' . DS . 'configuration_pro.inc.php');

	_qdrupal_define_application_databases($app->aid);
  
  define ('ALLOW_REMOTE_ADMIN', user_access('administer qdrupal'));
  define ('__URL_REWRITE__', 'apache'); 
  define ('__DEVTOOLS_CLI__', __DOCROOT__ . __SUBDIRECTORY__ . DS . '..' . DS . '_devtools_cli');
  define ('__INCLUDES__', __DOCROOT__ .  __SUBDIRECTORY__ . DS . 'includes');
  define ('__QCODO__', __INCLUDES__ . DS . 'qcodo');
  define ('__QCODO_CORE__', __INCLUDES__ . DS . 'qcodo' . DS . '_core');
  define ('__DATA_CLASSES__', QDRUPAL_APPLICATION_PATH . DS . 'data_classes');
  define ('__DATAGEN_CLASSES__', QDRUPAL_APPLICATION_PATH . DS . 'data_classes' . DS . 'generated');
  define ('__DATA_META_CONTROLS__', QDRUPAL_APPLICATION_PATH . DS . 'data_meta_controls');
  define ('__DATAGEN_META_CONTROLS__', QDRUPAL_APPLICATION_PATH . DS . 'data_meta_controls'. DS . 'generated');
  define ('__QDRUPAL_PAGES__', QDRUPAL_APPLICATION_PATH . DS . 'pages');
  define ('__QDRUPAL_NODES__', QDRUPAL_APPLICATION_PATH . DS . 'nodes');
  define ('__DEVTOOLS__', __SUBDIRECTORY__ . DS . '_devtools');
  define ('__FORM_DRAFTS__', base_path() . $qdrupal_path . DS . 'drafts');
  define ('__PANEL_DRAFTS__', base_path() . $qdrupal_path . DS . 'drafts' . DS . 'dashboard');

  // We don't want "Examples"
  define ('__EXAMPLES__', null);

	// Main Assets
  define ('__JS_ASSETS__', __SUBDIRECTORY__ . DS . 'assets' . DS . 'js');
  define ('__CSS_ASSETS__', __SUBDIRECTORY__ . DS . 'assets' . DS . 'css');
  define ('__IMAGE_ASSETS__', __SUBDIRECTORY__ . DS . 'assets' . DS . 'images');
  define ('__PHP_ASSETS__', __SUBDIRECTORY__ . DS . 'assets' . DS . 'php');

	// Local Assets
  define ('__LOCAL_JS_ASSETS__', base_path() . $qdrupal_path . APPLICATION_NAME . DS . 'assets' . DS . 'js');
  define ('__LOCAL_CSS_ASSETS__', base_path() . $qdrupal_path . APPLICATION_NAME . DS . 'assets' . DS . 'css');
  define ('__LOCAL_IMAGE_ASSETS__', base_path() . $qdrupal_path . APPLICATION_NAME . DS . 'assets' . DS . 'images');
  define ('__LOCAL_PHP_ASSETS__', base_path() . $qdrupal_path . APPLICATION_NAME . DS . 'assets' . DS . 'php');

  // TODO - Integrate Drupal's Location into this
  if ((function_exists('date_default_timezone_set')) && (!ini_get('date.timezone')))
    date_default_timezone_set('America/Denver');

  define('ERROR_PAGE_PATH', __PHP_ASSETS__ . DS . '_core' . DS . 'error_page.php');
  define('ERROR_LOG_PATH', QDRUPAL_APPLICATION_PATH . DS . 'error_log');
   
  //drupal_add_css() puts a / at the beginning, so need to strip it off
  drupal_add_css(substr(__CSS_ASSETS__ . DS . "styles.css", 1));
}

/**
  * Run the QDrupal prepend.inc.php file
  */
function _qdrupal_prepend(&$app) {
  if (defined('__PREPEND_INCLUDED__')) {
		return;
	}

	define('__PREPEND_INCLUDED__', 1);

	qdrupal_bootstrap($app);
	require(__QCODO_CORE__ . DS . 'qcodo.inc.php');

	if(file_exists(QDRUPAL_APPLICATION_PATH . DS . 'application.class.php')) {
		require_once(QDRUPAL_APPLICATION_PATH . DS . 'application.class.php');
	}
	else {
		abstract class QApplication extends QApplicationBase {
			public static function Autoload($strClassName) {
				if (!parent::Autoload($strClassName)) {
				}
			}
		}
	}

	if (array_key_exists('SERVER_PROTOCOL', $_SERVER)) {
		set_error_handler('_qdrupal_error_handler');
		set_exception_handler('QcodoHandleException');
	}

	QApplication::Initialize();
	QApplication::$RequestUri = request_uri();
	QApplication::InitializeDatabaseConnections();

	if (isset($_SESSION)) {
		if (array_key_exists('country_code', $_SESSION))
			QApplication::$CountryCode = $_SESSION['country_code'];
		if (array_key_exists('language_code', $_SESSION))
			QApplication::$LanguageCode = $_SESSION['language_code'];
	}

	/* Currently unsupported in QDrupal
	if (QApplication::$LanguageCode) {
		QI18n::Initialize();
	}
	else {
		global $language;
		QApplication::$CountryCode = 'us';  // Not sure where we can pull this value from yet
		QApplication::$LanguageCode = $language->language;
		QI18n::Initialize();
	}
	 */
}

function _qdrupal_check_qcubed_installed() {
  // Detect whether we're running qcubed or qcubed
  if(file_exists(QDRUPAL_ROOT . DS . 'qcubed')) {
    define('QCODO_DIST',QDRUPAL_ROOT . DS . 'qcubed');
    define ('__SUBDIRECTORY__', base_path().drupal_get_path('module','qdrupal').DS.'qcubed'.DS.'wwwroot');
  }
  else {
    // Keep old path for reference.
    if (!isset($_REQUEST['destination'])) {
      $_REQUEST['destination'] = $_GET['q'];
    }

    $path = drupal_get_normal_path('<front>');
    if ($path && $path != $_GET['q']) {
      // Set the active item in case there are tabs to display or other dependencies on the path.
      menu_set_active_item($path);
      $return = menu_execute_active_handler($path);
    }

    if (empty($return) || $return == MENU_NOT_FOUND || $return == MENU_ACCESS_DENIED) {
      drupal_set_title(t('QDrupal Fatal Error'));
      $return = t('The <a href="http://qcu.be">Qcubed</a> libraries are not installed!
        Please read the INSTALLATION instructions for the QDrupal module
        and install the correct libraries.');
    }
    print theme('page',$return);
  }
}

function _qdrupal_define_application_databases($aid) {
  // load database settings
  $profiles = qdrupal_profile_load($aid);
  $count = 1;
  if ($profiles) {
    foreach ($profiles as $profile) {
      $connection_array = array(
        'adapter' => $profile->setting['adapter'],
        'server' => $profile->setting['server'],
        'port' => $profile->setting['port'],
        'database' => $profile->setting['dbname'],
        'username' => $profile->setting['username'],
        'password' => $profile->setting['password'],
        'profiling' => $profile->setting['profiling']
      );
      define("DB_CONNECTION_$count", serialize($connection_array) );
      $count++;
    }
  } 
  else {
    global $db_url;
    if (is_array($db_url)) {
      $connect_url = $db_url['default'];
    }
    else {
      $connect_url = $db_url;
    }
    $drupal_db = parse_url($connect_url);
    $db_type = substr($connect_url, 0, strpos($connect_url, '://'));
    switch($db_type) {
      case 'pgsql':
        $drupal_adapter = 'PostgreSql';
        break;
      case 'mysqli':
        $drupal_adapter = 'MySqli5';
        break;
      default:
        $drupal_adapter = 'MySql';
        break;
    }
    define('DB_CONNECTION_1', serialize(array(
      'adapter' => $drupal_adapter,
      'server' => $drupal_db['host'],
			'port' => (isset($drupal_db['port'])?$drupal_db['port']:3306),
      'database' => substr($drupal_db['path'], 1),
      'username' => urldecode($drupal_db['user']),
      'password' => urldecode($drupal_db['pass']),
      'profiling' => FALSE)));
  }
}

function _qdrupal_error_handler($errno, $errstr, $errfile, $errline, $blnExit = TRUE) {
  ob_start();
  QcodoHandleError($__exc_errno, $__exc_errstr, $__exc_errfile, $__exc_errline,$blnExit = FALSE);
  $content = '<div style="width: 100%; overflow: auto; border: 1px solid #CDCDCD;">';
  $content .= ob_get_clean();
  $content .= '</div>';

  drupal_error_handler($errno, $errstr, $errfile, $errline, 1);
}

function _qdrupal_restore_drupal_error_handler() {
  set_error_handler('drupal_error_handler');
}

