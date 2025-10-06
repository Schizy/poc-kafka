<?php

namespace App\Controller;

use App\Service\KafkaProducer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestKafkaController
{
    #[Route('/send-kafka')]
    public function send(KafkaProducer $producer): Response
    {
        $producer->send('🤓 Je suis un message Kafka !');

        return new Response('Message envoyé à Kafka !');
    }
}
