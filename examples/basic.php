<?php

include __DIR__ . "/../vendor/autoload.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use \Vrann\PhpWit\ConverseRequest;
use \Vrann\PhpWit\WitHttp;
use \Vrann\PhpWit\Wit;

$WIT_ACCESS_TOKEN = getenv('WIT_ACCESS_TOKEN');

$logger = new Logger('phpwit_logger');
$logger->pushHandler(new StreamHandler('/tmp/phpwit.log', Logger::DEBUG));
$logger->addInfo('Start basic example, Access token: ' . $WIT_ACCESS_TOKEN);

$actions = [
    'send' => function($request, $response) {
        echo $response['text'] . "\n";
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
