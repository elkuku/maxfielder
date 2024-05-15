<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends BaseController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    #[IsGranted(User::ROLES['user'])]
    public function profile(
        Request                $request,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $user = $this->getUser();
        $params = $user?->getParams();
        if ($params) {
            $params['default_style'] = isset($params['default_style']) ? MapBoxStylesEnum::tryFrom($params['default_style']) : MapBoxStylesEnum::Standard;
            $params['default_profile'] = isset($params['default_profile']) ? MapBoxProfilesEnum::from($params['default_profile']) : MapBoxProfilesEnum::Driving;
        }
        $form = $this->createForm(ProfileFormType::class, $params);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user?->setParams($form->getData());

            $entityManager->flush();

            $this->addFlash('success', 'User data have been saved.');

            return $this->redirectToRoute('default');
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form,
        ]);
    }
}
