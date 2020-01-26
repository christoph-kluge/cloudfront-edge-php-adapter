<?php

namespace Sikei\CloudfrontEdge\Laravel;

use Sikei\CloudfrontEdge\Symfony\RequestFactory as SymfonyRequestFactory;
use Illuminate\Http\Request;

class RequestFactory
{
    private $factory;

    public function __construct(SymfonyRequestFactory $factory)
    {
        $this->factory = $factory;
    }

    public function make(array $event): Request
    {
        return Request::createFromBase(
            $this->factory->make($event)
        );
    }
}
