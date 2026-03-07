<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Security\ApiTokenManager;
use App\Security\OAuth\GoogleIdTokenVerifier;
use App\Security\Otp\TwilioVerifyClient;
use Sonata\UserBundle\Model\UserInterface as SonataUserInterface;
use Sonata\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'sonata.user.manager.user')]
        private readonly UserManagerInterface $userManager,
        private readonly ApiTokenManager $apiTokenManager,
        private readonly GoogleIdTokenVerifier $googleIdTokenVerifier,
        private readonly TwilioVerifyClient $twilioVerifyClient,
    ) {
    }

    #[Route('/google', name: 'api_auth_google', methods: ['POST'])]
    public function googleLogin(Request $request): JsonResponse
    {
        try {
            $payload = $this->getJsonPayload($request);
            $idToken = trim((string) ($payload['id_token'] ?? ''));
            if ('' === $idToken) {
                return $this->json(['message' => 'id_token is required.'], Response::HTTP_BAD_REQUEST);
            }

            $googlePayload = $this->googleIdTokenVerifier->verify($idToken);
            $user = $this->findOrCreateGoogleUser($googlePayload);

            if (!$user->isEnabled()) {
                return $this->json(['message' => 'User account is disabled.'], Response::HTTP_FORBIDDEN);
            }

            return $this->createAuthResponse($user);
        } catch (\LogicException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/otp/send', name: 'api_auth_otp_send', methods: ['POST'])]
    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $payload = $this->getJsonPayload($request);
            $phoneNumber = $this->normalizePhoneNumber($payload['phone'] ?? null);
            if (null === $phoneNumber) {
                return $this->json(['message' => 'phone must be a valid E.164 phone number.'], Response::HTTP_BAD_REQUEST);
            }

            $this->twilioVerifyClient->sendOtp($phoneNumber);

            return $this->json([
                'message' => 'OTP sent successfully.',
                'phone' => $phoneNumber,
            ]);
        } catch (\LogicException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/otp/verify', name: 'api_auth_otp_verify', methods: ['POST'])]
    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $payload = $this->getJsonPayload($request);
            $phoneNumber = $this->normalizePhoneNumber($payload['phone'] ?? null);
            $otpCode = trim((string) ($payload['otp'] ?? ''));

            if (null === $phoneNumber) {
                return $this->json(['message' => 'phone must be a valid E.164 phone number.'], Response::HTTP_BAD_REQUEST);
            }

            if (1 !== preg_match('/^\d{4,8}$/', $otpCode)) {
                return $this->json(['message' => 'otp must be a 4 to 8 digit code.'], Response::HTTP_BAD_REQUEST);
            }

            $isValidOtp = $this->twilioVerifyClient->verifyOtp($phoneNumber, $otpCode);
            if (!$isValidOtp) {
                return $this->json(['message' => 'Invalid or expired OTP.'], Response::HTTP_UNAUTHORIZED);
            }

            $user = $this->findOrCreatePhoneUser($phoneNumber);
            if (!$user->isEnabled()) {
                return $this->json(['message' => 'User account is disabled.'], Response::HTTP_FORBIDDEN);
            }

            return $this->createAuthResponse($user);
        } catch (\LogicException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonPayload(Request $request): array
    {
        $rawBody = trim($request->getContent());
        if ('' === $rawBody) {
            throw new \InvalidArgumentException('JSON request body is required.');
        }

        /** @var mixed $payload */
        $payload = json_decode($rawBody, true);
        if (!\is_array($payload)) {
            throw new \InvalidArgumentException('Invalid JSON payload.');
        }

        return $payload;
    }

    private function normalizePhoneNumber(mixed $phone): ?string
    {
        if (!\is_scalar($phone)) {
            return null;
        }

        $value = trim((string) $phone);
        if ('' === $value) {
            return null;
        }

        $value = str_replace([' ', '-', '(', ')'], '', $value);
        if (!str_starts_with($value, '+')) {
            $value = '+'.$value;
        }

        if (1 !== preg_match('/^\+[1-9]\d{7,14}$/', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param array{sub: string, email: string, email_verified: bool, name?: string, picture?: string} $googlePayload
     */
    private function findOrCreateGoogleUser(array $googlePayload): SonataUserInterface
    {
        $googleSubject = $googlePayload['sub'];
        $email = $googlePayload['email'];

        /** @var SonataUserInterface|null $user */
        $user = $this->userManager->findOneBy(['googleSubject' => $googleSubject]);
        if (null === $user) {
            $user = $this->userManager->findUserByEmail($email);
        }

        if (null !== $user && $user instanceof User && null !== $user->getGoogleSubject() && $googleSubject !== $user->getGoogleSubject()) {
            throw new \RuntimeException('This email is already linked to a different Google account.');
        }

        if (null !== $user) {
            $this->syncGoogleLoginData($user, $googlePayload);

            return $user;
        }

        $subject = preg_replace('/[^a-zA-Z0-9_-]/', '', $googleSubject);
        if (!\is_string($subject) || '' === $subject) {
            $subject = substr(hash('sha256', $googleSubject), 0, 24);
        }

        $username = $this->buildUniqueUsername('google_'.$subject);

        $newUser = $this->createUser($username, $email);
        $this->syncGoogleLoginData($newUser, $googlePayload);

        return $newUser;
    }

    private function findOrCreatePhoneUser(string $phoneNumber): SonataUserInterface
    {
        /** @var SonataUserInterface|null $user */
        $user = $this->userManager->findOneBy(['twilioPhoneNumber' => $phoneNumber]);
        if (null !== $user) {
            $this->syncTwilioLoginData($user, $phoneNumber);

            return $user;
        }

        $digits = preg_replace('/\D+/', '', $phoneNumber);
        if (!\is_string($digits) || '' === $digits) {
            throw new \RuntimeException('Invalid phone number.');
        }

        $username = 'phone_'.$digits;
        $user = $this->userManager->findUserByUsername($username);
        if (null !== $user) {
            $this->syncTwilioLoginData($user, $phoneNumber);

            return $user;
        }

        $newUser = $this->createUser($this->buildUniqueUsername($username), null);
        $this->syncTwilioLoginData($newUser, $phoneNumber);

        return $newUser;
    }

    /**
     * @param array{sub: string, email: string, email_verified: bool, name?: string, picture?: string} $googlePayload
     */
    private function syncGoogleLoginData(SonataUserInterface $user, array $googlePayload): void
    {
        if (!$user instanceof User) {
            return;
        }

        $googleName = null;
        if (isset($googlePayload['name']) && \is_string($googlePayload['name']) && '' !== trim($googlePayload['name'])) {
            $googleName = $googlePayload['name'];
        }

        $googlePictureUrl = null;
        if (isset($googlePayload['picture']) && \is_string($googlePayload['picture']) && '' !== trim($googlePayload['picture'])) {
            $googlePictureUrl = $googlePayload['picture'];
        }

        $user
            ->setAuthProvider('google')
            ->setGoogleSubject($googlePayload['sub'])
            ->setGoogleEmail($googlePayload['email'])
            ->setGoogleEmailVerified($googlePayload['email_verified'])
            ->setGoogleName($googleName)
            ->setGooglePictureUrl($googlePictureUrl)
            ->setLastSocialLoginAt(new \DateTimeImmutable());

        if (null !== $googleName && null === $user->getFullName()) {
            $user->setFullName($googleName);
        }

        $this->userManager->save($user);
    }

    private function syncTwilioLoginData(SonataUserInterface $user, string $phoneNumber): void
    {
        if (!$user instanceof User) {
            return;
        }

        $existingEmail = $user->getEmail();
        if (null !== $existingEmail && 1 === preg_match('/^phone_\\d+@otp\\.local$/', $existingEmail)) {
            $user->setEmail(null);
        }

        $user
            ->setAuthProvider('twilio')
            ->setTwilioPhoneNumber($phoneNumber)
            ->setTwilioChannel($this->twilioVerifyClient->getChannel())
            ->setLastSocialLoginAt(new \DateTimeImmutable());

        $this->userManager->save($user);
    }

    private function createUser(string $username, ?string $email): SonataUserInterface
    {
        $user = $this->userManager->create();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPlainPassword(bin2hex(random_bytes(24)));
        $user->setEnabled(true);
        $user->addRole('ROLE_USER');

        $this->userManager->save($user);

        return $user;
    }

    private function buildUniqueUsername(string $baseUsername): string
    {
        $candidate = substr($baseUsername, 0, 180);

        if (null === $this->userManager->findUserByUsername($candidate)) {
            return $candidate;
        }

        for ($i = 1; $i <= 100; ++$i) {
            $suffix = '_'.$i;
            $candidate = substr($baseUsername, 0, 180 - strlen($suffix)).$suffix;

            if (null === $this->userManager->findUserByUsername($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Unable to generate a unique username.');
    }

    private function createAuthResponse(SymfonyUserInterface $user): JsonResponse
    {
        $tokenData = $this->apiTokenManager->issueToken($user);

        return $this->json([
            'token_type' => 'Bearer',
            'access_token' => $tokenData['access_token'],
            'expires_at' => (new \DateTimeImmutable('@'.$tokenData['expires_at']))->format(\DateTimeInterface::ATOM),
            'user' => $this->buildUserPayload($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserPayload(SymfonyUserInterface $user): array
    {
        $payload = [
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'identifier' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ];

        if (!$user instanceof User) {
            return $payload;
        }

        $payload['full_name'] = $user->getFullName();
        $payload['auth_provider'] = $user->getAuthProvider();
        $payload['google_subject'] = $user->getGoogleSubject();
        $payload['google_email'] = $user->getGoogleEmail();
        $payload['google_name'] = $user->getGoogleName();
        $payload['google_picture_url'] = $user->getGooglePictureUrl();
        $payload['google_email_verified'] = $user->getGoogleEmailVerified();
        $payload['twilio_phone_number'] = $user->getTwilioPhoneNumber();
        $payload['twilio_channel'] = $user->getTwilioChannel();
        $payload['last_social_login_at'] = $user->getLastSocialLoginAt()?->format(\DateTimeInterface::ATOM);

        return $payload;
    }
}
