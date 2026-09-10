<?php
namespace App\Command;

use App\Entity\Curriculum;
use App\Entity\Lesson;
use App\Entity\Theme;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-catalog', description: 'Charge le catalogue beta Knowledge Learning.')]
final class SeedCatalogCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager) { parent::__construct(); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->entityManager->getRepository(Theme::class)->count([]) > 0) { $output->writeln('Catalogue déjà chargé.'); return Command::SUCCESS; }
        $catalogue = [
            'Musique' => [['Initiation à la guitare', 5000, [['Découverte de l’instrument', 2600], ['Les accords et les gammes', 2600]]], ['Initiation au piano', 5000, [['Découverte de l’instrument', 2600], ['Les accords et les gammes', 2600]]]],
            'Informatique' => [['Initiation au développement web', 6000, [['Les langages HTML et CSS', 3200], ['Dynamiser votre site avec JavaScript', 3200]]]],
            'Jardinage' => [['Initiation au jardinage', 3000, [['Les outils du jardinier', 1600], ['Jardiner avec la lune', 1600]]]],
            'Cuisine' => [['Initiation à la cuisine', 4400, [['Les modes de cuisson', 2300], ['Les saveurs', 2300]]], ['Initiation à l’art du dressage culinaire', 4800, [['Mettre en œuvre le style dans l’assiette', 2600], ['Harmoniser un repas à quatre plats', 2600]]]],
        ];
        foreach ($catalogue as $themeName => $curricula) {
            $theme = new Theme($themeName); $this->entityManager->persist($theme);
            foreach ($curricula as [$title, $price, $lessons]) {
                $curriculum = new Curriculum($theme, $title, $price); $this->entityManager->persist($curriculum);
                foreach ($lessons as $index => [$lessonTitle, $lessonPrice]) $this->entityManager->persist(new Lesson($curriculum, $lessonTitle, $index + 1, $lessonPrice));
            }
        }
        $this->entityManager->flush(); $output->writeln('Catalogue beta chargé.'); return Command::SUCCESS;
    }
}
