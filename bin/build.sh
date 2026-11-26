#!/bin/sh
# Crea dist/lu3g-manutenzione.zip (cartella lu3g-manutenzione/ all'interno), il pacchetto che WordPress installa e aggiorna.
# Esclude i file che servono solo allo sviluppo (git, script, automazioni).
set -e
cd "$(dirname "$0")/.."
rm -rf dist && mkdir -p dist/lu3g-manutenzione
cp -R lu3g-manutenzione.php readme.txt uninstall.php includes assets languages dist/lu3g-manutenzione/
(cd dist && zip -rq lu3g-manutenzione.zip lu3g-manutenzione -x '*.DS_Store' -x '*/.gitkeep')
rm -rf dist/lu3g-manutenzione
echo "dist/lu3g-manutenzione.zip"
