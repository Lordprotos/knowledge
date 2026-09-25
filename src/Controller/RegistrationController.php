<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Inscrit les clients et active leur compte à partir du lien envoyé par e-mail. */
final class RegistrationController extends AbstractController
{
    public function __construct(private string $mailerFrom) {}

    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        MailerInterface $mailer,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            // Normalise l’adresse avant de valider la saisie et de rechercher un compte existant.
            $email = mb_strtolower(trim((string) $request->request->get('email')));
            $plainPassword = (string) $request->request->get('password');
            if (
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{12,}$/', $plainPassword)
            ) {
                $this->addFlash(
                    'error',
                    'Utilisez une adresse e-mail valide et un mot de passe d’au moins 12 caractères avec majuscule, minuscule et chiffre.',
                );
                return $this->redirectToRoute('app_register');
            }
            // Un compte non activé peut demander un renvoi avec son mot de passe actuel.
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (
                $user &&
                ($user->isVerified() || !$hasher->isPasswordValid($user, $plainPassword))
            ) {
                $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.');
                return $this->redirectToRoute('app_register');
            }
            if (!$user) {
                $user = new User($email, '');
                // Seul le résultat du hachage est conservé en base.
                $user->setPassword($hasher->hashPassword($user, $plainPassword));
                $em->persist($user);
            }
            // Conserve le jeton existant pour que les liens déjà envoyés restent utilisables.
            if (!$user->getVerificationToken()) {
                $user->setVerificationToken(bin2hex(random_bytes(32)));
            }
            $em->flush();
            $url = $this->generateUrl(
                'app_verify_email',
                ['token' => $user->getVerificationToken()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
            try {
                $mailer->send(
                    (new Email())
                        ->from($this->mailerFrom)
                        ->to($email)
                        ->subject('Activez votre compte Knowledge Learning')
                        ->text("Bienvenue ! Activez votre compte : $url"),
                );
            } catch (TransportExceptionInterface) {
                // Le compte reste enregistré et non activé ; un nouvel essai pourra renvoyer le lien.
                $this->addFlash(
                    'error',
                    'Votre compte est enregistré, mais l’e-mail d’activation n’a pas pu être envoyé. Réessayez plus tard avec la même adresse et le même mot de passe pour renvoyer le lien.',
                );
                return $this->redirectToRoute('app_register', [], Response::HTTP_SEE_OTHER);
            }
            $this->addFlash('success', 'Compte créé. Consultez votre e-mail pour l’activer.');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('registration/register.html.twig');
    }

    #[Route('/activation/{token}', name: 'app_verify_email')]
    public function verify(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);
        if (!$user) {
            $this->addFlash('error', 'Ce lien est invalide ou a déjà été utilisé.');
            return $this->redirectToRoute('app_login');
        }
        // L’activation efface le jeton, ce qui empêche sa réutilisation.
        $user->verify();
        $em->flush();
        $this->addFlash('success', 'Votre compte est activé. Vous pouvez vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}
