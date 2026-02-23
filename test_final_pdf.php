<?php
require 'vendor/autoload.php';

use App\Entity\News;
use App\Service\FinalEnhancedPDFGeneratorService;
use Psr\Log\NullLogger;

$news = new News();
$news->setTitre('assassin\'s creed is OUT!');
$news->setContenu('Within the series\' alternate historical setting, Ezio was born into Italian nobility from Florence in 1459. His family had long been loyal to the Assassin Brotherhood, a secret organization inspired by the real-life Order of Assassins dedicated to safeguarding peace and freedom, though Ezio did not learn about his Assassin heritage until his late teens, after most of his immediate kin was executed during the Pazzi conspiracy.');
$news->setCategorie('announcements');
$news->setDatePublication(new DateTime('2026-02-18'));

$service = new FinalEnhancedPDFGeneratorService(new NullLogger());
$response = $service->generateNewsPDF($news);

// Get PDF content
$content = $response->getContent();
file_put_contents('final_test.pdf', $content);

echo "✓ PDF generated successfully\n";
echo "File size: " . strlen($content) . " bytes\n";
echo "First 150 chars:\n";
echo substr($content, 0, 150) . "\n";
echo "---\n";
echo "Checking for design elements:\n";
if (strpos($content, '0.1 0.1 0.15') !== false) echo "  ✓ Dark theme color found\n";
if (strpos($content, '1 1 1 rg') !== false) echo "  ✓ White text found\n";
if (strpos($content, 'CarthageGG') !== false) echo "  ✓ Branding found\n";
if (strpos($content, 'assassin') !== false) echo "  ✓ Content found\n";
?>
