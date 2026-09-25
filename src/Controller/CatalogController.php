<?php

namespace App\Controller;

use App\Entity\Certification;
use App\Entity\Curriculum;
use App\Entity\Lesson;
use App\Entity\Purchase;
use App\Entity\Theme;
use App\Service\LearningService;
use App\Service\StripeCheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Relie le catalogue, les paiements et le suivi pédagogique aux routes HTTP. */
final class CatalogController extends AbstractController
{
    #[Route('/', name: 'app_catalog')]
    public function catalog(EntityManagerInterface $em): Response
    {
        return $this->render('catalog/index.html.twig', [
            'themes' => $em->getRepository(Theme::class)->findAll(),
        ]);
    }

    #[Route('/cursus/{id}', name: 'app_curriculum')]
    public function curriculum(Curriculum $curriculum, LearningService $learning): Response
    {
        $user = $this->getUser();
        $owned = $user && $learning->ownsCurriculum($user, $curriculum);
        // La vue affiche un accès pour les contenus acquis ; les routes vérifient aussi les droits.
        $accessibleLessons = [];
        foreach ($curriculum->getLessons() as $lesson) {
            $accessibleLessons[$lesson->getId()] =
                $user && ($owned || $learning->canAccess($user, $lesson));
        }
        return $this->render(
            'catalog/curriculum.html.twig',
            compact('curriculum', 'owned', 'accessibleLessons'),
        );
    }

    #[Route('/cursus/{id}/acheter', name: 'app_buy_curriculum', methods: ['POST'])]
    public function buyCurriculum(
        Request $request,
        Curriculum $curriculum,
        StripeCheckoutService $stripe,
        LearningService $learning,
    ): Response {
        $this->csrf($request, 'buy-curriculum-' . $curriculum->getId());
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        // Un ancien formulaire ne doit pas relancer un paiement pour un cursus déjà acheté.
        if ($learning->ownsCurriculum($this->getUser(), $curriculum)) {
            $this->addFlash(
                'success',
                'Ce cursus est déjà acheté. Retrouvez vos leçons ci-dessous.',
            );
            return $this->redirectToRoute(
                'app_curriculum',
                ['id' => $curriculum->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->redirect($stripe->create($this->getUser(), $curriculum));
    }

    #[Route('/lecon/{id}', name: 'app_lesson')]
    public function lesson(Lesson $lesson, LearningService $learning): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        if (!$learning->canAccess($this->getUser(), $lesson)) {
            $this->addFlash('error', 'Achetez cette leçon pour y accéder.');
            return $this->redirectToRoute('app_curriculum', [
                'id' => $lesson->getCurriculum()->getId(),
            ]);
        }
        return $this->render('catalog/lesson.html.twig', compact('lesson'));
    }

    #[Route('/lecon/{id}/acheter', name: 'app_buy_lesson', methods: ['POST'])]
    public function buyLesson(
        Request $request,
        Lesson $lesson,
        StripeCheckoutService $stripe,
        LearningService $learning,
    ): Response {
        $this->csrf($request, 'buy-lesson-' . $lesson->getId());
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        // Un achat de cursus couvre également le paiement de ses leçons individuelles.
        if ($learning->canAccess($this->getUser(), $lesson)) {
            $this->addFlash('success', 'Cette leçon est déjà accessible sur votre compte.');
            return $this->redirectToRoute(
                'app_lesson',
                ['id' => $lesson->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->redirect($stripe->create($this->getUser(), $lesson));
    }

    #[Route('/paiement/succes', name: 'app_stripe_success')]
    public function stripeSuccess(
        Request $request,
        StripeCheckoutService $stripe,
        EntityManagerInterface $em,
        LearningService $learning,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        try {
            // Vérifie auprès de Stripe le paiement et son appartenance au client connecté.
            $session = $stripe->retrieve((string) $request->query->get('session_id'));
            if (
                $session->payment_status !== 'paid' ||
                $session->client_reference_id !== (string) $this->getUser()->getId()
            ) {
                throw new \LogicException();
            }
            // Le droit d’accès est enregistré seulement après ces vérifications.
            $id = (int) ($session->metadata->product_id ?? 0);
            if (
                ($session->metadata->product_type ?? '') === 'curriculum' &&
                ($item = $em->find(Curriculum::class, $id))
            ) {
                $learning->buyCurriculum($this->getUser(), $item);
                $this->addFlash('success', 'Paiement Stripe confirmé : accès accordé.');
                return $this->redirectToRoute('app_curriculum', ['id' => $id]);
            }
            if (
                ($session->metadata->product_type ?? '') === 'lesson' &&
                ($item = $em->find(Lesson::class, $id))
            ) {
                $learning->buyLesson($this->getUser(), $item);
                $this->addFlash('success', 'Paiement Stripe confirmé : accès accordé.');
                return $this->redirectToRoute('app_lesson', ['id' => $id]);
            }
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de confirmer ce paiement.');
        }
        return $this->redirectToRoute('app_catalog');
    }

    #[Route('/paiement/annule', name: 'app_stripe_cancel')]
    public function stripeCancel(): Response
    {
        $this->addFlash('error', 'Paiement annulé.');
        return $this->redirectToRoute('app_catalog');
    }

    #[Route('/lecon/{id}/valider', name: 'app_validate_lesson', methods: ['POST'])]
    public function validate(Request $request, Lesson $lesson, LearningService $learning): Response
    {
        $this->csrf($request, 'validate-lesson-' . $lesson->getId());
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $learning->validateLesson($this->getUser(), $lesson);
        return $this->redirectToRoute(
            'app_curriculum',
            ['id' => $lesson->getCurriculum()->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route('/certifications', name: 'app_certifications')]
    public function certifications(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        // Les achats et certificats sont filtrés par compte ; un achat n’est pas un certificat.
        return $this->render('catalog/certifications.html.twig', [
            'certifications' => $em
                ->getRepository(Certification::class)
                ->findBy(['user' => $this->getUser()]),
            'purchases' => $em
                ->getRepository(Purchase::class)
                ->findBy(
                    ['user' => $this->getUser(), 'status' => 'paid'],
                    ['purchasedAt' => 'DESC'],
                ),
        ]);
    }

    private function csrf(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
    }
}
