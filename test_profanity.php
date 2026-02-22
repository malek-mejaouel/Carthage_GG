<?php
require 'vendor/autoload.php';

use ConsoleTVs\Profanity\Builder;

// Test comment with bad words
$test1 = 'fuck you, this is damn bad';
$blocker1 = Builder::blocker($test1, '*');
$result1 = $blocker1->filter();
$clean1 = $blocker1->clean();
echo 'Test 1 Input: ' . $test1 . PHP_EOL;
echo 'Test 1 Filtered: ' . $result1 . PHP_EOL;
echo 'Test 1 Is Clean: ' . ($clean1 ? 'YES' : 'NO') . PHP_EOL;
echo 'Test 1 Bad Words: ' . json_encode($blocker1->badWords()) . PHP_EOL . PHP_EOL;

// Test clean comment
$test2 = 'This is a nice comment';
$blocker2 = Builder::blocker($test2, '*');
$result2 = $blocker2->filter();
$clean2 = $blocker2->clean();
echo 'Test 2 Input: ' . $test2 . PHP_EOL;
echo 'Test 2 Filtered: ' . $result2 . PHP_EOL;
echo 'Test 2 Is Clean: ' . ($clean2 ? 'YES' : 'NO') . PHP_EOL;
