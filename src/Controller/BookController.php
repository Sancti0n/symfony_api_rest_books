<?php

namespace App\Controller;

use App\Entity\Book;
use JMS\Serializer\Serializer;
use OpenApi\Annotations as OA;
use App\Repository\BookRepository;
use App\Service\VersioningService;
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

class BookController extends AbstractController {

    /**
     * Cette méthode permet de récupérer l'ensemble des livres.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des livres",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Book::class, groups={"getBooks"}))
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
     * @OA\Tag(name="Books")
     *
     * @param BookRepository $bookRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/api/books', name: 'book',methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour consulter des livres')]
    public function getBookList(BookRepository $bookRepository, SerializerInterface $serializer, Request $request, 
        TagAwareCacheInterface $cache): JsonResponse {
        $page = $request->get('page', 2);
        $limit = $request->get('limit', 3);

        $idCache = "getBookList-" . $page . "-" . $limit;

        $jsonBookList = $cache->get($idCache, function (ItemInterface $item) use ($bookRepository, $page, $limit, $serializer) {
            $item->tag("booksCache");
            $bookList = $bookRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(["getBooks"]);
            return $serializer->serialize($bookList, 'json', $context);
        });

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour consulter un livre')]
    public function getDetailBook(SerializerInterface $serializer, Book $book, VersioningService $versioningService): JsonResponse {
        $version = $versioningService->getVersion();
        $context = SerializationContext::create()->setGroups(['getBooks']);
        $context->setVersion($version);
        $jsonBook = $serializer->serialize($book, 'json', $context);
        return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un livre')]
    public function deleteBook(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse {
        $em->remove($book);
        $em->flush();
        // On vide le cache.
        $cache->invalidateTags(["booksCache"]);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /*
        Exemple:
        {
            "title": "Test !",
            "coverText": "Une aventure.",
            "isbn": "9789785721142",
            "idAuthor": "01898d66-490b-702d-90ea-8261ec2db98b",
            "idSerie": "01898da8-bf5b-740b-af40-48fd1696ef9f"
        }
    */
    #[Route('/api/books', name:"createBook", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]
    public function createBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
        UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, SerieRepository $serieRepository,
        ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($book);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            //throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requête est invalide");
        }

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1 || $content['idAuthor'] == "";
        $idAuthor = $content['idSerie'] ?? -1 || $content['idSerie'] == "";
        $book->setAuthor($authorRepository->find($idAuthor));
        $book->setSerie($serieRepository->find($idSerie));

        $em->persist($book);
        $em->flush();

        // On vide le cache. 
        $cache->invalidateTags(["booksCache"]);

        $context = SerializationContext::create()->setGroups(["getBooks"]);
        $jsonBook = $serializer->serialize($book, 'json', $context);
		
        $location = $urlGenerator->generate('detailBook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

		return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /*
        Exemple:
        {
            "idAuthor": "01898d66-490c-7219-8edc-fcc1d5f4c98f",
            "idSerie": ""
        }
    */
    #[Route('/api/books/{id}', name:"updateBook", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un livre')]
    public function updateBook(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, 
        AuthorRepository $authorRepository, SerieRepository $serieRepository, ValidatorInterface $validator, 
        TagAwareCacheInterface $cache): JsonResponse {
        $newBook = $serializer->deserialize($request->getContent(), Book::class, 'json');
        $currentBook->setTitle($newBook->getTitle());
        $currentBook->setCoverText($newBook->getCoverText());

        // On vérifie les erreurs
        $errors = $validator->validate($currentBook);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1 || $content['idAuthor'] == "";
        $idSerie = $content['idSerie'] ?? -1 || $content['idSerie'] == "";
    
        $currentBook->setAuthor($authorRepository->find($idAuthor));
        $currentBook->setSerie($serieRepository->find($idSerie));

        $em->persist($currentBook);
        $em->flush();

        // On vide le cache.
        $cache->invalidateTags(["booksCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}