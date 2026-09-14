<?php

namespace App\Controller\Enfant;

use App\Entity\Enfant;
use App\Entity\JournalEntree;
use App\Entity\User;
use App\Form\MotDePasseType;
use App\Repository\ContenuBienEtreRepository;
use App\Repository\JournalEntreeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Espace enfant : accueil, bibliothèque et profil.
 * Réservé à ROLE_CHILD par access_control (security.yaml).
 */
#[Route('/enfant')]
class AccueilController extends AbstractController
{
    #[Route('', name: 'enfant_accueil', methods: ['GET'])]
    public function accueil(
        #[CurrentUser] User $user,
        Request $request,
        JournalEntreeRepository $journalRepository,
    ): Response {
        $enfant = $this->getEnfant($user);
        $journal = $journalRepository->findAujourdhui($enfant);

        $totalEcran = $journal ? $journal->getTotalEcran() : 0;
        $limite = $enfant->getMaxMinutesJour();

        // Période du graphique : 7 ou 30 jours
        $periode = 30 === $request->query->getInt('periode') ? 30 : 7;

        return $this->render('enfant/accueil.html.twig', [
            'enfant' => $enfant,
            'journal' => $journal,
            'totalEcran' => $totalEcran,
            'niveau' => JournalEntree::niveauPourMinutes($totalEcran),
            'pourcentage' => min(100, (int) round($totalEcran / $limite * 100)),
            'depassement' => $totalEcran > $limite,
            'periode' => $periode,
            'graphique' => $journalRepository->getGraphiqueEcran($enfant, $periode),
        ]);
    }

    #[Route('/bibliotheque', name: 'enfant_bibliotheque', methods: ['GET'])]
    public function bibliotheque(ContenuBienEtreRepository $contenuRepository): Response
    {
        return $this->render('enfant/bibliotheque.html.twig', [
            'groupes' => $contenuRepository->findGroupesParType(),
        ]);
    }

    /** Informations de l'enfant et changement de son mot de passe. */
    #[Route('/profil', name: 'enfant_profil', methods: ['GET', 'POST'])]
    public function profil(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $enfant = $this->getEnfant($user);

        $form = $this->createForm(MotDePasseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motDePasse = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $motDePasse));
            $entityManager->flush();

            $this->addFlash('success', 'Ton nouveau mot de passe est enregistré. Pense à bien le retenir !');

            return $this->redirectToRoute('enfant_profil');
        }

        return $this->render('enfant/profil.html.twig', [
            'enfant' => $enfant,
            'form' => $form,
        ]);
    }

    /** Profil enfant rattaché au compte connecté. */
    private function getEnfant(User $user): Enfant
    {
        $enfant = $user->getProfilEnfant();

        if (null === $enfant) {
            throw $this->createNotFoundException('Aucun profil enfant n\'est rattaché à ce compte.');
        }

        return $enfant;
    }
}
