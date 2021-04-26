<?php


	namespace WPEmerge\Session;

	use WPEmerge\Session\FlashStore;
	use WPEmerge\Support\WPEmgereArr;

	/**
	 * Provide a way to get values from the previous request.
	 */
	class OldInputStore {

		/**
		 * FlashStore service.
		 *
		 * @var FlashStore
		 */
		protected $flash = null;

		/**
		 * Key to store the flashed data with.
		 *
		 * @var string
		 */
		protected $flash_key = '';

		/**
		 * Constructor.
		 *
		 * @codeCoverageIgnore
		 *
		 * @param  FlashStore  $flash
		 * @param  string  $flash_key
		 */
		public function __construct( FlashStore $flash, $flash_key = '__wpemergeOldInput' ) {

			$this->flash     = $flash;
			$this->flash_key = $flash_key;
		}

		/**
		 * Get whether the old input service is enabled.
		 *
		 * @return boolean
		 */
		public function enabled() {

			return $this->flash->enabled();
		}

		/**
		 * Get request value for key from the previous request.
		 *
		 * @param  string  $key
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function get( $key, $default = null ) {

			return WPEmgereArr::get( $this->flash->get( $this->flash_key, [] ), $key, $default );
		}

		/**
		 * Set input for the next request.
		 *
		 * @param  array  $input
		 */
		public function set( $input ) {

			$this->flash->add( $this->flash_key, $input );
		}

		/**
		 * Clear input for the next request.
		 *
		 * @return void
		 */
		public function clear() {

			$this->flash->clear( $this->flash_key );
		}

	}
