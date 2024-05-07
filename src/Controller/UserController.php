<?php

namespace App\Controller;

use App\Entity\User;
use App\Settings\UserSettings;
use Jbtronics\SettingsBundle\Form\SettingsFormFactoryInterface;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends BaseController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    #[IsGranted(User::ROLES['user'])]
    public function profile(
        SettingsFormFactoryInterface $settingsFormFactory,
        SettingsManagerInterface     $settingsManager,
        Request                      $request,
        UserSettings                 $userSettings,
    ): Response
    {
        $form = $settingsFormFactory->createSettingsFormBuilder($userSettings)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $settingsManager->save($userSettings);

            $this->addFlash('success', 'User data have been saved.');

            return $this->redirectToRoute('default');
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form,
        ]);
    }
}
