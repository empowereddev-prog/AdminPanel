<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // API requests must always get JSON. Without this a client that omits
        // Accept: application/json received a 302 redirect from validation, an
        // HTML error page from findOrFail, and a redirect back from throttling.
        if ($this->isApiRequest($request)) {
            return $this->renderApiException($request, $exception);
        }

        // Custom handling for Too Many Requests exceptions
        if ($exception instanceof TooManyRequestsHttpException) {
            return redirect()->back()->withErrors([
                'rate_limit' => 'Too many submissions. Please wait and try again later.',
            ])->withInput();
        }

        return parent::render($request, $exception);
    }

    protected function isApiRequest($request): bool
    {
        return $request->is('api/*') || $request->is('api');
    }

    /**
     * Map an exception onto the API envelope.
     *
     * Status codes match what Laravel already returns to a client that sends
     * Accept: application/json (422/401/403/404), so this only changes the
     * broken no-Accept path. v1 additionally keeps Laravel's own `errors` key
     * alongside the envelope, so nothing the client reads today disappears.
     */
    protected function renderApiException($request, Throwable $e)
    {
        // Exceptions that render themselves keep doing so, as the parent handler
        // allows. Nothing in app/ defines one today; this keeps a future custom
        // exception from silently becoming a 500 here.
        if (method_exists($e, 'render') && $rendered = $e->render($request)) {
            return $rendered;
        }

        if ($e instanceof \Illuminate\Contracts\Support\Responsable) {
            return $e->toResponse($request);
        }

        // A response the caller already built (FormRequest::failedValidation,
        // abort_if, and anything else throwing HttpResponseException) must be
        // returned as-is. The parent handler does this first; bypassing it
        // would turn every one of those into a 500.
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if ($e instanceof ValidationException) {
            $errors = $e->errors();

            return ApiResponse::error(
                collect($errors)->flatten()->first() ?? 'The given data was invalid.',
                422,
                $errors,
                null,
                // Laravel's native validation body, so existing clients keep it.
                ['errors' => $errors]
            );
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if ($e instanceof AuthorizationException) {
            return ApiResponse::error(
                $e->getMessage() ?: 'This action is unauthorized.',
                403
            );
        }

        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::error('Resource not found.', 404);
        }

        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::error('Endpoint not found.', 404);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error('Method not allowed for this endpoint.', 405);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return ApiResponse::error('Too many requests. Please wait and try again later.', 429);
        }

        if ($e instanceof HttpExceptionInterface) {
            return ApiResponse::error(
                $e->getMessage() ?: 'Request failed.',
                $e->getStatusCode()
            );
        }

        // Never leak driver/SQL text to a mobile client.
        return ApiResponse::error(
            config('app.debug')
                ? $e->getMessage()
                : 'Something went wrong. Please try again later.',
            500,
            config('app.debug')
                ? ['exception' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()]
                : null
        );
    }
}
