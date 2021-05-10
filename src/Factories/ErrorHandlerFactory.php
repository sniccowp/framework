<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Factories;

	use Whoops\Handler\JsonResponseHandler;
	use Whoops\Handler\PrettyPageHandler;
	use Whoops\Run;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Exceptions\DebugErrorHandler;
	use WPEmerge\Exceptions\ProductionErrorHandler;

	class ErrorHandlerFactory {

		const allowed_editors = [

			'emacs',
			'idea',
			'macvim',
			'phpstorm',
			'sublime',
			'textmate',
			'xdebug',
			'vscode',
			'atom',
			'espresso'

		];



		public static function make( RequestInterface $request, bool $is_debug, bool $is_ajax, string $editor = null ) {

			if ( ! $is_debug ) {

				return new ProductionErrorHandler();

			}

			$whoops = new Run();
			$pretty_page_handler = new PrettyPageHandler();
			$pretty_page_handler->handleUnconditionally(true);


			if ( $is_ajax ) {

				$json_handler = new JsonResponseHandler();
				$json_handler->addTraceToOutput(true);
				$whoops->appendHandler($json_handler);

			}

			if ( $editor ) {

				if ( ! in_array($editor , static::allowed_editors ) ) {

					throw new ConfigurationException(
						'The editor: ' . $editor . ' is not supported by Whoops.'
					);

				}

				$pretty_page_handler->setEditor($editor);

			}

			$whoops->appendHandler($pretty_page_handler);
			$whoops->allowQuit(false);
			$whoops->writeToOutput(true);


			return new DebugErrorHandler( $whoops );

		}

	}