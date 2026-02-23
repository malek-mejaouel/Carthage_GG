<?php

namespace App\Controller;

use App\Repository\StreamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StreamController extends AbstractController
{
    #[Route('/streams', name: 'app_streams_index')]
    public function index(
        StreamRepository $streams,
        \Doctrine\ORM\EntityManagerInterface $em,
        \App\Service\StreamStatusService $status
    ): Response
    {
        $latest = $streams->findLatest(24);
        $status->refreshForList($latest, $em, 0);
        // Recompute live list after status refresh to reflect latest isLive flags
        $live = $streams->findLive(12);
        return $this->render('Stream/index.html.twig', [
            'streams' => $latest,
            'live' => $live,
        ]);
    }

    #[Route('/streams/{id}', name: 'app_streams_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        StreamRepository $streams,
        \Symfony\Component\HttpFoundation\Request $request,
        \Doctrine\ORM\EntityManagerInterface $em,
        \App\Service\StreamStatusService $status
    ): Response
    {
        $stream = $streams->find($id);
        if (!$stream) {
            throw $this->createNotFoundException('Stream not found');
        }
        $status->refreshForList([$stream], $em, 15);
        return $this->render('Stream/show.html.twig', [
            'stream' => $stream,
            'twitch_parents' => array_unique([
                $request->getHost(),
                '127.0.0.1',
                'localhost'
            ]),
            'host' => $request->getHost(),
        ]);
    }
}
