# CORS et origines du projet

SymSvel utilise NelmioCorsBundle pour les endpoints `/api`, `/apibo` et `/apiopen`.
Le Back est la cible de ces appels, tandis que les interfaces BO, App
et Front sont les origines navigateur à autoriser.

## Mapping des URL au Setup (22/11/2026)

Le premier écran du Setup local et du premier déploiement production associe
quatre **scopes logiques** à leurs URL réelles :

| Scope | Exemple | Rôle |
| --- | --- | --- |
| `back` | `https://api.choralys.com` | API cible, non ajoutée aux CORS |
| `bo` | `https://admin.choralys.com` | Origine BO |
| `app` | `https://manager.choralys.com` | Origine App |
| `front` | `https://www.choralys.com` | Origine Front |

Ces URL sont **des exemples** ; seul `manager.choralys.com` est le cas
Choralys explicitement prévu. Les autres sont à saisir selon l'hébergement
réel. Aucune convention `app.<domaine>` n'est imposée et aucun dossier
physique n'est renommé. App et Front peuvent rester vides s'ils ne sont
pas installés.

Les URL doivent être des origines HTTP(S) sans chemin, query string,
userinfo ou fragment. HTTPS est requis pour les URL renseignées en production.
En local, l'HTTP est autorisé pour les serveurs de développement.

Le Setup publie dans `backend/.env.local` :

- `SYMSVEL_BACK_ORIGIN`, `SYMSVEL_BO_ORIGIN`, `SYMSVEL_APP_ORIGIN` et
  `SYMSVEL_FRONT_ORIGIN` : le mapping explicite des URL ;
- `SYMSVEL_CORS_ALLOW_ORIGIN` : expression régulière générée **uniquement**
  à partir des origines BO, App et Front renseignées ;
- `SYMSVEL_BO_DOMAIN` : domaine historique conservé comme fallback pour
  les anciennes versions d'Auth.

Le filtrage CORS compare exactement schéma, hôte et port. Il ne permet
ni variantes de sous-domaines ni ports non déclarés. Un scope absent
n'autorise aucune origine supplémentaire. Le fichier `.env.local` est
propre à chaque installation et ne doit jamais être versionné avec des secrets.

## Authentification navigateur BO

Le transport navigateur BO utilise
`POST /apibo/auth/browser/{login,refresh,logout}`.
Le module Auth vérifie séparément l'en-tête `Origin` :
`SYMSVEL_BO_ORIGIN` prime sur le domaine historique. Les URL
d'invitation et d'activation App utilisent `SYMSVEL_APP_ORIGIN`
lorsqu'elle est déclarée.

Le cookie de renouvellement de production est
`__Host-symsvel-bo-refresh` (`Secure`, `HttpOnly`, `SameSite=Strict`,
`Path=/`, sans attribut Domain).
Seul le chemin `^/apibo/auth/browser(?:/|$)` active
`allow_credentials: true` ; les autres routes restent à `false`.
L'access token est conservé en mémoire par le navigateur.

En développement local, Auth peut accepter une origine BO HTTP explicite
seulement pour un hôte de développement réservé (`.localhost`, `.local`,
`.test`) ou un accès réellement loopback, sur une requête non HTTPS.
Le cookie local n'est pas marqué Secure ; il reste host-only et HttpOnly.
Ce traitement n'est jamais autorisé en production.

## Validation

Les CORS ne remplacent ni l'authentification ni le contrôle d'origine propre
au module Auth. Les tests doivent couvrir un préflight autorisé et refusé,
une requête avec credentials BO, le refus d'une origine étrangère, la
connexion/rotation/logout et les liens d'invitation App sur le domaine mappé.

Les CI Back et Auth, puis les crash-tests Choralys en local et sur le VPS,
sont nécessaires avant de déclarer le comportement validé.

## API publiques (27/11/2026)

Le préfixe `/apiopen` utilise les mêmes origines exactes publiées par Setup et les mêmes en-têtes/méthodes que `/api`, sans credentials ni wildcard. Cela permet notamment les appels statiques App/Front aux API publiques des modules. Les modules restent responsables de leurs contrôles d'accès et d'origine ; aucune règle métier GDPR n'est ajoutée au Back. Le seul chemin avec credentials reste le transport navigateur BO Auth. Test de configuration dédié ; CT preflight sur les origines réelles requis.
