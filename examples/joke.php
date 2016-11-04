<?php

include __DIR__ . "/../vendor/autoload.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use \Vrann\PhpWit\ConverseRequest;
use \Vrann\PhpWit\WitHttp;
use \Vrann\PhpWit\Wit;

$WIT_ACCESS_TOKEN = 'SGQ3V7Q2JQAR4NDMEX5GCXQZDW355KIX'; //getenv('WIT_ACCESS_TOKEN');

$logger = new Logger('phpwit_logger');
$logger->pushHandler(new StreamHandler('/tmp/phpwit.log', Logger::DEBUG));
$logger->addInfo('Start basic example, Access token: ' . $WIT_ACCESS_TOKEN);


$allJokes = [
    'chuck'=> [
        'Chuck Norris counted to infinity - twice.',
        'Death once had a near-Chuck Norris experience.',
    ],
    'tech' => [
        'Did you hear about the two antennas that got married? The ceremony was long and boring, but the reception was great!',
        'Why do geeks mistake Halloween and Christmas? Because Oct 31 === Dec 25.',
    ],
    'default'=> [
        'Why was the Math book sad? Because it had so many problems.',
    ]
];


$actions = [
    'send' => function($request, $response) {
        echo $response['text'] . "\n";
    },
    'merge' => function($request) {
        $context = $request['context'];
        $entities = $request['entities'];
        if (isset($context['joke'])) {
            unset($context['joke']);
        }
        if (isset($entities['category'])) {
            $context['category'] = $entities['category'][0]['value'];
        }
        if (isset($entities['sentiment'])) {
            $sentiment = $entities['sentiment'][0]['value'];
            $context['ack'] = $sentiment == 'positive' ? 'Glad you liked it.' : 'Hmm.';
        } else if (isset($context['ack'])) {
            unset($context['ack']);
        }
        return $context;
    },
    'select_joke' => function($request) {
        $context = $request['context'];
        $jokes = isset($allJokes[$context['cat']]) ? $allJokes[$context['cat']] : $allJokes['default'];
        $context['joke'] = $jokes[0];
        return $context;
    }
];
$wit = new Wit(new ConverseRequest(new WitHttp($logger, $WIT_ACCESS_TOKEN), $logger), $logger, $actions);

$sessionId = md5(uniqid(rand(), true));
$handle = fopen ("php://stdin", "r");

echo "Input text which you'd like to to be responded by bot:\n";
while(true) {
    $message = fgets($handle);
    $wit->runActions($sessionId, $message, null, 5, true);
}
fclose($handle);
