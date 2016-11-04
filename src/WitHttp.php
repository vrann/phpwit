<?php
namespace Vrann\PhpWit;

use Psr\Log\LoggerInterface;

/**
 * Class WitHttp\Http implements transport layer to communicate with the Wit.AI API
 */
class WitHttp {
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string Access Token to Wit.AI
     */
    private $accessToken;

    /**
     * @var string Wit.AI endpoint url
     */
    private $apiUrl;

    /**
     * @var string version of Wit.AI API
     */
    private $apiVersion;

    /**
     * WitHttp constructor.
     *
     * @param LoggerInterface $logger
     * @param $accessToken
     * @param string $witAPIVersion
     * @param string $witAPIUrl
     */
    public function __construct(
        LoggerInterface $logger,
        $accessToken,
        $witAPIVersion = '20160516',
        $witAPIUrl = 'https://api.wit.ai'
    ) {
        $this->logger = $logger;
        $this->accessToken = $accessToken;
        $this->apiUrl = $witAPIUrl;
        $this->apiVersion = $witAPIVersion;
    }

    /**
     * Perform GET Request to Wit.AI API
     *
     * @param array $params
     * @return mixed
     * @throws WitException
     */
    public function requestGet($path, $params = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getRequestUrl($path, $params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return $this->sendRequest($ch);
    }

    /**
     * Perform POST Request to Wit.AI API
     *
     * @param array $params
     * @param string $data
     * @return array
     * @throws WitException
     */
    public function requestPost($path, $params = [], $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getRequestUrl($path, $params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return $this->sendRequest($ch);
    }

    /**
     * Get result of HTTP request
     *
     * @param $curlHandle resource
     * @return array
     * @throws WitException
     */
    private function sendRequest($curlHandle)
    {
        $serverOutput = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        curl_close($curlHandle);

        if ($httpCode > 200) {
            $this->logger->critical($serverOutput);
            throw new WitException(sprintf('Wit responded with status: %s (%s)', $httpCode, $serverOutput));
        }

        $this->logger->debug($serverOutput);
        $data = json_decode($serverOutput, true);

        return $data;
    }

    /**
     * Prepare request url
     *
     * @param $path
     * @param array $params
     * @return string
     */
    private function getRequestUrl($path, $params = [])
    {
        $url = $this->apiUrl . $path . "?" . http_build_query($params);
        $this->logger->debug($url);
        return $url;
    }

    /**
     * Prepare headers
     *
     * @return array
     */
    private function getHeaders()
    {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: ' . sprintf('application/vnd.wit.%s+json', $this->apiVersion),
            'Content-Type: application/json'
        ];
        $this->logger->debug(var_export($headers, true));
        return $headers;
    }
}
