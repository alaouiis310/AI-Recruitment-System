<?php

return [

    // Analyse assistée par modèle de langage : module 6

    'cle_api' => env('ANTHROPIC_API_KEY'),

    'modele' => env('IA_MODELE', 'claude-opus-5'),

    'max_tokens' => (int) env('IA_MAX_TOKENS', 2048),

    // Délai au-delà duquel l'appel est abandonné.
    'timeout' => (int) env('IA_TIMEOUT', 60),

];
