<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FaceRecognitionService
{
    private HttpClientInterface $httpClient;

    private string $baseUrl;

    public function __construct(HttpClientInterface $httpClient, string $faceRecognitionApiBaseUrl)
    {
        $this->httpClient = $httpClient;
        $this->baseUrl = rtrim($faceRecognitionApiBaseUrl, '/');
    }

    public function extractDescriptor(string $imageBase64): ?string
    {
        $payload = [
            'image' => $imageBase64,
        ];

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/extract', [
                'json' => $payload,
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return null;
            }

            $data = $response->toArray(false);

            if (!isset($data['descriptor'])) {
                return null;
            }

            $encoded = json_encode($data['descriptor']);
            return is_string($encoded) ? $encoded : null;
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
             error_log('Face API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param list<float> $descriptor1
     * @param list<float> $descriptor2
     */
    public function compareFaces(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            return 10.0; // Large distance (dissimilar)
        }

        $sum = 0.0;
        foreach ($descriptor1 as $i => $val) {
            $diff = $val - $descriptor2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }
}
