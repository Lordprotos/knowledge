# Guide de mise en ligne

## 1. Préparer la base MariaDB

Créer une base MariaDB encodée en `utf8mb4` et un compte applicatif limité à cette base. Ne jamais mettre le mot de passe dans Git.

```sql
CREATE DATABASE knowledge_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'knowledge_learning_app'@'localhost' IDENTIFIED BY 'un-mot-de-passe-fort';
GRANT ALL PRIVILEGES ON knowledge_learning.* TO 'knowledge_learning_app'@'localhost';
```

## 2. Définir les variables d’environnement

Sur l’hébergeur, renseigner :

```dotenv
APP_ENV=prod
APP_SECRET=une-cle-secrete-longue-et-aleatoire
DATABASE_URL="mysql://knowledge_learning_app:mot-de-passe@127.0.0.1:3306/knowledge_learning?serverVersion=11.5.2-MariaDB&charset=utf8mb4"
MAILER_DSN="smtp://utilisateur:mot-de-passe@smtp.exemple.fr:587"
DEFAULT_URI=https://votre-domaine.example
```

## 3. Installer et initialiser l’application

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-catalog
php bin/console app:create-admin admin@example.test 'MotDePasse123'
php bin/console cache:clear --env=prod
```

Le serveur web doit utiliser le répertoire `public/` comme racine du site.

## 4. Vérifier avant la remise

1. Créer un compte client.
2. Ouvrir l’e-mail reçu et activer le compte.
3. Se connecter, acheter une leçon puis un cursus.
4. Valider les leçons et contrôler la certification.
5. Se connecter comme administrateur et ouvrir `/admin`.
