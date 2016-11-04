<?php
namespace Vrann\PhpWit;

use Psr\Log\LoggerInterface;

class Wit {
    /**
     * @var array
     */
    private $actions = [];

    private $learnMore = 'Learn more at https://wit.ai/docs/quickstart';

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $sessions = [];

    /**
     * @var ConverseRequest
     */
    private $converseRequest;

    /**
     * Wit constructor.
     * @param $actions
     * @param ConverseRequest $converseRequest
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConverseRequest $converseRequest,
        LoggerInterface $logger,
        $actions = []
    ) {
        $this->logger = $logger;
        $this->actions = $actions;
        $this->converseRequest = $converseRequest;
    }

    private function continueRunActions($sessionId, $currentRequest, $message, $context, $i, $verbose)
    {
        if ($i < 0) {
            $this->logger->warning('Max steps reached, stopping.');
            return $context;
        }
        if ($currentRequest !== $this->sessions[$sessionId]) {
            return $context;
        }
        $json = $this->converseRequest->converse($sessionId, $message, $context, null, $verbose);
        if (!isset($json['type'])) {
            throw new WitException('Couldn\'t find type in Wit response');
        }
        $this->logger->debug(sprintf('Context: %s', $context));
        $this->logger->debug(sprintf('Response type: %s', $json['type']));

        if ($json['type'] == 'merge') {
            # backwards-cpmpatibility with API version 20160516
            $json['type'] = 'action';
            $json['action'] = 'merge';
        } else if ($json['type'] == 'error') {
            throw new WitException('Oops, I don\'t know what to do.');
        } else if ($json['type'] == 'stop') {
            return $context;
        }

        $request = [
            'session_id' => $sessionId,
            'context' => $context,
            'text' => $message,
            'entities' => $json['entities']
        ];

        if ($json['type'] == 'msg') {
            if (!isset($this->actions['send'])) {
                throw new WitException('No \'send\' action found.');
            }
            $response = [
                'text' => $json['msg'],
                'quickreplies' => isset($json['quickreplies']) ? $json['quickreplies'] : null,
            ];
            $this->actions['send']($request, $response);
        } else if ($json['type'] == 'action') {
            $action = $json['action'];
            if (!isset($this->actions[$action])) {
                throw new WitException(sprintf('No \'%s\' action found.', $action));
            }
            $context = $this->actions[$action]($request);
            if ($context == null) {
                $context = [];
            }
            if ($currentRequest !== $this->sessions[$sessionId]) {
                return $context;
            }
        } else {
            $this->logger->debug('unknown response type ' . $json['type']);
            throw new WitException('unknown response type ' . $json['type']);
        }
        return $this->continueRunActions($sessionId, $currentRequest, null, $context, $i - 1, $verbose);
    }

    public function runActions($sessionId, $message, $context, $maxSteps, $verbose)
    {
        if (empty($this->actions)) {
            throw new WitException(sprintf('You must provide the `actions` parameter to be able to use runActions. %s',
                $this->learnMore));
        }

        # Figuring out whether we need to reset the last turn.
        # Each new call increments an index for the session.
        # We only care about the last call to run_actions.
        # All the previous ones are discarded (preemptive exit).
        $currentRequest = $this->sessions[$sessionId] = isset($this->sessions[$sessionId]) ?  $this->sessions[$sessionId] + 1 : 1;
        $context = $this->continueRunActions($sessionId, $currentRequest, $message, $context, $maxSteps, $verbose);
        if ($currentRequest == $this->sessions[$sessionId]) {
            unset($this->sessions[$sessionId]);
        }
        return $context;
    }
}