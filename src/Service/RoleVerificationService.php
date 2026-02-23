<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class RoleVerificationService
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->logger = $logger;
    }

    public function verify(User $user, UploadedFile $file, ?string $roleName): array
    {
        if (!$this->isAllowedMime($file) || !$this->isAllowedSize($file)) {
            return ['success' => false, 'code' => 'invalid_format', 'message' => 'Invalid file format or size'];
        }

        $projectDir = (string) $this->params->get('kernel.project_dir');
        $refLogo = $projectDir . '/public/assets/images/validation/logo.png';
        $refSign = $projectDir . '/public/assets/images/validation/signature.png';

        $tmpPath = $file->getRealPath() ?: $file->getPathname();
        if (!$tmpPath || !is_file($tmpPath)) {
            return ['success' => false, 'code' => 'processing_error', 'message' => 'Unable to read uploaded file'];
        }

        $requireBoth = $this->params->has('role_verification_require_both') ? (bool) $this->params->get('role_verification_require_both') : true;
        $logoTrace = $this->matchReference($tmpPath, $refLogo, null);
        $signTrace = $this->matchReference($tmpPath, $refSign, null);
        $logoOk = (bool) ($logoTrace['matched'] ?? false);
        $signOk = (bool) ($signTrace['matched'] ?? false);
        if (!$signOk) {
            if ($this->containsBrand($tmpPath)) {
                $signOk = true;
                $signTrace['matched'] = true;
                $signTrace['brand_text_found'] = true;
            }
        }
        if (($requireBoth && (!$logoOk || !$signOk)) || (!$requireBoth && (!$logoOk && !$signOk))) {
            $this->logger->warning('role_verification.logo_signature_invalid', [
                'user_id' => $user->getId(),
                'trace' => [
                    'threshold' => $logoTrace['threshold'] ?? null,
                    'logo' => $logoTrace,
                    'signature' => $signTrace,
                    'require_both' => $requireBoth,
                ],
            ]);
            return [
                'success' => false,
                'code' => 'logo_signature_invalid',
                'message' => 'Logo/signature invalid',
                'trace' => [
                    'threshold' => $logoTrace['threshold'] ?? null,
                    'logo' => $logoTrace,
                    'signature' => $signTrace,
                    'require_both' => $requireBoth,
                ],
            ];
        }

        $names = $this->extractNames($tmpPath, $file->getClientOriginalName());
        $thName = $this->params->has('role_verification_name_similarity_threshold') ? (float) $this->params->get('role_verification_name_similarity_threshold') : 0.75;
        $uf = $this->normalizeName((string) ($user->getFirstName() ?? ''));
        $ul = $this->normalizeName((string) ($user->getLastName() ?? ''));
        $uu = $this->normalizeName((string) ($user->getUsername() ?? ''));
        $nFirst = $this->normalizeName((string) ($names['first'] ?? ''));
        $nLast = $this->normalizeName((string) ($names['last'] ?? ''));
        $nUsername = $this->normalizeName((string) ($names['username'] ?? ''));
        $nRole = strtoupper(trim((string) ($names['role'] ?? '')));
        if (($nFirst === '' || $nLast === '') && isset($names['text'])) {
            $parsed = $this->parseNamesFromText((string) $names['text']);
            if ($nFirst === '') { $nFirst = $this->normalizeName((string) ($parsed['first'] ?? '')); }
            if ($nLast === '') { $nLast = $this->normalizeName((string) ($parsed['last'] ?? '')); }
            if ($nUsername === '') { $nUsername = $this->normalizeName((string) ($parsed['username'] ?? '')); }
            if ($nRole === '' && isset($parsed['role'])) { $nRole = strtoupper(trim((string) $parsed['role'])); }
        }
        $userFull = trim($this->normalizeName(trim($uf . ' ' . $ul)));
        $certFull = trim($this->normalizeName(trim($nFirst . ' ' . $nLast)));
        $simFirst = $this->nameSimilarity($uf, $nFirst);
        $simLast = $this->nameSimilarity($ul, $nLast);
        $simFull = $this->nameSimilarity($userFull, $certFull);
        $simUser = $this->nameSimilarity($uu, $nUsername !== '' ? $nUsername : $certFull);
        $skipNameCheckIfNoOcr = $this->params->has('role_verification_skip_name_check_if_no_ocr') ? (bool) $this->params->get('role_verification_skip_name_check_if_no_ocr') : false;
        $strictNames = $this->params->has('role_verification_strict_names') ? (bool) $this->params->get('role_verification_strict_names') : true;
        $requireRoleMatch = $this->params->has('role_verification_require_role_match') ? (bool) $this->params->get('role_verification_require_role_match') : true;
        $ocrConfigured = $this->hasOcrConfigured();
        $nameCheckSkipped = false;
        if ($skipNameCheckIfNoOcr && !$ocrConfigured) {
            $nameCheckSkipped = true;
        }
        if (!$nameCheckSkipped && ($uf !== '' || $ul !== '')) {
            if ($strictNames) {
                $ok = ($userFull !== '' && $certFull !== '' && $userFull === $certFull) || (($uf !== '' && $ul !== '' && $nFirst !== '' && $nLast !== '' && $uf === $nFirst && $ul === $nLast));
                if (!$ok && $userFull !== '' && $certFull !== '') {
                    $tu = array_values(array_filter(explode(' ', $userFull), function ($x) { return $x !== ''; }));
                    $tc = array_values(array_filter(explode(' ', $certFull), function ($x) { return $x !== ''; }));
                    sort($tu);
                    sort($tc);
                    if ($tu === $tc) {
                        $ok = true;
                    }
                }
                if (!$ok && $uu !== '') {
                    $ok = ($uu === ($nUsername !== '' ? $nUsername : $certFull));
                }
            } else {
                $ok = ($simFull >= $thName) || ($simFirst >= $thName && $simLast >= $thName) || ($simUser >= $thName);
            }
            if (!$ok) {
                return [
                    'success' => false,
                    'code' => 'name_mismatch',
                    'message' => 'Name mismatch',
                    'trace' => [
                        'name_presence' => [
                            'user' => [
                                'first' => $uf !== '',
                                'last' => $ul !== '',
                                'username' => $uu !== '',
                            ],
                            'document' => [
                                'first' => $nFirst !== '',
                                'last' => $nLast !== '',
                                'username' => $nUsername !== '',
                                'text' => isset($names['text']) && trim((string) $names['text']) !== '',
                            ],
                            'ocr_configured' => $ocrConfigured,
                        ],
                        'strict_names' => $strictNames,
                        'name_similarity' => [
                            'threshold' => $thName,
                            'first' => $simFirst,
                            'last' => $simLast,
                            'full' => $simFull,
                            'username' => $simUser,
                        ],
                    ],
                ];
            }
        } elseif (!$nameCheckSkipped) {
            if ($strictNames) {
                $ok = ($uu !== '' && ($uu === ($nUsername !== '' ? $nUsername : $certFull)));
            } else {
                $ok = ($simUser >= $thName);
            }
            if (!$ok) {
                return [
                    'success' => false,
                    'code' => 'name_mismatch',
                    'message' => 'Name mismatch',
                    'trace' => [
                        'name_presence' => [
                            'user' => [
                                'first' => $uf !== '',
                                'last' => $ul !== '',
                                'username' => $uu !== '',
                            ],
                            'document' => [
                                'first' => $nFirst !== '',
                                'last' => $nLast !== '',
                                'username' => $nUsername !== '',
                                'text' => isset($names['text']) && trim((string) $names['text']) !== '',
                            ],
                            'ocr_configured' => $ocrConfigured,
                        ],
                        'strict_names' => $strictNames,
                        'name_similarity' => [
                            'threshold' => $thName,
                            'first' => $simFirst,
                            'last' => $simLast,
                            'full' => $simFull,
                            'username' => $simUser,
                        ],
                    ],
                ];
            }
        }
        $badge = $roleName ?: $this->defaultBadge($user);
        $docRole = $nRole !== '' ? $nRole : '';
        if ($requireRoleMatch) {
            if ($docRole === '') {
                return [
                    'success' => false,
                    'code' => 'role_missing',
                    'message' => 'Role not found in document',
                    'trace' => [
                        'document_role' => $docRole,
                        'expected_role' => $badge,
                        'ocr_configured' => $ocrConfigured,
                    ],
                ];
            }
            if (strtoupper($badge) !== $docRole) {
                return [
                    'success' => false,
                    'code' => 'role_mismatch',
                    'message' => 'Role mismatch',
                    'trace' => [
                        'document_role' => $docRole,
                        'expected_role' => strtoupper($badge),
                    ],
                ];
            }
        }

        $timestamp = new \DateTimeImmutable();

        $this->logger->info('role_verification.ok', [
            'user_id' => $user->getId(),
            'badge' => $badge,
            'trace' => [
                'threshold' => $logoTrace['threshold'] ?? null,
                'logo' => $logoTrace,
                'signature' => $signTrace,
                'require_both' => $requireBoth,
            ],
        ]);
        return [
            'success' => true,
            'code' => 'ok',
            'message' => 'Verification successful',
            'badge' => $badge,
            'timestamp' => $timestamp,
            'trace' => [
                'threshold' => $logoTrace['threshold'] ?? null,
                'logo' => $logoTrace,
                'signature' => $signTrace,
                'require_both' => $requireBoth,
                'name_similarity' => [
                    'threshold' => $thName,
                    'first' => $simFirst,
                    'last' => $simLast,
                    'full' => $simFull,
                    'username' => $simUser,
                ],
                'name_check_skipped' => $nameCheckSkipped ? ['skipped' => true, 'reason' => 'ocr_not_configured'] : ['skipped' => false],
                'document_role' => $nRole !== '' ? $nRole : '',
            ],
        ];
    }

    private function isAllowedMime(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        return in_array($mime, ['image/png', 'image/jpeg', 'image/jpg'], true);
    }

    private function isAllowedSize(UploadedFile $file): bool
    {
        return $file->getSize() <= 5 * 1024 * 1024;
    }

    private function matchReference(string $uploadedPath, string $refPath, ?string $hint = null): array
    {
        $th = $this->params->has('role_verification_threshold') ? (float) $this->params->get('role_verification_threshold') : 0.28;
        $corrTh = $this->params->has('role_verification_corr_threshold') ? (float) $this->params->get('role_verification_corr_threshold') : 0.78;
        $histTh = $this->params->has('role_verification_hist_threshold') ? (float) $this->params->get('role_verification_hist_threshold') : 0.6;
        $edgeTh = $this->params->has('role_verification_edge_threshold') ? (float) $this->params->get('role_verification_edge_threshold') : 0.65;
        $res = ['matched' => false, 'distance' => null, 'correlation' => null, 'hist_sim' => null, 'edge_corr' => null, 'scale' => null, 'x' => null, 'y' => null, 'scanned' => 0, 'threshold' => $th, 'corr_threshold' => $corrTh, 'hist_threshold' => $histTh, 'edge_threshold' => $edgeTh, 'hint' => $hint ?: '', 'error' => null];
        if (!is_file($refPath)) {
            $res['error'] = 'ref_missing';
            return $res;
        }
        if (!function_exists('imagecreatefromstring')) {
            $res['error'] = 'gd_unavailable';
            return $res;
        }
        $uData = @file_get_contents($uploadedPath);
        $rData = @file_get_contents($refPath);
        if ($uData === false || $rData === false) {
            $res['error'] = 'read_error';
            return $res;
        }
        $uIm = @imagecreatefromstring($uData);
        $rIm = @imagecreatefromstring($rData);
        if (!$uIm || !$rIm) {
            if ($uIm) { imagedestroy($uIm); }
            if ($rIm) { imagedestroy($rIm); }
            $res['error'] = 'decode_error';
            return $res;
        }
        $preCrop = $this->params->has('role_verification_pre_crop') ? (bool) $this->params->get('role_verification_pre_crop') : true;
        if ($preCrop) {
            $cropped = $this->cropToContent($rIm);
            if ($cropped) {
                imagedestroy($rIm);
                $rIm = $cropped;
            }
        }
        $refFp = $this->fingerprintResource($rIm);
        $refFpN = $this->fingerprintResourceNormalized($rIm);
        $refHist = $this->histogramResourceNormalized($rIm);
        $refGrad = $this->gradientFingerprintResourceNormalized($rIm);
        $uw = imagesx($uIm);
        $uh = imagesy($uIm);
        $rw = imagesx($rIm);
        $rh = imagesy($rIm);
        $scales = [0.25, 0.33, 0.5, 0.75, 1.0, 1.25, 1.5, 2.0];
        $max = 2000;
        $count = 0;
        $stepX = max(6, (int) floor($uw / 30));
        $stepY = max(6, (int) floor($uh / 30));
        $best = INF;
        $bestCorr = -INF;
        $bestX = null;
        $bestY = null;
        $bestS = null;
        $bestHist = -INF;
        $bestEdge = -INF;
        if ($hint === 'bottom-left' || $hint === 'bottom-right') {
            $patchW = min((int) floor($uw * 0.35), (int) floor($rw * 2));
            $patchH = min((int) floor($uh * 0.35), (int) floor($rh * 2));
            $px = $hint === 'bottom-left' ? 0 : max(0, $uw - $patchW);
            $py = max(0, $uh - $patchH);
            $cornerFp = $this->fingerprintPatch($uIm, $px, $py, $patchW, $patchH);
            $cornerDist = $this->distance($cornerFp, $refFp);
            $cornerFpN = $this->fingerprintPatchNormalized($uIm, $px, $py, $patchW, $patchH);
            $cornerCorr = $this->correlation($cornerFpN, $refFpN);
            $cornerHist = $this->histogramPatchNormalized($uIm, $px, $py, $patchW, $patchH);
            $cornerHistSim = $this->histogramIntersection($cornerHist, $refHist);
            $cornerGrad = $this->gradientFingerprintPatchNormalized($uIm, $px, $py, $patchW, $patchH);
            $cornerEdgeCorr = $this->correlation($cornerGrad, $refGrad);
            $sCorner = $rw > 0 ? ($patchW / $rw) : 1.0;
            if ($cornerDist < $best) {
                $best = $cornerDist;
                $bestX = $px;
                $bestY = $py;
                $bestS = $sCorner;
            }
            if ($cornerCorr > $bestCorr) {
                $bestCorr = $cornerCorr;
            }
            if ($cornerHistSim > $bestHist) {
                $bestHist = $cornerHistSim;
            }
            if ($cornerEdgeCorr > $bestEdge) {
                $bestEdge = $cornerEdgeCorr;
            }
            if ($cornerDist <= $th * 1.25) {
                $res['matched'] = true;
                $res['distance'] = $cornerDist;
                $res['correlation'] = $cornerCorr;
                $res['hist_sim'] = $cornerHistSim;
                $res['edge_corr'] = $cornerEdgeCorr;
                $res['scale'] = $sCorner;
                $res['x'] = $px;
                $res['y'] = $py;
                imagedestroy($uIm);
                imagedestroy($rIm);
                return $res;
            }
            if ($cornerCorr >= $corrTh) {
                $res['matched'] = true;
                $res['distance'] = $cornerDist;
                $res['correlation'] = $cornerCorr;
                $res['hist_sim'] = $cornerHistSim;
                $res['edge_corr'] = $cornerEdgeCorr;
                $res['scale'] = $sCorner;
                $res['x'] = $px;
                $res['y'] = $py;
                imagedestroy($uIm);
                imagedestroy($rIm);
                return $res;
            }
            if ($cornerHistSim >= $histTh) {
                $res['matched'] = true;
                $res['distance'] = $cornerDist;
                $res['correlation'] = $cornerCorr;
                $res['hist_sim'] = $cornerHistSim;
                $res['edge_corr'] = $cornerEdgeCorr;
                $res['scale'] = $sCorner;
                $res['x'] = $px;
                $res['y'] = $py;
                imagedestroy($uIm);
                imagedestroy($rIm);
                return $res;
            }
            if ($cornerEdgeCorr >= $edgeTh) {
                $res['matched'] = true;
                $res['distance'] = $cornerDist;
                $res['correlation'] = $cornerCorr;
                $res['hist_sim'] = $cornerHistSim;
                $res['edge_corr'] = $cornerEdgeCorr;
                $res['scale'] = $sCorner;
                $res['x'] = $px;
                $res['y'] = $py;
                imagedestroy($uIm);
                imagedestroy($rIm);
                return $res;
            }
        }
        $startX = 0;
        $endX = $uw;
        $startY = 0;
        $endY = $uh;
        if ($hint === 'bottom-left') {
            $endX = (int) floor($uw * 0.6);
            $startY = (int) floor($uh * 0.5);
        } elseif ($hint === 'bottom-right') {
            $startX = (int) floor($uw * 0.4);
            $startY = (int) floor($uh * 0.5);
        }
        foreach ($scales as $s) {
            $winW = max(16, (int) floor($rw * $s));
            $winH = max(16, (int) floor($rh * $s));
            if ($winW > ($endX - $startX) || $winH > ($endY - $startY)) {
                continue;
            }
            for ($y = $startY; $y + $winH <= $endY; $y += $stepY) {
                for ($x = $startX; $x + $winW <= $endX; $x += $stepX) {
                    $fp = $this->fingerprintPatch($uIm, $x, $y, $winW, $winH);
                    $dist = $this->distance($fp, $refFp);
                    $fpN = $this->fingerprintPatchNormalized($uIm, $x, $y, $winW, $winH);
                    $corr = $this->correlation($fpN, $refFpN);
                    $hist = $this->histogramPatchNormalized($uIm, $x, $y, $winW, $winH);
                    $histSim = $this->histogramIntersection($hist, $refHist);
                    $grad = $this->gradientFingerprintPatchNormalized($uIm, $x, $y, $winW, $winH);
                    $edgeCorr = $this->correlation($grad, $refGrad);
                    if ($dist < $best) {
                        $best = $dist;
                        $bestX = $x;
                        $bestY = $y;
                        $bestS = $s;
                    }
                    if ($corr > $bestCorr) {
                        $bestCorr = $corr;
                    }
                    if ($histSim > $bestHist) {
                        $bestHist = $histSim;
                    }
                    if ($edgeCorr > $bestEdge) {
                        $bestEdge = $edgeCorr;
                    }
                    if ($dist <= $th) {
                        $res['matched'] = true;
                        $res['distance'] = $dist;
                        $res['correlation'] = $corr;
                        $res['hist_sim'] = $histSim;
                        $res['edge_corr'] = $edgeCorr;
                        $res['scale'] = $s;
                        $res['x'] = $x;
                        $res['y'] = $y;
                        $res['scanned'] = $count + 1;
                        imagedestroy($uIm);
                        imagedestroy($rIm);
                        return $res;
                    }
                    if ($corr >= $corrTh) {
                        $res['matched'] = true;
                        $res['distance'] = $dist;
                        $res['correlation'] = $corr;
                        $res['hist_sim'] = $histSim;
                        $res['edge_corr'] = $edgeCorr;
                        $res['scale'] = $s;
                        $res['x'] = $x;
                        $res['y'] = $y;
                        $res['scanned'] = $count + 1;
                        imagedestroy($uIm);
                        imagedestroy($rIm);
                        return $res;
                    }
                    if ($histSim >= $histTh) {
                        $res['matched'] = true;
                        $res['distance'] = $dist;
                        $res['correlation'] = $corr;
                        $res['hist_sim'] = $histSim;
                        $res['edge_corr'] = $edgeCorr;
                        $res['scale'] = $s;
                        $res['x'] = $x;
                        $res['y'] = $y;
                        $res['scanned'] = $count + 1;
                        imagedestroy($uIm);
                        imagedestroy($rIm);
                        return $res;
                    }
                    if ($edgeCorr >= $edgeTh) {
                        $res['matched'] = true;
                        $res['distance'] = $dist;
                        $res['correlation'] = $corr;
                        $res['hist_sim'] = $histSim;
                        $res['edge_corr'] = $edgeCorr;
                        $res['scale'] = $s;
                        $res['x'] = $x;
                        $res['y'] = $y;
                        $res['scanned'] = $count + 1;
                        imagedestroy($uIm);
                        imagedestroy($rIm);
                        return $res;
                    }
                    $count++;
                    if ($count >= $max) {
                        break 2;
                    }
                }
            }
        }
        $res['distance'] = is_finite($best) ? $best : null;
        $res['correlation'] = is_finite($bestCorr) ? $bestCorr : null;
        $res['hist_sim'] = is_finite($bestHist) ? $bestHist : null;
        $res['edge_corr'] = is_finite($bestEdge) ? $bestEdge : null;
        $res['scale'] = $bestS;
        $res['x'] = $bestX;
        $res['y'] = $bestY;
        $res['scanned'] = $count;
        imagedestroy($uIm);
        imagedestroy($rIm);
        return $res;
    }

    private function fingerprint(string $path): ?array
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $im = @imagecreatefromstring($data);
        if (!$im) {
            return null;
        }
        $w = 32;
        $h = 32;
        $small = imagecreatetruecolor($w, $h);
        imagecopyresampled($small, $im, 0, 0, 0, 0, $w, $h, imagesx($im), imagesy($im));
        $out = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3.0 / 255.0;
                $out[] = $gray;
            }
        }
        imagedestroy($small);
        imagedestroy($im);
        return $out;
    }

    private function fingerprintResource($im): array
    {
        $w = 32;
        $h = 32;
        $small = imagecreatetruecolor($w, $h);
        imagecopyresampled($small, $im, 0, 0, 0, 0, $w, $h, imagesx($im), imagesy($im));
        $out = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3.0 / 255.0;
                $out[] = $gray;
            }
        }
        imagedestroy($small);
        return $out;
    }

    private function fingerprintResourceNormalized($im): array
    {
        $w = 32;
        $h = 32;
        $small = imagecreatetruecolor($w, $h);
        imagecopyresampled($small, $im, 0, 0, 0, 0, $w, $h, imagesx($im), imagesy($im));
        $out = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3.0 / 255.0;
                $out[] = $gray;
            }
        }
        imagedestroy($small);
        $mean = 0.0;
        foreach ($out as $v) { $mean += $v; }
        $n = max(1, count($out));
        $mean /= $n;
        $std = 0.0;
        foreach ($out as $v) { $std += ($v - $mean) * ($v - $mean); }
        $std = sqrt($std / $n);
        if ($std <= 1e-6) { $std = 1.0; }
        for ($i = 0; $i < $n; $i++) {
            $out[$i] = ($out[$i] - $mean) / $std;
        }
        return $out;
    }

    private function fingerprintPatch($im, int $x, int $y, int $w, int $h): array
    {
        $w32 = 32;
        $h32 = 32;
        $small = imagecreatetruecolor($w32, $h32);
        imagecopyresampled($small, $im, 0, 0, $x, $y, $w32, $h32, $w, $h);
        $out = [];
        for ($yy = 0; $yy < $h32; $yy++) {
            for ($xx = 0; $xx < $w32; $xx++) {
                $rgb = imagecolorat($small, $xx, $yy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3.0 / 255.0;
                $out[] = $gray;
            }
        }
        imagedestroy($small);
        return $out;
    }

    private function fingerprintPatchNormalized($im, int $x, int $y, int $w, int $h): array
    {
        $w32 = 32;
        $h32 = 32;
        $small = imagecreatetruecolor($w32, $h32);
        imagecopyresampled($small, $im, 0, 0, $x, $y, $w32, $h32, $w, $h);
        $out = [];
        for ($yy = 0; $yy < $h32; $yy++) {
            for ($xx = 0; $xx < $w32; $xx++) {
                $rgb = imagecolorat($small, $xx, $yy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3.0 / 255.0;
                $out[] = $gray;
            }
        }
        imagedestroy($small);
        $mean = 0.0;
        foreach ($out as $v) { $mean += $v; }
        $n = max(1, count($out));
        $mean /= $n;
        $std = 0.0;
        foreach ($out as $v) { $std += ($v - $mean) * ($v - $mean); }
        $std = sqrt($std / $n);
        if ($std <= 1e-6) { $std = 1.0; }
        for ($i = 0; $i < $n; $i++) {
            $out[$i] = ($out[$i] - $mean) / $std;
        }
        return $out;
    }
    private function distance(array $a, array $b): float
    {
        $sum = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $d = $a[$i] - $b[$i];
            $sum += $d * $d;
        }
        return sqrt($sum / max(1, $n));
    }

    private function correlation(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) return 0.0;
        $dot = 0.0;
        $sumA = 0.0;
        $sumB = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $sumA += $a[$i] * $a[$i];
            $sumB += $b[$i] * $b[$i];
        }
        $den = sqrt(max(1e-9, $sumA) * max(1e-9, $sumB));
        return $dot / $den;
    }

    private function histogramResourceNormalized($im): array
    {
        return $this->histogramPatchNormalized($im, 0, 0, imagesx($im), imagesy($im));
    }

    private function histogramPatchNormalized($im, int $x, int $y, int $w, int $h): array
    {
        $bins = 8;
        $hist = array_fill(0, $bins * $bins * $bins, 0.0);
        for ($yy = $y; $yy < $y + $h; $yy++) {
            for ($xx = $x; $xx < $x + $w; $xx++) {
                $rgb = imagecolorat($im, $xx, $yy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $rf = $r / 255.0;
                $gf = $g / 255.0;
                $bf = $b / 255.0;
                $maxc = max($rf, $gf, $bf);
                $minc = min($rf, $gf, $bf);
                $v = $maxc;
                $s = $maxc <= 1e-6 ? 0.0 : (($maxc - $minc) / $maxc);
                $hue = 0.0;
                if ($maxc > $minc) {
                    if ($maxc === $rf) {
                        $hue = ($gf - $bf) / ($maxc - $minc);
                    } elseif ($maxc === $gf) {
                        $hue = 2.0 + ($bf - $rf) / ($maxc - $minc);
                    } else {
                        $hue = 4.0 + ($rf - $gf) / ($maxc - $minc);
                    }
                    $hue *= 60.0;
                    if ($hue < 0.0) $hue += 360.0;
                }
                $hi = (int) floor(min($bins - 1, max(0, ($hue / 360.0) * $bins)));
                $si = (int) floor(min($bins - 1, max(0, $s * $bins)));
                $vi = (int) floor(min($bins - 1, max(0, $v * $bins)));
                $idx = $hi * $bins * $bins + $si * $bins + $vi;
                $hist[$idx] += 1.0;
            }
        }
        $sum = 0.0;
        foreach ($hist as $v) { $sum += $v; }
        if ($sum > 0.0) {
            for ($i = 0; $i < count($hist); $i++) {
                $hist[$i] /= $sum;
            }
        }
        return $hist;
    }

    private function histogramIntersection(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) return 0.0;
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $sum += min($a[$i], $b[$i]);
        }
        return $sum;
    }

    private function normalizeName(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        if (function_exists('mb_strtolower')) {
            $s = mb_strtolower($s, 'UTF-8');
        } else {
            $s = strtolower($s);
        }
        $orig = $s;
        if (function_exists('transliterator_transliterate')) {
            $s2 = @transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0300-\u036f] Remove; Lower()', $s);
            if (is_string($s2) && $s2 !== '') { $s = $s2; }
        } elseif (function_exists('iconv')) {
            $s2 = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
            if ($s2 !== false) { $s = strtolower($s2); }
        }
        $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', (string) $s);
        $s = trim((string) $s);
        if ($s === '' && $orig !== '') {
            $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $orig);
            $s = preg_replace('/\s+/', ' ', (string) $s);
            $s = trim((string) $s);
            if (function_exists('mb_strtolower')) {
                $s = mb_strtolower($s, 'UTF-8');
            } else {
                $s = strtolower($s);
            }
        }
        return $s;
    }

    private function nameSimilarity(string $a, string $b): float
    {
        $a = trim($a);
        $b = trim($b);
        if ($a === '' || $b === '') return 0.0;
        if ($a === $b) return 1.0;
        $len = max(strlen($a), strlen($b));
        $lev = $len > 0 ? max(0.0, 1.0 - (levenshtein($a, $b) / $len)) : 0.0;
        $ta = array_values(array_unique(array_filter(explode(' ', $a), function ($x) { return $x !== ''; })));
        $tb = array_values(array_unique(array_filter(explode(' ', $b), function ($x) { return $x !== ''; })));
        $j = 0.0;
        if (count($ta) > 0 && count($tb) > 0) {
            $inter = count(array_intersect($ta, $tb));
            $union = count(array_unique(array_merge($ta, $tb)));
            $j = $union > 0 ? ($inter / $union) : 0.0;
        }
        $contain = 0.0;
        if (str_contains($a, $b) || str_contains($b, $a)) {
            $la = max(1, strlen($a));
            $lb = max(1, strlen($b));
            $contain = min($la, $lb) / max($la, $lb);
        }
        return max($lev, $j, $contain);
    }

    private function parseNamesFromText(string $text): array
    {
        $out = ['first' => null, 'last' => null, 'username' => null, 'role' => null];
        $t = strtolower($text);
        if (preg_match('/first[_\\s-]*name[:\\s]+([\\p{L}\\p{N}\\-\\. ]{1,64})/iu', $text, $m1)) {
            $out['first'] = trim($m1[1]);
        }
        if (preg_match('/last[_\\s-]*name[:\\s]+([\\p{L}\\p{N}\\-\\. ]{1,64})/iu', $text, $m2)) {
            $out['last'] = trim($m2[1]);
        }
        if (preg_match('/username[:\\s]+([\\p{L}\\p{N}\\-\\. _]{1,64})/iu', $text, $m3)) {
            $out['username'] = trim($m3[1]);
        }
        if (
            preg_match('/role[\\s_-]*name?[:\\s]+([a-zA-Z]{3,20})/iu', $text, $mr) ||
            preg_match('/badge[:\\s]+([a-zA-Z]{3,20})/iu', $text, $mr) ||
            preg_match('/position[:\\s]+([a-zA-Z]{3,20})/iu', $text, $mr) ||
            preg_match('/title[:\\s]+([a-zA-Z]{3,20})/iu', $text, $mr)
        ) {
            $out['role'] = strtoupper(trim($mr[1]));
        } else {
            if (preg_match('/\\b(player|organiser|organizer|viewer|moderator|coach)\\b/iu', $text, $mrole)) {
                $tok = strtolower($mrole[1]);
                if ($tok === 'player') $out['role'] = 'PLAYER';
                elseif ($tok === 'coach') $out['role'] = 'COACH';
                elseif ($tok === 'organiser' || $tok === 'organizer') $out['role'] = 'ORGANISER';
                elseif ($tok === 'viewer' || $tok === 'moderator') $out['role'] = 'VIEWER';
            }
        }
        return $out;
    }

    private function gradientFingerprintResourceNormalized($im): array
    {
        $w = 32;
        $h = 32;
        $small = imagecreatetruecolor($w, $h);
        imagecopyresampled($small, $im, 0, 0, 0, 0, $w, $h, imagesx($im), imagesy($im));
        $arr = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3.0;
                $arr[$y * $w + $x] = $gray;
            }
        }
        $grad = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $gx = ($x + 1 < $w ? $arr[$y * $w + ($x + 1)] : $arr[$y * $w + $x]) - ($x > 0 ? $arr[$y * $w + ($x - 1)] : $arr[$y * $w + $x]);
                $gy = ($y + 1 < $h ? $arr[($y + 1) * $w + $x] : $arr[$y * $w + $x]) - ($y > 0 ? $arr[($y - 1) * $w + $x] : $arr[$y * $w + $x]);
                $mag = sqrt($gx * $gx + $gy * $gy);
                $grad[] = $mag;
            }
        }
        imagedestroy($small);
        $mean = 0.0;
        foreach ($grad as $v) { $mean += $v; }
        $n = max(1, count($grad));
        $mean /= $n;
        $std = 0.0;
        foreach ($grad as $v) { $std += ($v - $mean) * ($v - $mean); }
        $std = sqrt($std / $n);
        if ($std <= 1e-6) { $std = 1.0; }
        for ($i = 0; $i < $n; $i++) {
            $grad[$i] = ($grad[$i] - $mean) / $std;
        }
        return $grad;
    }

    private function gradientFingerprintPatchNormalized($im, int $x, int $y, int $w, int $h): array
    {
        $w32 = 32;
        $h32 = 32;
        $small = imagecreatetruecolor($w32, $h32);
        imagecopyresampled($small, $im, 0, 0, $x, $y, $w32, $h32, $w, $h);
        $arr = [];
        for ($yy = 0; $yy < $h32; $yy++) {
            for ($xx = 0; $xx < $w32; $xx++) {
                $rgb = imagecolorat($small, $xx, $yy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3.0;
                $arr[$yy * $w32 + $xx] = $gray;
            }
        }
        $grad = [];
        for ($yy = 0; $yy < $h32; $yy++) {
            for ($xx = 0; $xx < $w32; $xx++) {
                $gx = ($xx + 1 < $w32 ? $arr[$yy * $w32 + ($xx + 1)] : $arr[$yy * $w32 + $xx]) - ($xx > 0 ? $arr[$yy * $w32 + ($xx - 1)] : $arr[$yy * $w32 + $xx]);
                $gy = ($yy + 1 < $h32 ? $arr[($yy + 1) * $w32 + $xx] : $arr[$yy * $w32 + $xx]) - ($yy > 0 ? $arr[($yy - 1) * $w32 + $xx] : $arr[$yy * $w32 + $xx]);
                $mag = sqrt($gx * $gx + $gy * $gy);
                $grad[] = $mag;
            }
        }
        imagedestroy($small);
        $mean = 0.0;
        foreach ($grad as $v) { $mean += $v; }
        $n = max(1, count($grad));
        $mean /= $n;
        $std = 0.0;
        foreach ($grad as $v) { $std += ($v - $mean) * ($v - $mean); }
        $std = sqrt($std / $n);
        if ($std <= 1e-6) { $std = 1.0; }
        for ($i = 0; $i < $n; $i++) {
            $grad[$i] = ($grad[$i] - $mean) / $std;
        }
        return $grad;
    }

    private function extractNames(string $path, ?string $originalName = null): array
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            return [];
        }
        $base64 = 'data:image/png;base64,' . base64_encode($data);
        $provider = (string) ($this->params->has('role_verification_ocr_provider') ? $this->params->get('role_verification_ocr_provider') : '');
        if ($provider === 'optiic') {
            try {
                return $this->extractWithOptiic($base64);
            } catch (\Throwable $e) {
            }
        }
        $baseUrl = (string) ($this->params->has('role_verification_ocr_base_url') ? $this->params->get('role_verification_ocr_base_url') : '');
        if ($baseUrl !== '') {
            try {
                $res = $this->httpClient->request('POST', rtrim($baseUrl, '/') . '/ocr', [
                    'json' => ['image' => $base64],
                    'timeout' => 8.0,
                ]);
                if ($res->getStatusCode() === 200) {
                    $arr = $res->toArray(false);
                    $first = (string) ($arr['first'] ?? '');
                    $last = (string) ($arr['last'] ?? '');
                    $username = (string) ($arr['username'] ?? '');
                    $role = (string) ($arr['role'] ?? '');
                    $text = (string) ($arr['text'] ?? '');
                    return ['first' => $first, 'last' => $last, 'username' => $username, 'role' => $role, 'text' => $text];
                }
            } catch (\Throwable $e) {
            }
        }
        if (is_string($originalName) && $originalName !== '') {
            $nameOnly = $originalName;
            $pos = strrpos($nameOnly, '.');
            if ($pos !== false) {
                $nameOnly = substr($nameOnly, 0, $pos);
            }
            $nameOnly = (string) preg_replace('/[_\\-\\.]+/u', ' ', $nameOnly);
            $tokens = array_values(array_filter(preg_split('/[^\\p{L}\\p{N}]+/u', $nameOnly), function ($t) {
                return is_string($t) && trim($t) !== '';
            }));
            $role = '';
            $lowerAll = strtolower($nameOnly);
            if (str_contains($lowerAll, 'player')) { $role = 'PLAYER'; }
            elseif (str_contains($lowerAll, 'organiser') || str_contains($lowerAll, 'organizer')) { $role = 'ORGANISER'; }
            elseif (str_contains($lowerAll, 'viewer') || str_contains($lowerAll, 'moderator')) { $role = 'VIEWER'; }
            elseif (str_contains($lowerAll, 'coach')) { $role = 'COACH'; }
            if (count($tokens) >= 2) {
                return ['first' => (string) $tokens[0], 'last' => (string) $tokens[1], 'username' => implode('', $tokens), 'role' => $role, 'text' => ''];
            }
            if (count($tokens) === 1) {
                return ['first' => '', 'last' => '', 'username' => (string) $tokens[0], 'role' => $role, 'text' => ''];
            }
        }
        return [];
    }

    private function containsBrand(string $path): bool
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            return false;
        }
        $base64 = 'data:image/png;base64,' . base64_encode($data);
        $provider = (string) ($this->params->has('role_verification_ocr_provider') ? $this->params->get('role_verification_ocr_provider') : '');
        if ($provider === 'optiic') {
            try {
                $arr = $this->extractWithOptiic($base64);
                $text = strtolower((string) ($arr['text'] ?? ''));
                return $text !== '' && (str_contains($text, 'carthagegg') || str_contains($text, 'carthage gg'));
            } catch (\Throwable $e) {
                return false;
            }
        }
        $baseUrl = (string) ($this->params->has('role_verification_ocr_base_url') ? $this->params->get('role_verification_ocr_base_url') : '');
        if ($baseUrl === '') {
            return false;
        }
        try {
            $res = $this->httpClient->request('POST', rtrim($baseUrl, '/') . '/ocr', [
                'json' => ['image' => $base64],
                'timeout' => 8.0,
            ]);
            if ($res->getStatusCode() === 200) {
                $arr = $res->toArray(false);
                $text = strtolower((string) ($arr['text'] ?? ''));
                return $text !== '' && (str_contains($text, 'carthagegg') || str_contains($text, 'carthage gg'));
            }
        } catch (\Throwable $e) {
        }
        return false;
    }

    private function hasOcrConfigured(): bool
    {
        $provider = (string) ($this->params->has('role_verification_ocr_provider') ? $this->params->get('role_verification_ocr_provider') : '');
        if ($provider === 'optiic') {
            $endpoint = (string) ($this->params->has('role_verification_ocr_endpoint') ? $this->params->get('role_verification_ocr_endpoint') : '');
            $apiKey = (string) ($this->params->has('role_verification_ocr_api_key') ? $this->params->get('role_verification_ocr_api_key') : '');
            return $endpoint !== '' && $apiKey !== '';
        }
        return $this->params->has('role_verification_ocr_base_url') && (string) $this->params->get('role_verification_ocr_base_url') !== '';
    }

    private function extractWithOptiic(string $dataUrl): array
    {
        $endpoint = (string) ($this->params->has('role_verification_ocr_endpoint') ? $this->params->get('role_verification_ocr_endpoint') : 'https://api.optiic.dev/process');
        $apiKey = (string) ($this->params->has('role_verification_ocr_api_key') ? $this->params->get('role_verification_ocr_api_key') : '');
        if ($apiKey === '') {
            return [];
        }
        $payload = ['apiKey' => $apiKey, 'url' => $dataUrl];
        $res = $this->httpClient->request('POST', $endpoint, [
            'json' => $payload,
            'timeout' => 12.0,
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        if ($res->getStatusCode() !== 200) {
            return [];
        }
        $arr = $res->toArray(false);
        $text = (string) ($arr['text'] ?? ($arr['result']['text'] ?? ''));
        $out = ['first' => '', 'last' => '', 'username' => '', 'role' => '', 'text' => $text];
        if ($text !== '') {
            $parsed = $this->parseNamesFromText($text);
            $out['first'] = (string) ($parsed['first'] ?? '');
            $out['last'] = (string) ($parsed['last'] ?? '');
            $out['username'] = (string) ($parsed['username'] ?? '');
            $out['role'] = (string) ($parsed['role'] ?? '');
        }
        return $out;
    }

    private function cropToContent($im)
    {
        $w = imagesx($im);
        $h = imagesy($im);
        $minX = $w; $minY = $h; $maxX = 0; $maxY = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3.0 / 255.0;
                if ($gray < 0.97) {
                    if ($x < $minX) $minX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($x > $maxX) $maxX = $x;
                    if ($y > $maxY) $maxY = $y;
                }
            }
        }
        if ($maxX <= $minX || $maxY <= $minY) {
            return null;
        }
        $nw = $maxX - $minX + 1;
        $nh = $maxY - $minY + 1;
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopy($dst, $im, 0, 0, $minX, $minY, $nw, $nh);
        return $dst;
    }
    private function defaultBadge(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'ORGANISER';
        }
        if (in_array('ROLE_MODERATOR', $roles, true)) {
            return 'VIEWER';
        }
        return 'PLAYER';
    }
}

