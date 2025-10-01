<?php

if (! function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}
$opencartInstaller = new OpencartInstaller();
class OpencartInstaller
{
    private $OIversion = '1.0.1';
    private $versions = array(
        array(
            'code' => '4103',
            'version' => '4.1.0.3',
        ),
        array(
            'code' => '4102',
            'version' => '4.1.0.2',
        ),
        array(
            'code' => '4101',
            'version' => '4.1.0.1',
        ),
        array(
            'code' => '4100',
            'version' => '4.1.0.0',
        ),
        array(
            'code' => '4023',
            'version' => '4.0.2.3',
        ),
        array(
            'code' => '4022',
            'version' => '4.0.2.2',
        ),
        array(
            'code' => '4021',
            'version' => '4.0.2.1',
        ),
        array(
            'code' => '4020',
            'version' => '4.0.2.0',
        ),
        array(
            'code' => '4011',
            'version' => '4.0.1.1',
        ),
        array(
            'code' => '4010',
            'version' => '4.0.1.0',
        ),
        array(
            'code' => '4000',
            'version' => '4.0.0.0',
        ),
        array(
            'code' => '3041',
            'version' => '3.0.4.1',
        ),
        array(
            'code' => '3040',
            'version' => '3.0.4.0',
        ),
        array(
            'code' => '3039',
            'version' => '3.0.3.9',
        ),
        array(
            'code' => '3038',
            'version' => '3.0.3.8',
        ),
        array(
            'code' => '3037',
            'version' => '3.0.3.7',
        ),
        array(
            'code' => '303',
            'version' => '3.0.3.6',
        ),
        array(
            'code' => '3032',
            'version' => '3.0.3.2',
        ),
        array(
            'code' => '302',
            'version' => '3.0.2.0',
        ),
        array(
            'code' => '301',
            'version' => '3.0.1.2',
        ),
        array(
            'code' => '300',
            'version' => '3.0.0.0',
        ),
        array(
            'code' => '230',
            'version' => '2.3.0.2',
        ),
        array(
            'code' => '220',
            'version' => '2.2.0.0',
        ),
        array(
            'code' => '210',
            'version' => '2.1.0.2',
        ),
        array(
            'code' => '203',
            'version' => '2.0.3.1',
        ),
        array(
            'code' => '202',
            'version' => '2.0.2.0',
        ),
        array(
            'code' => '201',
            'version' => '2.0.1.1',
        ),
    );
    private $installShopunity = true;
    private $db;
    private $unused_db = array();
    public function __construct()
    {
        if (is_file(__DIR__.'/config.php')) {
            require_once(__DIR__.'/config.php');
        }
       
        ini_set('max_execution_time', 960);
        $this->connectMysqli();
        if (!empty($_POST) || !empty($_GET)) {
            $this->queryDefinition();
        }
        echo $this->showPage();
    }
    private function queryDefinition()
    {
        if (isset($_POST['name']) || isset($_GET['name'])) {
            if (!isset($_POST['shopunity'])) {
                $this->installShopunity = false;
            }
            if (DEBUG == '1') {
                $name = $_GET['name'];
                $version = $_GET['version'];
            } else {
                $name = $_POST['name'];
                $version = $_POST['version'];
            }
            $this->createStore($name, $version);
        } elseif (isset($_POST['delete_store'])) {
            $this->deleteStore($_POST);
        } elseif (isset($_POST['delete_database'])) {
            $this->deleteDatabase($_POST['delete_database']);
        } else {
            exit('ERROR');
        }
    }
    // Delete
    private function deleteStore($post)
    {
        $this->remove_dir($post['delete_store']);
        if (isset($post['delete_database']) && $post['delete_database']) {
            $this->db->query("DROP DATABASE " . $post['delete_database']);
            echo 'Table database ' . $post['delete_database'];
        }
    }
    private function deleteDatabase($database)
    {
        $this->db->query("DROP DATABASE " . $database);
        echo 'Table database ' . $database;
    }
    // Creating store
    private function createStore($name, $version)
    {
        $key = array_search($version, array_column($this->versions, 'code'));
        define('VERSION', $version);
        define('VERSION_FULL', $this->versions[$key]['version']);
        $name = str_replace(" ", "_", $name);
        $parts = explode('_', $name);
        unset($parts[0]);
        $realpath = str_replace('\\', '/' , __DIR__);
        $path = $realpath . '/' . VERSION . '/' . $name;
        $http_path =  VERSION . '/' . $name;
        // HTTP
        define('HTTP_SERVER', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/.\\') . '/' . $http_path . '/');
        define('HTTP_OPENCART', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/.\\') . '/' . $http_path . '/');
        define('HTTPS_OPENCART', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/.\\') . '/' . $http_path . '/');
        
        // DIR
        define('DIR_TEMP', str_replace('\\', '/' , __DIR__) . '/temp/');
        if (!is_dir(DIR_TEMP)) mkdir($realpath . '/temp', 0755, true);
        define('DIR_OPENCART', str_replace('\'', '/', $realpath) . '/' . $http_path . '/');
        define('DIR_SOURCE', str_replace('\'', '/', $realpath) . '/' . $http_path . '/upload/');
        define('DESTINATION', $path . '/');
        define('SOURCE', $path . '/upload/');
        // create database
        $this->createDatabase();
        // create a directory
        if (!file_exists($path . '/')) {
            mkdir($path . '/', 0755, true);
        }

        // upload files
        $this->uploadFiles($path);
        // give correct permissions
        $this->correctPermissions();
        // config.php definition
        $this->configDefinition();
        // admin config.php definition
        $this->adminConfigDefinition();
        //fill database
        $this->fillDatabase();
        // install shopunity
        if ($this->installShopunity && VERSION_FULL < '4.0.0.0') {
            $download = json_decode(file_get_contents(HTTP_API . "extensions/d_shopunity/download?store_version=" . (VERSION_FULL > '3.0.3.6' ? '3.0.3.6' : VERSION_FULL)), true);
            $target_url = $download['download'];
            $this->installShopunity($target_url);
        }
        // Delete install folder
        $this->remove_dir(DESTINATION . 'install');
        
        unlink(DESTINATION . 'config-dist.php');
        unlink(DESTINATION . 'admin/config-dist.php');
        echo 'all created';
    }
    private function createDatabase()
    {
        $i = 0;
        do {
            $i++;
            $db_name = DB_NAME_START . '_' . VERSION . '_' . $i;
            $db_exists = $this->db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . $db_name . "'");
            $result = $db_exists->row;
        } while ($result);
        define('DB_DATABASE', $db_name);
        $this->db->create(DB_DATABASE);
    }
    private function uploadFiles($path)
    {
        if (!extension_loaded('zip')) {
            dl('zip.so');
        }

        if (VERSION_FULL <= '3.0.3.6' && VERSION_FULL != '3.0.3.2') {
            $download = json_decode(file_get_contents(HTTP_API . "extensions/opencart/download?store_version=" . VERSION_FULL, false, stream_context_create((isset($arrContextOptions) ? $arrContextOptions : array()))), true);
            $target_url = $download['download'];
        } else if (VERSION_FULL !== '3.0.4.1') {
            $target_url = 'https://github.com/opencart/opencart/releases/download/'.VERSION_FULL.'/opencart-'.VERSION_FULL.'.zip';
        } else {
            $target_url = 'https://github.com/opencart/opencart/releases/download/'.VERSION_FULL.'/'.VERSION_FULL.'.zip';
        }

        $file_zip = $path . "/opencart.zip";
        $file_dest = $path . "/";

        $this->download($target_url, $file_dest, $file_zip);
    }
    private function removeFiles()
    {
        // $this->move_dir(DIR_TEMP, DESTINATION);
        // $this->remove_dir(DIR_TEMP);
        unlink(DESTINATION . 'opencart.zip');
        unlink(DESTINATION . 'config-dist.php');
        unlink(DESTINATION . 'admin/config-dist.php');
    }
    private function correctPermissions()
    {
        $dirs = array(
            "system/cache" => 0755,
            "system/logs" => 0755,
            "image" => 0755,
            "image/cache" => 0755,
            "image/data" => 0755,
            "image/catalog" => 0755,
            "storage/logs" => 0755,
            "storage/download" => 0755,
            "storage/upload" => 0755,
            "storage/modification" => 0755,
            "download" => 0755
        );
        foreach ($dirs as $dir => $permissions) {
            if (file_exists(DESTINATION . $dir)) {
                chmod(DESTINATION . $dir, $permissions);
            }
        }
    }
    private function configDefinition()
    {
        if (VERSION < 4000) {
            $output  = '<?php' . "\n";
            $output .= '// HTTP' . "\n";
            $output .= 'define(\'HTTP_SERVER\', \'' . HTTP_OPENCART . '\');' . "\n\n";
            $output .= '// HTTPS' . "\n";
            $output .= 'define(\'HTTPS_SERVER\', \'' . HTTPS_OPENCART . '\');' . "\n\n";
            $output .= '// DIR' . "\n";
            $output .= 'define(\'DIR_APPLICATION\', \'' . DIR_OPENCART . 'catalog/\');' . "\n";
            $output .= 'define(\'DIR_SYSTEM\', \'' . DIR_OPENCART . 'system/\');' . "\n";
            $output .= 'define(\'DIR_DATABASE\', \'' . DIR_OPENCART . 'system/database/\');' . "\n";
            $output .= 'define(\'DIR_LANGUAGE\', \'' . DIR_OPENCART . 'catalog/language/\');' . "\n";
            $output .= 'define(\'DIR_TEMPLATE\', \'' . DIR_OPENCART . 'catalog/view/theme/\');' . "\n";
            $output .= 'define(\'DIR_CONFIG\', \'' . DIR_OPENCART . 'system/config/\');' . "\n";
            $output .= 'define(\'DIR_IMAGE\', \'' . DIR_OPENCART . 'image/\');' . "\n";
            if (VERSION >= 210) {
                $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/storage/download/\');' . "\n";
                $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/storage/cache/\');' . "\n";
                $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/storage/logs/\');' . "\n\n";
                $output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/storage/modification/\');' . "\n";
                $output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/storage/upload/\');' . "\n";
            } elseif (VERSION >= 200) {
                $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/download/\');' . "\n";
                $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/cache/\');' . "\n";
                $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/logs/\');' . "\n\n";
                $output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/modification/\');' . "\n";
                $output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/upload/\');' . "\n";
            } else {
                $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'download/\');' . "\n";
                $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/cache/\');' . "\n";
                $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/logs/\');' . "\n\n";
            }
            if (VERSION >= 301) {
                $output .= 'define(\'DIR_STORAGE\', DIR_SYSTEM . \'storage/\');' . "\n";
                $output .= 'define(\'DIR_SESSION\', \'' . DIR_OPENCART . 'system/storage/session/\');' . "\n\n";
            }
        } else {
            $output = "<?php\n";
            $output .= "define('APPLICATION', 'Catalog');\n";
            $output .= "define('HTTP_SERVER', '" . HTTP_OPENCART . "');\n";
            $output .= "define('DIR_OPENCART', '" . DIR_OPENCART . "');\n";
            $output .= "define('DIR_APPLICATION', DIR_OPENCART . 'catalog/');\n";
            $output .= "define('DIR_EXTENSION', DIR_OPENCART . 'extension/');\n";
            $output .= "define('DIR_IMAGE', DIR_OPENCART . 'image/');\n";
            $output .= "define('DIR_SYSTEM', DIR_OPENCART . 'system/');\n";
            $output .= "define('DIR_STORAGE', DIR_SYSTEM . 'storage/');\n";
            $output .= "define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');\n";
            $output .= "define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');\n";
            $output .= "define('DIR_CONFIG', DIR_SYSTEM . 'config/');\n";
            $output .= "define('DIR_CACHE', DIR_STORAGE . 'cache/');\n";
            $output .= "define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');\n";
            $output .= "define('DIR_LOGS', DIR_STORAGE . 'logs/');\n";
            $output .= "define('DIR_SESSION', DIR_STORAGE . 'session/');\n";
            $output .= "define('DIR_UPLOAD', DIR_STORAGE . 'upload/');\n";
        }

        $output .= '// DB' . "\n";
        $output .= 'define(\'DB_DRIVER\', \'' . addslashes(DB_DRIVER) . '\');' . "\n";
        $output .= 'define(\'DB_HOSTNAME\', \'' . addslashes(DB_HOSTNAME) . '\');' . "\n";
        $output .= 'define(\'DB_USERNAME\', \'' . addslashes(DB_USERNAME) . '\');' . "\n";
        $output .= 'define(\'DB_PASSWORD\', \'' . addslashes(DB_PASSWORD) . '\');' . "\n";
        $output .= 'define(\'DB_DATABASE\', \'' . addslashes(DB_DATABASE) . '\');' . "\n";
        $output .= 'define(\'DB_PORT\', \'' . addslashes(DB_PORT) . '\');' . "\n";
        $output .= 'define(\'DB_PREFIX\', \'' . addslashes(DB_PREFIX) . '\');' . "\n";
        $output .= '?>';
        $file = fopen(DESTINATION . 'config.php', 'wb');
        fwrite($file, $output);
        fclose($file);
        chmod(DESTINATION . "config.php", 0755);
    }
    private function adminConfigDefinition()
    {
        if (VERSION < 4000) {
            $output  = '<?php' . "\n";
            $output .= '// HTTP' . "\n";
            $output .= 'define(\'HTTP_SERVER\', \'' . HTTP_OPENCART . 'admin/\');' . "\n";
            $output .= 'define(\'HTTP_CATALOG\', \'' . HTTP_OPENCART . '\');' . "\n\n";
            $output .= '// HTTPS' . "\n";
            $output .= 'define(\'HTTPS_SERVER\', \'' . HTTPS_OPENCART . 'admin/\');' . "\n";
            $output .= 'define(\'HTTPS_CATALOG\', \'' . HTTPS_OPENCART . '\');' . "\n\n";
            $output .= '// DIR' . "\n";
            $output .= 'define(\'DIR_APPLICATION\', \'' . DIR_OPENCART . 'admin/\');' . "\n";
            $output .= 'define(\'DIR_SYSTEM\', \'' . DIR_OPENCART . 'system/\');' . "\n";
            $output .= 'define(\'DIR_DATABASE\', \'' . DIR_OPENCART . 'system/database/\');' . "\n";
            $output .= 'define(\'DIR_LANGUAGE\', \'' . DIR_OPENCART . 'admin/language/\');' . "\n";
            $output .= 'define(\'DIR_TEMPLATE\', \'' . DIR_OPENCART . 'admin/view/template/\');' . "\n";
            $output .= 'define(\'DIR_CONFIG\', \'' . DIR_OPENCART . 'system/config/\');' . "\n";
            $output .= 'define(\'DIR_IMAGE\', \'' . DIR_OPENCART . 'image/\');' . "\n";
            if (VERSION >= 210) {
                $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/storage/download/\');' . "\n";
                $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/storage/cache/\');' . "\n";
                $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/storage/logs/\');' . "\n\n";
                $output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/storage/modification/\');' . "\n";
                $output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/storage/upload/\');' . "\n";
            } elseif (VERSION >= 200) {
                $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/download/\');' . "\n";
                $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/cache/\');' . "\n";
                $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/logs/\');' . "\n\n";
                $output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/modification/\');' . "\n";
                $output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/upload/\');' . "\n";
            } else {
                $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'download/\');' . "\n";
                $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/cache/\');' . "\n";
                $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/logs/\');' . "\n\n";
            }
            if (VERSION >= 301) {
                $output .= 'define(\'DIR_STORAGE\', DIR_SYSTEM . \'storage/\');' . "\n";
                $output .= 'define(\'DIR_SESSION\', \'' . DIR_OPENCART . 'system/storage/session/\');' . "\n\n";
            }
        } else {
            $output = "<?php\n";
            $output .= "define('APPLICATION', 'Admin');\n";
            $output .= "define('HTTP_SERVER', '" . HTTP_OPENCART . "admin/');\n";
            $output .= "define('HTTP_CATALOG', '" . HTTP_OPENCART . "');\n";
            $output .= "define('DIR_OPENCART', '" . DIR_OPENCART . "');\n";
            $output .= "define('DIR_APPLICATION', DIR_OPENCART . 'admin/');\n";
            $output .= "define('DIR_EXTENSION', DIR_OPENCART . 'extension/');\n";
            $output .= "define('DIR_IMAGE', DIR_OPENCART . 'image/');\n";
            $output .= "define('DIR_SYSTEM', DIR_OPENCART . 'system/');\n";
            $output .= "define('DIR_STORAGE', DIR_SYSTEM . 'storage/');\n";
            $output .= "define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');\n";
            $output .= "define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');\n";
            $output .= "define('DIR_CONFIG', DIR_SYSTEM . 'config/');\n";
            $output .= "define('DIR_CACHE', DIR_STORAGE . 'cache/');\n";
            $output .= "define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');\n";
            $output .= "define('DIR_LOGS', DIR_STORAGE . 'logs/');\n";
            $output .= "define('DIR_SESSION', DIR_STORAGE . 'session/');\n";
            $output .= "define('DIR_UPLOAD', DIR_STORAGE . 'upload/');\n";
        }

        $output .= 'define(\'DIR_CATALOG\', \'' . DIR_OPENCART . 'catalog/\');' . "\n\n";
        $output .= '// DB' . "\n";
        $output .= 'define(\'DB_DRIVER\', \'' . addslashes(DB_DRIVER) . '\');' . "\n";
        $output .= 'define(\'DB_HOSTNAME\', \'' . addslashes(DB_HOSTNAME) . '\');' . "\n";
        $output .= 'define(\'DB_USERNAME\', \'' . addslashes(DB_USERNAME) . '\');' . "\n";
        $output .= 'define(\'DB_PASSWORD\', \'' . addslashes(DB_PASSWORD) . '\');' . "\n";
        $output .= 'define(\'DB_DATABASE\', \'' . addslashes(DB_DATABASE) . '\');' . "\n";
        $output .= 'define(\'DB_PORT\', \'' . addslashes(DB_PORT) . '\');' . "\n";
        $output .= 'define(\'DB_PREFIX\', \'' . addslashes(DB_PREFIX) . '\');' . "\n";
        if (VERSION >= 300) {
            $output .= '// OpenCart API' . "\n";
            $output .= 'define(\'OPENCART_SERVER\', \'https://www.opencart.com/\');' . "\n";
        }
        $output .= '?>';
        $file = fopen(DESTINATION . 'admin/config.php', 'wb');
        fwrite($file, $output);
        fclose($file);
        chmod(DESTINATION . "admin/config.php", 0755);
    }
    private function fillDatabase()
    {
        $data = array(
            'db_host' => DB_HOSTNAME,
            'db_user' => DB_USERNAME,
            'db_password' => DB_PASSWORD,
            'db_name' => DB_DATABASE,
            'db_prefix' => DB_PREFIX,
            'username' => USERNAME,
            'email' => EMAIL,
            'version' => VERSION
        );
        if (VERSION > 153) {
            $salt = substr(md5(uniqid(rand(), true)), 0, 9);
            $data['salt'] = $salt;
            $data['password'] = sha1($salt . sha1($salt . sha1(PASSWORD)));
        }

        if (VERSION < 4000) {
            $this->db->fill_mysql_3($data);
        } else if (VERSION < 4100) {
            $data['password'] = PASSWORD;
            $this->db->fill_mysql_4($data);
        } else {
            $data['password'] = PASSWORD;
            $this->db->fill_mysql_41($data);
        }
    }
    private function installShopunity($target_url)
    {
        $this->install_mbooth($target_url);
        $this->db->query("INSERT INTO " . DB_PREFIX . "extension SET `type` = '" . $this->db->escape('module') . "', `code` = '" . $this->db->escape('shopunity') . "'");
        $this->db->addPermission('1', 'access', 'extension/module/shopunity');
        $this->db->addPermission('1', 'modify', 'extension/module/shopunity');
    }
    // Get data for output
    private function getAllFolders()
    {
        $results = scandir('./');
        $folders = array();
        foreach ($results as $k => $v) {
            $check = False;
            foreach ($this->versions as $version) {
                if ($v == $version['code']) {
                    $check = True;
                }
            }
            if ($check === False) {
                unset($results[$k]);
            }
        }
        foreach ($results as $folder) {
            if (is_dir(dirname(__FILE__) . '/' . $folder) && $folder != '.' && $folder != '..' && $folder != '.git') {
                $sub_results = scandir('./' . $folder);
                $sub_folders = array();
                foreach ($sub_results as $sub_folder) {
                    $git = false;
                    if (is_dir(dirname(__FILE__) . '/' . $folder . '/' . $sub_folder) && $sub_folder != '.' && $sub_folder != '..' && $sub_folder != '.git') {
                        $db_name = '';
                        $link = '';
                        if (file_exists(dirname(__FILE__) . '/' . $folder . '/' . $sub_folder . '/.git')) {
                            $git = $this->get_git_config('url', dirname(__FILE__) . '/' . $folder . '/' . $sub_folder . '/.git/config');
                        }
                        if (file_exists(dirname(__FILE__) . '/' . $folder . '/' . $sub_folder . '/config.php')) {
                            $db_name = $this->get_defined_value('DB_DATABASE', dirname(__FILE__) . '/' . $folder . '/' . $sub_folder . '/config.php');
                            $db_used[] = $db_name;
                            $link = $this->get_defined_value('HTTP_SERVER', dirname(__FILE__) . '/' . $folder . '/' . $sub_folder . '/config.php');
                        }
                        $sub_folders[] = array('name' => $sub_folder, 'link' => $link, 'path' => dirname(__FILE__) . '/' . $folder . '/' . $sub_folder, 'db' => $db_name, 'git' => $git);
                    }
                }
                $folders[$folder] = $sub_folders;
            }
        }

        $this->setUnusedDb($folders);
        return $folders;
    }
    private function setUnusedDb($folders)
    {
        // Get all db
        $db_list = $this->getAllDatabases();
        foreach ($db_list as $key => $db) {
            foreach ($folders as $shops) {
                foreach ($shops as $shop) {
                    if ($shop['db'] === $db) {
                        unset($db_list[$key]);
                    }
                }
            }
        }
        $this->unused_db = $db_list;
    }
    private function getAllDatabases()
    {
        $db_used = array('information_schema', 'mysql', 'performance_schema');
        $db_list = array();
        $result = $this->db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA ");
        foreach ($result->rows as $scheme) {
            if (!in_array($scheme['SCHEMA_NAME'], $db_used)) {
                $db_list[] = $scheme['SCHEMA_NAME'];
            }
        }
        return $db_list;
    }
    // Html
    private function showPage()
    {
        $folders = $this->getAllFolders();
        $db_list = $this->unused_db;
        krsort($folders);
        return '
            <html lang="en">
                <head>
                    <link href="http://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic,700italic&subset=latin,cyrillic-ext" rel="stylesheet" type="text/css"/>
                    <link href="https://dreamvention.github.io/RipeCSS/css/ripe.css" rel="stylesheet">
                    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
                    <title>Store Manager</title>
                </head>
                <body> ' . $this->getBody($folders, $db_list) . ' </body>
            </html>';
    }
    private function getBody($folders, $db_list)
    {
        return '
            <style>
                .preloader-wrap {
                    position: fixed;
                    width: 100%;
                    height: 100%;
                    z-index: 1000;
                }
            </style>
            <div class="preloader-wrap hide">
                <span class="preloader"></span>
            </div>
            <div id="wrapper"> 
                ' . $this->getStyle() . $this->getHeader() . $this->getContent($folders, $db_list) . $this->getFooter() . $this->getScripts() . '
            </div>
        ';
    }
    private function getStyle()
    {
        return '
            <style>
                body {
                    background-color:#F6F9FC;
                }
                .hide {
                    display: none;
                }
            </style>';
    }
    private function getHeader()
    {
        $version_list = '';
        foreach ($this->versions as $version) {
            $version_list .= '<option value="' . $version["code"] . '">' . $version["version"] . '</option>';
        }
        $header = '
        <style>
            .header {
                position:fixed;
                height:90px;
                padding:5px 10px;
                width:100%;
                background: #F6F9FC;
                z-index:1000;
                border-bottom:1px solid #dfe4e8;
            }
            .header .logo {
                display: block;
                padding: 6px;
                float: left;
            }
            .header .logo img {
                display: block;
            }
            .header .form {
                display: block;
                padding: 20px 10px;
                float: left;
            }
            .header .store-link {
                padding: 20px;
                float: left;
            }
            .header .search {
                display: block;
                padding: 15px 10px;
                float: right;
            }
            .icon {
                margin-top: 14px;
            }
            .header .ve-label{
                margin: 0 10px;
            }
        
            .header .ve-checkbox{
                margin-bottom:5px;
            }
        </style>
        <!-- Store Creator -->
        <div class="header">   
            <a class="logo" href="https://shopunity.net">
                <img src="https://shopunity.net/catalog/view/theme/default/image/logo.png">
            </a>
            <form id="form" action="" method="post" class="form">
                <div class="ve-field ve-field--inline">
                    <label for="input_version" class="ve-label">OC version:</label>
                    <select class="ve-input ve-input--lg"  name="version" id="input_version">' . $version_list . '</select>
                    <label class="ve-label" for="store_name"> Codename:</label>
                    <input class="ve-input ve-input--lg"  type="text" id="store_name" name="name"/>
                    <span class="ve-btn ve-btn--success ve-btn--lg" id="submit">Create Store</span>
                    <label class="ve-checkbox ve-label" for="shopunity"><input type="checkbox" checked  class="ve-input" id="shopunity" name="shopunity"><i></i> Install Shopunity</label>
                </div>
            </form>
            <div class="store-link hide">
                <a href="" id="link_to_shop"><span class="ve-btn ve-btn--success ve-btn--lg">Go to Store</span></a>
            </div>
            <div class="search text-right">
                <div>
                    <div class="ve-input-group ve-input-group--hg">
                        <label class="ve-input-group__addon"><i class="icon fas fa-search"></i></label>
                        <input class="ve-input" type="text" placeholder="Search" id="search">
                    </div>
                </div>
            </div>
        </div>';
        return $header;
    }
    private function getContent($folders, $db_list)
    {
        return '
            <style>
                .content{
                    padding: 120px 30px 30px 30px;
                }
                .content .db-div {
                    margin-top: 50px;
                }
            </style>
            <div class="content">
                <div class="row" id="pointerEventsShops">
                    ' . $this->getFolders($folders) . '
                </div>
                <div class="row db-div" id="pointerEventsDb">
                    ' . $this->getDatabases($db_list) . '
                </div>
            </div>';
    }
    private function getFolders($folders)
    {
        $output = '';

        foreach ($folders as $version => $shops) {
            if (!$shops == null) {
                $output .= '
                    <div class="ve-col-3">
                        <div class="ve-card stores-for-search">
                            <div class="ve-card__header">
                                <h2 class="ve-h3">' . $version . '</h2>
                            </div>
                            <div class="ve-list ve-list--borderless">
                                ' . $this->getShops($shops) . '
                            </div>
                        </div>
                    </div>';
            }
        }
        return $output;
    }
    private function getShops($shops)
    {
        $output = '
            <style>
                .span-git {
                    padding-left: 0;
                    flex: 0;
                }
                .span-db {
                    margin-top: 5px; 
                    display: inline-block
                }
            </style>';
        foreach ($shops as $shop) {
            // Git
            if ($shop["git"]) {
                $a_git = ' <a href="' . $shop["git"] . '" target="_blank" class="ve-btn ve-btn--default ve-btn--sm" title="' . $shop["git"] . '">Git</a>';
            } else {
                $a_git = '';
            }
            // Shop link
            if (!$shop["db"]) {
                $shop_link_class = 'class="not-working"';
            } else {
                $shop_link_class = "";
            }
            // Delete Store button
            $delete_but = '<a onclick="deleteStore()" class="delete delete-store ve-btn ve-btn--danger ve-btn--sm" data-store="' . $shop["path"] . '" data-database="' . $shop["db"] . '">X</a>';
            // Database
            $database = $shop['db'];
            if (empty($shop["db"])) {
                $database = 'Db: none';
            }
            // Output
            $output .= '
                <div class="ve-list__item">
                    <div>
                        <span class="span-git">' . $a_git . '</span>
                        <div class="text-left">
                        <a href="' . $shop["link"] . '"  target="_blank" title="' . $shop["db"] . '">' . $shop['name'] . '</a> &nbsp
                        (<a href="' . $shop["link"] . 'admin" target="_blank" title="' . $shop["db"] . '">admin</a>)<br/>
                            <span class="small span-db">' . $database . '</span>
                        </div>
                        <span class="text-right">' . $delete_but . '</span>
                    </div>
                </div>';
        }
        return $output;
    }
    private function getDatabases($db_list)
    {
        if (empty($db_list)) {
            return '';
        }
        return '<!-- Databases -->
            <style>
               .ve-card__section {
                    margin-top: 20px;
               }
                .db-div div ol {
                    columns: 4;
                }
            </style>
            <div class="ve-col-12" >
                <div class="ve-card">
                    <div class="ve-card__header">
                        <h2 class="ve-h2">Unused databases</h2>
                    </div>
                    <hr class="ve-hr"/>
                    <div class="ve-card__section">
                        <div class="ve-col-12">
                            <ol id="ol_db">' . $this->getDb($db_list) . '</ol>
                        </div>
                    </div>
                </div>
            </div>';
    }
    private function getDb($db_list)
    {
        $output = '
            <style>
                .li-db {
                    margin-bottom: 10px; 
                    padding-right:40px;
                }
                .li-db a span {
                    font-size: 10px;
                }
            </style>
        ';
        $class = 'item_for_search';
        foreach ($db_list as $db) {
            $output .= '
                <li class="li-db ' . $class . '">
                    <div class="row">
                        <span class="ve-col-10">' . $db . '</span>
                        <span class="ve-col-2">
                            <a onclick="deleteDb()" class="ve-btn ve-btn--danger delete delete-database" data-database="' . $db . '">
                                <span>X</span>
                            </a>
                        </span>
                    </div>
                </li>';
        }
        return $output;
    }
    private function getFooter()
    {
        $footer = '
            <style>
                .link-shopunity:hover {
                    text-decoration: none;
                }
                .footer {
                    margin: 0 auto; 
                    color: #929292; 
                    text-align: center;
                }
                .footer .version {
                    margin: 10px 0 5px 0;
                }
                .footer .version span {
                    margin-left: 5px;
                }
                .footer .powered {
                    margin-bottom: 20px;
                }
            </style>
            <div class="ve-col-11 footer">
                <hr class="ve-hr">
                <div class="version">Opencart Installer version:  
                    <span>' . $this->OIversion . '</span>
                </div>
                <div class="powered">
                    Powered by <a class="link-shopunity" href="https://shopunity.net">Shopunity.net</a>
                </div>
            </div>';
        return $footer;
    }
    private function getScripts()
    {
        $url = 'http://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/.\\") . '/';
        return '
        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script defer src="https://use.fontawesome.com/releases/v5.7.2/js/all.js" integrity="sha384-0pzryjIRos8mFBWMzSSZApWtPl/5++eIfzYmTgBBmXYdhvxPc+XcFEk+zJwDgWbP" crossorigin="anonymous"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/fuse.js/3.4.2/fuse.min.js"></script>
        <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/spin.js/2.3.2/spin.min.js"></script>
        <script type="text/javascript" src="//igorescobar.github.io/jQuery-Mask-Plugin/js/jquery.mask.min.js"></script>
        <script type="text/javascript"> 
            (function($) {
                $.fn.spin = function(opts, color) {
                    var presets = {
                        "tiny": { lines: 8, length: 2, width: 2, radius: 3 },
                        "small": { lines: 8, length: 4, width: 3, radius: 5 },
                        "large": { lines: 10, length: 8, width: 4, radius: 8 }
                    };
                    if (Spinner) {
                        return this.each(function() {
                                var $this = $(this),
                                data = $this.data();
    
                            if (data.spinner) {
                                data.spinner.stop();
                                delete data.spinner;
                            }
                            if (opts !== false) {
                                if (typeof opts === "string") {
                                    if (opts in presets) {
                                        opts = presets[opts];
                                    } else {
                                        opts = {};
                                    }
                                    if (color) {
                                        opts.color = color;
                                    }
                                }
                                data.spinner = new Spinner($.extend({color: $this.css("color")}, opts)).spin(this);
                            }
                        });
                    } else {
                        throw "Spinner class not available.";
                    }
                };
            })(jQuery);
        </script>
        <script type="text/javascript">
            $(document).ready(function(){
                $(".preloader").spin({ color:"black"});
                $("#store_name").mask("d_AAAAAAAAAAAAAAAAAAAA", {"translation": {
                    A: {pattern: /[\_A-Za-z0-9]/}
                }
                });
            });
            
            ' . $this->getSubmitScript($url) . '
            
            function deleteDb() {
                var delete_database = confirm("Are you sure, you want to delete this database?");
                if (delete_database === true) {
    
                    $.ajax({
                        url: "http://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), " /.\\") . '/index.php",
                        type: "post",
                        data: "delete_database=" + event.target.parentNode.children[0].getAttribute("data-database"),
                        dataType: "html",
                        beforeSend: function() {
                            $(".preloader-wrap").removeClass("hide");
                        },
                        complete: function() {
                            $(".preloader-wrap").addClass("hide");
                        },
                        success: function(html) {
                            location.reload(true);
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                        }
                    });
                }
            }
            
            function deleteStore() {
                var delete_store = confirm("Are you sure, you want to delete this store?");
                if (delete_store === true) {
                    $.ajax({
                        url: "http://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/.\\") . '/index.php",
                        type: "post",
                        data: "delete_store=" + event.target.parentNode.children[0].getAttribute("data-store") + 
                            "&delete_database=" + event.target.parentNode.children[0].getAttribute("data-database"),
                        dataType: "html",
                        beforeSend: function() {
                            $(".preloader-wrap").removeClass("hide");
                        },
                        complete: function() {
                            $(".preloader-wrap").addClass("hide");
                        },
                        success: function(html) {
                        console.log(html);
                            location.reload(true);
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                        }
                    });
                }
            }
        </script>
        
        <!-- Search -->
        <script>
            $(document).ready(function(){ 
                var stores = "";
                sessionStorage.removeItem("db");
                $(".search").keyup(function(){
                    const value = $("#search").val();
                    
                    if (stores === "") {
                        stores = getStores();
                    }
                    searchStores(value, stores);
                    searchDatabases(value);
                    
                });
            });
            
            function getStores() {
                var items = $(".stores-for-search");
                let data = {};
                for (let i = 0; i < items.length; i++) {
                    let version = items[i].children[0].children[0].innerText;
                    data[version] = {}
                  
                    for (let r = 1; r < items[i].children[1].children.length; r++) {
                        let name = items[i].children[1].children[r].children[0].children[1].children[0].innerText;
                        let db = items[i].children[1].children[r].children[0].children[1].children[2].innerText;
                        let link = items[i].children[1].children[r].children[0].children[1].children[0].href;
                        let path = items[i].children[1].children[r].children[0].children[2].children[0].getAttribute("data-store");
                        data[version][r-1] = {
                            name: name,
                            db: db,
                            link: link,
                            path: path,
                        }
                    }
                }
                
                return data;
            }
            
            function searchStores(value, stores) {
                const div = $("#pointerEventsShops");
                div.html("")
                
                if (value === "") {
                    var result = stores
                } else {
                    let names = [];
                    let i = 0
                    for (let version in stores) {
                        for (let key in stores[version]) {
                            names[i] = stores[version][key].name;
                            i++;
                        }
                    }
                    
                    names.reverse();
                    
                    var options = {
                            shouldSort: true,
                            threshold: 0.6,
                            location: 0,
                            distance: 100,
                            maxPatternLength: 32,
                            minMatchCharLength: 1,
                            keys: [
                                "title",
                                "author.firstName"
                            ]
                        };
                    var fuse = new Fuse(names, options);
                    result = fuse.search(value);
                    
                    var mas_name = [];
                    for (let i = 0; i < result.length; i++) {
                        mas_name.push(names[result[i]])
                    }
                    mas_name.sort();
                    mas_name = $.unique(mas_name)
                    
                    var result = {}
                    var k = 0;
                    for (let i = 0; i < mas_name.length; i++ ) {
                        for (let version in stores) {
                            for (let key in stores[version]) {
                                if (mas_name[i] === stores[version][key].name) {
                                    if (!result[version]) {
                                        result[version] = {}
                                    }
                                    result[version][k] = stores[version][key]
                                    k++;
                                }
                            }
                        }
                    }
                }
                
                var versions = Object.keys(result);
                versions.reverse();
                
                for (let t = 0; t < versions.length; t++) {
                    
                    var keys = Object.keys(result[versions[t]]);
                    var stores = "";
                    for (let l = 0; l < keys.length; l++) {
                        let link = result[versions[t]][keys[l]].link
                        stores += `<div class="ve-list__item">
                                <div>
                                    <span class="span-git"></span>
                                    <div class="text-left">
                                        <a href="` + link + `" 
                                            title="` + result[versions[t]][keys[l]].db + `">` + result[versions[t]][keys[l]].name + `  
                                        </a><br/>
                                        <span class="small span-db">` + result[versions[t]][keys[l]].db + `</span>
                                    </div>
                                    <span class="text-right">
                                        <a class="delete delete-store ve-btn ve-btn--danger ve-btn--sm" 
                                            data-store="` + result[versions[t]][keys[l]].path + `"  onclick="deleteStore()"
                                            data-database="` + result[versions[t]][keys[l]].db + `">X</a>
                                    </span>
                                </div>
                            </div>`
                    } 
                    div.append(`<style>
                                .span-git {
                                    padding-left: 0;
                                    flex: 0;
                                }
                                .span-db {
                                    margin-top: 5px; 
                                    display: inline-block
                                }
                            </style>
                    <div class="ve-col-3">
                        <div class="ve-card stores-for-search">
                            <div class="ve-card__header">
                                <h2 class="ve-h3">` + versions[t] + `</h2>
                            </div>
                            <div class="ve-list ve-list--borderless">` + stores + `</div>
                        </div>
                    </div>`)
                }
                
                
            }
            
            function searchDatabases(value) {
                const ol_db = $("#ol_db")
                
                if (!sessionStorage.getItem("db")) {
                    var items = $(".item_for_search");
                    var name = [];
                    for (let i = 0; i < items.length; i++) {
                        name[i] = items[i].children[0].children[0].innerText
                    }
                    sessionStorage.setItem("db", name);
                }
                
                ol_db.html("");
                
                const data = sessionStorage.getItem("db").split(",");
                
                var result = [];
                if (value === "") {
                    for (let i = 0; i < data.length; i++) {
                        result[i] = i 
                    }
                } else {
                    var options = {
                        shouldSort: true,
                        threshold: 0.6,
                        location: 0,
                        distance: 100,
                        maxPatternLength: 32,
                        minMatchCharLength: 1,
                        keys: [
                            "title",
                            "author.firstName"
                        ]
                    };
                    var fuse = new Fuse(data, options);
                    result = fuse.search(value);
                }
                
                for (let i = 0; i < result.length; i++) {
                    ol_db.append(`<style>
                            .li-db {
                                margin-bottom: 10px; 
                                padding-right:40px;
                            }
                            .li-db a span {
                                font-size: 10px;
                            }
                        </style>
                        <li class="li-db">
                            <div class="row">
                                <span class="ve-col-10">` + data[result[i]] + `</span>
                                <span class="ve-col-2">
                                    <a onclick="deleteDb()" class="ve-btn ve-btn--danger delete delete-database" data-database=` + data[result[i]] + `>
                                        <span>X</span>
                                    </a>
                                </span>
                            </div>
                        </li>`)
                }
            }
            
        </script>
        ';
    }
    private function getSubmitScript($url)
    {
        $script = '';
        if (DEBUG == 1) {
            $script .= '$("#submit").on("click",function(){
                            window.location.href = "' . $url . 'index.php?name=" + $("#store_name").val() + "&version=" + $("#input_version").val()
                        });';
        } else {
            $script .= '
                $("#submit").on("click",function(){
                    if ($("#store_name")[0].value !== "") {
                    
                        $("#form").fadeToggle("slow");
                        
                        $.ajax({
                            url: "' . $url . 'index.php",
                            type: "post",
                            data: $("#form").serialize(),
                            dataType: "html",
                            beforeSend: function() {
                                $(".preloader-wrap").removeClass("hide");
                                $("#wrapper").fadeTo("slow", "0.3");
                                $("#pointerEventsShops").css("pointerEvents", "none");
                                $("#pointerEventsDb").css("pointerEvents", "none");
                            },
                            complete: function() {
                                $(".preloader-wrap").addClass("hide");
                                $("#wrapper").fadeTo("slow", "1");
                                $("pointerEventsShops").css("pointerEvents", "auto");
                                $("pointerEventsDb").css("pointerEvents", "auto");
                            },
                            success: function(html) {
                                $("#link_to_shop").attr("href", "' . $url . '"+$("#input_version").val() + "/"+$("#store_name").val());
                                    $(".store-link").fadeToggle("slow");
                                },
                                error: function(xhr, ajaxOptions, thrownError) {
                                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                            }
                        });
                    }
                });';
        }
        return $script;
    }
    // Connecting to Database
    private function connectMysqli()
    {
        define('DB_DRIVER', 'mysqli');
        $this->db = new DBMySQLi(DB_HOSTNAME,  DB_USERNAME, DB_PASSWORD);
    }
    // Functions
    private function get_git_config($defined, $file)
    {
        $txt_file    = file_get_contents($file);
        $rows        = explode("\n", $txt_file);
        array_shift($rows);
        foreach ($rows as $row => $data) {
            if (strpos($data, $defined)) {
                $row_data = explode(" ", $data);
                return $row_data[2];
            }
        }
        return false;
    }

    private function get_defined_value($defined, $file)
    {
        $txt_file    = file_get_contents($file);
        $rows        = explode("\n", $txt_file);
        array_shift($rows);
        foreach ($rows as $row => $data) {
            if (strpos($data, $defined)) {
                $row_data = explode("'", $data);
                return $row_data[3];
            }
        }
        return false;
    }
    private function install_mbooth($target_url)
    {
        $file_zip = DESTINATION . "arhive.zip";
        $this->download($target_url, DESTINATION, $file_zip);
    }
    private function download($target_url, $file_dest, $file_zip)
    {
        $userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
        $ch = curl_init();
        $fp = fopen($file_zip, "w+");
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FILE, $fp);

        $page = curl_exec($ch);
        if (!$page) {
            exit;
        }

        curl_close($ch);
        fclose($fp);
        $zip = new \ZipArchive();

        if (!$zip) {
            exit;
        }
        
        if ($zip->open($file_zip) !== true) {
            exit;
        }
        $zip->extractTo(DIR_TEMP);
        $zip->close();

        $files = $this->read_dir(DIR_TEMP);
        
        if ($files) {
            foreach ($files as $file) {
                $source = substr($file, strlen(DIR_TEMP));
                $destination = str_replace('\\', '/', $source);
    
                $path_new = '';
                $directories = explode('/', dirname($destination));
                if ((isset($directories[0]) && $directories[0] == 'upload') || (isset($directories[1]) && $directories[1] == 'upload')) {
                    
                    if (isset($directories[1]) && $directories[1] == 'upload') {
                        unset($directories[0]);
                        unset($directories[1]);
                    } else {
                        unset($directories[0]);
                    }
                } else {
                    continue;
                }
    
                foreach ($directories as $directory) {
                    if (!$path_new) {
                        $path_new = $directory . '/';
                    } else {
                        $path_new = $path_new . $directory . '/';
                    }
                    if (!is_dir($file_dest . '/' . $path_new)){
                        mkdir($file_dest . '/' . $path_new . '/', 0777);
                    }
                }

                
                
                if (is_file($file) && !is_file($file_dest . $path_new . basename($destination))) copy($file, $file_dest . $path_new . basename($destination));
            }
        }
        $this->remove_dir(DIR_TEMP);
        unlink($file_zip);
    }

    private function read_dir($dir) {
        $result = array();
        foreach(scandir($dir) as $filename) {
            if ($filename[0] === '.' || $filename[0] === '..') continue;
            $filePath = $dir . $filename;
            if (is_dir($filePath)) {
                $filePath .= '/';
                foreach ($this->read_dir($filePath) as $childFilename) {
                    $result[] = $childFilename;
                }
            }
            $result[] = $dir . $filename;
        }
        return $result;
    }

    private function move_dir($source, $dest)
    {
        $files = scandir($source);
        foreach ($files as $file) {

            if ($file == '.' || $file == '..' || $file == '.DS_Store') continue;

            if (is_dir($source . $file)) {
                if (!file_exists($dest . $file . '/')) {
                    mkdir($dest . $file . '/', 0777, true);
                }
                $this->move_dir($source . $file . '/', $dest . $file . '/');
            } elseif (!rename($source . $file, $dest . $file)) {
            }
        }
    }

    private function remove_dir($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->remove_dir($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
}
final class DBMySQLi
{
    private $link;
    public function __construct($hostname, $username, $password)
    {
        $this->link = new mysqli($hostname, $username, $password);
        if ($this->link->connect_error) {
            trigger_error('Error: Could not make a database link (' . $this->link->connect_errno . ') ' . $this->link->connect_error);
        }
        $this->link->set_charset("utf8");
        $this->link->query("SET SQL_MODE = ''");
    }
    public function create($database)
    {
        if (!$this->link->query('CREATE DATABASE ' . $database)) {
            trigger_error('Error creating database: ' . $database);
        }
    }
    public function connect($database)
    {
        if (!$this->link->select_db($database)) {
            trigger_error('Error: Could not connect to database ' . $database);
        }
        $this->link->query("SET NAMES 'utf8'");
        $this->link->query("SET CHARACTER SET utf8");
        $this->link->query("SET CHARACTER_SET_CONNECTION=utf8");
        $this->link->query("SET SQL_MODE = ''");
    }
    public function query($sql)
    {
        $query = $this->link->query($sql);
        if (!$this->link->errno) {
            if ($query instanceof mysqli_result) {
                $data = array();
                while ($row = $query->fetch_assoc()) {
                    $data[] = $row;
                }
                $result = new stdClass();
                $result->num_rows = $query->num_rows;
                $result->row = isset($data[0]) ? $data[0] : array();
                $result->rows = $data;
                $query->close();
                return $result;
            } else {
                return true;
            }
        } else {
            trigger_error('Error: ' . $this->link->error  . '<br />Error No: ' . $this->link->errno . '<br />' . $sql);
        }
    }
    public function escape($value)
    {
        return $this->link->real_escape_string($value);
    }
    public function countAffected()
    {
        return $this->link->affected_rows;
    }
    public function getLastId()
    {
        return $this->link->insert_id;
    }
    public function __destruct()
    {
        $this->link->close();
    }
    public function fill_mysql_3($data)
    {
        $this->connect($data['db_name']);
        $file = DIR_OPENCART . 'install/opencart.sql';
        if (!file_exists($file)) {
            exit('Could not load sql file: ' . $file);
        }
        $lines = file($file);
        if ($lines) {
            $sql = '';
            foreach ($lines as $line) {
                if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
                    $sql .= $line;
                    if (preg_match('/;\s*$/', $line)) {
                        $sql = str_replace("DROP TABLE IF EXISTS `oc_", "DROP TABLE IF EXISTS `" . $data['db_prefix'], $sql);
                        $sql = str_replace("CREATE TABLE `oc_", "CREATE TABLE `" . $data['db_prefix'], $sql);
                        $sql = str_replace("INSERT INTO `oc_", "INSERT INTO `" . $data['db_prefix'], $sql);
                        $this->query($sql);
                        $sql = '';
                    }
                }
            }
            $this->query("SET CHARACTER SET utf8");
            $this->query("SET @@session.sql_mode = 'MYSQL40'");
            $this->query("DELETE FROM `" . $data['db_prefix'] . "user` WHERE user_id = '1'");
            $this->query("INSERT INTO `" . $data['db_prefix'] . "user` SET user_id = '1', user_group_id = '1', username = '" . $this->escape($data['username']) . "', salt = '" . $this->escape($data['salt']) . "', password = '" . $this->escape($data['password']) . "', status = '1', email = '" . $this->escape($data['email']) . "', date_added = NOW()");
            $this->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_email'");
            $this->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_url'");
            $this->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_encryption'");
            if ($data['version'] < 201) {
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `group` = 'config', `key` = 'config_email', value = '" . $this->escape($data['email']) . "'");
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `group` = 'config', `key` = 'config_url', value = '" . $this->escape(HTTP_OPENCART) . "'");
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `group` = 'config', `key` = 'config_encryption', value = '" . $this->escape(md5(mt_rand())) . "'");
            } else {
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', value = '" . $this->escape($data['email']) . "'");
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_url', value = '" . $this->escape(HTTP_OPENCART) . "'");
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_encryption', value = '" . $this->escape(md5(mt_rand())) . "'");
            }
            $this->query("UPDATE `" . $data['db_prefix'] . "product` SET `viewed` = '0'");
        }
    }
    public function addPermission($user_group_id, $type, $route)
    {
        $user_group_query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "user_group WHERE user_group_id = '" . (int)$user_group_id . "'");
        if ($user_group_query->num_rows) {
            if (VERSION >= 210) {
                $data = json_decode($user_group_query->row['permission'], true);
            } else {
                $data = unserialize($user_group_query->row['permission']);
            }
            $data[$type][] = $route;
            if (VERSION >= 210) {
                $data = json_encode($data);
            } else {
                $data = serialize($data);
            }
            $this->query("UPDATE " . DB_PREFIX . "user_group SET permission = '" . $this->escape($data) . "' WHERE user_group_id = '" . (int)$user_group_id . "'");
        }
    }

    public function fill_mysql_4($data)
    {
        $file = DIR_OPENCART . 'system/helper/db_schema.php';
        if (!file_exists($file)) {
            exit('Could not load file: ' . $file);
        }
        require($file);
        if (VERSION > 4011) {
            $tables = oc_db_schema();
        } else {
            $tables = VERSION > 4000 ? Opencart\System\Helper\DbSchema\db_schema() : db_schema();
        }
        

        include DIR_OPENCART . 'system/library/db.php';
        include DIR_OPENCART . 'system/library/db/mysqli.php';
        include DIR_OPENCART . 'system/helper/general.php';

        $db = new \Opencart\System\Library\DB(
            DB_DRIVER,
            html_entity_decode(DB_HOSTNAME, ENT_QUOTES, 'UTF-8'),
            html_entity_decode(DB_USERNAME, ENT_QUOTES, 'UTF-8'),
            html_entity_decode(DB_PASSWORD, ENT_QUOTES, 'UTF-8'),
            html_entity_decode(DB_DATABASE, ENT_QUOTES, 'UTF-8'),
            DB_PORT
        );
        
    
        foreach ($tables as $table) {
            $table_query = $db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $data['db_name'] . "' AND TABLE_NAME = '" . $data['db_prefix'] . $table['name'] . "'");

            if ($table_query->num_rows) {
                $db->query("DROP TABLE `" . $data['db_prefix'] . $table['name'] . "`");
            }

            $sql = "CREATE TABLE `" . $data['db_prefix'] . $table['name'] . "` (" . "\n";

            foreach ($table['field'] as $field) {
                $sql .= "  `" . $field['name'] . "` " . $field['type'] . (!empty($field['not_null']) ? " NOT NULL" : "") . (isset($field['default']) ? " DEFAULT '" . $db->escape($field['default']) . "'" : "") . (!empty($field['auto_increment']) ? " AUTO_INCREMENT" : "") . ",\n";
            }

            if (isset($table['primary'])) {
                $primary_data = array();

                foreach ($table['primary'] as $primary) {
                    $primary_data[] = "`" . $primary . "`";
                }

                $sql .= "  PRIMARY KEY (" . implode(",", $primary_data) . "),\n";
            }

            if (isset($table['index'])) {
                foreach ($table['index'] as $index) {
                    $index_data = array();

                    foreach ($index['key'] as $key) {
                        $index_data[] = "`" . $key . "`";
                    }

                    $sql .= "  KEY `" . $index['name'] . "` (" . implode(",", $index_data) . "),\n";
                }
            }

            $sql = rtrim($sql, ",\n") . "\n";
            $sql .= ") ENGINE=" . $table['engine'] . " CHARSET=" . $table['charset'] . " ROW_FORMAT=DYNAMIC COLLATE=" . $table['collate'] . ";\n";

            $db->query($sql);
        }

        // Data
        $lines = file(DIR_OPENCART . '/install/opencart.sql', FILE_IGNORE_NEW_LINES);

        if ($lines) {
            $sql = '';

            $start = false;

            foreach ($lines as $line) {
                if (substr($line, 0, 12) == 'INSERT INTO ') {
                    $sql = '';

                    $start = true;
                }

                if ($start) {
                    $sql .= $line;
                }

                if (substr($line, -2) == ');') {
                    $db->query(str_replace("INSERT INTO `oc_", "INSERT INTO `" . $data['db_prefix'], $sql));

                    $start = false;
                }
            }
        }
        if (VERSION > 4011) {
            $token = $db->escape(oc_token(512));
        } else {
            $token = VERSION > 4000 ? $db->escape(Opencart\System\Helper\General\token(512)) : $db->escape(token(512));
        }
        
        $db->query("SET CHARACTER SET utf8mb4");
               
		$db->query("DELETE FROM `" . $data['db_prefix'] . "user` WHERE `user_id` = '1'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET `user_id` = '1', `user_group_id` = '1', `username` = '" . $db->escape($data['username']) . "', `password` = '" . $db->escape(password_hash(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT)) . "', `firstname` = 'John', `lastname` = 'Doe', `email` = '" . $db->escape($data['email']) . "', `status` = '1', `date_added` = NOW()");
   
        $db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_email'");
        $db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', `value` = '" . $db->escape($data['email']) . "'");
        $db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_encryption'");
        $db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_encryption', `value` = '" . $token . "'");

        $db->query("INSERT INTO `" . $data['db_prefix'] . "api` SET `username` = 'Default', `key` = '" . $token . "', `status` = '1', `date_added` = NOW(), `date_modified` = NOW()");

        $api_id = $db->getLastId();

        $db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_api_id'");
        $db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_api_id', `value` = '" . (int)$api_id . "'");

        // set the current years prefix
        $db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `value` = 'INV-" . date('Y') . "-00' WHERE `key` = 'config_invoice_prefix'");
    }

    public function fill_mysql_41($data)
    {
        $file = DIR_OPENCART . 'system/helper/db_schema.php';
        if (!file_exists($file)) {
            exit('Could not load file: ' . $file);
        }
        require($file);
        $tables = oc_db_schema();
        

        include DIR_OPENCART . 'system/library/db.php';
        include DIR_OPENCART . 'system/library/db/mysqli.php';
        include DIR_OPENCART . 'system/helper/general.php';

        $db = new \Opencart\System\Library\DB(
            DB_DRIVER,
            html_entity_decode(DB_HOSTNAME, ENT_QUOTES, 'UTF-8'),
            html_entity_decode(DB_USERNAME, ENT_QUOTES, 'UTF-8'),
            html_entity_decode(DB_PASSWORD, ENT_QUOTES, 'UTF-8'),
            html_entity_decode(DB_DATABASE, ENT_QUOTES, 'UTF-8'),
            DB_PORT
        );
        
    
        foreach ($tables as $table) {
            $table_query = $db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $data['db_name'] . "' AND TABLE_NAME = '" . $data['db_prefix'] . $table['name'] . "'");

            if ($table_query->num_rows) {
                $db->query("DROP TABLE `" . $data['db_prefix'] . $table['name'] . "`");
            }

            $sql = "CREATE TABLE `" . $data['db_prefix'] . $table['name'] . "` (" . "\n";

            foreach ($table['field'] as $field) {
                $sql .= "  `" . $field['name'] . "` " . $field['type'] . (!empty($field['not_null']) ? " NOT NULL" : "") . (isset($field['default']) ? " DEFAULT '" . $db->escape($field['default']) . "'" : "") . (!empty($field['auto_increment']) ? " AUTO_INCREMENT" : "") . ",\n";
            }

            if (isset($table['primary'])) {
                $primary_data = [];

                foreach ($table['primary'] as $primary) {
                    $primary_data[] = "`" . $primary . "`";
                }

                $sql .= "  PRIMARY KEY (" . implode(",", $primary_data) . "),\n";
            }

            if (isset($table['index'])) {
                foreach ($table['index'] as $index) {
                    $index_data = [];

                    foreach ($index['key'] as $key) {
                        $index_data[] = "`" . $key . "`";
                    }

                    $sql .= "  KEY `" . $index['name'] . "` (" . implode(",", $index_data) . "),\n";
                }
            }

            $sql = rtrim($sql, ",\n") . "\n";
            $sql .= ") ENGINE=" . $table['engine'] . " CHARSET=" . $table['charset'] . " COLLATE=" . $table['collate'] . ";\n";

            $db->query($sql);
        }

        // Data
		$lines = file(DIR_OPENCART . '/install/opencart-en-gb.sql', FILE_IGNORE_NEW_LINES);

		if ($lines) {
			$sql = '';

			$start = false;

			foreach ($lines as $line) {
				if (substr($line, 0, 12) == 'INSERT INTO ') {
					$sql = '';

					$start = true;
				}

				if ($start) {
					$sql .= $line;
				}

				if (substr($line, -2) == ');') {
					$db->query(str_replace("INSERT INTO `oc_", "INSERT INTO `" . $data['db_prefix'], $sql));

					$start = false;
				}
			}
		}

		$db->query("SET CHARACTER SET utf8mb4");

		$db->query("SET @@session.sql_mode = ''");

		$db->query("DELETE FROM `" . $data['db_prefix'] . "user` WHERE `user_id` = '1'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "user` SET `user_id` = '1', `user_group_id` = '1', `username` = '" . $db->escape($data['username']) . "', `password` = '" . $db->escape(password_hash(html_entity_decode($data['password'], ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT)) . "', `firstname` = 'John', `lastname` = 'Doe', `email` = '" . $db->escape($data['email']) . "', `status` = '1', `date_added` = NOW()");

		$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_language_catalog', `value` = '" . $db->escape('en-gb') . "' WHERE `key` = 'config_language_catalog'");
		$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_language_admin', `value` = '" . $db->escape('en-gb') . "' WHERE `key` = 'config_language_admin'");

		$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', `value` = '" . $db->escape($data['email']) . "' WHERE `key` = 'config_email'");

		$db->query("INSERT INTO `" . $data['db_prefix'] . "api` SET `username` = 'Default', `key` = '" . $db->escape(oc_token(256)) . "', `status` = '1', `date_added` = NOW(), `date_modified` = NOW()");

		$api_id = $db->getLastId();

		$db->query("DELETE FROM `" . $data['db_prefix'] . "setting` WHERE `key` = 'config_api_id'");
		$db->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_api_id', `value` = '" . (int)$api_id . "'");

		// Set the current years prefix
		$db->query("UPDATE `" . $data['db_prefix'] . "setting` SET `value` = 'INV-" . date('Y') . "-00' WHERE `key` = 'config_invoice_prefix'");
    }
}
