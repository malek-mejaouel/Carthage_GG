<?php
require 'vendor/autoload.php';

use App\Entity\News;
use App\Service\EnhancedPDFGeneratorService;
use Psr\Log\NullLogger;

$news = new News();
$news->setTitre('Assassins Creed is OUT!');
$news->setContenu('Within the series alternate historical setting, Ezio was born into Italian nobility from Florence in 1459. His family had long been loyal to the Assassin Brotherhood, a secret organization inspired by the real-life Order of Assassins dedicated to safeguarding peace and freedom, though Ezio did not learn about his Assassin heritage until his late teens, after most of his immediate kin was executed during the Pazzi conspiracy.');
$news->setCategorie('announcements');
$news->setDatePublication(new DateTime('2026-02-18'));

$service = new EnhancedPDFGeneratorService(new NullLogger(), __DIR__);
try {
    $response = $service->generateNewsPDF($news);
    
    // Get content stream
    $content = '';
    foreach ($response->getContent() as $chunk) {
        $content .= $chunk;
        if (strlen($content) > 1000000) break; // Safety limit
    }
    
    file_put_contents('test_design.pdf', $content);
    echo "PDF saved successfully\n";
    echo "File size: " . filesize('test_design.pdf') . " bytes\n";
    echo "First 150 chars:\n";
    echo substr($content, 0, 150) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
