<?php
namespace Vrann\PhpWit;

use Psr\Log\LoggerInterface;

class MessageRequest
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WitHttp
     */
    private $transport;

    /**
     * Wit constructor.
     *
     * @param WitHttp $transport
     * @param LoggerInterface $logger
     */
    public function __construct(
        WitHttp $transport,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->transport = $transport;
    }

    /**
     * Message request to Wit.AI
     *
     * Returns the extracted meaning from a sentence, based on the app data.
     * Note that you may use JSONP to do cross-domain/cross-origin requests.
     *
     * @param $message
     * @param bool $verbose
     * @return mixed
     */
    public function message($message, $verbose = false)
    {
        $params = [];
        if ($verbose) {
            $params['verbose'] = true;
        }
        $params['q'] = $message;
        return $this->transport->requestGet('/message', $params);
    }
}
