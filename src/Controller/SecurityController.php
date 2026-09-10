<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response { return $this->render('security/login.html.twig', ['error' => $authenticationUtils->getLastAuthenticationError(), 'last_username' => $authenticationUtils->getLastUsername()]); }
    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): never { throw new \LogicException('Intercepté par Symfony.'); }
}
