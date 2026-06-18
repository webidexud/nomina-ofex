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
        $_SESSION['fecha_contrato'] = $contrato_data['fecha_contrato'];
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
    $fecha_contrato = $_SESSION['fecha_contrato'];
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
            'Bucket Name' => [
                'Bucket Name',
                'Nombre del depósito',
                'Depósito',
                'Nombre del cubo',      // ← Planner en español
                'Cubo',
                'Bucket',
            ],
            'Task Name' => [
                'Task Name',
                'Nombre de la tarea',
                'Tarea',
                'Task',
            ],
            'Labels' => [
                'Labels',
                'Etiquetas',
                'Label',
                'Etiqueta',
            ],
            'Description' => [
                'Description',
                'Descripción',
                'Notas',
                'Notes',
                'Nota',
            ]
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
            'anio_contrato' => date('Y', strtotime($contrato_data['fecha_contrato'] ?? date('Y-m-d'))),
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
    <title>Cargar Archivo Base y Generar Documentos | Universidad Distrital</title>
    <meta name="description" content="Plataforma de Gestión de Honorarios de la Universidad Distrital Francisco José de Caldas">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png"/>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #003366;
            --primary-dark: #002244;
            --primary-light: #004488;
            --primary-ultra-light: #E6F0FF;
            --accent-color: #FF8C00;
            --accent-hover: #FF7000;
            --white: #FFFFFF;
            --light-gray: #F5F7FA;
            --medium-gray: #E0E5EC;
            --dark-gray: #6B7280;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --success: #10B981;
            --error: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-container {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 15px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: 1;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo-wrapper {
            display: flex;
            align-items: center;
        }
        
        .logo-img {
            height: 45px;
            margin-right: 15px;
            filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
        }
        
        .app-title {
            font-size: 1.3rem;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            background-color: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            backdrop-filter: blur(5px);
        }
        
        .user-name {
            margin-right: 15px;
            font-weight: 500;
        }
        
        .logout-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .logout-btn {
            background-color: var(--error);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }
        
        .logout-btn:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .logout-icon {
            margin-right: 0.5rem;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(209, 213, 219, 0.3);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 18px 24px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--accent-color), var(--accent-light));
        }
        
        .card-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        }
        
        .card-icon {
            margin-right: 12px;
            font-size: 1.2rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 24px;
        }
        
        .contratos-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin: 20px 0;
        }
        
        .contratos-table th, .contratos-table td {
            padding: 14px 18px;
            text-align: left;
        }
        
        .contratos-table th {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: var(--white);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }
        
        .contratos-table th:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 25%;
            height: 50%;
            width: 1px;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .contratos-table tr {
            transition: background-color 0.2s ease;
        }
        
        .contratos-table tr:nth-child(even) {
            background-color: var(--light-gray);
        }
        
        .contratos-table tr:hover {
            background-color: var(--primary-ultra-light);
        }
        
        .contratos-table td {
            border-bottom: 1px solid var(--medium-gray);
        }
        
        .contratos-table tr:last-child td {
            border-bottom: none;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }
        
        .form-input-wrapper {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--medium-gray);
            border-radius: 0.375rem;
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
            pointer-events: none;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-icon {
            margin-right: 0.5rem;
        }
        
        .footer {
            text-align: center;
            padding: 1.5rem;
            background-color: var(--white);
            color: var(--text-light);
            font-size: 0.8rem;
            border-top: 1px solid var(--medium-gray);
            margin-top: auto;
        }
        
        /* Para los campos específicos del formulario */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        /* Animación */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fadeIn {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .delay-1 {
            animation-delay: 0.1s;
        }
        
        .delay-2 {
            animation-delay: 0.2s;
        }
        
        .delay-3 {
            animation-delay: 0.3s;
        }
        
        /* Media queries para responsividad */
        @media (max-width: 992px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .logo-wrapper {
                margin-bottom: 0.75rem;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-wrapper">
                <img src="images/LOGO_IDEXUD.png" alt="Logo IDEXUD" class="logo-img">
                <h1 class="app-title">Sistema de Gestión de Honorarios</h1>
            </div>
            <div class="user-info">
                <span class="user-name"><i class="fas fa-user-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($_SESSION['nombres']); ?></span>
                <a href="contratos.php" class="btn btn-secondary" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left btn-icon"></i>
                    Volver
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="card fadeIn">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-file-contract card-icon"></i>
                    Información del Contrato
                </h2>
            </div>
            <div class="card-body">
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
                            <td><?php echo htmlspecialchars($fecha_contrato); ?></td>
                            <td><?php echo htmlspecialchars($objeto); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="card fadeIn delay-1">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-upload card-icon"></i>
                            Cargar Archivo Base y Generar Documentos
                        </h2>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars("./index.php?id=" . $id_contrato); ?>" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label" for="excelFile">
                                    Selecciona el archivo Excel:
                                </label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-file-excel input-icon"></i>
                                    <input class="form-input" type="file" name="excelFile" id="excelFile" accept=".xlsx,.xls" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="startDate">
                                        Fecha de inicio del informe:
                                    </label>
                                    <div class="form-input-wrapper">
                                        <i class="fas fa-calendar-alt input-icon"></i>
                                        <input class="form-input" type="date" name="startDate" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="endDate">
                                        Fecha de fin del informe:
                                    </label>
                                    <div class="form-input-wrapper">
                                        <i class="fas fa-calendar-check input-icon"></i>
                                        <input class="form-input" type="date" name="endDate" required>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_contrato); ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Perfil:</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-user-tag input-icon"></i>
                                    <select class="form-input" id="tipo_contrato" onchange="sugerirValor()">
                                        <option value="">Seleccione tipo</option>
                                        <option value="3293013">Asistencial - $3'293.013</option>
                                        <option value="3951617">Técnico - $3'951.617</option>
                                        <option value="6059147">Profesional - $6'059.147</option>
                                        <option value="7903233">Especializado - $7'903.233</option>
                                        <option value="10537644">Asesor I - $10'537.644</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="nro_pago">Número de pago a cobrar:</label>
                                    <div class="form-input-wrapper">
                                        <i class="fas fa-list-ol input-icon"></i>
                                        <input class="form-input" type="number" name="nro_pago" id="nro_pago" min="1" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="total_pagos">Total de pagos:</label>
                                    <div class="form-input-wrapper">
                                        <i class="fas fa-calculator input-icon"></i>
                                        <input class="form-input" type="number" name="total_pagos" id="total_pagos" min="1" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="valor_cobro">Valor a cobrar:</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-dollar-sign input-icon"></i>
                                    <input class="form-input" type="text" name="valor_cobro" id="valor_cobro" oninput="formatearValor(this)" required>
                                </div>
                            </div>
                            
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-download btn-icon"></i>
                                Descargar Informe de Gestión y Cumplido
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="logout-container">
        <a href="back/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt logout-icon"></i>
            Cerrar Sesión
        </a>
    </div>
    
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>

    <script>
        function adjustLogoSize() {
            const logos = document.querySelectorAll('.logo-img');
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

        // Al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            adjustLogoSize();
            window.addEventListener('resize', adjustLogoSize);

            // Mejorar interacción de los campos
            const inputs = document.querySelectorAll('.form-input');
            
            inputs.forEach(input => {
                // Cambiar color del icono al enfocar el campo
                input.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    if (icon) {
                        icon.style.color = 'var(--primary-color)';
                    }
                });
                
                // Restaurar color del icono al perder el foco
                input.addEventListener('blur', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    if (icon) {
                        icon.style.color = 'var(--dark-gray)';
                    }
                });
            });
        });
    </script>
</body>
</html>
    <?php
}
