<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SerieController extends AbstractController {
    #[Route('/api/series', name: 'serie', methods: ['GET'])]
    public function getSerieList(SerieRepository $serieRepository, SerializerInterface $serializer): JsonResponse {
        $serieList = $serieRepository->findAll();
        $jsonSerieList = $serializer->serialize($serieList, 'json', ['groups' => 'getSeries']);
        return new JsonResponse($jsonSerieList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/series/{id}', name: 'detailSerie', methods: ['GET'])]
    public function getDetailSerie(string $id, SerieRepository $serieRepository, SerializerInterface $serializer): JsonResponse {
        $serie = $serieRepository->find($id);
        if ($serie) {
            $jsonSerie = $serializer->serialize($serie, 'json', ['groups' => 'getSeries']);
            return new JsonResponse($jsonSerie, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
