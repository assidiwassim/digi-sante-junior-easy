<?php

namespace App\Controller\Parent;

use App\Entity\User;
use App\Repository\EnfantRepository;
use App\Repository\JournalEntreeRepository;
use App\Service\ConseilService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Tableau de bord du parent : le suivi d'un enfant à la fois.
 * Réservé à ROLE_PARENT par access_control (security.yaml).
 */
class TableauDeBordController extends AbstractController
{
    #[Route('/parent', name: 'parent_dashboard', methods: ['GET'])]
    public function index(
        #[CurrentUser] User $parent,
        Request $request,
        EnfantRepository $enfantRepository,
        JournalEntreeRepository $journalRepository,
        ConseilService $conseilService,
    ): Response {
        $enfants = $enfantRepository->findByParent($parent);

        if ([] === $enfants) {
            return $this->render('parent/dashboard_vide.html.twig');
        }

        // Enfant choisi avec ?enfant=<id>. On ne cherche QUE parmi les enfants
        // du parent : un identifiant étranger affiche simplement le premier.
        $enfant = $enfants[0];
        foreach ($enfants as $candidat) {
            if ($candidat->getId() === $request->query->getInt('enfant')) {
                $enfant = $candidat;
            }
        }

        $periode = 30 === $request->query->getInt('periode') ? 30 : 7;
        $journalDuJour = $journalRepository->findAujourdhui($enfant);

        return $this->render('parent/dashboard.html.twig', [
            'enfants' => $enfants,
            'enfant' => $enfant,
            'journalDuJour' => $journalDuJour,
            // Les mêmes conseils que ceux reçus par l'enfant
            'conseils' => $journalDuJour ? $conseilService->getConseils($journalDuJour) : [],
            'periode' => $periode,
            'graphique' => $journalRepository->getGraphiqueEcran($enfant, $periode),
        ]);
    }
}
