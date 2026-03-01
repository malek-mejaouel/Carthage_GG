<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * GiphyService
 * 
 * Handles integration with the Giphy API for searching and retrieving GIFs.
 * Provides methods to search for GIFs by keyword and format results for frontend use.
 */
class GiphyService
{
    private const GIPHY_API_BASE = 'https://api.giphy.com/v1/gifs';
    private const GIPHY_API_KEY = 'E3ngwg8w3zZOdvJzVEAL6fTautfDivwY';
    
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    /**
     * Constructor - Injects HTTP client for API calls and logger for debugging
     * 
     * @param HttpClientInterface $httpClient Symfony HTTP client for making requests
     * @param LoggerInterface $logger Logger for debugging and error tracking
     */
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Search for GIFs by keyword
     * 
     * @param string $query Search query for GIFs
     * @param int $limit Number of results to return (default: 20, max: 50)
     * @param int $offset Pagination offset (default: 0)
     * 
     * @return array Array of GIF objects with url, name, and thumbnail
     */
    /**
     * @return list<array{
     *   id: ?string,
     *   name: string,
     *   url: ?string,
     *   thumbnail: ?string,
     *   width: ?string,
     *   height: ?string
     * }>
     */
    public function searchGifs(string $query, int $limit = 20, int $offset = 0): array
    {
        if (empty(trim($query))) {
            $this->logger->warning('GiphyService: Empty search query provided');
            return [];
        }

        try {
            // Ensure limit doesn't exceed API maximum
            $limit = min($limit, 50);
            $offset = max($offset, 0);

            $url = sprintf(
                '%s/search?q=%s&api_key=%s&limit=%d&offset=%d&rating=pg-13',
                self::GIPHY_API_BASE,
                urlencode($query),
                self::GIPHY_API_KEY,
                $limit,
                $offset
            );

            $this->logger->debug('GiphyService: Fetching GIFs', ['query' => $query, 'limit' => $limit, 'offset' => $offset]);

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('GiphyService: API returned error status', ['status' => $statusCode]);
                return [];
            }

            $data = $response->toArray();

            // Check if API returned data successfully
            if (!isset($data['data']) || !is_array($data['data'])) {
                $this->logger->warning('GiphyService: Unexpected API response format', ['data' => $data]);
                return [];
            }

            // Format GIF data for frontend consumption
            $gifs = [];
            foreach ($data['data'] as $gif) {
                $gifs[] = [
                    'id' => $gif['id'] ?? null,
                    'name' => $gif['title'] ?? 'Untitled GIF',
                    'url' => $gif['images']['fixed_height']['url'] ?? $gif['url'] ?? null,
                    'thumbnail' => $gif['images']['fixed_height_still']['url'] ?? null,
                    'width' => $gif['images']['fixed_height']['width'] ?? null,
                    'height' => $gif['images']['fixed_height']['height'] ?? null,
                ];
            }

            $this->logger->debug('GiphyService: Successfully fetched GIFs', ['count' => count($gifs)]);

            return $gifs;

        } catch (\Exception $e) {
            $this->logger->error('GiphyService: Error fetching GIFs', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            return [];
        }
    }

    /**
     * Get trending GIFs (no search query required)
     * 
     * @param int $limit Number of results to return (default: 20, max: 50)
     * @param int $offset Pagination offset (default: 0)
     * 
     * @return array Array of trending GIF objects
     */
    /**
     * @return list<array{
     *   id: ?string,
     *   name: string,
     *   url: ?string,
     *   thumbnail: ?string,
     *   width: ?string,
     *   height: ?string
     * }>
     */
    public function getTrendingGifs(int $limit = 20, int $offset = 0): array
    {
        try {
            $limit = min($limit, 50);
            $offset = max($offset, 0);

            $url = sprintf(
                '%s/trending?api_key=%s&limit=%d&offset=%d&rating=pg-13',
                self::GIPHY_API_BASE,
                self::GIPHY_API_KEY,
                $limit,
                $offset
            );

            $this->logger->debug('GiphyService: Fetching trending GIFs', ['limit' => $limit, 'offset' => $offset]);

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('GiphyService: Trending API error', ['status' => $response->getStatusCode()]);
                return [];
            }

            $data = $response->toArray();

            if (!isset($data['data']) || !is_array($data['data'])) {
                $this->logger->warning('GiphyService: Unexpected trending API response');
                return [];
            }

            $gifs = [];
            foreach ($data['data'] as $gif) {
                $gifs[] = [
                    'id' => $gif['id'] ?? null,
                    'name' => $gif['title'] ?? 'Untitled GIF',
                    'url' => $gif['images']['fixed_height']['url'] ?? $gif['url'] ?? null,
                    'thumbnail' => $gif['images']['fixed_height_still']['url'] ?? null,
                    'width' => $gif['images']['fixed_height']['width'] ?? null,
                    'height' => $gif['images']['fixed_height']['height'] ?? null,
                ];
            }

            return $gifs;

        } catch (\Exception $e) {
            $this->logger->error('GiphyService: Error fetching trending GIFs', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
