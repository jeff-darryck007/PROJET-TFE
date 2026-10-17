# Mise en ligne sur alwaysdata

Ce guide met en ligne **tout le projet** (API Symfony, base de données, application Vue, photos) sur l'offre gratuite d'alwaysdata, à une seule adresse :

| Adresse | Servi par |
|---|---|
| `https://COMPTE.alwaysdata.net/` et toutes les pages (`/login`, `/annonce/12`…) | l'application Vue |
| `https://COMPTE.alwaysdata.net/api/...` | Symfony |
| `https://COMPTE.alwaysdata.net/upload/...` | les photos des annonces |

Le site et l'API étant à la même adresse, il n'y a pas de problème de CORS.
Dans tout le guide, remplace **`COMPTE`** par ton nom de compte alwaysdata.

---

## 1. Créer le compte

1. Inscris-toi sur [alwaysdata.com](https://www.alwaysdata.com) et choisis l'offre **gratuite**.
2. Le **nom de compte** que tu choisis devient ton adresse : `COMPTE.alwaysdata.net`. Choisis-le bien (ex. `partagegratuit`).

## 2. Créer la base de données

Dans l'administration : **Bases de données → MySQL**.

1. Crée un **utilisateur** (ex. `COMPTE`) avec un mot de passe, et **note-le**.
2. Crée une **base** nommée `COMPTE_tfe` (alwaysdata impose le préfixe `COMPTE_`) et donne les droits à cet utilisateur.
3. Note la **version de MariaDB** affichée sur cette page (ex. `11.4`) : elle servira à l'étape 5.

Le serveur de la base est `mysql-COMPTE.alwaysdata.net`.

## 3. Se connecter en SSH

Dans l'administration : **Accès distant → SSH**. Vérifie que la connexion par mot de passe est activée.

Depuis **Git Bash** (ou PowerShell) sur ton PC :

```bash
ssh COMPTE@ssh-COMPTE.alwaysdata.net
```

Vérifie la version de PHP (il faut **8.2 ou plus**) :

```bash
php -v
```

## 4. Récupérer le code

Toujours en SSH :

```bash
git clone https://github.com/jeff-darryck007/PROJET-TFE.git
cd PROJET-TFE
chmod +x deploy.sh
```

> Si le dépôt GitHub est **privé**, GitHub demandera un identifiant : utilise ton nom d'utilisateur et un **token** (GitHub → Settings → Developer settings → Personal access tokens) à la place du mot de passe.

## 5. Configurer le backend

Génère deux valeurs aléatoires (une pour `APP_SECRET`, une pour `JWT_PASSPHRASE`) :

```bash
php -r 'echo bin2hex(random_bytes(16)), "\n", bin2hex(random_bytes(16)), "\n";'
```

Crée le fichier de configuration de production :

```bash
nano backend-TFE/.env.local
```

et colle ceci en remplaçant les valeurs en MAJUSCULES :

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=PREMIERE_VALEUR_GENEREE
DEFAULT_URI=https://COMPTE.alwaysdata.net

DATABASE_URL="mysql://UTILISATEUR:MOT_DE_PASSE_BDD@mysql-COMPTE.alwaysdata.net:3306/COMPTE_tfe?serverVersion=mariadb-11.4.0&charset=utf8mb4"

JWT_PASSPHRASE=DEUXIEME_VALEUR_GENEREE

CORS_ALLOW_ORIGIN='^https://COMPTE\.alwaysdata\.net$'

# Envoi des emails (mot de passe oublié) — voir étape 9
MAILER_DSN=smtp://ADRESSE%40gmail.com:CODE_APPLICATION@smtp.gmail.com:587
MAILER_FROM=ADRESSE@gmail.com
```

- Dans `serverVersion`, mets la version de MariaDB notée à l'étape 2 (ex. `mariadb-10.11.0`, `mariadb-11.4.0`).
- Si ton mot de passe de base contient des caractères spéciaux (`@`, `:`, `/`, `#`…), encode-les (`@` → `%40`, `#` → `%23`…).
- Enregistre avec `Ctrl+O`, `Entrée`, puis quitte avec `Ctrl+X`.

> ⚠️ Ne réutilise **pas** la passphrase `LIONEL` du fichier `.env` : elle est visible sur GitHub.

## 6. Installer le backend

```bash
cd ~/PROJET-TFE/backend-TFE
composer install --no-dev --optimize-autoloader --no-interaction
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear
```

- `lexik:jwt:generate-keypair` crée les clés de connexion (`config/jwt/*.pem`) avec ta nouvelle passphrase.
- La migration crée toutes les tables dans une base vide.

## 7. Configurer le site

Dans l'administration : **Web → Sites**, modifie le site existant (ou ajoute-en un) :

| Champ | Valeur |
|---|---|
| Adresse | `COMPTE.alwaysdata.net` |
| Type | **PHP** |
| Version de PHP | la même que `php -v` (8.2 ou plus) |
| Répertoire racine | `/PROJET-TFE/backend-TFE/public` |

Le fichier `backend-TFE/public/.htaccess` (déjà dans le projet) se charge de répartir les adresses entre Symfony, les photos et Vue.

> **Alternative sans passer par l'administration** (c'est ce qui est utilisé sur `darryck.alwaysdata.net`) : le site par défaut sert le dossier `~/www`. On le remplace par un lien vers le dossier `public` du projet :
>
> ```bash
> cd ~ && mv www www.origine && ln -s PROJET-TFE/backend-TFE/public www
> ```

## 8. Mettre en ligne le frontend Vue

Le frontend doit être **compilé**, puis copié dans `backend-TFE/public/`.

**Option A — compiler sur ton PC (recommandé, le serveur gratuit a peu de mémoire)**

Dans **Git Bash** sur ton PC :

```bash
cd /c/wamp64/www/PROJET-TFE/frontend-TFE
npm run build
scp -r dist/* COMPTE@ssh-COMPTE.alwaysdata.net:PROJET-TFE/backend-TFE/public/
```

**Option B — compiler sur le serveur**

En SSH : `cd ~/PROJET-TFE && ./deploy.sh --frontend`. Si la compilation échoue par manque de mémoire, utilise l'option A.

> En production, le frontend appelle l'API à la même adresse que le site (`frontend-TFE/.env.production`). En local, `npm run dev` continue d'utiliser `http://127.0.0.1:8000` (`frontend-TFE/.env.development`).

## 9. Envoi des emails

Pour que le code « mot de passe oublié » arrive chez **tous** les utilisateurs, utilise Gmail :

1. Active la **validation en deux étapes** sur ton compte Google.
2. Crée un **mot de passe d'application** sur [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords) (16 lettres, à coller sans espaces).
3. Mets-le dans `MAILER_DSN` (étape 5), en écrivant le `@` de ton adresse `%40`.
4. Applique le changement : `php bin/console cache:clear`.

## 10. Créer le compte administrateur

1. Inscris-toi normalement sur `https://COMPTE.alwaysdata.net/register`.
2. Dans l'administration alwaysdata, ouvre **phpMyAdmin** (Bases de données → MySQL), base `COMPTE_tfe`, onglet **SQL** :

```sql
UPDATE users SET roles = '["admin"]' WHERE email = 'ton.adresse@exemple.be';
```

3. Déconnecte-toi puis reconnecte-toi sur le site.

## 11. Vérifier

- `https://COMPTE.alwaysdata.net/` affiche l'accueil.
- `https://COMPTE.alwaysdata.net/api/anouncements` affiche du JSON.
- Recharger la page sur `/login` ne donne pas d'erreur 404.
- Connexion, publication d'une annonce avec photo, messagerie et mot de passe oublié fonctionnent.

---

## Mettre à jour le site plus tard

1. Sur ton PC : commit et `git push` comme d'habitude.
2. En SSH :

```bash
cd ~/PROJET-TFE
./deploy.sh
```

3. Si tu as modifié le frontend : refais l'**étape 8**.

## En cas de problème

| Symptôme | Cause probable | Solution |
|---|---|---|
| Erreur 500 | Erreur PHP / config | Lire la fin de `~/admin/logs/apache/apache.log` (en production, Symfony y écrit ses erreurs) |
| Page blanche à l'accueil | Frontend pas copié | Refaire l'étape 8 |
| Toujours « non connecté » (401) | Clés JWT absentes ou passphrase modifiée | Refaire `lexik:jwt:generate-keypair` (étape 6) |
| « Échec de l'envoi de l'email » | `MAILER_DSN` incorrect | Vérifier l'étape 9 |
| Erreur de connexion à la base | `DATABASE_URL` incorrect | Vérifier hôte, utilisateur, mot de passe, nom `COMPTE_tfe` |

## Reprendre les données de ton PC (facultatif)

Par défaut, la base en ligne démarre **vide**. Pour y copier tes annonces et comptes locaux :

1. Dans phpMyAdmin de WAMP (`http://localhost/phpmyadmin`) : base `tfe` → **Exporter** (format SQL).
2. Dans phpMyAdmin d'alwaysdata, sur une base `COMPTE_tfe` **vide** (ne lance pas l'étape 6 « migrations » dans ce cas) : **Importer** le fichier.
3. Les photos sont déjà sur le serveur : elles font partie du dépôt Git.
