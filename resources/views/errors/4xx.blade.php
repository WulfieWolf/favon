@include('errors.layout', [
    'status' => method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400,
    'errorKey' => '4xx',
    'exceptionMessage' => null,
])
