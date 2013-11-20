
<?php

	class extension_ssl_redirector extends Extension {

		public function about() {
			return array(
				'name'			=> 'SSL Redirector',
				'version'		=> '1.0',
				'release-date'	=> '2012-01-16',
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
		    );
		}
	     
	    public function install()
	    {
	        Symphony::Configuration()->setArray(array('ssl' => array(
		        'enabled' => 'yes',
		        'pages' => '',
		    )));
	        return Symphony::Configuration()->write();
	    }
	    
	    public function uninstall()
	    {
	        Symphony::Configuration()->remove('ssl');
	        return Symphony::Configuration()->write();
	    }
	    
		public function __frontendParamsResolve($context) {
			$conf = Symphony::Configuration()->get('ssl');
			$ssl_pages = explode(',', $conf['pages']);
		    
		    if($conf['enabled'] == 'yes' && count($ssl_pages) > 0){
			    $page_id = (int)$context['params']['current-page-id'];
			    $current_url = (string)$context['params']['current-url'];
			    
			    if (in_array($page_id, $ssl_pages) && __SECURE__ !== TRUE) {
			        header('HTTP/1.1 301 Moved Permanently');
			        redirect('Location: ' . preg_replace('/^http/', 'https', $current_url));
			        exit();
			    } else if (!in_array($page_id, $ssl_pages) && __SECURE__ === TRUE) {
			        header('HTTP/1.1 301 Moved Permanently');
			        redirect('Location: ' . preg_replace('/^https/', 'http', $current_url));
			        exit();
			    }
			    
			}

		}
	}

?>
