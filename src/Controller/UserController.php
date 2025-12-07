<?php

namespace App\Controller;

use App\Enum\UserRole;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends BaseController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    #[IsGranted(UserRole::USER->value)]
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user?->getUserParams())
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user?->setParams((array)$form->getData());

            $entityManager->flush();

            $this->addFlash('success', 'User data have been saved.');

            return $this->redirectToRoute('default');
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form,
        ]);
    }
}
