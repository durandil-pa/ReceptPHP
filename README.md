# Peters Receptbank v0.7.0

Receptbank byggd i PHP.

## Installation

1. Placera projektet på en server med PHP 7.2+ och PDO MySQL.
2. Besök `install/index.php`.
3. Ange databasuppgifterna. Installationen skapar databasen, tabellerna och den lokala filen `config/database.local.php`.
4. Se till att servern får skriva till `public/uploads/recipes/` för receptbilder.
5. Ta bort eller begränsa åtkomsten till katalogen `install/` när installationen är klar.

## Lokal konfiguration

Databasuppgifter ska aldrig läggas i Git. Installationsprogrammet skapar `config/database.local.php`. Om du vill konfigurera manuellt, kopiera `config/database.local.php.example` till `config/database.local.php` och fyll i dina lokala uppgifter.

Du kan också använda miljövariablerna `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` och `DB_CHARSET`.
