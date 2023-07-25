<?php

namespace App\DataFixtures;

use Faker;
use App\Entity\Book;
use App\Entity\Serie;
use App\Entity\Author;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture {
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
            $v = $listAuthor[array_rand($listAuthor)];
            $serie->setAuthor($v);
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
                    $s = $l;
                    $a = $listAuthor[$j];
                    break;
                }   
            }
            $livre->setSerie($s);
            $livre->setAuthor($a);
            $manager->persist($livre);
        }
        $manager->flush();
    }
}