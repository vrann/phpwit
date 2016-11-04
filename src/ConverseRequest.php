<?php
namespace Vrann\PhpWit;

use Psr\Log\LoggerInterface;

class ConverseRequest
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
     * Converse request to Wit.Ai API
     *
     * Returns what your bot should do next. The next step can be either answering to the user,
     * performing an action, or waiting for further requests.
     *
     * @param $sessionId
     * @param $message
     * @param null $context
     * @param bool $reset
     * @param bool $verbose
     * @return array
     */
    public function converse($sessionId, $message, $context = null, $reset = false, $verbose = false)
    {
        $params = [];
        if ($verbose) {
            $params['verbose'] = true;
        }
        $params['q'] = $message;
        $params['session_id'] = $sessionId;
        if ($reset) {
            $params['reset'] = true;
        }

        $data = null;
        if ($context !== null) {
            $data = json_encode($context);
        }

        return $this->transport->requestPost('/converse', $params, $data);
    }
}
