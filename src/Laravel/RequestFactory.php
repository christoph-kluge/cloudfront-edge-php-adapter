<?php

namespace Sikei\CloudfrontEdge\Laravel;

use Bref\Context\Context;
use Sikei\CloudfrontEdge\Symfony\RequestFactory as SymfonyRequestFactory;
use Illuminate\Http\Request;

class RequestFactory
{
    private $factory;

    public function __construct(SymfonyRequestFactory $factory)
    {
        $this->factory = $factory;
    }

    public function make(array $event, Context $context): Request
    {
        return Request::createFromBase(
            $this->factory->make($event, $context)
        );
    }
}
