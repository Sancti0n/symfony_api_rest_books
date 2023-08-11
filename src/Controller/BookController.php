<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BookController extends AbstractController {

    #[Route('/api/books', name: 'book',methods: ['GET'])]
    public function getBookList(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'detailBook', methods: ['GET'])]
    public function getDetailBook(string $id, SerializerInterface $serializer, BookRepository $bookRepository): JsonResponse {
        $book = $bookRepository->find($id);
        if ($book) {
            $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }
        return new JsonResponse($id." n'est pas dans la BDD.", Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
    public function deleteBook(Book $book, EntityManagerInterface $em): JsonResponse {
        $em->remove($book);
        $em->flush();
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
    public function createBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, SerieRepository $serieRepository, ValidatorInterface $validator): JsonResponse {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($book);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();
        // Récupération de l'idAuthor. S'il n'est pas défini, alors on met -1 par défaut.
        $idAuthor = $content['idAuthor'] ?? -1 || $content['idAuthor'] == "";
        $idSerie = $content['idSerie'] ?? -1 || $content['idSerie'] == "";
        // On cherche l'auteur qui correspond et on l'assigne au livre.
        // Si "find" ne trouve pas l'auteur, alors null sera retourné.
        $book->setAuthor($authorRepository->find($idAuthor));
        $book->setSerie($serieRepository->find($idSerie));
        $em->persist($book);
        $em->flush();

        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);
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
    public function updateBook(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository, SerieRepository $serieRepository): JsonResponse {
        $updatedBook = $serializer->deserialize($request->getContent(), Book::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]);
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1 || $content['idAuthor'] == "";
        $idSerie = $content['idSerie'] ?? -1 || $content['idSerie'] == "";
        $updatedBook->setAuthor($authorRepository->find($idAuthor));
        $updatedBook->setSerie($serieRepository->find($idSerie));
        
        $em->persist($updatedBook);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}