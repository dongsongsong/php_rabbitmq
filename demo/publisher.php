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
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'router';
$queue = 'msgs';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();
/*
    The following code is the same both in the consumer and the producer.
    In this way we are sure we always have a queue to consume from and an
        exchange where to publish messages.
*/

/*
 *
    name: $queue
    passive: false 一般情况下 false
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
$channel->queue_declare($queue, false, true, false, false);
/*
    name: $exchange
    type: direct
    passive: false
    durable: true // the exchange will survive server restarts
    auto_delete: false //the exchange won't be deleted once the channel is closed.
*/

$channel->exchange_declare($exchange, 'direct', false, true, false);

$channel->queue_bind($queue, $exchange);

$messageBody = 'send message';
$message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
$channel->basic_publish($message, $exchange);

$channel->close();
$connection->close();