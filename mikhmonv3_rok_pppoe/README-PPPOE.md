# Gestion des clients PPPoE — Mikhmon v3

Cette copie de `mikhmonv3_rok` ajoute un module complet de **gestion de clients
PPPoE**, construit sur exactement le même principe que le module Hotspot de
Mikhmon.

## Le principe repris du Hotspot

| Hotspot                                       | PPPoE                                          |
|-----------------------------------------------|------------------------------------------------|
| `/ip hotspot user profile` + script `on-login` | `/ppp profile` + script `on-up`                |
| `/ip hotspot user`                             | `/ppp secret`                                  |
| `/ip hotspot active`                           | `/ppp active`                                  |
| Prix / validité encodés dans le `on-login`     | Prix / validité encodés dans le `on-up`        |
| Date d'expiration écrite dans le `comment`     | Date d'expiration écrite dans le `comment`     |
| Scheduler `<profil>` qui purge les expirés     | Scheduler `pppoe-<profil>` qui purge les expirés |
| Ventes enregistrées dans `/system script`      | Mêmes enregistrements, **même rapport de ventes** |

Les données commerciales sont stockées dans la première ligne du script `on-up`
du profil, dans le format déjà utilisé par le hotspot :

```
:put (",<mode_expiration>,<prix>,<validite>,<prix_vente>,,<verrou>,")
```

ce qui permet de les relire côté PHP avec un simple `explode(",", $onup)`.

## Ce qui a été ajouté

### Nouveau dossier `ppp/`

| Fichier                | Rôle                                                          |
|------------------------|---------------------------------------------------------------|
| `pppsecrets.php`       | Liste des clients PPPoE : recherche, filtre par profil et par statut (actif / non activé / expiré), activation, désactivation, renouvellement, suppression |
| `addsecret.php`        | Ajout d'un client (avec choix du mode d'activation)           |
| `secretbyname.php`     | Fiche client : édition, date d'expiration, renouvellement, partage WhatsApp |
| `pppprofile.php`       | Liste des profils PPPoE avec prix, validité, mode d'expiration et état du moniteur |
| `addpppprofile.php`    | Création d'un profil (pool, rate-limit, DNS, prix, validité…) |
| `profilebyname.php`    | Édition d'un profil (le scheduler suit le profil s'il est renommé) |
| `pppactive.php`        | Connexions PPPoE en cours, avec déconnexion                   |
| `pppmisc.php`          | Fonctions partagées (parsing du `on-up`, statuts, validité → secondes, formatage des prix, écriture d'un enregistrement de vente) |
| `pppscript.php`        | Générateur des scripts RouterOS (`on-up` et scheduler de surveillance) |
| `pppjs.php`            | JavaScript partagé des formulaires PPPoE                      |

### Nouveaux traitements dans `process/`

| Fichier                    | Rôle                                                     |
|----------------------------|----------------------------------------------------------|
| `psecret.php`              | Activer / désactiver / supprimer un client (la session PPPoE en cours est coupée en même temps) |
| `renewpppsecret.php`       | Renouvellement : ajoute une validité à la date d'expiration, réactive le client et enregistre la vente |
| `removeexpiredsecret.php`  | Suppression en masse des clients expirés                 |
| `removepprofile.php`       | Suppression d'un profil et de son scheduler de surveillance |
| `getvalidpricep.php`       | Endpoint AJAX : prix et validité du profil sélectionné    |

### Fichiers existants modifiés

- `index.php` — routes PPPoE (`renew-pppsecret`, `remove-pppsecret-expired`,
  paramètre `pprofile`, mémorisation du filtre de profil).
- `include/menu.php` — section **PPPoE** dans le menu latéral
  (Clients → Liste / Ajouter, Profil PPPoE → Liste / Ajouter, PPPoE Active).
- `dashboard/home.php` — carte PPPoE (connexions actives, total clients,
  clients expirés, ajout rapide).
- `lang/en.php`, `lang/id.php`, `lang/es.php`, `lang/tl.php` — libellés du module.

## Modes d'expiration d'un profil

| Mode                | Effet à l'expiration                                   |
|---------------------|--------------------------------------------------------|
| None                | Aucun contrôle d'expiration                            |
| Remove              | Le client est supprimé                                 |
| Disable             | Le client est désactivé (il est conservé)              |
| Remove & Record     | Supprimé + vente enregistrée                           |
| Disable & Record    | Désactivé + vente enregistrée                          |

Le scheduler `pppoe-<nom_du_profil>` créé automatiquement compare la date
d'expiration écrite dans le `comment` de chaque secret du profil avec l'heure
du routeur, et coupe la session PPPoE en cours au passage.

## Activation d'un client

- **À la première connexion** — le `comment` est préfixé `pp-`. Le script
  `on-up` calcule `maintenant + validité` lors de la première connexion PPPoE et
  écrit la date dans le `comment` (le commentaire libre est conservé). C'est le
  comportement identique au voucher hotspot.
- **Maintenant** — Mikhmon écrit directement la date d'expiration, et enregistre
  la vente si le profil est en mode « & Record ».

Le bouton **Renew** repart de la date d'expiration en cours si le client est
encore actif, ou de la date du jour s'il est déjà expiré.

## Rapport de ventes

Les enregistrements PPPoE utilisent le même format que ceux du hotspot
(`/system script` avec `comment="mikhmon"`), ils apparaissent donc directement
dans **Report → Selling** et dans **Log → User Log** sans modification.

## Remarques

Quelques fichiers référencés par `index.php` sont absents de l'archive
d'origine et le restent dans cette copie (fonctionnalités hotspot, hors du
périmètre de ce module) : `hotspot/binding.php`, `process/makebinding.php`,
`report/export.php` et `include/info.php`.
