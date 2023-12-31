<?php

namespace App\Entity;

use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\AuthorRepository;
use JMS\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Doctrine\Common\Collections\ArrayCollection;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "detailAuthor",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getAuthors")
 * )
 * 
 * * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "deleteAuthor",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getAuthors", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 *
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "updateAuthor",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getAuthors", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 */
#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author {
    #[ORM\Id]
    #[ORM\GeneratedValue('CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    #[Groups(["getBooks", "getAuthors", "getSeries"])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks", "getAuthors", "getSeries"])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks", "getAuthors", "getSeries"])]
    private ?string $lastName = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Book::class)]
    #[Groups(["getAuthors"])]
    private Collection $books;

    #[ORM\OneToMany(mappedBy: 'Author', targetEntity: Serie::class)]
    #[Groups(["getAuthors"])]
    private Collection $series;

    public function __construct() {
        $this->books = new ArrayCollection();
        $this->series = new ArrayCollection();
    }

    public function getId(): ?Uuid {
        return $this->id;
    }

    public function getFirstName(): ?string {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * //Collection<int, Book>
     * @return Collection<string, Book>
     */
    public function getBooks(): Collection {
        return $this->books;
    }

    public function addBook(Book $book): static {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->setAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static {
        if ($this->books->removeElement($book)) {
            // set the owning side to null (unless already changed)
            if ($book->getAuthor() === $this) {
                $book->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * //Collection<int, Serie>
     * @return Collection<string, Serie>
     */
    public function getSeries(): Collection {
        return $this->series;
    }

    public function addSeries(Serie $series): static {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
            $series->setAuthor($this);
        }

        return $this;
    }

    public function removeSeries(Serie $series): static {
        if ($this->series->removeElement($series)) {
            // set the owning side to null (unless already changed)
            if ($series->getAuthor() === $this) {
                $series->setAuthor(null);
            }
        }

        return $this;
    }
}