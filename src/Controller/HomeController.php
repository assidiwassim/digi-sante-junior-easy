<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * Page d'accueil publique.
     *
     * Un utilisateur connecté est redirigé vers son espace. Comme la connexion
     * renvoie toujours ici (voir security.yaml), c'est aussi cette méthode qui
     * choisit où envoyer chacun après s'être connecté.
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->isGranted(User::ROLE_ADMIN)) {
            return $this->redirectToRoute('admin_contenus');
        }

        if ($this->isGranted(User::ROLE_PARENT)) {
            return $this->redirectToRoute('parent_dashboard');
        }

        if ($this->isGranted(User::ROLE_CHILD)) {
            return $this->redirectToRoute('enfant_accueil');
        }

        return $this->render('home/index.html.twig');
    }
}
