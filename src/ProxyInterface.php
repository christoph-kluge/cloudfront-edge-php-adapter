<?php namespace Sikei\CloudfrontEdge;

use Symfony\Component\HttpFoundation\Response;

interface ProxyInterface
{
    /**
     * Allows to perform actions or manipulate the incoming event from cloudfront
     * before it's passed to the application.
     *
     * @param array $event
     * @return array
     */
    public function incoming(array $event): array;

    /**
     * Allows to perform actions or manipulate the outgoing cloudfront event
     * after the application handled it and before it's passed to cloudfront.
     *
     * @param array $event
     * @return array
     */
    public function outgoing(array $event): array;
}
