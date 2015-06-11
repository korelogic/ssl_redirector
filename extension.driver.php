<?php
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	class extension_ssl_redirector extends Extension {

		public function about() {
			return array(
				'name'			=> 'SSL Redirector',
				'version'		=> '1.2',
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
		    
		    Symphony::Database()->query("ALTER TABLE `sym_pages` ADD COLUMN `force_ssl` enum('yes','no') DEFAULT 'no'");
		    
	        Symphony::Configuration()->setArray(array('ssl' => array(
		        'enabled' => 'yes',
		        'trailing_slash' => 'no',
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
			$fieldset->appendChild(new XMLElement('legend', __('SSL')));
			$container = new XMLElement('div', null, array('class' => 'field-multilingual'));
			
			$sslForceEnabled = Symphony::Database()->fetchRow(0, "
				SELECT force_ssl
				FROM `tbl_pages` AS page
				WHERE page.id = {$pageId}
				LIMIT 1;
			")["force_ssl"] == "yes";

			// handle
			// Append settings
			$label = Widget::Label();
			$input = Widget::Input('fields[force_ssl]', 'yes', 'checkbox');
			
			if($sslForceEnabled == 'yes'){
				$input->setAttribute('checked', 'checked');
			}
			
			$label->setValue($input->generate() . ' ' . __('Force SSL Redirect'));
			$container->appendChild($label);

			$fieldset->appendChild($container);
			$context['form']->appendChild($fieldset);
			
		}
		
		public function __saveSSLSettings($context) {
		    
			$pageId = $context['page_id'];
			$forceSSL = "no";
			
			//Disable force ssl mode by default
			
			if($context["fields"]["force_ssl"] == "yes"){
				$forceSSL = "yes";
			}
			
			Symphony::Database()->query("UPDATE `tbl_pages` SET `force_ssl` = '{$forceSSL}' WHERE `id` = {$pageId};");
			
		}
		
		//Frontend
	    
		public function __frontendParamsResolve($context) {
			
			$conf = Symphony::Configuration()->get('ssl');
			$pageId = (int)$context['params']['current-page-id'];
		    
		    if($conf['enabled'] == 'yes' && isset($pageId)){
			    
			    $sslForceEnabled = Symphony::Database()->fetchRow(0, "
					SELECT force_ssl
					FROM `tbl_pages` AS page
					WHERE page.id = {$pageId}
					LIMIT 1;
				")["force_ssl"] === "yes";
			    
			    $current_url = (string)$context['params']['current-url'];
			    
			    if($conf["trailing_slash"] == "yes"){
					    $current_url = $current_url . "/";
				}
			    
	          
			    if ($sslForceEnabled && __SECURE__ !== TRUE) {
			        header('HTTP/1.1 301 Moved Permanently');
			        redirect('Location: ' . preg_replace('/^http/', 'https', $current_url));
			        exit();
			    } else if (!$sslForceEnabled && __SECURE__ === TRUE) {
			        header('HTTP/1.1 301 Moved Permanently');
			        redirect('Location: ' . preg_replace('/^https/', 'http', $current_url));
			        exit();
			    }
				
			}

		}
	}

?>
