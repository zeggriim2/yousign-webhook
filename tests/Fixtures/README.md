# Fixtures d'événements

`Events/<event_name>.json` contient un payload par événement documenté par
Yousign (YouTrust), au format actuel `{ "metadata": …, "data": … }`.

- Le bloc `metadata` reprend celui des exemples de la documentation.
- Les blocs `data` de `signature_request.*`, `signer.*` et `electronic_seal.*`
  sont repris des exemples publiés ; ceux des autres familles sont
  **représentatifs** : ils respectent la structure décrite par la documentation
  mais ne sont pas des copies littérales.

Ces fixtures servent à garantir que le parsing accepte l'ensemble du catalogue
d'événements et que chaque nom se résout dans l'énumération `YousignEvent`.
Pour rejouer un événement réel, remplacez simplement le fichier concerné par une
capture issue de votre compte sandbox.
