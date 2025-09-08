<?php

namespace App\Service;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaContext;

class KafkaProducer
{
    public function send(string $message): void
    {
        $context = $this->getContext();
        $producer = $context->createProducer();

        $producer->send(
            $context->createTopic('MyTopic'),
            $context->createMessage($message)
        );
    }

    private function getContext(): RdKafkaContext
    {
        $factory = new RdKafkaConnectionFactory([
            'global' => [
                'metadata.broker.list' => 'kafka:9092',
            ],
        ]);

        return $factory->createContext();
    }
}
