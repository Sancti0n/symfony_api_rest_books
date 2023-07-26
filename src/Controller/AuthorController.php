<?php

namespace App\Controller;

use App\Entity\Author;
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

class AuthorController extends AbstractController {

    #[Route('/api/authors', name: 'author', methods:['GET'])]
    public function getAuthorList(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse {
        $authorList = $authorRepository->findAll();
        $jsonAuthorList = $serializer->serialize($authorList, 'json', ['groups' => 'getAuthors']);
        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors/{id}', name: 'detailAuthor', methods: ['GET'])]
    public function getDetailAuthor(string $id, AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse {
        $author = $authorRepository->find($id);
        if ($author) {
            $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);
            return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    /*
        Cette route permet de supprimer un auteur mais onDelete:"SET NULL" le livre n'est pas supprimé
        Si on veut supprimer les livres liés à l'auteur on met onDelete:"CASCADE"
    */
    #[Route('/api/authors/{id}', name: 'deleteAuthor', methods: ['DELETE'])]
    public function deleteAuthor(Author $author, EntityManagerInterface $em): JsonResponse {
        $em->remove($author);
        $em->flush();

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
    public function createAuthor(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, SerieRepository $serieRepository): JsonResponse {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($author);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idSerie = $content['idSerie'] ?? -1 || $content['idSerie'] == "";
        $author->setSerie($serieRepository->find($idSerie));

        $em->persist($author);
        $em->flush();

        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);
        $location = $urlGenerator->generate('detailAuthor', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);	
    }

    /*
        Exemple: 
        {
            "idSerie": "01898da8-bf5b-740b-af40-48fd1696ef9f"
        }

    */
    #[Route('/api/authors/{id}', name:"updateAuthors", methods:['PUT'])]
    public function updateAuthor(Request $request, SerializerInterface $serializer, Author $currentAuthor, EntityManagerInterface $em, SerieRepository $serieRepository): JsonResponse {
        $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);
        $content = $request->toArray();
        $idSerie = $content['idSerie'] ?? -1 || $content['idSerie'] == "";
        $updatedAuthor->addSeries($serieRepository->find($idSerie));
        $em->persist($updatedAuthor);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}