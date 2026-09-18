# Guide de configuration

## Variables d’environnement

Configurer ces valeurs hors de Git :

```dotenv
APP_ENV=prod
APP_SECRET=une-cle-longue-et-aleatoire
DATABASE_URL="mysql://utilisateur:mot-de-passe@127.0.0.1:3306/knowledge_learning?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
DEFAULT_URI=https://votre-domaine.example
STRIPE_SECRET_KEY=sk_test_votre_cle
MAILER_DSN="smtp://email-brevo:cle-smtp@smtp-relay.brevo.com:587?encryption=tls&auth_mode=login"
MAILER_FROM=adresse-verifiee@example.com
```

`MAILER_FROM` doit être une adresse expéditrice vérifiée dans Brevo. La clé Stripe reste une clé de test durant la démonstration.

## Initialisation

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-catalog
php bin/console app:create-admin admin@example.test "MotDePasse123"
php bin/console cache:clear --env=prod
```

Le serveur web doit servir le dossier `public/`.

## Vérifications

1. Créer un compte et activer son e-mail.
2. Tester Stripe avec une carte de test.
3. Vérifier l’accès à la leçon après le paiement.
4. Valider les leçons et contrôler la certification.
5. Vérifier la gestion du contenu sur `/admin`.
