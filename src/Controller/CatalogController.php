<?php
namespace App\Controller;

use App\Entity\Certification;
use App\Entity\Curriculum;
use App\Entity\Lesson;
use App\Entity\Theme;
use App\Entity\User;
use App\Service\LearningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route('/', name: 'app_catalog')] public function catalog(EntityManagerInterface $em): Response { return $this->render('catalog/index.html.twig', ['themes' => $em->getRepository(Theme::class)->findAll()]); }
    #[Route('/cursus/{id}', name: 'app_curriculum')] public function curriculum(Curriculum $curriculum): Response { return $this->render('catalog/curriculum.html.twig', compact('curriculum')); }
    #[Route('/cursus/{id}/acheter', name: 'app_buy_curriculum', methods: ['POST'])] public function buyCurriculum(Request $request, Curriculum $curriculum, LearningService $learning): Response { $this->assertCsrf($request, 'buy-curriculum-'.$curriculum->getId()); $this->denyAccessUnlessGranted('ROLE_CLIENT'); $learning->buyCurriculum($this->getUser(), $curriculum); $this->addFlash('success', 'Achat simulé : accès au cursus accordé.'); return $this->redirectToRoute('app_curriculum', ['id'=>$curriculum->getId()]); }
    #[Route('/lecon/{id}', name: 'app_lesson')] public function lesson(Lesson $lesson, LearningService $learning): Response { $this->denyAccessUnlessGranted('ROLE_CLIENT'); if (!$learning->canAccess($this->getUser(), $lesson)) { $this->addFlash('error', 'Achetez cette leçon pour y accéder.'); return $this->redirectToRoute('app_curriculum', ['id'=>$lesson->getCurriculum()->getId()]); } return $this->render('catalog/lesson.html.twig', compact('lesson')); }
    #[Route('/lecon/{id}/acheter', name: 'app_buy_lesson', methods: ['POST'])] public function buyLesson(Request $request, Lesson $lesson, LearningService $learning): Response { $this->assertCsrf($request, 'buy-lesson-'.$lesson->getId()); $this->denyAccessUnlessGranted('ROLE_CLIENT'); $learning->buyLesson($this->getUser(), $lesson); return $this->redirectToRoute('app_lesson', ['id'=>$lesson->getId()]); }
    #[Route('/lecon/{id}/valider', name: 'app_validate_lesson', methods: ['POST'])] public function validate(Request $request, Lesson $lesson, LearningService $learning): Response { $this->assertCsrf($request, 'validate-lesson-'.$lesson->getId()); $this->denyAccessUnlessGranted('ROLE_CLIENT'); $learning->validateLesson($this->getUser(), $lesson); return $this->redirectToRoute('app_lesson', ['id'=>$lesson->getId()]); }
    #[Route('/certifications', name: 'app_certifications')] public function certifications(EntityManagerInterface $em): Response { $this->denyAccessUnlessGranted('ROLE_CLIENT'); return $this->render('catalog/certifications.html.twig', ['certifications'=>$em->getRepository(Certification::class)->findBy(['user'=>$this->getUser()])]); }
    private function assertCsrf(Request $request, string $tokenId): void { if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) throw $this->createAccessDeniedException(); }
}
