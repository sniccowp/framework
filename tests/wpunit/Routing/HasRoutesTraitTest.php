<?php


	namespace WPEmergeTests\Routing;

	use Mockery;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Routing\RegisteresRoutes;
	use WPEmerge\Contracts\RouteInterface;
	use WP_UnitTestCase;

	/**
	 * @coversDefaultClass \WPEmerge\Routing\RegisteresRoutes
	 */
	class HasRoutesTraitTest extends TestCase {

		public function setUp() : void {

			parent::setUp();

			$this->subject = new HasRoutesTraitTestImplementation();
		}

		public function tearDown() : void {

			parent::tearDown();

			unset( $this->condition_factory );
			unset( $this->subject );
		}

		/**
		 * @covers ::addRoute
		 */
		public function testAddRoute() {

			$route1 = Mockery::mock( RouteInterface::class );
			$route2 = Mockery::mock( RouteInterface::class );

			$route1->shouldReceive( 'getAttribute' )
			       ->with( 'name' )
			       ->andReturn( '' );

			$route2->shouldReceive( 'getAttribute' )
			       ->with( 'name' )
			       ->andReturn( '' );

			$this->subject->addRoute( $route1 );
			$this->subject->addRoute( $route2 );
			$this->assertSame( $route2, $this->subject->getRoutes()[1] );
		}

		/**
		 * @covers ::addRoute
		 */
		public function testAddRoute_SameRoute_Exception() {

			$this->expectExceptionMessage('Attempted to register a route twice');

			$route = Mockery::mock( RouteInterface::class )->shouldIgnoreMissing();

			$this->subject->addRoute( $route );
			$this->subject->addRoute( $route );
		}

		/**
		 * @covers ::addRoute
		 */
		public function testAddRoute_SameRouteName_Exception() {

			$this->expectExceptionMessage('The route name "foo" is already registered');

			$route1 = Mockery::mock( RouteInterface::class );
			$route2 = Mockery::mock( RouteInterface::class );

			$route1->shouldReceive( 'getAttribute' )
			       ->with( 'name' )
			       ->andReturn( 'foo' );

			$route2->shouldReceive( 'getAttribute' )
			       ->with( 'name' )
			       ->andReturn( 'foo' );

			$this->subject->addRoute( $route1 );
			$this->subject->addRoute( $route2 );
		}

		/**
		 * @covers ::removeRoute
		 */
		public function testRemoveRoute() {

			$route = Mockery::mock( RouteInterface::class );

			$route->shouldReceive( 'getAttribute' )
			      ->with( 'name' )
			      ->andReturn( '' );

			$this->subject->removeRoute( $route );
			$this->assertEquals( [], $this->subject->getRoutes() );
			$this->subject->addRoute( $route );
			$this->assertEquals( [ $route ], $this->subject->getRoutes() );
			$this->subject->removeRoute( $route );
			$this->assertEquals( [], $this->subject->getRoutes() );
		}

	}


	class HasRoutesTraitTestImplementation {

		use RegisteresRoutes;
	}
