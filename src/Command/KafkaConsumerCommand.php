<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Enqueue\RdKafka\RdKafkaConnectionFactory;

#[AsCommand(
    name: 'kafka:consume',
    description: 'Consume messages from Kafka topic',
)]
class KafkaConsumerCommand extends Command
{
    private bool $shouldStop = false;

    protected function configure(): void
    {
        $this
            ->addOption(
                'max-runtime',
                null,
                InputOption::VALUE_REQUIRED,
                'Durée maximale (en secondes) avant arrêt automatique',
                0 // 0 = illimité
            )
            ->addOption(
                'max-messages',
                null,
                InputOption::VALUE_REQUIRED,
                'Nombre maximum de messages à consommer avant arrêt automatique',
                0 // 0 = illimité
            )
            ->addOption(
                'poll-timeout',
                null,
                InputOption::VALUE_REQUIRED,
                'Durée d’attente pour recevoir un message (en ms) avant de checker si le script doit se stopper',
                5000 // valeur par défaut = 5 secondes
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maxRuntime = (int)$input->getOption('max-runtime');
        $maxMessages = (int)$input->getOption('max-messages');
        $pollTimeout = (int)$input->getOption('poll-timeout');
        $startTime = time();
        $messagesConsumed = 0;

        // ⚡ Gestion des signaux
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () use ($io) {
            $io->warning("📢 SIGTERM reçu, arrêt après le message en cours...");
            $this->shouldStop = true;
        });

        pcntl_signal(SIGINT, function () use ($io) {
            $io->warning("📢 SIGINT reçu (Ctrl+C), arrêt après le message en cours...");
            $this->shouldStop = true;
        });

        $factory = new RdKafkaConnectionFactory([
            'global' => [
                'metadata.broker.list' => 'kafka:9092',
                'group.id' => 'symfony-consumer',
                'enable.auto.commit' => 'false',
                'auto.offset.reset' => 'earliest',
                'session.timeout.ms' => '10000',
                'max.poll.interval.ms' => '300000',
            ],
        ]);

        $context = $factory->createContext();
        $consumer = $context->createConsumer($context->createQueue('MyTopic'));

        $io->success("🚀 Waiting for Kafka messages... (poll-timeout = {$pollTimeout} ms)");

        while (!$this->shouldStop) {
            // Vérifie le runtime max
            if ($maxRuntime > 0 && (time() - $startTime) >= $maxRuntime) {
                $io->warning("⏰ Durée maximale atteinte ({$maxRuntime}s), arrêt en cours...");
                $this->shouldStop = true;
                break;
            }

            // Vérifie le nombre max de messages
            if ($maxMessages > 0 && $messagesConsumed >= $maxMessages) {
                $io->warning("📦 Nombre maximum de messages atteint ({$maxMessages}), arrêt en cours...");
                $this->shouldStop = true;
                break;
            }

            if ($message = $consumer->receive($pollTimeout)) {
                $io->info("✅ Received: " . $message->getBody());

                // 👇 traitement du message
                $consumer->acknowledge($message);

                $messagesConsumed++;
            }
        }

        // 👇 Commit final avant de quitter
        try {
            if (method_exists($consumer, 'commit')) {
                $consumer->commit();
                $io->success("💾 Offset commit final effectué avant l'arrêt.");
            } elseif (method_exists($consumer, 'commitAsync')) {
                $consumer->commitAsync();
                $io->success("💾 Offset commit final (async) effectué avant l'arrêt.");
            }
        } catch (\Throwable $e) {
            $io->error("❌ Impossible de faire le commit final : " . $e->getMessage());
        }

        $io->success("👋 Arrêt propre du consumer après {$messagesConsumed} messages.");
        return Command::SUCCESS;
    }
}
