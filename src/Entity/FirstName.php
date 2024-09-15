<?php

namespace App\Entity;

use App\Repository\FirstNameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FirstNameRepository::class)]
#[ORM\Table(name: 'first_name', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'unique_name', columns: ['name'])
])]
class FirstName
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]  // Le prénom doit être unique
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $isDrawn = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $team = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isDrawn(): ?bool
    {
        return $this->isDrawn;
    }

    public function setDrawn(bool $isDrawn): static
    {
        $this->isDrawn = $isDrawn;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTeam(): ?string
    {
        return $this->team;
    }

    public function setTeam(string $team): self
    {
        $this->team = $team;
        return $this;
    }
}
