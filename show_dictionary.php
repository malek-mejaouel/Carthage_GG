<?php
$json = file_get_contents('vendor/consoletvs/profanity/Dictionaries/Default.json');
$words = json_decode($json, true);

// Filter for English words only
$english = array_filter($words, function($item) {
    return $item['language'] === 'en';
});

// Get unique words and sort them
$uniqueWords = array_unique(array_column($english, 'word'));
sort($uniqueWords);

echo "=== English Profanity Dictionary ===\n";
echo "Total English profanity words: " . count($uniqueWords) . "\n\n";

echo "Sample of censored words:\n";
echo "─────────────────────────────\n";
foreach (array_slice($uniqueWords, 0, 80) as $word) {
    echo "  • $word\n";
}

// Count by language
$languages = [];
foreach ($words as $item) {
    $lang = $item['language'];
    $languages[$lang] = ($languages[$lang] ?? 0) + 1;
}

echo "\n=== Supported Languages ===\n";
arsort($languages);
foreach ($languages as $lang => $count) {
    echo "  $lang: $count words\n";
}

echo "\nTotal words in dictionary: " . count($words) . "\n";
