<?php
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	class extension_ssl_redirector extends Extension {

		public function about() {
			return array(
				'name'			=> 'SSL Redirector',
				'version'		=> '1.3',
				'release-date'	=> '2014-06-18',
				'author'		=> array(
					'name'			=> 'Michael Hay',
					'website'		=> 'http://www.korelogic.co.uk/',
					'email'			=> 'michael.hay@korelogic.co.uk'
				)
			);
		}
		
		public function getSubscribedDelegates(){
		    return array(       
		        array(
		            'page' => '/frontend/',
		            'delegate' => 'FrontendParamsResolve',
		            'callback' => '__frontendParamsResolve'
		        ),
		        array(
		            'page' => '/blueprints/pages/',
		            'delegate' => 'AppendPageContent',
		            'callback' => '__addSSLBtn'
		        ),
		        array(
					'page' => '/blueprints/pages/',
					'delegate' => 'PagePostCreate',
					'callback' => '__saveSSLSettings'
				),
				array(
					'page' => '/blueprints/pages/',
					'delegate' => 'PagePostEdit',
					'callback' => '__saveSSLSettings'
				),
		    );
		}
	     
	    public function install()
	    {
		    
		    Symphony::Database()->query("ALTER TABLE `sym_pages` ADD COLUMN `force_ssl` enum('http','https','both') DEFAULT 'http'");
		    
	        Symphony::Configuration()->setArray(array('ssl' => array(
		        'enabled' => 'yes',
		        'trailing_slash' => 'no'
		    )));
	        return Symphony::Configuration()->write();
	    }
	    
	    public function uninstall()
	    {
		    Symphony::Database()->query("ALTER TABLE `sym_pages` DROP `force_ssl`");
		    
	        Symphony::Configuration()->remove('ssl');
	        return Symphony::Configuration()->write();
	    }
	    
	    //Backoffice
	    
	    public function __addSSLBtn($context) {
		    
		    $pageId = intval($context["fields"]["id"]);
		    
			$fieldset = new XMLElement('fieldset', null, array('class' => 'settings'));
			$fieldset->appendChild(new XMLElement('legend', __('SSL Redirect')));
			$container = new XMLElement('div', null, array('class' => 'field-multilingual'));
			
			$sslForceState = Symphony::Database()->fetchRow(0, "
				SELECT force_ssl
				FROM `tbl_pages` AS page
				WHERE page.id = {$pageId}
				LIMIT 1;
			")["force_ssl"];

			// Append settings
			$label = Widget::Label();
			
			$options = array();
			$options[] = array('http', ($sslForceState == "http"), 'Force HTTP');
			$options[] = array('https', ($sslForceState == "https"), 'Force HTTPS');
			$options[] = array('both', ($sslForceState == "both"), 'HTTP or HTTPS');
			$input = Widget::Select('fields[force_ssl]', $options, array('class' => 'cdi-mode', 'style' => 'width: 250px;'));
			
			//if($sslForceEnabled == 'yes'){
			//	$input->setAttribute('checked', 'checked');
			//}
			
			$container->appendChild($input);

			$fieldset->appendChild($container);
			$context['form']->appendChild($fieldset);
			
		}
		
		public function __saveSSLSettings($context) {
		    
			$pageId = $context['page_id'];
			$forceSSL = "http";
			
			//Disable force ssl mode by default
			$forceSSL = $context["fields"]["force_ssl"];

			Symphony::Database()->query("UPDATE `tbl_pages` SET `force_ssl` = '{$forceSSL}' WHERE `id` = {$pageId};");
			
		}
		
		//Frontend
	    
		public function __frontendParamsResolve($context) {
			
			$conf = Symphony::Configuration()->get('ssl');
			$pageId = (int)$context['params']['current-page-id'];
		    
		    if($conf['enabled'] == 'yes' && isset($pageId)){
			    
			    $sslForceState = Symphony::Database()->fetchRow(0, "
					SELECT force_ssl
					FROM `tbl_pages` AS page
					WHERE page.id = {$pageId}
					LIMIT 1;
				")["force_ssl"];
			    
			    $current_url = (string)$context['params']['current-url'];
			    
			    if($conf["trailing_slash"] == "yes"){
					$current_url = $current_url . "/";
				}
			    
				if($sslForceState == 'http'){
					if(__SECURE__ === TRUE) {
				        header('HTTP/1.1 301 Moved Permanently');
				        redirect('Location: ' . preg_replace('/^https/', 'http', $current_url));
				        exit();
				    }
				}
				
				if($sslForceState == 'https'){
					if (__SECURE__ !== TRUE) {
			        	header('HTTP/1.1 301 Moved Permanently');
						redirect('Location: ' . preg_replace('/^http/', 'https', $current_url));
						exit();
				    } 
				}

			}

		}
	}

?>
