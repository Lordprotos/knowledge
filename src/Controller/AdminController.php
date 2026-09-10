<?php
namespace App\Controller;

use App\Entity\Curriculum;
use App\Entity\Purchase;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Backoffice read view for accounts, content and sandbox transactions. */
#[Route('/admin', name: 'app_admin_')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/dashboard.html.twig', [
            'users' => $em->getRepository(User::class)->findBy([], ['id'=>'DESC']),
            'themes' => $em->getRepository(Theme::class)->findAll(),
            'curricula' => $em->getRepository(Curriculum::class)->findAll(),
            'purchases' => $em->getRepository(Purchase::class)->findBy([], ['id'=>'DESC']),
        ]);
    }
}
