# Carte IGN pour WS Form

Champs de carte IGN et d'adresse BAN pour WS Form, sans Google et sans clé d'API.

**État : en développement.** Les champs carte et adresse fonctionnent sur WS Form PRO et LITE. La traduction, la vérification PHPCS et la mise à jour automatique restent à faire. Pour reprendre le travail, lis `CLAUDE.md`.

## Limites connues

La valeur de la carte est un JSON. Elle s'affiche en `latitude,longitude` dans la liste des soumissions, l'export CSV et les e-mails.

Elle reste en JSON à deux endroits, car WS Form n'y offre aucun filtre :

- `#field(ID)` hors e-mail
- le détail d'une soumission, dans la barre latérale de l'admin
