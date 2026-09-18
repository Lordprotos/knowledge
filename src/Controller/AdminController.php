<?php
namespace App\Controller;

use App\Entity\Curriculum;
use App\Entity\Lesson;
use App\Entity\Purchase;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'app_admin_')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'users' => $em->getRepository(User::class)->findBy([], ['id' => 'DESC']),
            'themes' => $em->getRepository(Theme::class)->findAll(),
            'curricula' => $em->getRepository(Curriculum::class)->findAll(),
            'lessons' => $em->getRepository(Lesson::class)->findAll(),
            'purchases' => $em->getRepository(Purchase::class)->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/theme/new', name: 'theme_new', methods: ['POST'])]
    public function newTheme(Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'theme_new');
        $name = trim((string) $request->request->get('name'));
        if ($name === '') return $this->failure('Le nom du thème est obligatoire.');
        $theme = new Theme($name); $theme->setAuditUser($this->getUser()?->getUserIdentifier());
        $em->persist($theme); $em->flush();
        return $this->success('Thème créé.');
    }

    #[Route('/theme/{id}/edit', name: 'theme_edit', methods: ['POST'])]
    public function editTheme(Theme $theme, Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'theme_edit_'.$theme->getId());
        $name = trim((string) $request->request->get('name'));
        if ($name === '') return $this->failure('Le nom du thème est obligatoire.');
        $theme->setName($name); $em->flush(); return $this->success('Thème modifié.');
    }

    #[Route('/theme/{id}/delete', name: 'theme_delete', methods: ['POST'])]
    public function deleteTheme(Theme $theme, Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'theme_delete_'.$theme->getId());
        $em->remove($theme); $em->flush(); return $this->success('Thème supprimé.');
    }

    #[Route('/curriculum/new', name: 'curriculum_new', methods: ['POST'])]
    public function newCurriculum(Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'curriculum_new');
        $theme = $em->find(Theme::class, $request->request->getInt('theme_id'));
        $title = trim((string) $request->request->get('title')); $price = $this->price($request);
        if (!$theme || $title === '' || $price === null) return $this->failure('Vérifie le thème, le titre et le prix.');
        $item = new Curriculum($theme, $title, $price); $item->setAuditUser($this->getUser()?->getUserIdentifier());
        $em->persist($item); $em->flush(); return $this->success('Parcours créé.');
    }

    #[Route('/curriculum/{id}/edit', name: 'curriculum_edit', methods: ['POST'])]
    public function editCurriculum(Curriculum $item, Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'curriculum_edit_'.$item->getId());
        $theme = $em->find(Theme::class, $request->request->getInt('theme_id'));
        $title = trim((string) $request->request->get('title')); $price = $this->price($request);
        if (!$theme || $title === '' || $price === null) return $this->failure('Vérifie le thème, le titre et le prix.');
        $item->setTheme($theme); $item->setTitle($title); $item->setPriceCents($price); $em->flush();
        return $this->success('Parcours modifié.');
    }

    #[Route('/curriculum/{id}/delete', name: 'curriculum_delete', methods: ['POST'])]
    public function deleteCurriculum(Curriculum $item, Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'curriculum_delete_'.$item->getId());
        $em->remove($item); $em->flush(); return $this->success('Parcours supprimé.');
    }

    #[Route('/lesson/new', name: 'lesson_new', methods: ['POST'])]
    public function newLesson(Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'lesson_new');
        $curriculum = $em->find(Curriculum::class, $request->request->getInt('curriculum_id'));
        if (!$curriculum) return $this->failure('Choisis un parcours pour la leçon.');
        $item = new Lesson($curriculum, 'temp', 1, 0);
        if (!$this->fillLesson($item, $request, $em)) return $this->failure('Vérifie tous les champs de la leçon.');
        $item->setAuditUser($this->getUser()?->getUserIdentifier()); $em->persist($item); $em->flush();
        return $this->success('Leçon créée.');
    }

    #[Route('/lesson/{id}/edit', name: 'lesson_edit', methods: ['POST'])]
    public function editLesson(Lesson $item, Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'lesson_edit_'.$item->getId());
        if (!$this->fillLesson($item, $request, $em)) return $this->failure('Vérifie tous les champs de la leçon.');
        $em->flush(); return $this->success('Leçon modifiée.');
    }

    #[Route('/lesson/{id}/delete', name: 'lesson_delete', methods: ['POST'])]
    public function deleteLesson(Lesson $item, Request $request, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'lesson_delete_'.$item->getId());
        $em->remove($item); $em->flush(); return $this->success('Leçon supprimée.');
    }

    private function fillLesson(Lesson $item, Request $request, EntityManagerInterface $em): bool
    {
        $curriculum = $em->find(Curriculum::class, $request->request->getInt('curriculum_id'));
        $title = trim((string) $request->request->get('title')); $content = trim((string) $request->request->get('content'));
        $position = $request->request->getInt('position'); $price = $this->price($request);
        if (!$curriculum || $title === '' || $content === '' || $position < 1 || $price === null) return false;
        $item->setCurriculum($curriculum); $item->setTitle($title); $item->setContent($content); $item->setPosition($position); $item->setPriceCents($price);
        $item->setVideoUrl(trim((string) $request->request->get('video_url'))); return true;
    }

    private function price(Request $request): ?int
    {
        $value = str_replace(',', '.', trim((string) $request->request->get('price')));
        return is_numeric($value) && (float) $value >= 0 ? (int) round((float) $value * 100) : null;
    }
    private function csrf(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) throw $this->createAccessDeniedException('Jeton CSRF invalide.');
    }
    private function success(string $message): Response { $this->addFlash('success', $message); return $this->redirectToRoute('app_admin_dashboard'); }
    private function failure(string $message): Response { $this->addFlash('error', $message); return $this->redirectToRoute('app_admin_dashboard'); }
}
