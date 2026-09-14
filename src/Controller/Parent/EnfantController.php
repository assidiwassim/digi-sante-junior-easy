<?php

namespace App\Controller\Parent;

use App\Entity\Enfant;
use App\Entity\User;
use App\Form\EnfantType;
use App\Form\MotDePasseType;
use App\Repository\EnfantRepository;
use App\Repository\UserRepository;
use App\Security\EnfantVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Gestion des enfants par leur parent.
 *
 * Toute action sur un enfant précis vérifie d'abord, avec EnfantVoter, que
 * l'enfant appartient bien au parent connecté.
 */
#[Route('/parent/enfants')]
class EnfantController extends AbstractController
{
    #[Route('', name: 'parent_enfants', methods: ['GET'])]
    public function index(#[CurrentUser] User $parent, EnfantRepository $enfantRepository): Response
    {
        return $this->render('parent/enfants/index.html.twig', [
            'enfants' => $enfantRepository->findByParent($parent),
        ]);
    }

    /** Crée le profil de l'enfant ET son compte de connexion. */
    #[Route('/nouveau', name: 'parent_enfant_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(
        #[CurrentUser] User $parent,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $enfant = new Enfant();
        $enfant->setParent($parent);

        $form = $this->createForm(EnfantType::class, $enfant, ['creation' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Compte de connexion : identifiant calculé à partir du prénom,
            // mot de passe choisi par le parent.
            $compte = new User();
            $compte->setUsername($userRepository->genererUsername($enfant->getPrenom()));
            $compte->setRoles([User::ROLE_CHILD]);
            $compte->setPassword($passwordHasher->hashPassword($compte, $form->get('motDePasse')->getData()));

            $enfant->setCompte($compte);

            // Le compte est enregistré avec l'enfant (cascade « persist »).
            $entityManager->persist($enfant);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le compte de %s est créé. Son identifiant de connexion est « %s ».',
                $enfant->getPrenom(),
                $compte->getUsername(),
            ));

            return $this->redirectToRoute('parent_enfants');
        }

        return $this->render('parent/enfants/nouveau.html.twig', [
            'form' => $form,
        ]);
    }

    /** Modifie le profil (et la limite d'écran) ou le mot de passe de l'enfant. */
    #[Route('/{id}/modifier', name: 'parent_enfant_modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifier(
        Enfant $enfant,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $this->denyAccessUnlessGranted(EnfantVoter::GERER, $enfant);

        $form = $this->createForm(EnfantType::class, $enfant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('Le profil de %s a été mis à jour.', $enfant->getPrenom()));

            return $this->redirectToRoute('parent_enfants');
        }

        $formMotDePasse = $this->createForm(MotDePasseType::class);
        $formMotDePasse->handleRequest($request);

        if ($formMotDePasse->isSubmitted() && $formMotDePasse->isValid()) {
            $compte = $enfant->getCompte();
            $compte->setPassword($passwordHasher->hashPassword($compte, $formMotDePasse->get('plainPassword')->getData()));
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le mot de passe de %s a été modifié.', $enfant->getPrenom()));

            return $this->redirectToRoute('parent_enfant_modifier', ['id' => $enfant->getId()]);
        }

        return $this->render('parent/enfants/modifier.html.twig', [
            'enfant' => $enfant,
            'form' => $form,
            'formMotDePasse' => $formMotDePasse,
        ]);
    }

    /** Supprime l'enfant, son compte et ses journaux. */
    #[Route('/{id}/supprimer', name: 'parent_enfant_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimer(Enfant $enfant, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(EnfantVoter::GERER, $enfant);

        if (!$this->isCsrfTokenValid('supprimer-enfant-'.$enfant->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // Le compte et les journaux partent avec l'enfant (cascade « remove »).
        $entityManager->remove($enfant);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le profil de %s et son compte ont été supprimés.', $enfant->getPrenom()));

        return $this->redirectToRoute('parent_enfants');
    }
}
