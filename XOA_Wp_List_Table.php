<?php
if (!class_exists('DAA_Wp_List_Table')) {
	class DAA_Wp_List_Table
	{
/**
* Constructor will create the menu item
*/

		public function list_table_page()
		{
			global $wpdb;
			
			$wp = new WPDirectAdmin();
			if ($wp->serverTest($_REQUEST['server'])) {
				$exampleListTable = new DAA_List_Table();
				$exampleListTable->prepare_items();
			} else {
				$_SESSION['daerror'] = '<b>Cannot connect to server</b>, is it online?';
			}
			?>
			<style>
				.acc_user{
					background: #faffc0;
					border: #ffffff solid 2px;
					border-radius: 10px;
				}
				.acc_user_adm{
					background:#ffd2d2;
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
			<div id="dialog_user_passwd" title="Change Account Password">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="user_passwd_form">
					<input type="hidden" name="action" value="user_passwd">
					<input type="hidden" name="data[server]" value="" id="data_server">
					<input type="hidden" name="data[nonce]" value="<? echo wp_create_nonce( 'wpda' ); ?>">
				Username:<br>
				<input type="text" style="width: 100%" readonly="readonly" name="data[user]" id="data_user"><br>
				Password:<br>
				<a href="javascript:;" onclick="randomPass('passwdchng');">
					<i class="fa-solid fa-key fa-xl" title="Generate Password""></i></a>&nbsp;
				<a href="javascript:;" onclick="togPass('passwdchng');">
					<i class="fa-solid fa-eye fa-xl" title="View Password"></i></a>&nbsp;<input type="password" name="data[passwdchng]" id="passwdchng" style="width: 80% !important;" /><br>
					<input type="checkbox" value="NO" name="data[notify]" onclick="toggleTick('notify_tick');" id="notify_tick"> Notify the user?
					
				</form>
			</div>
			<div id="add_user_to_wp" title="Link WordPress Account">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wp_link">
					<input type="hidden" name="action" value="link_user">
					<input type="hidden" name="data[nonce]" value="<? echo wp_create_nonce( 'wpda' ); ?>">
					<input type="hidden" id="wplinkuser" name="data[user]">
					<input type="hidden" id="wplinkserver" name="data[server]">
					<select id="wpuser" name="data[wpuser]" style="width:100% !important;">
						<?
			$table = $wpdb->prefix."users";
			$db = $wpdb->get_results("SELECT * FROM $table");

			foreach ($db as $user) {
				$disabled = '';
				if ($user->user_nicename == 'admin') {
					$disabled = 'disabled="disabled"';
				}

				echo '<option '.$disabled.' value="'.$user->ID.'">'.$user->display_name.' ('.$user->user_nicename.')</option>';
			}
			?>
					</select>
				</form>
			</div>
			<div id="dialog_add_user" title="Add User">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="add_user">
					<input type="hidden" name="data[notify]" value="no">
					<input type="hidden" name="data[add]" value="yes">
					<input type="hidden" name="data[action]" value="create">
					<input type="hidden" name="data[nonce]" value="<? echo wp_create_nonce( 'wpda' ); ?>">

					WordPress User:<br>
					<select id="wpuser" name="data[wpuser]" style="width:100% !important;">
						<?
			$table = $wpdb->prefix."users";
			$db = $wpdb->get_results("SELECT * FROM $table");

			foreach ($db as $user) {
				$disabled = false;
				if ($user->user_nicename == 'admin') {
					$disabled = 'disabled="disabled"';
				}
				echo '<option '.$disabled.' value="'.$user->ID.'">'.$user->display_name.' ('.$user->user_nicename.')</option>';
			}
			?>
					</select><br>
					Password:<br>
					<a href="javascript:;" onclick="randomPass('passwd');">
						<i class="fa-solid fa-key fa-xl" title="Generate Password""></i></a>&nbsp;
					<a href="javascript:;" onclick="togPass('passwd');">
						<i class="fa-solid fa-eye fa-xl" title="View Password"></i></a>&nbsp;<input type="password" name="data[passwd]" id="passwd" style="width: 80% !important;" /><br>
					Domain Name:<br>
					<input type="text" name="data[domain]" placeholder="domain.com" style="width: 100% !important;">
					<br>
					Server:<br>
					<select id="serverselect" name="data[serverid]" style="width: 100% !important;" onchange="getPackages();">
						<option value="0">- Select A Server -</option>
						<?
			$table = $wpdb->prefix."da_server_list";
			$db = $wpdb->get_results("SELECT * FROM $table");

			foreach ($db as $server) {
				echo '<option value="'.$server->server_id.'">'.$server->server_friendly_name.' [#'.$server->server_id.']</option>';
			}
			?>
					</select><br>
					<div id="ajax" style="display: none;">
					<br>
						<center><img height="32px" src="<? echo plugins_url('/images/loader.png', __FILE__); ?>" title="Loading"></center>
					</div>
					<br><br>
					<button class="button button-primary" type="submit" id="sub_add" disabled="disabled">Add User</button>
				</form>
			</div>
<?
$wp = new WPDirectAdmin;
if ($wp->serverTest($_REQUEST['server'])) {
	?>
	<div class="wrap">
	<h4>List Users</h4>
	<button class="button button-primary" id="add_user">+ Add User</button>
	<?php $exampleListTable->display(); ?>
</div>
<?php
} else {

	$wpda = $wp;
	if (!empty($_SESSION['daerror'])) {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo _e($_SESSION['daerror'],$wpda->slug); ?></p>
	</div>
	<?php
	$_SESSION['daerror'] = false;
	}

}
}
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
* Create a new table class that will extend the WP_List_Table
*/
class DAA_List_Table extends WP_List_Table
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
		'user'          => 'User',
		'domain'		=> 'Main Domain',
		'type'		=> 'Account Type',
		'owner'       => 'Owner',
		'package' => 'Package',
		'wordpress' => 'WordPress',
		'options'	=> 'Options'
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
		$server = sanitize_text_field($_REQUEST['server']);

		$wp = new WPDirectAdmin;
		$userpass = $wp->getServerLogin($server);

		$server_host = $wpdb->get_var("SELECT server_host from $table where server_id=".$server);
		$server_port = $wpdb->get_var("SELECT server_port from $table where server_id=".$server);

		$da = new DirectAdmin("https://".$server_host.":".$server_port, $userpass['user'], $userpass['pass'], FALSE);
		$resellers = $da->query("CMD_API_SHOW_RESELLERS");
		$users = $da->query("CMD_API_SHOW_ALL_USERS");
		$admins = $da->query("CMD_API_SHOW_ADMINS");
		
		//var_dump($users);
		//var_dump($resellers);
		//$da->logout(true);

		//return $data;
		$data = array();
		foreach ($admins as $rec => $reseller) {
			// 	CMD_API_SHOW_USER_CONFIG

			$account_info = $da->query("CMD_API_SHOW_USER_CONFIG", array(
			"user" => $reseller), "GET");
			
			//$user_info = $da->query('CMD_API_SHOW_USER_CONFIG?user='.$reseller);
			$user_info = $account_info;
			
			
			//print_r($account_info);
			$data[] = array(
			'user'          => $reseller,
			'type'			=> 'ADMIN',
			'owner'			=> $account_info['creator'],
			'domain'		=> $account_info['domain'],
			'package'		=> $account_info['package'],
			'suspended'		=> $account_info['suspended'],
			'server'		=> $server,
			'user_info'		=> $user_info
			);

		}
		
		foreach ($resellers as $rec => $reseller) {
			// 	CMD_API_SHOW_USER_CONFIG
			
			$account_info = $da->query("CMD_API_SHOW_USER_CONFIG", array(
			"user" => $reseller
			), "GET");
			//$user_info = $da->query('CMD_API_SHOW_USER_CONFIG?user='.$reseller);
			$user_info = $account_info;
			//print_r($account_info);
			$data[] = array(
			'user'          => $reseller,
			'type'			=> 'RESELLER',
			'owner'			=> $account_info['creator'],
			'domain'		=> $account_info['domain'],
			'package'		=> $account_info['package'],
			'suspended'		=> $account_info['suspended'],
			'server'		=> $server,
			'user_info'		=> $user_info
			);

		}
		foreach ($users as $rec => $reseller) {
			$account_info = $da->query("CMD_API_SHOW_USER_CONFIG", array(
			"user" => $reseller
			), "GET");
			//$user_info = $da->query('CMD_API_SHOW_USER_CONFIG?user='.$reseller);
			$user_info = $account_info;
			$data[] = array(
			'user'          => $reseller,
			'type'			=> 'USER',
			'owner'			=> $account_info['creator'],
			'domain'		=> $account_info['domain'],
			'package'		=> $account_info['package'],
			'suspended'		=> $account_info['suspended'],
			'server'		=> $server,
			'user_info'		=> $user_info
			);

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
			
			case 'wordpress':
			$wpda = new WPDirectAdmin;
			if ($wpda->hasWPLink($item['user'], $item['server'])<1) {
				if ($item['user'] <> 'admin') {
			?>
			<a href="javascript:;" onclick="jsLinktoWP('<? echo $item['user']; ?>', <? echo $item['server']; ?>);">
	<i class="fa-solid fa-link fa-lg" title="Link To A WordPress Account"></i></a>
				<?
			}
				?>
<i class="fa-brands fa-wordpress fa-lg" title="WordPress Account Link"></i> Not Linked
			<?
		} else {
			?>
 <a href="javascript:;" onclick="jsLinktoWPD('<? echo $item['user']; ?>', <? echo $item['server']; ?>, <? echo $wpda->hasWPLink($item['user'], $item['server']); ?>);">
	<font color="#ce0000"><i class="fa-solid fa-link-slash fa-lg" title="Remove Link To WordPress Account"></i></font></a>

 <font color="green"><i class="fa-brands fa-wordpress fa-lg" title="WordPress Account Link"></i> Linked</font>
			<?
		}

		break;
			case 'package':
			$found = false;
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
					'server_title'	=> $server->server_friendly_name
					);
					
					if ($package == $item['package']) {
						$found = true; }
				}

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
					if ($package == $item['package']) {
						$found = true; }
				}
			}

			$da->logout(true);
			
			if (!$found && !($item['package'] == 'admin')) {
				?>
 <font color="orange"><i class="fa-solid fa-triangle-exclamation fa-lg" title="Package Not Found!"></i></font>&nbsp;
 <?
 
 switch($item['type']){
 	
 	case "USER":
	 if ($server->USER_PACKAGE <> '') {
		 $canswitch = true;
		 $switchpack = $server->USER_PACKAGE;
		 }
	 break;
	 
	 case "RESELLER":
	 if ($server->RESELLER_PACKAGE <> '') {
		 $canswitch = true;
		 $switchpack = $server->RESELLER_PACKAGE;
	 }	 
	 break;
 }
 if ($canswitch) {
 ?>
 <a href="javascript:;" onclick="jsSwitchPackage(<? echo $item['server']; ?>, '<? echo $item['user']; ?>', '<? echo $item['type']; ?>');">
	 <i class="fa-solid fa-shuffle fa-lg" title="Switch to default '<? echo $item['type']; ?>' package (<? echo $switchpack; ?>)"></i></a>&nbsp;
 <?
 }
				//we could switch at this point or offer a switch package feature
			}
			echo $item['package'];

			
			break;
			case 'type':
			
			if ($item['type'] == 'ADMIN') {
				$type = '<div class="acc_user_adm" align="center"><font color="red"><b>ADMIN</b></font></div>';
			} else {
				$type = '<div class="acc_user" align="center"><b>'.$item['type'].'</div></b>';
			}
			if ($item['type'] == 'RESELLER') {
				$type = '<div class="acc_user_reseller" align="center"><font color="orange"><b>RESELLER</b></font></div>';
			}
			return $type;
			
			case 'options':
			if ($item['user'] <> 'admin') {
			?>
			<font color="#c80000"><a href="javascript:;" onclick="jsDeleteUser('<? echo $item['user']; ?>', <? echo $item['server']; ?>);"><i class="fa-solid fa-trash-can fa-2xl" title="Delete User"></i></a></font>
 <a href="javascript:;" onclick="jsChangeUserPassWd('<? echo $item['user']; ?>', <? echo $item['server']; ?>);">
	<i class="fa-solid fa-fingerprint fa-2xl" title="Change Password"></i></a>
 <?
 if($item['suspended'] == 'yes'){
 	?>
 <font color="red"><a href="javascript:;" onclick="jsUnlockUser('<? echo $item['user']; ?>', <? echo $item['server']; ?>);">
<i class="fa-solid fa-unlock fa-2xl" title="Unlock User"></i></a></font>
 	<?
 } else {
 	?>
 <a href="javascript:;" onclick="jsLockUser('<? echo $item['user']; ?>', <? echo $item['server']; ?>);">
<i class="fa-solid fa-lock fa-2xl" title="Suspend User" style="font: red !important;"></i></a>
 	<?
 }
 
 ?>
			<?
		}	
			break;
			
		case 'user':

			if ($item['suspended'] == 'yes') {
				$user = '<img src="'.plugins_url('/images/warning.png', __FILE__).'" title="Suspended"><b><span title="'.$item['user_info']['email'].'"><i class="fa-solid fa-circle-info"></i>&nbsp;'.$item['user'].'</span></b>';
			} else {
				$user = '<b><span title="'.$item['user_info']['email'].'"><i class="fa-solid fa-circle-info">&nbsp;</i>'.$item['user'].'</span></b>';
			}
			return $user;
			break;
	
			default:
				//return print_r( $item, true ) ;
				return $item[ $column_name ];
				break;
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