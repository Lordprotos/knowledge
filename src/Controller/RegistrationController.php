<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(private string $mailerFrom) {}

    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) throw $this->createAccessDeniedException();
            $email = mb_strtolower(trim((string) $request->request->get('email')));
            $plainPassword = (string) $request->request->get('password');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{12,}$/', $plainPassword)) { $this->addFlash('error', 'Utilisez une adresse e-mail valide et un mot de passe d’au moins 12 caractères avec majuscule, minuscule et chiffre.'); return $this->redirectToRoute('app_register'); }
            if ($em->getRepository(User::class)->findOneBy(['email' => $email])) { $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.'); return $this->redirectToRoute('app_register'); }
            $user = new User($email, ''); $user->setPassword($hasher->hashPassword($user, $plainPassword));
            $user->setVerificationToken(bin2hex(random_bytes(32))); $em->persist($user); $em->flush();
            $url = $this->generateUrl('app_verify_email', ['token' => $user->getVerificationToken()], UrlGeneratorInterface::ABSOLUTE_URL);
            $mailer->send((new Email())->from($this->mailerFrom)->to($email)->subject('Activez votre compte Knowledge Learning')->text("Bienvenue ! Activez votre compte : $url"));
            $this->addFlash('success', 'Compte créé. Consultez votre e-mail pour l’activer.'); return $this->redirectToRoute('app_login');
        }
        return $this->render('registration/register.html.twig');
    }

    #[Route('/activation/{token}', name: 'app_verify_email')]
    public function verify(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);
        if (!$user) { $this->addFlash('error', 'Ce lien est invalide ou a déjà été utilisé.'); return $this->redirectToRoute('app_login'); }
        $user->verify(); $em->flush(); $this->addFlash('success', 'Votre compte est activé. Vous pouvez vous connecter.'); return $this->redirectToRoute('app_login');
    }
}
