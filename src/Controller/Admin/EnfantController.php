<?php

namespace App\Controller\Admin;

use App\Entity\Enfant;
use App\Repository\EnfantRepository;
use App\Repository\JournalEntreeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Administration des profils enfants : liste, fiche et suppression.
 */
#[Route('/admin/enfants')]
class EnfantController extends AbstractController
{
    #[Route('', name: 'admin_enfants', methods: ['GET'])]
    public function index(EnfantRepository $enfantRepository): Response
    {
        return $this->render('admin/enfants/index.html.twig', [
            'enfants' => $enfantRepository->findAllAvecParent(),
        ]);
    }

    #[Route('/{id}', name: 'admin_enfant_voir', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function voir(Enfant $enfant, JournalEntreeRepository $journalRepository): Response
    {
        return $this->render('admin/enfants/voir.html.twig', [
            'enfant' => $enfant,
            'journaux' => $journalRepository->findDerniers($enfant, 10),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_enfant_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimer(Enfant $enfant, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-enfant-'.$enfant->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // Le compte et les journaux partent avec l'enfant (cascade « remove »).
        $entityManager->remove($enfant);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le profil de %s et son compte ont été supprimés.', $enfant->getNomComplet()));

        return $this->redirectToRoute('admin_enfants');
    }
}
