<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\traits\TestHelpers;
    use Tests\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Router;
    use Tests\traits\CreateDefaultWpApiMocks;

    class RouteAttributesTest extends UnitTest
    {

        use TestHelpers;
        use CreateDefaultWpApiMocks;

        const controller_namespace = 'Tests\stubs\Controllers\Web';

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();

        }


        /** @test */
        public function basic_get_routing_works()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('HEAD', '/foo');
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function basic_post_routing_works()
        {

            $this->createRoutes(function () {

                $this->router->post('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('POST', '/foo');
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function basic_put_routing_works()
        {

            $this->createRoutes(function () {

                $this->router->put('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('PUT', '/foo');
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function basic_patch_routing_works()
        {

            $this->createRoutes(function () {

                $this->router->patch('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('PATCH', '/foo');
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function basic_delete_routing_works()
        {

            $this->createRoutes(function () {

                $this->router->delete('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('DELETE', '/foo');
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function basic_options_routing_works()
        {

            $this->createRoutes(function () {

                $this->router->options('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('OPTIONS', '/foo');
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function a_route_can_match_all_methods()
        {

            $this->createRoutes(function () {

                $this->router->any('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('POST', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('PUT', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('PATCH', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('DELETE', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('OPTIONS', '/foo');
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function a_route_can_match_specific_methods()
        {

            $this->createRoutes(function () {

                $this->router->match(['GET', 'POST'], '/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('POST', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('PUT', '/foo');
            $this->runAndAssertOutput('', $request);


        }

        /** @test */
        public function the_route_handler_can_be_defined_with_a_separate_method()
        {

            $this->createRoutes(function () {

                $this->router->get('foo')->handle(function () {

                    return 'foo';
                });
            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request);

        }

        /**
         *
         * @test
         *
         * Failed conditions on a matching static route by url will lead to no route matching.
         *
         */
        public function static_and_dynamic_routes_can_be_added_for_the_same_uri_while_static_routes_take_precedence()
        {

            $this->createRoutes(function () {


                $this->router->post('/foo/bar', function () {

                    return 'foo_bar_static';

                })->where('false');

                $this->router->post('/foo/baz', function () {

                    return 'foo_baz_static';

                });

                $this->router->post('/foo/{dynamic}', function () {

                    return 'dynamic_route';

                });


            });

            // failed condition
            $request = $this->webRequest('POST', '/foo/bar');
            $this->runAndAssertOutput('', $request);

            $request = $this->webRequest('POST', '/foo/baz');
            $this->runAndAssertOutput('foo_baz_static', $request);

            $request = $this->webRequest('POST', '/foo/biz');
            $this->runAndAssertOutput('dynamic_route', $request);


        }

        /** @test */
        public function http_verbs_can_be_defined_after_attributes_and_finalize_the_route()
        {

            $this->createRoutes(function () {

                $this->router->namespace(self::controller_namespace)
                             ->get('/foo', 'RoutingController@foo');

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function middleware_can_be_set()
        {

            $this->createRoutes(function () {

                $this->router
                    ->get('/foo')
                    ->middleware('foo')
                    ->handle(function (Request $request) {

                        return $request->body;

                    });

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request);

        }

        /** @test */
        public function a_route_can_have_multiple_middlewares()
        {

            $this->createRoutes(function () {

                $this->router
                    ->get('/foo')
                    ->middleware(['foo', 'bar'])
                    ->handle(function (Request $request) {

                        return $request->body;

                    });

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foobar', $request);


        }

        /** @test */
        public function middleware_can_pass_arguments()
        {

            $this->createRoutes(function () {

                $this->router
                    ->get('/foo')
                    ->middleware(['foo:FOO', 'bar:BAR'])
                    ->handle(function (Request $request) {

                        return $request->body;

                    });

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('FOOBAR', $request);

        }

        /** @test */
        public function middleware_can_be_set_before_the_http_verb()
        {

            $this->createRoutes(function () {

                $this->router
                    ->middleware('foo')
                    ->get('/foo')
                    ->handle(function (Request $request) {

                        return $request->body;

                    });

                // As array.
                $this->router
                    ->middleware(['foo', 'bar'])
                    ->post('/bar')
                    ->handle(function (Request $request) {

                        return $request->body;

                    });

                // With Args
                $this->router
                    ->middleware(['foo:FOO', 'bar:BAR'])
                    ->put('/baz')
                    ->handle(function (Request $request) {

                        return $request->body;

                    });

            });

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('POST', '/bar');
            $this->runAndAssertOutput('foobar', $request);

            $request = $this->webRequest('PUT', '/baz');
            $this->runAndAssertOutput('FOOBAR', $request);


        }


    }

