<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_user', methods: ['GET'])]
    public function getUsers(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse {
        $userList = $userRepository->findAll();
        $jsonUserList = $serializer->serialize($userList, 'json');
        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }
}
