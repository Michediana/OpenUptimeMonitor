<?php
// Verifica se sono stati inviati dati dal form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Recupera i dati dal form
    $domainTitle = $_POST["title"];
    $domainURL = $_POST["url"];

    // Funzione per salvare i dati nel database
    function saveToDatabase($title, $url) {
        // Imposta il fuso orario su "Europe/Rome"
        date_default_timezone_set('Europe/Rome');

        // Ottieni l'indirizzo IP del dominio
        $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

        // Registra il tempo corrente
        $timestamp = date('Y-m-d H:i:s');

        // Salva i dati nel database SQLite
        $db = new SQLite3('uptime.db'); // Crea o apri il database 'uptime.db'

        // Crea una tabella se non esiste già
        $db->exec('CREATE TABLE IF NOT EXISTS domains (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT,
                url TEXT,
                ip TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            )');

        // Inserisci i dati nel database
        $stmt = $db->prepare('INSERT INTO domains (title, url, ip, timestamp) VALUES (:title, :url, :ip, :timestamp)');
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':timestamp', $timestamp, SQLITE3_TEXT);
        $stmt->execute();

        // Chiudi il database
        $db->close();
    }

    // Chiamata alla funzione per salvare i dati nel database
    saveToDatabase($domainTitle, $domainURL);

    // Reindirizza l'utente alla pagina principale o a una pagina di conferma
    header('Location: index.php');
    exit;
}
?>
