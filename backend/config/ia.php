<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analyse assistée par modèle de langage — module 6
    |--------------------------------------------------------------------------
    |
    | Le modèle n'intervient que sur deux points : l'extraction du contenu du
    | CV et la rédaction du résumé. Le score de compatibilité (RG40) est
    | calculé en PHP par ScoringService et ne dépend jamais de ces réglages :
    | sans clé d'API, l'analyse produit un score complet et un résumé rédigé
    | à partir des seules données de la base.
    |
    */

    'cle_api' => env('ANTHROPIC_API_KEY'),

    'modele' => env('IA_MODELE', 'claude-opus-5'),

    'max_tokens' => (int) env('IA_MAX_TOKENS', 2048),

    // Délai au-delà duquel l'appel est abandonné : la file ne doit pas rester
    // bloquée sur un service indisponible.
    'timeout' => (int) env('IA_TIMEOUT', 60),

];
