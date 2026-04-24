<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FinanceCategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: FinanceCategoryRepository::class)]
#[ORM\Table(
    name: 'finance_category',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_finance_category_name', columns: ['name']),
    ],
    indexes: [
        new ORM\Index(name: 'idx_finance_category_is_active', columns: ['is_active']),
        new ORM\Index(name: 'idx_finance_category_created', columns: ['created_at']),
        new ORM\Index(name: 'idx_finance_category_active_name', columns: ['is_active', 'name']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class FinanceCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $name = '';

    #[ORM\Column(name: 'icon_name', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $iconName = null;

    #[Vich\UploadableField(mapping: 'finance_category_icon', fileNameProperty: 'iconName')]
    #[Assert\File(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])]
    private ?File $iconFile = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function __toString(): string
    {
        return '' !== $this->name ? $this->name : 'Category';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $name = trim($name);
        if ('' !== $name) {
            $this->name = $name;
        }

        return $this;
    }

    public function getIconName(): ?string
    {
        return $this->iconName;
    }

    public function setIconName(?string $iconName): self
    {
        $iconName = null === $iconName ? null : trim($iconName);
        $this->iconName = '' === $iconName ? null : $iconName;

        return $this;
    }

    public function getIconFile(): ?File
    {
        return $this->iconFile;
    }

    public function setIconFile(?File $iconFile): self
    {
        $this->iconFile = $iconFile;

        if (null !== $iconFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function updateTimestampsOnCreate(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestampOnUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
