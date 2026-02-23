<?php
require 'vendor/autoload.php';

use App\Service\ProfanityFilter;

$profanityFilter = new ProfanityFilter();

echo "=== Profanity Filter Integration Test ===\n\n";

// Test 1: Comment with profanities
$test1 = 'fuck you, this is damn bad shit';
echo "Test 1 - Comment with profanities:\n";
echo "  Input:    " . $test1 . "\n";
echo "  Filtered: " . $profanityFilter->filter($test1) . "\n";
echo "  Contains Profanity: " . ($profanityFilter->containsProfanity($test1) ? 'YES' : 'NO') . "\n";
echo "  Matches: " . json_encode($profanityFilter->getMatches($test1)) . "\n\n";

// Test 2: Clean comment
$test2 = 'This is a nice comment';
echo "Test 2 - Clean comment:\n";
echo "  Input:    " . $test2 . "\n";
echo "  Filtered: " . $profanityFilter->filter($test2) . "\n";
echo "  Contains Profanity: " . ($profanityFilter->containsProfanity($test2) ? 'YES' : 'NO') . "\n\n";

// Test 3: Comment with parent_id encoding and profanities
$test3 = '__PARENT__5||hey man, this fucking sucks';
echo "Test 3 - Reply with parent_id encoding and profanity:\n";
echo "  Input:    " . $test3 . "\n";
echo "  Filtered: " . $profanityFilter->filter($test3) . "\n";
echo "  Contains Profanity: " . ($profanityFilter->containsProfanity($test3) ? 'YES' : 'NO') . "\n";
