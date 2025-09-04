<?php

namespace App\Controller;

use App\Service\KafkaProducer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestKafkaController extends AbstractController
{
    #[Route('/send-kafka')]
    public function send(KafkaProducer $producer): Response
    {
        $producer->send('🤓 Je suis un message Kafka !');
        return new Response('Message envoyé à Kafka !');
    }
}
