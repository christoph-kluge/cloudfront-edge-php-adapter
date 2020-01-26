<?php namespace Sikei\CloudfrontEdge\Laravel;

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

    public function __invoke(array $event): array
    {
        foreach ($this->proxies as $proxy) {
            $event = $proxy->incoming($event);
        }

        $response = $this->kernel->handle(
            $request = $this->factory->request($event)
        );

        $cfResponse = $this->factory->response($response);

        $this->kernel->terminate($request, $response);

        foreach ($this->proxies as $proxy) {
            $cfResponse = $proxy->outgoing($cfResponse);
        }

        return $cfResponse;
    }
}
