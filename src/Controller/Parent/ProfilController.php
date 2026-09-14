<?php

namespace App\Controller\Parent;

use App\Entity\User;
use App\Form\MotDePasseType;
use App\Form\ProfilParentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Profil du parent : ses informations et son mot de passe.
 *
 * Deux formulaires sur la même page : chacun a son propre nom
 * (`profil_parent` et `mot_de_passe`), donc handleRequest() ne traite que
 * celui qui a été envoyé.
 */
class ProfilController extends AbstractController
{
    #[Route('/parent/profil', name: 'parent_profil', methods: ['GET', 'POST'])]
    public function profil(
        #[CurrentUser] User $parent,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $formProfil = $this->createForm(ProfilParentType::class, $parent);
        $formProfil->handleRequest($request);

        if ($formProfil->isSubmitted() && $formProfil->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('parent_profil');
        }

        if ($formProfil->isSubmitted()) {
            // Saisie invalide : le formulaire a déjà modifié l'objet User en
            // mémoire. Or c'est l'utilisateur connecté : un email vide ou faux
            // le déconnecterait. On recharge donc ses vraies valeurs.
            $entityManager->refresh($parent);
        }

        $formMotDePasse = $this->createForm(MotDePasseType::class);
        $formMotDePasse->handleRequest($request);

        if ($formMotDePasse->isSubmitted() && $formMotDePasse->isValid()) {
            $motDePasse = $formMotDePasse->get('plainPassword')->getData();
            $parent->setPassword($passwordHasher->hashPassword($parent, $motDePasse));
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié.');

            return $this->redirectToRoute('parent_profil');
        }

        return $this->render('parent/profil.html.twig', [
            'form' => $formProfil,
            'formMotDePasse' => $formMotDePasse,
        ]);
    }
}
