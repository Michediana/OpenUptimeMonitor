<?php
// Connessione al database (assumi che sia già configurato)
$db = new SQLite3('uptime.db');

// Gestione delle richieste di aggiornamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $ip = $_POST['ip'];
    $url = $_POST['url'];

    // Esegui l'aggiornamento dei dati nel database
    $updateQuery = "UPDATE domains SET ip = :ip, url = :url WHERE title = :title";
    $stmt = $db->prepare($updateQuery);
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':url', $url, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $stmt->execute();
}

// Recupera i dati dalla tabella domains
$selectQuery = "SELECT * FROM domains";
$result = $db->query($selectQuery);

// Visualizza i dati in una tabella HTML
echo '<table border="1">';
echo '<tr><th>Title</th><th>URL</th><th>IP</th><th>Created on</th></tr>';
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo '<tr>';
    echo '<td>' . $row['title'] . '</td>';
    echo '<td>' . $row['url'] . '</td>';
    echo '<td>' . $row['ip'] . '</td>';
    echo '<td>' . $row['timestamp'] . '</td>';
    echo '</tr>';
}
echo '</table>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Domini</title>
</head>
    <body>
    <h2>Modifica i dati</h2>
    <form method="post" action="">
        <label for="title">Title:</label>
        <select name="title" required>
        <?php 
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                echo '<option value="'.$row['title'].'">';
                echo  $row['title'];
                echo '</option>';
            }
        ?>
        </select>
            
        <br>

        <label for="url">URL:</label>
        <input type="text" name="url"><br>

        <label for="ip">IP:</label>
        <input type="text" name="ip"><br>

        <input type="submit" value="Aggiorna">
    </form>
    <h1>Inserisci Domini da Monitorare</h1>
    <form action="process.php" method="post">
        <label for="domainTitle">Titolo del Dominio:</label>
        <input type="text" id="domainTitle" name="domainTitle" required><br><br>

        <label for="domainURL">URL del Dominio:</label>
        <input type="url" id="domainURL" name="domainURL" required><br><br>

        <input type="submit" value="Aggiungi Dominio">
    </form>
</body>
</html>
