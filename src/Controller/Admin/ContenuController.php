<?php

namespace App\Controller\Admin;

use App\Entity\ContenuBienEtre;
use App\Form\ContenuBienEtreType;
use App\Repository\ContenuBienEtreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Administration de la bibliothèque de contenus : liste, ajout, modification,
 * suppression. Réservé à ROLE_ADMIN par access_control (security.yaml).
 */
#[Route('/admin')]
class ContenuController extends AbstractController
{
    /** L'administration s'ouvre sur la liste des contenus. */
    #[Route('', name: 'admin_accueil', methods: ['GET'])]
    public function accueil(): Response
    {
        return $this->redirectToRoute('admin_contenus');
    }

    #[Route('/contenus', name: 'admin_contenus', methods: ['GET'])]
    public function index(ContenuBienEtreRepository $contenuRepository): Response
    {
        return $this->render('admin/contenus/index.html.twig', [
            'contenus' => $contenuRepository->findTousTries(),
        ]);
    }

    #[Route('/contenus/nouveau', name: 'admin_contenu_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contenu = new ContenuBienEtre();

        $form = $this->createForm(ContenuBienEtreType::class, $contenu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contenu);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le contenu « %s » a été ajouté.', $contenu->getTitre()));

            return $this->redirectToRoute('admin_contenus');
        }

        return $this->render('admin/contenus/formulaire.html.twig', [
            'form' => $form,
            'titrePage' => 'Nouveau contenu',
            'bouton' => 'Ajouter le contenu',
        ]);
    }

    #[Route('/contenus/{id}/modifier', name: 'admin_contenu_modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifier(ContenuBienEtre $contenu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContenuBienEtreType::class, $contenu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le contenu « %s » a été mis à jour.', $contenu->getTitre()));

            return $this->redirectToRoute('admin_contenus');
        }

        return $this->render('admin/contenus/formulaire.html.twig', [
            'form' => $form,
            'titrePage' => 'Modifier un contenu',
            'bouton' => 'Enregistrer les modifications',
        ]);
    }

    #[Route('/contenus/{id}/supprimer', name: 'admin_contenu_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimer(ContenuBienEtre $contenu, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-contenu-'.$contenu->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($contenu);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le contenu « %s » a été supprimé.', $contenu->getTitre()));

        return $this->redirectToRoute('admin_contenus');
    }
}
