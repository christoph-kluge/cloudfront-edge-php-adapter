<?php namespace Sikei\CloudfrontEdge\Laravel;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestResponseFactory
{

    private $request;
    private $response;

    public function __construct(RequestFactory $request, ResponseFactory $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function request(array $event): Request
    {
        return $this->request->make($event);
    }

    public function response(Response $response): array
    {
        return $this->response->make($response);
    }
}
