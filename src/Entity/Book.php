<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book {
    #[ORM\Id]
    #[ORM\GeneratedValue('CUSTOM')]
    //#[ORM\GeneratedValue]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    //#[ORM\Column(type: 'uuid', unique: true)]
    //#[ORM\Column]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]

    #[Groups(["getBooks", "getAuthors", "getSeries"])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks", "getAuthors", "getSeries"])]
    #[Assert\NotBlank(message: "Le titre du livre est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le titre doit faire au moins {{ limit }} caractère", maxMessage: "Le titre ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["getBooks", "getAuthors", "getSeries"])]
    private ?string $coverText = null;

    #[ORM\Column(length: 13)]
    #[Groups(["getBooks", "getAuthors", "getSeries"])]
    private ?string $isbn = null;

    #[ORM\ManyToOne(inversedBy: 'books')]
    //#[ORM\JoinColumn(onDelete:"CASCADE")]
    #[ORM\JoinColumn(onDelete:"SET NULL")]
    //#[Groups(["getBooks", "getAuthors", "getSeries"])]
    #[Groups(["getBooks", "getSeries"])]
    private ?Author $author = null;

    #[ORM\ManyToOne(inversedBy: 'Book')]
    #[Groups(["getBooks", "getAuthors"])]
    
    private ?Serie $serie = null;

    //public function getId(): ?int
    public function getId(): ?Uuid {
        return $this->id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(string $title): static {
        $this->title = $title;
        return $this;
    }

    public function getCoverText(): ?string {
        return $this->coverText;
    }

    public function setCoverText(?string $coverText): static {
        $this->coverText = $coverText;
        return $this;
    }

    public function getIsbn(): ?string {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): static {
        $this->isbn = $isbn;

        return $this;
    }

    public function getAuthor(): ?Author{
        return $this->author;
    }

    public function setAuthor(?Author $author): static {
        $this->author = $author;

        return $this;
    }

    public function getSerie(): ?Serie {
        return $this->serie;
    }

    public function setSerie(?Serie $serie): static {
        $this->serie = $serie;

        return $this;
    }
}