<?php

namespace App\Security\Otp;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class TwilioVerifyClient
{
    private const BASE_URL = 'https://verify.twilio.com/v2/Services/%s/%s';

    public function __construct(
        #[Autowire('%env(string:TWILIO_ACCOUNT_SID)%')]
        private readonly string $accountSid,
        #[Autowire('%env(string:TWILIO_AUTH_TOKEN)%')]
        private readonly string $authToken,
        #[Autowire('%env(string:TWILIO_VERIFY_SERVICE_SID)%')]
        private readonly string $verifyServiceSid,
        #[Autowire('%env(string:TWILIO_VERIFY_CHANNEL)%')]
        private readonly string $channel,
    ) {
    }

    public function sendOtp(string $phoneNumber): void
    {
        $this->assertConfigured();

        $response = $this->postForm(
            sprintf(self::BASE_URL, $this->verifyServiceSid, 'Verifications'),
            [
                'To' => $phoneNumber,
                'Channel' => $this->getChannel(),
            ]
        );

        if ($response['status_code'] >= 400) {
            throw new \RuntimeException($response['message']);
        }
    }

    public function verifyOtp(string $phoneNumber, string $otpCode): bool
    {
        $this->assertConfigured();

        $response = $this->postForm(
            sprintf(self::BASE_URL, $this->verifyServiceSid, 'VerificationCheck'),
            [
                'To' => $phoneNumber,
                'Code' => $otpCode,
            ]
        );

        if ($response['status_code'] >= 500) {
            throw new \RuntimeException($response['message']);
        }

        return 'approved' === ($response['body']['status'] ?? '');
    }

    public function getChannel(): string
    {
        return '' === $this->channel ? 'sms' : $this->channel;
    }

    private function assertConfigured(): void
    {
        if ('' === $this->accountSid || '' === $this->authToken || '' === $this->verifyServiceSid) {
            throw new \LogicException('Twilio Verify credentials are not configured.');
        }
    }

    /**
     * @param array<string, string> $data
     *
     * @return array{status_code: int, message: string, body: array<string, mixed>}
     */
    private function postForm(string $url, array $data): array
    {
        $body = http_build_query($data);
        $authHeader = 'Authorization: Basic '.base64_encode($this->accountSid.':'.$this->authToken);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                    $authHeader,
                ])."\r\n",
                'content' => $body,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        if (false === $responseBody) {
            throw new \RuntimeException('Unable to contact Twilio Verify API.');
        }

        $statusCode = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
            $statusCode = (int) $matches[1];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($responseBody, true);
        if (!\is_array($decoded)) {
            $decoded = [];
        }

        $message = (string) ($decoded['message'] ?? 'Twilio Verify request failed.');

        return [
            'status_code' => $statusCode,
            'message' => $message,
            'body' => $decoded,
        ];
    }
}
