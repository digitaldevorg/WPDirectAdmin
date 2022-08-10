<?php
/*
Plugin Name: WP Direct Admin
Plugin URI: https://digitaldev.org.uk/project-da
Description: Connect To DirectAdmin Servers
Version: 1.4.0.1
*/

//Major.Feature.Function.Fix

require_once "require.class.php";


class WPDirectAdmin {
    public $plugin_file=__FILE__;
    public $responseObj;
    public $licenseMessage;
    public $showMessage=false;
    public $slug="wpda";
	public $enckey = '';
    function __construct() {
		session_start();
		$this->checkRequirements();
		
        add_action( 'admin_print_styles', [ $this, 'SetAdminStyle' ] );
		add_action('activate_WPDirectAdmin/WPDirectAdmin.php', [$this,'xInstall']);
		add_action('deactivate_WPDirectAdmin/WPDirectAdmin.php', [$this,'xUninstall']);
		
        $licenseKey=get_option("WPDirectAdmin_lic_Key","");
        $liceEmail=get_option( "WPDirectAdmin_lic_email","");
        
        WPDirectAdminBase::addOnDelete(function(){
           delete_option("WPDirectAdmin_lic_Key");
        });
		if (WPDirectAdminBase::CheckWPPlugin($licenseKey,$liceEmail,$this->licenseMessage,$this->responseObj,__FILE__)) {
			

			$this->enckey = $this->responseObj->enckey;
            add_action( 'admin_menu', [$this,'ActiveAdminMenu'],99999);
            add_action( 'admin_post_WPDirectAdmin_el_deactivate_license', [ $this, 'action_deactivate_license' ] );


			if (class_exists('WPDAPro') && $this->responseObj->pro==1) {
				add_action('wp_ajax_reboot_server', [$this, 'reboot_server']);
				add_action('wp_ajax_restart_service', [$this, 'restart_service']);
				add_action('wp_ajax_check_service', [$this, 'check_service']);
			}
			
			
			//plugin actions
			add_action('upgrader_process_complete', [$this, 'updateApp']);
			add_filter( 'cron_schedules', [$this, 'cron_interval'] );
			add_action( 'wpda_cron_hook', [$this, 'wpda_cron_exec'] );

			if ( ! wp_next_scheduled( 'wpda_cron_hook' ) ) {
				wp_schedule_event( time(), 'sixty_seconds', 'wpda_cron_hook' );
			}

			//ajax actions
			add_action('wp_ajax_add_server', [$this, 'add_server']);
			add_action('wp_ajax_delete_user', [$this, 'delete_user']);
			add_action('wp_ajax_delete_package', [$this, 'delete_package']);
			add_action('wp_ajax_update_settings', [$this, 'update_settings']);
			add_action('wp_ajax_switch_to_default', [$this, 'switch_to_default']);
			add_action('wp_ajax_get_random_password', [$this, 'get_random_password']);
			add_action('wp_ajax_get_packages', [$this, 'get_packages']);
			add_action('wp_ajax_remove_link', [$this, 'remove_link']);
			add_action('wp_ajax_get_server', [$this, 'get_server']);
			add_action('wp_ajax_reset_window', [$this, 'resetWindow']);
			add_action('wp_ajax_remove_server', [$this, 'removeServer']);


			add_action('wp_ajax_unlock_user', [$this, 'unlock_user']);
			add_action('wp_ajax_lock_user', [$this, 'lock_user']);

			//form actions
			add_action('admin_post_f_add_package', [$this, 'f_add_package']);
			add_action('admin_post_add_user', [$this, 'add_user']);
			add_action('admin_post_link_user', [$this, 'link_user']);
			add_action('admin_post_update_server', [$this, 'update_server']);
			add_action('admin_post_user_passwd', [$this, 'user_passwd']);
			
			//internal actions
			//add_action('wpda_pass_change', [$this, 'act_pass_change'], 10, 2);

        }else{
            if(!empty($licenseKey) && !empty($this->licenseMessage)){
               $this->showMessage=true;
            }
            update_option("WPDirectAdmin_lic_Key","") || add_option("WPDirectAdmin_lic_Key","");
            add_action( 'admin_post_WPDirectAdmin_el_activate_license', [ $this, 'action_activate_license' ] );
            add_action( 'admin_menu', [$this,'InactiveMenu']);
        }
    }
    
	function wpda_cron_exec(){
		
		//start of cron
		
		if (class_exists('WPDAPro')) {
			$this->proCron();
		}
		
		//end of cron
	}

	function errorServerConnect(){
		?>
		<div class="el-license-container" id="admin_error">
			<div class="notice notice-error is-dismissible">
				<p><?php echo _e('<b>Cannot connect to this server</b>, is it online?',$this->slug); ?></p>
			</div>
		</div>
		<?
	}
	function printStyles(){
?>
<style>
.w3-light-grey, .w3-hover-light-grey:hover, .w3-light-gray, .w3-hover-light-gray:hover {
	color: #000 !important;
	background-color: #f1f1f1 !important;
}
.w3-green, .w3-hover-green:hover {
	color: #fff !important;
	background-color: #4CAF50 !important;
}
.w3-center {
	text-align: center !important;
}
.w3-container, .w3-panel {
	padding: 0.01em 16px;
}
</style>
<?
	}
	function checkRequirements(){
		if (!in_array('font-awesome/index.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			$_SESSION['daerror'] = 'Plugin <b>font-awesome</b> is required to load icons.';
		}
	}
	function removeServer(){
		global $wpdb;
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		$server = sanitize_text_field($_REQUEST['server']);
		$current_user = wp_get_current_user();
		$server_title = $this->getServerInfo($server, 'server_friendly_name');

		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {

			//remove server links
			$table = $wpdb->prefix."da_account_links";
			$wpdb->query("DELETE FROM $table where serverid=".$server);
			//remove server data
			$table = $wpdb->prefix."da_server_list";
			$wpdb->query("DELETE FROM $table where server_id=".$server);	
			$table = $wpdb->prefix."da_server_statistics";
			$wpdb->query("DELETE FROM $table where server_id=".$server);
			
			do_action('wpda_deleted_server', $server, $server_title, $current_user->user_login);			
		}		
		
		wp_die();
	}
	function check_service()
	{
		global $wpdb;
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		$server = sanitize_text_field($_REQUEST['server']);
		$service = $_REQUEST['service'];

		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {

			$da = $this->serverConn($server);

			$sysinfo = $da->query("CMD_API_SYSTEM_INFO");
			//print_r($sysinfo);
			
			$sysinfo = explode("|", $sysinfo[$service]);
			//print_r($sysinfo);
			
			$status = $sysinfo[1];
			
			$da->logout(true);
			
			
			if (str_contains($status, 'Running')) {
				echo 1;
			} else {
				
				echo 0;
			}

			
			/*
			$service_directadmin = explode("|", $sysinfo['directadmin']);
			$service_dovecot = explode("|", $sysinfo['dovecot']);
			$service_exim = explode("|", $sysinfo['exim']);
			$service_httpd = explode("|", $sysinfo['httpd']);
			$service_mysqld = explode("|", $sysinfo['mysqld']);
			$service_named = explode("|", $sysinfo['named']);
			$service_pure_ftpd = explode("|", $sysinfo['pure-ftpd']);
			$service_sshd = explode("|", $sysinfo['sshd']);
			*/
			

		}
		wp_die();
	}
	function writeToLog($data, $type){
		
		//$type == warn, error, all, none
		$setting = get_option('wpda_log_level');
		
		if (!$setting) {
			$setting = 'all';
		}
		
		$logfile = 'log_'.date("d-m-Y").'.log';
		$logpath = plugin_dir_path( __DIR__ ).'/WPDirectAdmin/logs/';
		//$txt = '['.date("d-m-Y H:i:s").'] '.$data."\n";
		//file_put_contents($logpath.$logfile, $txt, FILE_APPEND);
		
		switch ($setting) {
			case "none":
			//do nothing
			break;
			case 'all':
			$txt = '['.date("d-m-Y H:i:s").'] '.$data."\n";
			if ($type == 'warn') {
				$txt = '['.date("d-m-Y H:i:s").'] (WARNING) '.$data."\n";	
			}
			if ($type == 'error') {
				$txt = '['.date("d-m-Y H:i:s").'] (ERROR) '.$data."\n";
			}
						
			file_put_contents($logpath.$logfile, $txt, FILE_APPEND);			
			break;
			
			case 'error':
			if ($type == 'error') {
				$txt = '['.date("d-m-Y H:i:s").'] (ERROR) '.$data."\n";
				file_put_contents($logpath.$logfile, $txt, FILE_APPEND);
			}
			break;
			
			case 'warn':
			if ($type == 'warn') {
				$txt = '['.date("d-m-Y H:i:s").'] (WARNING) '.$data."\n";
				file_put_contents($logpath.$logfile, $txt, FILE_APPEND);
			}			
			if ($type == 'error') {
				$txt = '['.date("d-m-Y H:i:s").'] (ERROR) '.$data."\n";
				file_put_contents($logpath.$logfile, $txt, FILE_APPEND);
			}
			break;
			
		}

		
	}
	function unlock_user()
	{
		global $wpdb;
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		$server = sanitize_text_field($_REQUEST['server']);
		$user = sanitize_text_field($_REQUEST['user']);
		$current_user = wp_get_current_user();

		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {

			$da = $this->serverConn($server);


			$result = $da->query("CMD_API_SELECT_USERS", array(
			'location' => 'CMD_API_ALL_USER_SHOW',
			'dounsuspend' => 'yes',
			'select0' => $user), "POST");
			
			$da->logout(true);
			
			if (!$result['error'] || $result['error']<1) {
				do_action('wpda_user_unlocked', $user, $current_user->user_login);
			}
			

		}
		wp_die();
	}
	function lock_user()
	{
		global $wpdb;
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		$server = sanitize_text_field($_REQUEST['server']);
		$user = sanitize_text_field($_REQUEST['user']);
		$current_user = wp_get_current_user();

		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {

			$da = $this->serverConn($server);


			$result = $da->query("CMD_API_SELECT_USERS", array(
			'location' => 'CMD_API_ALL_USER_SHOW',
			'dosuspend' => 'yes',
			'reason' => 'none',
			'details' => 'Suspended Via WordPress',
			'select0' => $user), "POST");
			
			$da->logout(true);
			
			if ($result['error']<1 || !$result['error']) {
				do_action('wpda_locked_user', $user, $current_user->user_login);
			}

		}
		wp_die();
	}

	function user_passwd(){
		
		global $wpdb;
		$data = $_POST['data'];
		$nonce = sanitize_text_field($data['nonce']);
		$server = sanitize_text_field($data['server']);
		$passwd = $data['passwdchng'];
		$current_user = wp_get_current_user();
		
		if (!$passwd) {
			$_SESSION['daerror'] = '<b>Missing Password</b>, Cannot change password.';
			wp_safe_redirect(admin_url( 'admin.php?page=wpda&sub=users&server='.$data['server']));
			
		}

		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {
			$da = $this->serverConn($server);
			$user_info = $da->query('CMD_API_SHOW_USER_CONFIG?user='.$data['user']);
			
			$result = $da->query("CMD_API_USER_PASSWD", array(
			'username' => $data['user'],
			'passwd' => $passwd,
			'action' => 'create',
			'referer' => 'CMD_API_USER_PASSWD',
			'passwd2' => $passwd,
			), "POST");
			
			$da->logout(true);
			
			if (!$result['error'] || $reuslt['error']<1) {

				$log = $current_user->user_login.' changed password for user: '.$data['user'];
				$this->writeToLog($log, 'all');

				do_action('wpda_pass_change', $data['user'], $user_info['email']);
				
			}
			if ((!$result['error'] || $reuslt['error']<1) && $data['notify'] == 'YES') {
				
				
				
				$output[] = "Hello (".$data['user']."),<br>";
				$output[] = "Your WPDirectAdmin password was recently changed by an administrator.<br>";
				$output[] = "";
				$output[] = "Your new password is now: <b>".$data['passwdchng']."</b>";
				$html = join("<br>",$output);
				
				
				wp_mail($user_info['email'], 'WPDirectAdmin - Password Changed', $html);
			
			}
			if ($request['error']==1) {
				$log = 'Error changing password for user: '.$data['user'].'('.$result['text'].' '.$result['details'].')';
				$this->writeToLog($log, 'error');
				
				$_SESSION['daerror'] = '<b>'.$result['text'].'</b> - '.$result['details'];
			}
		}
		
		wp_safe_redirect(admin_url( 'admin.php?page=wpda&sub=users&server='.$data['server']));
		
		
	}
	function update_server(){
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		$data = $_REQUEST['data'];
		
		if ( current_user_can('administrator') && wp_verify_nonce($data['nonce'], 'wpda')) {

			$wpdb->query("UPDATE $table set USER_PACKAGE = '".$data['USER_PACKAGE']."', RESELLER_PACKAGE = '".$data['RESELLER_PACKAGE']."', server_weight=".$data['server_weight'].", uptime_number=".$data['uptime_number'].", uptime_interval = '".$data['uptime_interval']."' WHERE server_id=".$data['serverid']);
		}
		
		wp_safe_redirect(admin_url( 'admin.php?page=wpda'));
		
	}
	function resetWindow(){
		?>
		<center><img src="<? echo plugins_url('/images/loader.png', __FILE__ ); ?>" height="48px" title="Loading"></center>
		<?
		wp_die();
	}
	function get_server(){
?>
<style>
.profield{
	background-color: #fffee2;
	border:#cf8a00 2px solid;
	border-radius: 10px;
	padding: 5px 5px 5px 5px;
}
</style>
<?
		$server = sanitize_text_field($_REQUEST['server']);
		global $wpdb;
		
		$table = $wpdb->prefix."da_server_list";
		$db = $wpdb->get_results("SELECT * FROM $table where server_id=".$server.' LIMIT 1');
		foreach ($db as $server) {
		?>
		<i class="fa-solid fa-server"> [#<? echo $server->server_id; ?> <? echo $server->server_friendly_name; ?>]</i> - <? echo $server->server_host; ?>
		<hr>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="update_server" name="update_server">
		<input type="hidden" name="action" value="update_server">
		<input type="hidden" name="data[serverid]" value="<? echo $server->server_id; ?>">
		<input type="hidden" name="data[nonce]" value="<? echo wp_create_nonce("wpda"); ?>">

		<font size="2">Default <b>USER</b> package:</font><br>
		<select style="width: 100%;" name="data[USER_PACKAGE]" class="ui-selectmenu-text">
			<option value="">- Select Package - </option>
			<?

		//get servers Packages_List_Table
		$da = $this->serverConn($server->server_id);
		//CMD_API_PACKAGES_USER
		$user_packages = $da->query("CMD_API_PACKAGES_USER");


		foreach ($user_packages as $id => $package) {
			$current_package = $da->query("CMD_API_PACKAGES_USER", array(
			"package" => $package), "GET");
			$data[] = array(
			'package'          => $package,
			'server_id'		=> $server->server_id,
			'type'			=> 'USER',
			'limits'		=> $current_package,
			'server_title'	=> $server->server_friendly_name
			);
		}

		$da->logout(true);

		foreach ($data as $id => $title) {
			$selected = '';
			if ($title['package'] == $server->USER_PACKAGE) {
				$selected = ' selected="selected"'; }
			echo '<option value='.$title['package'].$selected.'>'.$title['package'].'</option>';
		}
		$data = array();
		?>
		</select><br>
		<font size="2">Default <b>RESELLER</b> package:</font><br>
		<select style="width: 100%;" name="data[RESELLER_PACKAGE]">
			<option value="">- Select Package - </option>
			<?

		//get servers Packages_List_Table
		$da = $this->serverConn($server->server_id);
		$reseller_packages = $da->query("CMD_API_PACKAGES_RESELLER");
		//CMD_API_PACKAGES_USER
		;

		foreach ($reseller_packages as $id => $package) {
			$current_package = $da->query("CMD_API_PACKAGES_RESELLER", array(
			"package" => $package), "GET");


			$data[] = array(
			'package'          => $package,
			'server_id'		=> $server->server_id,
			'type'			=> 'RESELLER',
			'limits'		=> $current_package,
			'server_title'	=> $server->server_friendly_name
			);
		}



		$da->logout(true);

		foreach ($data as $id => $title) {
			$selected = '';
			if ($title['package'] == $server->RESELLER_PACKAGE) {
				$selected = ' selected="selected"'; }
			echo '<option value='.$title['package'].$selected.'>'.$title['package'].'</option>';
		}

		?>
		</select><br>
		<font size="2">Server Weight:</font><br>
		<input type="number" value="<? echo $server->server_weight; ?>" name="data[server_weight]"> <b>Note:</b> Used for load balance features
		<hr>
		<b>Detect short server uptime.</b><br>
		(can send notifications if the server is up less than this time.)<br>
		<input type="number" name="data[uptime_number]" min="0" max="99" value="<? echo $server->uptime_number; ?>" style="width: 100%;">
		<br><br>

		<select style="width: 100%;" class="ui-selectmenu-text" name="data[uptime_interval]">
			<option value="minutes" <?
		if ($server->uptime_interval == 'minutes') {
			echo 'selected="selected"'; } ?>>Minutes</option>
			<option value="hours" <?
		if ($server->uptime_interval == 'hours') {
			echo 'selected="selected"'; } ?>>Hours</option>
			<option value="days" <?
		if ($server->uptime_interval == 'days') {
			echo 'selected="selected"'; } ?>>Days</option>
		</select>
		<b>Note: </b> Should be relative to the server's uptime i.e 59 Minutes, 23 Hours...
		<hr>
		<?
		if ($this->responseObj->pro<1) { ?>
		<div class="profield">
			<i class="fa-brands fa-product-hunt" title="Pro Feature"></i>  <input type="checkbox" disabled="disabled"> Send Email Notification</div>
		<? } else { ?>
		<div class="profield">
			<i class="fa-brands fa-product-hunt" title="Pro Feature"></i> <input type="checkbox" > Send Email Notification</div>
		<? } ?>
</form>
		<?
	}
		wp_die();
	}
	function cron_interval( $schedules )
	{
		$schedules['sixty_seconds'] = array(
		'interval' => 60,
		'display'  => esc_html__( 'Every Minute' ), );
		return $schedules;
	}
	
	function remove_link()
	{
		global $wpdb;
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		$user = sanitize_text_field($_REQUEST['user']);
		$server = sanitize_text_field($_REQUEST['server']);
		$wpuid = sanitize_text_field($_REQUEST['wpuid']);

		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {
			$table = $wpdb->prefix."da_account_links";
			$wpdb->query("DELETE FROM $table WHERE serverid=".$server." and user_ref = '".$user."' and wpuid=".$wpuid);
			do_action('wpda_user_link_removed', $user, $server, $wpuid);
		}
		wp_die();
	}
    function link_user(){
		global $wpdb;
		$data = $_POST['data'];
		$nonce = sanitize_text_field($data['nonce']);
		$serverid = $data['server'];
		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {
			$table = $wpdb->prefix."da_account_links";
			$sql = "INSERT INTO $table (wpuid, serverid, user_ref) VALUES (".$data['wpuser'].", ".$data['server'].", '".$data['user']."')";
			$wpdb->query($sql);
		}
		
		do_action('wpda_linked_user', $data['wpuser'], $data['server'], $data['user']);
		wp_safe_redirect(admin_url( 'admin.php?page=wpda&sub=users&server='.$serverid));
    	
    }
    function get_packages(){
		if ($_REQUEST['server']<1) {

		} else {
			$serverid = $_REQUEST['server'];
			$wpda = new WPDirectAdmin;
			$da = $wpda->serverConn($serverid);
			$ips = $da->query("CMD_API_IP_MANAGER");
			
		?>
 Select An IP Address:<br>
<select name="data[ip]" style="width:100%;">
	<?
	foreach ($ips as $i => $idata) {
		$i = str_replace("_", ".", $i);
		echo '<option value="'.$i.'">'.$i.'</option>';
	}
	?>
</select><br>
		Select A User Package:<br>
		<select style="width: 100%;" name="data[package]">
			<?
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		$db = $wpdb->get_results("SELECT * FROM $table where server_id=".$serverid);

		
		//return $data;
		$data = array();

		foreach ($db as $server) {
			//get servers Packages_List_Table
			
			//CMD_API_PACKAGES_USER
			$user_packages = $da->query("CMD_API_PACKAGES_USER");


			foreach ($user_packages as $id => $package) {
				$current_package = $da->query("CMD_API_PACKAGES_USER", array(
				"package" => $package), "GET");
				$data[] = array(
				'package'          => $package,
				'server_id'		=> $server->server_id,
				'type'			=> 'USER',
				'limits'		=> $current_package,
				'server_title'	=> $server->server_friendly_name
				);
			}
		}

		$da->logout(true);

		foreach ($data as $id => $title) {
			$selected = '';
			if ($title['package'] == get_option('wpda_default_package')) {
				$selected = ' selected="selected"'; }
			echo '<option value='.$title['package'].$selected.'>'.$title['package'].'</option>';
		}
		?>
		</select>
		<?
	}
		wp_die();
    }
    function add_user(){
		global $wpdb;
		$data = $_POST['data'];
		$nonce = sanitize_text_field($data['nonce']);
		$current_user = wp_get_current_user();
		
		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {

			//var_dump($data);
			//get users details from wpuser
			$wpuid = $data['wpuser'];
			$user_data = get_userdata($wpuid);
			$username = $user_data->user_nicename;
			$email = $user_data->user_email;
			$serverid = $data['serverid'];
			
			/*
			/CMD_ACCOUNT_USER
			array (
			'username' => 'devuser',
			'email' => 'devuser@test.com',
			'passwd' => 'RZEAstpTwR',
			'passwd2' => 'RZEAstpTwR',
			'domain' => 'devuser.com',
			'package' => 'SharedHost',
			'ip' => '69.10.46.206',
			'notify' => 'no',
			'json' => 'yes',
			'add' => 'yes',
			'action' => 'create',
			)
			*/
			
			$da = $this->serverConn($serverid);

			$formdata = array (
			'username' => $username,
			'email' => $email,
			'passwd' => $data['passwd'],
			'passwd2' => $data['passwd'],
			'domain' => $data['domain'],
			'package' => $data['package'],
			'notify' => $data['notify'],
			'ip'	=> $data['ip'],
			'add' => 'yes',
			'action' => 'create',
			);

			$result = $da->query('CMD_API_ACCOUNT_USER', $formdata, 'POST');
			
			//var_dump($result);
			
			
			if($result["error"] <1){
				//we can add to DB as a Link
				
				$table = $wpdb->prefix."da_account_links";
    
	$wpdb->query("INSERT INTO $table (wpuid, serverid, user_ref) VALUES (".$wpuid.", ".$serverid.", '".$username."')");
			
	$log = $current_user->user_login.' added new user: '.$username.' to server #ID'.$serverid;
	$this->writeToLog($log, 'all');
				
			}
			$da->logout(TRUE);
			
			do_action('wpda_user_added', $formdata);

			if ($result['error'] == 1) {

				$_SESSION['daerror'] = '<b>'.$result['text'].'</b> - '.$result['details'];
				
				$log = 'Error adding user - ('.$result['text'].') '.$result['details'].' '.serialize($formdata);
				$this->writeToLog($log, 'error');
			}
		}
		wp_safe_redirect(admin_url( 'admin.php?page=wpda&sub=users&server='.$serverid));
    }
    function get_random_password(){
    	
		echo $this->randomPassword();
		wp_die();
    }
	function randomPassword()
	{
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}
    function updateApp(){
    	global $wpdb;
    	
		add_option('wpda_log_level', 'none');
		
    	//insert table if not exists
    	
		$table = $wpdb->prefix."da_server_notifications";
		$structure = "CREATE TABLE IF NOT EXISTS $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
		UNIQUE KEY ID (ID), server_id INT(9)
	DEFAULT 0, n_function VARCHAR(50), n_period VARCHAR(5), n_url VARCHAR(200), n_email VARCHAR(200), n_sms VARCHAR(50), n_phone VARCHAR(50), last_notification VARCHAR(50), n_title VARCHAR(200), n_active INT(9) DEFAULT 1
    );";

		$wpdb->query($structure);
		
		
		$table = $wpdb->prefix."da_account_links";
		$structure = "CREATE TABLE IF NOT EXISTS $table (
        link_id INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY link_id (link_id), wpuid INT(9) DEFAULT 0, serverid INT(9) DEFAULT 0, user_ref VARCHAR(200)
    );";
    
	$wpdb->query($structure);
	$table = $wpdb->prefix."da_server_statistics";

	$structure = "CREATE TABLE IF NOT EXISTS $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
		UNIQUE KEY ID (ID), server_id INT(9)
	DEFAULT 0, time_stamp VARCHAR(50), uptime VARCHAR(100), email_deliveries_outgoing INT(9) DEFAULT 0, RX VARCHAR(50), TX VARCHAR(50), disk_data LONGTEXT, service_status_data LONGTEXT
    );";

	$wpdb->query($structure);
	
	$table = $wpdb->prefix."da_server_list";
	
	$structure = "ALTER TABLE $table ADD USER_PACKAGE VARCHAR(200) AFTER server_weight, ADD RESELLER_PACKAGE VARCHAR(200) AFTER USER_PACKAGE";
	$wpdb->query($structure);
	
	$structure = "ALTER TABLE $table ADD uptime_interval VARCHAR(5) DEFAULT 'hours' AFTER RESELLER_PACKAGE, ADD uptime_number INT(9) DEFAULT 1 AFTER uptime_interval";
	$wpdb->query($structure);	
	
    }
	function switch_to_default(){
		$server = sanitize_text_field($_REQUEST['server']);
		$user = sanitize_text_field($_REQUEST['user']);
		$usertype = sanitize_text_field($_REQUEST['usertype']);
		
		switch ($usertype){
			
			case 'USER':
			$package = get_option('wpda_default_package');
			break;
			
			case 'RESELLER':
			$package = get_option('wpda_default_package_reseller');
			break;
		}
		
		
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {
			
			//switch the user
			$da = $this->serverConn($server);
			$result = $da->query("CMD_API_MODIFY_USER", array(
			"action"	=> 'package',
			"user"	=> $user,
			"package"	=> $package
			), "POST");

			$da->logout(TRUE);

		}		
		
		$this->writeToLog('WPDA plugin updated successfully.', 'all');
		wp_die();
		
	}
	
function hasWPLink($user, $server){
	global $wpdb;
	$table = $wpdb->prefix."da_account_links";
	$count = 0;
	$sql = "SELECT * FROM $table where user_ref = '".$user."' and serverid=".$server;
	
	$db = $wpdb->get_results($sql);
	foreach ($db as $rec) {
		$count = $rec->wpuid;
	}
	

	return $count;
}
	function f_add_package(){
		
		$data = $_POST['data'];
		
		$allfields = true;
				
		if ( current_user_can('administrator') && wp_verify_nonce($data['nonce'], 'wpda') && $allfields){
			//print_r($data);
			
			//now we can add to $data['serverid'];
			
			$da = $this->serverConn($data['serverid']);
			
			$formdata = array (
			'bandwidth' => $data['bandwidth'],
			'quota' => $data['quota'],
			'inode' => '',
			'uinode' => 'yes',
			'vdomains' => $data['vdomains'],
			'nsubdomains' => $data['nsubdomains'],
			'nemails' => $data['nemails'],
			'nemailf' => $data['nemailf'],
			'nemailml' => $data['nemailml'],
			'nemailr' => $data['nemailr'],
			'mysql' => $data['mysql'],
			'domainptr' => $data['domainptr'],
			'ftp' => $data['ftp'],
			'aftp' => 'OFF',
			'cgi' => $data['cgi'],
			'git' => $data['git'],
			'php' => $data['php'],
			'spam' => $data['spam'],
			'catchall' => $data['catchall'],
			'ssl' => $data['ssl'],
			'ssh' => $data['ssh'],
			'cron' => $data['cron'],
			'sysinfo' => $data['sysinfo'],
			'login_keys' => $data['login_keys'],
			'dnscontrol' => $data['bnscontrol'],
			'suspend_at_limit' => $data['suspend_at_limit'],
			'language' => $data['language'],
			'skin' => $data['skin'],
			'packagename' => $data['packagename'],
			'add' => 'yes',
			'feature_sets' => '',
			'email_daily_limit' => $data['daily_email_limit'],
			'jail' => $data['jail'],
			'wordpress' => $data['wordpress'],
			'plugins_allow' => '[clear]',
			'plugins_deny' => '[clear]',
			'CPUQuota' => '',
			'IOReadBandwidthMax' => '',
			'IOReadIOPSMax' => '',
			'IOWriteBandwidthMax' => '',
			'IOWriteIOPSMax' => '',
			'MemoryHigh' => '',
			'MemoryMax' => '',
			'TasksMax' => '',
			);
			if ($data['daily_email_limit']<0) {
				//uemail_daily_limit
				$formdata['uemail_daily_limit'] = 'yes';
			}
			if ($data['bandwidth']<0) {

				$formdata['ubandwidth'] = 'yes';
			} 
			if ($data['quota']<0) {

				$formdata['uquota'] = 'yes';
			} 
	
			
			if ($data['cmd'] == 'CMD_MANAGE_RESELLER_PACKAGES') {
				$formdata['dns'] = $data['dns'];
				$formdata['serverip'] = $data['serverip'];
				if ($data['nusers']<0) {
					$formdata['nusers'] = 100;
					$formdata['unusers'] = 'yes';
				} else {
					$formdata['nusers'] = $data['nusers'];
				}
				$formdata['ips'] = 0;
				$formdata['oversell'] = $data['oversell'];
			}
						
			
			$result = $da->query($data['cmd'], $formdata, 'POST');
			//var_dump($formatdata);

			$da->logout(TRUE);
			
			//if ($result['error'] == 1) {

				//$_SESSION['daerror'] = '<b>'.$result['text'].'</b> - '.$result['details'];
			//}			
		}
			
		var_dump($result);
		var_dump($formdata);
		
		//wp_safe_redirect(admin_url( 'admin.php?page=wpda_packages'));
	}
	function update_settings(){
		$setting = sanitize_text_field($_REQUEST['setting']);
		$value = sanitize_text_field($_REQUEST['value']);
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {
			update_option($setting, $value);
		}	
		
		wp_die();	
	}
    function delete_package(){
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		$package = sanitize_text_field($_REQUEST['package']);
		$server = sanitize_text_field($_REQUEST['server']);
		$type = sanitize_text_field($_REQUEST['type']);
		$nonce = sanitize_text_field($_REQUEST['nonce']); 
		$current_user = wp_get_current_user();
		   
		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {

			$da = $this->serverConn($server);
			//look for users on this package and switch to default
			
			$result = $da->query($cmd, array(
			"delete"	=> 'yes',
			"select0"	=> $package,
			"delete0"	=> $package
			), "POST");

			$da->logout(TRUE);
			
			if ($result['error']<1 || !$result['error']) {
				switch ($type) {

					case 'RESELLER':
						$resellers = $da->query("CMD_API_SHOW_RESELLERS");
						$opt = 'wpda_default_package_reseller';
						$opt = $wpdb->get_var("SELECT RESELLER_PACKAGE from $table where server_id=".$server);
						$cmd = 'CMD_API_MANAGE_RESELLER_PACKAGES';
						$modcmd = 'CMD_API_MODIFY_RESELLER';
						if ($package == $opt) {
							$wpdb->query("UPDATE $table set RESELLER_PACKAGE = '' WHERE server_id=".$server);
						}

						break;

					case 'USER':
						//CMD_API_PACKAGES_USER
						$resellers = $da->query("CMD_API_SHOW_ALL_USERS");
						$opt = 'wpda_default_package';
						//get $opt from server data
						$opt = $wpdb->get_var("SELECT USER_PACKAGE from $table where server_id=".$server);
						$cmd = 'CMD_API_MANAGE_USER_PACKAGES';
						$modcmd = 'CMD_API_MODIFY_USER';
						if ($package == $opt) {
							$wpdb->query("UPDATE $table set USER_PACKAGE = '' WHERE server_id=".$server);
						}
						break;
				}
				foreach ($resellers as $rec => $reseller) {
					// 	CMD_API_SHOW_USER_CONFIG

					$account_info = $da->query("CMD_API_SHOW_USER_CONFIG", array(
					"user" => $reseller
					), "GET");
					//print_r($account_info);
					if ($package == $account_info['package']) {
						//switch the user to defalt.
						$result = $da->query($modcmd, array(
						"action"	=> 'package',
						"user"	=> $reseller,
						"package"	=> $opt
						), "POST");

					}


				}
				//log()
				$log = $current_user->user_login.' removed package ('.$package.') from server #'.$server;
				$this->writeToLog($log, 'all');
			} else {
				//error
				$log = 'Error removing package ('.$package.') '.$result['text'].' '.$result['details'];
				$this->writeToLog($log, 'error');
			}
			

		}	
		wp_die();
    }
    function delete_user(){
		global $wpdb;
		$table = $wpdb->prefix."da_account_links";
		$user = sanitize_text_field($_REQUEST['user']);
		$server = sanitize_text_field($_REQUEST['server']);
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		$current_user = wp_get_current_user();
		
		if ( current_user_can('administrator') && wp_verify_nonce($nonce, 'wpda')) {
			//CMD_API_SELECT_USERS
			$da = $this->serverConn($server);
			$result = $da->query("CMD_API_SELECT_USERS", array(
			"confirmed" => 'Confirm',
			"delete"	=> 'yes',
			"select0"	=> $user
			), "POST");
			
			$da->logout(TRUE);
			
			if($result['error']<1 || !$result['error']){
				$wpdb->query("DELETE FROM $table where serverid=".$server." and user_ref = '".$user."'");
				$log = $current_user->user_login.' removed user: '.$user.' from server #'.$server;
				$this->writeToLog($log, 'all');
				do_action('wpda_deleted_user', $user, $current_user->user_login);
			} else {
				$log = 'Error deleting user from server #'.$server.' ('.$result['text'].') '.$result['details'];
				$this->writeToLog($log, 'error');
			}
		}
		
		wp_die();
    }
    function add_server(){
		global $wpdb;
		if ( current_user_can('administrator')) {
			$server_friendly_name = sanitize_text_field($_REQUEST['server_friendly_name']);
			$server_host = sanitize_text_field($_REQUEST['server_host']);
			$server_port = sanitize_text_field($_REQUEST['server_port']);
			$server_module = sanitize_text_field($_REQUEST['server_module']);
			$server_username = sanitize_text_field($_REQUEST['server_username']);
			$server_password = sanitize_text_field($_REQUEST['server_password']);
			$current_user = wp_get_current_user();
			
			$userpass = $server_username."::".$server_password;
			if (function_exists('mcrypt_encrypt')) {
				$enc = new Encryption;
				$userpass = $enc->encode($userpass, $this->enckey);
			}
			
			$table = $wpdb->prefix."da_server_list";
			
			$sql = $wpdb->prepare("INSERT INTO $table (server_friendly_name, server_host, server_port, server_userpass, server_module) VALUES ('%s', '%s', '%d', '%s', '%s')", array($server_friendly_name, $server_host, $server_port, $userpass, $server_module));
			$wpdb->query($sql);
			
		}
		$serverid = $wpdb->insert_id;
		$log = $current_user->user_login.' added server #'.$serverid;
		$this->writeToLog($log, 'all');
		wp_die();
    }
    function xInstall(){
    	
		global $wpdb;
		
		$table = $wpdb->prefix."da_server_notifications";
		$structure = "CREATE TABLE IF NOT EXISTS $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
		UNIQUE KEY ID (ID), server_id INT(9)
	DEFAULT 0, n_function VARCHAR(50), n_period VARCHAR(5), n_url VARCHAR(200), n_email VARCHAR(200), n_sms VARCHAR(50), n_phone VARCHAR(50), last_notification VARCHAR(50), n_title VARCHAR(200), n_active INT(9) DEFAULT 1
    );";

		$wpdb->query($structure);
				
		$table = $wpdb->prefix."da_server_statistics";
		
		$structure = "CREATE TABLE IF NOT EXISTS $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
		UNIQUE KEY ID (ID), server_id INT(9)
	DEFAULT 0, time_stamp VARCHAR(50), uptime VARCHAR(100), email_deliveries_outgoing INT(9) DEFAULT 0, RX VARCHAR(50), TX VARCHAR(50), disk_data LONGTEXT, service_status_data LONGTEXT
    );";

		$wpdb->query($structure);
				
		
		$table = $wpdb->prefix."da_account_links";
		$structure = "CREATE TABLE IF NOT EXISTS $table (
        link_id INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY link_id (link_id), wpuid INT(9) DEFAULT 0, serverid INT(9) DEFAULT 0, user_ref VARCHAR(200)
    );";

		$wpdb->query($structure);
		
		$table = $wpdb->prefix."da_server_category";
		$structure = "CREATE TABLE $table (
        category_id INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY category_id (category_id), category_name VARCHAR(200)
    );";
		$wpdb->query($structure);
    		
		$table = $wpdb->prefix."da_server_list";
		
		$structure = "CREATE TABLE $table (
        server_id INT(9) NOT NULL AUTO_INCREMENT,
		UNIQUE KEY server_id (server_id), server_friendly_name VARCHAR(200), server_host VARCHAR(200), server_port VARCHAR(200), server_userpass LONGTEXT, server_module VARCHAR(50), server_weight INT(9), USER_PACKAGE VARCHAR(200), RESELLER_PACKAGE VARCHAR(200), uptime_number INT(9) DEFAULT 1, uptime_interval VARCHAR(50) DEFAULT 'hours'
    );";
    
	//wp_mail('service@btctech.co.uk', 'SQL install', $structure);

		$wpdb->query($structure);
		
		add_option('wpda_default_package', '');
		add_option('wpda_default_package_reseller', '');
		add_option('wpda_log_level', 'none');
    }
    function getServerInfo($server, $info){
		global $wpdb;
		$table = $wpdb->prefix."da_server_list"; 
		
		$info = $wpdb->get_var("SELECT $info from $table where server_id=".$server);
		
		return $info;   	
    }
    function xUninstall(){
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		$wpdb->query("DROP TABLE $table");
		
		$table = $wpdb->prefix."da_account_links";
		$wpdb->query("DROP TABLE $table");
		
		$table = $wpdb->prefix."da_server_category";
		$wpdb->query("DROP TABLE $table");
		
		
		delete_option('wpda_default_package');
		delete_option('wpda_default_package_reseller');
		delete_option('wpda_log_level');
    	
    }
    function SetAdminStyle() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-ui-dialog' );

		wp_register_style( "JQUIs", plugins_url("jquery-ui.css",$this->plugin_file),1);
		wp_enqueue_style( "JQUIs" );
			
        wp_register_style( "WPDirectAdminLic", plugins_url("_lic_style.css",$this->plugin_file),1);
        wp_enqueue_style( "WPDirectAdminLic" );

		wp_enqueue_script( 'wpda-js', plugins_url('/custom.js', __FILE__ ), array(), '', true );
		
		wp_localize_script( 'wpda-js', 'wpda', array(
		// URL to wp-admin/admin-ajax.php to process the request
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		// generate a nonce with a unique ID "myajax-post-comment-nonce"
		// so that you can check it later when an AJAX request is sent
		'nonce' => wp_create_nonce( 'wpda' ),
		'loader' => plugins_url('/images/loader.png', __FILE__ )
		//
		));
    }
	function page_backups()
	{
		$server = sanitize_text_field($_REQUEST['server']);
		if ($server) {
			$da = $this->serverConn($server);
			$backups = $da->query("CMD_API_ADMIN_BACKUP");
			$da->logout(TRUE);
			//print_r($backups);
		?>
		<div class="el-license-container" id="admin_block_backups">
			<h4>
				<i class="fa-solid fa-folder-tree"></i> Server Backups</h4><? echo $this->getServerInfo($server, 'server_host'); ?>
			<hr>
 <i class="fa-solid fa-file-circle-question"></i> Location: <b><? echo $backups['location']; ?></b> - (<? echo $backups['num_files']; ?>) Files available.<hr>
 <?
 foreach ($backups as $bak => $dat) {
	 if (str_contains($bak, 'file') && ($bak !== 'num_files')) {

 ?>
 <i class="fa-solid fa-folder-tree"></i> <? echo $dat; ?><br>
 <?
 }
 }
 ?>
			
		</div>
		<?
	} else {
		?>
 <div class="el-license-container" id="admin_block_backups">
	Under Construction

</div>		
		<?
	}
}
	function page_alerts(){
		?>
		<div class="el-license-container" id="admin_block_notifications">
			<h4>
				<i class="fa-solid fa-bell"></i> Notifications Manager</h4>
				Setup notifications to alert you or other 3rd-party applications on diffirent events.
				<hr>

		</div>
		<?	
	}
	function page_packages(){
		?>
		<div class="el-license-container" id="admin_block_user">
		<?php
		$table = new Packages_List_Table;
		$table->list_table_page();
		?>

		</div>
		<?
	}
	
	function pro_features(){
?>
 <div class="el-license-container" id="pro_features">
	 <h4>
		 <i class="fa-brands fa-medapps"></i> PRO Pack</h4><br>
 PRO pack is a one-time purchase to unlock more usefull features in your <b>WPDirectAdmin</b> plugin.
 <br>This is a low cost to help cover the development and server costs in running the project. Our Beta-Testers can request the PRO pack for free on  <a target="_blank" href="https://digitaldev.org.uk/project-da"> WPDA Project's Homepage</a>.<hr>
 <h5><i class="fa-solid fa-sim-card"></i> SMS Notifications</h5>Send/Receive SMS notifications and server alerts with notifications manager.<br>
 <h5>
	<i class="fa-solid fa-power-off"></i> Power Management</h5>Reboot & Power Down your server quickly from within your WordPress admin panel and widgets. Get server down/offline notificiations.<br>
 <h5>
	<i class="fa-solid fa-microchip"></i> Services Manager</h5>Restart services quicly in real-time for your WordPress admin panel and widgets. Get notifications when services stop/start.
 </div>
<?
	}
    function ActiveAdminMenu(){
        
		add_menu_page (  "WPDirectAdmin", "WP Direct Admin", "activate_plugins", $this->slug, [$this,"Activated"], plugins_url('/images/da.png', __FILE__));
		if ($this->servers()>0) {
			add_submenu_page(  $this->slug, "Packages", "Packages", "activate_plugins",  $this->slug."_packages", [$this,"page_packages"] );
			add_submenu_page(  $this->slug, "Notifications", "Notifications", "activate_plugins",  $this->slug."_alerts", [$this,"page_alerts"] );
		}
		if (class_exists('WPDAPro') && $this->responseObj->pro==1) {
			add_submenu_page(  $this->slug, "Backup Manager", "Backup Manager", "activate_plugins",  $this->slug."_backups", [$this,"page_backups"] );
			
		} else {
			add_submenu_page(  $this->slug, "PRO Features", "PRO Features", "activate_plugins",  $this->slug."_pro", [$this,"pro_features"] );
		}

    }
    
function formatUptime($uptime){
	//$uptime = 0 Days, 0 Hours and 0 Minutes
	$daysdat = explode(",", $uptime);
	$days = explode(" ", $daysdat[0]);
	$days = (int)str_replace(" ", "", $days[0]);
	
	$remains = explode(" and ", $daysdat[1],);
	
	$hours = explode(" ", $remains[0], 1);
	$hours = (int)str_replace(" ", "", $hours[0]);
	
	$mins = explode(" ", $remains[1]);
	$mins = (int)str_replace(" ", "", $mins[0]);
	
	$uptime = array();
	
	$uptime['hours'] = $hours;
	$uptime['minutes'] = $mins;
	$uptime['days'] = $days;
	
	return $uptime;
	
	
}
    function InactiveMenu() {
		add_menu_page( "WPDirectAdmin", "WP Direct Admin", 'activate_plugins', $this->slug,  [$this,"LicenseForm"], plugins_url('/images/da.png', __FILE__) );

    }
    function action_activate_license(){
        check_admin_referer( 'el-license' );
        $licenseKey=!empty($_POST['el_license_key'])?$_POST['el_license_key']:"";
        $licenseEmail=!empty($_POST['el_license_email'])?$_POST['el_license_email']:"";
        update_option("WPDirectAdmin_lic_Key",$licenseKey) || add_option("WPDirectAdmin_lic_Key",$licenseKey);
        update_option("WPDirectAdmin_lic_email",$licenseEmail) || add_option("WPDirectAdmin_lic_email",$licenseEmail);
        update_option('_site_transient_update_plugins','');
        wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
    }
    function action_deactivate_license() {
        check_admin_referer( 'el-license' );
        $message="";
        if(WPDirectAdminBase::RemoveLicenseKey(__FILE__,$message)){
            update_option("WPDirectAdmin_lic_Key","") || add_option("WPDirectAdmin_lic_Key","");
            update_option('_site_transient_update_plugins','');
        }
        wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
    }
    function getServerLogin($server){
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		
		$up = $wpdb->get_Var("SELECT server_userpass FROM $table where server_id=".$server);
		$enc = new Encryption;
		$key = $this->enckey;
		if (function_exists('mcrypt_encrypt')) {
			$up = $enc->decode($up, $key);
		}
		$up = explode("::", $up);
		
		$userpass = array();
		$userpass['user'] = $up[0];
		$userpass['pass'] = $up[1];
		return $userpass;
		
    }
    function serverTest($server){
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		$userpass = $this->getServerLogin($server);

		$server_host = $wpdb->get_var("SELECT server_host from $table where server_id=".$server);
		$server_port = $wpdb->get_var("SELECT server_port from $table where server_id=".$server);

		$da = new DirectAdmin("https://".$server_host.":".$server_port, $userpass['user'], $userpass['pass'], FALSE);
		$stats = $da->query("CMD_API_SYSTEM_INFO");
		if (!$stats['uptime']) {
			return false;
		} else {
			return true;
		}
		$da->logout(true);
    }
    function serverConn($server){
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";

		$server_title = $wpdb->get_Var("SELECT server_friendly_name FROM $table where server_id=".$server);
		$server_host = $wpdb->get_Var("SELECT server_host FROM $table where server_id=".$server);

		$server_port = $wpdb->get_Var("SELECT server_port FROM $table where server_id=".$server);

		$userpass = $this->getServerLogin($server);
		$da = new DirectAdmin("https://".$server_host.":".$server_port, $userpass['user'], $userpass['pass'], FALSE);
		return $da;
    }
    function sub_server_info(){
		$server = sanitize_text_field($_REQUEST['server']);
		
		global $wpdb;

		if (!$this->serverTest($server)) {
?>
<div class="el-license-container" id="admin_error">
<div class="notice notice-error is-dismissible">
	<p><?php echo _e('<b>Cannot connect to this server</b>, is it online?',$this->slug); ?></p>
</div>
</div>
<?
} else {
	$da = $this->serverConn($server);
	$stats = $da->query("CMD_API_ADMIN_STATS");
	//CMD_API_SHOW_SERVICES
	$services = $da->query("CMD_API_SHOW_SERVICES");
	//CMD_API_SYSTEM_INFO
	$sysinfo = $da->query("CMD_API_SYSTEM_INFO");
	

	$da->logout(true);

	//print_r($sysinfo);

	$software_version = explode("|", $sysinfo['directadmin']);

?>
<style>
	.w3-light-grey, .w3-hover-light-grey:hover, .w3-light-gray, .w3-hover-light-gray:hover {
		color: #000 !important;
		background-color: #f1f1f1 !important;
	}
	.w3-green, .w3-hover-green:hover {
		color: #fff !important;
		background-color: #4CAF50 !important;
	}
	.w3-center {
		text-align: center !important;
	}
	.w3-container, .w3-panel {
		padding: 0.01em 16px;
	}
</style>
<div class="el-license-container" id="admin_server_users">
	<h4>Server Information - <?php echo $server_title; ?></h4><?php echo $server_host; ?><hr>
	<ul class="el-license-info">
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><i class="fa-solid fa-code-branch fa-xl" title="DirectAdmin Software Version"></i> Software Version</span>
		<span class="el-license-key"><?php _e($software_version[0],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><i class="fa-solid fa-clock-rotate-left fa-xl"></i> Server Uptime</span>
		<span class="el-license-key"><?php _e($sysinfo['uptime'],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><i class="fa-solid fa-envelope-open-text fa-xl" title="Outgoing Mail Deliveries"></i> Mail Deliveries</span>
		<span class="el-license-key"><?php _e($stats['email_deliveries_outgoing'],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><font color="green"><i class="fa-solid fa-right-left fa-xl" title="Data IN [RX]"></i></font> Data IN [RX]</span>
		<span class="el-license-key"><?php _e($stats['RX'],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><font color="red"><i class="fa-solid fa-right-left fa-xl" title="Data OUT [TX]"></i></font> Data OUT [TX]</span>
		<span class="el-license-key"><?php _e($stats['TX'],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><i class="fa-solid fa-shuffle fa-xl" title="FTP Accounts"></i> FTP Accounts</span>
		<span class="el-license-key"><?php _e($stats['ftp'],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><i class="fa-solid fa-database fa-xl"></i> MySQL Databases</span>
		<span class="el-license-key"><?php _e($stats['mysql'],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><i class="fa-solid fa-globe fa-xl"></i> Domains</span>
		<span class="el-license-key"><?php _e($stats['vdomains'],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><i class="fa-solid fa-at fa-xl"></i> Email Accounts</span>
		<span class="el-license-key"><?php _e($stats['nemails'],$this->slug); ?></span>
		</div>
		</li>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><i class="fa-solid fa-people-group fa-xl"></i> Resellers</span>
		<span class="el-license-key"><?php _e($stats['nresellers'],$this->slug); ?></span>
		</div>
		</li>
		<?
		foreach ($stats as $stat => $dat) {
			if (str_contains($stat, 'disk') && !str_contains($dat, 'tmpfs')) {
				//echo '<li>['.$stat.'] '.$dat.'</li>';

				$hdd = explode(":", $dat);

		?>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;"><img src="<?php echo plugins_url('/images/ssd.png', __FILE__); ?>" title="<? echo $stat; ?>"> <?php echo strtoupper($stat); ?></span>
		<span class="el-license-key"><?php _e($hdd[0],$this->slug); ?> - <?php _e($hdd[4],$this->slug); ?>
		<div class="w3-light-grey">
		<div class="w3-container w3-green w3-center" style="width:<?php _e($hdd[4],$this->slug); ?>"><?php _e($hdd[4],$this->slug); ?></div>
		</div><br>
		</span>
		</div>
		</li>
		<?
	}
		}
		?>
	</ul>
	<hr>
	<h4>Services</h4><? echo $this->getServerInfo($server, 'server_host'); ?><br>
	<ul class="el-license-info">
		<?
		
		$service_directadmin = explode("|", $sysinfo['directadmin']);
		$service_dovecot = explode("|", $sysinfo['dovecot']);
		$service_exim = explode("|", $sysinfo['exim']);
		$service_httpd = explode("|", $sysinfo['httpd']);
		$service_mysqld = explode("|", $sysinfo['mysqld']);
		$service_named = explode("|", $sysinfo['named']);
		$service_pure_ftpd = explode("|", $sysinfo['pure-ftpd']);
		$service_sshd = explode("|", $sysinfo['sshd']);

		$service_mon = array();
		$service_mon['directadmin'] = $service_directadmin[1];
		$service_mon['dovecot'] = $service_dovecot[1];
		$service_mon['exim'] = $service_exim[1];
		$service_mon['httpd'] = $service_httpd[1];
		$service_mon['mysqld'] = $service_mysqld[1];
		$service_mon['named'] = $service_named[1];
		$service_mon['pure-ftpd'] = $service_pure_ftpd[1];
		$service_mon['sshd'] = $service_sshd[1];

		foreach ($service_mon as $serv => $state) {

		?>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;">
			<i class="fa-solid fa-microchip fa-xl"></i> <? echo strtoupper($serv); ?></span>
		<span class="el-license-key">
		<?
		if (str_contains($state, 'Running')) {
		?>
		<span id="<? echo $serv.'_'.$server; ?>"><font color="green"><i class="fa-solid fa-square-check"></i> Running</font></span>
		<?
	} else {
		?>
		<span id="<? echo $server.'_'.$server; ?>"><font color="red">
		<i class="fa-solid fa-rectangle-xmark"></i> Stopped (<? echo $state; ?>)</font></span>
		<?

	}
		?>
		</span>
		</div>
		</li>
		<?
	}
		?>
		<hr>
		<?
		foreach ($services as $service => $status) {
		?>
		<li>
		<div>
		<span class="el-license-info-title" style="width:200px;">
		<i class="fa-solid fa-microchip fa-xl"></i> <? echo strtoupper($service); ?></span>
		<span class="el-license-key">
		<?
		if ($status == 'on') {
		?>
		<font color="green"><i class="fa-solid fa-square-check"></i> ON</font>
		<?
	} else {
		?>
		<font color="red"><i class="fa-solid fa-rectangle-xmark"></i> OFF</font>
		<?

	}
		?>
		</span>
		</div>
		</li>
		<?
	}
		?>
	</ul>
</div>
<?
}
    	
    }
    function sub_users(){
		$server = sanitize_text_field($_REQUEST['server']);
		
		if ($server) {
			
		?>
		<div class="el-license-container" id="admin_server_users">
			<?php
			$table = new DAA_Wp_List_Table;
			$table->list_table_page();
			?>
		</div>
		<?
	}	
    }
	function servers()
	{
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		$count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table");
		return $count;
	}
    function gsl(){
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		$count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table");
		$limit = $this->responseObj->server_limit;
		$return = 0;
		//echo 'Limit: '.$limit.' Count: '.$count;
		if ($limit == 'Unlimited') {
			$return =  0;
		} else {
			
			if ($count == $limit || $count > $limit) {
				$return = 1;
			}
		}		
		//echo $return;
		return $return;
    }
    function gpsflu(){
		return $this->responseObj->pro;
    }
    function application_settings(){
    	?>
		<div class="el-license-container" id="admin_bapp_settings">
		<h3>
		<i class="fa-solid fa-screwdriver-wrench"></i> Application Settings</h3>
		</div>
		<div class="el-license-container" id="admin_block_user">
		<?php
		$table = new DA_Wp_List_Table;
		$table->list_table_page();
		?>
		</div>
    	<?
    	
    }
    function Activated(){

		if ( is_admin() ) {
			if ( ! function_exists('get_plugin_data') ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_data = get_plugin_data( __FILE__ );

		}
        ?>
 <?php
 if (!empty($_SESSION['daerror'])) {
 ?>
 <br><br>
<div class="notice notice-error is-dismissible">
	<p><?php echo _e($_SESSION['daerror'],$this->slug); ?></p>
</div>
<?php
$_SESSION['daerror'] = '';
}
//if (!is_plugin_active( 'font-awesome/font-awesome.php' )) {
	$this->application_settings();
	
switch ($_REQUEST['sub']){
	
	case 'users':
	$this->sub_users();
	break;
	
	case 'serverinfo':
	$this->sub_server_info();
	break;
}
?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="WPDirectAdmin_el_deactivate_license"/>
            <div class="el-license-container">
                <h3 ><i class="dashicons-before dashicons-admin-network"></i> <?php _e("WPDA License Info",$this->slug);?> </h3>
                <hr>
                <ul class="el-license-info">
                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Status",$this->slug);?></span>

                        <?php if ( $this->responseObj->is_valid ) : ?>
                        <?
						if ($this->responseObj->pro==1 && class_exists('WPDAPro')) {
							$valid = "PRO";
						} else {
							$valid = "Valid";
						}
                        ?>
                            <span class="el-license-valid"><?php _e($valid,$this->slug);?></span>
                        <?php else : ?>
                            <span class="el-license-valid"><?php _e("Invalid",$this->slug);?></span>
                        <?php endif; ?>
                    </div>
                </li>
                
				<li>
				<div>
				<span class="el-license-info-title"><?php _e("Application Version",$this->slug); ?></span>
				<?php echo $plugin_data['Version']; ?>
				</div>
				</li>
				
                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("License Type",$this->slug);?></span>
                        <?php echo $this->responseObj->license_title; ?>
                    </div>
                </li>
				<li>
				<div>
				<span class="el-license-info-title"><?php _e("Server Limit",$this->slug); ?></span>
				<?php echo $this->responseObj->server_limit; ?>
				</div>
				</li>
               <li>
                   <div>
                       <span class="el-license-info-title"><?php _e("License Expired on",$this->slug);?></span>
                       <?php echo $this->responseObj->expire_date;
                       if(!empty($this->responseObj->expire_renew_link)){
                           ?>
                           <a target="_blank" class="el-blue-btn" href="<?php echo $this->responseObj->expire_renew_link; ?>">Renew</a>
                           <?php
                       }
                       ?>
                   </div>
               </li>

               <li>
                   <div>
                       <span class="el-license-info-title"><?php _e("Support Expired on",$this->slug);?></span>
                       <?php
                           echo $this->responseObj->support_end;
                        if(!empty($this->responseObj->support_renew_link)){
                            ?>
                               <a target="_blank" class="el-blue-btn" href="<?php echo $this->responseObj->support_renew_link; ?>">Renew</a>
                            <?php
                        }
                       ?>
                   </div>
               </li>
                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Your License Key",$this->slug);?></span>
                        <span class="el-license-key"><?php echo esc_attr( substr($this->responseObj->license_key,0,9)."XXXXXXXX-XXXXXXXX".substr($this->responseObj->license_key,-9) ); ?></span>
                    </div>
                </li>
                </ul>
                <div class="el-license-active-btn">
                    <?php wp_nonce_field( 'el-license' ); ?>
                    <?php submit_button('Deactivate'); ?>
                </div>
            </div>
        </form>
    <?php
    }

    function LicenseForm() {
        ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="WPDirectAdmin_el_activate_license"/>
        <div class="el-license-container">
            <h3 class="el-license-title"><i class="dashicons-before dashicons-star-filled"></i> <?php _e("WP Direct Admin Licensing",$this->slug);?></h3>
            <hr>
            <?php
            if(!empty($this->showMessage) && !empty($this->licenseMessage)){
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo _e($this->licenseMessage,$this->slug); ?></p>
                </div>
                <?php
            }
            ?>
            <p><?php _e("Enter your license key here, to activate the product, and get full feature updates and premium support.",$this->slug);?></p>
<ol>
    <li><?php _e("Write your licnese key details",$this->slug);?></li>
    <li><?php _e("How buyer will get this license key?",$this->slug);?></li>
    <li><?php _e("Describe other info about licensing if required",$this->slug);?></li>
    <li>. ...</li>
</ol>
            <div class="el-license-field">
                <label for="el_license_key"><?php _e("License code",$this->slug);?></label>
                <input type="text" class="regular-text code" name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx" required="required">
            </div>
            <div class="el-license-field">
                <label for="el_license_key"><?php _e("Email Address",$this->slug);?></label>
                <?php
                    $purchaseEmail   = get_option( "WPDirectAdmin_lic_email", get_bloginfo( 'admin_email' ));
                ?>
                <input type="text" class="regular-text code" name="el_license_email" size="50" value="<?php echo $purchaseEmail; ?>" placeholder="" required="required">
                <div><small><?php _e("We will send update news of this product by this email address, don't worry, we hate spam",$this->slug);?></small></div>
            </div>
            <div class="el-license-active-btn">
                <?php wp_nonce_field( 'el-license' ); ?>
                <?php submit_button('Activate'); ?>
            </div>
        </div>
    </form>
        <?php
    }
}
if (class_exists('WPDAPro')) {
	new WPDAPro();
}
 else {
 	new WPDirectAdmin();
 }
