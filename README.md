# Knowledge Learning

Plateforme e-learning réalisée avec Symfony 7.4, Doctrine, MariaDB et Twig pour l'entreprise fictive Knowledge. Les contenus pédagogiques sont des démonstrations et les paiements utilisent le mode test de Stripe.

## Fonctions

- inscription avec activation par e-mail ;
- catalogue organisé par thèmes, parcours et leçons ;
- paiement par Stripe Checkout en mode test ;
- accès aux contenus achetés, suivi de progression et certifications ;
- achats conservés sur le compte et affichés dans « Mes certifications », section « Mes formations achetées » ;
- remplacement des boutons d'achat par des liens d'accès et contrôle serveur avant tout nouveau paiement d'un contenu acquis ;
- administration des thèmes, parcours et leçons ;
- logo, favicon et lecteur vidéo si une URL vidéo est renseignée.

## Prérequis et installation

- PHP 8.2 minimum et Composer 2 ; extensions `ctype`, `iconv`, `mbstring`, `pdo_mysql` et `pdo_sqlite` pour les tests.
- MariaDB démarré, avec un utilisateur autorisé à créer et modifier la base.
- Git, une clé Stripe de test pour les achats et un serveur SMTP pour les e-mails d'activation.

```bash
git clone https://github.com/Lordprotos/knowledge.git
cd knowledge
composer install
composer check-platform-reqs
```

Créer `.env.local` à la racine :

```dotenv
APP_ENV=dev
APP_SECRET=remplacer_par_une_valeur_aleatoire
DATABASE_URL="mysql://utilisateur:mot_de_passe@127.0.0.1:3306/knowledge_learning?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
DEFAULT_URI=http://127.0.0.1:8000
STRIPE_SECRET_KEY=sk_test_A_REMPLACER
MAILER_DSN="smtp://utilisateur:mot_de_passe@smtp.example.com:587"
MAILER_FROM=formation@example.com
```

Adapter les identifiants, le port et la version MariaDB au serveur : `3306` et `10.11.0-MariaDB` sont des exemples, pas des valeurs imposées. Encoder les caractères réservés des identifiants dans les URL de connexion. Ces valeurs sont fictives ; l'envoi du lien d'activation nécessite un SMTP fonctionnel.

## Démarrage local

Configurer `DATABASE_URL`, `STRIPE_SECRET_KEY`, `MAILER_DSN` et `MAILER_FROM` dans `.env.local`, puis exécuter :

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console app:seed-catalog
php bin/console app:create-admin admin@example.test "MotDePasse123"
php -S 127.0.0.1:8000 -t public
```

Le site est disponible à `http://127.0.0.1:8000`.

Laisser le terminal du serveur ouvert ; `Ctrl+C` l'arrête. La commande `app:seed-catalog` initialise une base sans thème et ne recharge pas un catalogue déjà présent.

| Page | URL locale | Accès |
|---|---|---|
| Catalogue | http://127.0.0.1:8000/ | Public |
| Inscription | http://127.0.0.1:8000/inscription | Public |
| Connexion | http://127.0.0.1:8000/connexion | Public |
| Achats et certifications | http://127.0.0.1:8000/certifications | Client connecté |
| Administration | http://127.0.0.1:8000/admin | Administrateur |

Les pages `/cursus/{id}` et `/lecon/{id}` sont accessibles depuis le catalogue. Le contenu d'une leçon nécessite un achat payé de cette leçon ou de son cursus.

## Comptes de démonstration

Créer un client de test vérifié avec :

```bash
php bin/console app:create-test-user test@example.test "TestPassword123"
```

Ces commandes créent des comptes déjà activés ; aucun e-mail n'est nécessaire pour leur connexion. Les identifiants ci-dessus sont des exemples de démonstration. Si l'adresse existe déjà, la commande ne modifie pas son mot de passe.

## Paiement et progression

Après un paiement de test, revenir sur le site pour que le serveur vérifie la session Stripe et enregistre l'achat. Un cursus acheté ouvre l'accès à toutes ses leçons. La validation d'une leçon enregistre la progression puis ramène à la page du cursus.

La certification d'un thème est attribuée lorsque toutes les leçons de ses cursus sont validées. L'achat seul ne délivre pas de certificat ; la validation est déclarative, sans examen automatique.

**Limites du paiement :** aucun webhook n'est implémenté ; fermer le navigateur avant son retour peut empêcher l'enregistrement de l'achat. Le contrôle des achats existants bloque les nouveaux départs vers Stripe, mais n'annule pas les sessions de paiement ouvertes auparavant.

## Configuration des e-mails

Stripe utilise une clé `sk_test_…`. Brevo utilise une clé SMTP et une adresse expéditrice validée. Ne jamais versionner ces secrets.

Exemple Brevo à adapter dans `.env.local` :

```dotenv
MAILER_DSN="smtp://IDENTIFIANT_SMTP_ENCODE:CLE_SMTP@smtp-relay.brevo.com:587"
MAILER_FROM=formation@example.com
```

Utiliser l'identifiant SMTP fourni par Brevo et une clé **SMTP**, pas une clé API ni le mot de passe du compte. Remplacer notamment `@` par `%40` dans l'identifiant. `MAILER_FROM` correspond à l'expéditeur et peut être différent du login SMTP.

- « The mailer DSN must contain a scheme » : vérifier le préfixe `smtp://`.
- « A non-empty secret is required » pendant l'authentification SMTP : vérifier la présence du mot de passe après le séparateur `:`.
- Erreur SMTP `535` : vérifier l'identifiant et la clé auprès du prestataire.

Si le compte a été enregistré sans réception du lien, corriger la configuration puis soumettre à nouveau l'inscription avec la même adresse et le même mot de passe. Un compte non activé peut ainsi demander un nouvel envoi.

## Tests

Installer les dépendances de développement avec `composer install`, puis exécuter :

```bash
php bin/phpunit
```

Les tests fonctionnels recréent leur schéma. `.env.test` configure SQLite dans `var/` : vérifier les surcharges locales et conserver une base de test dédiée. Ces tests ne réalisent pas de paiement Stripe complet et ne valident pas les migrations MariaDB.

Pour une exécution sans envoi d'e-mail, créer `.env.test.local` avec :

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/knowledge_learning_test.db"
MAILER_DSN="null://null"
```

Vérifier également qu'aucune variable `DATABASE_URL` définie dans le terminal ne remplace cette configuration. Le transport `null://null` est réservé ici aux tests : il n'envoie aucun message.

La suite couvre notamment les comptes, l'administration, les droits d'accès après achat, la séparation des achats entre utilisateurs, la progression et le renvoi d'activation après un échec simulé. Dernière exécution complète vérifiée le 25 septembre 2026 : **13 tests, 76 assertions**. Relancer la suite après toute modification.

## Organisation du code

- `src/Controller/` : routes HTTP et coordination des actions.
- `src/Service/` : règles d'achat, de progression et intégration Stripe.
- `src/Entity/` : données métier et mapping Doctrine.
- `src/Security/` : contrôle de l'activation des comptes.
- `src/Command/` : initialisation du catalogue et création des comptes.
- `templates/` et `assets/` : vues Twig, styles et JavaScript.
- `migrations/` et `tests/` : évolution du schéma et vérifications automatisées.

## Documents de soutenance

Le dossier `docs/` est conservé localement et exclu du dépôt GitHub. Les prérequis, les instructions d'installation et de lancement ainsi que les commandes de test sont disponibles dans ce README.
