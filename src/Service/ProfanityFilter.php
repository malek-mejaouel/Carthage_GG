<?php

namespace App\Service;

use ConsoleTVs\Profanity\Builder;

/**
 * ProfanityFilter Service
 * 
 * Wraps the ConsoleTVs/Profanity library to filter bad words in comments.
 * Replaces profanities with asterisks (****).
 */
class ProfanityFilter
{
    /**
     * Filter a string by replacing bad words with asterisks
     * 
     * @param string $text The text to filter
     * @return string The filtered text with bad words replaced by asterisks
     */
    public function filter(string $text): string
    {
        try {
            $blocker = Builder::blocker($text, '*');
            return $blocker->filter();
        } catch (\Exception $e) {
            // If filtering fails, return the original text
            return $text;
        }
    }

    /**
     * Check if a string contains bad words
     * 
     * @param string $text The text to check
     * @return bool True if bad words are found, false otherwise
     */
    public function containsProfanity(string $text): bool
    {
        try {
            $blocker = Builder::blocker($text);
            return !$blocker->clean();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all bad word matches in a string
     * 
     * @param string $text The text to check
     * @return array Array of matched bad words
     */
    public function getMatches(string $text): array
    {
        try {
            $blocker = Builder::blocker($text);
            return $blocker->badWords();
        } catch (\Exception $e) {
            return [];
        }
    }
}
