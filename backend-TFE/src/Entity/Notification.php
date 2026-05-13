<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $text = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $createAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Users $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Anouncement $anouncement = null;

    public function getId(): ?int { return $this->id; }

    public function getText(): ?string { return $this->text; }
    public function setText(string $text): static { $this->text = $text; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): static { $this->isRead = $isRead; return $this; }

    public function getCreateAt(): ?\DateTime { return $this->createAt; }
    public function setCreateAt(\DateTime $createAt): static { $this->createAt = $createAt; return $this; }

    public function getUser(): ?Users { return $this->user; }
    public function setUser(?Users $user): static { $this->user = $user; return $this; }

    public function getAnouncement(): ?Anouncement { return $this->anouncement; }
    public function setAnouncement(?Anouncement $anouncement): static { $this->anouncement = $anouncement; return $this; }
}
