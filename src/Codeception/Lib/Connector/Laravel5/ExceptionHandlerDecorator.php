<?php
namespace Codeception\Lib\Connector\Laravel5;

use Throwable;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;

/**
 * Class ExceptionHandlerDecorator
 *
 * @package Codeception\Lib\Connector\Laravel5
 */
class ExceptionHandlerDecorator implements ExceptionHandlerContract
{
    /**
     * @var ExceptionHandlerContract
     */
    private $laravelExceptionHandler;

    /**
     * @var boolean
     */
    private $exceptionHandlingDisabled = true;

    /**
     * ExceptionHandlerDecorator constructor.
     *
     * @param object $laravelExceptionHandler
     */
    public function __construct($laravelExceptionHandler)
    {
        $this->laravelExceptionHandler = $laravelExceptionHandler;
    }

    /**
     * @param boolean $exceptionHandlingDisabled
     */
    public function exceptionHandlingDisabled($exceptionHandlingDisabled)
    {
        $this->exceptionHandlingDisabled = $exceptionHandlingDisabled;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable $e
     * @return void
     */
    public function report(Throwable $e)
    {
        $this->laravelExceptionHandler->report($e);
    }

    /**
      * Determine if the exception should be reported.
     *
     * @param  \Throwable $e
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        return $this->exceptionHandlingDisabled;
    }

    /**
     * @param $request
     * @param Throwable $e
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        $response = $this->laravelExceptionHandler->render($request, $e);

        if ($this->exceptionHandlingDisabled && $this->isSymfonyExceptionHandlerOutput($response->getContent())) {
            // If content was generated by the \Symfony\Component\Debug\ExceptionHandler class
            // the Laravel application could not handle the exception,
            // so re-throw this exception if the Codeception user disabled Laravel's exception handling.
            throw $e;
        }

        return $response;
    }

    /**
     * Check if the response content is HTML output of the Symfony exception handler class.
     *
     * @param string $content
     * @return bool
     */
    private function isSymfonyExceptionHandlerOutput($content)
    {
        return strpos($content, '<div id="sf-resetcontent" class="sf-reset">') !== false ||
            strpos($content, '<div class="exception-summary">') !== false;
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  \Throwable $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e)
    {
        $this->laravelExceptionHandler->renderForConsole($output, $e);
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->laravelExceptionHandler, $method], $args);
    }
}
