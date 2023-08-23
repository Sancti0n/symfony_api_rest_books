<?php

namespace App\Controller;

use App\Entity\Serie;
use JMS\Serializer\Serializer;
use App\Repository\SerieRepository;
use App\Repository\AuthorRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
//use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SerieController extends AbstractController {
    #[Route('/api/series', name: 'serie', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour consulter des séries')]
    public function getSerieList(SerieRepository $serieRepository, SerializerInterface $serializer, 
        Request $request, TagAwareCacheInterface $cache): JsonResponse {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getSerieList-" . $page . "-" . $limit;
        /*
        $serieList = $serieRepository->findAll();
        $jsonSerieList = $serializer->serialize($serieList, 'json', ['groups' => 'getSeries']);
        return new JsonResponse($jsonSerieList, Response::HTTP_OK, [], true);
        */
        $jsonBookList = $cache->get($idCache, function (ItemInterface $item) use ($serieRepository, $page, $limit, $serializer) {
            $item->tag("seriesCache");
            $serieList = $serieRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(["getSeries"]);
            return $serializer->serialize($serieList, 'json', $context);
        });

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/series/{id}', name: 'detailSerie', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour consulter une série')]
    public function getDetailSerie(Serie $serie, SerializerInterface $serializer): JsonResponse {
        
        /*$serie = $serieRepository->find($id);
        if ($serie) {
            $jsonSerie = $serializer->serialize($serie, 'json', ['groups' => 'getSeries']);
            return new JsonResponse($jsonSerie, Response::HTTP_OK, [], true);
        }
        return new JsonResponse($serie, Response::HTTP_NOT_FOUND);
        */
        $context = SerializationContext::create()->setGroups(["getSeries"]);
        $jsonSerie = $serializer->serialize($serie, 'json', $context);
        return new JsonResponse($jsonSerie, Response::HTTP_OK, [], true);
    }

    #[Route('/api/series/{id}', name: 'deleteSerie', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer une série')]
    public function deleteSerie(Serie $serie, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse {
        $em->remove($serie);
        $em->flush();
        // On vide le cache.
        $cache->invalidateTags(["seriesCache"]);
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une série')]
    public function createSerie(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
        UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator,
        TagAwareCacheInterface $cache): JsonResponse {
        $serie = $serializer->deserialize($request->getContent(), Serie::class, 'json');
        
        // On vérifie les erreurs
        $errors = $validator->validate($serie);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $em->persist($serie);
        $em->flush();
        
        // On vide le cache. 
        $cache->invalidateTags(["seriesCache"]);

        $context = SerializationContext::create()->setGroups(["getSeries"]);
        $jsonSerie = $serializer->serialize($serie, 'json', $context);
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour une série')]
    public function updateSerie(Request $request, SerializerInterface $serializer, Serie $currentSerie, 
        EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse {
        // On vérifie les erreurs
        $errors = $validator->validate($currentSerie);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $newSerie = $serializer->deserialize($request->getContent(), Serie::class, 'json');
        $currentSerie->setTitle($newSerie->getTitle());
        
        $em->persist($currentSerie);
        $em->flush();

        // On vide le cache. 
        $cache->invalidateTags(["seriesCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
