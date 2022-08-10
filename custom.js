function toggleTick(toggle){
	jQuery(document).ready(function($) {
		var val = $("#"+toggle).val();
		if (val == 'NO') {
			$("#"+toggle).val('YES');
		} else {
			$("#"+toggle).val('NO');
		}
	});	
}
function jsSetLogging(log){
	jQuery(document).ready(function($) {
		
		//ui-checkboxradio-checked ui-state-active
		$("#r_all").removeClass('ui-checkboxradio-checked ui-state-active');
		$("#r_none").removeClass('ui-checkboxradio-checked ui-state-active');
		$("#r_warn").removeClass('ui-checkboxradio-checked ui-state-active');
		$("#r_error").removeClass('ui-checkboxradio-checked ui-state-active');
		
		
		$("#r_"+log).addClass('ui-checkboxradio-checked ui-state-active');
		//now change the log level
		var data = {
			'action': 'save_log_setting',
			'nonce': wpda.nonce,
			'logtype': log
		};

		jQuery.post(wpda.ajaxurl, data, function(response) {
			//document.location.href='';
		});		
		
	});	
}
function viewPackage(elem){
	jQuery(document).ready(function($) {
		$("#"+elem).slideToggle();
	});
}
function jsRemoveServer(server)
{
	jQuery(document).ready(function($) {
		var data = {
			'action': 'remove_server',
			'nonce': wpda.nonce,
			'server': server
		};

		jQuery.post(wpda.ajaxurl, data, function(response) {
			document.location.href='';
		});
	});
}
function jsUnlockUser(user, server){
		jQuery(document).ready(function($) {
				var data = {
				'action': 'unlock_user',
				'nonce': wpda.nonce,
				'server': server,
				'user': user
			};

			jQuery.post(wpda.ajaxurl, data, function(response) {
				document.location.href='';
			});
		});	
}
function jsRestartService(service, server){
	jQuery(document).ready(function($) {
		$("#"+service+"_"+server).html('<font color="orange"><i class="fa-solid fa-hourglass"></i></i> Restarting...</font>');
		
	var data = {
			'action': 'restart_service',
			'nonce': wpda.nonce,
			'server': server,
			'service': service
				};

		jQuery.post(wpda.ajaxurl, data, function(response) {
			$("#"+service+"_"+server).html('<font color="orange"><i class="fa-solid fa-hourglass"></i></i> Checking Status...</font>');
			
			
	
	setTimeout(function(){
		var data = {
				'action': 'check_service',
				'nonce': wpda.nonce,
				'server': server,
				'service': service
			};

			jQuery.post(wpda.ajaxurl, data, function(response) {
				if (response == 1) {

					$("#"+service+"_"+server).html('<font color="green"><i class="fa-solid fa-square-check"></i></i> Running</font>');
				} else {
					$("#"+service+"_"+server).html('<font color="red"><i class="fa-solid fa-rectangle-xmark"></i> Stopped</font>');
				}
			});
		}, 5000);

	});
		});
}
function jsLockUser(user, server)
{
	jQuery(document).ready(function($) {
		var data = {
			'action': 'lock_user',
			'nonce': wpda.nonce,
			'server': server,
			'user': user
		};

		jQuery.post(wpda.ajaxurl, data, function(response) {
			document.location.href='';
		});
	});
}
function jsRebootServer(server){
		jQuery(document).ready(function($) {
			
			//disable the server div
			$('#div_servers').fadeTo('slow',.6);
			$('#div_servers').append('<div style="position: absolute;top:0;left:0; width: 100%;height:100%;z-index:2;opacity:0.4;filter: alpha(opacity = 50)"><center><img src="'+wpda.loader+'"></center></div>');
			
		var data = {
				'action': 'reboot_server',
				'nonce': wpda.nonce,
				'server': server
			};

			jQuery.post(wpda.ajaxurl, data, function(response) {
	
			});
			setTimeout(function(){
					document.location.href='';
				}, 60000);
		});	
}
function randomPass(fld){
		jQuery(document).ready(function($) {
	//get_random_password
	
		var data = {
		'action': 'get_random_password'
	};

	jQuery.post(wpda.ajaxurl, data, function(response) {
		 var x = document.getElementById(fld);
		x.value=response;
	});
		});
}
function jsChangeUserPassWd(user, server){
		jQuery(document).ready(function($) {
			$("#data_user").val(user);
			$("#data_server").val(server);
			$("#dialog_user_passwd").dialog("open");	
		});
	// $("#dialog_user_passwd").dialog
}
function togPass(passwd){
	  var x = document.getElementById(passwd);
	if (x.type === "password") {
		x.type = "text";
	} else {
		x.type = "password";
	}
	setTimeout(function(){
		var x = document.getElementById(passwd);

			x.type = "password";
	
	}, 5000);
}
function getPackages(){
jQuery(document).ready(function($) {
	$("#ajax").show();
	$("#sub_add").attr("disabled", true);
	var server = $("#serverselect").val();
		var data = {
		'action': 'get_packages',
		'server': server
	};

	jQuery.post(wpda.ajaxurl, data, function(response) {
		$("#ajax").html(response);
		if(server>0){
		$("#sub_add").attr("disabled", false);
					}
	});	
	
});


}
function toggleonoff(toggle){
	jQuery(document).ready(function($) {
		var val = $("#"+toggle).val();
		if (val == 'OFF') {
			$("#"+toggle).val('ON');
			} else {
			$("#"+toggle).val('OFF');
			}
	});	
}
function jsDeleteUser(user, server){
jQuery(document).ready(function($) {
		var data = {
		'action': 'delete_user',
		'user': user,
		'server': server,
		'nonce': wpda.nonce
	};

	jQuery.post(wpda.ajaxurl, data, function(response) {
	document.location.href='';
	});
	
});
}
function jsSwitchPackage(server, user, usertype)
{
	jQuery(document).ready(function($) {

		var data = {
			'action': 'switch_to_default',
			'server': server,
			'user': user,
			'usertype': usertype,
			'nonce': wpda.nonce
		};

		jQuery.post(wpda.ajaxurl, data, function(response) {
			document.location.href='';
		});

	});
}
function jaxSetting(setting){

	jQuery(document).ready(function($) {
		
		var val = $("#"+setting).val();
			var data = {
				'action': 'update_settings',
				'setting': setting,
				'value': val,
				'nonce': wpda.nonce
			};

			jQuery.post(wpda.ajaxurl, data, function(response) {
				document.location.href='';
			});
		
	});	
}
function jsDeletePackage(server, packageid, type){
	jQuery(document).ready(function($) {
		var data = {
			'action': 'delete_package',
			'package': packageid,
			'server': server,
			'type': type,
			'nonce': wpda.nonce
		};

		jQuery.post(wpda.ajaxurl, data, function(response) {
			document.location.href='';
		});
	});	
}
function jsLinktoWPD(user, server, wpuid)
{
	jQuery(document).ready(function($) {
			var data = {
			'action': 'remove_link',
			'user': user,
			'server': server,
			'wpuid': wpuid,
			'nonce': wpda.nonce
		};

		jQuery.post(wpda.ajaxurl, data, function(response) {
			document.location.href='';
		});

	});
}

function jsLinktoWP(user, server){
jQuery(document).ready(function($) {
	$("#add_user_to_wp").dialog("open");
	$("#wplinkuser").val(user);
	$("#wplinkserver").val(server);
	
		});	
}
jQuery(document).ready(function($) {
	  $("#server_set_dialog").dialog({
		autoOpen: false,
		modal: true,
		width: "40%",
		buttons: {
			Cancel: function() {
				$( this ).dialog( "close" );
				var data = {
					'action': 'reset_window'
				};

				jQuery.post(wpda.ajaxurl, data, function(response) {
					$("#server_set_dialog").html(response);
				});
			},
			Save: function() {
				$("#update_server").submit();
			}

		}
	});
	//dialog_user_passwd
	  $("#dialog_user_passwd").dialog({
		autoOpen: false,
		modal: true,
		width: "50%",
		buttons: {
			Cancel: function() {
				$( this ).dialog( "close" );
			},
			Save: function() {
				$("#user_passwd_form").submit();
			}

		}
	});	
	//pop_view_users
	
	  $("#pop_view_users").dialog({
		autoOpen: false,
		modal: true,
		width: "40%"
	});
	//add_user_to_wp
	  $("#add_user_to_wp").dialog({
		autoOpen: false,
		modal: true,
		width: "40%",	
		buttons: {
			Cancel: function() {
				$( this ).dialog( "close" );
			},
			Link: function() {
				$("#wp_link").submit();
			}
			
				}
	});		
	 $("#package_dialog").dialog({
	 	autoOpen: false,
	 	modal: true,
		width: "40%",
		 buttons: {
			 Cancel: function(){
				$( this ).dialog( "close" );
			}
				}
	});	 	
	 $("#package_dialog_r").dialog({
		autoOpen: false,
		modal: true,
		width: "40%",
		buttons: {
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}
	});	 	
	 $("#dialog_add_user").dialog({
		autoOpen: false,
		modal: true,
		width: "40%",
		buttons: {
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}
	});		 	
	  $("#server_dialog").dialog({
		autoOpen: false,
		modal: true,
		width: "60%",
		      buttons: {
			Create: function() {
				
				//form validation
				var server_friendly_name = $("#sadd_friendly_name").val();
				var server_host = $("#sadd_server_host").val();
				var server_port = $("#sadd_server_port").val();
				var server_username = $("#sadd_server_username").val();
				var server_password = $("#sadd_server_password").val();
				var server_module = $("#sadd_server_module").val();
				
				var err = false;

				if (!server_password || server_password == '') {
					err = true;
					$("#sadd_server_password").focus();
				}				
				if (!server_username || server_username == '') {
					err = true;
					$("#sadd_server_username").focus();
				}				
				if (!server_port || server_port == '') {
					err = true;
					$("#sadd_server_port").focus();
				}					
				if (!server_host || server_host == '') {
					err = true;
					$("#sadd_server_host").focus();
				}					
				if (!server_friendly_name || server_friendly_name == '') {
					err = true;
					$("#sadd_friendly_name").focus();
				}	
				if(!err){
				$( this ).dialog( "close" );
				//submit to ajax
				var data = {
					'action': 'add_server',
					'server_friendly_name': server_friendly_name,
					'server_host': server_host,
					'server_port': server_port,
					'server_username': server_username,
					'server_password': server_password,
					'server_module': server_module
				};

				jQuery.post(wpda.ajaxurl, data, function(response) {
					document.location.href='';
				});
				//refresh the page
						}
			}
		}
	});
	$("#add_user").click(function() {
		$("#dialog_add_user").dialog("open");
		return false;

	});
	$("#add_server").click(function(){
		    $("#server_dialog").dialog("open");
		return false;
		
	});
	$("#add_package").click(function() {
		$("#package_dialog").dialog("open");
		return false;

	});	
	$("#add_package_r").click(function() {
		$("#package_dialog_r").dialog("open");
		return false;

	});

});
function jsServerSet(server)
{
	jQuery(document).ready(function($) {
		$("#server_set_dialog").dialog("open");
		
				var data = {
			'action': 'get_server',
			'server': server
		};

		jQuery.post(wpda.ajaxurl, data, function(response) {
			$("#server_set_dialog").html(response);
		});


	});
}
