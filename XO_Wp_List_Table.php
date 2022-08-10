<?php
if (!class_exists('DA_Wp_List_Table')) {
	class DA_Wp_List_Table
	{
/**
* Constructor will create the menu item
*/

		public function list_table_page()
		{
			global $wpdb;
			$exampleListTable = new DA_List_Table();
			$exampleListTable->prepare_items();
			$wpda = new WPDirectAdmin;
?>
<div id="server_set_dialog" title="Server Configuration">
	<center><img src="<? echo plugins_url('/images/loader.png', __FILE__ ); ?>" height="48px" title="Loading"></center>
</div>
<?
if ($wpda->gsl()<1) {
?>
<div id="server_dialog" title="Add new server">
Friendly Name:<br>
<input id="sadd_friendly_name" type="text" style="width: 100%" placeholder="Server Friendly Name"/><br>
Server Host:
<input id="sadd_server_host" type="text" style="width: 100%" placeholder="server.domain.name"/><br>
<b>Note:</b> No need to include https:// just the server's host or IP address.<br>
Server Port:<br>
<input id="sadd_server_port"  type="number" size="10"/><br>
Server Type:<br>
<select id="sadd_server_module" style="width: 100%;">
	<option value="da">DirectAdmin</option>
</select><br>
Admin Username:<br>
<input id="sadd_server_username" type="text" style="width: 100%" placeholder="username"><br>
Admin Password:<br>
<input id="sadd_server_password" type="password" style="width: 100%">
</div>
<?
}
?>
<div class="wrap" id="div_servers">
	<h3>
		<i class="fa-solid fa-server"></i> DirectAdmin Servers</h3>
		<?
		
		//$table = $wpdb->prefix."da_server_list";
		//$count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
		//$limit = $wpda->responseObj->server_limit;
		
		if ($wpda->gsl()>0) {
			$disabled = 'disabled="disabled"';
			$title = 'title="Server Limit Reached"';
		}
		?>
		<button class="button button-primary" id="add_server" <? echo $disabled." ".$title; ?>>+ Add Server</button>
	<?
	
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
class DA_List_Table extends WP_List_Table
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
		'server_id'          => 'Server ID',
		'server_host'       => 'Host',
		'server_status' => 'Status',
		'server_info' => ''
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

		$db = $wpdb->get_results("SELECT * from $table");

		//return $data;
		$data = array();

		foreach ($db as $rec) {

			$data[] = array(
			'server_id'          => $rec->server_id,
			'server_host'       => $rec->server_host,
			'server_port'		=> $rec->server_port,
			'server_userpass'	=> $rec->server_userpass,
			'server_friendly_name' => $rec->server_friendly_name,
			'uptime_number'	=> $rec->uptime_number,
			'uptime_interval'	=> $rec->uptime_interval
			);

		}

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
		$wp = new WPDirectAdmin;
		$userpass = $wp->getServerLogin($item['server_id']);
		$da = new DirectAdmin("https://".$item['server_host'].":".$item['server_port'], $userpass['user'], $userpass['pass'], FALSE);
		
$status = $da->query("CMD_API_ADMIN_STATS");
		switch ( $column_name ) {
			case 'server_status':
				
				if ($status['loadavg']) {
					$sysinfo = $da->query("CMD_API_SYSTEM_INFO");

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

					$services_offline = '';
					foreach ($service_mon as $s => $state) {
						if (str_contains($state, 'Stopped')) {
							if ($services_offline <> '') {
								$services_offline = $services_offline.',';
							}
							$services_offline = $services_offline . strtoupper($s);
						}
					}

					//$service_mon = serialize($service_mon);
					if ($services_offline <> '') {
						//<i class="fa-solid fa-triangle-exclamation"></i>
						return "<center><font color=\"orange\"><i class=\"fa-solid fa-triangle-exclamation fa-2xl\"></i></font><br>".$services_offline." (offline)</b></center>";						
					} else {
						$uptime = $wp->formatUptime($sysinfo['uptime']);
						
						$setting_digit = $item['uptime_number'];
						$setting_interval = $item['uptime_interval'];
						$downtime = 0;
						switch ($setting_interval) {
							case "hours":
							if ($uptime['hours']<$setting_digit) {
								$downtime = 1;
							}
							break;
							
							case "minutes":
							if ($uptime['minutes']<$setting_digit && $uptime['hours']<1) {
								$downtime = 1;
							}
							break;
							
							case "days":
							if ($uptime['days']<$setting_digit) {
								$downtime = 1;
							}
							
							break;
							
						}
						if ($downtime>0) {
							return "<center><font color=\"orange\"><i class=\"fa-solid fa-triangle-exclamation fa-2xl\"></i></font><br>Uptime is less than <b>".$setting_digit." (".strtoupper($setting_interval).")</b></b></center>";	

						} else {
							return "<center><font color=\"green\"><i class=\"fa-solid fa-play fa-3x\"></i></font></center>";
						}
					}
				} else {
					return "<font color=\"red\"><i class=\"fa-solid fa-power-off fa-2xl\"></i></font>";
				}
				
				
			case 'server_host':
				return "<b>{$item[ $column_name ]}</b><br>Port:".$item['server_port'];

			case 'server_id':
			if (!$item['server_icon']) {
				$item['server_icon'] = plugins_url("images/server_icons/server.png",$wp->plugin_file);
			}
			return "<img src=\"".$item['server_icon']."\" title=\"".$status['loadavg']."\" height=\"32px\" style=\"float: left; margin: 0px 15px 15px 0px;\"><b>{$item[ 'server_friendly_name' ]}</b><br>Server #".$item['server_id'];

			case 'server_info':
			
			if ($wp->serverTest($item['server_id'])) {
				if ($wp->gpsflu()==1 && class_exists('WPDAPro')) {
					$return =  '<center><a href="'.admin_url( 'admin.php?page=wpda&sub=users&server='.$item['server_id']).'" ><i class="fa-solid fa-users-gear fa-3x" title="View Users"></i></a>&nbsp;<a href="'.admin_url( 'admin.php?page=wpda&sub=serverinfo&server='.$item['server_id']).'" ><i class="fa-solid fa-heart-pulse fa-3x" title="Server Information"></i></a>&nbsp;<a href="'.admin_url( 'admin.php?page=wpda_backups&server='.$item['server_id']).'"><i class="fa-solid fa-file-shield fa-3x" title="Backup Manager"></i></a>&nbsp;<a href="javascript:;" onclick="jsServerSet('.$item['server_id'].');"><i class="fa-solid fa-sliders fa-3x" title="Server Settings"></i></a>&nbsp;<font color="red"><i class="fa-solid fa-plug-circle-bolt fa-3x" title="Reboot Server" onclick="jsRebootServer('.$item['server_id'].')"></i></font>';
				} else {
					$return = '<center><a href="'.admin_url( 'admin.php?page=wpda&sub=users&server='.$item['server_id']).'" ><i class="fa-solid fa-users-line fa-2xl" title="View Users"></i></a>&nbsp;<a href="'.admin_url( 'admin.php?page=wpda&sub=serverinfo&server='.$item['server_id']).'" ><i class="fa-solid fa-circle-info fa-2xl" title="Server Information"></i></a>&nbsp;<a href="'.admin_url( 'admin.php?page=wpda_backups&server='.$item['server_id']).'"><i class="fa-solid fa-file-zipper fa-2xl" title="Backup Manager"></i></a>&nbsp;<a href="javascript:;" onclick="jsServerSet('.$item['server_id'].');"><i class="fa-solid fa-gear fa-2xl"></i></a>';
				}
				
				$return = $return .'&nbsp;<a href="javascript:;" onclick="jsRemoveServer('.$item['server_id'].')"><i class="fa-solid fa-delete-left fa-3x" title="Remove Server"></i></a></center>';
				return $return;
			}
			
		case 'alarms':
		
		$sysinfo = $da->query("CMD_API_SYSTEM_INFO");

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
		
		$services_offline = '';
		foreach ($service_mon as $s => $state) {
			if (str_contains($state, 'Stopped')) {
				if($services_offline <> '') { 
					$services_offline = $services_offline.','; 
					}
				$services_offline = $services_offline . strtoupper($s);		
			}
		}
		
		//$service_mon = serialize($service_mon);
		if ($services_offline <> '') {
			?>
			<font Color="red"><i class="fa-solid fa-microchip fa-2xl" title="<? echo $services_offline; ?> (offline)"></i></font>
			
			<?
		}
		
		//alerm if any are not running
		
		
		break;

			default:
				//return print_r( $item, true ) ;
				return $item[ $column_name ];
		}
		$da->logout(true);
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