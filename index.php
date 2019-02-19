<?php

$opencartInstaller = new OpencartInstaller();

class OpencartInstaller {

    private $OIversion = '1.0.0';

    private $versions = array(
        array(
            'code' => '303',
            'version' => '3.0.3.1',
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


    public function __construct() {
        if (is_file('config.php')) {
            require_once('config.php');
        }

        ini_set('max_execution_time', 960);

        $this->connectMysqli();

        if (!empty($_POST) || !empty($_GET)) {
            $this->queryDefinition();
        }

        echo $this->showPage();

    }

    private function queryDefinition() {
        if (isset($_POST['name']) || isset($_GET['name'])) {

            if (!isset($_POST['shopunity'])) {
                $this->installShopunity = false;
            }

            if(DEBUG == '1'){
                $name = $_GET['name'];
                $version = $_GET['version'];

            }else{
                $name = $_POST['name'];
                $version = $_POST['version'];

            }

            $this->createStore($name, $version);

        } elseif (isset($_POST['delete_store'])) {

            $this->deleteStore($_POST['delete_store']);

        } elseif (isset($_POST['delete_database'])) {

            $this->deleteDatabase($_POST['delete_database']);

        } else {
            exit('ERROR');
        }
    }

    // Delete
    private function deleteStore($store) {
        $this->remove_dir($store);
        if(isset($store) && $store != ''){
            $this->db->query("DROP DATABASE ".$store);
            echo 'Table database '.$store;
        }
    }

    private function deleteDatabase($database) {
        $this->db->query("DROP DATABASE ".$database);
        echo 'Table database '.$database;
    }


    // Creating store
    private function createStore($name, $version) {
        $key = array_search($version, array_column($this->versions, 'code'));

        define('VERSION', $version);
        define('VERSION_FULL', $this->versions[$key]['version']);

        $name = str_replace(" ","_",$name);
        $parts = explode('_', $name);
        unset($parts[0]);

        $path = VERSION.'/'.$name;

        // HTTP
        define('HTTP_SERVER', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/.\\') . '/' . $path . '/');
        define('HTTP_OPENCART', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/.\\'). '/' .$path . '/');
        define('HTTPS_OPENCART', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/.\\'). '/' .$path . '/');

        $realpath = implode('/', explode('\\', realpath(dirname(__FILE__))));
        // DIR
        define('DIR_OPENCART', str_replace('\'', '/', $realpath) . '/'.$path.'/');
        define('DIR_SOURCE', str_replace('\'', '/', $realpath) . '/'.$path.'/upload/');
        define('DESTINATION', $path.'/');
        define('SOURCE', $path.'/upload/');

        // create database
        $this->createDatabase();

        // create a directory
        if (!file_exists('./'.$path.'/')) {
            mkdir('./'.$path .'/', 0755, true);
        }

        // upload files
        $this->uploadFiles($path);

        // move files
        $this->removeFiles();

        // give correct permissions
        $this->correctPermissions();

        // config.php definition
        $this->configDefinition();

        // admin config.php definition
        $this->adminConfigDefinition();

        //fill database
        $this->fillDatabase();

        // install shopunity
        if ($this->installShopunity) {
            $download = json_decode(file_get_contents(HTTP_API."extensions/d_shopunity/download?store_version=".VERSION_FULL),true);
            $target_url =$download['download'];
            $this->installShopunity($target_url);
        }

        echo 'all created';
    }

    private function createDatabase() {
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

    private function uploadFiles($path) {
        if (!extension_loaded('zip')) {
            dl('zip.so');
        }

        $download = json_decode(file_get_contents(HTTP_API."extensions/opencart/download?store_version=".VERSION_FULL),true);
        $target_url =$download['download'];

        $file_zip = $path."/opencart.zip";
        $file_dest = $path."/";

        $this->download($target_url, $file_dest, $file_zip);
    }

    private function removeFiles() {
        $this->move_dir(SOURCE, DESTINATION);
        $this->remove_dir(SOURCE);

        unlink(DESTINATION.'opencart.zip');
        unlink(DESTINATION.'config-dist.php');
        unlink(DESTINATION.'admin/config-dist.php');
    }

    private function correctPermissions() {
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
        foreach ($dirs as $dir => $permissions){
            if (file_exists(DESTINATION.$dir)) {
                chmod(DESTINATION.$dir, $permissions);
            }
        }
    }

    private function configDefinition() {
        $output  = '<?php' . "\n";
        $output .= '// HTTP' . "\n";
        $output .= 'define(\'HTTP_SERVER\', \'' . HTTP_OPENCART . '\');' . "\n\n";

        $output .= '// HTTPS' . "\n";
        $output .= 'define(\'HTTPS_SERVER\', \'' . HTTPS_OPENCART . '\');' . "\n\n";

        $output .= '// DIR' . "\n";
        $output .= 'define(\'DIR_APPLICATION\', \'' . DIR_OPENCART . 'catalog/\');' . "\n";
        $output .= 'define(\'DIR_SYSTEM\', \'' . DIR_OPENCART. 'system/\');' . "\n";
        $output .= 'define(\'DIR_DATABASE\', \'' . DIR_OPENCART . 'system/database/\');' . "\n";
        $output .= 'define(\'DIR_LANGUAGE\', \'' . DIR_OPENCART . 'catalog/language/\');' . "\n";
        $output .= 'define(\'DIR_TEMPLATE\', \'' . DIR_OPENCART . 'catalog/view/theme/\');' . "\n";
        $output .= 'define(\'DIR_CONFIG\', \'' . DIR_OPENCART . 'system/config/\');' . "\n";
        $output .= 'define(\'DIR_IMAGE\', \'' . DIR_OPENCART . 'image/\');' . "\n";


        if(VERSION >= 210){
            $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/storage/download/\');' . "\n";
            $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/storage/cache/\');' . "\n";
            $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/storage/logs/\');' . "\n\n";
            $output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/storage/modification/\');' . "\n";
            $output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/storage/upload/\');' . "\n";

        }elseif(VERSION >= 200){
            $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/download/\');' . "\n";
            $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/cache/\');' . "\n";
            $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/logs/\');' . "\n\n";
            $output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/modification/\');' . "\n";
            $output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/upload/\');' . "\n";

        }else{
            $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'download/\');' . "\n";
            $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/cache/\');' . "\n";
            $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/logs/\');' . "\n\n";
        }

        if(VERSION >=301){
            $output .= 'define(\'DIR_STORAGE\', DIR_SYSTEM . \'storage/\');' . "\n";
            $output .= 'define(\'DIR_SESSION\', \'' . DIR_OPENCART . 'system/storage/session/\');' . "\n\n";
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
        chmod(DESTINATION."config.php", 0755);

    }

    private function adminConfigDefinition() {
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


        if(VERSION >= 210){
            $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/storage/download/\');' . "\n";
            $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/storage/cache/\');' . "\n";
            $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/storage/logs/\');' . "\n\n";
            $output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/storage/modification/\');' . "\n";
            $output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/storage/upload/\');' . "\n";

        }elseif(VERSION >= 200){
            $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'system/download/\');' . "\n";
            $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/cache/\');' . "\n";
            $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/logs/\');' . "\n\n";
            $output .= 'define(\'DIR_MODIFICATION\', \'' . DIR_OPENCART . 'system/modification/\');' . "\n";
            $output .= 'define(\'DIR_UPLOAD\', \'' . DIR_OPENCART . 'system/upload/\');' . "\n";

        }else{
            $output .= 'define(\'DIR_DOWNLOAD\', \'' . DIR_OPENCART . 'download/\');' . "\n";
            $output .= 'define(\'DIR_CACHE\', \'' . DIR_OPENCART . 'system/cache/\');' . "\n";
            $output .= 'define(\'DIR_LOGS\', \'' . DIR_OPENCART . 'system/logs/\');' . "\n\n";
        }

        if(VERSION >=301){
            $output .= 'define(\'DIR_STORAGE\', DIR_SYSTEM . \'storage/\');' . "\n";
            $output .= 'define(\'DIR_SESSION\', \'' . DIR_OPENCART . 'system/storage/session/\');' . "\n\n";
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

        if(VERSION >=300){
            $output .= '// OpenCart API' . "\n";
            $output .= 'define(\'OPENCART_SERVER\', \'https://www.opencart.com/\');' . "\n";

        }

        $output .= '?>';

        $file = fopen(DESTINATION. 'admin/config.php', 'wb');
        fwrite($file, $output);
        fclose($file);
        chmod(DESTINATION."admin/config.php", 0755);
    }

    private function fillDatabase() {
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
        if(VERSION > 153){
            $salt = substr(md5(uniqid(rand(), true)), 0, 9);
            $data['salt'] = $salt;
            $data['password'] = sha1( $salt . sha1($salt . sha1(PASSWORD)));
        }

        $this->db->fill_mysql($data);
    }

    private function installShopunity($target_url) {
        $this->install_mbooth($target_url);
        $this->db->query("INSERT INTO " . DB_PREFIX . "extension SET `type` = '" . $this->db->escape('module') . "', `code` = '" . $this->db->escape('shopunity') . "'");
        $this->db->addPermission('1', 'access', 'extension/module/shopunity');
        $this->db->addPermission('1', 'modify', 'extension/module/shopunity');
    }


    // Output
    private function getAllFolders() {
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
        return $folders;
    }

    private function getAllDatabases() {

        $db_used = array('information_schema', 'mysql', 'performance_schema');

        $db_list = array();
        $result = $this->db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA ");

        foreach($result->rows as $scheme){
            if(!in_array($scheme['SCHEMA_NAME'], $db_used)){
                $db_list[] = $scheme['SCHEMA_NAME'];
            }
        }
        return $db_list;
    }

    private function showPage() {
        $folders = $this->getAllFolders();
        $db_list = $this->getAllDatabases();
        krsort($folders);
        $html = '<html lang="en">
<head>
    <link href="http://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic,700italic&subset=latin,cyrillic-ext" rel="stylesheet" type="text/css"/>
    <link href="https://dreamvention.github.io/RipeCSS/css/ripe.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    <title>Store Manager</title>
</head>
<body>
    <style>
    body {
        background-color:#F6F9FC;
    }
    .header{
        position:fixed;
        height:90px;
        padding:5px 10px;
        width:100%;
        background: #F6F9FC;
        z-index:1000;
        border-bottom:1px solid #dfe4e8;
    }
    .header .logo{
        display: block;
        padding: 6px;
        float: left;
    }
    .header .logo img{
        display: block;
    }
    .header .form{
        display: block;
        padding: 20px 30px;
        float: left;
    }
    .header .store-link{
        padding: 20px;
        float: left;
    }
    .content{
        padding: 30px;
        padding-top:120px;
    }
    .hide {
        display: none;
    }
    .link-shopunity:hover {
        text-decoration: none;
    }
    .preloader-wrap{
        position: fixed;
        width: 100%;
        height: 100%;
        z-index: 1000;
        background: rgba(0,0,0,0.6);
    }
    
    .header .ve-label{
        margin: 0px 10px ;
    }

    .header .ve-checkbox{
        margin-bottom:5px;
    }
</style>
    
    <div class="preloader-wrap hide">
        <span class="preloader"></span>
    </div>
    <div id="wrapper">
        
        <!-- Store Creator -->
        <div class="header">   
            <a class="logo" href="https://shopunity.net">
                <img src="https://shopunity.net/catalog/view/theme/default/image/logo.png">
            </a>
            <form id="form" action="" method="post" class="form">
                <div class="ve-field ve-field--inline">
                    <label for="input_version" class="ve-label">OC version:</label>
                    <select class="ve-input ve-input--lg"  name="version" id="input_version" onchange="changeVersion()">';
foreach($this->versions as $version){
$html .= '<option value="'.$version["code"].'">'.$version["version"].'</option>';
}
$html .= '              </select>
                    <label class="ve-label" for="input_name"> Codename:</label>
                    <input class="ve-input ve-input--lg"  type="text" name="name" id="input_name"/>
                    <span class="ve-btn ve-btn--success ve-btn--lg submit-btn" id="submit">Create Store</span>
                    <label class="ve-checkbox ve-label" for="shopunity"> <input type="checkbox" checked  class="ve-input" id="shopunity" name="shopunity"><i></i> Install Shopunity</label>
                </div>
                
            </form>
            <div class="store-link hide">
                <a href="" id="link_to_shop"><span class="ve-btn ve-btn--success ve-btn--lg submit-btn ">Go to Store</span></a>
            </div>
        </div>
        <div class="content">
            <div class="row" id="pointerEventsShops">';
            $i = 1;
            foreach ($folders as $version => $shops) {
                if (!$shops == null) {
                    $html .= '<div class="ve-col-3">
                                <div class="ve-card">
                                     <div class="ve-card__header">
                                        <h2 class="ve-h3">'.$version.'</h2>
                                    </div>
                                    <div class="ve-list ve-list--borderless">';
                    foreach ($shops as $shop) {
                        // Git
                        if ($shop["git"]) {
                            $a_git = ' <a href="'.$shop["git"].'" target="_blank" class="ve-btn ve-btn--default ve-btn--sm" title="'.$shop["git"].'">Git</a>';
                        } else {
                            $a_git = '';
                        }

                        // Shop link
                        if (!$shop["db"]){
                            $shop_link_class = 'class="not-working"';
                        } else {
                            $shop_link_class = "";
                        }

                        // Delete Store button
                        $delete_but = '<a class="delete delete-store ve-btn ve-btn--danger ve-btn--sm" data-store="'.$shop["path"].'" data-database="'.$shop["db"].'">X</a>';

                        // Database
                        $database = $shop['db'];
                        if (empty($shop["db"])) {
                            $database = 'Db: none';
                        }

                        // Output
                        $html .= '   <div class="ve-list__item">
                                        <div>
                                            <span style="padding-left: 0px; flex: 0;">'.$a_git.'</span>
                                            <div class="text-left"><a href="'.$shop["link"].'" title="'.$shop["db"].'">'.$shop['name'].'</a><br/>
                                            <span class="small" style="margin-top: 5px; display:inline-block">'.$database.'</span>
                                            </div>
                                           
                                           <span class="text-right">'.$delete_but.'</span>
                                        </div>
                                    </div>';
                        //$html .=  '<li style="margin-bottom: 20px">'.$a_git.$shop_link.$delete_but.$database.'</li>';
                        $i++;
                    }
                    $html .= '</div></div></div>';
                }
            }
            $html .='
                </div>
                <div class="row" id="pointerEventsDb">
                    <!-- Databases -->
                    <div class="ve-col-12" >
                        <div class="ve-card">
                            <div class="ve-card__header">
                                <h2 class="ve-h2">Unused databases</h2>
                            </div>
                            <hr class="ve-hr"/>
                            <div class="ve-card__section"  style="padding-top: 30px">
                                <div class="ve-col-12">
                                    <ol style="columns: 4;">';
            foreach ($db_list as $db) {
                $html .= '<li style="margin-bottom: 10px; padding-right:40px;"><span style="cursor: text;">'.$db.'</span>
                            <a style="" class="delete delete-database ve-btn ve-btn--danger ve-pull-right" data-database="'.$db.'"><span style="font-size: 10px;">X</span></a></li>';
            }
            $html .= '        </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer ve-col-11" style="margin: 0 auto; color: #929292; text-align: center;">
                <hr class="ve-hr">
                <div style="margin: 10px 0 5px 0">OpencartInstaller version:  <span style="margin-left: 5px">'.$this->OIversion .'</span></div>
                <div style="margin-bottom: 20px">
                    Powered by <a class="link-shopunity" href="https://shopunity.net">Shopunity.net</a>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
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
                $("#input_name").mask("d_AAAAAAAAAAAAAAAAAAAA", {"translation": {
                        A: {pattern: /[\_A-Za-z0-9]/}
                    }
                });
            });';
        if (DEBUG == 1) {
            $html .= '$("#submit").on("click",function(){
                window.location.href = "http://'.$_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/.\\") .'/index.php?name=" + $("#input_name").val() + "&version=" + $("#input_version").val()});';
        } else {
            $html .= '
                $("#submit").on("click",function(){
                    $("#form").slideUp("slow");
                    $.ajax({
                        url: "http://'.$_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/.\\") .'/index.php",
                        type: "post",
                        data: $("#form").serialize(),
                        dataType: "html",
                        beforeSend: function() {
                            $(".preloader-wrap").removeClass("hide");
                            $("#form").addClass("hide");
                            document.getElementById("pointerEventsShops").style.pointerEvents = "none";
                            document.getElementById("pointerEventsDb").style.pointerEvents = "none";
                        },
                        complete: function() {
                            $(".preloader-wrap").addClass("hide");
                            $("#form").addClass("hide");
                            document.getElementById("pointerEventsShops").style.pointerEvents = "auto";
                            document.getElementById("pointerEventsDb").style.pointerEvents = "auto";
                        },
                        success: function(html) {
                            $("#link_to_shop").attr("href", "http://'.$_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/.\\") . "/".'"+$("#input_version").val() + "/"+$("#input_name").val());
                            $(".store-link").removeClass("hide");
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                        }
                    });
                });';
        }
        $html .= '$(".delete-database").click(function() {
                var delete_database = confirm("Are you sure, you want to delete this database?");
                if (delete_database === true) {

                    var database = $(this);

                     $.ajax({
                         url: "http://'.$_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), " /.\\") .'/index.php",
                         type: "post",
                         data: "delete_database=" + database.attr("data-database"),
                         dataType: "html",
                         beforeSend: function() {
                            $(".preloader-wrap").removeClass("hide");
                         },
                         complete: function() {
                            $(".preloader-wrap").addClass("hide");
                         },
                         success: function(html) {
                            database.parent().remove();
                         },
                         error: function(xhr, ajaxOptions, thrownError) {
                            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                         }
                     });
                 }

            });
            $(".delete-store").click(function(){
                var delete_store = confirm("Are you sure, you want to delete this store?");
                if (delete_store === true) {

                    var store = $(this);

                    $.ajax({
                        url: "http://'.$_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/.\\") .'/index.php",
                        type: "post",
                        data: "delete_store=" + store.attr("data-store") + "&delete_database=" + store.attr("data-database"),
                        dataType: "html",
                        beforeSend: function() {
                            $(".preloader-wrap").removeClass("hide");

                        },
                        complete: function() {
                            $(".preloader-wrap").addClass("hide");

                        },
                        success: function(html) {
                            store.parent().remove();
                            location.reload(true);
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                        }
                    });
                }
            })
            
    </script>

</body>
</html>';
        return $html;
    }


    // Connecting to Database
    private function connectMysqli() {
        define('DB_DRIVER', 'mysqli');
        $this->db = new DBMySQLi(DB_HOSTNAME,  DB_USERNAME, DB_PASSWORD);
    }

    // Functions
    private function get_git_config($defined, $file) {
        $txt_file    = file_get_contents($file);
        $rows        = explode("\n", $txt_file);
        array_shift($rows);

        foreach($rows as $row => $data) {

            if(strpos($data, $defined)){
                $row_data = explode(" ", $data);
                return $row_data[2];
            }

        }
        return false;
    }
    
    private function get_defined_value($defined, $file) {
        $txt_file    = file_get_contents($file);
        $rows        = explode("\n", $txt_file);
        array_shift($rows);

        foreach($rows as $row => $data)
        {

            if(strpos($data, $defined)){
                $row_data = explode("'", $data);
                return $row_data[3];
            }
        }
        return false;
    }

    private function install_mbooth($target_url){
        $file_zip = DESTINATION."arhive.zip";

        $this->download($target_url, DESTINATION, $file_zip);

        $this->move_dir(SOURCE, DESTINATION);
        $this->remove_dir(SOURCE);
        unlink($file_zip);
    }

    private function download($target_url, $file_dest, $file_zip){

        $userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
        $ch = curl_init();
        $fp = fopen("$file_zip", "w");
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FILE, $fp);
    
        $page = curl_exec($ch);
        if (!$page) {
            exit;
        }
    
        curl_close($ch);
        $zip = new ZipArchive;
    
        if (! $zip) {
            exit;
        }
        if($zip->open("$file_zip") != "true") {
            exit;
        }
        $zip->extractTo($file_dest);
        $zip->close();
    }
    
    private function move_dir($source, $dest){

        $files = scandir($source);

        foreach($files as $file){
    
            if($file == '.' || $file == '..' || $file == '.DS_Store') continue;
    
            if(is_dir($source.$file)){
                if (!file_exists($dest.$file.'/')) {
                    mkdir($dest.$file.'/', 0777, true);
                }
                $this->move_dir($source.$file.'/', $dest.$file.'/');
            } elseif (!rename($source.$file, $dest.$file)){

            }
        }
    }
    
    private function remove_dir($dir) {
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

final class DBMySQLi {
    private $link;

    public function __construct($hostname, $username, $password) {
        $this->link = new mysqli($hostname, $username, $password);

        if ($this->link->connect_error) {
            trigger_error('Error: Could not make a database link (' . $this->link->connect_errno . ') ' . $this->link->connect_error);
        }

        $this->link->set_charset("utf8");
        $this->link->query("SET SQL_MODE = ''");
    }

    public function create($database){
        if (!$this->link->query('CREATE DATABASE '.$database)) {
          trigger_error('Error creating database: ' . $database);
        }

    }

    public function connect ($database){

        if (!$this->link->select_db($database)) {
            trigger_error('Error: Could not connect to database ' . $database);
        }

        $this->link->query("SET NAMES 'utf8'");
        $this->link->query("SET CHARACTER SET utf8");
        $this->link->query("SET CHARACTER_SET_CONNECTION=utf8");
        $this->link->query("SET SQL_MODE = ''");

    }

    public function query($sql) {
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

    public function escape($value) {
        return $this->link->real_escape_string($value);
    }

    public function countAffected() {
        return $this->link->affected_rows;
    }

    public function getLastId() {
        return $this->link->insert_id;
    }

    public function __destruct() {
        $this->link->close();
    }

    public function fill_mysql($data) {

        $this->connect($data['db_name']);

        $file = DIR_OPENCART . 'install/opencart.sql';

        if (!file_exists($file)) {
            exit('Could not load sql file: ' . $file);
        }

        $lines = file($file);

        if ($lines) {
            $sql = '';

            foreach($lines as $line) {
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

            if($data['version'] < 201){
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `group` = 'config', `key` = 'config_email', value = '" . $this->escape($data['email']) . "'");
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `group` = 'config', `key` = 'config_url', value = '" . $this->escape(HTTP_OPENCART) . "'");
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `group` = 'config', `key` = 'config_encryption', value = '" . $this->escape(md5(mt_rand())) . "'");

            }else{
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_email', value = '" . $this->escape($data['email']) . "'");
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_url', value = '" . $this->escape(HTTP_OPENCART) . "'");
                $this->query("INSERT INTO `" . $data['db_prefix'] . "setting` SET `code` = 'config', `key` = 'config_encryption', value = '" . $this->escape(md5(mt_rand())) . "'");

            }
            $this->query("UPDATE `" . $data['db_prefix'] . "product` SET `viewed` = '0'");

        }
    }

    public function addPermission($user_group_id, $type, $route) {
        $user_group_query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "user_group WHERE user_group_id = '" . (int)$user_group_id . "'");

        if ($user_group_query->num_rows) {

            if(VERSION >= 210){

                $data = json_decode($user_group_query->row['permission'], true);
            }else{
                $data = unserialize($user_group_query->row['permission']);
            }


            $data[$type][] = $route;


            if(VERSION >= 210){
                $data = json_encode($data);
            }else{
                $data = serialize($data);
            }

            $this->query("UPDATE " . DB_PREFIX . "user_group SET permission = '" . $this->escape($data) . "' WHERE user_group_id = '" . (int)$user_group_id . "'");
        }
    }
}
