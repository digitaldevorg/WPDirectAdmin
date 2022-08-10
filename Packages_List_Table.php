<?php
if (!class_exists('Packages_List_Table')) {
	class Packages_List_Table
	{
/**
* Constructor will create the menu item
*/

		public function list_table_page()
		{
			$exampleListTable = new WP_Packages_List_Table();
			$exampleListTable->prepare_items();
?>
<style>
	/* The container */
	.container {
		display: block;
		position: relative;
		padding-left: 35px;
		margin-bottom: 12px;
		cursor: pointer;
		font-size: 22px;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}

	/* Hide the browser's default radio button */
	.container input {
		position: absolute;
		opacity: 0;
		cursor: pointer;
	}

	/* Create a custom radio button */
	.checkmark {
		position: absolute;
		top: 0;
		left: 0;
		height: 25px;
		width: 25px;
		background-color: #eee;
		border-radius: 50%;
	}

	/* On mouse-over, add a grey background color */
	.container:hover input ~ .checkmark {
		background-color: #ccc;
	}

	/* When the radio button is checked, add a blue background */
	.container input:checked ~ .checkmark {
		background-color: #2196F3;
	}

	/* Create the indicator (the dot/circle - hidden when not checked) */
	.checkmark:after {
		content: "";
		position: absolute;
		display: none;
	}

	/* Show the indicator (dot/circle) when checked */
	.container input:checked ~ .checkmark:after {
		display: block;
	}

	/* Style the indicator (dot/circle) */
	.container .checkmark:after {
		top: 9px;
		left: 9px;
		width: 8px;
		height: 8px;
		border-radius: 100%;
		background: white;
	}
</style>
<style>
	.switch {
		position: relative;
		display: inline-block;
		width: 60px;
		height: 34px;
	}

	.switch input {
		opacity: 0;
		width: 0;
		height: 0;
	}

	.slider {
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: #ccc;
		-webkit-transition: .4s;
		transition: .4s;
	}

	.slider:before {
		position: absolute;
		content: "";
		height: 26px;
		width: 26px;
		left: 4px;
		bottom: 4px;
		background-color: white;
		-webkit-transition: .4s;
		transition: .4s;
	}

	input:checked + .slider {
		background-color: #2196F3;
	}

	input:focus + .slider {
		box-shadow: 0 0 1px #2196F3;
	}

	input:checked + .slider:before {
		-webkit-transform: translateX(26px);
		-ms-transform: translateX(26px);
		transform: translateX(26px);
	}

	/* Rounded sliders */
	.slider.round {
		border-radius: 34px;
	}

	.slider.round:before {
		border-radius: 50%;
	}
</style>
 <style>
	.acc_user{
		background: #faffc0;
		border: #ffffff solid 2px;
		border-radius: 10px;
	}
	.acc_user_adm{
		background: #ffd2d2;
		border: #ffffff solid 2px;
		border-radius: 10px;
	}
	.acc_user_reseller{
		background: #ffdda1;
		border: #ffffff solid 2px;
		border-radius: 10px;
		padding-left: 5px;
		padding-right: 5px;
	}
</style>
<div id="package_dialog" title="Add USER package">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="faddpackage">
	<input type="hidden" name="action" value="f_add_package">
	<input type="hidden" name="data[cmd]" value="CMD_MANAGE_USER_PACKAGES">
	<input type="hidden" name="data[nonce]" value="<? echo wp_create_nonce( 'wpda' ); ?>">
	<fieldset><legend>Server & Package</legend>
	<font size="2">Package Name:</font><br>
	<input type="text" name=data[packagename]" placeholder="Package Name" style="margin-bottom: 10px;" size="50" required="required"><br>
	<font size="2">Server:</font><br>
	<select name="data[serverid]" style="width: 100%">
	<?
	global $wpdb;
	$table = $wpdb->prefix."da_server_list";
	$db = $wpdb->get_results("SELECT * FROM $table");
	foreach ($db as $server) {
		echo '<option value="'.$server->server_id.'">'.$server->server_friendly_name.' [#'.$server->server_id.']</option>';
	}
	?>	
	</select></fieldset><br>
	<fieldset><legend>HDD Quota</legend>
	<font size="2">Bandwidth:</font>
	<input type="text" name="data[bandwidth]" placeholder="1024MB" style="margin-bottom: 10px;" size="10" required="required"> MB
	<font size="2">Disk Quota:</font>
	<input type="text" name="data[quota]" placeholder="1024MB" style="margin-bottom: 10px;" size="10" required="required"> MB<br><b>Note:</b> Set either to -1 to set unlimited.</fieldset><br>
	<font size="2">Daily Email Limit:</font>
	<input type="number" name="data[daily_email_limit]" style="margin-bottom: 10px;" size="5" value="-1"> <b>Note:</b> -1 to set unlimited.	
	<fieldset><legend>Limits</legend>
		<font size="2">Domains:</font>
	<input type="number" name="data[vdomains]" style="margin-bottom: 10px;" value="1" size="3">
	<font size="2">Sub Domains:</font>
	<input type="number" name="data[nsubdomains]" style="margin-bottom: 10px;" value="1" size="3">
	<font size="2">Email Accounts:</font>
	<input type="number" name="data[nemails]" style="margin-bottom: 10px;" value="1" size="3"><br>
	<font size="2">Forwarders:</font>
	<input type="number" name=data[nemailf]" style="margin-bottom: 10px;" value="1" size="3">
	<font size="2">Mailing Lists:</font>
	<input type="number" name="data[nemailml]" style="margin-bottom: 10px;" value="1" size="3">
	<font size="2">Auto Responders:</font>
	<input type="number" name="data[nemailr]" style="margin-bottom: 10px;" value="1" size="3"><br>
	<font size="2">MySQL DB:</font>
	<input type="number" name="data[mysql]" style="margin-bottom: 10px;" value="1" size="3">
	<font size="2">Domain Pointers:</font>
	<input type="number" name="data[domainptr]" style="margin-bottom: 10px;" value="1" size="3">
	<font size="2">FTP Accounts:</font>
	<input type="number" name="data[ftp]" style="margin-bottom: 10px;" value="1" size="3">
	</fieldset><br>
	<fieldset><legend>Features</legend>
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_php');">
		<span class="slider"></span>
	</label> PHP Access
	<input type="hidden" name="data[php]" id="f_php" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_spam');">
		<span class="slider"></span>
	</label> SpamAssasin
	<input type="hidden" name="data[spam]" id="f_spam" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_ssl');">
		<span class="slider"></span>
	</label> SSL Access<br>
	<input type="hidden" name="data[ssl]" id="f_ssl" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_cron');">
		<span class="slider"></span>
	</label> CRON Jobs
	<input type="hidden" name="data[cron]" id="f_cron" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_login_keys');">
		<span class="slider"></span>
	</label> Login Keys
	<input type="hidden" name="data[login_keys]" id="f_login_keys" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_sysinfo');">
		<span class="slider"></span>
	</label> System Info<br>
	<input type="hidden" name="data[sysinfo]" id="f_sysinfo" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_dnscontrol');">
		<span class="slider"></span>
	</label> DNS Control
	<input type="hidden" name="data[dnscontrol]" id="f_dnscontrol" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_suspend_at_limit');">
		<span class="slider"></span>
	</label> Suspend At Limit
	<input type="hidden" name="data[suspend_at_limit]" id="f_suspend_at_limit" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_jail');">
		<span class="slider"></span>
	</label> Jailed<br>
	<input type="hidden" name="data[jail]" id="f_jail" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_cgi');">
		<span class="slider"></span>
	</label> CGI Access
	<input type="hidden" name="data[cgi]" id="f_cgi" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_git');">
		<span class="slider"></span>
	</label> GIT Access
	<input type="hidden" name="data[git]" id="f_git" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_catchall');">
		<span class="slider"></span>
	</label> Catch-All Email<br>
	<input type="hidden" name="data[catchall]" id="f_catchall" value="OFF">
	<label class="switch">
		<input type="checkbox" onchange="toggleonoff('f_wordpress');">
		<span class="slider"></span>
	</label> WordPress<br>
	<input type="hidden" name="data[wordpress]" id="f_wordpress" value="OFF">
	</fieldset><br>
	<fieldset><legend>Localization</legend>
		<font size="2">Language:</font>
		<input type="text" name="data[language]" placeholder="en" style="margin-bottom: 10px;" size="5" required="required">
		<font size="2">Skin:</font>
		<input type="text" name="data[skin]" placeholder="evolution" style="margin-bottom: 10px;" size="20" required="required"><br>
	</fieldset>
	<hr>
	<button type="submit" class="button button-primary">Add Package</button>
</form>
</div>
<div id="package_dialog_r" title="Add RESELLER package">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="faddpackagereseller">
		<input type="hidden" name="action" value="f_add_package">
		<input type="hidden" name="data[cmd]" value="CMD_MANAGE_RESELLER_PACKAGES">
		<input type="hidden" name="data[nonce]" value="<? echo wp_create_nonce( 'wpda' ); ?>">
		<fieldset>
			<legend>Server & Package</legend>
			<font size="2">Package Name:</font><br>
			<input type="text" name=data[packagename]" placeholder="Package Name" style="margin-bottom: 10px;" size="50" required="required"><br>
			<font size="2">Server:</font><br>
			<select name="data[serverid]" style="width: 100%">
				<?
	global $wpdb;
	$table = $wpdb->prefix."da_server_list";
	$db = $wpdb->get_results("SELECT * FROM $table");
	foreach ($db as $server) {
		echo '<option value="'.$server->server_id.'">'.$server->server_friendly_name.' [#'.$server->server_id.']</option>';
	}
	?>
			</select></fieldset><br>
			<fieldset><legend>Reseller Options</legend>
				<input type="hidden" name="data[oversell]" value="OFF">
				<input type="hidden" name="data[dns]" value="ON">
				<input type="hidden" name="data[serverip]" value="ON">
				<input type="hidden" name="data[ips]" value="0">
				<font size="2">Max Users:</font>
				<input type="number" name="data[nusers]" style="margin-bottom: 10px;" size="5" required="required" value="-1"><br>
				<b>Note: </b> Set -1 for unlimited			
				
			</fieldset>
		<fieldset>
			<legend>HDD Quota</legend>
			<font size="2">Bandwidth:</font>
			<input type="text" name="data[bandwidth]" placeholder="1024MB" style="margin-bottom: 10px;" size="10" required="required"> MB
			<font size="2">Disk Quota:</font>
			<input type="text" name="data[quota]" placeholder="1024MB" style="margin-bottom: 10px;" size="10" required="required"> MB<br><b>Note:</b> Set either to -1 to set unlimited.</fieldset><br>
		<font size="2">Daily Email Limit:</font>
		<input type="number" name="data[daily_email_limit]" style="margin-bottom: 10px;" size="5" value="-1"> <b>Note:</b> -1 to set unlimited.
		<fieldset>
			<legend>Limits</legend>
			<font size="2">Domains:</font>
			<input type="number" name="data[vdomains]" style="margin-bottom: 10px;" value="1" size="3">
			<font size="2">Sub Domains:</font>
			<input type="number" name="data[nsubdomains]" style="margin-bottom: 10px;" value="1" size="3">
			<font size="2">Email Accounts:</font>
			<input type="number" name="data[nemails]" style="margin-bottom: 10px;" value="1" size="3"><br>
			<font size="2">Forwarders:</font>
			<input type="number" name="data[nemailf]" style="margin-bottom: 10px;" value="1" size="3">
			<font size="2">Mailing Lists:</font>
			<input type="number" name="data[nemailml]" style="margin-bottom: 10px;" value="1" size="3">
			<font size="2">Auto Responders:</font>
			<input type="number" name="data[nemailr]" style="margin-bottom: 10px;" value="1" size="3"><br>
			<font size="2">MySQL DB:</font>
			<input type="number" name="data[mysql]" style="margin-bottom: 10px;" value="1" size="3">
			<font size="2">Domain Pointers:</font>
			<input type="number" name="data[domainptr]" style="margin-bottom: 10px;" value="1" size="3">
			<font size="2">FTP Accounts:</font>
			<input type="number" name="data[ftp]" style="margin-bottom: 10px;" value="1" size="3">
		</fieldset><br>
		<fieldset>
			<legend>Features</legend>
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_php');">
				<span class="slider"></span>
			</label> PHP Access
			<input type="hidden" name="data[php]" id="fr_php" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_spam');">
				<span class="slider"></span>
			</label> SpamAssasin
			<input type="hidden" name="data[spam]" id="fr_spam" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_ssl');">
				<span class="slider"></span>
			</label> SSL Access<br>
			<input type="hidden" name="data[ssl]" id="fr_ssl" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_cron');">
				<span class="slider"></span>
			</label> CRON Jobs
			<input type="hidden" name="data[cron]" id="fr_cron" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_login_keys');">
				<span class="slider"></span>
			</label> Login Keys
			<input type="hidden" name="data[login_keys]" id="fr_login_keys" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_sysinfo');">
				<span class="slider"></span>
			</label> System Info<br>
			<input type="hidden" name="data[sysinfo]" id="fr_sysinfo" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_dnscontrol');">
				<span class="slider"></span>
			</label> DNS Control
			<input type="hidden" name="data[dnscontrol]" id="fr_dnscontrol" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_suspend_at_limit');">
				<span class="slider"></span>
			</label> Suspend At Limit
			<input type="hidden" name="data[suspend_at_limit]" id="fr_suspend_at_limit" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_jail');">
				<span class="slider"></span>
			</label> Jailed<br>
			<input type="hidden" name="data[jail]" id="fr_jail" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_cgi');">
				<span class="slider"></span>
			</label> CGI Access
			<input type="hidden" name="data[cgi]" id="fr_cgi" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_git');">
				<span class="slider"></span>
			</label> GIT Access
			<input type="hidden" name="data[git]" id="fr_git" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_catchall');">
				<span class="slider"></span>
			</label> Catch-All Email<br>
			<input type="hidden" name="data[catchall]" id="fr_catchall" value="OFF">
			<label class="switch">
				<input type="checkbox" onchange="toggleonoff('fr_wordpress');">
				<span class="slider"></span>
			</label> WordPress<br>
			<input type="hidden" name="data[wordpress]" id="fr_wordpress" value="OFF">
		</fieldset><br>
		<fieldset>
			<legend>Localization</legend>
			<font size="2">Language:</font>
			<input type="text" name="data[language]" placeholder="en" style="margin-bottom: 10px;" size="5" required="required">
			<font size="2">Skin:</font>
			<input type="text" name="data[skin]" placeholder="evolution" style="margin-bottom: 10px;" size="20" required="required"><br>
		</fieldset>
		<hr>
		<button type="submit" class="button button-primary">Add Package</button>
	</form>
</div>
<div class="wrap">
	<h4>Available Packages</h4>
	<button class="button button-primary" id="add_package">+ Add USER Package</button>&nbsp;
	<button class="button button-primary" id="add_package_r">+ Add RESELLER Package</button>
	<hr>
	<?php
	$wpda = new WPDirectAdmin;
	if (!empty($_SESSION['daerror'])) {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo _e($_SESSION['daerror'],$wpda->slug); ?></p>
	</div>
	<?php
	$_SESSION['daerror'] = false;
	}
	?>
	<?php $exampleListTable->display(); ?>
</div>
<?php
}
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
* Create a new table class that will extend the WP_List_Table
*/
class WP_Packages_List_Table extends WP_List_Table
{
/**
* Prepare the items for the table to process
*
* @return Void
*/
	public function prepare_items()
	{
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();
		usort( $data, array( &$this, 'sort_data' ) );

		$perPage = 10;
		$currentPage = $this->get_pagenum();
		$totalItems = count($data);

		$this->set_pagination_args( array(
		'total_items' => $totalItems,
		'per_page'    => $perPage
		) );

		$data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $data;
	}

/**
* Override the parent columns method. Defines the columns to use in your listing table
*
* @return Array
*/
	public function get_columns()
	{
		$columns = array(
		'server_title'          => 'Server',
		'type'				=> 'Package Type',
		'package'       => 'Package',
		'limits' => 'Limits',
		'options' => ''
		);

		return $columns;
	}

/**
* Define which columns are hidden
*
* @return Array
*/
	public function get_hidden_columns()
	{
		return array();
	}

/**
* Define the sortable columns
*
* @return Array
*/
	public function get_sortable_columns()
	{
		return array('last_sync' => array('last_sync', false));
	}

/**
* Get the table data
*
* @return Array
*/
	private function table_data()
	{
		global $wpdb;
		$table = $wpdb->prefix."da_server_list";
		$db = $wpdb->get_results("SELECT * FROM $table");
		
			$wpda = new WPDirectAdmin;
		//return $data;
		$data = array();

		foreach ($db as $server) {
			//get servers Packages_List_Table
			$da = $wpda->serverConn($server->server_id);
			$reseller_packages = $da->query("CMD_API_PACKAGES_RESELLER");
			//CMD_API_PACKAGES_USER
			$user_packages = $da->query("CMD_API_PACKAGES_USER");
			
			foreach ($reseller_packages as $id => $package) {
				$current_package = $da->query("CMD_API_PACKAGES_RESELLER", array(
				"package" => $package), "GET");
				
				
				$data[] = array(
				'package'          => $package,
				'server_id'		=> $server->server_id,
				'type'			=> 'RESELLER',
				'limits'		=> $current_package,
				'server_title'	=> $server->server_friendly_name,
				'USER_PACKAGE'	=> $server->USER_PACKAGE,
				'RESELLER_PACKAGE'	=> $server->RESELLER_PACKAGE
				);
			}

			foreach ($user_packages as $id => $package) {
				$current_package = $da->query("CMD_API_PACKAGES_USER", array(
				"package" => $package), "GET");
				$data[] = array(
				'package'          => $package,
				'server_id'		=> $server->server_id,
				'type'			=> 'USER',
				'limits'		=> $current_package,
				'server_title'	=> $server->server_friendly_name,
				'RESELLER_PACKAGE'	=> $server->RESELLER_PACKAGE,
				'USER_PACKAGE'	=> $server->USER_PACKAGE
				);
			}
		}
		
		$da->logout(true);
		return $data;
	}

/**
* Define what data to show on each column of the table
*
* @param  Array $item        Data
* @param  String $column_name - Current column name
*
* @return Mixed
*/
	public function column_default( $item, $column_name )
	{
		switch ( $column_name ) {
			
			case 'server_title':
			
			return $item['server_title'].' <a href="'.admin_url( 'admin.php?page=wpda&sub=serverinfo&server='.$item['server_id']).'"><b>[#'.$item['server_id'].']</b></a>';
			
			break;
			
		case "type":
		switch ($item['type']){
			
			case "USER":
			$limits = $item['limits'];
			if ($limits['quota'] == 'unlimited') {
				$quota = "Unlimited";
			} else {
				$quota = $limits['quota'] / 1024 .'GB';
			}
			return '<div class="acc_user" align="center"><b>USER</b><div id="package_info_'.$item['package'].'" align="left" style=" display: none; padding-left: 10px;"><hr><i class="fa-solid fa-database " title="MySQL Databases"> MySQL: '.$limits['mysql'].'</i><br><i class="fa-solid fa-floppy-disk" title="HDD Quota"> Quota: '.$quota.'</i><br><i class="fa-solid fa-at" title="Email POP Accounts"> POP3: '.$limits['nemails'].'</i><br><i class="fa-solid fa-globe" title="Domains"> Domains: '.$limits['vdomains'].'</i><hr></div></div>';
			break;
			
			case "RESELLER":
			$limits = $item['limits'];
			if ($limits['quota'] == 'unlimited') {
				$quota = "Unlimited";
			} else {
				$quota = $limits['quota'] / 1024 .'GB';
			}
			return '<div class="acc_user_reseller" align="center"><font color="orange"><b>RESELLER</b></font><div id="package_info_'.$item['package'].'" align="left" style=" display: none; padding-left: 10px;"><hr><i class="fa-solid fa-database " title="MySQL Databases"> MySQL: '.$limits['mysql'].'</i><br><i class="fa-solid fa-floppy-disk" title="HDD Quota"> Quota: '.$quota.'</i><br><i class="fa-solid fa-at" title="Email POP Accounts"> POP3: '.$limits['nemails'].'</i><br><i class="fa-solid fa-globe" title="Domains"> Domains: '.$limits['vdomains'].'</i><br><i class="fa-solid fa-user" title="Users"> Users: '.$limits['nusers'].'</i><hr></div></div>';
			break;
		}
		break;
			
			case "options":
			//print_r($item);
			if ($item['package'] <> $item['USER_PACKAGE'] && $item['package'] <> $item['RESELLER_PACKAGE']) {
			?>
			<p align="right"><font color="red"><a href="javascript:;" onclick="jsDeletePackage(<? echo $item['server_id']; ?>, '<? echo $item['package']; ?>', '<? echo $item['type']; ?>');"><i class="fa-solid fa-trash-can fa-xl" title="Delete Package"></i></a></font></p>
			<?
		}
			break;
			
			case "limits":
?>
<a href="javascript:;" onclick="viewPackage('package_info_<? echo $item['package']; ?>');">
	<i class="fa-solid fa-eye fa-xl"></i></a>
<?

			break;

			default:
				//return print_r( $item, true ) ;
				return $item[ $column_name ];
		}
	}

/**
* Allows you to sort the data by the variables set in the $_GET
*
* @return Mixed
*/
	private function sort_data( $a, $b )
	{
		// Set defaults
		$orderby = 'last_sync';
		$order = 'asc';

		// If orderby is set, use this as the sort column
		if (!empty($_GET['orderby'])) {
			$orderby = $_GET['orderby'];
		}

		// If order is set use this as the order
		if (!empty($_GET['order'])) {
			$order = $_GET['order'];
		}


		$result = strcmp( $a[$orderby], $b[$orderby] );

		if ($order === 'asc') {
			return $result;
		}

		return -$result;
	}
}
}
?>