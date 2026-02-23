<?php
require 'vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Symfony\Component\Dotenv\Dotenv;

// Load environment
if (file_exists($file = __DIR__.'/.env')) {
    (new Dotenv())->usePutenv()->load($file);
}
if (file_exists($file = __DIR__.'/.env.local')) {
    (new Dotenv())->usePutenv()->load($file);
}

// Bootstrap Symfony kernel
$kernel = new \App\Kernel($_ENV['APP_ENV'] ?? 'dev', $_ENV['APP_DEBUG'] ?? false);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
$pdfService = $container->get('\App\Service\EnhancedPDFGeneratorService');

// Get a real news article
$newsRepo = $em->getRepository('\App\Entity\News');
$news = $newsRepo->findOneBy(['newsId' => 1]);

if (!$news) {
    echo "No news article found with ID 1\n";
    exit(1);
}

echo "Found article: " . $news->getTitre() . "\n";

try {
    $response = $pdfService->generateNewsPDF($news);
    
    // Get content
    $content = $response->getContent();
    
    // Save to file
    file_put_contents('real_enhanced_test.pdf', $content);
    echo "✓ PDF generated and saved: real_enhanced_test.pdf\n";
    echo "Size: " . strlen($content) . " bytes\n";
    
    // Check for design elements
    if (strpos($content, 'CarthageGG') !== false) {
        echo "✓ CarthageGG branding found\n";
    }
    if (strpos($content, '0.08 0.08 0.1') !== false) {
        echo "✓ Dark header color found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
