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
    description: 'Add a short description for your command',
)]
class KafkaConsumerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $factory = new RdKafkaConnectionFactory([
            'global' => [
                'metadata.broker.list' => 'kafka:9092',
                // Si on ne spécifie pas de groupId Kafka va en générer un
                // Et comme on aura jamais le même 2x
                // On ne pourra pas reprendre là où on en était
                'group.id' => 'symfony-consumer',
            ],
        ]);

        $context = $factory->createContext();

        // 🔹 Ici aussi, on définit le topic/queue explicitement
        $consumer = $context->createConsumer($context->createQueue('MyTopic'));

        echo "🚀 Waiting for Kafka messages...\n";

        while (true) {
            if ($message = $consumer->receive(5000)) {
                $io->info("✅ Received: " . $message->getBody());
                $consumer->acknowledge($message);
            }
        }
    }
}
