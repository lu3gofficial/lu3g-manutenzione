# LU3G Manutenzione

Plugin WordPress di LU3G Agenzia Web: mette il sito in manutenzione. I visitatori vedono una schermata con il logo
LU3G; dal **lucchetto in alto a destra** chi ha un account accede con le credenziali del sito e lo vede normalmente.

## Uso
**Impostazioni → Manutenzione**
- *Modalità manutenzione*: accende o spegne la schermata. Al salvataggio la cache delle pagine viene svuotata.
- *Testi*: titolo, messaggio, riga in evidenza facoltativa (es. "Torniamo online lunedì 1 dicembre").
- *Logo*: LU3G (predefinito), logo del sito, immagine dalla Libreria media.
- *Accesso dal lucchetto*: chi vede il sito dopo l'accesso (chi lavora sul sito / solo amministratori / tutti gli utenti
  con un account) e se mostrare il lucchetto.
- *Anteprima della schermata*: la vedi anche con la manutenzione spenta.

Finché la manutenzione è accesa, nella barra di amministrazione c'è il promemoria rosso "Manutenzione attiva".

## Come funziona
- La schermata è una pagina autonoma (non usa il tema né i page builder), con stato **HTTP 503** e `Retry-After`:
  Google la considera temporanea e non toglie le pagine dall'indice. Non viene mai salvata in cache.
- Il modulo del lucchetto invia a `wp-login.php`: funzionano i plugin di sicurezza (captcha, tentativi, 2FA, login
  nascosto). Con credenziali sbagliate si torna alla schermata con l'errore.
- Restano raggiungibili la bacheca, `wp-login.php`, `admin-ajax.php`, il cron, WP-CLI, `robots.txt`. Le API REST
  rispondono 503 a chi non può vedere il sito.
- Cache esterne (Cloudflare, Varnish del provider) vanno svuotate dal loro pannello se servono pagine vecchie.

## Emergenza
`wp-login.php` funziona sempre. Per spegnere la schermata senza entrare nella bacheca, in `wp-config.php`:

```php
define( 'LU3G_MANUTENZIONE_OFF', true );
```

## Filtri
- `lu3g_manutenzione_user_can_bypass` (bool, user_id): chi vede il sito.
- `lu3g_manutenzione_allow_request` (bool): lascia passare una richiesta.
- `lu3g_manutenzione_rest_allowed` (bool, route): lascia aperta una rotta REST.
- Azione `lu3g_manutenzione_purge_cache`: svuota altre cache.

## Rilascio
Versione in `lu3g-manutenzione.php` (intestazione e costante) e `readme.txt`, sezione nel `CHANGELOG.md`, poi tag
`vX.Y.Z`: l'Action crea la release con `lu3g-manutenzione.zip`. L'updater (`includes/class-updater.php`) è generato
da `lu3gofficial/lu3g-updater`: non modificarlo a mano.
