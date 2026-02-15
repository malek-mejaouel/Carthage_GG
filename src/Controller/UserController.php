<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users')]
final class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $users = $this->userRepository->findUsersPaginated($page, 10);
        $total = $this->userRepository->getTotalCount();

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'perPage' => 10,
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->save($user, true);

            return $this->redirectToRoute('user_show', ['id' => $user->getUserId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->save($user, true);

            return $this->redirectToRoute('user_show', ['id' => $user->getUserId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getUserId(), $request->request->get('_token'))) {
            $this->userRepository->remove($user, true);
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/active/list', name: 'user_active', methods: ['GET'])]
    public function active(): Response
    {
        $users = $this->userRepository->findActiveUsers();

        return $this->render('user/active.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/search', name: 'user_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $keyword = $request->query->get('q', '');
        $users = [];

        if ($keyword) {
            $users = $this->userRepository->searchByUsername($keyword);
        }

        return $this->render('user/search.html.twig', [
            'users' => $users,
            'keyword' => $keyword,
        ]);
    }
}
