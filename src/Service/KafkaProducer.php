<?php

namespace App\Service;

use Enqueue\RdKafka\RdKafkaConnectionFactory;

class KafkaProducer
{
    public function send(string $message): void
    {
        $factory = new RdKafkaConnectionFactory([
            'global' => [
                'metadata.broker.list' => 'kafka:9092',
            ],
        ]);

        $context = $factory->createContext();

        // 🔹 On définit le topic explicitement ici
        $topic = $context->createTopic('MyTopic');

        $producer = $context->createProducer();
        $producer->send($topic, $context->createMessage($message));
    }
}
