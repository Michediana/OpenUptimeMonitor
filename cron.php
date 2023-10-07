<?php 
function monitorAndSaveSites($url, $domain_id, $title) {
    // Imposta il fuso orario su "Europe/Rome"
    date_default_timezone_set('Europe/Rome');

    // Ottieni l'indirizzo IP del dominio
    $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

    // Registra il tempo prima di inviare la richiesta
    $startTime = microtime(true);

    // Crea una risorsa cURL
    $ch = curl_init($url);

    // Imposta le opzioni di cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36"); //Latest Desk User AGent
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10); //Max redirects set to 10 for limit time execution
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //Follow Redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout dopo 10 secondi

    // Esegui la richiesta HTTP
    $response = curl_exec($ch);

    // Registra il tempo dopo aver ricevuto la risposta
    $endTime = microtime(true);

    // Calcola il tempo di risposta in millisecondi
    $responseTime = round(($endTime - $startTime) * 1000, 2);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Verifica se c'è un errore
    if (curl_errno($ch)) {
        // Si è verificato un errore durante la richiesta (ad esempio, il sito è giù)
        $online = false;
    } else {
        // Verifica il codice di stato HTTP (200 indica che il sito è online)
        $online = true;
    }

    $status_code = ($httpCode);

    // Chiudi la risorsa cURL
    curl_close($ch);

    $now = date('Y-m-d H:i:s');

    // Salva i dati nel database SQLite
    $db = new SQLite3('uptime.db'); // Crea o apri il database 'uptime.db'

    // Crea una tabella se non esiste già
    $db->exec('CREATE TABLE IF NOT EXISTS sites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            domain_id INTEGER,
            title TEXT,
            url TEXT,
            ip TEXT,
            online INT,
            status_code INT,
            response_time REAL,
            timestamp DATETIME
        )');

    // Inserisci i dati nel database
    $stmt = $db->prepare('INSERT INTO sites (domain_id, title, url, ip, online, status_code , response_time, timestamp) VALUES (:domain_id, :title, :url, :ip, :online, :status_code, :response_time, :timestamp)');
    $stmt->bindValue(':domain_id', $domain_id, SQLITE3_INTEGER);
    $stmt->bindValue(':url', $url, SQLITE3_TEXT);
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $stmt->bindValue(':online', $online ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(':response_time', $responseTime, SQLITE3_FLOAT);
    $stmt->bindValue(':timestamp', $now, SQLITE3_TEXT);
    $stmt->bindValue(':status_code', $status_code, SQLITE3_TEXT);
    $stmt->execute();

    // Chiudi il database
    $db->close();
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
            'id' => $row['id'],
            'title' => $row['title'],
            'url' => $row['url']
        ];
    }

    // Chiudi il database
    $db->close();

    return $domains;
}


//=======================================
// Esempio di utilizzo:
$domainsList = getDomainsFromDatabase();
// Puoi iterare sui risultati per utilizzarli come desideri
foreach ($domainsList as $domain) {
    // Chiamata alla funzione per monitorare e aggiornare lo stato
    monitorAndSaveSites($domain['url'], $domain['id'], $domain['title']);
}

echo "done";

?>