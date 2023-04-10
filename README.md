# Projet ExamGenerator

Fork from [NoxFly's Php-Base-Template](https://github.com/NoxFly/php-base-template) repository.

## Liens externes

* [Figma](https://www.figma.com/file/MPQnrDMHzkpdPn3JDaf0kv/Untitled?node-id=0%3A1&t=t6sr3Z5kocohsSRC-1)
* [Backlog](https://docs.google.com/spreadsheets/d/1xUzL6Ok_dp5c1N3y5IBcbtzgdtjSnG_0A4Mo_Jr7sHo/edit)
* [Schéma BDD](https://lucid.app/lucidchart/069abdea-f68f-4ed1-a40b-8f2f54040354/edit?viewport_loc=-275%2C-93%2C2490%2C1201%2C0_0&invitationId=inv_62e11778-ab51-4421-83f7-cfd16dcb30c0)



## Technologies

technologie | version
------------|-----------
Wamp | >= 3.2
Php | 7.3, 7.4 (wo/ framework)<br>
SCSS | ^1.58.3<br>
Javascript | ES6

## Utilisation

Penser à créer la base de données MySQL dans PhpMyAdmin, via les 2 scripts situés dans `api/src/model`.

## Structuration

Chaque dossier au root du projet représentent un sous-domaine du serveur de l'application.<br>
Pour `http(s)://<dossier>.examgenerator.fr`, on aurait donc :
- `www.examgenerator.fr`
- `api.examgenerator.fr`
- `developer.examgenerator.fr`

Les dossiers `common/` et `engine/` ne sont pas des sous-domaines du serveur, ils rassemblent du code commun / factorisé des différentes applications.

Trois dossiers :
- `www/` : la webapp, client, site web. Appelle l'api du moment qu'il faut faire des requêtes qui touchent des données.
- `api/` : contient l'api, communique avec la base de données.
- `developer/` : Permet de communiquer directement avec l'API, afin de lister et tester les différents endpoints.

Les trois parties on la même structuration interne :
- `conf/`
    * `config.ini`
- `src/`
    * `controllers/`
    * `guards/`
    * `services/`
    * `templates/`
    * `Site.php`

La webapp et le developer portal contiennent en plus :
- `src/public/`
    * `asset/`
        * `css/`
        * `fonts`
        * `images/`
        * `scss/`
    * `components/`
    * `js/`
    * `views/`

L'api contient en plus :
- `src/model/`
    * Les fichiers `.sql`

Les classes `Router`, `Site` ainsi que les classes abstraites `Guard` et `Controller` sont similaires pour les trois solutions, et sont donc placés dans le dossier `engine`.

Tout ce qui peut être commun à la webapp et à l'api et propre à l'application (examgenerator) et non au moteur sont dans le dossier `common/`.

## Configuration

Possibilité de rajouter la racine du projet en tant que virtualhost pour une URL plus courte (facultatif).

Tout paramètrage se fait dans le `conf/config.ini`.

1. Pour le client :
    - l'environnement (dev / prod)
    - le nom de l'application
    - la page de template (chemin depuis le dossier `views/` avec extension)
    - le routage de la webapp

1. Pour l'API :
    - l'environnement (dev / prod)
    - le nom de l'application
    - le routage de l'api
    - la connexion à la BDD (host, port, db, username, password)

## Routage

Le système de routage a été designé de façon à ce que tout soit orienté objet et modulaire. Chaque route est déclarée dans le fichier `conf/config.ini`.

Quelle que soit la page demandée, on est forcément redirigé sur `index.php` avec l'URI comme query (.htaccess).

Pour ajouter / supprimer / modifier une route, il faut aller dans la section `[ROUTES]`.

Le pattern est le suivant : `METHOD[] = URL CONTROLLER`, où :
- `METHOD` est, au choix `GET` / `POST` / `PUT` / `DELETE` / `PATCH` / `OPTIONS` / `HEAD`.
- `URL` est l'url tapée dans le navigateur
- `CONTROLLER` est le nom du controlleur qui gère cette URL pour cette méthode.

Il est possible d'assigner le même controlleur pour la même URL ayant différentes méthodes, ou même pour plusieurs URL différentes.

Le nom du controlleur permet d'identifier le fichier et la classe de celui-ci.<br>
Par exemple :

Pour `CONTROLLER = auth`, le routeur ira chercher le fichier `src/controllers/auth.ctrl.php` et essayera d'instancier la classe `AuthController`.

Une classe de controlleur doit spécialiser la classe abstraite `Controller`.

Chaque controlleur dispose de 7 méthodes, portant le même nom que la méthode HTTP qui est écrite dans le fichier config.

Un controlleur peut appeler un middleware ou un service (pour que celui-ci effectue des opérations de modification sur le modèle) avant de renvoyer une réponse.

Un controlleur a un Guard, qui permet, à travers une méthode `canActivate`, si oui ou non, la requête est accessible pour celui qui la demande (renvoie un booléen).


## Request Pipeline

Lorsqu'une requête est effectuée, l'exécution se déroule dans cet ordre :

1. `Site` est instancié avec le fichier config. Il contient un `Router`. Il récupère le nom de la page demandée et appelle son routeur.
1. `Router` construit l'arbre de routage lorsqu'il est instancié.
Lorsque `Site` l'appelle en demandant une page, si celle-ci existe dans son arbre et qu'il gère cette méthode, alors :
    1. Il appelle son guard. Si celui-ci renvoie `true`,
    1. Il appelle son controlleur en lui passant un objet `$req` et le `Site` en tant que `$res`. Il renvoie un booléen suivant s'il a pu exécuter le controlleur ou non.
1. Le controlleur reçoit un objet requête et réponse. Il traîte la requête, et précise à `$res` le type et le contenu de la réponse. Il peut définir un status (par défaut 200, 201 pour PUT, 204 pour POST et OPTIONS), et peut également rediriger la réponse.
1. `Site` appelle une méthode abstraite (qu'il faut spécialiser) `onPageLoaded($result)` contenant le résultat du routeur.




## Status et réponses

* `res->render(view, data?NULL)`, où :
    - `view` est le chemin depuis le dossier `public/views/` du fichier de la page à afficher dans le template, sans son extension.
    - `data`, un objet qui est accessible depuis la vue (facultatif).
* `res->send(str)` : permet de renvoyer une chaîne de caractères.
* `res->json(obj)` : permet de renvoyer un json.

Il est possible de préciser le status de la réponse (par défaut 200) :

```php
$res->status(301)->render('errors/401');
```

Il est également possible de rediriger la réponse :

```php
$res->redirect('/');
```

Aucun code qui suit cette instruction ne sera exécuté.


## La vue

La balise `<main>` a une classe `.page-<uri>`.<br>
Par exemple, pour `localhost/board/profile` : `.page-board-profile`.

Pour accéder aux données transmises par le controlleur et le routeur :

```php
$this; // Correspond à la classe Site

$this->req; // objet req passé par le routeur
    // une partie de cet objet :
    $this->req['params']; // les paramètres dans l'url (exemple: /user/:id)
    $this->req['query']; // les query params de l'url (exemple : /user?id=1)
    $this->req['body']; // les paramètres du body si la méthode le permet, vide sinon
$this->data; // objet passé par le controlleur via la méthode `render($file, $data)`

$this->getRouter();
$this->getDatabase();

$this->includePage($path); // inclut le contenu d'une page depuis le dossier `public/views/`

$this->includeComponent($path); // inclut un composant d'une page depuis le dossier `public/components/`
$this->requireComponent($path);

$this->includeCSS($path); // inclut une feuille de style depuis le dossier `public/asset/css/`
$this->includeJS($path); // inclut un script depuis le dossier `public/js/`
```

Pour les liens :

```php
// on veut localhost/
<a href="<?=$this->url('/')?>">Accueil</a>
// on veut localhost/404
<a href="<?=$this->url('/404')?>">404</a>
```

## L'API

Le client peut interroger l'API comme suit :
```php
$site = new Site(); // dans index.php

try {
    $data = $site->api()->fetch('GET', '/sous-url/depuis/api', $body);
}
catch(ApiException $e) {
    // possibilités
    $e->getStatus(); // 4XX/5XX
    $e->getMessage(); // string
    $e->getDetails(); // string | NULL
    $e->sendJSON($res);
}
```

En Javascript :
```js
import { GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD } from './ajax';

(async () => {
    // une requête qui commence par '/api/' est détecté et l'url est formattée pour interroger l'uri de l'api
    // signature pour les requêtes GET, DELETE, OPTIONS, HEAD :
    // FCT(url, responseType='json', contentType='json', additionalHeaders={});
    // signature pour les requêtes POST, PUT, PATCH :
    // FCT(url, body={}, responseType='json', contentType='json', additionalHeaders={});

    try {
        const getRes = await GET('/api/users');
        const postRes = await POST('/api/users/1', data);

        // status < 400
    }
    catch(e) { // status >= 400
        if('status' in e) {
            /* e a cette forme : {
                status: {
                    status_code: number,
                    message: string,
                    details?: any
                }
            } */
        }

        console.error(e);
    }

})();
```

- L'API renvoie forcément un json.
- Elle n'est accessible que par un utilisateur enregistré (sauf pour l'endpoint de login).
- Elle part du principe qu'un utilisateur enregistré appartient à une université. Il est donc inutile pour lui de la préciser, et n'a accès qu'aux données de son université.
- Réponse pour un utilisateur non enregistré : `401`.
- Si authentifié mais tente d'accéder à une ressource qui n'est pas de son université ou dont il n'a pas les privilège : `403`.
- Si endpoint inconnu : `404`
- Sinon l'objet de la requête.
- l'url racine de l'api `/api` donne l'arbre du routage de l'api (si ENV = 'dev' seulement).

## Le portail développeur

Il est possible de passer par le portail développeur pour tester ses requêtes facilement.

accessible via le dossier `developer/`.

Ne nécessite pas d'authentification. Lors de l'envoi d'une requête, nécessite un token de connexion de l'utilisateur à émuler.

## Auteurs

Jean-Charles Armbruster<br>
Arthur Gros<br>
Dorian Thivolle<br>