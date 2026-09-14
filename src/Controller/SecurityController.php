<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\InscriptionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Connexion, déconnexion et inscription.
 *
 * La vérification du mot de passe n'est pas codée ici : Symfony s'en charge
 * grâce à `form_login` dans config/packages/security.yaml.
 */
class SecurityController extends AbstractController
{
    /** Connexion des parents et de l'administrateur (email + mot de passe). */
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Connexion des enfants (identifiant + mot de passe).
     *
     * Le formulaire est envoyé à /login, comme celui des parents. Il contient
     * un champ caché `_failure_path` pour revenir ici en cas d'erreur.
     */
    #[Route('/connexion-enfant', name: 'app_enfant_login', methods: ['GET'])]
    public function loginEnfant(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login_enfant.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /** Cette méthode n'est jamais exécutée : Symfony intercepte /logout. */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
    }

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function inscription(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $parent = new User();
        $parent->setRoles([User::ROLE_PARENT]);

        $form = $this->createForm(InscriptionType::class, $parent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motDePasse = $form->get('plainPassword')->getData();
            $parent->setPassword($passwordHasher->hashPassword($parent, $motDePasse));

            $entityManager->persist($parent);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte est créé ! Connectez-vous pour ajouter vos enfants.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/inscription.html.twig', [
            'form' => $form,
        ]);
    }
}
