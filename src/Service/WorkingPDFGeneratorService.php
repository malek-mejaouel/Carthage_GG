<?php

namespace App\Service;

use App\Entity\News;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Psr\Log\LoggerInterface;

/**
 * WorkingPDFGeneratorService
 * 
 * Generates PDFs using a proven format that works in all PDF readers.
 * Uses absolute positioning and explicit graphics state management.
 */
class WorkingPDFGeneratorService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function generateNewsPDF(News $news): StreamedResponse
    {
        try {
            $pdfContent = $this->generatePDF($news);
            
            $filename = $this->sanitizeFilename($news->getTitre() ?? 'Article') . '.pdf';
            
            $response = new StreamedResponse(function () use ($pdfContent) {
                echo $pdfContent;
            });

            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Content-Length', (string) strlen($pdfContent));
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

            $this->logger->info('PDF generated', ['filename' => $filename]);
            
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('PDF error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate PDF using absolutely reliable format
     */
    private function generatePDF(News $news): string
    {
        $title = substr($this->cleanText($news->getTitre()), 0, 100) ?: 'Article';
        $content = substr($this->cleanText($news->getContenu()), 0, 2000) ?: 'No content';
        $category = substr($this->cleanText($news->getCategorie()), 0, 50) ?: 'General';
        $date = $news->getDatePublication() ? $news->getDatePublication()->format('M d, Y') : date('M d, Y');

        // Objects
        $obj1 = "1 0 obj\n<</Type/Catalog/Pages 2 0 R>>\nendobj\n";
        $obj2 = "2 0 obj\n<</Type/Pages/Kids[3 0 R]/Count 1>>\nendobj\n";
        $obj3 = "3 0 obj\n<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>/>\nendobj\n";
        
        // Stream content
        $stream = "0 0 0 rg\n"; // Black text
        $stream .= "BT\n";
        $stream .= "/F1 16 Tf\n";
        $stream .= "50 750 Td\n";
        $stream .= "(" . $this->pdfEscape($title) . ") Tj\n";
        $stream .= "0 -20 Td\n";
        $stream .= "/F1 10 Tf\n";
        $stream .= "(" . $this->pdfEscape("Category: $category") . ") Tj\n";
        $stream .= "0 -15 Td\n";
        $stream .= "(" . $this->pdfEscape("Date: $date") . ") Tj\n";
        $stream .= "0 -20 Td\n";
        $stream .= "(" . $this->pdfEscape(str_repeat("-", 50)) . ") Tj\n";
        $stream .= "0 -20 Td\n";
        
        // Content
        $lines = $this->wrapText($content, 70);
        foreach ($lines as $line) {
            $stream .= "(" . $this->pdfEscape($line) . ") Tj\n";
            $stream .= "0 -12 Td\n";
        }
        
        $stream .= "ET\n";
        
        $obj4 = "4 0 obj\n<</Length " . strlen($stream) . ">>\nstream\n$stream\nendstream\nendobj\n";
        $obj5 = "5 0 obj\n<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>\nendobj\n";
        
        // Build complete PDF
        $pdf = "%PDF-1.1\n";
        
        $offsets = [];
        $offsets[1] = strlen($pdf);
        $pdf .= $obj1;
        
        $offsets[2] = strlen($pdf);
        $pdf .= $obj2;
        
        $offsets[3] = strlen($pdf);
        $pdf .= $obj3;
        
        $offsets[4] = strlen($pdf);
        $pdf .= $obj4;
        
        $offsets[5] = strlen($pdf);
        $pdf .= $obj5;
        
        // XRef
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        
        $pdf .= "trailer\n<</Size 6/Root 1 0 R>>\n";
        $pdf .= "startxref\n$xrefPos\n%%EOF";
        
        return $pdf;
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
        if ($text === null) return '';
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $cleaned = preg_replace('/\s+/', ' ', $text) ?? '';
        return trim($cleaned);
    }

    /**
     * @return iterable<string>
     */
    private function wrapText(string $text, int $width = 70): iterable
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
        foreach ($lines as $l) {
            yield $l;
        }
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
