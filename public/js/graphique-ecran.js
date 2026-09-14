/**
 * Graphique « temps d'écran » (Chart.js), utilisé par l'accueil de l'enfant
 * et par le tableau de bord du parent.
 *
 *   canvasId  identifiant de la balise <canvas>
 *   donnees   { labels: ['08/09', …], minutes: [95, …] }
 *   limite    limite quotidienne en minutes (ligne rouge en pointillés)
 *   libelles  { ecran: 'Temps d\'écran (min)', limite: 'Limite fixée (min)' }
 */
function afficherGraphiqueEcran(canvasId, donnees, limite, libelles) {
    Chart.defaults.font.family = 'Nunito, system-ui, sans-serif';
    Chart.defaults.font.weight = 700;
    Chart.defaults.color = '#5b6b85';

    new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: {
            labels: donnees.labels,
            datasets: [
                {
                    label: libelles.ecran,
                    data: donnees.minutes,
                    borderColor: '#0E7C7B',
                    backgroundColor: 'rgba(14, 124, 123, 0.15)',
                    borderWidth: 3,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#0E7C7B'
                },
                {
                    label: libelles.limite,
                    data: donnees.labels.map(function () { return limite; }),
                    borderColor: '#DC2626',
                    borderDash: [6, 6],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Minutes' } } },
            plugins: { legend: { position: 'bottom' } }
        }
    });
}
