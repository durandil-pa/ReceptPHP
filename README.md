# Peters Receptbank v0.7.0

Receptbank byggd i PHP.

## Lokal konfiguration

Databasuppgifter ska aldrig läggas i Git. Kopiera `config/database.local.php.example` till `config/database.local.php` och fyll i dina lokala uppgifter, eller ange motsvarande miljövariabler:

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_CHARSET`

Projektet kräver PHP 7.2 eller senare med PDO och PDO MySQL.
