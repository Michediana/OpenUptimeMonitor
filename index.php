<?php
// Connessione al database (assumi che sia già configurato)
$db = new SQLite3('uptime.db');

// Gestione delle richieste di aggiornamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $ip = $_POST['ip'];
    $url = $_POST['url'];
    $id = $_POST['id'];

    // Esegui l'aggiornamento dei dati nel database
    $updateQuery = "UPDATE domains SET title = :title, url = :url WHERE id = :id";
    $stmt = $db->prepare($updateQuery);
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':url', $url, SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_TEXT);
    $stmt->execute();
    echo "<script>alert('Domain updated!');</script>";
    header('Location: index.php');
}

// Funzione per stampare i dati dal database
function printDatabaseResults() {
    // Imposta il fuso orario su "Europe/Rome"
    date_default_timezone_set('Europe/Rome');

    $db = new SQLite3('uptime.db'); // Apri il database 'uptime.db'

    // Esegui una query per selezionare tutti i dati dalla tabella 'sites'
    $query = 'SELECT * FROM sites ORDER BY timestamp DESC'; // Ordina per data decrescente
    $result = $db->query($query);

    if ($result) {
        // Stampa l'intestazione della tabella
        echo "<table border='1'>
        <tr>
            <th>ID</th>
            <th>Domain ID</th>
            <th>URL</th>
            <th>IP</th>
            <th>Status</th>
            <th>Status Code</th>
            <th>Response time (ms)</th>
            <th>Timestamp</th>
        </tr>";

        // Stampa i dati dalla tabella
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['domain_id'] . "</td>";
        echo "<td>" . $row['url'] . "</td>";
        echo "<td>" . $row['ip'] . "</td>";
        echo "<td>" . ($row['online'] ? 'Online' : 'Giù') . "</td>";
        echo "<td>" . $row['status_code'] . "</td>";
        echo "<td>" . $row['response_time'] . "</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($row['timestamp'])) . "</td>"; // Formatta il timestamp
        echo "</tr>";
        }

        // Chiudi il database
        $db->close();

        // Chiudi la tabella
        echo "</table>";
    }
}

function getReponseTimeById($id) {
    $db = new SQLite3('uptime.db');
    $query = 'SELECT * FROM sites WHERE domain_id = '.$id.' ORDER BY timestamp DESC LIMIT 50'; // Ordina per data decrescente
    $result = $db->query($query);
    $print;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $print = $print . $row["response_time"].",";
    }
    return $print;
}

function getTimestampById($id) {
    $db = new SQLite3('uptime.db');
    $query = 'SELECT * FROM sites WHERE domain_id = '.$id.' ORDER BY timestamp DESC LIMIT 50 '; // Ordina per data decrescente
    $result = $db->query($query);
    $print;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $print = $print . "'".$row["timestamp"]."',";
    }
    return $print;
}

function printDatabaseResultsInCards() {
    // Imposta il fuso orario su "Europe/Rome"
    date_default_timezone_set('Europe/Rome');

    $db = new SQLite3('uptime.db'); // Apri il database 'uptime.db'

    // Esegui una query per selezionare tutti i dati dalla tabella 'sites'
    $query = 'SELECT * FROM (SELECT * FROM sites ORDER BY timestamp DESC ) GROUP BY domain_id'; // Ordina per data decrescente
    $result = $db->query($query);

    // Stampa i dati dalla tabella
    if ($result) {
        echo "<div class='row' style='gap:20px;'>";
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "<div class='col s12 m4'>
                    <div class='card'>
                    <div class='card-image'>
                        <canvas id='chart_".$row['id']."'></canvas>
                    </div>
                    <div class='card-content'>";
            echo "<div class='deep-orange-text flex'><i class='tiny material-symbols-rounded'>language</i><span>" . $row['title'] . "</span></div>";
            echo "<small><a target='_blank' href='".$row['url']."'>".$row['url']."</a></small><br>";
            echo "<small>Latest: " . date('d/m/Y H:i:s', strtotime($row['timestamp'])) . "</small><br><br>";
            echo "<span class='new badge " . ($row['online'] ? 'green' : 'red') . "' data-badge-caption=''>" . ($row['online'] ? 'Online' : 'Offline') . " ".$row['status_code']."</span>";
            echo "<p>@".$row['ip']." in <code>" . $row['response_time'] . " ms</code></p>";
            echo "</div></div></div>";

            echo "
            <script>
                let chart_".$row['id']." = document.getElementById('chart_".$row['id']."');
            
                new Chart('chart_".$row['id']."', {
                    type: 'line',
                    data: {
                        labels: [".getTimestampById($row['domain_id'])."],
                        datasets: [{
                        data: [".getReponseTimeById($row['domain_id'])."],
                        borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => `\${value} ms`
                                }
                            },
                            x: {
                                display: false
                            }
                        },
                        tension: 0.1,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: value => `\${value.formattedValue} ms`
                                },
                            },
                        },
                    }
                });
            </script>
            ";
        }
        echo "</div>";
    } else {
        echo "<h6>No domains monitored yet :'(</h6>";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@materializecss/materialize@2.1.0/dist/css/materialize.min.css">

    <!-- Compiled and minified JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@materializecss/materialize@2.1.0/dist/js/materialize.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        header, main, footer {
            padding-left: 300px;
        }

        @media only screen and (max-width : 992px) {
            header, main, footer {
                padding-left: 0;
            }
        }


        .rounded {
            border-radius: 12px;
        }

        .shadow {
            box-shadow: 0 2px 2px 0 rgba(0,0,0,.14),0 3px 1px -2px rgba(0,0,0,.12),0 1px 5px 0 rgba(0,0,0,.2);
        }

        .flex {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
            
</head>
    <body class="grey lighten-3">
        <ul id="slide-out" class="sidenav sidenav-fixed">
            <li>
                <img src="img/favicon.png" style="padding: 50px; width: 100%;">
            </li>
            <li><a onclick="showSection('home')" class="waves-effect"><i class="material-symbols-rounded">home</i>Home</a></li>
            <li><a onclick="showSection('edit_data_section')" class="waves-effect"><i class="material-symbols-rounded">list</i>Domains List</a></li>
            <li><a onclick="showSection('raw_data')" class="waves-effect"><i class="material-symbols-rounded">database</i>Raw Data</a></li>
            <li><div class="divider"></div></li>
            <li><a class="subheader">Developed by Michele Diana</a></li>
            <li><a class="subheader">V 0.0.1 - ALPHA</a></li>
        </ul>
        <main>
        
        <nav class="deep-orange">
            <div class="nav-wrapper">
                <a href="#" data-target="slide-out" class="sidenav-trigger show-on-large"><i class="material-symbols-rounded">menu</i></a>
                <a href="#!" class="brand-logo center">Open UPtime Monitor</a>
            </div>
        </nav>
        <div class="progress" id="progressbar">
            <div class="indeterminate"></div>
        </div>
        
        <div class="container">

            <section id="home">
                <h3>Your Monitors</h3>
                 <!-- Modal Trigger -->
                <a class="waves-effect waves-light btn modal-trigger" href="#modal1"><i class="material-symbols-rounded left">add</i>New domain</a>
                <a class="waves-effect waves-light btn green" onclick="refresh()"><i class="material-symbols-rounded left">refresh</i>Refresh</a>
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
                                    <input placeholder=" " id="domain_label" type="text" class="validate" name="title" required>
                                    <label for="domain_label">Domain Label</label>
                                </div>
                            </div>
                        </div>
                        <br><br><br>
                        <div class="row">
                            <div class="col s12">
                                <div class="input-field outlined col s12">
                                    <i class="material-symbols-rounded prefix">link</i>
                                    <input placeholder=" " id="url" type="url" class="validate" name="url" required>
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
                
                <?php printDatabaseResultsInCards(); ?>
                
            </section>

            <section id="edit_data_section" style="display:none;">
                <h3>Domains List</h3>
                <div class="row">
                    <div class="col s3">
                        <ul class="tabs tabs-fixed-width rounded shadow" id="domain_list_tabs">
                            <li class="tab col s3"><a href="#domain_list_view" class="active">VIEW</a></li>
                            <li class="tab col s3"><a href="#domain_list_edit">EDIT</a></li>
                        </ul>
                    </div>
                    <div id="domain_list_view" class="col s12">
                        <?php
                            // Recupera i dati dalla tabella domains
                            $selectQuery = "SELECT * FROM domains ORDER BY id DESC";
                            $result = $db->query($selectQuery);

                            if ($result) {
                                // Visualizza i dati in una tabella HTML
                                echo '<div class="card-panel"><table border="1">';
                                echo '<tr><th>ID</th><th>Title</th><th>URL</th><th>IP</th><th>Created on</th></tr>';
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<tr>';
                                    echo '<td>' . $row['id'] . '</td>';
                                    echo '<td>' . $row['title'] . '</td>';
                                    echo '<td>' . $row['url'] . '</td>';
                                    echo '<td>' . $row['ip'] . '</td>';
                                    echo '<td>' . $row['timestamp'] . '</td>';
                                    echo '</tr>';
                                }
                                echo '</table></div>'; 
                            } else {
                                echo "<h6>No domains found :'(</h6>";
                            }

                        
                        ?>
                    </div>
                    <div id="domain_list_edit" class="col s12">
                        <?php
                            // Recupera i dati dalla tabella domains
                            $selectQuery = "SELECT * FROM domains ORDER BY id DESC";
                            $result = $db->query($selectQuery);

                            if ($result) {
                                // Visualizza i dati in una tabella HTML
                                echo '<div class="card-panel"><form action="" method="post">';
                                echo '<div class="row"><div class="input-field col s12 outlined"><label for="edit_form_select">Select domain</label><select id="edit_form_select" name="id">';
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . $row['id'] . '">' . $row['title'] . '</option>';
                                }
                                echo "</select></div></div>";
                                echo '
                                <br><br>
                                <div class="row">
                                    <div class="col s12">
                                        <div class="input-field outlined">
                                            <i class="material-symbols-rounded prefix">label</i>
                                            <input placeholder=" " id="edit_domain_label" type="text" class="validate" name="title" required>
                                            <label for="edit_domain_label">Domain Label</label>
                                        </div>
                                    </div>
                                </div>
                                <br><br><br>
                                <div class="row">
                                    <div class="col s12">
                                        <div class="input-field outlined col s12">
                                            <i class="material-symbols-rounded prefix">link</i>
                                            <input placeholder=" " id="edit_url" type="url" class="validate" name="url" value="https://" required>
                                            <label for="edit_url">URL</label>
                                        </div>
                                    </div>
                                </div>
                                <br><br>
                                <button class="btn waves-effect waves-light" type="submit" name="action">Update Domain
                                    <i class="material-symbols-rounded left">send</i>
                                </button>
                                ';
                                echo '</form></div>'; 
                            } else {
                                echo "<h6>No domains found :'(</h6>";
                            }

                        
                        ?>
                    </div>
                </div>
            </section>

            <section id="raw_data" style="display:none;">
                <div class="card-panel">
                    <h4>Raw DB Data</h4>
                    <?php printDatabaseResults(); ?>
                </div>
            </section>
        </div>
        </main>
    </body>
    <script>
        M.AutoInit();
        const progressbar = document.getElementById("progressbar");
        document.addEventListener("DOMContentLoaded", () => {
            progressbar.style.display = "none";
            showSection(window.location.hash.replace("#", "") || "home");
            setTimeout(() => {
                refresh();
            }, 30000);
        });

        function showSection(id){
            document.querySelectorAll('section').forEach(el => {
                el.style.display = "none";
            });
            document.getElementById(id).style.display = "block";
            window.location.hash = id;
        }

        function refresh(){
            progressbar.style.display = "block";
            let xhttp = new XMLHttpRequest();
            xhttp.onload = function() {
                M.toast({text: 'Refresh done!'});
                progressbar.style.display = "none";
                location.reload();
            }
            xhttp.open("GET", "cron.php", true);
            xhttp.send();
        }
    </script>
</html>
