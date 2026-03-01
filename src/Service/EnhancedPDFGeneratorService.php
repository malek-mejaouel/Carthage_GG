<?php

namespace App\Service;

use App\Entity\News;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

/**
 * EnhancedPDFGeneratorService
 * 
 * Generates professional PDFs with images, logo, and enhanced design.
 * Includes article image and CarthageGG branding.
 */
class EnhancedPDFGeneratorService
{
    private LoggerInterface $logger;
    private string $projectDir;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    public function generateNewsPDF(News $news): Response
    {
        try {
            $pdfContent = $this->generatePDF($news);
            
            $filename = $this->sanitizeFilename($news->getTitre() ?? 'News Article') . '.pdf';
            
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('PDF error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate enhanced PDF with styling (images will be added later)
     */
    private function generatePDF(News $news): string
    {
        $title = substr($this->cleanText($news->getTitre()), 0, 100) ?: 'Article';
        $content = substr($this->cleanText($news->getContenu()), 0, 3000) ?: 'No content';
        $category = substr($this->cleanText($news->getCategorie()), 0, 50) ?: 'General';
        $date = $news->getDatePublication() ? $news->getDatePublication()->format('M d, Y') : date('M d, Y');

        // Build PDF objects
        $objNum = 1;
        $objects = [];
        
        // Catalog
        $objects[$objNum++] = "1 0 obj\n<</Type/Catalog/Pages 2 0 R>>\nendobj\n";
        
        // Pages
        $objects[$objNum++] = "2 0 obj\n<</Type/Pages/Kids[3 0 R]/Count 1>>\nendobj\n";
        
        // Page
        $pageResources = "<</Font<</F1 5 0 R/F2 6 0 R>>";
        $pageResources .= ">>";
        
        $objects[$objNum++] = "3 0 obj\n<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources" . $pageResources . ">>\nendobj\n";
        
        // Content stream
        $stream = $this->buildContent($title, $category, $date, $content);
        $objects[$objNum++] = "4 0 obj\n<</Length " . strlen($stream) . ">>\nstream\n$stream\nendstream\nendobj\n";
        
        // Fonts
        $objects[$objNum++] = "5 0 obj\n<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>\nendobj\n";
        $objects[$objNum++] = "6 0 obj\n<</Type/Font/Subtype/Type1/BaseFont/Helvetica-Bold>>\nendobj\n";
        
        // Build complete PDF
        $pdf = "%PDF-1.4\n";
        
        $offsets = [];
        foreach ($objects as $idx => $obj) {
            $offsets[$idx] = strlen($pdf);
            $pdf .= $obj;
        }
        
        // XRef
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        
        $pdf .= "trailer\n<</Size " . (count($objects) + 1) . "/Root 1 0 R>>\n";
        $pdf .= "startxref\n$xrefPos\n%%EOF";
        
        return $pdf;
    }

    /* removed unused loadAndEmbedImage */

    /**
     * Build PDF content stream with enhanced styling and colors
     */
    private function buildContent(string $title, string $category, string $date, string $content): string
    {
        $stream = "";

        // Set colors and fonts
        $stream .= "q\n"; // Save graphics state
        
        // Header background color (dark blue/gold)
        $stream .= "0.08 0.08 0.1 rg\n"; // Dark background
        $stream .= "0 760 612 32 re\n";
        $stream .= "f\n";
        
        $stream .= "Q\n"; // Restore graphics state

        // Header text (white on dark background)
        $stream .= "BT\n";
        $stream .= "1 1 1 rg\n"; // White text
        $stream .= "/F2 14 Tf\n"; // Bold font
        $stream .= "50 766 Td\n";
        $stream .= "(CarthageGG News) Tj\n";
        $stream .= "ET\n";

        // Title
        $stream .= "BT\n";
        $stream .= "0 0 0 rg\n"; // Black text
        $stream .= "/F2 18 Tf\n";
        $stream .= "50 730 Td\n";
        $stream .= "(" . $this->pdfEscape($title) . ") Tj\n";
        $stream .= "ET\n";

        // Metadata line
        $stream .= "BT\n";
        $stream .= "0.4 0.4 0.4 rg\n"; // Gray text
        $stream .= "/F1 9 Tf\n";
        $stream .= "50 716 Td\n";
        $stream .= "(" . $this->pdfEscape($category) . " • " . $date . ") Tj\n";
        $stream .= "ET\n";

        // Separator line
        $stream .= "q\n";
        $stream .= "0.8 0.8 0.8 RG\n"; // Light gray line
        $stream .= "1 w\n";
        $stream .= "50 710 m\n";
        $stream .= "562 710 l\n";
        $stream .= "S\n";
        $stream .= "Q\n";

        // Content
        $stream .= "BT\n";
        $stream .= "0 0 0 rg\n"; // Black text
        $stream .= "/F1 10 Tf\n";
        $stream .= "50 690 Td\n";
        $stream .= "12 TL\n"; // Line leading

        $lines = $this->wrapText($content, 75);
        foreach ($lines as $line) {
            $stream .= "(" . $this->pdfEscape($line) . ") Tj\n";
            $stream .= "T*\n";
        }

        $stream .= "ET\n";

        // Footer
        $stream .= "q\n";
        $stream .= "0.95 0.95 0.95 rg\n";
        $stream .= "0 0 612 20 re\n";
        $stream .= "f\n";
        $stream .= "Q\n";

        $stream .= "BT\n";
        $stream .= "0.5 0.5 0.5 rg\n"; // Gray text
        $stream .= "/F1 8 Tf\n";
        $stream .= "50 8 Td\n";
        $stream .= "(CarthageGG News Platform • Generated: " . date('M d, Y H:i') . " • " . basename($this->projectDir) . ") Tj\n";
        $stream .= "ET\n";

        return $stream;
    }

    private function pdfEscape(string $str): string
    {
        $str = strip_tags($str);
        $str = html_entity_decode($str);
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('(', '\\(', $str);
        $str = str_replace(')', '\\)', $str);
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str) ?? '';
        $str = preg_replace('/[^\x20-\x7E]/', '', $str) ?? '';
        return $str;
    }

    private function cleanText(?string $text): string
    {
        if (!$text) return '';
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $cleaned = preg_replace('/\s+/', ' ', $text) ?? '';
        return trim($cleaned);
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text, int $width = 75): array
    {
        $lines = [];
        $words = explode(' ', $text);
        $line = '';
        
        foreach ($words as $word) {
            if (strlen($line) + strlen($word) + 1 > $width) {
                if ($line) $lines[] = $line;
                $line = $word;
            } else {
                $line .= ($line ? ' ' : '') . $word;
            }
        }
        
        if ($line) $lines[] = $line;
        return $lines;
    }

    private function sanitizeFilename(string $title): string
    {
        $name = strtolower($title);
        $name = preg_replace('/[^a-z0-9_-]/', '_', $name) ?? '';
        $name = preg_replace('/_+/', '_', $name) ?? '';
        $name = trim($name, '_');
        return substr($name, 0, 50) ?: 'article';
    }
}
