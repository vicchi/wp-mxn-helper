<?php

class WP_MXNHelper extends WP_PluginBase {
	
	private static $providers;
	private $provider = null;
	
	function __construct () {
		self::$providers = array (

			// Provider Array components
			// 'description' => human friendly description of who the provider is
			// 'has-script' => this provider requires a JS script to be included
			// 'has-header' => this provider needs additional header code to be included
			// 'has-style' => this provider requires a CSS script to be included
			// 'has-init' => this provider needs initialisation code to be included
			// 'has-callback' => this provider requires authentication keys via a callback
			// 'callback' => provider specific callback, set via register_callback(

			// 'cloudmade' callback should return:
			// array ('key' => 'your cloudmade key')
			// see http://cloudmade.com/signin
			'cloudmade' => array (
				'description' => 'CloudMade',
				'has-script' => true,
				'has-header' => false,
				'has-style' => false,
				'has-init' => true,
				'has-callback' => true,
				'callback' => null
				),
			// 'googlev3' callback should return:
			// array ('key' => 'your google key', 'sensor' => 'true or false')
			// see https://code.google.com/apis/console
			'googlev3' => array (
				'description' => 'Google Maps v3',
				'has-script' => true,
				'has-header' => false,
				'has-style' => false,
				'has-init' => false,
				'has-callback' => true,
				'callback' => null
				),
			// 'leaflet' requires no authentication, thus doesn't need a callback
			'leaflet' => array (
				'description' => 'Leaflet',
				'has-script' => true,
				'has-header' => false,
				'has-style' => true,
				'has-init' => false,
				'has-callback' => false,
				'callback' => null
				),
			// 'nokia' callback should return:
			// array ('app-id' => 'your nokia app id', 'auth-token' => 'your nokia auth token')
			// see http://www.developer.nokia.com/Profile/Join.xhtml?locale=en
			'nokia' => array (
				'description' => 'Nokia Maps',
				'has-script' => true,
				'has-header' => true,
				'has-style' => false,
				'has-init' => true,
				'has-callback' => true,
				'callback' => null
				),
			// 'openlayers' requires no authentication, thus doesn't need a callback
			'openlayers' => array (
				'description' => 'OpenLayers',
				'has-script' => true,
				'has-header' => false,
				'has-style' => false,
				'has-init' => false,
				'has-callback' => false,
				'callback' => null
				),
			// 'openmq' requires no authentication, thus doesn't need a callback
			'openmq' => array (
				'description' => 'MapQuest Open',
				'has-script' => true,
				'has-header' => false,
				'has-style' => false,
				'has-init' => false,
				'has-callback' => false,
				'callback' => null
				),
			// 'openspace' callback should return:
			// array ('key' => 'your openspace key')
			// see https://openspace.ordnancesurvey.co.uk/osmapapi/register.do
			'openspace' => array (
				'description' => 'Ordnance Survey OpenSpace',
				'has-script' => true,
				'has-header' => false,
				'has-style' => false,
				'has-init' => false,
				'has-callback' => true,
				'callback' => null
				)
			);
			
		$this->hook ('wp_head', 'head_meta');
		$this->hook ('wp_head', 'head_init', 11);
		$this->hook ('wp_enqueue_scripts', 'enqueue_scripts');
	}
	
	static public function get_providers () {
		return apply_filters ('wp_mxn_helper_providers', self::$providers);
	}

	public function set_provider ($provider) {
		if ($this->validate_provider ($provider)) {
			$this->provider = $provider;
		}
	}
	
	public function register_callback ($provider, $callback) {
		if ($this->validate_provider ($provider)) {
			if (self::$providers[$provider]['has-callback']) {
				self::$providers[$provider]['callback'] = $callback;
			}
		}
	}

	public function head_meta () {
		if ($this->validate_provider ($this->provider)) {
			$meta = $this->get_provider_header ($this->provider);
			if (isset ($meta) && !empty ($meta)) {
				echo $meta;
			}
		}
	}
	
	public function head_init () {
		if ($this->validate_provider ($this->provider)) {
			$init = $this->get_provider_init ($this->provider);
			if (isset ($init) && !empty ($init)) {
				echo $init;
			}
		}
	}
	
	public function enqueue_scripts () {
		if ($this->validate_provider ($this->provider)) {
			$core = $this->get_core_script ($this->provider);
			$api = $this->get_provider_script ($this->provider);
			
			if (isset ($core) && !empty ($core) && isset ($api) && !empty ($api)) {
				$style = $this->get_provider_style ($this->provider);
				if (isset ($style) && !empty ($style)) {
					wp_register_style ($style['handle'], $style['style']);
					wp_enqueue_style ($style['handle']);
				}
				
				wp_register_script ($api['handle'], $api['script']);
				wp_register_script ($core['handle'], $core['script']);
				
				wp_enqueue_script ($api['handle']);
				wp_enqueue_script ($core['handle']);
			}
		}
	}
	
	public function get_provider_style ($provider) {
		if ($this->validate_provider ($provider)) {
			if (self::$providers[$provider]['has-style']) {
				$method = $provider . '_style';
				if (method_exists ($this, $method)) {
					$style = call_user_func (array ($this, $method), $provider);
					$handle = $provider . '-mxn-style';
					return array ('handle' => $handle, 'style' => $style);
				}
			}
		}
	}
	
	public function get_provider_script ($provider) {
		if ($this->validate_provider ($provider)) {
			if (self::$providers[$provider]['has-script']) {
				$method = $provider . '_script';
				if (method_exists ($this, $method)) {
					$script = call_user_func (array ($this, $method), $provider);
					$handle = $provider . '-mxn-script';
					return array ('handle' => $handle, 'script' => $script);
				}
			}
		}
	}
	
	public function get_provider_header ($provider) {
		if ($this->validate_provider ($provider)) {
			if (self::$providers[$provider]['has-header']) {
				$method = $provider . '_header';
				if (method_exists ($this, $method)) {
					$header = call_user_func (array ($this, $method), $provider);
					return $header;
				}
			}
		}
	}
	
	public function get_provider_init ($provider) {
		if ($this->validate_provider ($provider)) {
			if (self::$providers[$provider]['has-init']) {
				$method = $provider . '_init';
				if (method_exists ($this, $method)) {
					$init = call_user_func (array ($this, $method), $provider);
					return $init;
				}
			}
		}
	}
	
	public function get_mxn_script ($provider) {
		if ($this->validate_provider ($provider)) {
			$stub = 'https://raw.github.com/vicchi/mxn/master/source/mxn.js?(%s)';
			//$stub = 'https://raw.github.com/mapstraction/mxn/master/source/mxn.js?(%s)';
			$script = sprintf ($stub, $provider);
			$handle = 'mxn-core';
			return array ('handle' => $handle, 'script' => $script);
		}
	}

	private function validate_provider ($provider) {
		if (isset ($provider) && !empty ($provider)) {
			return array_key_exists ($provider, self::$providers);
		}
		
		return false;
	}
	
	// CloudMade helpers ...

	private function cloudmade_script ($provider) {
		return 'http://tile.cloudmade.com/wml/latest/web-maps-lite.js';
	}
	
	private function cloudmade_init ($provider) {
		if (self::$providers[$provider]['has-callback'] && isset (self::$providers[$provider]['callback'])) {
			$meta = call_user_func (self::$providers[$provider]['callback']);
			if (array_key_exists ('key', $meta)) {
				$init = array ();
				$init[] = '<script type="text/javascript">';
				$init[] = sprintf ('cloudmade_key = "%s";', $meta['key']);
				$init[] = '</script>';

				return implode (PHP_EOL, $init) . PHP_EOL;
			}
		}
		
	}
	
	// Google Maps v3 helpers ...
	
	private function googlev3_script ($provider) {
		if (self::$providers[$provider]['has-callback'] && isset (self::$providers[$provider]['callback'])) {
			$meta = call_user_func (self::$providers[$provider]['callback']);
			if (array_key_exists ('key', $meta) && array_key_exists ('sensor', $meta)) {
				$stub = 'http://maps.googleapis.com/maps/api/js?key=%s&sensor=%s';
				return sprintf ($stub, $meta['key'], $meta['sensor']);
			}
		}
	}

	// CloudMade Leaflet helpers ...
	
	private function leaflet_style ($provider) {
		return 'https://raw.github.com/CloudMade/Leaflet/master/dist/leaflet.css';
	}
	
	private function leaflet_script ($provider) {
		return 'https://raw.github.com/CloudMade/Leaflet/master/dist/leaflet.js';
	}
	
	// Nokia Maps helpers ...
	
	private function nokia_header ($provider) {
		return '<meta http-equiv="X-UA-Compatible" content="IE=7; IE=EmulateIE9" />';
	}
	
	private function nokia_script ($provider) {
		return 'http://api.maps.nokia.com/2.2.1/jsl.js';
	}

	private function nokia_init ($provider) {
		if (self::$providers[$provider]['has-callback'] && isset (self::$providers[$provider]['callback'])) {
			$meta = call_user_func (self::$providers[$provider]['callback']);
			if (array_key_exists ('app-id', $meta) && array_key_exists ('auth-token', $meta)) {
				$init = array ();
				$init[] = '<script type="text/javascript">';
				$init[] = sprintf ('nokia.Settings.set ("appId", "%s");', $meta['app-id']);
				$init[] = sprintf ('nokia.Settings.set ("authenticationToken", "%s");', $meta['auth-token']);
				$init[] = '</script>';

				return implode (PHP_EOL, $init) . PHP_EOL;
			}
		}
	}

	// OpenLayers helpers ...
	private function openlayers_script ($provider) {
		return 'https://raw.github.com/openlayers/openlayers/master/lib/OpenLayers.js';
	}
	
	// OS OpenSpace helpers ...
	
	private function openspace_script ($provider) {
		
	}
	
}	// end-class WP_MXNHelper
?>