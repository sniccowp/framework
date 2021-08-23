<?php

namespace Snicco\Bootstrap;

use Throwable;
use Exception;
use ErrorException;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseEmitter;
use Snicco\Contracts\Bootstrapper;
use Snicco\Application\Application;
use Snicco\Contracts\ExceptionHandler;
use Snicco\ExceptionHandling\FatalError;

class HandlesExceptions implements Bootstrapper
{
    
    /**
     * Reserved memory so that errors can be displayed properly on memory exhaustion.
     */
    public static string $reserved_memory;
    private Application  $app;
    
    public function bootstrap(Application $app) :void
    {
        // This always to not use the global exception handling offered by the framework.
        if ($app->config('app.exception_handling') !== true) {
            return;
        }
        
        self::$reserved_memory = str_repeat('x', 10240);
        $this->app = $app;
        
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
        
        $this->configureErrorLog($app);
        $this->configureErrorDisplay($app);
        $this->disableWPFatalErrorHandler($app);
        
    }
    
    public function handleException(Throwable $e)
    {
        try {
            self::$reserved_memory = null;
            $this->getExceptionHandler()->report($e, $this->getRequest());
        } catch (Exception $e) {
            //
        }
        
        $this->renderHttpResponse($e);
        
    }
    
    /**
     * Convert PHP errors to ErrorException instances.
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        
        if ( ! error_reporting() || ! $level) {
            return;
        }
        
        $dont_abort_on_error_levels = $this->app->isLocal()
            ? $this->app->config('app.allow_local_error_levels', [])
            : $this->app->config('app.allow_production_error_levels', []);
        
        if (in_array($level, $dont_abort_on_error_levels)) {
            
            $this->getExceptionHandler()->report(
                new ErrorException($message, 0, $level, $file, $line),
                $this->getRequest()
            );
            
        }
        else {
            
            throw new ErrorException($message, 0, $level, $file, $line);
            
        }
        
    }
    
    public function handleShutdown()
    {
        if ( ! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalErrorFromPhpError($error, 0));
        }
    }
    
    /**
     * Create a new fatal error instance from an error array.
     *
     * @param  array  $error
     * @param  int|null  $traceOffset
     *
     * @return FatalError
     */
    private function fatalErrorFromPhpError(array $error, $traceOffset = null) :FatalError
    {
        return new FatalError($error['message'], 0, $error, $traceOffset);
    }
    
    private function getExceptionHandler() :ExceptionHandler
    {
        return $this->app->resolve(ExceptionHandler::class);
    }
    
    private function isFatal(int $type) :bool
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }
    
    private function getRequest() :Request
    {
        return $this->app->resolve(Request::class);
    }
    
    private function renderHttpResponse(Throwable $e)
    {
        
        try {
            /** @var ResponseEmitter $emitter */
            $emitter = $this->app->resolve(ResponseEmitter::class);
            $request = $this->getRequest();
            
            $response = $this->getExceptionHandler()->toHttpResponse($e, $request);
            $response = $emitter->prepare($response, $request);
            $emitter->emit($response);
            
        } catch (Throwable $e) {
            // Nothing we can do.
        }
        
    }
    
    private function configureErrorLog(Application $app)
    {
        
        if ($app->config('logging.disable_native_log', false)) {
            
            ini_set('log_errors', 'Off');
            
        }
        else {
            
            ini_set('log_errors', 'On');
            
            if ($log_path = $app->config('app.error_log_path')) {
                
                ini_set('error_log', $app->basePath($log_path));
                
            }
            else {
                
                ini_set('error_log', WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'debug.log');
                
            }
        }
        
    }
    
    private function configureErrorDisplay(Application $app)
    {
        if ($app->isRunningUnitTest() || $app->isLocal()) {
            ini_set('display_errors', 'On');
        }
        
        else {
            ini_set('display_errors', 'Off');
        }
    }
    
    private function disableWPFatalErrorHandler(Application $app)
    {
        
        $disabled_in_config = defined('WP_DISABLE_FATAL_ERROR_HANDLER')
                              && WP_DISABLE_FATAL_ERROR_HANDLER === true;
        
        if ($disabled_in_config) {
            return;
        }
        
        if ( ! is_file(WP_CONTENT_DIR.'/php-error.php')) {
            
            file_put_contents(
                WP_CONTENT_DIR.'/php-error.php',
                '<?php // generated to stop WP_Fatal_Error_Handler output.'
            );
            
        }
        
    }
    
}