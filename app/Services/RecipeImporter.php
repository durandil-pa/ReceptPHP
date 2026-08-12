<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class RecipeImporter
{
    private const MAX_SIZE = 2097152;

    /** @return array<string, mixed> */
    public function import(string $url): array
    {
        $url = $this->url($url);
        $recipe = $this->recipe($this->fetch($url));
        if ($recipe === null || $this->text((string) ($recipe['name'] ?? '')) === '') throw new RuntimeException('Sidan innehåller inget recept som kan importeras automatiskt.');
        $ingredients = [];
        foreach ((array) ($recipe['recipeIngredient'] ?? []) as $ingredient) { $value = substr($this->text((string) $ingredient), 0, 255); if ($value !== '') $ingredients[] = $value; }
        $steps = []; $this->steps($recipe['recipeInstructions'] ?? [], $steps);
        $yield = 4; if (preg_match('/\d+/', $this->text((string) ($recipe['recipeYield'] ?? '')), $match)) $yield = max(1, min(999, (int) $match[0]));
        $minutes = ''; $duration = strtoupper((string) ($recipe['totalTime'] ?? ($recipe['cookTime'] ?? '')));
        if (preg_match('/^P(?:\d+D)?T(?:(\d+)H)?(?:(\d+)M)?/', $duration, $match)) $minutes = (string) min(9999, ((int) ($match[1] ?? 0) * 60) + (int) ($match[2] ?? 0));
        return ['title' => $this->text((string) $recipe['name']), 'description' => $this->text((string) ($recipe['description'] ?? '')), 'servings' => $yield, 'cook_time' => $minutes, 'instructions' => substr(implode("\n\n", $steps), 0, 65000), 'ingredients' => array_slice($ingredients, 0, 100), 'source_url' => $url];
    }

    private function url(string $url): string
    {
        $url = trim($url); $parts = parse_url($url); $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : ''; $host = is_array($parts) ? (string) ($parts['host'] ?? '') : '';
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true) || $host === '') throw new RuntimeException('Ange en giltig webbadress som börjar med http:// eller https://.');
        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, [80, 443], true)) throw new RuntimeException('Receptlänken måste använda standardport för webben.');
        return $url;
    }

    private function fetch(string $url): string
    {
        if (!function_exists('curl_init')) throw new RuntimeException('Import kräver att PHP-tillägget cURL är aktiverat på servern.');
        $parts = parse_url($url); $host = (string) $parts['host']; $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : gethostbynamel($host);
        if ($addresses === false || $addresses === []) throw new RuntimeException('Receptsidan kunde inte hittas.');
        foreach ($addresses as $address) if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) throw new RuntimeException('Den här webbadressen kan inte användas för import.');
        $body = ''; $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => false, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_USERAGENT => 'Peters-Receptbank/0.8', CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int { if (strlen($body) + strlen($chunk) > self::MAX_SIZE) return 0; $body .= $chunk; return strlen($chunk); }]);
        $ok = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $type = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE); curl_close($curl);
        if ($ok === false || $status < 200 || $status >= 300) throw new RuntimeException('Receptsidan kunde inte hämtas. Prova en annan länk.');
        if ($type !== '' && stripos($type, 'html') === false) throw new RuntimeException('Länken verkar inte gå till en vanlig receptsida.');
        return $body;
    }

    /** @return array<string, mixed>|null */
    private function recipe(string $html): ?array
    {
        preg_match_all('/<script\b([^>]*)>(.*?)<\/script>/is', $html, $scripts, PREG_SET_ORDER);
        foreach ($scripts as $script) { if (!preg_match('/\btype\s*=\s*(["\'])application\/ld\+json(?:;[^"\']*)?\1/i', $script[1])) continue; $data = json_decode(trim($script[2]), true); if (json_last_error() === JSON_ERROR_NONE) { $recipe = $this->node($data); if ($recipe !== null) return $recipe; } }
        return null;
    }

    /** @param mixed $value @return array<string, mixed>|null */
    private function node($value): ?array
    {
        if (!is_array($value)) return null; $types = (array) ($value['@type'] ?? []);
        foreach ($types as $type) if (strtolower((string) $type) === 'recipe') return $value;
        foreach ($value as $child) { $recipe = $this->node($child); if ($recipe !== null) return $recipe; }
        return null;
    }

    /** @param mixed $value @param array<int, string> $steps */
    private function steps($value, array &$steps): void
    {
        if (is_string($value)) { $value = $this->text($value); if ($value !== '') $steps[] = $value; return; }
        if (!is_array($value)) return;
        if (isset($value['text'])) { $this->steps($value['text'], $steps); return; }
        if (isset($value['itemListElement'])) { $this->steps($value['itemListElement'], $steps); return; }
        foreach ($value as $item) $this->steps($item, $steps);
    }

    private function text(string $text): string { return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'))); }
}
