<?php

if (!class_exists ('WP_MXNHelper_v2_0')) {
	class WP_MXNHelper_v2_0 extends WP_PluginBase_v1_1 {

		private $supported_providers;
		private $admin_providers = null;
		private $frontend_providers = null;
		private $sanitise_callback = null;

		function __construct () {
			$this->supported_providers = array (

			// Supported Provider Array components
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
			// 'microsoft7 callback should return:
			// array ('key' => 'your bing maps v7 key')
			// see http://www.bingmapsportal.com/
			'microsoft7' => array (
			'description' => 'Bing Maps v7.0',
			'has-script' => true,
			'has-header' => false,
			'has-style' => false,
			'has-init' => true,
			'has-callback' => true,
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
			
			if (is_admin ()) {
				$this->hook ('admin_head', 'admin_head_meta');
				$this->hook ('admin_head', 'admin_head_init', 11);
				$this->hook ('admin_enqueue_scripts', 'admin_enqueue_scripts');
			}

			else {
				$this->hook ('wp_head', 'frontend_head_meta');
				$this->hook ('wp_head', 'frontend_head_init', 11);
				$this->hook ('wp_enqueue_scripts', 'frontend_enqueue_scripts');
			}
		}

		public function get_supported_providers () {
			$providers = apply_filters ('wp_mxn_helper_providers', $this->supported_providers);

			if (isset ($this->sanitise_callback)) {
				$providers = call_user_func ($this->sanitise_callback, $providers);
			}

			return $providers;
		}

		public function set_admin_providers ($providers) {
			if (!empty ($providers)) {
				$this->admin_providers = array ();

				foreach ($providers as $provider) {
					if ($this->validate_provider ($provider)) {
						$this->admin_providers[] = $provider;
					}
				}
			}
		}
		
		public function set_frontend_providers ($providers) {
			if (!empty ($providers)) {
				$this->frontend_providers = array ();

				foreach ($providers as $provider) {
					if ($this->validate_provider ($provider)) {
						$this->frontend_providers[] = $provider;
					}
				}
			}
		}
		
		public function register_callback ($provider, $callback) {
			if ($this->validate_provider ($provider)) {
				if ($this->supported_providers[$provider]['has-callback']) {
					$this->supported_providers[$provider]['callback'] = $callback;
				}
			}
		}

		public function register_sanitise_callback ($callback) {
			if (isset ($callback)) {
				$this->sanitise_callback = $callback;
			}
		}
		
		public function admin_head_meta () {
			if (empty ($this->admin_providers)) {
				return;
			}

			foreach ($this->admin_providers as $provider) {
				if ($this->validate_provider ($provider)) {
					$meta = $this->get_provider_header ($provider);
					if (isset ($meta) && !empty ($meta)) {
						echo $meta . PHP_EOL;
					}
				}
			}	// end-foreach
		}

		public function frontend_head_meta () {
			if (empty ($this->frontend_providers)) {
				return;
			}

			foreach ($this->frontend_providers as $provider) {
				if ($this->validate_provider ($provider)) {
					$meta = $this->get_provider_header ($provider);
					if (isset ($meta) && !empty ($meta)) {
						echo $meta . PHP_EOL;
					}
				}
			}	// end-foreach
		}

		public function admin_head_init () {
			if (empty ($this->admin_providers)) {
				return;
			}

			foreach ($this->admin_providers as $provider) {
				if ($this->validate_provider ($provider)) {
					$init = $this->get_provider_init ($provider);
					if (isset ($init) && !empty ($init)) {
						echo $init;
					}
				}
			}	// end-foreach
		}

		public function frontend_head_init () {
			if (empty ($this->frontend_providers)) {
				return;
			}

			foreach ($this->frontend_providers as $provider) {
				if ($this->validate_provider ($provider)) {
					$init = $this->get_provider_init ($provider);
					if (isset ($init) && !empty ($init)) {
						echo $init;
					}
				}
			}	// end-foreach
		}

		public function admin_enqueue_scripts () {
			if (empty ($this->admin_providers)) {
				return;
			}
			
			foreach ($this->admin_providers as $provider) {
				$api = $this->get_provider_script ($provider);
				if (isset ($api) && !empty ($api)) {
					$style = $this->get_provider_style ($provider);
					if (isset ($style) && !empty ($style)) {
						wp_register_style ($style['handle'], $style['style']);
						wp_enqueue_style ($style['handle']);
					}

					wp_register_script ($api['handle'], $api['script']);
					wp_enqueue_script ($api['handle']);
					$deps[] = $api['handle'];
				}
			}	// end-foreach

			$core = $this->get_core_script ($this->admin_providers);
			wp_register_script ($core['handle'], $core['script'], $deps);
			wp_enqueue_script ($core['handle']);
		}

		public function frontend_enqueue_scripts () {
			if (empty ($this->frontend_providers)) {
				return;
			}

			foreach ($this->frontend_providers as $provider) {
				$api = $this->get_provider_script ($provider);
				if (isset ($api) && !empty ($api)) {
					$style = $this->get_provider_style ($provider);
					if (isset ($style) && !empty ($style)) {
						wp_register_style ($style['handle'], $style['style']);
						wp_enqueue_style ($style['handle']);
					}

					wp_register_script ($api['handle'], $api['script']);
					wp_enqueue_script ($api['handle']);
					$deps[] = $api['handle'];
				}
			}	// end-foreach

			$core = $this->get_core_script ($this->frontend_providers);
			wp_register_script ($core['handle'], $core['script'], $deps);
			wp_enqueue_script ($core['handle']);
		}

		public function get_provider_style ($provider) {
			if ($this->validate_provider ($provider)) {
				if ($this->supported_providers[$provider]['has-style']) {
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
				if ($this->supported_providers[$provider]['has-script']) {
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
				if ($this->supported_providers[$provider]['has-header']) {
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
				if ($this->supported_providers[$provider]['has-init']) {
					$method = $provider . '_init';
					if (method_exists ($this, $method)) {
						$init = call_user_func (array ($this, $method), $provider);
						return $init;
					}
				}
			}
		}

		public function get_core_script ($providers) {
			$stub = 'https://raw.github.com/vicchi/mxn/master/source/mxn.js?(%s)';
			//$stub = 'https://raw.github.com/mapstraction/mxn/master/source/mxn.js?(%s)';
			$script = sprintf ($stub, implode (",", $providers));
			$handle = 'mxn-core';
			return array ('handle' => $handle, 'script' => $script);
		}

		private function validate_provider ($provider) {
			if (isset ($provider) && !empty ($provider)) {
				return array_key_exists ($provider, $this->supported_providers);
			}

			return false;
		}

		// CloudMade helpers ...

		private function cloudmade_script ($provider) {
			return 'http://tile.cloudmade.com/wml/latest/web-maps-lite.js';
		}

		private function cloudmade_init ($provider) {
			if ($this->supported_providers[$provider]['has-callback'] && isset ($this->supported_providers[$provider]['callback'])) {
				$meta = call_user_func ($this->supported_providers[$provider]['callback']);
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
			if ($this->supported_providers[$provider]['has-callback'] && isset ($this->supported_providers[$provider]['callback'])) {
				$meta = call_user_func ($this->supported_providers[$provider]['callback']);
				if (array_key_exists ('key', $meta) && array_key_exists ('sensor', $meta)) {
					$stub = 'http://maps.googleapis.com/maps/api/js?key=%s&sensor=%s';
					return sprintf ($stub, $meta['key'], $meta['sensor']);
				}
			}
		}

		// CloudMade Leaflet helpers ...

		private function leaflet_style ($provider) {
			//return 'https://raw.github.com/CloudMade/Leaflet/master/dist/leaflet.css';
			return 'http://cdn.leafletjs.com/leaflet-0.4/leaflet.css';
		}

		private function leaflet_script ($provider) {
			//return 'https://raw.github.com/CloudMade/Leaflet/master/dist/leaflet.js';
			return 'http://cdn.leafletjs.com/leaflet-0.4/leaflet.js';
		}

		// Microsoft / Bing v7 helpers ...

		private function microsoft7_script () {
			return 'http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0';
		}

		private function microsoft7_init ($provider) {
			if ($this->supported_providers[$provider]['has-callback'] && isset ($this->supported_providers[$provider]['callback'])) {
				$meta = call_user_func ($this->supported_providers[$provider]['callback']);
				if (array_key_exists ('key', $meta)) {
					$init = array ();
					$init[] = '<script type="text/javascript">';
					$init[] = sprintf ('microsoft_key = "%s";', $meta['key']);
					$init[] = '</script>';

					return implode (PHP_EOL, $init) . PHP_EOL;
				}
			}
		}

		// Nokia Maps helpers ...

		private function nokia_header ($provider) {
			return '<meta http-equiv="X-UA-Compatible" content="IE=7; IE=EmulateIE9" />';
		}

		private function nokia_script ($provider) {
			return 'http://api.maps.nokia.com/2.2.1/jsl.js';
		}

		private function nokia_init ($provider) {
			if ($this->supported_providers[$provider]['has-callback'] && isset ($this->supported_providers[$provider]['callback'])) {
				$meta = call_user_func ($this->supported_providers[$provider]['callback']);
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
			//return 'https://raw.github.com/openlayers/openlayers/master/lib/OpenLayers.js';
			return 'http://openlayers.org/api/OpenLayers.js';
		}

		// OS OpenSpace helpers ...

		private function openspace_script ($provider) {

		}

	}	// end-class WP_MXNHelper_v2_0
}	// end-if (!class_exists ('WP_MXNHelper_v2_0'))
?>