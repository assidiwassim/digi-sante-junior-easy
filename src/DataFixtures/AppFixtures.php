<?php

namespace App\DataFixtures;

use App\Entity\ContenuBienEtre;
use App\Entity\DouleurZone;
use App\Entity\Enfant;
use App\Entity\JournalEntree;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données de démonstration : un admin, deux parents, quatre enfants avec
 * 15 jours de journaux chacun, et la bibliothèque de contenus.
 *
 * Chargement : php bin/console doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Mêmes nombres « aléatoires » à chaque chargement
        mt_srand(20240912);

        $this->chargerContenus($manager);

        $admin = $this->creerUtilisateur('admin@digisante.local', 'admin123', User::ROLE_ADMIN, 'France', 'Paris');
        $parent = $this->creerUtilisateur('parent@digisante.local', 'parent123', User::ROLE_PARENT, 'France', 'Lyon');
        $sofia = $this->creerUtilisateur('sofia@digisante.local', 'parent123', User::ROLE_PARENT, 'Belgique', 'Bruxelles');
        $manager->persist($admin);
        $manager->persist($parent);
        $manager->persist($sofia);

        // Léa : beaucoup d'écran, douleurs au cou et aux yeux
        $lea = $this->creerEnfant($manager, $parent, 'Léa', 'Martin', 'lea', 'renard', 'today -12 years -4 months', 120);
        $this->creerJournaux($manager, $lea, 'beaucoup_ecran');

        // Tom : écran modéré, parfois mal aux poignets
        $tom = $this->creerEnfant($manager, $parent, 'Tom', 'Martin', 'tom', 'dragon', 'today -9 years -8 months', 90);
        $this->creerJournaux($manager, $tom, 'poignets');

        // Noah : douleurs aux épaules et au dos
        $noah = $this->creerEnfant($manager, $sofia, 'Noah', 'Dubois', 'noah', 'pingouin', 'today -13 years -7 months', 150);
        $this->creerJournaux($manager, $noah, 'epaules');

        // Inès : journées équilibrées
        $ines = $this->creerEnfant($manager, $sofia, 'Inès', 'Dubois', 'ines', 'licorne', 'today -11 years -2 months', 120);
        $this->creerJournaux($manager, $ines, 'equilibre');

        $manager->flush();
    }

    private function creerUtilisateur(string $email, string $motDePasse, string $role, string $pays, string $ville): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        $user->setPays($pays);
        $user->setVille($ville);
        $user->setPassword($this->passwordHasher->hashPassword($user, $motDePasse));

        return $user;
    }

    private function creerEnfant(
        ObjectManager $manager,
        User $parent,
        string $prenom,
        string $nom,
        string $username,
        string $avatar,
        string $dateNaissance,
        int $limite,
    ): Enfant {
        // Tous les enfants de démonstration ont le mot de passe « enfant123 »
        $compte = new User();
        $compte->setUsername($username);
        $compte->setRoles([User::ROLE_CHILD]);
        $compte->setPassword($this->passwordHasher->hashPassword($compte, 'enfant123'));

        $enfant = new Enfant();
        $enfant->setParent($parent);
        $enfant->setCompte($compte);
        $enfant->setPrenom($prenom);
        $enfant->setNom($nom);
        $enfant->setAvatar($avatar);
        $enfant->setDateNaissance(new \DateTimeImmutable($dateNaissance));
        $enfant->setMaxMinutesJour($limite);

        $manager->persist($enfant);

        return $enfant;
    }

    /** 15 journaux : d'il y a 14 jours jusqu'à aujourd'hui. */
    private function creerJournaux(ObjectManager $manager, Enfant $enfant, string $profil): void
    {
        for ($joursAvant = 14; $joursAvant >= 0; --$joursAvant) {
            $journal = new JournalEntree();
            $journal->setEnfant($enfant);
            $journal->setDate(new \DateTimeImmutable('today -'.$joursAvant.' days'));

            match ($profil) {
                'beaucoup_ecran' => $this->remplirBeaucoupEcran($journal, $joursAvant),
                'poignets' => $this->remplirPoignets($journal, $joursAvant),
                'epaules' => $this->remplirEpaules($journal, $joursAvant),
                default => $this->remplirEquilibre($journal, $joursAvant),
            };

            $manager->persist($journal);
        }
    }

    private function remplirBeaucoupEcran(JournalEntree $journal, int $joursAvant): void
    {
        $journal->setEcranTv(mt_rand(30, 75));
        $journal->setEcranOrdinateur(mt_rand(45, 110));
        $journal->setEcranSmartphone(mt_rand(50, 120));
        $journal->setEcranTablette(mt_rand(20, 60));
        $journal->setEcranConsole(mt_rand(0, 45));
        $journal->setEcranAutre(mt_rand(0, 20));

        if (0 === $joursAvant % 2) {
            $journal->addDouleur(new DouleurZone('cou', mt_rand(3, 5)));
        }
        if (0 === $joursAvant % 3) {
            $journal->addDouleur(new DouleurZone('yeux', mt_rand(2, 4)));
        }
    }

    private function remplirPoignets(JournalEntree $journal, int $joursAvant): void
    {
        $journal->setEcranTv(mt_rand(20, 60));
        $journal->setEcranOrdinateur(mt_rand(10, 40));
        $journal->setEcranSmartphone(mt_rand(15, 50));
        $journal->setEcranTablette(mt_rand(20, 70));
        $journal->setEcranConsole(mt_rand(20, 80));

        if (0 === $joursAvant % 4) {
            $journal->addDouleur(new DouleurZone('poignet', mt_rand(1, 3)));
        }
    }

    private function remplirEpaules(JournalEntree $journal, int $joursAvant): void
    {
        $journal->setEcranTv(mt_rand(30, 70));
        $journal->setEcranOrdinateur(mt_rand(30, 90));
        $journal->setEcranSmartphone(mt_rand(20, 70));
        $journal->setEcranTablette(mt_rand(10, 50));
        $journal->setEcranConsole(mt_rand(0, 40));

        if ($joursAvant <= 1) {
            $journal->addDouleur(new DouleurZone('epaule', mt_rand(3, 4)));
        }
        if (0 === $joursAvant % 5) {
            $journal->addDouleur(new DouleurZone('dos', mt_rand(2, 4)));
        }
    }

    private function remplirEquilibre(JournalEntree $journal, int $joursAvant): void
    {
        $journal->setEcranTv(mt_rand(15, 40));
        $journal->setEcranOrdinateur(mt_rand(10, 35));
        $journal->setEcranSmartphone(mt_rand(10, 30));
        $journal->setEcranTablette(mt_rand(0, 25));
        $journal->setEcranConsole(mt_rand(0, 20));

        if (7 === $joursAvant) {
            $journal->addDouleur(new DouleurZone('main', 2));
        }
    }

    private function chargerContenus(ObjectManager $manager): void
    {
        // [type, titre, texte, lien, règle du moteur]
        $contenus = [
            [
                'exercice',
                'La règle du 20-20-20',
                "Toutes les 20 minutes passées devant un écran :\n"
                ."1. Lève les yeux de ton écran.\n"
                ."2. Regarde un objet situé à environ 6 mètres (20 pieds) — la fenêtre, le fond du couloir…\n"
                ."3. Garde le regard dessus pendant 20 secondes en clignant tranquillement des yeux.\n\n"
                .'Tes yeux se détendent et la fatigue visuelle diminue nettement.',
                null,
                ContenuBienEtre::DECLENCHEUR_20_20_20,
            ],
            [
                'video',
                'Étirements du cou et des épaules en 3 minutes',
                "Une routine tout en douceur à faire assis sur ta chaise :\n"
                ."• Penche lentement la tête vers l'épaule droite, compte jusqu'à 10, puis change de côté.\n"
                ."• Fais rouler tes épaules vers l'arrière, 10 fois.\n"
                ."• Rentre le menton pour étirer la nuque, compte jusqu'à 10.\n\n"
                .'À faire chaque fois que tu sens ton cou tout raide.',
                'https://www.youtube.com/results?search_query=etirement+cou+epaules+enfant',
                ContenuBienEtre::DECLENCHEUR_ETIREMENT,
            ],
            [
                'exercice',
                'Le yoga des yeux',
                "Cinq mouvements pour réveiller tes yeux fatigués :\n"
                ."• Regarde en haut, puis en bas — 10 fois, sans bouger la tête.\n"
                ."• Regarde à gauche, puis à droite — 10 fois.\n"
                ."• Dessine un grand cercle avec ton regard, dans un sens puis dans l'autre.\n"
                ."• Cligne très vite des yeux pendant 15 secondes.\n"
                ."• Frotte tes mains l'une contre l'autre, puis pose-les en coupe sur tes paupières fermées pendant 30 secondes.",
                null,
                ContenuBienEtre::DECLENCHEUR_YOGA_YEUX,
            ],
            [
                'exercice',
                'Le défi des 7 minutes',
                "Sept mouvements, 30 secondes chacun, 10 secondes de pause entre chaque :\n"
                ."1. Sauts avec écart (jumping jacks)\n2. Chaise contre le mur\n3. Pompes sur les genoux\n"
                ."4. Abdominaux\n5. Montées sur une marche\n6. Squats\n7. Gainage sur les coudes\n\n"
                .'Aucun matériel nécessaire : juste toi et un peu de place !',
                'https://www.youtube.com/results?search_query=defi+7+minutes+enfant+sport',
                'defi_sport',
            ],
            [
                'fiche',
                'Bien dormir quand on a beaucoup d\'écran',
                "La lumière des écrans envoie à ton cerveau le message « il fait encore jour ».\n\n"
                ."• Arrête les écrans au moins 1 heure avant de dormir.\n"
                ."• Laisse tablette et téléphone hors de ta chambre pour la nuit.\n"
                ."• Lis quelques pages ou écoute une histoire à la place.\n"
                .'• Couche-toi à peu près à la même heure chaque soir, même le week-end.',
                null,
                'sommeil',
            ],
            [
                'fiche',
                'Bien s\'installer devant un écran',
                "Ta position compte autant que la durée !\n\n"
                ."• Le haut de l'écran doit être au niveau de tes yeux.\n"
                ."• Garde le dos bien droit contre le dossier, les pieds à plat au sol.\n"
                ."• Laisse une distance d'environ un bras entre tes yeux et l'écran.\n"
                .'• Évite de jouer allongé ou avec la tête penchée : c\'est ce qui fait mal au cou.',
                null,
                'posture',
            ],
            [
                'fiche',
                'Combien de temps d\'écran par jour ?',
                "Les spécialistes recommandent, pour les 8-14 ans, environ 2 heures d'écran "
                ."de loisir par jour au maximum — les devoirs sur ordinateur ne comptent pas.\n\n"
                .'L\'idée n\'est pas de tout supprimer, mais de garder du temps pour bouger, '
                .'voir tes amis, dormir et t\'ennuyer un peu (oui, c\'est utile !).',
                null,
                null,
            ],
            [
                'quiz',
                'Quiz : es-tu un pro des écrans ?',
                "1. Après combien de minutes d'écran faut-il faire une pause pour les yeux ?\n"
                ."   a) 20 min · b) 60 min · c) 2 h\n\n"
                ."2. À quelle distance regarder pendant une pause visuelle ?\n"
                ."   a) 20 cm · b) 6 mètres · c) le plus près possible\n\n"
                ."3. Combien de minutes d'activité physique par jour sont conseillées ?\n"
                ."   a) 15 min · b) 30 min · c) 60 min\n\n"
                .'Réponses : 1-a, 2-b, 3-c. Trois bonnes réponses ? Tu es incollable ! 🏆',
                null,
                null,
            ],
            [
                'quiz',
                'Quiz : mon corps et les écrans',
                "1. Pourquoi le cou fait-il mal après une longue session ?\n"
                ."   a) Parce qu'on penche la tête en avant · b) Parce que l'écran est trop lumineux\n\n"
                ."2. Que veut dire « fatigue visuelle » ?\n"
                ."   a) Avoir sommeil · b) Avoir les yeux secs, qui piquent ou qui voient flou\n\n"
                ."3. Que faire quand les poignets tirent ?\n"
                ."   a) Continuer · b) Faire des petits cercles avec les poignets et faire une pause\n\n"
                .'Réponses : 1-a, 2-b, 3-b.',
                null,
                null,
            ],
            [
                'glossaire',
                'Fatigue visuelle',
                'Ce sont tes yeux qui te disent « stop ». Les signes : yeux qui piquent, qui pleurent '
                .'ou qui sont secs, vision floue, mal de tête. C\'est très fréquent après une longue '
                .'période devant un écran, parce qu\'on cligne trois fois moins des yeux ! '
                .'Bonne nouvelle : ça disparaît avec des pauses régulières.',
                null,
                null,
            ],
            [
                'glossaire',
                'Lumière bleue',
                'Une lumière émise par les écrans. Le soir, elle trompe ton cerveau qui croit '
                .'qu\'il fait encore jour et retarde l\'endormissement. C\'est pour ça qu\'on '
                .'conseille d\'éteindre les écrans une heure avant le coucher.',
                null,
                null,
            ],
            [
                'glossaire',
                'Sédentarité',
                'C\'est le fait de rester assis ou allongé très longtemps sans bouger. '
                .'Bouger au moins 60 minutes par jour aide ton cœur, tes muscles, ton sommeil '
                .'et même ton humeur. Marcher, danser dans ta chambre ou jouer dehors, tout compte !',
                null,
                null,
            ],
            [
                'video',
                'Réveil en douceur : 5 minutes pour bien démarrer',
                "Une routine du matin pour se réveiller sans écran :\n"
                ."• Étire-toi comme un chat, bras au-dessus de la tête.\n"
                ."• Fais 10 petits sauts sur place.\n"
                ."• Bois un grand verre d'eau.\n"
                .'• Ouvre les rideaux : la lumière du jour aide ton corps à se réveiller.',
                'https://www.youtube.com/results?search_query=reveil+musculaire+enfant',
                null,
            ],
            [
                'fiche',
                'Les écrans et les émotions',
                "Parfois on va sur un écran parce qu'on s'ennuie, qu'on est triste ou énervé.\n\n"
                ."C'est normal ! Mais l'écran ne fait pas disparaître l'émotion : il la met en pause.\n\n"
                .'La prochaine fois, essaie d\'abord d\'en parler à quelqu\'un en qui tu as confiance, '
                .'de dessiner, de sortir ou d\'écouter de la musique. Puis compare comment tu te sens.',
                null,
                null,
            ],
            [
                'exercice',
                'La pause « secoue-toi »',
                "Toutes les heures d'écran, lance un chrono d'une minute et enchaîne :\n"
                ."• 10 secondes à secouer les mains et les bras\n"
                ."• 10 secondes de rotation des épaules\n"
                ."• 20 secondes à marcher dans la pièce\n"
                ."• 20 secondes à regarder par la fenêtre\n\n"
                .'Une minute suffit à relancer la machine !',
                null,
                'posture',
            ],
        ];

        foreach ($contenus as [$type, $titre, $texte, $url, $declencheur]) {
            $contenu = new ContenuBienEtre();
            $contenu->setType($type);
            $contenu->setTitre($titre);
            $contenu->setContenu($texte);
            $contenu->setUrl($url);
            $contenu->setDeclencheur($declencheur);

            $manager->persist($contenu);
        }
    }
}
