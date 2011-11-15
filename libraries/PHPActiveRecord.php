<?php if ( ! defined('BASEPATH')) die ('No direct script access allowed');
/*
 *
 * Class name: PHPActiveRecord
 * Initializes PHPActiveRecord and registers the autoloader
 *
 */

class PHPActiveRecord {

    public function __construct()
    {
        // Set a path to the spark root that we can reference
        $spark_path = dirname(__DIR__).'/';

        // Include the CodeIgniter database config file
        // Is the config file in the environment folder?
        if ( ! defined('ENVIRONMENT') OR ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/database.php'))
        {
            if ( ! file_exists($file_path = APPPATH.'config/database.php'))
            {
                show_error('PHPActiveRecord: The configuration file database.php does not exist.');
            }
        }
        require($file_path);

        // Include the ActiveRecord bootstrapper
        require_once $spark_path.'vendor/php-activerecord/ActiveRecord.php';

        // PHPActiveRecord allows multiple connections.
        $connections = array();

        if ($db && $active_group)
        {
            foreach ($db as $conn_name => $conn)
            {
                // Build the DSN string for each connection
                $connections[$conn_name] =   $conn['dbdriver'].
                                    '://'   .$conn['username'].
                                    ':'     .$conn['password'].
                                    '@'     .$conn['hostname'].
                                    '/'     .$conn['database'].
                                    '?charset='. $conn['char_set'];
            }

            // Initialize PHPActiveRecord
            ActiveRecord\Config::initialize(function ($cfg) use ($connections, $active_group) {
                $cfg->set_model_directory(APPPATH.'models/');
                try {
                    $cfg->set_connections($connections);
                } catch(Exception $e) {
                    show_error('PHPActiveRecord: Unable to initialize connection.');
                }
                // This connection is the default for all models
                $cfg->set_default_connection($active_group);
            });

        }

        // extend activerecord_autoload() to allow models in loaded packages
        spl_autoload_register('extended_activerecord_autoload',false,PHP_ACTIVERECORD_AUTOLOAD_PREPEND);
    }
}

// --------------------------------------------------------------------

function extended_activerecord_autoload($class_name)
{
    $CI = get_instance();
    $paths = $CI->load->get_package_paths();
    array_pop($paths); // remove 'application/'
    foreach ($paths as $path)
    {
        $root = realpath($path . '/models/');
        if ( ! $root)
            continue;

        if ($namespaces = ActiveRecord\get_namespaces($class_name))
            $class_name = array_pop($namespaces);

        $file = "$root/$class_name.php";
        if (isset($file) && file_exists($file)) {
            require $file;
            return;
        }
    }
}

/* End of file PHPActiveRecord.php */
/* Location: ./sparks/php-activerecord/<version>/libraries/PHPActiveRecord.php */
