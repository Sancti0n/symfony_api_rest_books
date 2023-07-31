<?php

namespace App\DataFixtures;

use Faker;
use App\Entity\Book;
use App\Entity\User;
use App\Entity\Serie;
use App\Entity\Author;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture {

    private $userPasswordHasher;
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher) {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void {

        $faker = Faker\Factory::create('fr_FR');
        $listAuthor = [];
        $listSerie = [];

        for ($i = 0; $i < 7; $i++) {
            $author = new Author();
            $author->setFirstName($faker->firstName);
            $author->setLastName($faker->lastName);
            $manager->persist($author);
            $listAuthor[] = $author;
        }
        
        for ($i = 0; $i < 10; $i++) {
            $serie = new Serie();
            $serie->setTitle($faker->sentence($nbWords = 6, $variableNbWords = true));
            $serie->setAuthor($listAuthor[array_rand($listAuthor)]);
            $manager->persist($serie);
            $listSerie[] = $serie;
        }

        for ($i = 0; $i < 20; $i++) {
            $livre = new Book();
            $livre->setTitle($faker->sentence($nbWords = 6, $variableNbWords = true));
            $livre->setCoverText('Quatrième de couverture numéro : ' . $i);
            $livre->setIsbn(strval(random_int(9780000000000, 9790000000000)));

            for ($j=0;$j<count($listAuthor);$j++) {
                $l = $listSerie[array_rand($listSerie)];
                if ($listAuthor[$j]->getId() == $l->getAuthor()->getId()) {
                    $livre->setSerie($l);
                    $livre->setAuthor($listAuthor[$j]);
                    $manager->persist($livre);
                    break;
                }
                if ($j+1==count($listAuthor) && $listAuthor[$j]->getId() != $l->getAuthor()->getId()) {
                    $livre->setSerie($l);
                    $livre->setAuthor($listAuthor[array_rand($listAuthor)]);
                    $manager->persist($livre);
                }
            }
        }

        // Création d'un user "normal"
        $user = new User();
        $user->setEmail("user@bookapi.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
        $manager->persist($user);
        
        // Création d'un user admin
        $userAdmin = new User();
        $userAdmin->setEmail("admin@bookapi.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password"));
        $manager->persist($userAdmin);
        
        $manager->flush();
    }
}