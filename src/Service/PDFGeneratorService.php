<?php

namespace App\Service;

use App\Entity\News;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Psr\Log\LoggerInterface;

/**
 * PDFGeneratorService
 * 
 * Generates professional PDF files for news articles using pure PHP.
 * Creates well-formatted, downloadable PDFs with title, metadata, and full content.
 * No external dependencies required - uses native PDF format.
 */
class PDFGeneratorService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Generate PDF for a news article
     * 
     * @param News $news The news article to convert to PDF
     * @return StreamedResponse HTTP response with PDF file download
     */
    public function generateNewsPDF(News $news): StreamedResponse
    {
        try {
            // Create PDF content for the news article
            $pdfContent = $this->createPDF($news);

            // Create a streamed response to download the PDF
            $response = new StreamedResponse(function () use ($pdfContent) {
                echo $pdfContent;
            });

            // Set headers for PDF download
            $filename = $this->sanitizeFilename($news->getTitre() ?? 'News Article') . '.pdf';
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Content-Length', (string) strlen($pdfContent));
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            $this->logger->info('PDF generated successfully', ['filename' => $filename, 'news_id' => $news->getNewsId()]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('PDF generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create professional PDF structure using native PDF format
     * 
     * @param News $news The news article data
     * @return string Raw PDF content
     */
    private function createPDF(News $news): string
    {
        // Prepare content
        $title = $this->cleanText($news->getTitre()) ?: 'News Article';
        $content = $this->cleanText($news->getContenu()) ?: 'No content available';
        $category = $this->cleanText($news->getCategorie()) ?: 'General';
        $date = $news->getDatePublication() ? $news->getDatePublication()->format('F d, Y - H:i') : 'N/A';

        // Build simple, reliable PDF
        $objects = [];
        
        // Object 1: Catalog
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        // Object 2: Pages
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        
        // Build page content stream
        $pageStream = $this->buildSimplePageStream($title, $category, $date, $content);
        
        // Object 4: Stream
        $objects[4] = "4 0 obj\n<< /Length " . strlen($pageStream) . " >>\nstream\n" . $pageStream . "endstream\nendobj\n";
        
        // Object 3: Page
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        
        // Object 5: Font
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        // Build PDF with xref
        $pdf = "%PDF-1.4\n";
        $pdf .= "%âãÏÓ\n";
        
        $offset = strlen($pdf);
        $xrefOffsets = [];
        
        foreach ($objects as $objNum => $objContent) {
            $xrefOffsets[$objNum] = $offset;
            $pdf .= $objContent;
            $offset = strlen($pdf);
        }
        
        // Xref table
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        
        foreach ($objects as $objNum => $objContent) {
            $pdf .= sprintf("%010d 00000 n \n", $xrefOffsets[$objNum]);
        }
        
        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= $xrefOffset . "\n";
        $pdf .= "%%EOF\n";
        
        return $pdf;
    }

    /**
     * Build simple page stream with guaranteed visible content
     */
    private function buildSimplePageStream(string $title, string $category, string $date, string $content): string
    {
        $stream = "";
        
        // Set text rendering mode and font
        $stream .= "BT\n";
        $stream .= "/F1 12 Tf\n";
        $stream .= "50 750 Td\n";
        
        // Title
        $stream .= "(" . $this->escapePDFText($title) . ") Tj\n";
        $stream .= "0 -25 Td\n";
        
        // Category and Date
        $stream .= "/F1 10 Tf\n";
        $stream .= "(" . $this->escapePDFText("Category: " . $category) . ") Tj\n";
        $stream .= "0 -15 Td\n";
        $stream .= "(" . $this->escapePDFText("Date: " . $date) . ") Tj\n";
        $stream .= "0 -25 Td\n";
        
        // Horizontal line
        $stream .= "ET\n";
        $stream .= "q\n";
        $stream .= "0.5 w\n";
        $stream .= "50 700 m\n";
        $stream .= "545 700 l\n";
        $stream .= "S\n";
        $stream .= "Q\n";
        
        // Content
        $stream .= "BT\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "50 680 Td\n";
        
        // Wrap and add content lines
        $lines = $this->wrapText($content, 80);
        $yPos = 680;
        $maxLines = 50;
        
        foreach ($lines as $i => $line) {
            if ($i >= $maxLines) break;
            
            $stream .= "(" . $this->escapePDFText($line) . ") Tj\n";
            $stream .= "0 -12 Td\n";
        }
        
        $stream .= "ET\n";
        
        // Footer
        $stream .= "BT\n";
        $stream .= "/F1 8 Tf\n";
        $stream .= "50 20 Td\n";
        $stream .= "(Generated from CarthageGG News Platform) Tj\n";
        $stream .= "ET\n";
        
        return $stream;
    }

    /**
     * Build the page content with professional formatting
     */
    /* removed deprecated buildPageContent */

    /**
     * Escape special characters for PDF text
     */
    private function escapePDFText(string $text): string
    {
        // Remove control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? '';
        // Escape special PDF characters
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        // Keep only printable ASCII
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
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

        // Remove HTML tags
        $text = strip_tags($text);
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        // Remove extra whitespace
        $cleaned = preg_replace('/\s+/', ' ', $text) ?? '';
        $text = trim($cleaned);

        return $text;
    }

    /**
     * Wrap text to fit PDF width
     */
    /**
     * @return list<string>
     */
    private function wrapText(string $text, int $width = 80): array
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
     * Sanitize filename for safe download
     */
    private function sanitizeFilename(string $title): string
    {
        $filename = strtolower($title);
        $filename = preg_replace('/[^a-z0-9]+/', '_', $filename) ?? '';
        $filename = trim($filename, '_');
        $filename = substr($filename, 0, 50);

        return $filename ?: 'news_article';
    }
}
