<?php

function medirTiempo($callback) {
    $start = microtime(true);
    $memStart = memory_get_usage(true);

    $callback();

    $memEnd = memory_get_usage(true);
    $end = microtime(true);

    return [
        'tiempo' => round($end - $start, 4),
        'memoria' => round(($memEnd - $memStart) / 1024 / 1024, 4) // en MB
    ];
}

function leerCSV($ruta) {
    $data = [];
    if (($handle = fopen($ruta, "r")) !== false) {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }
        fclose($handle);
    }
    return $data;
}

function escribirCSV($ruta, $data) {
    $f = fopen($ruta, 'w');
    fputcsv($f, array_keys($data[0]));
    foreach ($data as $row) {
        fputcsv($f, $row);
    }
    fclose($f);
}

function leerJSON($ruta) {
    return json_decode(file_get_contents($ruta), true);
}

function escribirJSON($ruta, $data) {
    file_put_contents($ruta, json_encode($data, JSON_PRETTY_PRINT));
}

function obtenerTamanoArchivo($ruta) {
    return round(filesize($ruta) / 1024, 2); // Tamaño en KB
}

function usoCPU() {
    $output = shell_exec("top -bn1 | grep 'Cpu(s)'");
    if (preg_match('/(\d+\.\d+)\s*id/', $output, $matches)) {
        $cpuLibre = floatval($matches[1]);
        return round(100 - $cpuLibre, 2);
    }
    return null;
}

function usoMemoria() {
    $output = shell_exec("free -m");
    $lines = explode("\n", $output);
    if (isset($lines[1])) {
        $parts = preg_split('/\s+/', $lines[1]);
        return intval($parts[2]); // MB en uso
    }
    return null;
}

// Rutas
$csvPath = 'datos.csv';
$jsonPath = 'datos.json';
$dataCSV = leerCSV($csvPath);

// --- Operaciones ---
$operaciones = [];

function registrarOperacion($nombre, $formato, $callback, $archivo = null) {
    global $operaciones;
    $resultado = medirTiempo($callback);
    $resultado['operacion'] = $nombre;
    $resultado['formato'] = $formato;
    $resultado['tamano'] = $archivo ? obtenerTamanoArchivo($archivo) : '-';
    $resultado['cpu'] = usoCPU();
    $resultado['mem_sistema'] = usoMemoria();
    $operaciones[] = $resultado;
}

// Ejecuciones
registrarOperacion("Lectura", "CSV", function() use ($csvPath) {
    leerCSV($csvPath);
}, $csvPath);

registrarOperacion("Lectura", "JSON", function() use ($jsonPath) {
    leerJSON($jsonPath);
}, $jsonPath);

registrarOperacion("Escritura", "CSV", function() use ($dataCSV) {
    escribirCSV('nuevo.csv', $dataCSV);
}, 'nuevo.csv');

registrarOperacion("Escritura", "JSON", function() use ($dataCSV) {
    escribirJSON('nuevo.json', $dataCSV);
}, 'nuevo.json');

registrarOperacion("Conversión CSV → JSON", "-", function() use ($csvPath) {
    escribirJSON('convertido.json', leerCSV($csvPath));
}, 'convertido.json');

registrarOperacion("Conversión JSON → CSV", "-", function() use ($jsonPath) {
    escribirCSV('convertido.csv', leerJSON($jsonPath));
}, 'convertido.csv');

// Mostrar resultados
echo "Resultados de Rendimiento:\n";
foreach ($operaciones as $op) {
    echo "{$op['operacion']} | {$op['formato']} | Tiempo: {$op['tiempo']}s | Memoria PHP: {$op['memoria']}MB | Tamaño: {$op['tamano']}KB | CPU: {$op['cpu']}% | RAM: {$op['mem_sistema']}MB\n";
}
?>
