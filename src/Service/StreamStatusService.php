<?php

namespace App\Service;

use App\Entity\Stream;
use Doctrine\ORM\EntityManagerInterface;

class StreamStatusService
{
    private ?string $twitchClientId;
    private ?string $twitchAccessToken;
    private ?string $youtubeApiKey;

    public function __construct(
        ?string $twitchClientId = null,
        ?string $twitchAccessToken = null,
        ?string $youtubeApiKey = null
    ) {
        $this->twitchClientId = $twitchClientId;
        $this->twitchAccessToken = $twitchAccessToken;
        $this->youtubeApiKey = $youtubeApiKey;
    }

    /**
     * @param array<\App\Entity\Stream> $streams
     */
    public function refreshForList(array $streams, EntityManagerInterface $em, int $minIntervalSeconds = 60): void
    {
        $changed = false;
        $now = new \DateTimeImmutable();
        foreach ($streams as $s) {
            $updatedAt = $s->getUpdatedAt();
            $diff = $now->getTimestamp() - $updatedAt->getTimestamp();
            if ($diff < $minIntervalSeconds) {
                continue;
            }
            $wasLive = $s->isLive();
            $viewers = $s->getViewerCount();
            $status = $this->getStatus($s);
            if ($status !== null) {
                $s->setIsLive((bool)$status['live']);
                $s->setViewerCount($status['viewers']);
                $s->setUpdatedAt($now);
                $changed = true;
            }
        }
        if ($changed) {
            $em->flush();
        }
    }

    /**
     * @return array{live: bool, viewers: int}|null
     */
    public function getStatus(Stream $s): ?array
    {
        $platform = $s->getPlatform();
        if ($platform === 'twitch' && $s->getChannelName()) {
            return $this->fetchTwitchStatus($s->getChannelName());
        }
        if ($platform === 'youtube' && $s->getYoutubeVideoId()) {
            return $this->fetchYouTubeStatus($s->getYoutubeVideoId());
        }
        return null;
    }

    /**
     * @return array{live: bool, viewers: int}|null
     */
    private function fetchTwitchStatus(string $channel): ?array
    {
        if (!$this->twitchClientId) { $this->twitchClientId = getenv('TWITCH_CLIENT_ID') ?: null; }
        if (!$this->twitchAccessToken) { $this->twitchAccessToken = getenv('TWITCH_ACCESS_TOKEN') ?: null; }
        if (!$this->twitchClientId || !$this->twitchAccessToken) {
            return null;
        }
        $url = "https://api.twitch.tv/helix/streams?user_login=" . rawurlencode($channel);
        $resp = $this->curlGet($url, [
            'Client-Id: ' . $this->twitchClientId,
            'Authorization: Bearer ' . $this->twitchAccessToken,
        ]);
        if (!$resp) { return null; }
        $json = json_decode($resp, true);
        if (!is_array($json)) { return null; }
        $data = $json['data'] ?? [];
        if (is_array($data) && count($data) > 0) {
            $entry = $data[0];
            return [
                'live' => true,
                'viewers' => (int)($entry['viewer_count'] ?? 0),
            ];
        }
        return ['live' => false, 'viewers' => 0];
    }

    /**
     * @return array{live: bool, viewers: int}|null
     */
    private function fetchYouTubeStatus(string $videoId): ?array
    {
        if (!$this->youtubeApiKey) { $this->youtubeApiKey = getenv('YOUTUBE_API_KEY') ?: null; }
        if (!$this->youtubeApiKey) {
            return null;
        }
        $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet,liveStreamingDetails&id=" . rawurlencode($videoId) . "&key=" . rawurlencode($this->youtubeApiKey);
        $resp = $this->curlGet($url);
        if (!$resp) { return null; }
        $json = json_decode($resp, true);
        if (!is_array($json)) { return null; }
        $items = $json['items'] ?? [];
        if (!is_array($items) || count($items) === 0) { return null; }
        $item = $items[0];
        $snippet = $item['snippet'] ?? [];
        $liveContent = $snippet['liveBroadcastContent'] ?? 'none';
        $lsd = $item['liveStreamingDetails'] ?? [];
        $viewers = (int)($lsd['concurrentViewers'] ?? 0);
        return [
            'live' => $liveContent === 'live',
            'viewers' => $viewers,
        ];
    }

    /**
     * @param array<string> $headers
     */
    private function curlGet(string $url, array $headers = []): ?string
    {
        if ($url === '') {
            return null;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $body = curl_exec($ch);
        $errNo = curl_errno($ch);
        curl_close($ch);
        if ($errNo !== 0) {
            return null;
        }
        return is_string($body) ? $body : null;
    }
}
