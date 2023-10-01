<?php 
function monitorAndSaveSites($urls) {
    // Imposta il fuso orario su "Europe/Rome"
    date_default_timezone_set('Europe/Rome');

    foreach ($urls as $url) {
        // Ottieni l'indirizzo IP del dominio
        $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

        // Registra il tempo prima di inviare la richiesta
        $startTime = microtime(true);

        // Crea una risorsa cURL
        $ch = curl_init($url);

        // Imposta le opzioni di cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout dopo 10 secondi

        // Esegui la richiesta HTTP
        $response = curl_exec($ch);

        // Registra il tempo dopo aver ricevuto la risposta
        $endTime = microtime(true);

        // Calcola il tempo di risposta in millisecondi
        $responseTime = round(($endTime - $startTime) * 1000, 2);

        // Verifica se c'è un errore
        if (curl_errno($ch)) {
            // Si è verificato un errore durante la richiesta (ad esempio, il sito è giù)
            $online = false;
        } else {
            // Ottieni il codice di stato HTTP dalla risposta
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Verifica il codice di stato HTTP (200 indica che il sito è online)
            $online = ($httpCode == 200);
        }

        // Chiudi la risorsa cURL
        curl_close($ch);

        $now = new DateTime();
        $now->setTimezone(new DateTimeZone('Europe/Rome'));

        // Salva i dati nel database SQLite
        $db = new SQLite3('uptime.db'); // Crea o apri il database 'uptime.db'

        // Crea una tabella se non esiste già
        $db->exec('CREATE TABLE IF NOT EXISTS sites (url TEXT, ip TEXT, online INT, response_time REAL, timestamp DATETIME)');

        // Inserisci i dati nel database
        $stmt = $db->prepare('INSERT INTO sites (url, ip, online, response_time) VALUES (:url, :ip, :online, :response_time)');
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':online', $online ? 1 : 0, SQLITE3_INTEGER);
        $stmt->bindValue(':response_time', $responseTime, SQLITE3_FLOAT);
        $stmt->bindValue(':timestamp', $now, SQLITE3_DATETIME);
        $stmt->execute();

        // Chiudi il database
        $db->close();
    }
}

// Esempio di utilizzo:
$urlsToMonitor = [
    "https://www.google.com",
    "https://www.bing.com",
    "https://www.maps.google.com",
];

//monitorAndSaveSites($urlsToMonitor);

// Esempio di utilizzo:
$domainsList = getDomainsFromDatabase();

// Puoi iterare sui risultati per utilizzarli come desideri
foreach ($domainsList as $domain) {
    // Chiamata alla funzione per monitorare e aggiornare lo stato
    monitorAndSaveSites([$domain['url']]);
}

// Funzione per recuperare gli URL dal database
function getDomainsFromDatabase() {
    // Apri il database 'uptime.db'
    $db = new SQLite3('uptime.db');

    // Esegui una query per selezionare gli URL dalla tabella 'domains'
    $query = 'SELECT * FROM domains ORDER BY timestamp DESC'; // Ordina per data decrescente
    $result = $db->query($query);

    $domains = [];

    // Estrai i dati dalla query
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $domains[] = [
            'title' => $row['title'],
            'url' => $row['url'],
            'ip' => $row['ip'],
            'timestamp' => $row['timestamp']
        ];
    }

    // Chiudi il database
    $db->close();

    return $domains;
}

?>