<?php

return [
    // General API responses
    'success' => 'Succès',
    'error' => 'Une erreur s\'est produite',
    'validation_failed' => 'Échec de la validation',
    'not_found' => 'Ressource non trouvée',
    'internal_server_error' => 'Erreur interne du serveur',
    'unauthorized' => 'Accès non autorisé',
    'forbidden' => 'Accès interdit',
    'too_many_requests' => 'Trop de demandes',

    // Resource operations
    'created' => 'Ressource créée avec succès',
    'updated' => 'Ressource mise à jour avec succès',
    'deleted' => 'Ressource supprimée avec succès',
    'retrieved' => 'Ressource récupérée avec succès',

    // Specific resources
    'resources' => [
        'quote' => [
            'created' => 'Devis créé avec succès',
            'updated' => 'Devis mis à jour avec succès',
            'not_found' => 'Devis non trouvé',
            'retrieved' => 'Devis récupéré avec succès',
            'calculated' => 'Devis calculé avec succès',
            'invalid_parameters' => 'Paramètres invalides pour le calcul du devis',
            'no_routes_found' => 'Aucune route trouvée pour les emplacements spécifiés',
            'rate_not_available' => 'Tarif non disponible pour cette route',
        ],
        'rate' => [
            'created' => 'Tarif créé avec succès',
            'updated' => 'Tarif mis à jour avec succès',
            'not_found' => 'Tarif non trouvé',
            'retrieved' => 'Tarif récupéré avec succès',
            'deleted' => 'Tarif supprimé avec succès',
            'invalid_zone' => 'Zone invalide spécifiée',
            'overlapping_zones' => 'Les zones de tarifs ne peuvent pas se chevaucher',
        ],
        'location' => [
            'created' => 'Emplacement créé avec succès',
            'updated' => 'Emplacement mis à jour avec succès',
            'not_found' => 'Emplacement non trouvé',
            'retrieved' => 'Emplacement récupéré avec succès',
            'deleted' => 'Emplacement supprimé avec succès',
            'invalid_coordinates' => 'Coordonnées invalides fournies',
        ],
        'zone' => [
            'created' => 'Zone créée avec succès',
            'updated' => 'Zone mise à jour avec succès',
            'not_found' => 'Zone non trouvée',
            'retrieved' => 'Zone récupérée avec succès',
            'deleted' => 'Zone supprimée avec succès',
            'invalid_geometry' => 'Géométrie de zone invalide',
        ],
        'vehicle_type' => [
            'created' => 'Type de véhicule créé avec succès',
            'updated' => 'Type de véhicule mis à jour avec succès',
            'not_found' => 'Type de véhicule non trouvé',
            'retrieved' => 'Type de véhicule récupéré avec succès',
            'deleted' => 'Type de véhicule supprimé avec succès',
        ],
        'city' => [
            'created' => 'Ville créée avec succès',
            'updated' => 'Ville mise à jour avec succès',
            'not_found' => 'Ville non trouvée',
            'retrieved' => 'Ville récupérée avec succès',
            'deleted' => 'Ville supprimée avec succès',
        ],
    ],

    // Data validation messages
    'validation' => [
        'required' => 'Le champ :attribute est requis',
        'email' => 'Le :attribute doit être une adresse e-mail valide',
        'unique' => 'Le :attribute a déjà été pris',
        'min' => 'Le :attribute doit avoir au moins :min caractères',
        'max' => 'Le :attribute ne peut pas avoir plus de :max caractères',
        'numeric' => 'Le :attribute doit être un nombre',
        'integer' => 'Le :attribute doit être un entier',
        'boolean' => 'Le champ :attribute doit être vrai ou faux',
        'date' => 'Le :attribute n\'est pas une date valide',
        'in' => 'Le :attribute sélectionné est invalide',
        'exists' => 'Le :attribute sélectionné n\'existe pas',
        'coordinates' => 'Le :attribute doit être des coordonnées valides',
        'geometry' => 'Le :attribute doit être une géométrie valide',
    ],

    // Business logic messages
    'business' => [
        'invalid_route' => 'Route invalide spécifiée',
        'distance_calculation_failed' => 'Échec du calcul de distance',
        'no_available_vehicles' => 'Aucun véhicule disponible pour cette route',
        'price_calculation_error' => 'Erreur de calcul de prix',
        'zone_overlap_detected' => 'Chevauchement de zones détecté',
        'location_outside_service_area' => 'L\'emplacement est en dehors de la zone de service',
    ],

    // Pagination
    'pagination' => [
        'showing' => 'Affichage de :from à :to sur :total résultats',
        'no_results' => 'Aucun résultat trouvé',
        'per_page_limit' => 'Maximum :limit éléments par page',
    ],

    // Cache and performance
    'cache' => [
        'cleared' => 'Cache vidé avec succès',
        'hit' => 'Données récupérées du cache',
        'miss' => 'Données non trouvées dans le cache',
    ],
];