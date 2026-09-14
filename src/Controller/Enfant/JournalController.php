<?php

namespace App\Controller\Enfant;

use App\Entity\DouleurZone;
use App\Entity\Enfant;
use App\Entity\JournalEntree;
use App\Entity\User;
use App\Form\JournalDouleursType;
use App\Form\JournalEcransType;
use App\Repository\JournalEntreeRepository;
use App\Service\ConseilService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Journal quotidien en 2 étapes :
 *   1. temps d'écran  -> gardé en session
 *   2. douleurs       -> le journal complet est enregistré en base
 *
 * Rien n'est écrit en base avant la fin de l'étape 2 : si l'enfant abandonne,
 * aucun journal à moitié rempli ne reste enregistré.
 */
#[Route('/enfant/journal')]
class JournalController extends AbstractController
{
    /** Clé de session où l'étape 1 range les minutes d'écran. */
    private const SESSION_ECRANS = 'journal_ecrans';

    /** « Mon journal » : le formulaire, ou les conseils si c'est déjà fait. */
    #[Route('', name: 'enfant_journal', methods: ['GET'])]
    public function demarrer(
        #[CurrentUser] User $user,
        Request $request,
        JournalEntreeRepository $journalRepository,
    ): Response {
        if ($journalRepository->findAujourdhui($this->getEnfant($user))) {
            return $this->redirectToRoute('enfant_journal_conseils');
        }

        $request->getSession()->remove(self::SESSION_ECRANS);

        return $this->redirectToRoute('enfant_journal_etape1');
    }

    #[Route('/etape/1', name: 'enfant_journal_etape1', methods: ['GET', 'POST'])]
    public function etape1(
        #[CurrentUser] User $user,
        Request $request,
        JournalEntreeRepository $journalRepository,
    ): Response {
        $enfant = $this->getEnfant($user);

        if ($journalRepository->findAujourdhui($enfant)) {
            return $this->redirectToRoute('enfant_journal_conseils');
        }

        // Si l'enfant revient de l'étape 2, on réaffiche ses valeurs.
        $session = $request->getSession();
        $form = $this->createForm(JournalEcransType::class, $session->get(self::SESSION_ECRANS));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->set(self::SESSION_ECRANS, $form->getData());

            return $this->redirectToRoute('enfant_journal_etape2');
        }

        return $this->render('enfant/journal/etape1.html.twig', [
            'enfant' => $enfant,
            'form' => $form,
        ]);
    }

    #[Route('/etape/2', name: 'enfant_journal_etape2', methods: ['GET', 'POST'])]
    public function etape2(
        #[CurrentUser] User $user,
        Request $request,
        JournalEntreeRepository $journalRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $enfant = $this->getEnfant($user);

        if ($journalRepository->findAujourdhui($enfant)) {
            return $this->redirectToRoute('enfant_journal_conseils');
        }

        // L'étape 1 doit avoir été faite avant.
        $session = $request->getSession();
        $ecrans = $session->get(self::SESSION_ECRANS);
        if (null === $ecrans) {
            return $this->redirectToRoute('enfant_journal_etape1');
        }

        $form = $this->createForm(JournalDouleursType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $journal = new JournalEntree();
            $journal->setEnfant($enfant);
            $journal->setEcranTv((int) $ecrans['ecranTv']);
            $journal->setEcranOrdinateur((int) $ecrans['ecranOrdinateur']);
            $journal->setEcranSmartphone((int) $ecrans['ecranSmartphone']);
            $journal->setEcranTablette((int) $ecrans['ecranTablette']);
            $journal->setEcranConsole((int) $ecrans['ecranConsole']);
            $journal->setEcranAutre((int) $ecrans['ecranAutre']);

            // Le champ caché contient du JSON : {"cou": 3, "yeux": 2}.
            // Il vient du navigateur, donc on revérifie chaque valeur.
            $douleurs = json_decode((string) $form->get('douleurs')->getData(), true);

            if (\is_array($douleurs)) {
                foreach ($douleurs as $zone => $intensite) {
                    $zoneConnue = isset(DouleurZone::ZONES[$zone]);
                    $intensiteValide = \is_int($intensite) && $intensite >= 1 && $intensite <= 5;

                    if ($zoneConnue && $intensiteValide) {
                        $journal->addDouleur(new DouleurZone($zone, $intensite));
                    }
                }
            }

            // Les douleurs sont enregistrées avec le journal (cascade « persist »).
            $entityManager->persist($journal);
            $entityManager->flush();

            $session->remove(self::SESSION_ECRANS);

            return $this->redirectToRoute('enfant_journal_conseils');
        }

        return $this->render('enfant/journal/etape2.html.twig', [
            'enfant' => $enfant,
            'form' => $form,
            'zones' => DouleurZone::ZONES,
        ]);
    }

    /** Écran de fin : récapitulatif du jour et conseils personnalisés. */
    #[Route('/conseils', name: 'enfant_journal_conseils', methods: ['GET'])]
    public function conseils(
        #[CurrentUser] User $user,
        JournalEntreeRepository $journalRepository,
        ConseilService $conseilService,
    ): Response {
        $enfant = $this->getEnfant($user);
        $journal = $journalRepository->findAujourdhui($enfant);

        if (null === $journal) {
            return $this->redirectToRoute('enfant_journal');
        }

        return $this->render('enfant/journal/conseils.html.twig', [
            'enfant' => $enfant,
            'journal' => $journal,
            'conseils' => $conseilService->getConseils($journal),
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
