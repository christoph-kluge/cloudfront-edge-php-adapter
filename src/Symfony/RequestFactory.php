<?php

namespace Sikei\CloudfrontEdge\Symfony;

use Symfony\Component\HttpFoundation\Request;

class RequestFactory
{

    public function make(array $event): Request
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

        $s3OriginHeaders = [];
        $customOriginHeaders = [];
        $cfHeaders = $this->headersToServer($cfRequest['headers']);

        if (isset($cfRequest['origin']['s3']['customHeaders'])) {
            $s3OriginHeaders = $this->headersToServer($cfRequest['origin']['s3']['customHeaders']);
        }

        if (isset($cfRequest['origin']['custom']['customHeaders'])) {
            $customOriginHeaders = $this->headersToServer($cfRequest['origin']['custom']['customHeaders']);
        }

        return array_merge($server, $cfHeaders, $s3OriginHeaders, $customOriginHeaders);
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

        $fileDelimiter = $matches[1];

        $rawFiles = explode($fileDelimiter, $content);

        // remove first element after "not-so-smart-explode"
        $firstElement = reset($rawFiles);
        if ($firstElement == '--') {
            array_shift($rawFiles);
        }

        // remove last element after "not-so-smart-explode"
        $lastElement = end($rawFiles);
        if ($lastElement == '--' || $lastElement == "--\r\n") {
            array_pop($rawFiles);
        }

        // remove all remaining "--" parts and trim returns and newlines
        $rawFiles = array_map(function ($item) {
            return trim(trim($item, '--'));
        }, $rawFiles);

        $files = [];
        foreach ($rawFiles as $rawFile) {
            list($rawFileHeaders, $fileBody) = explode("\r\n\r\n", $rawFile, 2);

            $rawFileHeaders = explode("\r\n", $rawFileHeaders);
            $rawFileHeaders = array_filter($rawFileHeaders, function ($item) use ($fileDelimiter) {
                return stripos($item, $fileDelimiter) === false;
            });

            $fileHeaders = [];
            foreach ($rawFileHeaders as $line) {
                list($key, $value) = explode(': ', $line, 2);
                $fileHeaders[strtolower($key)] = $value;
            }

            // if content-type is not set inside the content, then it's not a file but a normal form field
            if (!isset($fileHeaders['content-type'])) {
                continue;
            }

            $tempFile = tempnam(sys_get_temp_dir(), uniqid());
            file_put_contents($tempFile, $fileBody);

            preg_match('#^form-data; name="(.*)"; filename="(.*)"#i', $fileHeaders['content-disposition'], $matches);
            $formName = $matches[1];
            $origFilename = $matches[2];
            $type = $fileHeaders['content-type'] ?? '';
            $size = strlen($fileBody);
            $tmp_name = $tempFile;
            $error = 0; // @FIXME: check what kind of errors might appear with file uploads and if we need to take care of them

            preg_match_all('#([a-z0-9-_]+)?#i', $formName, $matches);
            $keys = array_filter($matches[0]);

            $files = $this->addFile($keys, [
                'name' => $origFilename,
                'type' => $type,
                'size' => $size,
                'tmp_name' => $tmp_name,
                'error' => $error,
            ], null, $files);

//            $_FILES['userfile']['name'] // The original name of the file on the client machine.
//            $_FILES['userfile']['type'] // The mime type of the file, if the browser provided this information. An example would be "image/gif". This mime type is however not checked on the PHP side and therefore don't take its value for granted.
//            $_FILES['userfile']['size'] // The size, in bytes, of the uploaded file.
//            $_FILES['userfile']['tmp_name'] // The temporary filename of the file in which the uploaded file was stored on the server.
//            $_FILES['userfile']['error'] // The error code associated with this file upload.
        }

        return $files;
    }

    private function addFile(array $inputNames, array $fileData, string $inputName = null, array $files = []): array
    {
        if (empty($inputNames)) {
            if (!isset($files[$inputName])) {
                $files[$inputName] = [];
            }

            $singleEntryExists = isset($files[$inputName]['name']) && !is_array($files[$inputName]['name']);
            $multiEntryExists = isset($files[$inputName]['name']) && is_array($files[$inputName]['name']);
            if ($singleEntryExists) { // transform current item to to index-based
                $current = $files[$inputName];

                // transform existing
                $files[$inputName] = [];
                foreach (array_keys($current) as $key) {
                    $files[$inputName][$key][0] = $current[$key];
                }

                // add new entry
                $idx = count($files[$inputName]['name']);
                foreach (array_keys($fileData) as $key) {
                    $files[$inputName][$key][$idx] = $fileData[$key];
                }

                return $files;

            } elseif ($multiEntryExists) { // add new entry to existing inputName
                $idx = count($files[$inputName]['name']);
                foreach (array_keys($fileData) as $key) {
                    $files[$inputName][$key][$idx] = $fileData[$key];
                }
                return $files;
            }

            // first entry for the inputName
            $files[$inputName] = $fileData;

            return $files;
        }

        $nextName = array_shift($inputNames);

        if (!is_null($inputName)) {
            if (isset($files[$inputName])) {
                return [
                    $inputName => $this->addFile($inputNames, $fileData, $nextName, $files[$inputName]),
                ];
            }

            return [
                $inputName => $this->addFile($inputNames, $fileData, $nextName, $files),
            ];
        }

        return $this->addFile($inputNames, $fileData, $nextName, $files);
    }

    /**
     * @param array $cfHeaders
     * @return array
     */
    private function headersToServer(array $cfHeaders): array
    {
        $server = [];

        // Add headers to server
        foreach ($cfHeaders as $key => $values) {
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
}
