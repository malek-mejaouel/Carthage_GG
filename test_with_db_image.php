<?php
require 'vendor/autoload.php';

use App\Entity\News;
use App\Service\FinalEnhancedPDFGeneratorService;
use Psr\Log\NullLogger;

// Create a test news item with a known image
$news = new News();
$news->setTitre('assassin\'s creed is OUT!');
$news->setContenu('Within the series\' alternate historical setting, Ezio was born into Italian nobility from Florence in 1459. His family had long been loyal to the Assassin Brotherhood, a secret organization inspired by the real-life Order of Assassins dedicated to safeguarding peace and freedom, though Ezio did not learn about his Assassin heritage until his late teens, after most of his immediate kin was executed during the Pazzi conspiracy.');
$news->setCategorie('announcements');
$news->setDatePublication(new DateTime('2026-02-18'));
// Set a known image from the uploads folder
$news->setImage('ezio-collection-ncsa-pagemeta-key-art-6995b16aa027b.avif');  // Known image file

$service = new FinalEnhancedPDFGeneratorService(new NullLogger(), __DIR__);
$response = $service->generateNewsPDF($news);

$content = $response->getContent();
file_put_contents('pdf_with_image.pdf', $content);

echo "PDF generated: " . strlen($content) . " bytes\n";
echo "Image set: " . $news->getImage() . "\n";

echo "Checking PDF structure:\n";
if (strpos($content, '/IMG') !== false) {
    echo "  ✓ Image XObject reference found\n";
}
if (strpos($content, '/XObject') !== false) {
    echo "  ✓ XObject declaration found\n";
}
if (strpos($content, 'stream') !== false) {
    echo "  ✓ Stream data present\n";
}
if (strpos($content, '%PDF') !== false) {
    echo "  ✓ Valid PDF header\n";
}
echo "\nPDF saved to: pdf_with_image.pdf\n";
?>
