<?php
require 'vendor/autoload.php';

use App\Entity\News;
use App\Service\EnhancedPDFGeneratorService;
use Psr\Log\NullLogger;

$news = new News();
$news->setTitre('Test PDF with Enhanced Design');
$news->setContenu('This is a test PDF to verify the enhanced design with colors and branding works correctly.  The PDF should have a dark header with white text, an article title, category and date, and properly formatted content below. Thank you for testing!');
$news->setCategorie('Gaming');
$news->setDatePublication(new DateTime('2026-02-19'));

$service = new EnhancedPDFGeneratorService(new NullLogger(), __DIR__);

// Use reflection to call the private generatePDF method for testing
$reflClass = new ReflectionClass($service);
$generateMethod = $reflClass->getMethod('generatePDF');
$generateMethod->setAccessible(true);

$pdfContent = $generateMethod->invoke($service, $news);
file_put_contents('test_design.pdf', $pdfContent);

echo "PDF saved: test_design.pdf\n";
echo "Size: " . strlen($pdfContent) . " bytes\n";
echo "First 200 characters:\n";
echo substr($pdfContent, 0, 200) . "\n";
?>
