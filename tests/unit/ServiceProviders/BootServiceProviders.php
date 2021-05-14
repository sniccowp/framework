<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use SniccoAdapter\BaseContainerAdapter;
    use Tests\CreateContainer;
    use Tests\SetUpDefaultMocks;
	use Tests\stubs\TestApp;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationConfig;
	use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ServiceProvider;
    use WpFacade\WpFacade;

	trait BootServiceProviders {

		use SetUpDefaultMocks;
		use CreateContainer;

		/** @var Application */
		protected $app;

		/** @var ApplicationConfig */
		protected $config;

		private $provider_classes;

		abstract function neededProviders () :array;

		protected function setUp() : void {

			parent::setUp();

			$this->setUpWithConfig( [] );

		}

		protected function tearDown() : void {

			TestApp::setApplication( null );

			unset( $this->app );
			unset( $this->config );
			parent::tearDown();

		}

		protected function setUpWithConfig( array $config = [] ) {

			$container = $this->createContainer();
			$config    = new ApplicationConfig( $config );

			WpFacade::setFacadeContainer($container);

			$this->config = $config;

			/** @var ServiceProvider[] $provider_classes */
			$provider_classes = [];

			$this->app = TestApp::make( $container );

			if ( is_array( $providers = $this->neededProviders() ) ) {

				foreach ( $providers as $provider ) {

					$provider_classes[] = new $provider( $container, $this->config );

				}

				foreach ( $provider_classes as $provider_class ) {

					$provider_class->register();

				}

				$this->provider_classes = $provider_classes;

			}

            ApplicationEvent::make( $container );
            // ApplicationEvent::fake(); ??

		}

		protected function boostrapProviders() {

			foreach ( $this->provider_classes as $provider_class ) {

				$provider_class->bootstrap();

			}
		}


	}