<?php namespace Sikei\CloudfrontEdge\Laravel;

use Bref\Context\Context;
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

    public function request(array $event, Context $context): Request
    {
        return $this->request->make($event, $context);
    }

    public function response(Response $response, Context $context, array $event): array
    {
        return $this->response->make($response, $context, $event);
    }
}
