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

// Funzione per stampare i dati dal database
function printDatabaseResults() {
    // Imposta il fuso orario su "Europe/Rome"
    date_default_timezone_set('Europe/Rome');

    $db = new SQLite3('uptime.db'); // Apri il database 'uptime.db'

    // Esegui una query per selezionare tutti i dati dalla tabella 'sites'
    $query = 'SELECT * FROM sites ORDER BY timestamp DESC'; // Ordina per data decrescente
    $result = $db->query($query);

    // Stampa l'intestazione della tabella
    echo "<table border='1'>
            <tr>
                <th>URL</th>
                <th>IP</th>
                <th>Stato</th>
                <th>Tempo di Risposta (ms)</th>
                <th>Timestamp</th>
            </tr>";

    // Stampa i dati dalla tabella
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['url'] . "</td>";
        echo "<td>" . $row['ip'] . "</td>";
        echo "<td>" . ($row['online'] ? 'Online' : 'Giù') . "</td>";
        echo "<td>" . $row['response_time'] . "</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($row['timestamp'])) . "</td>"; // Formatta il timestamp
        echo "</tr>";
    }

    // Chiudi il database
    $db->close();

    // Chiudi la tabella
    echo "</table>";
}

function printDatabaseResultsInCards() {
    // Imposta il fuso orario su "Europe/Rome"
    date_default_timezone_set('Europe/Rome');

    $db = new SQLite3('uptime.db'); // Apri il database 'uptime.db'

    // Esegui una query per selezionare tutti i dati dalla tabella 'sites'
    $query = 'SELECT * FROM sites GROUP BY url ORDER BY timestamp DESC'; // Ordina per data decrescente
    $result = $db->query($query);

    // Stampa i dati dalla tabella
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<div class='col s12 m4'><div class='card-panel'>";
        echo "<p class='deep-orange-text'><i class='tiny material-symbols-rounded'>language</i> " . $row['url'] . "</p>";
        echo "<small>" . date('d/m/Y H:i:s', strtotime($row['timestamp'])) . "</small>";
        echo "<span class='new badge " . ($row['online'] ? 'green' : 'red') . "' data-badge-caption=''>" . ($row['online'] ? 'Online' : 'Offline') . "</span>";
        echo "<p>@".$row['ip']." in " . $row['response_time'] . "ms</p>";
        echo "</div></div>";
    }

    // Chiudi il database
    $db->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open UPtime Monitor</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.png">
    
    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@materializecss/materialize@2.0.3-alpha/dist/css/materialize.min.css">

    <!-- Compiled and minified JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@materializecss/materialize@2.0.3-alpha/dist/js/materialize.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />

    <style>
        header, main, footer {
            padding-left: 300px;
        }

        @media only screen and (max-width : 992px) {
            header, main, footer {
                padding-left: 0;
            }
        }
    </style>
            
</head>
    <body>
        <ul id="slide-out" class="sidenav sidenav-fixed">
            <li>
                <img src="img/favicon.png" style="padding: 50px; width: 100%;">
            </li>
            <li><a href="#!" class="waves-effect"><i class="material-symbols-rounded">home</i>Home</a></li>
            <li><a href="#!">Second Link</a></li>
            <li><div class="divider"></div></li>
            <li><a class="subheader">Subheader</a></li>
            <li><a class="waves-effect" href="#!">Third Link With Waves</a></li>
        </ul>
        <main>
        
        <nav class="deep-orange">  
            <div class="nav-wrapper">
                <a href="#" data-target="slide-out" class="sidenav-trigger show-on-large"><i class="material-symbols-rounded">menu</i></a>
                <a href="#!" class="brand-logo center">Open UPtime Monitor</a>
            </div>
        </nav>
        
        <div class="container">

            <section id="home">
                <h3>Your Monitors</h3>
                 <!-- Modal Trigger -->
                <a class="waves-effect waves-light btn modal-trigger" href="#modal1"><i class="material-symbols-rounded left">add</i>New monitor</a>
                <br><br>
                <!-- Modal Structure -->
                <div id="modal1" class="modal">
                <div class="modal-content">
                    <h4><i class="material-symbols-rounded">add</i>Add new monitor</h4>
                    <form action="process.php" method="post">
                        <br><br>
                        <div class="row">
                            <div class="col s12">
                                <div class="input-field outlined">
                                    <i class="material-symbols-rounded prefix">label</i>
                                    <input placeholder=" " id="domain_label" type="text" class="validate" name="domainTitle" required>
                                    <label for="domain_label">Domain Label</label>
                                </div>
                            </div>
                        </div>
                        <br><br><br>
                        <div class="row">
                            <div class="col s12">
                                <div class="input-field outlined col s12">
                                    <i class="material-symbols-rounded prefix">link</i>
                                    <input placeholder=" " id="url" type="url" class="validate" name="domainURL" required>
                                    <label for="url">URL</label>
                                </div>
                            </div>
                        </div>
                        <br><br>
                        <button class="btn waves-effect waves-light" type="submit" name="action">Add Domain
                            <i class="material-symbols-rounded right">send</i>
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close waves-effect btn-flat">Close</a>
                </div>
                </div>
                <div class="row" style="gap:20px;">
                <?php printDatabaseResultsInCards(); ?>
                </div>
            </section>

            <section id="edit_data_section">
                <?php
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
            </section>
        
            <section id="edit_data_section">
                <h1>Inserisci Domini da Monitorare</h1>
                <form action="process.php" method="post">
                    <label for="domainTitle">Titolo del Dominio:</label>
                    <input type="text" id="domainTitle" name="domainTitle" required><br><br>

                    <label for="domainURL">URL del Dominio:</label>
                    <input type="url" id="domainURL" name="domainURL" required><br><br>

                    <input type="submit" value="Aggiungi Dominio">
                </form>
            </section>
        </div>
        </main>
    </body>
    <script>
        M.AutoInit();
    </script>
</html>
