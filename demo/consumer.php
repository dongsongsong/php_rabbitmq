<?php
require_once 'config.php';
require_once '../PhpAmqpLib/Wire/GenericContent.php';
require_once '../PhpAmqpLib/Message/AMQPMessage.php';

require_once '../PhpAmqpLib/Wire/IO/AbstractIO.php';
require_once '../PhpAmqpLib/Wire/IO/StreamIO.php';
require_once '../PhpAmqpLib/Exception/AMQPExceptionInterface.php';
require_once '../PhpAmqpLib/Exception/AMQPRuntimeException.php';
require_once '../PhpAmqpLib/Exception/AMQPProtocolException.php';
require_once '../PhpAmqpLib/Exception/AMQPProtocolConnectionException.php';

require_once '../PhpAmqpLib/Message/AMQPMessage.php';
require_once '../PhpAmqpLib/Wire/AbstractClient.php';
require_once '../PhpAmqpLib/Wire/AMQPAbstractCollection.php';
require_once '../PhpAmqpLib/Wire/AMQPWriter.php';

require_once '../PhpAmqpLib/Helper/Protocol/MethodMap091.php';
require_once '../PhpAmqpLib/Helper/Protocol/Protocol091.php';
require_once '../PhpAmqpLib/Helper/Protocol/Wait091.php';
require_once '../PhpAmqpLib/Helper/DebugHelper.php';
require_once '../PhpAmqpLib/Helper/MiscHelper.php';


require_once '../PhpAmqpLib/Exception/AMQPProtocolChannelException.php';
require_once '../PhpAmqpLib/Channel/AbstractChannel.php';
require_once '../PhpAmqpLib/Channel/AMQPChannel.php';

require_once '../PhpAmqpLib/Wire/Constants091.php';
require_once '../PhpAmqpLib/Wire/AMQPReader.php';

require_once '../PhpAmqpLib/Channel/AbstractChannel.php';
require_once '../PhpAmqpLib/Connection/AbstractConnection.php';
require_once '../PhpAmqpLib/Connection/AMQPStreamConnection.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$exchange = 'router';
$queue = 'msgs';
$consumerTag = 'consumer';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

/*
    The following code is the same both in the consumer and the producer.
    In this way we are sure we always have a queue to consume from and an
        exchange where to publish messages.
*/

/*
    name: $queue
    passive: false
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
$channel->queue_declare($queue, true, true, false, false);

/*
    name: $exchange
    type: direct
    passive: false
    durable: true // the exchange will survive server restarts
    auto_delete: false //the exchange won't be deleted once the channel is closed.
*/

$channel->exchange_declare($exchange, 'direct', false, true, false);

$channel->queue_bind($queue, $exchange);

/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
function process_message($message)
{
    echo "\n--------\n";
    echo $message->body;
    echo "\n--------\n";

    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    sleep(10);
    // Send a message with the string "quit" to cancel the consumer.
    if ($message->body === 'quit') {
        $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
    }
}

/*
    queue: Queue from where to get the messages
    consumer_tag: Consumer identifier
    no_local: Don't receive messages published by this consumer.
    no_ack: Tells the server if the consumer will acknowledge the messages.
    exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
    nowait:
    callback: A PHP Callback
*/

$channel->basic_consume($queue, $consumerTag, false, false, false, false, 'process_message');

/**
 * @param \PhpAmqpLib\Channel\AMQPChannel $channel
 * @param \PhpAmqpLib\Connection\AbstractConnection $connection
 */
function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

// Loop as long as the channel has callbacks registered
while (count($channel->callbacks)) {
    $channel->wait();
}