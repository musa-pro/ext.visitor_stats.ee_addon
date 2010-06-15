<?php

/*
  =====================================================  
   File: mcp.visitstats.php
  -----------------------------------------------------
   Purpose: Visitor Stats class - CP
  =====================================================
*/
 
 if ( ! defined('EXT'))
 {
    exit('Invalid file request');
 }
 
 class Visitstats_CP{
 
      var   $version      = '1.0';
      var	$row_limit		= 30; // Used for pagination
      var	$horizontal_nav	= TRUE;
      
      /** -------------------------
      /**  Constructor
      /** -------------------------*/
      
      function VisitStats_CP( $switch = TRUE )
      {
          global $IN, $DB, $DSP, $LANG;
          
          /** -------------------------------
          /**  Is the module installed?
          /** -------------------------------*/
          
          $query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Visitstats'");
          
          if ($query->row['count'] == 0)
          {
          	return;
          }
          
          /** -------------------------------
          /**  Assign Base Crumb
          /** -------------------------------*/
          
          $DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=Visitstats', $LANG->line('visitstats_module_name'));        
          
          if ($switch)
          {
              switch($IN->GBL('P'))
              {
                  case 'entry_form'                : $this->entry_form();
                        break;
                  case 'insert_new_entry'          : $this->insert_new_entry();
                        break;                        
                  case 'update_entry'   		       :  $this->update_entry();
                        break;
                  case 'multi_edit_entries'        :  $this->multi_edit_entries();
                        break;            
                  case 'view_entries'   			     :  $this->view_entries();
                        break;
                  case 'visitstats_home'                :  $this->visitstats_home();
                        break;                                    
                  default       			       :  $this->visitstats_home();
                        break;
              }
          }
          
      }
      /* END */
      
      /**------------------------------
      /**   Visit stats home, show the latest visits
      /**------------------------------*/
                  
      function visitstats_home($msg='')
      {        

		 global $DSP, $IN, $DB, $LANG, $FNS, $LOC, $PREFS;

		/** ------------------------------------
		/**  Page heading/crumb/title
		/** ------------------------------------*/
    		
        $title  = $LANG->line('visitstats_module_name');
    	$crumb = $LANG->line('visitstats_home');
    		
    	$r = '';
    	
    	$page = $IN->GBL('url');
        if(empty($page)){
        	$sql = "SELECT url FROM exp_visitstats_pages ORDER BY page_id ASC LIMIT 0,1";
	        $page_query = $DB->query($sql);
	        if ($page_query->num_rows == 1)
	        	$page = $page_query->row['url'];
			else $page = '';
        }
        
        $sel_date = $IN->GBL('sel_date');
        if(empty($sel_date)){
        	$sel_date = date("Y-m-d", ($LOC->server_now) );
        }
        
		$sql = "SELECT exp_visitstats.title, exp_visitstats.url, exp_visitstats.visit_date, exp_visitstats_timeslot.time_start, exp_visitstats_timeslot.time_end, count(*) as count FROM exp_visitstats 
		LEFT JOIN exp_visitstats_timeslot ON exp_visitstats.timeslot_id=exp_visitstats_timeslot.timeslot_id   
		WHERE exp_visitstats.url='$page' AND exp_visitstats.visit_date='$sel_date' 
		GROUP BY (exp_visitstats.timeslot_id) ORDER BY  exp_visitstats_timeslot.time_start DESC";
        
        $query = $DB->query($sql);

    		/** -----------------------------
    		/**  Do we need pagination?
    		/** -----------------------------*/
		
    		$paginate = '';
    		
    		if ($query->num_rows > $this->row_limit)
    		{ 
    			$row_count = ( ! $IN->GBL('row')) ? 0 : $IN->GBL('row');    						
    			$base_url = BASE.AMP.'C=modules'.AMP.'M=visitsats'.AMP.'P=visitstats_home';
    			$paginate = $DSP->pager(  $base_url,
    									  $query->num_rows, 
    									  $this->row_limit,
    									  $row_count,
    									  'row'
    									);
    			$sql .= " LIMIT ".$row_count.", ".$this->row_limit;
    			$query = $DB->query($sql);    
    		}

    		/** ------------------------------
    		/**  Build the output
    		/** ------------------------------*/
    		        
        if ($PREFS->ini('time_format') == 'us')
    		{
    			$datestr = '%m/%d/%y %h:%i %a';
    		}
    		else
    		{
    			$datestr = '%Y-%m-%d %H:%i';
    		}
                    

        // Build the output		
		    $nav = $this->nav(	array(
									'visitstats_home'			       => array('P' => 'visitstats_home'),
									'visitstats_new_entry'			   => array('P' => 'entry_form'),								
									'visitstats_view_entries'		 => array('P' => 'view_entries', 'mode' => 'view')
								)
				);
				

    		if ($nav != '')
    		{
    			$r .= $nav;
    		}
        
        
    	// If there are no entries yet we'll show an error message    
        if ($query->num_rows == 0)
        {          
    			$r  .= $DSP->qdiv('box', $DSP->qdiv('highlight', $LANG->line('no_entries')));			    
			    return $this->content_wrapper($title, $crumb, $r);              			
        }

        /** ------------------------------
        /**  Form to Filter entries
        /** ------------------------------*/
            
        $r .=	  $DSP->toggle().   
        		    $DSP->form_open(
        		    
                						array(
                								'action' => 'C=modules'.AMP.'M=visitstats'.AMP.'P=visitstats_home', 
                								'name'	=> 'target',
                								'id'	=> 'target',
                							)
            					  );

        /** ------------------------------
        /**  Table Header
        /** ------------------------------*/
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
                  	  $DSP->tr().
                  	  $DSP->td('tableHeading', '', '').$LANG->line('visitstats_stats').$DSP->td_c().                  	  
                      $DSP->td('tableHeading', '', '').$LANG->line('url');

	    $pages_sql = "SELECT page_id, title, url FROM exp_visitstats_pages ORDER BY page_id ASC";
	    $pages_query = $DB->query($pages_sql);


        $r .= NBS.$DSP->input_select_header('url');

    		foreach ($pages_query->result as $row)
    		{		
        		$r .= $DSP->input_select_option($row['url'], $row['title'], $page==$row['url']?'yes':null );
        	}	

        $r .= $DSP->input_select_footer();  
        $r .= NBS.$LANG->line('select_date').NBS.$DSP->input_text('sel_date', $sel_date, '105', '105', 'input', '100px');
        $r .= $DSP->td('tableHeading', '').$DSP->input_submit($LANG->line('submit')).$DSP->td_c();//.$DSP->tr_c();        
        $r .= $DSP->td('tableHeading', '', '2').$DSP->td_c().$DSP->tr_c();
        
        $r .= $DSP->form_close();
        
    		/** ------------------------------
    		/**  Table Rows
    		/** ------------------------------*/
    
    		$i = 0;
    	
    		foreach ($query->result as $row)
    		{		
    			
    			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
    			
    			$r .=  $DSP->tr();
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$row['title'], '20%');
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$row['url'], '35%');
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$row['visit_date'], '10%');
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$row['time_start'].'-'.$row['time_end'], '25%');
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$row['count'].NBS.$LANG->line('visitors'), '10%', '', 'left');
    			$r .=  $DSP->tr_c();
    		}

        $r .=  $DSP->table_c();
		
        $r .= $DSP->table('', '0', '', '100%');
        $r .= $DSP->tr().$DSP->td();
            
        // Pagination
            
        if ($paginate != '')
        {
        	$r .= $DSP->qdiv('crumblinks', $DSP->qdiv('itemWrapper', $paginate));
        }
        
        $r .= $DSP->td_c();
              $DSP->tr_c().
              $DSP->table_c();
        

        
        return $this->content_wrapper($title, $crumb, $r);

      
      }
      /* END */
      
      /** ------------------------------------------------
      /**  Content Wrapper
      /** ------------------------------------------------*/
      
      function content_wrapper($title = '', $crumb = '', $content = '')
      {
          global $DSP, $DB, $IN, $SESS, $FNS, $LANG;
                                    
          // Default page title if not supplied  
                          
          if ($title == '')
          {
              $title = $LANG->line('visitstats_history');
          }
                  
          // Default bread crumb if not supplied
          
          if ($crumb == '')
          {
      	     $crumb = '';        
          }
                  
          // Set breadcrumb and title
          
          $DSP->title  = $title;
          $DSP->crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=twitter'.AMP.'P=visitstats_home')).$crumb;
      
          // Default content if not supplied
      
          if ($content == '')
          {
              $content .= $this->visitstats_home();
          }
                  
          $DSP->body	.=	$DSP->td('', '', '', '', 'top');
          
          $DSP->body	.=	$DSP->qdiv('itemWrapper', $content);
          
      }
      /* END */
                 

      /** ----------------------------------
      /** Entry form to store new page url 
      /** ---------------------------------*/
      function entry_form($msg = '')
      {
		      global $IN, $DSP, $LANG, $DB;
		
		/** ------------------------------------
		/**  Are we editing an existing url?
		/** ------------------------------------*/		      
		
		$page_id = ( ! $IN->GBL('page_id')) ? FALSE : $IN->GBL('page_id');
		
		if ($page_id !== FALSE)
		{
			$query = $DB->query("SELECT page_id, title, url FROM exp_visitstats_pages WHERE page_id = '".$DB->escape_str($page_id)."' ");
			if ($query->num_rows == 1){
				$title = $query->row['title'];
		  		$url        =  $query->row['url'];
		  	}else{ 
		  		$title = '';
		  		$url        =  '';
		  	}	
		}else{
		 	$title = '';
		 	$url = '';
		} 		
		
		
		/** ------------------------------------
		/**  Page heading/crumb/title
		/** ------------------------------------*/
		
		$DSP->title  = $LANG->line('visitstats_module_name');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=visitstats', $LANG->line('visitstats_module_name'));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('visitstats_new_entry'));       	
		
		
		// Build the output		
		$nav = $this->nav(	array(
								'visitstats_home'			       => array('P' => 'visitstats_home'),
								'visitstats_new_entry'			   => array('P' => 'entry_form'),								
								'visitstats_view_entries'		 => array('P' => 'view_entries', 'mode' => 'view')
							)
			);
			
		
		if ($nav != '')
		{
			$DSP->body .= $nav;
		}
		
		$DSP->body  .= $DSP->qdiv('tableHeading', $LANG->line('visitstats_new_entry'));
		
		if ($page_id !== FALSE)
		{
		
				$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M=visitstats'.AMP.'P=update_entry'));
				$DSP->body .= $DSP->input_hidden('page_id', $page_id);
		}else{
			$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M=visitstats'.AMP.'P=insert_new_entry'));
		}
		
		if ($msg != '')
		{
		    $DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
		}
        
        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%');
		
		$style ='tableCellOne';

		$DSP->body .= $DSP->tr();

		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('page_title')), '10%');				
		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text('title', $title, null, '400', 'input', '650px'))), '25%');
		$DSP->body .= $DSP->tr_c();

		$DSP->body .= $DSP->tr();		
		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $LANG->line('page_url')), '10%');				
		$DSP->body .= $DSP->table_qcell($style, $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text('url', $url, null, '400', 'input', '650px'))), '50%');
		
		$DSP->body .= $DSP->tr_c();


            		
		$DSP->body .= $DSP->table_c();		
	    $DSP->body	.=	$DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('submit')));       
        $DSP->body .= $DSP->form_close();   
      
      }      
      /* END */
      

      /** ----------------------------------
      /** Store page url to DB
      /** ---------------------------------*/                      
      function insert_new_entry()
      {
          global $IN, $DSP, $SESS, $LOC, $LANG, $DB, $REGX;

          if ($IN->GBL('url')=='' || $IN->GBL('title')=='' ){
			       return $DSP->error_message($LANG->line('invalid_inputs'));
          }

         
          $DB->query("INSERT INTO exp_visitstats_pages(title, url) values ('".$IN->GBL('title')."', '".$IN->GBL('url')."')");
          
          //$msg = $LANG->line('visitstats_message_inserted');          
          return $this->view_entries('insert');          
      
      }
      /* END */

      
      /** ----------------------------------
      /** View Store Pages
      /** ---------------------------------*/
      function view_entries($action = '')
      {
		 global $DSP, $IN, $DB, $LANG, $FNS, $LOC, $PREFS;

    		/** ------------------------------------
    		/**  Page heading/crumb/title
    		/** ------------------------------------*/
    		
        $title  = $LANG->line('visitstats_module_name');
    	$crumb = $LANG->line('visitstats_view_entries');
    		
    	$r = '';
        if ($action == 'update')
        {
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.NBS.$LANG->line('url_updated') )));
        }
        elseif ($action == 'insert')
        {
			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.NBS.$LANG->line('url_added') )));
        }               	
        
        //fetch entries
        $sql = "SELECT page_id, title, url FROM exp_visitstats_pages ORDER BY page_id desc";
        $query = $DB->query($sql);

    		/** -----------------------------
    		/**  Do we need pagination?
    		/** -----------------------------*/
		
    		$paginate = '';
    		
    		if ($query->num_rows > $this->row_limit)
    		{ 
    			$row_count = ( ! $IN->GBL('row')) ? 0 : $IN->GBL('row');    						
    			$base_url = BASE.AMP.'C=modules'.AMP.'M=visitsats'.AMP.'P=view_entries';
    			$paginate = $DSP->pager(  $base_url,
    									  $query->num_rows, 
    									  $this->row_limit,
    									  $row_count,
    									  'row'
    									);
    			$sql .= " LIMIT ".$row_count.", ".$this->row_limit;
    			$query = $DB->query($sql);    
    		}

    		/** ------------------------------
    		/**  Build the output
    		/** ------------------------------*/
    		
    		
    		// This message is shown when entries are deleted
    		
    		if ($IN->GBL('action') == 'delete')
    		{
    			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('successBox', $DSP->qdiv('success', $LANG->line('entry_deleted'))));                         
    		}
    		elseif (isset($LANG->language['action_'.$IN->GBL('action')]))
    		{
    			$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('successBox', $DSP->qdiv('success', $LANG->line('action_'.$IN->GBL('action')))));
    		}
    		        
        if ($PREFS->ini('time_format') == 'us')
    		{
    			$datestr = '%m/%d/%y %h:%i %a';
    		}
    		else
    		{
    			$datestr = '%Y-%m-%d %H:%i';
    		}
                    

        // Build the output		
		    $nav = $this->nav(	array(
									'visitstats_home'			       => array('P' => 'visitstats_home'),
									'visitstats_new_entry'			   => array('P' => 'entry_form'),								
									'visitstats_view_entries'		 => array('P' => 'view_entries', 'mode' => 'view')
								)
				);
				

    		if ($nav != '')
    		{
    			$r .= $nav;
    		}
        
        
    		// If there are no categories yet we'll show an error message    
        if ($query->num_rows == 0)
        {          
    			$r  .= $DSP->qdiv('box', $DSP->qdiv('highlight', $LANG->line('no_entries')));			    
			    return $this->content_wrapper($title, $crumb, $r);              			
        }

        /** ------------------------------
        /**  Form to Deletes entries
        /** ------------------------------*/
            
        $r .=	  $DSP->toggle().   
        		    $DSP->form_open(
        		    
                						array(
                								'action' => 'C=modules'.AMP.'M=visitstats'.AMP.'P=multi_edit_entries', 
                								'name'	=> 'target',
                								'id'	=> 'target',
                							)
            					  );

        /** ------------------------------
        /**  Table Header
        /** ------------------------------*/
        
        $r .= $DSP->table('tableBorder', '0', '10', '100%').
                  	  $DSP->tr().
                  	  $DSP->td('tableHeading', '', '').$LANG->line('title').$DSP->td_c().                  	  
                      $DSP->td('tableHeading', '', '').$LANG->line('url').$DSP->td_c().
                  	  $DSP->td('tableHeading', '', '').$DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").$DSP->td_c().
                  	  $DSP->tr_c();

    		/** ------------------------------
    		/**  Table Rows
    		/** ------------------------------*/
    
    		$i = 0;
    	
    		foreach ($query->result as $row)
    		{		
    			
    			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
    			
    			$r .=  $DSP->tr();
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$row['title'], '10%');
    			$r .=  $DSP->table_qcell($style, NBS.NBS.$DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=visitstats'.AMP.'P=visitstats_home'.AMP.'page_id='.$row['page_id'], $row['url']), '27%');    
    			$r .=  $DSP->table_qcell($style, $DSP->input_checkbox('toggle[]', $row['page_id'], '' , ' id="delete_box_'.$row['page_id'].'"'), '10%');      
    			$r .=  $DSP->tr_c();
    		}

        $r .=  $DSP->table_c();
		
        $r .= $DSP->table('', '0', '', '100%');
        $r .= $DSP->tr().$DSP->td();
            
        // Pagination
            
        if ($paginate != '')
        {
        	$r .= $DSP->qdiv('crumblinks', $DSP->qdiv('itemWrapper', $paginate));
        }
        
        $r .= $DSP->td_c().$DSP->td('defaultRight');
        
            
        // Actions and submit button
        
        $r .= $DSP->div('itemWrapper');
        
        $r .= $DSP->input_submit($LANG->line('submit'));
        
        $r .= NBS.$DSP->input_select_header('action').
              $DSP->input_select_option('delete', $LANG->line('delete_selected')).
              $DSP->input_select_option('null', '--').
              
              $DSP->input_select_footer();
              
        $r .= $DSP->div_c();
        
        $r .= $DSP->td_c().
              $DSP->tr_c().
              $DSP->table_c();
        
        $r .= $DSP->form_close();
        
        return $this->content_wrapper($title, $crumb, $r);
      
      }      
      /* END */
      
    	/** -----------------------------
    	/**  Edit Multiple entries
    	/** -----------------------------*/
    	
    	function multi_edit_entries()
    	{
        global $IN, $DB, $DSP, $FNS;
            
                
        $entries = array();
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
    		      $entries[] = $DB->escape_str($val);
            }
        }
            
        if (sizeof($entries) == 0)
        {
          $FNS->redirect(BASE.AMP.'C=modules'.AMP.'M=visitstats'.AMP.'P=view_entries');
          exit;
        }
            
        $action = $IN->GBL('action');
        

        if ($IN->GBL('action') == 'delete')
        {
        	$DB->query("DELETE FROM  exp_visitstats_pages WHERE page_id IN ('".implode("','", $entries)."') ");
        }
        
        $FNS->redirect(BASE.AMP.'C=modules'.AMP.'M=visitstats'.AMP.'P=view_entries'.AMP.'action='.$action);
        exit;
	}
    	/* END */
      
      /** -----------------------------------
      /**  Navigation Tabs
      /** -----------------------------------*/
  
      // Takes an array as input and creates the navigation tabs from it.
      // This functiion is called by the one above.
  
      function nav($nav_array)
      {
        global $IN, $DSP, $PREFS, $REGX, $FNS, $LANG;
        
                
    		/** -------------------------------
    		/**  Build the menus
    		/** -------------------------------*/
    		// Equalize the text length.
    		// We do this so that the tabs will all be the same length.
		
    		$temp = array();
    		foreach ($nav_array as $k => $v)
    		{
    			$temp[$k] = $LANG->line($k);
    		}
    		$temp = $DSP->equalize_text($temp);

    		//-------------------------------
                                
        $highlight = array(
        					'visitstats_home'			    => 'visitstats_home',
        					'entry_form'			    => 'visitstats_new_entry',        				
        					'view_entries'			  => 'visitstats_view_entries'
        					);
        					
        $page = $IN->GBL('P');					
        					
        if (isset($highlight[$page]))
        {
        	$page = $highlight[$page];
        }
        
        $r = <<<EOT
        <script type="text/javascript"> 
        <!--

    		function styleswitch(link)
    		{                 
    			if (document.getElementById(link).className == 'altTabs')
    			{
    				document.getElementById(link).className = 'altTabsHover';
    			}
    		}
    	
    		function stylereset(link)
    		{                 
    			if (document.getElementById(link).className == 'altTabsHover')
    			{
    				document.getElementById(link).className = 'altTabs';
    			}
    		}
    		
    		-->
    		</script>		

EOT;
    
		    $r .= $DSP->table_open(array('width' => '100%'));

    		$nav = array();
    		foreach ($nav_array as $key => $val)
    		{
    			$url = '';
    		
    			if (is_array($val))
    			{
    				$url = BASE.AMP.'C=modules'.AMP.'M=visitstats';		
    			
    				foreach ($val as $k => $v)
    				{
    					$url .= AMP.$k.'='.$v;
    				}					
    				$title = $temp[$key];
    			}
    			else
    			{
    				$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';        
    				$url = $REGX->prep_query_string($FNS->fetch_site_index()).$qs.'URL='.$REGX->prep_query_string($this->prefs['visitstats_url']);
    				
    				$title = $LANG->line('visitstats_module_name');
    			}
    			
    
    			$url = ($url == '') ? $val : $url;
    
    			$div = ($page == $key) ? 'altTabSelected' : 'altTabs';
    			$linko = '<div class="'.$div.'" id="'.$key.'"  onclick="navjump(\''.$url.'\');" onmouseover="styleswitch(\''.$key.'\');" onmouseout="stylereset(\''.$key.'\');">'.$title.'</div>';
    			
    			$nav[] = array('text' => $DSP->anchor($url, $linko));
    		}

    		$r .= $DSP->table_row($nav);		
    		$r .= $DSP->table_close();

  		  return $r;          
      }
      /* END */
      
      /** -------------------------
      /** Module Installer
      /** -------------------------*/
      
      function visitstats_module_install()
      {
          global $DB;
            
          $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Visitstats','$this->version', 'y')";
									          
          $sql[] = "CREATE TABLE IF NOT EXISTS exp_visitstats_pages(
            					page_id int(4) unsigned NOT NULL auto_increment,
            					title varchar(255) NOT NULL,
            					url varchar(255) NOT NULL,
            					PRIMARY KEY (page_id)                   
                   )";

          $sql[] = "CREATE TABLE IF NOT EXISTS exp_visitstats_timeslot(
            					timeslot_id int(4) unsigned NOT NULL auto_increment,
					            time_start time NOT NULL,
					            time_end   time NOT NULL,
            					PRIMARY KEY (timeslot_id) 
                   )";
		  //Default time slots	
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '00:00:00', '00:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '00:30:00', '01:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '01:00:00', '01:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '01:30:00', '02:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '02:00:00', '02:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '02:30:00', '03:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '03:00:00', '03:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '03:30:00', '04:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '04:00:00', '04:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '04:30:00', '05:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '05:00:00', '05:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '05:30:00', '06:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '06:00:00', '06:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '06:30:00', '07:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '07:00:00', '07:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '07:30:00', '08:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '08:00:00', '08:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '08:30:00', '09:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '09:00:00', '09:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '09:30:00', '10:00:00')";

          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '10:00:00', '10:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '10:30:00', '11:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '11:00:00', '11:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '11:30:00', '12:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '12:00:00', '12:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '12:30:00', '13:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '13:00:00', '13:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '13:30:00', '14:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '14:00:00', '14:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '14:30:00', '15:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '15:00:00', '15:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '15:30:00', '16:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '16:00:00', '16:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '16:30:00', '17:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '17:00:00', '17:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '17:30:00', '18:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '18:00:00', '18:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '18:30:00', '19:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '19:00:00', '19:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '19:30:00', '20:00:00')";

          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '20:00:00', '20:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '20:30:00', '21:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '21:00:00', '21:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '21:30:00', '22:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '22:00:00', '22:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '22:30:00', '23:00:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '23:00:00', '23:30:00')";
          $sql[] = "INSERT INTO exp_visitstats_timeslot (timeslot_id, time_start, time_end) VALUES ('', '23:30:00', '24:00:00')";

          $sql[] = "CREATE TABLE IF NOT EXISTS exp_visitstats(
            					visitstat_id int(11) unsigned NOT NULL auto_increment,
            					title varchar(256) default NULL,
					            url varchar(255) NOT NULL,
					            referrer_url varchar(255) NOT NULL,
					            user_agent varchar(255) NOT NULL, 
					            member_id int(11) unsigned NOT NULL default '0',
					            ip varchar(16) NOT NULL default '0',
					            visit_date date NOT NULL,
					            visit_time time NOT NULL,
					            timeslot_id int(4) NOT NULL,
					            entry_date timestamp(14) DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            					PRIMARY KEY (visitstat_id),
                      			KEY (url)                   
                   )";


            
          foreach ($sql as $query)
          {
              $DB->query($query);
          }
          
          return true;
      }
      
    /** ----------------------------------------
    /**  Module de-installer
    /** ----------------------------------------*/

    function visitstats_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Visitstats'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";        
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Visitstats'";

		$sql[] = "DELETE FROM exp_actions WHERE class = 'Visitstats'";
		
        $sql[] = "DROP TABLE IF EXISTS exp_visitstats_pages";
        $sql[] = "DROP TABLE IF EXISTS exp_visitstats_timeslot";
        $sql[] = "DROP TABLE IF EXISTS exp_visitstats";        

        foreach ($sql as $query)
        {
            $DB->query($query);
        }

        return true;
    }
    /* END */
                       
                
 }
 
 
?>
