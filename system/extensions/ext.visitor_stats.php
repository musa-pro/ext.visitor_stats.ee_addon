<?php
	date_default_timezone_set('Australia/Sydney');
	class Visitor_stats{
	
		var $settings = array();
		var $name = 'Visitor Statistics Extension';
		var $version = '1.0.0';
		var $description = 'Store visitor information with referer, time and other info';		
		var $settings_exist = 'n';
		var $docs_url = '';
		
		function Visitor_stats($settings=''){
			global $SESS;
			$this->settings = $settings;

		}
		
		function activate_extension(){
		    global $DB;
					    
		    $DB->query($DB->insert_string('exp_extensions',
		                                  array(
		                                        'extension_id' => '',
		                                        'class'        => "Visitor_stats",
		                                        'method'       => "save_visit_data",
		                                        'hook'         => "sessions_end",
		                                        'settings'     => "",
		                                        'priority'     => 10,
		                                        'version'      => $this->version,
		                                        'enabled'      => "y"
		                                      )
		                                 )
		              );

		}
		
		function disable_extension()
		{
		    global $DB;
		    $DB->query("DELETE FROM exp_extensions WHERE class = 'Visitor_stats'");
		}

		//Save visit info
		function save_visit_data($SESS){
			global $IN, $FNS, $DB, $LOC, $PREFS;
			
			
			
			$page_lock_time = 15;	//in minutes

			//$LOC->zones[$PREFS->core_ini['server_timezone']];
			//$current_local_time = $LOC->server_now+$LOC->zones[$PREFS->core_ini['server_timezone']]*60*60;

			if(empty($IN->QSTR)) return;

//			$current_page = $FNS->fetch_current_uri();			
			$current_page = $PREFS->core_ini['site_url'] . substr($_SERVER['REQUEST_URI'],1);		
//			$referrer_page = $FNS->create_url($SESS->tracker[1]);
			$referrer_page = $PREFS->core_ini['site_url'] .(stristr($current_page,'index.php')?'index.php/':''). substr($SESS->tracker[1],1);

			//Check if page need to track
	        $sql = "SELECT page_id, title, url FROM exp_visitstats_pages WHERE url='".$current_page."'";
	        $query = $DB->query($sql);
	        if($query->num_rows==0)
	        	return;

			//Check if he is visited that page within fifteen minutes	        
	        $accessed_time = date("Y-m-d H:i:s", ( time() - $page_lock_time*60));

			$sql = "SELECT title, url, entry_date FROM exp_visitstats WHERE url='".$current_page."' AND user_agent='".$SESS->sdata['user_agent']."' AND member_id='".$SESS->sdata['member_id']."' AND ip='".$SESS->sdata['ip_address']."' AND entry_date>'$accessed_time'";
	        $exist_query = $DB->query($sql);
	        
	        //visited within fifteen minutes that page, so do not store
	        if($exist_query->num_rows==1)
	        	return;
			
			$entry_datetime = date("Y-m-d H:i:s", time() );
      		$visit_date = date("Y-m-d", time() );
			$visit_time = date("H:i:s", time() );

			//Get the time slot
			$sql = "SELECT timeslot_id, time_start, time_end FROM exp_visitstats_timeslot WHERE '$visit_time'>time_start AND '$visit_time'<=time_end ";
			$timeslot_query = $DB->query($sql);
			
			$sql = "INSERT INTO exp_visitstats 
						(title, url, referrer_url, user_agent, member_id, ip, visit_date, visit_time, timeslot_id, entry_date) 											VALUES ('".$query->row['title']."', '$current_page',  '$referrer_page' , '".$SESS->sdata['user_agent']."'  , ".$SESS->sdata['member_id'].", '".$SESS->sdata['ip_address']."', '$visit_date', '$visit_time', ".$timeslot_query->row['timeslot_id'].", '$entry_datetime')";
			
			$DB->query($sql);

		}
	
	}
?>