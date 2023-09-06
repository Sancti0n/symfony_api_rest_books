<?php

namespace App\Controller;

use App\Entity\Author;
use JMS\Serializer\Serializer;
use OpenApi\Annotations as OA;
use App\Repository\SerieRepository;
use App\Repository\AuthorRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
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

class AuthorController extends AbstractController {

    /**
     * Cette méthode permet de récupérer l'ensemble des auteurs.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des auteurs",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Author::class, groups={"getAuthors"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Authors")
     *
     * @param AuthorRepository $bookRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/authors', name: 'author', methods:['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour consulter les auteurs')]
    public function getAuthorList(AuthorRepository $authorRepository, SerializerInterface $serializer, 
        Request $request, TagAwareCacheInterface $cache): JsonResponse {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAuthorList-" . $page . "-" . $limit;

        $jsonAuthorList = $cache->get($idCache, function (ItemInterface $item) use ($authorRepository, $page, $limit, $serializer) {
            $item->tag("authorsCache");
            $authorList = $authorRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(["getAuthors"]);
            return $serializer->serialize($authorList, 'json', $context);
        });

        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors/{id}', name: 'detailAuthor', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour consulter un auteur')]
    public function getDetailAuthor(Author $author, SerializerInterface $serializer): JsonResponse {
        $context = SerializationContext::create()->setGroups(["getAuthors"]);
        $jsonAuthor = $serializer->serialize($author, 'json', $context);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    /*
        Cette route permet de supprimer un auteur mais onDelete:"SET NULL" le livre n'est pas supprimé
        Si on veut supprimer les livres liés à l'auteur on met onDelete:"CASCADE"
    */
    #[Route('/api/authors/{id}', name: 'deleteAuthor', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un auteur')]
    public function deleteAuthor(Author $author, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse {
        $em->remove($author);
        $em->flush();
        // On vide le cache.
        $cache->invalidateTags(["authorsCache"]);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    /*
        Exemple:
        {
            "firstName": "Beta2",
            "lastName": "Omega2"
        }
    */
    #[Route('/api/authors', name: 'createAuthor', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un auteur')]
    public function createAuthor(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
        UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');
        
        // On vérifie les erreurs
        $errors = $validator->validate($author);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $em->persist($author);
        $em->flush();
        
        // On vide le cache. 
        $cache->invalidateTags(["authorsCache"]);

        $context = SerializationContext::create()->setGroups(["getAuthors"]);
        $jsonAuthor = $serializer->serialize($author, 'json', $context);
        $location = $urlGenerator->generate('detailAuthor', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /*
        Exemple: 
        {
            "idSerie": "01898da8-bf5b-740b-af40-48fd1696ef9f"
        }

    */
    #[Route('/api/authors/{id}', name:"updateAuthor", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un auteur')]
    public function updateAuthor(Request $request, SerializerInterface $serializer, Author $currentAuthor, 
        EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse {
        // On vérifie les erreurs
        $errors = $validator->validate($currentAuthor);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $newAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json');
        $currentAuthor->setFirstName($newAuthor->getFirstName());
        $currentAuthor->setLastName($newAuthor->getLastName());
        
        $em->persist($currentAuthor);
        $em->flush();

        // On vide le cache. 
        $cache->invalidateTags(["authorsCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}