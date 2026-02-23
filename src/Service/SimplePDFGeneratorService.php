<?php

namespace App\Service;

use App\Entity\News;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Psr\Log\LoggerInterface;

/**
 * SimplePDFGeneratorService
 * 
 * Ultra-simplified PDF generator using only basic PDF 1.0 features.
 * Creates completely valid PDFs without complex stream handling.
 */
class SimplePDFGeneratorService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function generateNewsPDF(News $news): StreamedResponse
    {
        try {
            $pdfContent = $this->createBasicPDF($news);
            
            $filename = $this->sanitizeFilename($news->getTitre()) . '.pdf';
            
            $response = new StreamedResponse(function () use ($pdfContent) {
                echo $pdfContent;
            });

            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Content-Length', strlen($pdfContent));
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

            $this->logger->info('PDF generated', ['filename' => $filename, 'size' => strlen($pdfContent)]);
            
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('PDF generation error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create a completely basic PDF using minimal PDF 1.0 spec
     * This approach avoids all complexities with streams and graphics states
     */
    private function createBasicPDF(News $news): string
    {
        $title = $this->cleanText($news->getTitre()) ?: 'News Article';
        $content = $this->cleanText($news->getContenu()) ?: 'No content available';
        $category = $this->cleanText($news->getCategorie()) ?: 'General';
        $date = $news->getDatePublication() ? $news->getDatePublication()->format('F d, Y H:i') : date('F d, Y H:i');

        // Build the text content with line breaks
        $textLines = [];
        $textLines[] = $title;
        $textLines[] = '';
        $textLines[] = 'Category: ' . $category;
        $textLines[] = 'Published: ' . $date;
        $textLines[] = str_repeat('-', 50);
        $textLines[] = '';
        
        // Wrap content
        $contentLines = $this->wrapText($content, 70);
        $textLines = array_merge($textLines, $contentLines);

        // Create PDF objects
        $pdfHeader = "%PDF-1.0\n";
        
        // Object 1: Catalog
        $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        // Object 2: Pages
        $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        
        // Object 3: Page
        $obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        
        // Build content stream - simple text positioning
        $contentStream = "BT\n";
        $contentStream .= "/F1 12 Tf\n";
        $contentStream .= "50 740 Td\n";
        
        foreach ($textLines as $line) {
            // Show text
            $contentStream .= "(" . $this->escapePDFString($line) . ") Tj\n";
            // Move to next line
            $contentStream .= "0 -14 Td\n";
        }
        
        $contentStream .= "ET\n";
        
        // Object 4: Content stream
        $obj4_content = $contentStream;
        $obj4 = "4 0 obj\n<< /Length " . strlen($obj4_content) . " >>\nstream\n" . $obj4_content . "endstream\nendobj\n";
        
        // Object 5: Font (Helvetica)
        $obj5 = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        // Build PDF with proper offset tracking
        $pdf = $pdfHeader;
        
        // Calculate and store offsets
        $offsets = [];
        
        // Offset 1
        $offsets[1] = strlen($pdf);
        $pdf .= $obj1;
        
        // Offset 2
        $offsets[2] = strlen($pdf);
        $pdf .= $obj2;
        
        // Offset 3
        $offsets[3] = strlen($pdf);
        $pdf .= $obj3;
        
        // Offset 4
        $offsets[4] = strlen($pdf);
        $pdf .= $obj4;
        
        // Offset 5
        $offsets[5] = strlen($pdf);
        $pdf .= $obj5;
        
        // Build xref table
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 6\n";
        $pdf .= "0000000000 65535 f \n";
        
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        
        // Add trailer
        $pdf .= "trailer\n";
        $pdf .= "<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= $xrefOffset . "\n";
        $pdf .= "%%EOF\n";
        
        return $pdf;
    }

    /**
     * Escape string for PDF
     */
    private function escapePDFString(string $text): string
    {
        // Remove problematic characters
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        
        // Escape backslash and parentheses
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('(', '\\(', $text);
        $text = str_replace(')', '\\)', $text);
        
        // Remove control characters but keep newlines
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Keep only printable ASCII
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        
        return $text;
    }

    /**
     * Clean and normalize text
     */
    private function cleanText(?string $text): string
    {
        if (!$text) return '';
        
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        return $text;
    }

    /**
     * Wrap text to specified width
     */
    private function wrapText(string $text, int $width = 70): array
    {
        $lines = [];
        $words = explode(' ', $text);
        $currentLine = '';

        foreach ($words as $word) {
            if (strlen($currentLine) + strlen($word) + 1 > $width) {
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            } else {
                $currentLine .= ($currentLine ? ' ' : '') . $word;
            }
        }

        if ($currentLine) {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $title): string
    {
        $filename = strtolower($title);
        $filename = preg_replace('/[^a-z0-9_-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');
        $filename = substr($filename, 0, 50);

        return $filename ?: 'news_article';
    }
}
