<?php

namespace App\Controller;

use App\Entity\FaceAuthentication;
use App\Entity\User;
use App\Repository\FaceAuthenticationRepository;
use App\Repository\UserRepository;
use App\Service\FaceRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FaceAuthenticationController extends AbstractController
{
    private FaceRecognitionService $faceRecognitionService;

    private EntityManagerInterface $entityManager;

    private FaceAuthenticationRepository $faceAuthenticationRepository;

    private UserRepository $userRepository;

    private Security $security;

    public function __construct(
        FaceRecognitionService $faceRecognitionService,
        EntityManagerInterface $entityManager,
        FaceAuthenticationRepository $faceAuthenticationRepository,
        UserRepository $userRepository,
        Security $security
    ) {
        $this->faceRecognitionService = $faceRecognitionService;
        $this->entityManager = $entityManager;
        $this->faceAuthenticationRepository = $faceAuthenticationRepository;
        $this->userRepository = $userRepository;
        $this->security = $security;
    }

    #[Route('/profile/face/register', name: 'profile_face_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['image'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        $imageData = $data['image'];

        $validationResult = $this->validateImage($imageData);
        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        $descriptorJson = $this->faceRecognitionService->extractDescriptor($imageData);
        if ($descriptorJson === null) {
            return new JsonResponse(['success' => false, 'message' => 'Face recognition service unavailable'], Response::HTTP_BAD_GATEWAY);
        }

        $faceAuth = $this->faceAuthenticationRepository->findOneBy(['user' => $user]);
        if (!$faceAuth) {
            $faceAuth = new FaceAuthentication();
            $faceAuth->setUser($user);
        }

        $faceAuth->setDescriptor($descriptorJson);
        $faceAuth->setEnabled(true);
        $faceAuth->setRegisteredAt(new \DateTime());

        $this->entityManager->persist($faceAuth);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/face/login', name: 'face_login', methods: ['POST'])]
    public function loginWithFace(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $attempts = $session->get('face_login_attempts', []);
        $now = time();
        $windowSeconds = 300;
        $maxAttempts = 5;

        $attempts = array_filter($attempts, static function (int $timestamp) use ($now, $windowSeconds): bool {
            return ($now - $timestamp) <= $windowSeconds;
        });

        if (count($attempts) >= $maxAttempts) {
            return new JsonResponse(['success' => false, 'message' => 'Too many face login attempts, please try again later'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['image'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        $imageData = $data['image'];

        $validationResult = $this->validateImage($imageData);
        if ($validationResult instanceof JsonResponse) {
            $attempts[] = $now;
            $session->set('face_login_attempts', $attempts);

            return $validationResult;
        }

        $descriptorJson = $this->faceRecognitionService->extractDescriptor($imageData);
        if ($descriptorJson === null) {
            $attempts[] = $now;
            $session->set('face_login_attempts', $attempts);

            return new JsonResponse(['success' => false, 'message' => 'Face recognition service unavailable'], Response::HTTP_BAD_GATEWAY);
        }

        $loginDescriptorRaw = json_decode($descriptorJson, true);
        if (!is_array($loginDescriptorRaw)) {
             return new JsonResponse(['success' => false, 'message' => 'Invalid face descriptor received'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $loginDescriptor = array_values(array_map(static fn($v) => (float) $v, $loginDescriptorRaw));

        $email = isset($data['email']) ? trim((string) $data['email']) : null;
        $faceAuths = [];
        if ($email) {
            $candidateUser = $this->userRepository->findOneBy(['email' => $email]);
            if ($candidateUser instanceof User) {
                $fa = $this->faceAuthenticationRepository->findOneBy(['user' => $candidateUser, 'enabled' => true]);
                if ($fa) {
                    $faceAuths = [$fa];
                }
            }
        }
        if (count($faceAuths) === 0) {
            $faceAuths = $this->faceAuthenticationRepository->findBy(['enabled' => true]);
        }
        
        $bestMatchUser = null;
        $minDistance = 100.0;

        foreach ($faceAuths as $faceAuth) {
            $storedDescriptorJson = $faceAuth->getDescriptor();
            if (!$storedDescriptorJson) {
                continue;
            }

            $storedDescriptorRaw = json_decode($storedDescriptorJson, true);
            if (!is_array($storedDescriptorRaw)) {
                continue;
            }
            $storedDescriptor = array_values(array_map(static fn($v) => (float) $v, $storedDescriptorRaw));
            $distance = $this->faceRecognitionService->compareFaces($loginDescriptor, $storedDescriptor);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestMatchUser = $faceAuth->getUser();
            }
        }

        $threshold = 0.4;

        if ($bestMatchUser === null || $minDistance > $threshold) {
            $attempts[] = $now;
            $session->set('face_login_attempts', $attempts);

            return new JsonResponse(['success' => false, 'message' => 'Face not recognized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $bestMatchUser;
        $this->security->login($user, 'security.authenticator.form_login.main');

        $session->set('face_login_attempts', []);

        return new JsonResponse([
            'success' => true,
            'redirect' => $this->generateUrl('app_index'),
        ]);
    }

    private function validateImage(string $imageData): ?JsonResponse
    {
        $base64 = $imageData;
        $mimeType = null;

        if (str_starts_with($imageData, 'data:')) {
            $parts = explode(',', $imageData, 2);
            if (count($parts) !== 2) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid image data'], Response::HTTP_BAD_REQUEST);
            }
            $meta = $parts[0];
            $base64 = $parts[1];

            $metaParts = explode(';', $meta);
            if (str_starts_with($metaParts[0], 'data:')) {
                $mimeType = substr($metaParts[0], 5);
            }
        }

        if ($mimeType !== null) {
            $allowedMimeTypes = ['image/jpeg', 'image/png'];
            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                return new JsonResponse(['success' => false, 'message' => 'Unsupported image format'], Response::HTTP_BAD_REQUEST);
            }
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid image encoding'], Response::HTTP_BAD_REQUEST);
        }

        $maxBytes = 2 * 1024 * 1024;
        if (strlen($binary) > $maxBytes) {
            return new JsonResponse(['success' => false, 'message' => 'Image is too large'], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}
