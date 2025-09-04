<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Enqueue\RdKafka\RdKafkaConnectionFactory;

#[AsCommand(
    name: 'kafka:consume',
    description: 'Consume messages from Kafka topic',
)]
class KafkaConsumerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $factory = new RdKafkaConnectionFactory([
            'global' => [
                'metadata.broker.list' => 'kafka:9092',
                'group.id' => 'symfony-consumer',   // 👈 stable
                'enable.auto.commit' => 'false',    // 👈 on force le commit manuel
                'auto.offset.reset' => 'earliest',  // 👈 lit tout au premier run
                'session.timeout.ms' => '10000',
                'max.poll.interval.ms' => '300000',
            ],
        ]);

        $context = $factory->createContext();
        $consumer = $context->createConsumer($context->createQueue('MyTopic'));

        $io->success("🚀 Waiting for Kafka messages...");

        while (true) {
            if ($message = $consumer->receive(5000)) {
                $io->info("✅ Received: " . $message->getBody());

                // 👇 commit explicite après traitement
                $consumer->acknowledge($message);
            }
        }
    }
}
