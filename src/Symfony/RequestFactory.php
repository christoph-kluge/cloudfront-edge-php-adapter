<?php

namespace Sikei\CloudfrontEdge\Symfony;

use Symfony\Component\HttpFoundation\Request;

class RequestFactory
{

    public function fromCloudfrontEvent(array $event): Request
    {
        $cfConfig = $event['Records'][0]['cf']['config'];
        $cfRequest = $event['Records'][0]['cf']['request'];

        Request::enableHttpMethodParameterOverride();

        $request = Request::create(
            $this->getUri($cfConfig, $cfRequest),
            $this->getMethod($cfRequest),
            $this->getParameters($cfRequest),
            $this->getCookies($cfRequest),
            $this->getFiles($cfRequest),
            $this->getServer($cfRequest),
            $this->getContent($cfRequest)
        );

        return $request;
    }

    private function getUri(array $cfConfig, array $cfRequest): string
    {
        $domain = $cfConfig['distributionDomainName'];
        if (isset($cfRequest['headers']['host'][0]['value'])) { // optional host header
            $domain = $cfRequest['headers']['host'][0]['value'];
        }

        $isSecure = true;
        $scheme = 'http://';
        if ($isSecure) {
            $scheme = 'https://';
        }

        $query = '';
        if (strlen($cfRequest['querystring']) > 0) {
            $query = '?' . $cfRequest['querystring'];
        }

        return $scheme . $domain . $cfRequest['uri'] . $query;
    }

    private function getMethod(array $cfRequest): string
    {
        return $cfRequest['method'];
    }

    private function getContent(array $cfRequest)
    {
        $content = null;
        if (!empty($cfRequest['body']['data']) && $cfRequest['body']['encoding'] === 'base64') {
            $content = base64_decode($cfRequest['body']['data']);
        }
        return $content;
    }

    private function getServer(array $cfRequest): array
    {
        $server = [];

        // add clientIp to server
        $server['REMOTE_ADDR'] = $cfRequest['clientIp'];

        // https
        $server['HTTPS'] = 'on';

        // Add headers to server
        foreach ($cfRequest['headers'] as $key => $values) {
            $origKey = strtoupper($values[0]['key']);
            $origKey = str_replace('-', '_', $origKey);

            // CONTENT_* are not prefixed with HTTP_
            if (0 === strpos($origKey, 'CONTENT_')) {
                $server[$origKey] = $values[0]['value'];
            } else {
                $server['HTTP_' . $origKey] = $values[0]['value'];
            }
        }
        return $server;
    }

    private function getParameters(array $cfRequest): array
    {
        if (!in_array($this->getMethod($cfRequest), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return [];
        }

        if (!isset($cfRequest['headers']['content-type'][0]['value'])
            || $cfRequest['headers']['content-type'][0]['value'] !== 'application/x-www-form-urlencoded') { // check
            return [];
        }

        parse_str($this->getContent($cfRequest), $data);

        return $data;
    }

    private function getCookies(array $cfRequest): array
    {
        if (!isset($cfRequest['headers']['cookie'][0]['value'])) {
            return [];
        }

        $cookieHeaderLine = $cfRequest['headers']['cookie'][0]['value'];
        $cookies = array_filter(explode(';', $cookieHeaderLine));

        $cookies = array_map(function ($item) {
            list($key, $value) = explode('=', $item, 2);
            return [
                'key' => trim($key),
                'value' => urldecode(trim($value)),
            ];
        }, $cookies);

        $new = [];
        foreach ($cookies as $item) {
            $new[$item['key']] = $item['value'];
        }

        return $new;
    }

    private function getFiles(array $cfRequest): array
    {
        if (!isset($cfRequest['headers']['content-type'][0]['value'])) {
            return [];
        }

        $contentType = $cfRequest['headers']['content-type'][0]['value'];
        if ('multipart/form-data' !== substr($contentType, 0, 19)) {
            return [];
        }

        $content = $this->getContent($cfRequest);

        preg_match('#multipart/form-data; boundary=(.*)#', $contentType, $matches);

        $fileDelimiter = $matches['1'];

        $rawFiles = explode($fileDelimiter, $content);

        $files = [];
        foreach ($rawFiles as $rawFile) {
            if (strlen($rawFile) <= 8) { // @TODO: this is super wrong like that.. need to fix it later
                continue;
            }

            list($rawFileHeaders, $fileBody) = explode("\r\n\r\n", $rawFile, 2);

            $tempFile = tempnam(sys_get_temp_dir(), uniqid());
            file_put_contents($tempFile, $fileBody);

            // @FIXME: I guess there is a bit smarter way to do this.. check if there is existing snippets for this
            $fileHeaders = [];
            foreach (explode("\r\n", $rawFileHeaders) as $headerLine) {
                if (empty($headerLine)) {
                    continue;
                }

//                var_dump($headerLine);
                list($fileHEader, $fileHEaderValue) = explode(': ', $headerLine);

                $fileHeaders[$fileHEader] = $fileHEaderValue;
            }

            // @FIXME: I guess there is a bit smarter way to do this.. check if there is existing snippets for this
            preg_match('#form-data; name="(.*)"; filename="(.*)"#i', $fileHeaders['Content-Disposition'], $matches);

            $formName = $matches[1];
            $origFilename = $matches[2];
            $type = $fileHeaders['Content-Type'] ?? '';
            $size = strlen($fileBody);
            $tmp_name = $tempFile;
            $error = 0; // @FIXME: check what kind of errors might appear with file uploads and if we need to take care of them

//            $_FILES['userfile']['name'] // The original name of the file on the client machine.
//            $_FILES['userfile']['type'] // The mime type of the file, if the browser provided this information. An example would be "image/gif". This mime type is however not checked on the PHP side and therefore don't take its value for granted.
//            $_FILES['userfile']['size'] // The size, in bytes, of the uploaded file.
//            $_FILES['userfile']['tmp_name'] // The temporary filename of the file in which the uploaded file was stored on the server.
//            $_FILES['userfile']['error'] // The error code associated with this file upload.

            $files[$formName] = [
                'name' => $origFilename,
                'type' => $type,
                'size' => $size,
                'tmp_name' => $tmp_name,
                'error' => $error,
            ];
        }

        return $files;
    }
}