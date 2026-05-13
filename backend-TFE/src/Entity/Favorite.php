<?php

namespace App\Entity;

use App\Repository\FavoriteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_anouncement', columns: ['user_id', 'anouncement_id'])]
class Favorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Users $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Anouncement $anouncement = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $createAt = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?Users { return $this->user; }
    public function setUser(?Users $user): static { $this->user = $user; return $this; }

    public function getAnouncement(): ?Anouncement { return $this->anouncement; }
    public function setAnouncement(?Anouncement $anouncement): static { $this->anouncement = $anouncement; return $this; }

    public function getCreateAt(): ?\DateTime { return $this->createAt; }
    public function setCreateAt(\DateTime $createAt): static { $this->createAt = $createAt; return $this; }
}
