<?php

namespace App\Entity;

use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SerieRepository;
use JMS\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Doctrine\Common\Collections\ArrayCollection;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "detailSerie",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getSeries")
 * )
 *
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "deleteSerie",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getSeries", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 *
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "updateSerie",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getSeries", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 */
#[ORM\Entity(repositoryClass: SerieRepository::class)]
class Serie {
    #[ORM\Id]
    #[ORM\GeneratedValue('CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator('doctrine.uuid_generator')]
    #[Groups(["getBooks","getAuthors", "getSeries"])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks","getAuthors", "getSeries"])]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    #[Groups(["getBooks", "getSeries"])]
    private ?Author $Author = null;

    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: Book::class)]
    private Collection $Book;

    public function __construct() {
        $this->Book = new ArrayCollection();
    }

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