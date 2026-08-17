# Peters Receptbank v0.7.0

Receptbank byggd i PHP.

## Installation på webbserver

1. Ladda upp hela projektmappen `ReceptPHP` med FTP.
2. Ställ in domänens eller underdomänens dokumentrot till `ReceptPHP/public`. Filerna i `config/`, `app/`, `database/` och `install/` ska inte vara publikt åtkomliga.
3. Kontrollera att webbservern kör PHP 7.2 eller senare med PDO MySQL.
4. Kontrollera att webbservern får skriva till `ReceptPHP/public/uploads/recipes/`.
5. Besök `https://din-domän.se/install.php` och ange databasuppgifter samt första administratörskontot.
6. Ta bort `public/install.php` från servern när installationen är klar.

## Lokal konfiguration

Databasuppgifter ska aldrig läggas i Git. Installationsprogrammet skapar `config/database.local.php`. Om du vill konfigurera manuellt, kopiera `config/database.local.php.example` till `config/database.local.php` och fyll i dina lokala uppgifter.

Du kan också använda miljövariablerna `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` och `DB_CHARSET`.

## Måttomvandling

Den inloggade applikationen har en fristående måttomvandlare för amerikanska receptmått. Logiken finns i `App\Services\MeasurementConverter` och kan även användas av import- och visningsflöden:

```php
$result = $converter->convert(2, 'cups');
// ['value' => 4.73176473, 'unit' => 'dl', 'category' => 'volume', ...]
```

Volymenheten `fl oz` och viktenheten `oz` är separata enheter. Omvandlaren stöder cup, fluid ounce, ounce (vikt), tablespoon/tbsp, teaspoon/tsp, pound/lb och Fahrenheit.
