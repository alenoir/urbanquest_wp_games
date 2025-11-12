# Liste d'optimisations ACF PRO pour Urban Quest

## 📋 Vue d'ensemble

Ce document liste les optimisations possibles pour améliorer les performances, la maintenabilité et l'utilisation des fonctionnalités ACF PRO dans votre projet.

---

## 🔴 CRITIQUES - À faire en priorité

### 1. **Utiliser les relations bidirectionnelles**

**Problème actuel :** Toutes les relations sont unidirectionnelles, ce qui nécessite de parcourir tous les posts pour trouver les enfants.

**Solution ACF PRO :** Activer `bidirectional: 1` et définir `bidirectional_target` pour chaque relation.

**Exemple :**

```json
// Dans acf.json pour le champ "city" du jeu
{
  "key": "field_690dc3f29d95a",
  "type": "relationship",
  "bidirectional": 1,
  "bidirectional_target": ["field_ville_games"] // Nouveau champ à créer sur "ville"
}
```

**Bénéfices :**

- Requêtes inverses automatiques (trouver tous les jeux d'une ville sans parcourir tous les jeux)
- Performance améliorée (requêtes SQL optimisées)
- Code simplifié dans les templates

**Impact :** ⚡⚡⚡ Très élevé sur les performances

---

### 2. **Optimiser les requêtes avec ACF Query Filters**

**Problème actuel :** Les templates récupèrent TOUS les posts puis filtrent en PHP :

```php
$all_games = get_posts(['post_type' => 'game', 'posts_per_page' => -1]);
foreach ($all_games as $game) {
    $city_id = extract_acf_relationship_id(get_field('city', $game->ID));
    if ($city_id == $ville_id) { ... }
}
```

**Solution ACF PRO :** Utiliser `meta_query` avec les champs ACF :

```php
$games = get_posts([
    'post_type' => 'game',
    'meta_query' => [
        [
            'key' => 'city',
            'value' => '"' . $ville_id . '"', // Format sérialisé ACF
            'compare' => 'LIKE'
        ]
    ]
]);
```

**Ou mieux encore avec ACF PRO :** Utiliser `acf/load_value` et `acf/format_value` pour optimiser.

**Bénéfices :**

- Requêtes SQL directes au lieu de PHP
- Performance 10-100x meilleure selon le nombre de posts
- Moins de mémoire utilisée

**Impact :** ⚡⚡⚡ Très élevé sur les performances

---

### 3. **Centraliser les fonctions helper dupliquées**

**Problème actuel :** Les fonctions `extract_acf_relationship_id()` et `get_game_display_data()` sont dupliquées dans chaque template.

**Solution :** Elles existent déjà dans `functions.php` mais ne sont pas utilisées partout.

**Action :** Remplacer toutes les occurrences dans les templates par les fonctions centralisées :

- `extract_acf_relationship_id()` → `urbanquest_extract_acf_relationship_id()`
- `get_game_display_data()` → `urbanquest_get_game_display_data()`

**Impact :** ⚡⚡ Maintenabilité et cohérence

---

## 🟡 IMPORTANTES - À faire rapidement

### 4. **Utiliser return_format: "id" au lieu de "object"**

**Problème actuel :** `return_format: "object"` charge tous les objets WP_Post en mémoire.

**Solution :** Changer en `return_format: "id"` pour les relations simples (max: 1).

**Bénéfices :**

- Moins de mémoire utilisée
- Requêtes plus rapides
- Code plus simple (directement un ID au lieu d'un objet)

**Note :** Garder "object" uniquement si vous avez besoin des propriétés du post (title, excerpt, etc.) directement.

**Impact :** ⚡⚡ Performance mémoire

---

### 5. **Remplacer les champs image multiples par un Repeater**

**Problème actuel :** 5 champs séparés pour les images "pourquoi choisir" :

- `pourquoi_choisir_image_1` à `pourquoi_choisir_image_5`

**Solution ACF PRO :** Utiliser un champ `repeater` :

```json
{
  "key": "field_pourquoi_choisir_images",
  "label": "Images composition",
  "name": "pourquoi_choisir_images",
  "type": "repeater",
  "sub_fields": [
    {
      "key": "field_pourquoi_choisir_image_item",
      "name": "image",
      "type": "image"
    }
  ],
  "min": 0,
  "max": 5
}
```

**Bénéfices :**

- Code plus propre dans les templates
- Plus flexible (peut avoir 3 ou 7 images selon les besoins)
- Meilleure UX dans l'admin WordPress

**Impact :** ⚡⚡ Maintenabilité et flexibilité

---

### 6. **Remplacer les champs feature multiples par un Repeater**

**Problème actuel :** 3 groupes de champs pour les fonctionnalités :

- `pourquoi_choisir_feature_1_icone`, `pourquoi_choisir_feature_1_titre`, `pourquoi_choisir_feature_1_description`
- Idem pour feature_2 et feature_3

**Solution ACF PRO :** Un seul repeater :

```json
{
  "key": "field_pourquoi_choisir_features",
  "type": "repeater",
  "sub_fields": [
    { "name": "icone", "type": "text" },
    { "name": "titre", "type": "text" },
    { "name": "description", "type": "textarea" }
  ],
  "min": 1,
  "max": 5
}
```

**Bénéfices :** Même que #5

**Impact :** ⚡⚡ Maintenabilité

---

### 7. **Utiliser des Groupes de champs pour mieux organiser**

**Problème actuel :** Beaucoup de champs au même niveau dans le groupe "Jeu".

**Solution ACF PRO :** Créer des sous-groupes avec `group` field :

- Groupe "SEO" (rating_value, review_count)
- Groupe "Prix" (prix, prix_original, titre_offre, payment_url)
- Groupe "Jauges" (jauge_fun, jauge_histoire, etc.)

**Bénéfices :**

- Meilleure organisation dans l'admin
- Code plus structuré dans les templates
- Réutilisation possible des groupes

**Impact :** ⚡ UX Admin

---

## 🟢 AMÉLIORATIONS - À considérer

### 8. **Ajouter des champs calculés avec ACF PRO**

**Problème actuel :** Certaines valeurs sont calculées dans les templates (ex: durée formatée).

**Solution ACF PRO :** Utiliser `acf/load_value` pour créer des champs calculés :

- `duree_formatee` (calculé depuis `duree_minutes`)
- `nombre_joueurs_formate` (calculé depuis min/max)

**Bénéfices :**

- Logique centralisée
- Réutilisable partout
- Moins de code dans les templates

**Impact :** ⚡ Maintenabilité

---

### 9. **Utiliser des Field Groups conditionnels**

**Problème actuel :** Tous les champs sont toujours visibles.

**Solution ACF PRO :** Utiliser `conditional_logic` pour afficher/masquer selon le contexte :

- Afficher "titre_offre" seulement si "prix_original" est rempli
- Masquer certaines jauges si non utilisées

**Bénéfices :**

- UX admin améliorée
- Moins d'erreurs de saisie

**Impact :** ⚡ UX Admin

---

### 10. **Optimiser les requêtes de jeux liés**

**Problème actuel :** La fonction `urbanquest_get_related_games()` fait plusieurs boucles imbriquées.

**Solution :** Utiliser une seule requête avec `meta_query` complexe ou créer une fonction de cache.

**Exemple optimisé :**

```php
function urbanquest_get_related_games_optimized($current_game_id, $ville_id, $departement_id, $region_id, $limit = 6) {
    // Utiliser transients pour cache
    $cache_key = "related_games_{$current_game_id}_{$ville_id}_{$departement_id}_{$region_id}";
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    // Requête optimisée avec meta_query
    $games = get_posts([
        'post_type' => 'game',
        'post__not_in' => [$current_game_id],
        'posts_per_page' => $limit,
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'city',
                'value' => '"' . $ville_id . '"',
                'compare' => 'LIKE'
            ],
            // Ajouter d'autres conditions selon la hiérarchie
        ],
        'orderby' => 'rand' // ou 'date', 'title', etc.
    ]);

    set_transient($cache_key, $games, HOUR_IN_SECONDS);
    return $games;
}
```

**Impact :** ⚡⚡ Performance

---

### 11. **Ajouter des champs de cache pour les relations**

**Problème actuel :** Les relations sont recalculées à chaque chargement de page.

**Solution :** Créer des champs ACF cachés qui stockent les IDs calculés :

- Sur "ville" : champ `_cached_games_ids` (array d'IDs)
- Sur "departement" : champ `_cached_villes_ids`
- Mettre à jour via `acf/save_post` hook

**Bénéfices :**

- Requêtes ultra-rapides
- Pas besoin de parcourir toute la hiérarchie

**Impact :** ⚡⚡⚡ Performance (si beaucoup de données)

---

### 12. **Utiliser des Field Groups pour les sections répétitives**

**Problème actuel :** Les sections "terrain de jeu" et "jeu unique" sont similaires.

**Solution ACF PRO :** Créer un groupe réutilisable avec `clone` field :

```json
{
  "key": "field_section_template",
  "type": "group",
  "sub_fields": [
    { "name": "titre", "type": "text" },
    { "name": "image", "type": "image" },
    { "name": "description", "type": "wysiwyg" }
  ]
}
```

Puis cloner pour chaque section.

**Impact :** ⚡ Maintenabilité

---

## 📊 Résumé des priorités

| #   | Optimisation                 | Impact Performance | Impact Maintenabilité | Priorité        |
| --- | ---------------------------- | ------------------ | --------------------- | --------------- |
| 1   | Relations bidirectionnelles  | ⚡⚡⚡             | ⚡⚡                  | 🔴 CRITIQUE     |
| 2   | ACF Query Filters            | ⚡⚡⚡             | ⚡⚡                  | 🔴 CRITIQUE     |
| 3   | Centraliser fonctions helper | ⚡                 | ⚡⚡⚡                | 🔴 CRITIQUE     |
| 4   | return_format: "id"          | ⚡⚡               | ⚡                    | 🟡 IMPORTANT    |
| 5   | Repeater pour images         | ⚡                 | ⚡⚡                  | 🟡 IMPORTANT    |
| 6   | Repeater pour features       | ⚡                 | ⚡⚡                  | 🟡 IMPORTANT    |
| 7   | Groupes de champs            | ⚡                 | ⚡⚡                  | 🟡 IMPORTANT    |
| 10  | Optimiser requêtes liées     | ⚡⚡               | ⚡                    | 🟢 AMÉLIORATION |
| 11  | Champs de cache              | ⚡⚡⚡             | ⚡                    | 🟢 AMÉLIORATION |

---

## 🚀 Plan d'action recommandé

### Phase 1 (Semaine 1) - Critiques

1. ✅ Centraliser les fonctions helper (3)
2. ✅ Activer les relations bidirectionnelles (1)
3. ✅ Optimiser les requêtes avec meta_query (2)

### Phase 2 (Semaine 2) - Importantes

4. ✅ Changer return_format en "id" (4)
5. ✅ Remplacer champs multiples par Repeaters (5, 6)
6. ✅ Organiser avec Groupes (7)

### Phase 3 (Semaine 3+) - Améliorations

7. ✅ Optimiser requêtes liées (10)
8. ✅ Ajouter cache si nécessaire (11)

---

## 📝 Notes importantes

- **Tester chaque changement** sur un environnement de staging avant production
- **Sauvegarder** le fichier `acf.json` avant chaque modification
- **Documenter** les changements dans le code
- **Mesurer** les performances avant/après avec Query Monitor

---

## 🔗 Ressources ACF PRO

- [Documentation Relations bidirectionnelles](https://www.advancedcustomfields.com/resources/bidirectional-relationships/)
- [Documentation Repeater Field](https://www.advancedcustomfields.com/resources/repeater/)
- [Documentation Group Field](https://www.advancedcustomfields.com/resources/group/)
- [Optimisation des requêtes ACF](https://www.advancedcustomfields.com/resources/querying-relationship-fields/)
