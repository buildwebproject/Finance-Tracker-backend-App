<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[ORM\Table(
    name: 'country',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_country_iso2_code', columns: ['iso2_code']),
        new ORM\UniqueConstraint(name: 'uniq_country_iso3_code', columns: ['iso3_code']),
    ],
    indexes: [
        new ORM\Index(name: 'idx_country_name', columns: ['name']),
        new ORM\Index(name: 'idx_country_dial_code', columns: ['dial_code']),
        new ORM\Index(name: 'idx_country_currency_code', columns: ['currency_code']),
        new ORM\Index(name: 'idx_country_is_active', columns: ['is_active']),
        new ORM\Index(name: 'idx_country_active_name', columns: ['is_active', 'name']),
    ]
)]
#[UniqueEntity(fields: ['iso2Code'], message: 'country.iso2_code.unique')]
#[UniqueEntity(fields: ['iso3Code'], message: 'country.iso3_code.unique', ignoreNull: true)]
#[ORM\HasLifecycleCallbacks]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private string $name = '';

    #[ORM\Column(name: 'iso2_code', type: 'string', length: 2)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[A-Z]{2}$/', message: 'country.iso2_code.invalid')]
    private string $iso2Code = '';

    #[ORM\Column(name: 'iso3_code', type: 'string', length: 3, nullable: true)]
    #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'country.iso3_code.invalid')]
    private ?string $iso3Code = null;

    #[ORM\Column(name: 'dial_code', type: 'string', length: 10)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\+[1-9]\d{0,8}$/', message: 'country.dial_code.invalid')]
    private string $dialCode = '';

    #[ORM\Column(name: 'flag_emoji', type: 'string', length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    private ?string $flagEmoji = null;

    #[ORM\Column(name: 'currency_code', type: 'string', length: 3, nullable: true)]
    #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'country.currency_code.invalid')]
    private ?string $currencyCode = null;

    #[ORM\Column(name: 'currency_icon', type: 'string', length: 16, nullable: true)]
    #[Assert\Length(max: 16)]
    private ?string $currencyIcon = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        if ('' !== $this->name) {
            return $this->name;
        }

        return '' !== $this->iso2Code ? $this->iso2Code : 'Country';
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
        $this->name = trim($name);

        return $this;
    }

    public function getIso2Code(): string
    {
        return $this->iso2Code;
    }

    public function setIso2Code(string $iso2Code): self
    {
        $this->iso2Code = strtoupper(trim($iso2Code));

        return $this;
    }

    public function getIso3Code(): ?string
    {
        return $this->iso3Code;
    }

    public function setIso3Code(?string $iso3Code): self
    {
        $iso3Code = null === $iso3Code ? null : strtoupper(trim($iso3Code));
        $this->iso3Code = '' === $iso3Code ? null : $iso3Code;

        return $this;
    }

    public function getDialCode(): string
    {
        return $this->dialCode;
    }

    public function setDialCode(string $dialCode): self
    {
        $dialCode = trim($dialCode);
        if ('' !== $dialCode && !str_starts_with($dialCode, '+')) {
            $dialCode = '+'.$dialCode;
        }

        $this->dialCode = $dialCode;

        return $this;
    }

    public function getFlagEmoji(): ?string
    {
        return $this->flagEmoji;
    }

    public function setFlagEmoji(?string $flagEmoji): self
    {
        $flagEmoji = null === $flagEmoji ? null : trim($flagEmoji);
        $this->flagEmoji = '' === $flagEmoji ? null : $flagEmoji;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $currencyCode = null === $currencyCode ? null : strtoupper(trim($currencyCode));
        $this->currencyCode = '' === $currencyCode ? null : $currencyCode;

        return $this;
    }

    public function getCurrencyIcon(): ?string
    {
        return $this->currencyIcon;
    }

    public function setCurrencyIcon(?string $currencyIcon): self
    {
        $currencyIcon = null === $currencyIcon ? null : trim($currencyIcon);
        $this->currencyIcon = '' === $currencyIcon ? null : $currencyIcon;

        return $this;
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

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function updateTimestampsOnCreate(): void
    {
        $now = new \DateTimeImmutable();
        if (!isset($this->createdAt)) {
            $this->createdAt = $now;
        }

        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestampOnUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
