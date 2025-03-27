<?php
require 'vendor/autoload.php';
include 'db/conexion.php';
header('Content-Type: text/html; charset=utf-8');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

session_start();
$fecha_actual = date('Y-m-d');

if (isset($_GET['id'])) {
    $id_contrato = $_GET['id'];
} else if (isset($_POST['id'])) {
    $id_contrato = $_POST['id'];
} else {
    // Redirigir a una página de error o a la página de login si no hay ID
    header("Location: login.php");
    exit();
}
    
    $id_contrato = $_GET['id'] ?? $_POST['id'] ?? null;
    $query = "SELECT * FROM contrato_informe WHERE id = '$id_contrato'";
    $result = mysqli_query($conexion, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $contrato_data = mysqli_fetch_assoc($result);
        
        // Asignar los valores del contrato a variables de sesión
        $_SESSION['email_contratista_pro'] = $contrato_data['email_contratista'];
        $_SESSION['nombre_supervisor'] = $contrato_data['nombre_supervisor'];
        $_SESSION['email_supervisor'] = $contrato_data['email_supervisor'];
        $_SESSION['numero_contrato'] = $contrato_data['numero_contrato'];
        $_SESSION['anio'] = $contrato_data['anio'];
        $_SESSION['objeto'] = $contrato_data['objeto'];
        
        // Puedes agregar más campos según la estructura de tu tabla
    } else {
        echo "No se encontró el contrato especificado.";
        exit;
    }


    $cc_contratista = $_SESSION['cedula'];
    $nombres_contratista =  $_SESSION['nombres'];
    $email_contratista =  $_SESSION['email'];
    $celular_contratista = $_SESSION['celular'];
    $email_contratista_pro = $_SESSION['email_contratista_pro'];
    $nombre_supervisor = $_SESSION['nombre_supervisor'];
    $numero_contrato = $email_supervisor = $_SESSION['email_supervisor'];
    $numero_contrato = $_SESSION['numero_contrato'];
    $anio = $_SESSION['anio'];
    $objeto = $_SESSION['objeto'];

function formatDescriptionWithArialEight($text) {
    // Aplicar el formato Arial 8 a todo el texto
    return '</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="16"/><w:szCs w:val="16"/></w:rPr><w:t>' . $text . '</w:t></w:r><w:r><w:t>';
}
function createHyperlinkMarkup($text, $url) {
    // Mantener el formato Arial 8 incluso en los hipervínculos
    return '</w:t></w:r><w:r><w:fldChar w:fldCharType="begin"/></w:r><w:r><w:instrText xml:space="preserve"> HYPERLINK "' . $url . '" </w:instrText></w:r><w:r><w:fldChar w:fldCharType="separate"/></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="16"/><w:szCs w:val="16"/><w:u w:val="single"/><w:color w:val="0033CC"/></w:rPr><w:t>' . $text . '</w:t></w:r><w:r><w:fldChar w:fldCharType="end"/></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="16"/><w:szCs w:val="16"/></w:rPr><w:t>';
}
function formatTextWithArialEight($text) {
    return '</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="16"/><w:szCs w:val="16"/></w:rPr><w:t>' . $text . '</w:t></w:r><w:r><w:t>';
}
function wrapWithArialEight($content) {
    return '</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="16"/><w:szCs w:val="16"/></w:rPr><w:t>' . $content . '</w:t></w:r><w:r><w:t>';
}
function processDescriptionWithLinks($text) {
    // Patrón para URLs
    $pattern = '/(https?:\/\/[^\s<>"]+)/';
    
    // Dividir el texto en partes
    $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    $result = '';
    foreach ($parts as $i => $part) {
        if (preg_match($pattern, $part)) {
            // Es una URL
            $result .= createHyperlinkMarkup($part, $part);
        } else if (trim($part) !== '') {
            // Texto normal
            $result .= formatDescriptionWithArialEight($part);
        }
    }
    
    return $result;
}

function getMesActual() {
    $meses = array(
        1 => 'Enero', 'Febrero', 'Marzo', 'Abril',
        'Mayo', 'Junio', 'Julio', 'Agosto',
        'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    );
    return $meses[date('n')];
}

// Nueva función para formatear fechas en el formato "12 de enero del 2025"
function formatearFecha($fecha) {
    if (empty($fecha)) return '';
    
    $timestamp = strtotime($fecha);
    $dia = date('j', $timestamp);
    $mes = date('n', $timestamp);
    $anio = date('Y', $timestamp);
    
    $meses = array(
        1 => 'enero', 'febrero', 'marzo', 'abril',
        'mayo', 'junio', 'julio', 'agosto',
        'septiembre', 'octubre', 'noviembre', 'diciembre'
    );
    
    return $dia . ' de ' . $meses[$mes] . ' del ' . $anio;
}

if ($nombres_contratista == null || $id_contrato == null) {
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}else{
    echo '';
}

function replaceInSheet($sheet, $search, $replace) {
    foreach ($sheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $value = $cell->getValue();
            // Verificar que el valor no sea null y usar str_contains
            if ($value !== null && str_contains($value, $search)) {
                $cell->setValue(str_replace($search, $replace, $value));
            }
        }
    }
}


$fecha_actual = date('Y-m-d');
function createExcelFromTemplate($activities, $numero_contrato, $sessionVars) {
    $mesActual = getMesActual();
    $fecha_actual = date('Y-m-d');
    $templatePath = 'plantilla_excel.xlsx';
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    $replacements = [
        '${numero_contrato}' => $sessionVars['numero_contrato'],
        '${anio_contrato}' => $sessionVars['anio_contrato'],
        '${nombres}' => $sessionVars['nombres'],
        '${fecha_inicio}' => formatearFecha($_POST['startDate']),
        '${fecha_fin}' => formatearFecha($_POST['endDate']),
        '${nombre_supervisor}' => $sessionVars['nombre_supervisor'],
        '${objeto}' => $sessionVars['objeto'],
        '${valor_cobro}' => str_replace(['$', "'"], '', $_POST['valor_cobro']),
        '${nro_pago}' => $_POST['nro_pago'],
        '${total_pagos}' => $_POST['total_pagos'],
        // Variables del formulario de contrato
        '${fecha_inicio_contrato}' => formatearFecha($sessionVars['fecha_inicio']),
        '${fecha_fin_contrato}' => formatearFecha($sessionVars['fecha_fin']),
        '${valor_contrato}' => $sessionVars['valor'],
        '${forma_pago}' => $sessionVars['forma_pago'],
        '${rp}' => $sessionVars['rp'],
        '${cuenta_bancaria}' => $sessionVars['cuenta_bancaria'],
        '${banco}' => $sessionVars['banco_nombre'],
        '${tipo_cuenta}' => $sessionVars['tipo_cuenta'],
        '${fecha_actual}' => formatearFecha($fecha_actual),
        '${cc_contratista}' => $sessionVars['cc']
    ];

    foreach ($replacements as $search => $replace) {
        replaceInSheet($sheet, $search, $replace);
    }

   $outputFile = 'Cumplido_Autorizaciondegiro_' . $mesActual . '.xlsx';
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($outputFile);
    
    return $outputFile;
}

 
// Función para procesar el archivo Excel
function processExcel($inputFile) {
    try {
        $spreadsheet = IOFactory::load($inputFile);
        $worksheet = $spreadsheet->getActiveSheet();
        $activities = [];
        $tasks = [];
        $headers = [];

        // Mapeo de nombres de columnas en inglés y español
        $columnMapping = [
            'Bucket Name' => ['Bucket Name', 'Nombre del depósito'],
            'Task Name' => ['Task Name', 'Nombre de la tarea'],
            'Labels' => ['Labels', 'Etiquetas'],
            'Description' => ['Description', 'Descripción']
        ];

       // Obtener los encabezados
       foreach ($worksheet->getRowIterator()->current()->getCellIterator() as $cell) {
        $headers[$cell->getColumn()] = $cell->getValue();
    }

    // Encontrar los índices de las columnas necesarias
    $columnIndices = [];
    foreach ($columnMapping as $key => $possibleNames) {
        $columnIndices[$key] = null;
        foreach ($possibleNames as $name) {
            $index = array_search($name, $headers);
            if ($index !== false) {
                $columnIndices[$key] = $index;
                break;
            }
        }
        if ($columnIndices[$key] === null) {
            throw new Exception("No se encontró la columna $key.");
        }
    }

    // Procesar las filas
    foreach ($worksheet->getRowIterator() as $row) {
        $rowData = [];
        foreach ($row->getCellIterator() as $cell) {
            $rowData[$cell->getColumn()] = $cell->getValue();
        }

        $bucketName = $rowData[$columnIndices['Bucket Name']];
        $taskName = $rowData[$columnIndices['Task Name']];
        $label = $rowData[$columnIndices['Labels']];
        $description = $rowData[$columnIndices['Description']];

        if ($bucketName == 'Actividades' || $bucketName == 'Activities') {
            $activities[] = [
                'name' => $taskName,
                'label' => $label,
                'description' => $description,
                'tasks' => []
            ];
        } elseif ($bucketName == 'Terminadas' || $bucketName == 'Completed') {
            $tasks[] = [
                'name' => $taskName,
                'label' => $label,
                'description' => $description
            ];
        }
    }

    // Ordenar actividades y asignar tareas (sin cambios)
    usort($activities, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    foreach ($tasks as $task) {
        foreach ($activities as &$activity) {
            if ($activity['label'] == $task['label']) {
                $activity['tasks'][] = [
                    'name' => $task['name'],
                    'description' => $task['description']
                ];
                break;
            }
        }
    }

    return $activities;
} catch (Exception $e) {
    error_log('Error processing Excel: ' . $e->getMessage());
    throw new Exception('Error al procesar el archivo Excel: ' . $e->getMessage());
}
}

// Función para crear el documento Word
function createWordDocument($activities, $templateFile, $startDate, $endDate, $sessionVars) {
    try {
        $templateProcessor = new TemplateProcessor($templateFile);

        // Formatear fechas en el estilo deseado (12 de enero del 2025)
        $formattedStartDate = formatearFecha($startDate);
        $formattedEndDate = formatearFecha($endDate);

        $templateProcessor->setValue('fecha_inicio', $formattedStartDate);
        $templateProcessor->setValue('fecha_fin', $formattedEndDate);

        foreach ($sessionVars as $key => $value) {
            $templateProcessor->setValue($key, htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
        }

        $templateProcessor->setValue('nro_pago', $_POST['nro_pago']);
        $templateProcessor->setValue('total_pagos', $_POST['total_pagos']);
        $templateProcessor->setValue('valor_cobro', $_POST['valor_cobro']);
        $templateProcessor->setValue('fecha', formatearFecha(date('Y-m-d')));
        $templateProcessor->setValue('total_actividades', count($activities));

        if (!empty($activities)) {
            $templateProcessor->cloneRow('actividad', count($activities));
            foreach ($activities as $index => $activity) {
                $rowIndex = $index + 1;
                $activityName = htmlspecialchars($activity['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $activityDescription = htmlspecialchars($activity['description'] ?? '', ENT_QUOTES, 'UTF-8');
                $actividadConDescripcion = $activityName . ($activityDescription ? ": " . $activityDescription : '');
                $templateProcessor->setValue("actividad#$rowIndex", $actividadConDescripcion);
                $templateProcessor->setValue("label#$rowIndex", htmlspecialchars($activity['label'] ?? '', ENT_QUOTES, 'UTF-8'));
                
                $tasksNames = [];
                $tasksDescriptions = [];
                foreach ($activity['tasks'] as $task) {
                    $taskName = htmlspecialchars($task['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    if ($taskName) {
                        $tasksNames[] = formatDescriptionWithArialEight("• " . $taskName);
                    }
                    
                    if (!empty($task['description'])) {
                        $taskDescription = htmlspecialchars($task['description'], ENT_QUOTES, 'UTF-8');
                        // Procesar la descripción y asegurar que todo tiene el formato correcto
                        $processedDesc = formatDescriptionWithArialEight("• ") . processDescriptionWithLinks($taskDescription);
                        $tasksDescriptions[] = $processedDesc;
                    }
                }
                
                // Asegurar que los saltos de línea también mantienen el formato
                $doubleLineBreak = '</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="16"/><w:szCs w:val="16"/></w:rPr><w:br/><w:br/><w:t>';
                
                $templateProcessor->setValue(
                    "tareas#$rowIndex", 
                    wrapWithArialEight(implode($doubleLineBreak, $tasksNames) ?: 'Está actividad no se realizó en el periodo de este informe, sin embargo, se realizará en el marco del contrato.')
                );
                
                $templateProcessor->setValue(
                    "tareas_descripcion#$rowIndex", 
                    implode($doubleLineBreak, $tasksDescriptions) ?: wrapWithArialEight(''), 
                    1
                );
            }
        } else {
            $templateProcessor->setValue('actividad', 'No se encontraron actividades');
            $templateProcessor->setValue('label', 'N/A');
            $templateProcessor->setValue('tareas', wrapWithArialEight('N/A'));
            $templateProcessor->setValue('tareas_descripcion', wrapWithArialEight('N/A'));
        }

        $mesActual = getMesActual();
        $outputFile = 'Informe_Actividades_' . $mesActual . '.docx';
        $templateProcessor->saveAs($outputFile);

        return $outputFile;
    } catch (Exception $e) {
        error_log('Error creating Word document: ' . $e->getMessage());
        throw new Exception('Error al crear el documento Word: ' . $e->getMessage());
    }
}

function createExcelOutput($activities, $numero_contrato) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($numero_contrato);
    
    // Headers
    $sheet->setCellValue('A1', 'Actividad');
    $sheet->setCellValue('B1', 'Etiqueta');
    $sheet->setCellValue('C1', 'Tareas');
    
    $row = 2;
    foreach ($activities as $activity) {
        $sheet->setCellValue('A' . $row, $activity['name']);
        $sheet->setCellValue('B' . $row, $activity['label']);
        $tasks = array_map(function($task) {
            return $task['name'];
        }, $activity['tasks']);
        $sheet->setCellValue('C' . $row, implode("\n", $tasks));
        $row++;
    }
    
    $excelFile = 'informe_' . $numero_contrato . '_' . date('YmdHis') . '.xlsx';
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($excelFile);
    
    return $excelFile;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excelFile"])) {
    try {
        $targetFile = $_FILES["excelFile"]["tmp_name"];
        $fileType = strtolower(pathinfo($_FILES["excelFile"]["name"], PATHINFO_EXTENSION));
 
        if ($fileType != "xlsx" && $fileType != "xls") {
            throw new Exception("Solo se permiten archivos XLSX o XLS.");
        }
 
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
        $activities = processExcel($targetFile);
        $templateFile = __DIR__ . '/plantilla.docx';
        $templateExcel = __DIR__ . '/plantilla_excel.xlsx';
 
        $banco_query = "SELECT b.nombre as banco_nombre FROM contrato_informe c 
                   LEFT JOIN banco b ON c.banco_id = b.id 
                   WHERE c.id = '$id_contrato'";
        $banco_result = mysqli_query($conexion, $banco_query);
        $banco_data = mysqli_fetch_assoc($banco_result);
 
        $sessionVars = [
            'cc' => $_SESSION['cedula'] ?? '',
            'nombres' => $_SESSION['nombres'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'celular' => $_SESSION['celular'] ?? '',
            'email_contratista_pro' => $_SESSION['email_contratista_pro'] ?? '',
            'nombre_supervisor' => $_SESSION['nombre_supervisor'] ?? '',
            'email_supervisor' => $_SESSION['email_supervisor'] ?? '',
            'numero_contrato' => $_SESSION['numero_contrato'] ?? '',
            'anio_contrato' => $_SESSION['anio'] ?? '',
            'objeto' => $_SESSION['objeto'] ?? '',
            'fecha_inicio' => $contrato_data['fecha_inicio'] ?? '',
            'fecha_fin' => $contrato_data['fecha_fin'] ?? '',
            'valor' => str_replace(['$', "'"], '', $contrato_data['valor'] ?? ''),
            'forma_pago' => $contrato_data['forma_pago'] ?? '',
            'rp' => $contrato_data['rp'] ?? '',
            'cuenta_bancaria' => $contrato_data['cuenta_bancaria'] ?? '',
            'banco_nombre' => $banco_data['banco_nombre'] ?? '',
            'tipo_cuenta' => $contrato_data['tipo_cuenta'] ?? '',
            'valor_cobro' => str_replace(['$', "'"], '', $_POST['valor_cobro'] ?? ''),
            'nro_pago' => $_POST['nro_pago'] ?? '',
            'total_pagos' => $_POST['total_pagos'] ?? ''
        ];

        // Obtener el mes actual y la cédula
        $mesActual = getMesActual();
        $cedula = $_SESSION['cedula'];
 
        // Generar archivos con los nuevos nombres
        $wordFile = createWordDocument($activities, $templateFile, $startDate, $endDate, $sessionVars);
        $excelFile = createExcelFromTemplate($activities, $_SESSION['numero_contrato'], $sessionVars);
 
        // Renombrar los archivos con el formato solicitado
        $newWordFile = 'Informe_Actividades_' . $mesActual . '.docx';
        $newExcelFile = 'Cumplido_Autorizaciondegiro_' . $mesActual . '.xlsx';
        rename($wordFile, $newWordFile);
        rename($excelFile, $newExcelFile);
 
        if (!file_exists($newWordFile) || !file_exists($newExcelFile)) {
            throw new Exception("Error generando archivos");
        }
 
        $zip = new ZipArchive();
        $zipName = $mesActual . '_' . $cedula . '.zip';
        
        if ($zip->open($zipName, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Error creando ZIP");
        }
 
        $zip->addFile($newWordFile, basename($newWordFile));
        $zip->addFile($newExcelFile, basename($newExcelFile));
        $zip->close();
 
        if (!file_exists($zipName)) {
            throw new Exception("Error: ZIP no creado");
        }
 
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$zipName.'"');
        header('Content-Length: ' . filesize($zipName));
        readfile($zipName);
 
        // Limpieza de archivos temporales
        unlink($newWordFile);
        unlink($newExcelFile);
        unlink($zipName);
        exit;
 
    } catch (Exception $e) {
        error_log('Error creating Word document: ' . $e->getMessage());
        throw new Exception('Error al crear el documento Word: ' . $e->getMessage());
    }
}else {
    // Mostrar el formulario si no se ha enviado un archivo
  ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cargar Archivo Base y Generar Documentos</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        .header {
            background-color: #003366;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 100px;
        }
        .logout-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        .logout-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .logout-btn:hover {
            background-color: #ff3333;
        }
        .logo {
            height: 60px;
        }
        .title {
            font-size: 1.2em;
            text-align: center;
            flex-grow: 1;
            margin: 0 20px;
        }
        .logout-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .logout-btn:hover {
            background-color: #ff3333;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: center;
            }
            .logo {
                margin-bottom: 10px;
            }
            .title {
                font-size: 1em;
                margin-bottom: 10px;
            }
        }
        .contratos-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px auto ;
        font-family: Arial, sans-serif;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    .contratos-table th {
        background-color: #f8f9fa;
        color: #333;
        text-align: left;
        padding: 12px;
        font-weight: bold;
        border-bottom: 2px solid #dee2e6;
    }
    .contratos-table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
    }
    .contratos-table tr:hover {
        background-color: #f5f5f5;
    }
    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }
    .btn-ver {
        background-color: #007bff;
        color: white;
    }
    .btn-modificar {
        background-color: #ffc107;
        color: black;
    }
    .btn-borrar {
        background-color: #dc3545;
        color: white;
    }
    </style>
    <body class="bg-gray-100 p-8">
    <header class="header">
        <img src="images/ud.png" alt="Logo Universidad Distrital" class="logo" id="logo-ud">
        <div class="title">
            Universidad Distrital Francisco José de Caldas<br>
            Oficina de Extensión
        </div>
        <img src="images/idexud.png" alt="Logo Oficina de Extensión" class="logo" id="logo-extension">
    </header>

    <table class="contratos-table">
    <thead>
        <tr>
            
            <th>NÚMERO DE CONTRATO</th>
            <th>AÑO</th>
            <th>OBJETO</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php echo htmlspecialchars($numero_contrato); ?></td>
            <td><?php echo htmlspecialchars($anio); ?></td>
            <td><?php echo htmlspecialchars($objeto); ?></td>
        </tr>
    </tbody>
</table>


    <script>
        function adjustLogoSize() {
            const logos = document.querySelectorAll('.logo');
            const screenWidth = window.innerWidth;
            
            logos.forEach(logo => {
                if (screenWidth <= 480) {
                    logo.style.height = '40px';
                } else if (screenWidth <= 768) {
                    logo.style.height = '50px';
                } else {
                    logo.style.height = '60px';
                }
            });
        }
        
    </script>


        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
            <div class="md:flex">
                <div class="p-8">
                    <h2 class="block mt-1 text-lg leading-tight font-medium text-black">Cargar Archivo Base y Generar Documentos</h2>
                    <form action="<?php echo htmlspecialchars("./index.php?id=" . $id_contrato); ?>" method="post" enctype="multipart/form-data" class="mt-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="excelFile">
                                Selecciona el archivo Excel:
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="file" name="excelFile" id="excelFile" accept=".xlsx,.xls" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="startDate">
                                Fecha de inicio del informe:
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="date" name="startDate" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="endDate">
                                Fecha de fin del informe:
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="date" name="endDate" required>
                        </div>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_contrato); ?>">
                        <div class="mb-4">
   <label class="block text-gray-700 text-sm font-bold mb-2">
       Perfil:
   </label>
   <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
       id="tipo_contrato" onchange="sugerirValor()">
       <option value="">Seleccione tipo</option>
       <option value="3133219">Asistencial - $3'133.219</option>
       <option value="3759864">Técnico - $3'759.864</option>
       <option value="5765126">Profesional - $5'765.126</option>
       <option value="7519727">Especializado - $7'519.727</option>
       <option value="10026303">Asesor I - $10'026.303</option>
   </select>
</div>

<div class="mb-4">
   <label class="block text-gray-700 text-sm font-bold mb-2" for="nro_pago">
       Número de pago a cobrar:
   </label>
   <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
       type="number" name="nro_pago" id="nro_pago" min="1" required>
</div>

<div class="mb-4">
   <label class="block text-gray-700 text-sm font-bold mb-2" for="total_pagos">
       Total de pagos:
   </label>
   <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
       type="number" name="total_pagos" id="total_pagos" min="1" required>
</div>

<div class="mb-4">
   <label class="block text-gray-700 text-sm font-bold mb-2" for="valor_cobro">
       Valor a cobrar:
   </label>
   <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
       type="text" name="valor_cobro" id="valor_cobro" 
       oninput="formatearValor(this)" required>
</div>

<script>
// Función para formatear la moneda correctamente
function formatCurrency(value) {
   // Eliminar caracteres no numéricos
   value = value.toString().replace(/[^\d]/g, '');
   // Formatear con $ y separador de miles con comilla simple
   return '$' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "'");
}

// Función para formatear el valor del input
function formatearValor(input) {
   input.value = formatCurrency(input.value);
}

// Función para sugerir el valor al seleccionar un perfil
function sugerirValor() {
   var valor = document.getElementById('tipo_contrato').value;
   if(valor) {
       document.getElementById('valor_cobro').value = formatCurrency(valor);
   }
}

// Asignar el evento onchange al select
document.addEventListener('DOMContentLoaded', function() {
   // Asegurar que los elementos existen
   var tipoContrato = document.getElementById('tipo_contrato');
   if(tipoContrato) {
       // Asignar el evento onchange
       tipoContrato.onchange = sugerirValor;
   }
});
</script>
                        <div class="flex items-center justify-between">
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                                Descargar Informe de Gestión y Cumplido
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

       

        <div class="logout-container">
            <a href="back/logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </body>
    </html>
    <?php
}
