<?php

namespace App\Service;

use App\Entity\JournalEntree;
use App\Repository\ContenuBienEtreRepository;
use App\Twig\DureeExtension;

/**
 * Moteur de conseils : lit un journal et renvoie les conseils à afficher.
 *
 * Utilisé par l'écran « conseils » de l'enfant ET par le tableau de bord du
 * parent : c'est la seule raison pour laquelle ce code vit dans un service.
 *
 * Règles :
 *  1. temps d'écran ≥ 2 h             -> règle du 20-20-20
 *  2. temps d'écran > limite parent   -> conseil de pause (remplace la règle 1)
 *  3. douleur au cou ou aux épaules ≥ 3 -> étirements
 *  4. douleur aux yeux                -> yoga des yeux
 *
 * Le texte des exercices vient de la base (ContenuBienEtre) : l'administrateur
 * peut le modifier sans toucher au code.
 *
 * Chaque conseil est un simple tableau :
 *   ['titre' => …, 'message' => …, 'emoji' => …, 'couleur' => 'orange'|'vert', 'contenu' => ?ContenuBienEtre]
 */
class ConseilService
{
    /**
     * Seuil du conseil 20-20-20, atteint dès 2 h d'écran : c'est aussi le
     * moment où la jauge passe à l'orange (JournalEntree::niveauPourMinutes).
     */
    private const SEUIL_ECRAN_MINUTES = 120;
    private const SEUIL_DOULEUR = 3;

    public function __construct(private ContenuBienEtreRepository $contenuRepository)
    {
    }

    /** @return list<array{titre: string, message: string, emoji: string, couleur: string, contenu: mixed}> */
    public function getConseils(JournalEntree $journal): array
    {
        $conseils = [];
        $total = $journal->getTotalEcran();
        $limite = $journal->getEnfant()->getMaxMinutesJour();

        // Règles 1 et 2 : un seul conseil sur les écrans, jamais deux.
        if ($total > $limite) {
            $conseils[] = [
                'titre' => 'Tu as dépassé ta limite d\'écran',
                'message' => sprintf(
                    'Aujourd\'hui : %s d\'écran pour une limite de %s. Ce n\'est pas grave, mais demain essaie de faire une pause plus tôt ! 💪',
                    DureeExtension::formater($total),
                    DureeExtension::formater($limite),
                ),
                'emoji' => '📵',
                'couleur' => 'orange',
                'contenu' => $this->contenuRepository->findPremierPourDeclencheur('20-20-20'),
            ];
        } elseif ($total >= self::SEUIL_ECRAN_MINUTES) {
            $conseils[] = [
                'titre' => 'Repose tes yeux avec le 20-20-20',
                'message' => sprintf(
                    'Tu as passé %s devant un écran. Toutes les 20 minutes, regarde quelque chose à 20 pieds (environ 6 mètres) pendant 20 secondes.',
                    DureeExtension::formater($total),
                ),
                'emoji' => '👁️',
                'couleur' => 'orange',
                'contenu' => $this->contenuRepository->findPremierPourDeclencheur('20-20-20'),
            ];
        }

        // Règles 3 et 4 : les douleurs
        $douleurCouOuEpaule = false;
        $douleurYeux = false;

        foreach ($journal->getDouleurs() as $douleur) {
            if (\in_array($douleur->getZone(), ['cou', 'epaule'], true) && $douleur->getIntensite() >= self::SEUIL_DOULEUR) {
                $douleurCouOuEpaule = true;
            }

            if ('yeux' === $douleur->getZone()) {
                $douleurYeux = true;
            }
        }

        if ($douleurCouOuEpaule) {
            $conseils[] = [
                'titre' => 'Détends ton cou et tes épaules',
                'message' => 'Tu as signalé une douleur au niveau du haut du corps. Voici une vidéo d\'étirements tout doux à faire assis ou debout.',
                'emoji' => '🧘',
                'couleur' => 'orange',
                'contenu' => $this->contenuRepository->findPremierPourDeclencheur('etirement_cervical'),
            ];
        }

        if ($douleurYeux) {
            $conseils[] = [
                'titre' => 'Un peu de yoga des yeux',
                'message' => 'Tes yeux sont fatigués. Fais-les bouger doucement de haut en bas, de gauche à droite, puis frotte tes mains et pose-les sur tes paupières fermées.',
                'emoji' => '👀',
                'couleur' => 'orange',
                'contenu' => $this->contenuRepository->findPremierPourDeclencheur('yoga_yeux'),
            ];
        }

        // Aucune règle déclenchée : on félicite l'enfant.
        if ([] === $conseils) {
            $conseils[] = [
                'titre' => 'Super journée !',
                'message' => 'Tes écrans sont bien maîtrisés et ton corps va bien. Continue comme ça ! 🌈',
                'emoji' => '🎉',
                'couleur' => 'vert',
                'contenu' => null,
            ];
        }

        return $conseils;
    }
}
