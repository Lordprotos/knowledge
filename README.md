# Knowledge Learning

Plateforme e-learning réalisée avec Symfony 7, Doctrine, MariaDB et Twig.

## Fonctions

- inscription avec activation par e-mail ;
- catalogue organisé par thèmes, parcours et leçons ;
- paiement par Stripe Checkout en mode test ;
- accès aux contenus achetés, suivi de progression et certifications ;
- administration des thèmes, parcours et leçons ;
- logo, favicon et lecteur vidéo si une URL vidéo est renseignée.

## Démarrage local

Configurer `DATABASE_URL`, `STRIPE_SECRET_KEY`, `MAILER_DSN` et `MAILER_FROM` dans `.env.local`, puis exécuter :

```bash
php bin/console doctrine:migrations:migrate
php bin/console app:seed-catalog
php bin/console app:create-admin admin@example.test "MotDePasse123"
php -S 127.0.0.1:8000 -t public
```

Le site est disponible à `http://127.0.0.1:8000`.

## Comptes de démonstration

Créer un client de test vérifié avec :

```bash
php bin/console app:create-test-user test@example.test "TestPassword123"
```

## Paiement, e-mail et tests

Stripe utilise une clé `sk_test_…`. Brevo utilise une clé SMTP et une adresse expéditrice validée. Ne jamais versionner ces secrets.

```bash
php bin/phpunit
```

Les documents du projet sont dans [`docs/`](docs/) : [MPD](docs/MPD.md), [déploiement](docs/DEPLOIEMENT.md) et [présentation](docs/PRESENTATION.md).
