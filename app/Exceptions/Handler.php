<?php

namespace App\Exceptions;

use Config;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        if ($e instanceof \Exception) {
            $status_code = '';
            if (ExceptionHandler::isHttpException($e)) {
                $status_code = $e->getStatusCode();

            }
            $debugSetting = Config::get('app.debug');
            Config::set('app.debug', true);
            if (ExceptionHandler::isHttpException($e)) {
                $content = ExceptionHandler::toIlluminateResponse(ExceptionHandler::renderHttpException($e), $e);
            } else {
                $content = ExceptionHandler::toIlluminateResponse(ExceptionHandler::convertExceptionToResponse($e), $e);
            }
            Config::set('app.debug', $debugSetting);
            $environment = getenv('APP_ENV');
            if ($environment == 'live' && $status_code != 404 && $status_code != 401) {
                $appAdminEmail = getenv('APP_ADMIN_EMAIL');
                $bccEmail = getenv('BCC_EMAIL');
                $mailFrom = getenv('MAIL_FROM');
                $mailFromName = getenv('MAIL_FROM_NAME');
                $environment = getenv('APP_ENV');
                $data['content'] = (!isset($content->original)) ? $e->getMessage() : $content->original;
                Mail::send('emails.exceptions', compact('data'), function ($m) use ($appAdminEmail, $bccEmail, $mailFrom, $mailFromName, $environment) {
                    $m->to($appAdminEmail, 'EverGenius Server')
                        ->from($mailFrom, $mailFromName)
                        ->subject('!! EverGenius Server Error-'.$environment);
                        //->bcc($bccEmail);
                });
            }
        }
        return parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof \Yajra\DataTables\Exception) {
            return response([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Laravel Error Handler',
            ]);
        }
        return parent::render($request, $e);
    }

}
