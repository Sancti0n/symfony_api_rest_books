<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
        return new JsonResponse($serie, Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/series/{id}', name: 'deleteSerie', methods: ['DELETE'])]
    public function deleteSerie(Serie $serie, EntityManagerInterface $em): JsonResponse {
        $em->remove($serie);
        $em->flush();

        return new JsonResponse($serie, Response::HTTP_NO_CONTENT);
    }

    /*
        Exemple:
        {
            "title": "Jamy",
            "idAuthor": "01898d66-490c-7219-8edc-fcc1d5f4c98f"
        }
    */
    #[Route('/api/series', name: 'createSerie', methods: ['POST'])]
    public function createSerie(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, AuthorRepository $authorRepository): JsonResponse {
        $serie = $serializer->deserialize($request->getContent(), Serie::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($serie);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        // Récupération de l'idAuthor. S'il n'est pas défini, alors on met -1 par défaut.
        $idAuthor = $content['idAuthor'] ?? -1 || $content['idAuthor'] == "";
        // On cherche l'auteur qui correspond et on l'assigne au livre.
        // Si "find" ne trouve pas l'auteur, alors null sera retourné.
        $serie->setAuthor($authorRepository->find($idAuthor));
        $em->persist($serie);
        $em->flush();

        $jsonSerie = $serializer->serialize($serie, 'json', ['groups' => 'getSeries']);
        $location = $urlGenerator->generate('detailSerie', ['id' => $serie->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonSerie, Response::HTTP_CREATED, ["Location" => $location], true);	
    }

    /*
        Exemple:
        {
            "idAuthor": "01898d66-490c-7219-8edc-fcc1d5e5e90b"
        }
    */
    #[Route('/api/series/{id}', name:"updateSeries", methods:['PUT'])]
    public function updateSerie(Request $request, SerializerInterface $serializer, Serie $currentSerie, EntityManagerInterface $em, AuthorRepository $authorRepository): JsonResponse {
        $updatedSerie = $serializer->deserialize($request->getContent(), Serie::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentSerie]);
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1 || $content['idAuthor'] == "";
        $updatedSerie->setAuthor($authorRepository->find($idAuthor));
        $em->persist($updatedSerie);
        $em->flush();

        return new JsonResponse($updatedSerie, JsonResponse::HTTP_NO_CONTENT);
    }
}
