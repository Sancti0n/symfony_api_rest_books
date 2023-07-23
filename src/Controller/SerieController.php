<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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

    #[Route('/api/series/{id}', name: 'deleteSerie', methods: ['DELETE'])]
    public function deleteSerie(Serie $serie, EntityManagerInterface $em): JsonResponse {
        $em->remove($serie);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/series', name: 'createSerie', methods: ['POST'])]
    public function createSerie(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse {
        $serie = $serializer->deserialize($request->getContent(), Serie::class, 'json');
        $em->persist($serie);
        $em->flush();

        $jsonSerie = $serializer->serialize($serie, 'json', ['groups' => 'getSeries']);
        $location = $urlGenerator->generate('detailSerie', ['id' => $serie->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonSerie, Response::HTTP_CREATED, ["Location" => $location], true);	
    }

    #[Route('/api/series/{id}', name:"updateSeries", methods:['PUT'])]
    public function updateSerie(Request $request, SerializerInterface $serializer, Serie $currentSerie, EntityManagerInterface $em): JsonResponse {
        $updatedSerie = $serializer->deserialize($request->getContent(), Serie::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentSerie]);
        $em->persist($updatedSerie);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
