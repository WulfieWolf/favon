@include('errors.layout', [
    'status' => method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500,
    'errorKey' => '5xx',
    'exceptionMessage' => null,
])
