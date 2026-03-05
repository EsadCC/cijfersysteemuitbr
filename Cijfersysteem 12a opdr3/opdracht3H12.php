<?php
include 'connect.php';

// STAP 2: VERWIJDEREN Als er in de URL ?delete_id=3 staat, verwijder dan dat cijfer
if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];

    // conn: prepare → execute([waarde]
    $stmt = $conn->prepare("DELETE FROM cijfers WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// STAP 3: INVOEREN Als het formulier verstuurd is, sla dan het nieuwe cijfer op
$fout = '';

if (isset($_POST['toevoegen'])) {
    $leerling = trim($_POST['leerling']);
    $cijfer   = str_replace(',', '.', trim($_POST['cijfer']));// Bijv 7.5 -> 7,5
    $vak      = trim($_POST['vak']);
    $docent   = trim($_POST['docent']);

    if ($leerling == '' || $cijfer == '' || $vak == '' || $docent == '') {
        $fout = 'Vul alle velden in!';
    } elseif (!is_numeric($cijfer)) {
        $fout = 'Het cijfer moet een getal zijn (bijv. 7,5)';
    } else {
        $cijfer = (float) $cijfer;

        // conn: geef alle waardes mee als array in execute()
        // De volgorde moet overeenkomen met de ? in de query
        $stmt = $conn->prepare("INSERT INTO cijfers (leerling, cijfer, vak, docent)
                               VALUES (?, ?, ?, ?)");
        $stmt->execute([$leerling, $cijfer, $vak, $docent]);

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// STAP 4: ZOEKEN EN OPHALEN Haal alle cijfers op, met optioneel filteren op naam
$zoek = trim($_GET['zoek'] ?? '');

if ($zoek != '') {
    $zoekterm = "%$zoek%";

    // conn: zelfde principe, waarde meegeven in execute()
    $stmt = $conn->prepare("SELECT * FROM cijfers WHERE leerling LIKE ? ORDER BY leerling");
    $stmt->execute([$zoekterm]);

    // Bij conn gebruik je gewoon $stmt direct om te loopen
    $result = $stmt;

} else {
    $result = $conn->query("SELECT * FROM cijfers ORDER BY leerling");
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Cijfersysteem</title>
    <style>
body 
    { 
        font-family: Arial, sans-serif; 
        max-width: 900px; 
        margin: 20px auto; 
        padding: 0 15px; 
    }
h2   
    { 
        color: #333; 
    }
input        
    { 
        padding: 6px; 
        margin: 4px; 
        border: 1px solid #ccc; 
        border-radius: 4px; 
    }
button       
    { 
        padding: 6px 14px; 
        border: none; 
        border-radius: 4px; 
        color: white; 
        cursor: pointer; 
    }
.btn-blauw   
    { 
        background: #2196F3; 
    }

.btn-rood    
    { 
        background: #f44336; 
    }
.foutmelding 
    { 
        color: red; 
        margin: 8px 0; 
    }
table    
    { 
        border-collapse: collapse; 
        width: 100%; 
        margin-top: 15px; 
    }
th, td   
    { 
        border: 1px solid #ccc; 
        padding: 8px 12px; 
        text-align: left; 
    }
thead th 
    { 
        background: #4CAF50; 
        color: white; 
        cursor: pointer; 
    }
tr:nth-child(even) 
    { 
        background: #f9f9f9; 
    }
    </style>
</head>
<body>

<h2>📋 Cijfersysteem</h2>

<!--  ZOEKBALK  -->
<form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="margin-bottom: 10px;">
    <input type="text" name="zoek" placeholder="Zoek op leerlingnaam..."
           value="<?php echo htmlspecialchars($zoek); ?>">
    <button class="btn-blauw" type="submit">🔍 Zoeken</button>
    <?php if ($zoek != ''): ?>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>">❌ Wis zoekresultaat</a>
    <?php endif; ?>
</form>

<!--  FOUTMELDING  -->
<?php if ($fout != ''): ?>
    <p class="foutmelding">⚠️ <?php echo htmlspecialchars($fout); ?></p>
<?php endif; ?>

<!--  INVOERFORMULIER  -->
<form method="post" style="margin-bottom: 20px;">
    <strong>Nieuw cijfer invoeren:</strong><br><br>
    <input type="text" name="leerling" placeholder="Naam leerling" required>
    <input type="text" name="cijfer"   placeholder="Cijfer (bijv. 7,5)" required>
    <input type="text" name="vak"      placeholder="Vak (bijv. NL)" required>
    <input type="text" name="docent"   placeholder="Docent (bijv. JNS)" required>
    <button class="btn-blauw" type="submit" name="toevoegen">➕ Invoeren</button>
</form>

<!--  TABEL MET CIJFERS  -->
<table id="tabel">
    <thead>
        <tr>
            <th onclick="sorteer(0)">Leerling ↕</th>
            <th onclick="sorteer(1)">Cijfer ↕</th>
            <th>Vak</th>
            <th>Docent</th>
            <th>Actie</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($rij = $result->fetch()): ?>
            <tr>
                <td><?php echo htmlspecialchars($rij['leerling']); ?></td>
                <td><?php echo number_format($rij['cijfer'], 1, ',', ''); ?></td>
                <td><?php echo htmlspecialchars($rij['vak']); ?></td>
                <td><?php echo htmlspecialchars($rij['docent']); ?></td>
                <td>
                    <a href="?delete_id=<?php echo (int)$rij['id']; ?>"
                       onclick="return confirm('Weet je zeker dat je dit cijfer wilt verwijderen?');"
                       class="btn-rood"
                       style="padding:4px 10px; border-radius:4px; color:white; text-decoration:none;">
                        🗑️ Verwijder
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!--  JAVASCRIPT: SORTEREN  -->
<script>
    // true = oplopend, false = aflopend
    let oplopend = true;

    function sorteer(kolomNummer) {
        const tbody = document.querySelector('#tabel tbody');
        const rijen = Array.from(tbody.querySelectorAll('tr'));

        rijen.sort((a, b) => {
            // Haal de tekst op uit de cel
            let waardeA = a.cells[kolomNummer].textContent.trim();
            let waardeB = b.cells[kolomNummer].textContent.trim();

            // Zet komma om naar punt zodat cijfers werken: 7,5 → 7.5
            let numA = parseFloat(waardeA.replace(',', '.'));
            let numB = parseFloat(waardeB.replace(',', '.'));

            let vergelijking = !isNaN(numA) && !isNaN(numB)
                ? numA - numB                          // numeriek
                : waardeA.localeCompare(waardeB);      // alfabetisch

            // Als oplopend false is, draai het resultaat om
            return oplopend ? vergelijking : -vergelijking;
        });

        rijen.forEach(rij => tbody.appendChild(rij));
        oplopend = !oplopend;
    }
</script>

</body>
</html>

<?php
$conn = null;
?>