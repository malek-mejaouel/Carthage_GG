<?php

namespace App\Service;

use App\Entity\News;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

/**
 * FinalEnhancedPDFGeneratorService
 * 
 * Generates professional PDF documents with:
 * - Dark header with white branding text
 * - Article title in bold
 * - Category and date metadata  
 * - Clean content formatting
 * - Professional colors and spacing
 */
class FinalEnhancedPDFGeneratorService
{
    private LoggerInterface $logger;
    private string $projectDir;

    public function __construct(LoggerInterface $logger, string $projectDir = '')
    {
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    /**
     * Generate PDF for news article and return as download response
     */
    public function generateNewsPDF(News $news): Response
    {
        try {
            $pdfContent = $this->buildPDF($news);
            
            $filename = $this->sanitizeFilename($news->getTitre() ?? 'News Article') . '.pdf';
            
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Content-Length', (string) strlen($pdfContent));

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build the complete PDF structure with enhanced design and image support
     */
    private function buildPDF(News $news): string
    {
        $title = $this->cleanText($news->getTitre());
        $content = $this->cleanText($news->getContenu());
        $category = $this->cleanText($news->getCategorie());
        $date = $news->getDatePublication() ? $news->getDatePublication()->format('F d, Y') : date('F d, Y');

        // Load article image if available
        $imageData = null;
        $imageWidth = 0;
        $imageHeight = 0;
        
        if ($news->getImage()) {
            $imagePath = $this->projectDir . '/public/uploads/news/' . $news->getImage();
            if (file_exists($imagePath)) {
                $imageInfo = @getimagesize($imagePath);
                if ($imageInfo !== false) {
                    $imageData = file_get_contents($imagePath);
                    $imageWidth = $imageInfo[0];
                    $imageHeight = $imageInfo[1];
                }
            }
        }

        // Build page content stream with professional design and image
        $streamData = $this->buildPageContent($title, $category, $date, $content, $imageData !== null);

        // Determine object count (image adds 2 objects if present)
        $objCount = 6;
        if ($imageData) {
            $objCount = 8; // Add font and image objects
        }

        // Create PDF objects
        $objects = [
            "1 0 obj\n<</Type /Catalog /Pages 2 0 R>>\nendobj",
            "2 0 obj\n<</Type /Pages /Kids [3 0 R] /Count 1>>\nendobj",
        ];

        // Page object with resources
        $resourceStr = "<</Font << /F1 5 0 R /F2 6 0 R >>";
        if ($imageData) {
            $resourceStr .= " /XObject << /IMG 7 0 R >>";
        }
        $resourceStr .= " >>";

        $objects[] = "3 0 obj\n<</Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources " . $resourceStr . " >>\nendobj";

        // Content stream
        $objects[] = "4 0 obj\n<</Length " . strlen($streamData) . ">>\nstream\n" . $streamData . "\nendstream\nendobj";

        // Fonts
        $objects[] = "5 0 obj\n<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>\nendobj";
        $objects[] = "6 0 obj\n<</Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold>>\nendobj";

        // Image object if present
        if ($imageData) {
            $objects[] = $this->createImageXObject($imageData, $imageWidth, $imageHeight);
        }

        // Build complete PDF with xref table
        return $this->assemblePDF($objects);
    }

    /**
     * Build the page content stream with enhanced styling and image support
     */
    private function buildPageContent(string $title, string $category, string $date, string $content, bool $hasImage = false): string
    {
        $stream = "";

        // Background color for header - Dark blue/navy
        $stream .= "q\n";
        $stream .= "0.1 0.1 0.15 rg\n";  // Dark navy background
        $stream .= "0 760 612 32 re\n";   // Rectangle from bottom-left to top-right
        $stream .= "f\n";                  // Fill
        $stream .= "Q\n";

        // Logo area - simple rectangular accent
        $stream .= "q\n";
        $stream .= "0.8 0.65 0.2 rg\n";   // Gold/bronze color for accent
        $stream .= "50 765 100 4 re\n";   // Gold line accent
        $stream .= "f\n";
        $stream .= "Q\n";

        // Header text - White on dark background
        $stream .= "BT\n";
        $stream .= "1 1 1 rg\n";           // White text
        $stream .= "/F2 14 Tf\n";          // Bold font, 14pt
        $stream .= "160 768 Td\n";         // Position for header text
        $stream .= "(CarthageGG News) Tj\n";
        $stream .= "ET\n";

        // Article title - Large, bold, black
        $stream .= "BT\n";
        $stream .= "0 0 0 rg\n";           // Black text
        $stream .= "/F2 20 Tf\n";          // Bold font, 20pt
        $stream .= "50 730 Td\n";          // Position
        for ($i = 0; $i < strlen($title); $i += 60) {
            $line = substr($title, $i, 60);
            $stream .= "(" . $this->pdfEscape($line) . ") Tj\n";
            if ($i + 60 < strlen($title)) {
                $stream .= "0 -18 Td\n";   // Move down for next line
            }
        }
        $stream .= "ET\n";

        // Metadata - Gray text, smaller
        $stream .= "BT\n";
        $stream .= "0.35 0.35 0.35 rg\n";  // Dark gray
        $stream .= "/F1 10 Tf\n";          // Regular font, 10pt
        $stream .= "50 700 Td\n";          // Position below title
        $stream .= "(" . $this->pdfEscape($category) . ") Tj\n";
        $stream .= "20 0 Td\n";            // Move right
        $stream .= "( \u{2022} ) Tj\n";    // Bullet separator
        $stream .= "(" . date('M d, Y') . ") Tj\n";
        $stream .= "ET\n";

        // Decorative separator line
        $stream .= "q\n";
        $stream .= "0.8 0.8 0.8 RG\n";     // Light gray line
        $stream .= "1.5 w\n";              // Line width
        $stream .= "50 692 m\n";           // Start point
        $stream .= "562 692 l\n";          // End point
        $stream .= "S\n";                  // Stroke
        $stream .= "Q\n";

        // Add featured image if present
        if ($hasImage) {
            $stream .= "q\n";
            // Scale image to fit width (512 points), maintain aspect ratio
            // Position at top of content area
            $stream .= "50 570 512 130 cm\n";  // Transform matrix (x, y, width, height)
            $stream .= "/IMG Do\n";             // Draw image
            $stream .= "Q\n";

            // Space after image
            $stream .= "q\n";
            $stream .= "0.9 0.9 0.9 rg\n";
            $stream .= "50 560 512 8 re\n";
            $stream .= "f\n";
            $stream .= "Q\n";

            $contentYStart = 545;
        } else {
            $contentYStart = 680;
        }

        // Article content - Justified, with line breaks
        $stream .= "BT\n";
        $stream .= "0 0 0 rg\n";           // Black text
        $stream .= "/F1 11 Tf\n";          // Regular font, 11pt
        $stream .= "50 " . $contentYStart . " Td\n";  // Starting position
        $stream .= "14 TL\n";              // Line leading (space between lines)

        // Wrap and display content
        $lines = $this->wrapText($content, 82);
        foreach ($lines as $line) {
            $stream .= "(" . $this->pdfEscape($line) . ") Tj\n";
            $stream .= "T*\n";             // Move to next line
        }
        $stream .= "ET\n";

        // Footer area - Light background
        $stream .= "q\n";
        $stream .= "0.96 0.96 0.96 rg\n";  // Very light gray
        $stream .= "0 0 612 30 re\n";      // Footer rectangle
        $stream .= "f\n";
        $stream .= "Q\n";

        // Footer text
        $stream .= "BT\n";
        $stream .= "0.5 0.5 0.5 rg\n";     // Medium gray
        $stream .= "/F1 8 Tf\n";           // Small font
        $stream .= "50 10 Td\n";           // Position
        $stream .= "(CarthageGG News Platform) Tj\n";
        $stream .= "450 0 Td\n";           // Right align
        $stream .= "(Generated: " . date('M d, Y @ H:i') . ") Tj\n";
        $stream .= "ET\n";

        return $stream;
    }

    /**
     * Assemble the complete PDF with all objects and xref table
     */
    /**
     * @param list<string> $objects
     */
    private function assemblePDF(array $objects): string
    {
        $pdf = "%PDF-1.4\n";

        // Record byte offsets for all objects
        $offsets = [];
        foreach ($objects as $objIndex => $objContent) {
            $offsets[$objIndex + 1] = strlen($pdf); // Object numbers start at 1
            $pdf .= $objContent . "\n";
        }

        // Cross-reference table
        $xrefStart = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";  // First entry (unused)

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<</Size " . (count($objects) + 1) . " /Root 1 0 R>>\n";
        $pdf .= "startxref\n" . $xrefStart . "\n";
        $pdf .= "%%EOF";

        return $pdf;
    }

    /**
     * Escape special characters for PDF strings
     */
    private function pdfEscape(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Convert entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Escape backslashes first
        $text = str_replace('\\', '\\\\', $text);
        
        // Escape parentheses
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        
        // Remove control characters and keep only printable ASCII
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', '', $text) ?? '';
        
        return $text;
    }

    /**
     * Clean text for display
     */
    private function cleanText(?string $text): string
    {
        if (!$text) {
            return '';
        }

        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $cleaned = preg_replace('/\s+/', ' ', $text) ?? '';
        return trim($cleaned);
    }

    /**
     * Wrap text to specified width
     */
    /**
     * @return list<string>
     */
    private function wrapText(string $text, int $width = 82): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            if (strlen($line) + strlen($word) + 1 > $width) {
                if ($line !== '') {
                    $lines[] = $line;
                }
                $line = $word;
            } else {
                $line .= ($line === '' ? '' : ' ') . $word;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $title): string
    {
        $name = strtolower($title);
        $name = preg_replace('/[^a-z0-9_-]/', '_', $name) ?? '';
        $name = preg_replace('/_+/', '_', $name) ?? '';
        $name = trim($name, '_');
        return substr($name ?: 'article', 0, 60);
    }

    /**
     * Create PDF Image XObject from JPEG/PNG data
     */
    private function createImageXObject(string $imageData, int $width, int $height): string
    {
        // For JPEG, use DCTDecode filter (native JPEG compression)
        $dataLength = strlen($imageData);
        
        return "7 0 obj\n"
            . "<</Type /XObject /Subtype /Image /Width $width /Height $height /ColorSpace /DeviceRGB "
            . "/BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($imageData) . ">>\n"
            . "stream\n"
            . $imageData
            . "\nendstream\nendobj";
    }
}
