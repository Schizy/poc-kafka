<?php

function handleSignal($signal) {
    echo "\n🛑 Signal reçu: $signal\n";
    echo "Mais on fait semblant de rien et on continue 🥸";
}

// Enregistrer le handler pour SIGINT (Ctrl+C)
pcntl_signal(SIGINT, 'handleSignal');

// Un handler pour SINGTERM (kill pid)
pcntl_signal(SIGTERM, 'handleSignal');

echo "Appuyez sur Ctrl+C pour tester...\n";
while (true) {
    echo ".";
    sleep(1);
    // IMPORTANT: Cette ligne traite les signaux en attente
    pcntl_signal_dispatch();
}
