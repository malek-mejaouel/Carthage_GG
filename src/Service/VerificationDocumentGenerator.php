<?php

namespace App\Service;

use App\Dto\GenerationResult;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VerificationDocumentGenerator
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function generateTemplate(): GenerationResult
    {
        $projectDirVal = $this->params->get('kernel.project_dir');
        if (!is_string($projectDirVal)) {
            return new GenerationResult(false, null, 'Invalid project dir');
        }
        $projectDir = $projectDirVal;
        $validationDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'validation';
        $verifyDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'verification';
        if (!is_dir($verifyDir)) {
            @mkdir($verifyDir, 0775, true);
        }
        $logoPath = $validationDir . DIRECTORY_SEPARATOR . 'logo.png';
        $signPath = $validationDir . DIRECTORY_SEPARATOR . 'signature.png';
        if (!is_file($logoPath) || !is_file($signPath)) {
            return new GenerationResult(false, null, 'Missing validation assets');
        }
        if (!function_exists('imagecreatetruecolor')) {
            return new GenerationResult(false, null, 'GD extension not available');
        }
        $width = 1200;
        $height = 800;
        $im = imagecreatetruecolor(1200, 800);
        $white = (int) imagecolorallocate($im, 255, 255, 255);
        $black = (int) imagecolorallocate($im, 20, 24, 31);
        $gold = (int) imagecolorallocate($im, 212, 175, 55);
        imagefilledrectangle($im, 0, 0, $width, $height, $white);
        $borderColor = (int) imagecolorallocate($im, 230, 230, 230);
        imagesetthickness($im, 4);
        imagerectangle($im, 10, 10, $width - 10, $height - 10, $borderColor);
        imagesetthickness($im, 1);
        $title = 'Validation Certif';
        $this->drawText($im, 32, $gold, (int)($width / 2), 80, $title, true);
        $this->drawText($im, 16, $black, 100, 180, 'first_name: ______________________________');
        $this->drawText($im, 16, $black, 100, 230, 'last_name: ______________________________');
        $this->drawText($im, 16, $black, 100, 280, 'Role: ______________________________');
        $this->drawText($im, 14, $black, 100, 340, 'Brand: CarthageGG');
        $logo = @imagecreatefrompng($logoPath);
        $sign = @imagecreatefrompng($signPath);
        if ($logo) {
            $this->placeImage($im, $logo, 80, $height - 220, 240, 140);
            imagedestroy($logo);
        }
        if ($sign) {
            $this->placeImage($im, $sign, $width - 360, $height - 240, 280, 160);
            imagedestroy($sign);
        }
        $outPath = $verifyDir . DIRECTORY_SEPARATOR . 'certificate_template.png';
        if (is_file($outPath)) {
            $outPath = $verifyDir . DIRECTORY_SEPARATOR . 'certificate_template_' . date('Ymd_His') . '.png';
        }
        imagepng($im, $outPath);
        imagedestroy($im);
        return new GenerationResult(true, $outPath);
    }

    public function generateTestDocument(): GenerationResult
    {
        $projectDirVal = $this->params->get('kernel.project_dir');
        if (!is_string($projectDirVal)) {
            return new GenerationResult(false, null, 'Invalid project dir');
        }
        $projectDir = $projectDirVal;
        $validationDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'validation';
        $verifyDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'verification';
        if (!is_dir($verifyDir)) {
            @mkdir($verifyDir, 0775, true);
        }
        $logoPath = $validationDir . DIRECTORY_SEPARATOR . 'logo.png';
        $signPath = $validationDir . DIRECTORY_SEPARATOR . 'signature.png';
        if (!is_file($logoPath) || !is_file($signPath)) {
            return new GenerationResult(false, null, 'Missing validation assets');
        }
        if (!function_exists('imagecreatetruecolor')) {
            return new GenerationResult(false, null, 'GD extension not available');
        }
        $width = 1200;
        $height = 800;
        $im = imagecreatetruecolor(1200, 800);
        $white = (int) imagecolorallocate($im, 255, 255, 255);
        $black = (int) imagecolorallocate($im, 20, 24, 31);
        $gold = (int) imagecolorallocate($im, 212, 175, 55);
        imagefilledrectangle($im, 0, 0, $width, $height, $white);
        $borderColor = (int) imagecolorallocate($im, 230, 230, 230);
        imagesetthickness($im, 4);
        imagerectangle($im, 10, 10, $width - 10, $height - 10, $borderColor);
        imagesetthickness($im, 1);
        $title = 'Validation Certif';
        $this->drawText($im, 32, $gold, (int)($width / 2), 80, $title, true);
        $this->drawText($im, 16, $black, 100, 180, 'first_name: Malek');
        $this->drawText($im, 16, $black, 100, 230, 'last_name: test');
        $this->drawText($im, 16, $black, 100, 280, 'Role: player');
        $this->drawText($im, 14, $black, 100, 340, 'Brand: CarthageGG');
        $logo = @imagecreatefrompng($logoPath);
        $sign = @imagecreatefrompng($signPath);
        if ($logo) {
            $this->placeImage($im, $logo, 80, $height - 220, 240, 140);
            imagedestroy($logo);
        }
        if ($sign) {
            $this->placeImage($im, $sign, $width - 360, $height - 240, 280, 160);
            imagedestroy($sign);
        }
        $outPath = $verifyDir . DIRECTORY_SEPARATOR . 'test_malek_test_player.png';
        if (is_file($outPath)) {
            $outPath = $verifyDir . DIRECTORY_SEPARATOR . 'test_malek_test_player_' . date('Ymd_His') . '.png';
        }
        imagepng($im, $outPath);
        imagedestroy($im);
        return new GenerationResult(true, $outPath);
    }

    public function generateCustomDocument(string $firstName, string $lastName, string $role): GenerationResult
    {
        $projectDirVal = $this->params->get('kernel.project_dir');
        if (!is_string($projectDirVal)) {
            return new GenerationResult(false, null, 'Invalid project dir');
        }
        $projectDir = $projectDirVal;
        $validationDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'validation';
        $verifyDir = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'verification';
        if (!is_dir($verifyDir)) {
            @mkdir($verifyDir, 0775, true);
        }
        $logoPath = $validationDir . DIRECTORY_SEPARATOR . 'logo.png';
        $signPath = $validationDir . DIRECTORY_SEPARATOR . 'signature.png';
        if (!is_file($logoPath) || !is_file($signPath)) {
            return new GenerationResult(false, null, 'Missing validation assets');
        }
        if (!function_exists('imagecreatetruecolor')) {
            return new GenerationResult(false, null, 'GD extension not available');
        }
        $width = 1200;
        $height = 800;
        $im = imagecreatetruecolor(1200, 800);
        $white = (int) imagecolorallocate($im, 255, 255, 255);
        $black = (int) imagecolorallocate($im, 20, 24, 31);
        $gold = (int) imagecolorallocate($im, 212, 175, 55);
        imagefilledrectangle($im, 0, 0, $width, $height, $white);
        $borderColor = (int) imagecolorallocate($im, 230, 230, 230);
        imagesetthickness($im, 4);
        imagerectangle($im, 10, 10, $width - 10, $height - 10, $borderColor);
        imagesetthickness($im, 1);
        $title = 'Validation Certif';
        $this->drawText($im, 32, $gold, (int)($width / 2), 80, $title, true);
        $this->drawText($im, 16, $black, 100, 180, 'first_name: ' . $firstName);
        $this->drawText($im, 16, $black, 100, 230, 'last_name: ' . $lastName);
        $this->drawText($im, 16, $black, 100, 280, 'Role: ' . $role);
        $this->drawText($im, 14, $black, 100, 340, 'Brand: CarthageGG');
        $logo = @imagecreatefrompng($logoPath);
        $sign = @imagecreatefrompng($signPath);
        if ($logo) {
            $this->placeImage($im, $logo, 80, $height - 220, 240, 140);
            imagedestroy($logo);
        }
        if ($sign) {
            $this->placeImage($im, $sign, $width - 360, $height - 240, 280, 160);
            imagedestroy($sign);
        }
        $fname = 'test_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($firstName)) . '_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($lastName)) . '_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($role)) . '.png';
        $outPath = $verifyDir . DIRECTORY_SEPARATOR . $fname;
        if (is_file($outPath)) {
            $outPath = $verifyDir . DIRECTORY_SEPARATOR . pathinfo($fname, PATHINFO_FILENAME) . '_' . date('Ymd_His') . '.png';
        }
        imagepng($im, $outPath);
        imagedestroy($im);
        return new GenerationResult(true, $outPath);
    }

    private function drawText(\GdImage $im, int $size, int $color, int $x, int $y, string $text, bool $center = false): void
    {
        if ($center) {
            $width = imagefontwidth(5) * strlen($text);
            $x = max(0, $x - (int) ($width / 2));
        }
        imagestring($im, 5, $x, $y, $text, $color);
    }

    private function placeImage(\GdImage $dst, \GdImage $src, int $x, int $y, int $w, int $h): void
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $tw = max(1, $w);
        $th = max(1, $h);
        $tmp = imagecreatetruecolor($tw, $th);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        imagecopyresampled($tmp, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh);
        imagecopy($dst, $tmp, $x, $y, 0, 0, $tw, $th);
        imagedestroy($tmp);
    }
}
