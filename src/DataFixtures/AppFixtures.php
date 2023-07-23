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
        // Création d'une vingtaine de livres ayant pour titre
        $listAuthor = [];
        $listSerie = [];

        for ($i = 0; $i < 7; $i++) {
            $author = new Author();
            $author->setFirstName($faker->firstName);
            $author->setLastName($faker->lastName);
            $manager->persist($author);
            // On sauvegarde l'auteur créé dans un tableau.
            $listAuthor[] = $author;
        }
        //print_r($listAuthor);
        
        for ($i = 0; $i < 10; $i++) {
            $serie = new Serie();
            $serie->setTitle($faker->sentence($nbWords = 6, $variableNbWords = true));
            $v = $listAuthor[array_rand($listAuthor)];
            $serie->setAuthor($v);
            //print_r($serie->getTitle());
            $manager->persist($serie);
            //$listSerie[$serie->getId()] = $v->getId();
            $listSerie[] = $serie;
        }

        for ($i = 0; $i < 20; $i++) {
            $livre = new Book();
            $livre->setTitle($faker->sentence($nbWords = 6, $variableNbWords = true));
            //$livre->setTitle($faker->sentence());
            $livre->setCoverText('Quatrième de couverture numéro : ' . $i);
            $livre->setIsbn(strval(random_int(9780000000000, 9790000000000)));
            

            //print_r($listSerie);
            //print_r($listAuthor);
            $test = $listAuthor[array_rand($listAuthor)];
            $v = array_search($test->getId(),$listSerie);
            //print($listAuthor[array_rand($listAuthor)]->getId());
            print_r($test->getId());
            echo "\n";
            print_r($listSerie[$i]->getTitle());
            echo "\n";
            //print_r($listSerie->getTitle());
            /*
            echo $test->getId();
            echo "\n";
            */
            //print_r($v);
            $author = $v;

            /*
            $filtered_array = array_filter($listSerie, function ($obj) use ($v) {
                print_r($obj);
                echo "\n";
                print_r($v->getId());
                return $obj == $v->getId();
            });
            */

            //print_r($filtered_array);
            
            //print_r($listAuthor);
            //print_r($livre);
            //$serie = $listAuthor[getId()]
            //print_r($listSerie);
            $livre->setAuthor($test);
            //$livre->setSerie($filtered_array);
            $livre->setSerie($listSerie[array_rand($listSerie)]);
            //$livre->setSerie($listSerie[$v]);
            
            //$livre->setAuthor($author);
            
            //print_r($author->getId());
            
            //$livre->setIsbn($faker->generator->ean13());
            //$serie = new Serie();
            //print_r($listSerie);
            //$livre->setAuthor($listAuthor[$i%7]);
            $manager->persist($livre);
        }
        //print_r($listSerie);
        
        $manager->flush();
    }
}
