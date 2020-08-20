<?php namespace Sikei\CloudfrontEdge\Laravel;

use Bref\Context\Context;
use Illuminate\Contracts\Http\Kernel;
use Sikei\CloudfrontEdge\ProxyInterface;

class CloudfrontEventHandler
{

    private $kernel;
    private $factory;
    /** @var ProxyInterface[] */
    private $proxies = [];

    public function __construct(Kernel $kernel, RequestResponseFactory $factory)
    {
        $this->kernel = $kernel;
        $this->factory = $factory;
    }

    public function addProxy(ProxyInterface $proxy)
    {
        $this->proxies[class_basename($proxy)] = $proxy;

        return $this;
    }

    public function __invoke(array $event, Context $context): array
    {
        foreach ($this->proxies as $proxy) {
            $event = $proxy->incoming($event, $context);
        }

        $response = $this->kernel->handle(
            $request = $this->factory->request($event, $context)
        );

        $cfResponse = $this->factory->response($response, $context, $event);

        $this->kernel->terminate($request, $response);

        foreach ($this->proxies as $proxy) {
            $cfResponse = $proxy->outgoing($cfResponse, $context);
        }

        return $cfResponse;
    }
}
