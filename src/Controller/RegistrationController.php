<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($hashedPassword);

            // Generate username from first and last name
            $username = strtolower(($user->getFirstName() ?? '') . '_' . ($user->getLastName() ?? ''));
            $user->setUsername($username);

            // Set user as inactive initially (they haven't logged in yet)
            $user->setIsActive(false);

            // Roles: form field is unmapped and returns a single string, convert to array
            $selectedRole = $form->get('roles')->getData();
            if ($selectedRole) {
                if (is_array($selectedRole)) {
                    $roles = array_values(array_map('strval', $selectedRole));
                    $user->setRoles($roles);
                } else {
                    $user->setRoles([strval($selectedRole)]);
                }
            }

            // Persist and flush
            $entityManager->persist($user);
            $entityManager->flush();

            try {
                $fullName = trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? ''));
                if ($fullName === '') {
                    $fullName = (string) $user->getUsername();
                }
                $response = $httpClient->request('POST', 'https://michealt1.app.n8n.cloud/webhook-test/welcome-user', [
                    'json' => [
                        'name' => $fullName,
                        'email' => (string) $user->getEmail(),
                    ],
                    'timeout' => 3.0,
                ]);
                $response->getStatusCode();
            } catch (\Throwable $e) {
            }

            // Redirect to login or dashboard
            return $this->redirectToRoute('app_login');
        }

        // If submitted but not valid, collect detailed errors to show in template
        $formErrors = [];
        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true, true) as $error) {
                $name = 'form';
                $message = '';
                if ($error instanceof \Symfony\Component\Form\FormError) {
                    $origin = $error->getOrigin();
                    if ($origin) {
                        $name = $origin->getName();
                    }
                    $message = $error->getMessage();
                } else {
                    $message = (string) $error;
                }
                $formErrors[$name][] = $message;
            }
        }

        return $this->render('login\sign_up/sign_up.html.twig', [
            'form' => $form,
            'form_errors' => $formErrors,
        ]);
    }
}
