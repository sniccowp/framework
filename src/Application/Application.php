<?php


    declare(strict_types = 1);


    namespace WPEmerge\Application;

    use Contracts\ContainerAdapter;
    use Nyholm\Psr7\Factory\Psr17Factory;
    use Nyholm\Psr7Server\ServerRequestCreator;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use SniccoAdapter\BaseContainerAdapter;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Exceptions\ConfigurationException;
    use WPEmerge\Http\Request;
    use WPEmerge\ServiceProviders\AliasServiceProvider;
    use WPEmerge\ServiceProviders\ApplicationServiceProvider;
    use WPEmerge\ServiceProviders\EventServiceProvider;
    use WPEmerge\ServiceProviders\ExceptionServiceProvider;
    use WPEmerge\ServiceProviders\FactoryServiceProvider;
    use WPEmerge\ServiceProviders\HttpServiceProvider;
    use WPEmerge\ServiceProviders\RoutingServiceProvider;
    use WPEmerge\ServiceProviders\ViewServiceProvider;
    use WpFacade\WpFacade;

    class Application
    {

        use ManagesAliases;
        use LoadsServiceProviders;
        use HasContainer;

        const CORE_SERVICE_PROVIDERS = [

            EventServiceProvider::class,
            FactoryServiceProvider::class,
            ApplicationServiceProvider::class,
            HttpServiceProvider::class,
            ExceptionServiceProvider::class,
            RoutingServiceProvider::class,
            ViewServiceProvider::class,
            AliasServiceProvider::class,

        ];

        private $bootstrapped = false;

        /**
         * @var ApplicationConfig
         */
        private $config;

        public function __construct(ContainerAdapter $container, ServerRequestInterface $server_request = null)
        {

            $server_request = $server_request ?? $this->captureRequest();

            $this->setContainer($container);
            $this->container()->instance(Application::class, $this);
            $this->container()->instance(ContainerAdapter::class, $this->container());
            $this->container()->instance(Request::class, new Request($server_request));
            WpFacade::setFacadeContainer($container);

        }

        /**
         * Make and assign a new application instance.
         *
         * @param  string|ContainerAdapter  $container_adapter  ::class or default
         *
         * @return static
         */
        public static function create($container_adapter) : Application
        {

            return new static(
                ($container_adapter !== 'default') ? $container_adapter : new BaseContainerAdapter()
            );
        }

        /**
         * Bootstrap the application and loads all service providers.
         *
         * @param  array  $config  The configuration provided by a user during bootstrapping.
         *
         * @throws ConfigurationException
         */
        public function boot(array $config = []) : void
        {


            if ($this->bootstrapped) {

                throw new ConfigurationException(static::class.' already bootstrapped.');

            }

            $this->bindConfigInstance($config);

            $this->loadServiceProviders();

            $this->bootstrapped = true;

            // If we would always unregister here it would not be possible to handle
            // any errors that happen between this point and the the triggering of the
            // hooks that run the HttpKernel.
            if ( ! $this->isTakeOverMode()) {

                /** @var ErrorHandlerInterface $error_handler */
                $error_handler = $this->container()->make(ErrorHandlerInterface::class);
                $error_handler->unregister();

            }


        }

        public function isTakeOverMode()
        {

            return $this->config->get(ApplicationServiceProvider::STRICT_MODE, false);

        }

        public function config(string $key, $default = null)
        {

            return $this->config->get($key, $default);

        }

        private function bindConfigInstance(array $config)
        {

            $config = new ApplicationConfig($config);

            $this->container()->instance(ApplicationConfig::class, $config);
            $this->config = $config;

        }

        private function captureRequest() : ServerRequestInterface
        {

            $factory = $factory ?? new Psr17Factory();
            $creator = new ServerRequestCreator(
                $factory,
                $factory,
                $factory,
                $factory
            );

            return $creator->fromGlobals();

        }

    }
