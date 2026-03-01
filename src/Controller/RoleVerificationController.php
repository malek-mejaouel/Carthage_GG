<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RoleVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class RoleVerificationController extends AbstractController
{
    #[Route('/verify-role', name: 'verify_role_info', methods: ['GET'])]
    public function info(Request $request): Response
    {
        $isAjax = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept'), 'application/json');
        if ($isAjax) {
            return $this->json(['success' => false, 'code' => 'method_not_allowed', 'message' => 'Use POST /verify-role via the verification form'], Response::HTTP_METHOD_NOT_ALLOWED);
        }
        $id = (int) $request->query->get('id', 0);
        if ($id > 0) {
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        return $this->redirectToRoute('app_admin');
    }
 
    #[Route('/verify-role', name: 'verify_role', methods: ['POST'])]
    public function verify(
        Request $request,
        UserRepository $users,
        RoleVerificationService $service,
        EntityManagerInterface $em,
        Security $security,
        LoggerInterface $logger
    ): Response {
        $isAjax = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept'), 'application/json');
        $id = (int) $request->request->get('id');
        $user = $users->find($id);
        if (!$user) {
            if ($isAjax) {
                return $this->json(['success' => false, 'code' => 'user_not_found', 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('app_admin');
        }
        $current = $security->getUser();
        $isOwner = $current instanceof User && $current->getId() === $user->getId();
        $canVerifyOthers = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_VERIFIER');
        if (!$isOwner && !$canVerifyOthers) {
            if ($isAjax) {
                return $this->json(['success' => false, 'code' => 'access_denied', 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
            $this->addFlash('error', 'Access denied');
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        $submittedToken = $request->request->get('_token');
        if (!is_string($submittedToken) || !$this->isCsrfTokenValid('verify_role_' . $id, $submittedToken)) {
            if ($isAjax) {
                return $this->json(['success' => false, 'code' => 'invalid_csrf', 'message' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
            }
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        $uploaded = $request->files->get('verification_image');
        if (!$uploaded) {
            if ($isAjax) {
                return $this->json(['success' => false, 'code' => 'no_image', 'message' => 'No image uploaded'], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'No image uploaded');
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        $roleName = (string) $request->request->get('role_name');
        try {
            $result = $service->verify($user, $uploaded, $roleName ?: null);
        } catch (\Throwable $e) {
            $logger->error('role_verification.exception', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
            if ($isAjax) {
                return $this->json(['success' => false, 'code' => 'server_error', 'message' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $this->addFlash('error', 'Internal server error');
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        if (!$result['success']) {
            $user->setIsVerified(false);
            $user->setVerifiedRoleBadge(null);
            $user->setVerificationDate(null);
            $em->persist($user);
            $em->flush();
            if ($isAjax) {
                return $this->json(['success' => false, 'code' => $result['code'], 'message' => $result['message'], 'trace' => $result['trace'] ?? null], Response::HTTP_OK);
            }
            $this->addFlash('error', $result['message']);
            return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
        }
        $user->setIsVerified(true);
        $user->setVerifiedRoleBadge(isset($result['badge']) ? (string) $result['badge'] : '');
        $verAt = isset($result['timestamp']) ? \DateTime::createFromImmutable($result['timestamp']) : new \DateTime();
        $user->setVerificationDate($verAt);
        $em->persist($user);
        $em->flush();
        if ($isAjax) {
            $ts = $user->getVerificationDate() ? $user->getVerificationDate()->format('M d, Y H:i') : '';
            return $this->json([
                'success' => true,
                'badge' => (string) $user->getVerifiedRoleBadge(),
                'timestamp' => $ts,
                'trace' => $result['trace'] ?? null,
            ]);
        }
        $this->addFlash('success', 'Verification successful');
        return $this->redirectToRoute('app_profile_edit', ['id' => $id]);
    }
}

