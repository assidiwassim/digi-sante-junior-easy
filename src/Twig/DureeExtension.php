<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * Filtre Twig « duree » : affiche des minutes de façon lisible.
 *
 *   {{ 45|duree }}   ->  45 min
 *   {{ 120|duree }}  ->  2 h
 *   {{ 150|duree }}  ->  2 h 30
 */
class DureeExtension
{
    #[AsTwigFilter('duree')]
    public static function formater(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);
        $heures = intdiv($minutes, 60);
        $reste = $minutes % 60;

        if (0 === $heures) {
            return $reste.' min';
        }

        if (0 === $reste) {
            return $heures.' h';
        }

        return sprintf('%d h %02d', $heures, $reste);
    }
}
