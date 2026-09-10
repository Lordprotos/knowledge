# Knowledge Learning

Prototype Symfony 7 d’une plateforme de formation. Le projet utilise une architecture MVC : les contrôleurs HTTP appellent des services métier, Doctrine fournit les entités et les dépôts, et Twig rend les vues.

## Démarrage

MariaDB doit être lancé sur le port 3307. La connexion locale est dans `.env.local` (ce fichier ne doit jamais être envoyé sur Git). Exécuter `php bin/console doctrine:migrations:migrate` puis `php bin/console app:seed-catalog`.

Les routes principales sont `/`, `/inscription`, `/connexion` et `/certifications`. Les paiements sont une simulation sandbox : aucun moyen de paiement réel n’est utilisé.

## Modèle physique

`users` gère les comptes, rôles et activation. `themes`, `curricula` et `lessons` forment le catalogue. `purchases` porte les droits d’accès. `lesson_progress` mémorise les validations et `certifications` les certifications par thème. Les tables métier possèdent `created_at`, `updated_at`, `created_by` et `updated_by`; les relations sont protégées par des clés étrangères InnoDB.

## Sécurité et tests

Les mots de passe sont hachés avec le hasher Symfony. L’inscription vérifie longueur, majuscule, minuscule et chiffre; les formulaires de compte utilisent des jetons CSRF. Les tests se lancent avec `php bin/phpunit`.

Un administrateur se crée sans identifiants codés en dur avec `php bin/console app:create-admin admin@example.test 'MotDePasse123'`. Il peut ensuite accéder à `/admin`.

Les livrables de soutenance sont disponibles dans [`docs/`](docs/) : [modèle physique](docs/MPD.md), [guide de déploiement](docs/DEPLOIEMENT.md) et [support de présentation](docs/PRESENTATION.md).
