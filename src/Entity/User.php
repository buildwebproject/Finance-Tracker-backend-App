<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sonata\UserBundle\Entity\BaseUser3;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
#[ORM\AttributeOverrides([
    new ORM\AttributeOverride(
        name: 'email',
        column: new ORM\Column(type: 'string', length: 180, nullable: true),
    ),
    new ORM\AttributeOverride(
        name: 'emailCanonical',
        column: new ORM\Column(name: 'email_canonical', type: 'string', length: 180, unique: true, nullable: true),
    ),
])]
class User extends BaseUser3
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $authProvider = null;

    #[ORM\Column(type: 'string', length: 191, unique: true, nullable: true)]
    private ?string $googleSubject = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googleEmail = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googleName = null;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $googlePictureUrl = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $googleEmailVerified = null;

    #[ORM\Column(type: 'string', length: 20, unique: true, nullable: true)]
    private ?string $twilioPhoneNumber = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $twilioChannel = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSocialLoginAt = null;

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $fullName = null === $fullName ? null : trim($fullName);
        $this->fullName = '' === $fullName ? null : $fullName;

        return $this;
    }

    public function getAuthProvider(): ?string
    {
        return $this->authProvider;
    }

    public function setAuthProvider(?string $authProvider): self
    {
        $authProvider = null === $authProvider ? null : strtolower(trim($authProvider));
        $this->authProvider = '' === $authProvider ? null : $authProvider;

        return $this;
    }

    public function setEmail(?string $email): void
    {
        $email = null === $email ? null : trim($email);
        parent::setEmail('' === $email ? null : $email);
    }

    public function getGoogleSubject(): ?string
    {
        return $this->googleSubject;
    }

    public function setGoogleSubject(?string $googleSubject): self
    {
        $googleSubject = null === $googleSubject ? null : trim($googleSubject);
        $this->googleSubject = '' === $googleSubject ? null : $googleSubject;

        return $this;
    }

    public function getGoogleEmail(): ?string
    {
        return $this->googleEmail;
    }

    public function setGoogleEmail(?string $googleEmail): self
    {
        $googleEmail = null === $googleEmail ? null : trim($googleEmail);
        $this->googleEmail = '' === $googleEmail ? null : $googleEmail;

        return $this;
    }

    public function getGoogleName(): ?string
    {
        return $this->googleName;
    }

    public function setGoogleName(?string $googleName): self
    {
        $googleName = null === $googleName ? null : trim($googleName);
        $this->googleName = '' === $googleName ? null : $googleName;

        return $this;
    }

    public function getGooglePictureUrl(): ?string
    {
        return $this->googlePictureUrl;
    }

    public function setGooglePictureUrl(?string $googlePictureUrl): self
    {
        $googlePictureUrl = null === $googlePictureUrl ? null : trim($googlePictureUrl);
        $this->googlePictureUrl = '' === $googlePictureUrl ? null : $googlePictureUrl;

        return $this;
    }

    public function getGoogleEmailVerified(): ?bool
    {
        return $this->googleEmailVerified;
    }

    public function setGoogleEmailVerified(?bool $googleEmailVerified): self
    {
        $this->googleEmailVerified = $googleEmailVerified;

        return $this;
    }

    public function getTwilioPhoneNumber(): ?string
    {
        return $this->twilioPhoneNumber;
    }

    public function setTwilioPhoneNumber(?string $twilioPhoneNumber): self
    {
        $twilioPhoneNumber = null === $twilioPhoneNumber ? null : trim($twilioPhoneNumber);
        $this->twilioPhoneNumber = '' === $twilioPhoneNumber ? null : $twilioPhoneNumber;

        return $this;
    }

    public function getTwilioChannel(): ?string
    {
        return $this->twilioChannel;
    }

    public function setTwilioChannel(?string $twilioChannel): self
    {
        $twilioChannel = null === $twilioChannel ? null : strtolower(trim($twilioChannel));
        $this->twilioChannel = '' === $twilioChannel ? null : $twilioChannel;

        return $this;
    }

    public function getLastSocialLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastSocialLoginAt;
    }

    public function setLastSocialLoginAt(?\DateTimeImmutable $lastSocialLoginAt): self
    {
        $this->lastSocialLoginAt = $lastSocialLoginAt;

        return $this;
    }
}
