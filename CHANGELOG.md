# Registro delle modifiche

## 1.0.2
- **Pannello riordinato**: ogni sezione ha titolo e una riga di spiegazione; interruttore principale con la pastiglia
  Accesa/Spenta (bordo rosso quando è accesa, aggiornato subito); scelta del logo più compatta con anteprima su sfondo
  scuro, pallino di selezione e pulsante "Scegli/Cambia immagine" sotto l'anteprima; lucchetto in un riquadro accanto
  al menu di accesso e nota informativa separata; barra Salva fissa in basso con Anteprima; "Se resti chiuso fuori"
  come ultima sezione con il codice in un riquadro. Foglio di stile ripulito dagli stili non usati.

## 1.0.1
- Il pannello è sotto il menu **LU3G** (LU3G Hub), come gli altri plugin LU3G, non più in Impostazioni. Senza LU3G Hub
  compare la voce "LU3G Manutenzione" nel menu laterale.

## 1.0.0
Prima versione.
- **Modalità manutenzione** (LU3G → Manutenzione): i visitatori vedono una schermata con il logo LU3G (oppure
  il logo del sito o un'immagine a scelta), titolo, messaggio e una riga in evidenza facoltativa. Stato HTTP 503 con
  Retry-After, noindex, mai salvata in cache.
- **Lucchetto in alto a destra**: apre una finestra di accesso che invia le credenziali alla normale pagina di accesso di
  WordPress (compatibile con captcha, limiti dei tentativi, 2FA, indirizzi di accesso personalizzati). Con credenziali
  sbagliate si torna alla schermata con l'errore. Finestra accessibile: Esc, clic fuori, focus intrappolato e
  restituito; su telefono sale dal basso.
- **Chi vede il sito dopo l'accesso**: chi lavora sul sito (predefinito), solo amministratori o tutti gli utenti con un
  account. Gli altri vedono un messaggio e il pulsante Esci.
- **Cache**: accendendo, spegnendo o cambiando la schermata viene svuotata la cache delle pagine (WP Rocket, LiteSpeed,
  W3 Total Cache, WP Super Cache, SiteGround, WP Fastest Cache, Breeze, Cache Enabler, Hummingbird, Nginx Helper).
- API REST chiuse a chi non può vedere il sito; bacheca, wp-login.php, admin-ajax, cron e robots.txt raggiungibili.
- Promemoria rosso "Manutenzione attiva" nella barra di amministrazione, avviso in Bacheca e Plugin, anteprima per gli
  amministratori, uscita di emergenza con `define( 'LU3G_MANUTENZIONE_OFF', true );` in wp-config.php.
- Aggiornamenti da GitHub (lu3gofficial/lu3g-manutenzione).
