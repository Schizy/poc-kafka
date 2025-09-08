<?php

namespace App\Command;

use Enqueue\RdKafka\RdKafkaConsumer;
use Interop\Queue\Consumer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Enqueue\RdKafka\RdKafkaConnectionFactory;

#[AsCommand(
    name: 'kafka:consume',
    description: 'Consumes messages from Kafka',
)]
class KafkaConsumerCommand extends Command
{
    private bool $shouldStop = false;

    protected function configure(): void
    {
        $this
            ->addOption(
                'max-runtime',
                ['t', 'time'],
                InputOption::VALUE_REQUIRED,
                'Durée maximale (en secondes) avant arrêt automatique',
                0 // 0 = illimité
            )
            ->addOption(
                'max-messages',
                ['m', 'messages'],
                InputOption::VALUE_REQUIRED,
                'Nombre maximum de messages à consommer avant arrêt automatique',
                0 // 0 = illimité
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->handleSignals($io);

        $startTime = time();
        $maxRuntime = (int)$input->getOption('max-runtime');

        $messagesConsumed = 0;
        $maxMessages = (int)$input->getOption('max-messages');

        $consumer = $this->getConsumer();

        $io->success("🚀 Waiting for Kafka messages...");

        while (!$this->shouldStop) {
            // Vérifie le runtime max
            if ($maxRuntime > 0 && (time() - $startTime) >= $maxRuntime) {
                $io->warning("⏰ Durée maximale atteinte ({$maxRuntime}s), arrêt en cours...");
                break;
            }

            // Vérifie le nombre max de messages
            if ($maxMessages > 0 && $messagesConsumed >= $maxMessages) {
                $io->warning("📦 Nombre maximum de messages atteint ({$maxMessages}), arrêt en cours...");
                break;
            }

            // Le script se bloque complètement 1s le temps que ça écoute Kafka
            if ($message = $consumer->receive(1000)) {
                $io->info("✅ Received: " . $message->getBody());

                // On fait semblant de traiter le message
                $progressBar = $io->createProgressBar(100);
                for ($i = 0; $i < 5; ++$i) {
                    sleep(1);
                    $progressBar->advance(20);
                }

                $io->newLine(2);
                $io->note("Message traité après 5s ! ✓");

                $consumer->acknowledge($message);
                $messagesConsumed++;
            }
        }

        $io->success("👋 Arrêt du consumer après {$messagesConsumed} messages.");

        return Command::SUCCESS;
    }

    private function getConsumer(): Consumer|RdKafkaConsumer
    {
        $context = new RdKafkaConnectionFactory([
            'global' => [
                'metadata.broker.list' => 'kafka:9092',
                'group.id' => 'symfony-consumer',
                'enable.auto.commit' => 'false',
                'auto.offset.reset' => 'earliest',
                'session.timeout.ms' => '10000',
                'max.poll.interval.ms' => '300000',
            ],
        ])
            ->createContext();

        return $context->createConsumer($context->createQueue('MyTopic'));
    }

    private function handleSignals(SymfonyStyle $io): void
    {
        // ⚡ Gestion des signaux
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () use ($io) {
            $io->warning("📢 SIGTERM reçu, arrêt gracieux après le message en cours...");
            $this->shouldStop = true;
        });

        pcntl_signal(SIGINT, function () use ($io) {
            $io->warning("📢 SIGINT reçu (Ctrl+C), arrêt gracieux après le message en cours...");
            $this->shouldStop = true;
        });
    }
}
