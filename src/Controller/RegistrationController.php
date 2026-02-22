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

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
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
            $username = strtolower($user->getFirstName() . '_' . $user->getLastName());
            $user->setUsername($username);

            // Set user as inactive initially (they haven't logged in yet)
            $user->setIsActive(false);

            // Roles: form field is unmapped and returns a single string, convert to array
            $selectedRole = $form->get('roles')->getData();
            if ($selectedRole) {
                if (is_array($selectedRole)) {
                    $user->setRoles($selectedRole);
                } else {
                    $user->setRoles([$selectedRole]);
                }
            }

            // Persist and flush
            $entityManager->persist($user);
            $entityManager->flush();

            // Redirect to login or dashboard
            return $this->redirectToRoute('app_login');
        }

        // If submitted but not valid, collect detailed errors to show in template
        $formErrors = [];
        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $origin = $error->getOrigin();
                $name = $origin ? $origin->getName() : 'form';
                $formErrors[$name][] = $error->getMessage();
            }
        }

        return $this->render('login\sign_up/sign_up.html.twig', [
            'form' => $form,
            'form_errors' => $formErrors,
        ]);
    }
}
