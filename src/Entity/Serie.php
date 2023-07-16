<?php

namespace App\Entity;

use App\Repository\SerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
//use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
class Serie {
    #[ORM\Id]
    #[ORM\GeneratedValue('CUSTOM')]
    //#[ORM\GeneratedValue]
    #[ORM\Column(type: 'uuid', unique: true)]
    //#[ORM\Column]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    //private ?int $id = null;
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    private ?Author $Author = null;

    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: Book::class)]
    private Collection $Book;

    public function __construct() {
        $this->Book = new ArrayCollection();
    }

    public function getId(): ?string {
    //public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(string $title): static {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?Author {
        return $this->Author;
    }

    public function setAuthor(?Author $Author): static {
        $this->Author = $Author;

        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBook(): Collection {
        return $this->Book;
    }

    public function addBook(Book $book): static {
        if (!$this->Book->contains($book)) {
            $this->Book->add($book);
            $book->setSerie($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static {
        if ($this->Book->removeElement($book)) {
            // set the owning side to null (unless already changed)
            if ($book->getSerie() === $this) {
                $book->setSerie(null);
            }
        }

        return $this;
    }
}

/*
ALTER TABLE `book` DROP FOREIGN KEY `FK_CBE5A331D94388BD`;
ALTER TABLE `book` DROP COLUMN `serie_id`;
*/