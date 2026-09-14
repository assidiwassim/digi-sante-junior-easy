<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Administration des comptes parents : liste, fiche et suppression.
 */
#[Route('/admin/parents')]
class ParentController extends AbstractController
{
    #[Route('', name: 'admin_parents', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/parents/index.html.twig', [
            'parents' => $userRepository->findParents(),
        ]);
    }

    #[Route('/{id}', name: 'admin_parent_voir', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function voir(User $parent): Response
    {
        // L'id peut désigner n'importe quel compte : on n'affiche que les parents.
        if (!$parent->isParent()) {
            throw $this->createNotFoundException('Ce compte n\'est pas un compte parent.');
        }

        return $this->render('admin/parents/voir.html.twig', [
            'parent' => $parent,
        ]);
    }

    /** Supprime le parent et, avec lui, ses enfants, leurs comptes et leurs journaux. */
    #[Route('/{id}/supprimer', name: 'admin_parent_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimer(User $parent, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$parent->isParent()) {
            throw $this->createNotFoundException('Ce compte n\'est pas un compte parent.');
        }

        if (!$this->isCsrfTokenValid('supprimer-parent-'.$parent->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $nombreEnfants = $parent->getEnfants()->count();

        $entityManager->remove($parent);
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Le compte %s a été supprimé, avec %d profil(s) enfant.',
            $parent->getEmail(),
            $nombreEnfants,
        ));

        return $this->redirectToRoute('admin_parents');
    }
}
