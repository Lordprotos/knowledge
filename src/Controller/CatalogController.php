<?php
namespace App\Controller;

use App\Entity\Certification;
use App\Entity\Curriculum;
use App\Entity\Lesson;
use App\Entity\Theme;
use App\Service\LearningService;
use App\Service\StripeCheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route('/', name: 'app_catalog')] public function catalog(EntityManagerInterface $em): Response { return $this->render('catalog/index.html.twig', ['themes' => $em->getRepository(Theme::class)->findAll()]); }
    #[Route('/cursus/{id}', name: 'app_curriculum')] public function curriculum(Curriculum $curriculum): Response { return $this->render('catalog/curriculum.html.twig', compact('curriculum')); }
    #[Route('/cursus/{id}/acheter', name: 'app_buy_curriculum', methods: ['POST'])]
    public function buyCurriculum(Request $request, Curriculum $curriculum, StripeCheckoutService $stripe): Response
    {
        $this->csrf($request, 'buy-curriculum-'.$curriculum->getId()); $this->denyAccessUnlessGranted('ROLE_CLIENT');
        return $this->redirect($stripe->create($this->getUser(), $curriculum));
    }
    #[Route('/lecon/{id}', name: 'app_lesson')]
    public function lesson(Lesson $lesson, LearningService $learning): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        if (!$learning->canAccess($this->getUser(), $lesson)) { $this->addFlash('error', 'Achetez cette leçon pour y accéder.'); return $this->redirectToRoute('app_curriculum', ['id' => $lesson->getCurriculum()->getId()]); }
        return $this->render('catalog/lesson.html.twig', compact('lesson'));
    }
    #[Route('/lecon/{id}/acheter', name: 'app_buy_lesson', methods: ['POST'])]
    public function buyLesson(Request $request, Lesson $lesson, StripeCheckoutService $stripe): Response
    {
        $this->csrf($request, 'buy-lesson-'.$lesson->getId()); $this->denyAccessUnlessGranted('ROLE_CLIENT');
        return $this->redirect($stripe->create($this->getUser(), $lesson));
    }
    #[Route('/paiement/succes', name: 'app_stripe_success')]
    public function stripeSuccess(Request $request, StripeCheckoutService $stripe, EntityManagerInterface $em, LearningService $learning): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        try {
            $session = $stripe->retrieve((string) $request->query->get('session_id'));
            if ($session->payment_status !== 'paid' || $session->client_reference_id !== (string) $this->getUser()->getId()) throw new \LogicException();
            $id = (int) ($session->metadata->product_id ?? 0);
            if (($session->metadata->product_type ?? '') === 'curriculum' && ($item = $em->find(Curriculum::class, $id))) { $learning->buyCurriculum($this->getUser(), $item); $this->addFlash('success', 'Paiement Stripe confirmé : accès accordé.'); return $this->redirectToRoute('app_curriculum', ['id' => $id]); }
            if (($session->metadata->product_type ?? '') === 'lesson' && ($item = $em->find(Lesson::class, $id))) { $learning->buyLesson($this->getUser(), $item); $this->addFlash('success', 'Paiement Stripe confirmé : accès accordé.'); return $this->redirectToRoute('app_lesson', ['id' => $id]); }
        } catch (\Throwable) { $this->addFlash('error', 'Impossible de confirmer ce paiement.'); }
        return $this->redirectToRoute('app_catalog');
    }
    #[Route('/paiement/annule', name: 'app_stripe_cancel')]
    public function stripeCancel(): Response { $this->addFlash('error', 'Paiement annulé.'); return $this->redirectToRoute('app_catalog'); }
    #[Route('/lecon/{id}/valider', name: 'app_validate_lesson', methods: ['POST'])]
    public function validate(Request $request, Lesson $lesson, LearningService $learning): Response { $this->csrf($request, 'validate-lesson-'.$lesson->getId()); $this->denyAccessUnlessGranted('ROLE_CLIENT'); $learning->validateLesson($this->getUser(), $lesson); return $this->redirectToRoute('app_lesson', ['id' => $lesson->getId()]); }
    #[Route('/certifications', name: 'app_certifications')]
    public function certifications(EntityManagerInterface $em): Response { $this->denyAccessUnlessGranted('ROLE_CLIENT'); return $this->render('catalog/certifications.html.twig', ['certifications' => $em->getRepository(Certification::class)->findBy(['user' => $this->getUser()])]); }
    private function csrf(Request $request, string $id): void { if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) throw $this->createAccessDeniedException(); }
}
