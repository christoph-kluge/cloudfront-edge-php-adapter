<?php namespace Sikei\CloudfrontEdge;

use Bref\Context\Context;

interface ProxyInterface
{
    /**
     * Allows to perform actions or manipulate the incoming event from cloudfront
     * before it's passed to the application.
     *
     * @param array $event
     * @param Context $context
     * @return array
     */
    public function incoming(array $event, Context $context): array;

    /**
     * Allows to perform actions or manipulate the outgoing cloudfront event
     * after the application handled it and before it's passed to cloudfront.
     *
     * @param array $event
     * @param Context $context
     * @return array
     */
    public function outgoing(array $event, Context $context): array;
}
