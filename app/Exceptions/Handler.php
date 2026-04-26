<?php

namespace App\Exceptions;

use App\Traits\ApiResponser;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;

use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponser;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {

        if($exception instanceof UnauthorizedException){

            return response()->json(['error' => ['msg' => $exception->getMessage(), 'code' => Response::HTTP_FORBIDDEN]]);
        
        }

        if($exception instanceof CardException){

            return response()->json(['error' => ['msg' => $exception->getMessage(), 'code' => Response::HTTP_FORBIDDEN]]);

        }

        if($exception instanceof InvalidRequestException){

            return response()->json(['error' => ['msg' => $exception->getMessage(), 'code' => Response::HTTP_BAD_REQUEST]]);

        }

        if($exception instanceof HttpException){
            $code = $exception->getStatusCode();
            $message = Response::$statusTexts[$code];
            return $this->errorResponse($message, $code);
        }

        if ($exception instanceof ModelNotFoundException) {
            $model = strtolower(class_basename($exception->getModel()));
            return response()->json(['error' => ['message' => "Does not exist any instance of {$model} with the given id", 'code' => 404]], 404);
        }

        if ($exception instanceof AuthorizationException) {
            return response()->json(['error' => ['message' => $exception->getMessage(), 'code' => Response::HTTP_FORBIDDEN]]);
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json(['error' => ['message' => $exception->getMessage(), 'code' => Response::HTTP_UNAUTHORIZED]]);
        }

        if ($exception instanceof ValidationException) {

            $errors = $exception->validator->errors()->getMessages();

            return $this->errorResponse($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if(env('APP_DEBUG', false)){
            return parent::render($request, $exception);
        }

        return $this->errorResponse('Unexpected error. Try later', Response::HTTP_INTERNAL_SERVER_ERROR);

    }

}
