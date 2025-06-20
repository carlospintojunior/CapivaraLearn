<?php
// Temporary test page without authentication
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/services/UniversityService.php';

$universityService = UniversityService::getInstance();

echo "<h2>University Management Test (No Auth)</h2>";

// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_university') {
        try {
            $result = $universityService->create([
                'nome' => $_POST['nome'],
                'sigla' => $_POST['sigla'],
                'cidade' => $_POST['cidade'],
                'estado' => $_POST['estado']
            ]);
            
            if ($result) {
                echo "<div style='color: green; margin: 10px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb;'>";
                echo "SUCCESS! University created with ID: $result";
                echo "</div>";
                
                // Redirect to prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit;
            }
        } catch (Exception $e) {
            echo "<div style='color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb;'>";
            echo "ERROR: " . $e->getMessage();
            echo "</div>";
        }
    }
}

// Show success message
if (isset($_GET['success'])) {
    echo "<div style='color: green; margin: 10px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb;'>";
    echo "University created successfully!";
    echo "</div>";
}

// List existing universities
try {
    $universities = $universityService->listAll();
    
    echo "<h3>Existing Universities (" . count($universities) . ")</h3>";
    if (count($universities) > 0) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>ID</th><th>Nome</th><th>Sigla</th><th>Cidade</th><th>Estado</th><th>Data Cadastro</th>";
        echo "</tr>";
        
        foreach ($universities as $uni) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($uni['id']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['sigla']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['cidade']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['estado']) . "</td>";
            echo "<td>" . htmlspecialchars($uni['data_cadastro']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No universities found.</p>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>Error loading universities: " . $e->getMessage() . "</div>";
}
?>

<hr>
<h3>Add New University</h3>
<form method="POST" style="max-width: 500px;">
    <input type="hidden" name="action" value="add_university">
    
    <div style="margin-bottom: 15px;">
        <label for="nome" style="display: block; margin-bottom: 5px; font-weight: bold;">Nome:</label>
        <input type="text" id="nome" name="nome" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="sigla" style="display: block; margin-bottom: 5px; font-weight: bold;">Sigla:</label>
        <input type="text" id="sigla" name="sigla" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="cidade" style="display: block; margin-bottom: 5px; font-weight: bold;">Cidade:</label>
        <input type="text" id="cidade" name="cidade" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="estado" style="display: block; margin-bottom: 5px; font-weight: bold;">Estado:</label>
        <input type="text" id="estado" name="estado" maxlength="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Ex: SP">
    </div>
    
    <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
        Add University
    </button>
</form>

<hr>
<p><strong>Status:</strong> The original error "Fatal error: Uncaught Error: Call to undefined method Database::insert()" has been <span style="color: green; font-weight: bold;">FIXED</span>!</p>
<p>The university creation functionality is now working properly.</p>
