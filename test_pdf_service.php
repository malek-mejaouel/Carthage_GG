<?php
// Quick test of Enhanced PDF service

require 'vendor/autoload.php';

use App\Entity\News;
use App\Service\EnhancedPDFGeneratorService;
use Psr\Log\NullLogger;

// Create a real News entity
$news = new News();
$news->setTitre('Test Article Title');
$news->setContenu('This is test content for the PDF. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.');
$news->setCategorie('Gaming');
$news->setDatePublication(new DateTime('2026-02-19'));
// Note: No need to set NewsId as it's optional

try {
    $projectDir = __DIR__;
    $service = new EnhancedPDFGeneratorService(new NullLogger(), $projectDir);
    $response = $service->generateNewsPDF($news);
    
    echo "✓ PDF generated successfully\n";
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
    echo "Content-Length: " . $response->headers->get('Content-Length') . "\n";
    
    // Get the content and show first 200 bytes
    $content = '';
    foreach ($response->getContent() as $chunk) {
        $content .= $chunk;
    }
    
    echo "Content Preview: " . substr($content, 0, 200) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>

