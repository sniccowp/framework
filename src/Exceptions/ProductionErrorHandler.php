<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Exceptions;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;
	use Throwable;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\Http\Request;
	use WPEmerge\Http\Response;
	use WPEmerge\Traits\HandlesExceptions;

	class ProductionErrorHandler implements ErrorHandlerInterface {

		use HandlesExceptions;

		/**
		 * @var bool
		 */
		private $is_ajax;

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		public function __construct( ContainerAdapter $container, bool $is_ajax ) {

			$this->is_ajax = $is_ajax;
			$this->container = $container;
		}

		public function handleException ( $exception, $in_routing_flow = false, RequestInterface $request = null ) {

			$request = $request ?? $this->container->make(RequestInterface::class);

			$response = $this->determineResponse($exception, $request );

			if ( $in_routing_flow ) {

				return $response;

			}

			if ( $request ) {

				$response->prepareForSending($request);

			}

			$response->sendHeaders();
			$response->sendBody();

			// Shuts down the script
			UnrecoverableExceptionHandled::dispatch();

		}

		public function transformToResponse( Throwable $exception, RequestInterface $request = null ) : ResponseInterface {

			return $this->handleException( $exception, true, $request );

		}

		private function contentType() : string {

			return ( $this->is_ajax ) ? 'application/json' : 'text/html';

		}

		private function defaultResponse() : ResponseInterface {

			return (new Response( 'Internal Server Error', 500))
				->setType($this->contentType());

		}

		private function determineResponse (Throwable $e, RequestInterface $request ) : ResponseInterface {


			if ( method_exists($e, 'render') ) {

				/** @var ResponseInterface $response */
				$response = $this->container->call([$e, 'render'], ['request' => $request] );

				return $response->setType($this->contentType());

			}

			return $this->defaultResponse();

		}


	}