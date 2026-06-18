<?php
ob_start();
require 'vendor/autoload.php';
include 'db/conexion.php';
header('Content-Type: text/html; charset=utf-8');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

session_start();
$fecha_actual = date('Y-m-d');

// Verificar si hay datos en la sesión para generar el informe
if (!isset($_SESSION['informe_fecha_inicio']) || !isset($_SESSION['informe_fecha_fin']) || !isset($_SESSION['informe_valor_cobro'])) {
    header("Location: contratos.php");
    exit();
}

// Obtener el ID del contrato
if (isset($_GET['id'])) {
    $id_contrato = $_GET['id'];
} else {
    header("Location: contratos.php");
    exit();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['cedula'])) {
    header("Location: login.php");
    exit();
}

$cc_contratista = $_SESSION['cedula'];

// Consulta para obtener datos del contrato
$query = "SELECT ci.*, b.nombre as banco_nombre, p.nombre as proceso_nombre 
          FROM contrato_informe ci 
          LEFT JOIN banco b ON ci.banco_id = b.id 
          LEFT JOIN proceso p ON ci.proceso_id = p.id 
          WHERE ci.id = ? AND ci.cc_contratista = ?";
$stmt = mysqli_prepare($conexion, $query);

if (!$stmt) {
    die("Error en la preparación de la consulta: " . mysqli_error($conexion));
}

mysqli_stmt_bind_param($stmt, "is", $id_contrato, $cc_contratista);
if (!mysqli_stmt_execute($stmt)) {
    die("Error al ejecutar la consulta: " . mysqli_error($conexion));
}

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    // El contrato no existe o no pertenece al usuario actual
    header("Location: contratos.php");
    exit();
}

$contrato = mysqli_fetch_assoc($result);

// Obtener datos bancarios del contratista
// Obtener datos adicionales del banco si es necesario
if (isset($contrato['banco_id']) && !empty($contrato['banco_id'])) {
    $banco_query = "SELECT nombre FROM banco WHERE id = ?";
    $banco_stmt = mysqli_prepare($conexion, $banco_query);
    mysqli_stmt_bind_param($banco_stmt, "i", $contrato['banco_id']);
    mysqli_stmt_execute($banco_stmt);
    $banco_result = mysqli_stmt_get_result($banco_stmt);
    if ($banco_data = mysqli_fetch_assoc($banco_result)) {
        $contrato['banco_nombre'] = $banco_data['nombre'];
    }
}

// Obtener las actividades contractuales
$actividades_query = "SELECT * FROM contrato_actividades 
                      WHERE contrato_id = ? AND cc_contratista = ? 
                      ORDER BY orden ASC";
$actividades_stmt = mysqli_prepare($conexion, $actividades_query);
mysqli_stmt_bind_param($actividades_stmt, "is", $id_contrato, $cc_contratista);
mysqli_stmt_execute($actividades_stmt);
$actividades_result = mysqli_stmt_get_result($actividades_stmt);

$actividades = [];
while ($actividad = mysqli_fetch_assoc($actividades_result)) {
    $actividades[] = $actividad;
}

// Obtener las tareas y evidencias
$tareas_query = "SELECT * FROM contrato_tareas 
                 WHERE contrato_id = ? AND cc_contratista = ? 
                 ORDER BY actividad_id, orden ASC";
$tareas_stmt = mysqli_prepare($conexion, $tareas_query);
mysqli_stmt_bind_param($tareas_stmt, "is", $id_contrato, $cc_contratista);
mysqli_stmt_execute($tareas_stmt);
$tareas_result = mysqli_stmt_get_result($tareas_stmt);

$tareas = [];
while ($tarea = mysqli_fetch_assoc($tareas_result)) {
    $tareas[] = $tarea;
}

// Obtener las evidencias
$evidencias_query = "SELECT * FROM contrato_evidencias 
                     WHERE contrato_id = ? AND cc_contratista = ?";
$evidencias_stmt = mysqli_prepare($conexion, $evidencias_query);
mysqli_stmt_bind_param($evidencias_stmt, "is", $id_contrato, $cc_contratista);
mysqli_stmt_execute($evidencias_stmt);
$evidencias_result = mysqli_stmt_get_result($evidencias_stmt);

$evidencias = [];
while ($evidencia = mysqli_fetch_assoc($evidencias_result)) {
    $evidencias[$evidencia['tarea_id']] = $evidencia;
}

// Organizar tareas por actividad
$tareas_por_actividad = [];
foreach ($tareas as $tarea) {
    $actividad_id = $tarea['actividad_id'];
    if (!isset($tareas_por_actividad[$actividad_id])) {
        $tareas_por_actividad[$actividad_id] = [];
    }
    $tareas_por_actividad[$actividad_id][] = $tarea;
}

// Obtener datos del informe desde la sesión
$fecha_inicio = $_SESSION['informe_fecha_inicio'];
$fecha_fin = $_SESSION['informe_fecha_fin'];
$valor_cobro = $_SESSION['informe_valor_cobro'];
$nro_pago = $_SESSION['informe_nro_pago'];
$total_pagos = $_SESSION['informe_total_pagos'];

// Limpiar variables de sesión después de usarlas
unset($_SESSION['informe_fecha_inicio']);
unset($_SESSION['informe_fecha_fin']);
unset($_SESSION['informe_valor_cobro']);
unset($_SESSION['informe_nro_pago']);
unset($_SESSION['informe_total_pagos']);

// Función para obtener el mes actual en texto
function getMesActual() {
    $meses = array(
        1 => 'Enero', 'Febrero', 'Marzo', 'Abril',
        'Mayo', 'Junio', 'Julio', 'Agosto',
        'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    );
    return $meses[date('n')];
}

function formatearFechaTextoCompleto($fecha) {
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
    
    return $dia . ' de ' . $meses[$mes] . ' de ' . $anio;
}

// Función para formatear fechas
function formatearFechaSimple($fecha) {
    if (empty($fecha)) {
        error_log('Fecha vacía recibida en formatearFechaSimple');
        return '';
    }
    
    // Verificar si la fecha ya está en formato correcto
    if (strpos($fecha, '/') !== false) {
        return $fecha;
    }
    
    $timestamp = strtotime($fecha);
    
    if ($timestamp === false) {
        error_log('Error al convertir fecha: ' . $fecha);
        return $fecha;
    }
    
    return date('d/m/Y', $timestamp);
}

// Función para formatear fechas en texto (12 de enero del 2025)
function formatearFechaTexto($fecha) {
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

// Función para limpiar y formatear valores monetarios
function limpiarValorMonetario($valor) {
    // Eliminar todos los caracteres que no sean dígitos o punto
    return preg_replace('/[^0-9.]/', '', $valor);
}

// Función para obtener el mes anterior (para seguridad social)
function obtenerMesAnteriorSegSocial($fecha) {
    $timestamp = strtotime($fecha);
    // Restamos un mes
    $mes_anterior_timestamp = strtotime('-1 month', $timestamp);
    
    $meses = array(
        1 => 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL',
        'MAYO', 'JUNIO', 'JULIO', 'AGOSTO',
        'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
    );
    
    $mes_numero = date('n', $mes_anterior_timestamp);
    $anio = date('Y', $mes_anterior_timestamp);
    
    return [
        'mes' => $meses[$mes_numero],
        'anio' => $anio
    ];
}

// Calcular el porcentaje de ejecución en tiempo
function calcularPorcentajeEjecucionTiempo($fecha_inicio_contrato, $fecha_fin_contrato, $fecha_fin_informe) {
    $inicio_timestamp = strtotime($fecha_inicio_contrato);
    $fin_timestamp = strtotime($fecha_fin_contrato);
    $fin_informe_timestamp = strtotime($fecha_fin_informe);
    
    // Asegurarse de que la fecha de fin de informe no exceda la fecha de fin de contrato
    if ($fin_informe_timestamp > $fin_timestamp) {
        $fin_informe_timestamp = $fin_timestamp;
    }
    
    // Duración total del contrato en días
    $duracion_total = ($fin_timestamp - $inicio_timestamp) / (60 * 60 * 24);
    
    // Duración ejecutada hasta la fecha de fin de informe
    $duracion_ejecutada = ($fin_informe_timestamp - $inicio_timestamp) / (60 * 60 * 24);
    
    // Calcular porcentaje
    $porcentaje_ejecutado = ($duracion_ejecutada / $duracion_total) * 100;
    
    // Redondear a 2 decimales
    $porcentaje_ejecutado = round($porcentaje_ejecutado, 2);
    
    // Porcentaje pendiente
    $porcentaje_pendiente = 100 - $porcentaje_ejecutado;
    
    return [
        'ejecutado' => $porcentaje_ejecutado,
        'pendiente' => $porcentaje_pendiente
    ];
}

// Calcular valores de ejecución financiera
function calcularValoresEjecucion($fecha_inicio_contrato, $fecha_fin_contrato, $fecha_inicio_informe, $fecha_fin_informe, $valor_contrato) {
    // Limpiar valor del contrato para tener solo el número
    $valor_contrato = limpiarValorMonetario($valor_contrato);
    
    // Verificar que el valor del contrato sea válido
    if ($valor_contrato <= 0) {
        return [
            'valor_a_cobrar' => 0,
            'valor_ejecutado' => 0,
            'valor_pendiente' => 0,
            'nro_pago' => 1,
            'total_pagos' => 1
        ];
    }
    
    // Convertir fechas a objetos DateTime con manejo de errores
    try {
        $inicio_contrato = new DateTime($fecha_inicio_contrato);
        $fin_contrato = new DateTime($fecha_fin_contrato);
        $inicio_informe = new DateTime($fecha_inicio_informe);
        $fin_informe = new DateTime($fecha_fin_informe);
    } catch (Exception $e) {
        return [
            'valor_a_cobrar' => 0,
            'valor_ejecutado' => 0,
            'valor_pendiente' => 0,
            'nro_pago' => 1,
            'total_pagos' => 1
        ];
    }
    
    // Verificar que la fecha de fin de informe no exceda la fecha de fin de contrato
    if ($fin_informe > $fin_contrato) {
        $fin_informe = clone $fin_contrato;
    }
    
    // Calcular días totales del contrato
    $dias_totales = $inicio_contrato->diff($fin_contrato)->days + 1;
    
    // Valor diario del contrato
    $valor_diario = $valor_contrato / max(1, $dias_totales);
    
    // Calcular días del periodo del informe
    $dias_periodo = $inicio_informe->diff($fin_informe)->days + 1;
    
    // Valor a cobrar del periodo actual
    $valor_a_cobrar = round($valor_diario * $dias_periodo);
    
    // Calcular valor ya ejecutado (hasta el día anterior al inicio del informe)
    $dia_anterior_informe = clone $inicio_informe;
    $dia_anterior_informe->modify('-1 day');
    
    // Si la fecha de inicio del informe es posterior a la fecha de inicio del contrato
    if ($inicio_informe > $inicio_contrato) {
        $dias_ejecutados = $inicio_contrato->diff($dia_anterior_informe)->days + 1;
        $valor_ejecutado = round($valor_diario * $dias_ejecutados);
    } else {
        // Si este es el primer periodo, no hay valor ejecutado previo
        $valor_ejecutado = 0;
    }
    
    // Valor pendiente después de este periodo
    $valor_pendiente = $valor_contrato - $valor_ejecutado - $valor_a_cobrar;
    
    // Si por alguna razón el valor pendiente es negativo, lo ajustamos a cero
    if ($valor_pendiente < 0) {
        $valor_pendiente = 0;
    }
    
    // Calcular número de pago y total de pagos
    $intervalo_total = $inicio_contrato->diff($fin_contrato);
    $meses_totales = $intervalo_total->y * 12 + $intervalo_total->m + ($intervalo_total->d > 0 ? 1 : 0);
    $meses_totales = max(1, $meses_totales);
    
    $intervalo_actual = $inicio_contrato->diff($inicio_informe);
    $meses_ejecutados = $intervalo_actual->y * 12 + $intervalo_actual->m;
    $nro_pago = min($meses_ejecutados + 1, $meses_totales);
    
    return [
        'valor_a_cobrar' => $valor_a_cobrar,
        'valor_ejecutado' => $valor_ejecutado,
        'valor_pendiente' => $valor_pendiente,
        'nro_pago' => $nro_pago,
        'total_pagos' => $meses_totales
    ];
}

// Convertir número a letras en español
function numeroALetras($numero) {
    $numero = preg_replace('/[^0-9.]/', '', $numero);
    $numero = intval($numero);
    
    $unidades = array('', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE');
    $decenas = array('', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA');
    $centenas = array('', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS');
    
    // Arrays para los números especiales
    $especial_10_a_19 = array('DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE');
    $especial_20_a_29 = array('VEINTE', 'VEINTIÚN', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE');
    
    if ($numero == 0) return 'CERO PESOS MONEDA CORRIENTE';
    
    $letras = '';
    
    // Millones
    $millones = floor($numero / 1000000);
    if ($millones == 1) {
        $letras .= 'UN MILLÓN ';
    } else if ($millones > 1) {
        // Función auxiliar para convertir un grupo de números
        $letras_millones = '';
        $centena = floor($millones / 100);
        if ($centena > 0) {
            if ($centena == 1 && $millones == 100) {
                $letras_millones .= 'CIEN ';
            } else {
                $letras_millones .= $centenas[$centena] . ' ';
            }
        }
        $millones %= 100;
        
        if ($millones > 0) {
            if ($millones < 10) {
                $letras_millones .= $unidades[$millones] . ' ';
            } else if ($millones < 20) {
                $letras_millones .= $especial_10_a_19[$millones - 10] . ' ';
            } else if ($millones < 30) {
                $letras_millones .= $especial_20_a_29[$millones - 20] . ' ';
            } else {
                $decena = floor($millones / 10);
                $unidad = $millones % 10;
                
                $letras_millones .= $decenas[$decena];
                if ($unidad > 0) {
                    $letras_millones .= ' Y ' . $unidades[$unidad] . ' ';
                } else {
                    $letras_millones .= ' ';
                }
            }
        }
        
        $letras .= $letras_millones . 'MILLONES ';
    }
    $numero %= 1000000;
    
    // Miles
    $miles = floor($numero / 1000);
    if ($miles == 1) {
        $letras .= 'MIL ';
    } else if ($miles > 1) {
        // Función auxiliar para convertir un grupo de números
        $letras_miles = '';
        $centena = floor($miles / 100);
        if ($centena > 0) {
            if ($centena == 1 && $miles == 100) {
                $letras_miles .= 'CIEN ';
            } else {
                $letras_miles .= $centenas[$centena] . ' ';
            }
        }
        $miles %= 100;
        
        if ($miles > 0) {
            if ($miles < 10) {
                $letras_miles .= $unidades[$miles] . ' ';
            } else if ($miles < 20) {
                $letras_miles .= $especial_10_a_19[$miles - 10] . ' ';
            } else if ($miles < 30) {
                $letras_miles .= $especial_20_a_29[$miles - 20] . ' ';
            } else {
                $decena = floor($miles / 10);
                $unidad = $miles % 10;
                
                $letras_miles .= $decenas[$decena];
                if ($unidad > 0) {
                    $letras_miles .= ' Y ' . $unidades[$unidad] . ' ';
                } else {
                    $letras_miles .= ' ';
                }
            }
        }
        
        $letras .= $letras_miles . 'MIL ';
    }
    $numero %= 1000;
    
    // Centenas, decenas y unidades
    if ($numero > 0) {
        $centena = floor($numero / 100);
        if ($centena > 0) {
            if ($centena == 1 && $numero == 100) {
                $letras .= 'CIEN ';
            } else {
                $letras .= $centenas[$centena] . ' ';
            }
        }
        $numero %= 100;
        
        if ($numero > 0) {
            if ($numero < 10) {
                $letras .= $unidades[$numero] . ' ';
            } else if ($numero < 20) {
                $letras .= $especial_10_a_19[$numero - 10] . ' ';
            } else if ($numero < 30) {
                $letras .= $especial_20_a_29[$numero - 20] . ' ';
            } else {
                $decena = floor($numero / 10);
                $unidad = $numero % 10;
                
                $letras .= $decenas[$decena];
                if ($unidad > 0) {
                    $letras .= ' Y ' . $unidades[$unidad] . ' ';
                } else {
                    $letras .= ' ';
                }
            }
        }
    }
    
    return trim($letras) . ' PESOS MONEDA CORRIENTE';
}

// Crear el informe en Excel
function createExcelFromTemplate($actividades, $tareas_por_actividad, $evidencias, $contrato, $fecha_inicio, $fecha_fin, $valor_cobro, $nro_pago, $total_pagos) {
    // Usar la plantilla en lugar de crear un nuevo archivo
    $templateFile = __DIR__ . '/informe_cumplido.xlsx';
    $spreadsheet = IOFactory::load($templateFile);
    $sheet = $spreadsheet->getActiveSheet();
    
    // Calcular algunos datos adicionales
    $mes_anterior = obtenerMesAnteriorSegSocial($fecha_inicio);
    $porcentajes_tiempo = calcularPorcentajeEjecucionTiempo(
        $contrato['fecha_inicio'], 
        $contrato['fecha_fin'], 
        $fecha_fin
    );

    $valores_ejecucion = calcularValoresEjecucion(
        $contrato['fecha_inicio'], 
        $contrato['fecha_fin'],
        $fecha_inicio,
        $fecha_fin,
        $contrato['valor']
    );

    $valor_contrato_limpio = limpiarValorMonetario($contrato['valor']);
    $valor_cobro_limpio = limpiarValorMonetario($valor_cobro);
    $valor_cobro_letras = numeroALetras($valor_cobro_limpio);
    
    $fecha_inicio_texto = formatearFechaSimple($fecha_inicio);
    $fecha_fin_texto = formatearFechaSimple($fecha_fin);
    
    // Reemplazos básicos en toda la plantilla
    $replacements = [
        '${cc_contratista}' => $contrato['cc_contratista'],
        '${nombres}' => $_SESSION['nombres'],
        '${email}' => $_SESSION['email'],
        '${celular}' => $_SESSION['celular'],
        '${email_contratista_pro}' => $contrato['email_contratista'],
        '${nombre_supervisor}' => $contrato['nombre_supervisor'],
        '${email_supervisor}' => $contrato['email_supervisor'],
        '${numero_contrato}' => $contrato['numero_contrato'],
        '${fecha_contrato}' => formatearFechaTexto($contrato['fecha_contrato']),
        '${objeto}' => $contrato['objeto'],
        '${fecha_inicio}' => formatearFechaSimple($contrato['fecha_inicio']),
        '${fecha_fin}' => formatearFechaSimple($contrato['fecha_fin']),
        '${valor}' => '$' . number_format(limpiarValorMonetario($contrato['valor']), 0, '', "'"),
        '${forma_pago}' => $contrato['forma_pago'],
        '${rp}' => $contrato['rp'],
        '${cuenta_bancaria}' => $contrato['cuenta_bancaria'],
        '${banco_nombre}' => $contrato['banco_nombre'] ?? 'No especificado',
        '${tipo_cuenta}' => $contrato['tipo_cuenta'],
        '${proceso}' => $contrato['proceso_nombre'] ?? 'No especificado',
        '${unidad_academica}' => $contrato['unidad_academica'] ?? 'No especificada',
        '${sede}' => $contrato['sede'] ?? 'No especificada',
        '${disponibilidad_presupuestal}' => $contrato['disponibilidad_presupuestal'] ?? '',
        '${nro_pago}' => $nro_pago,
        '${total_pagos}' => $total_pagos,
        '${valor_cobro}' => '$' . number_format($valor_cobro_limpio, 0, '', "'"),
        '${valor_cobro_letras}' => $valor_cobro_letras,
        '${fecha_actual}' => formatearFechaSimple(date('Y-m-d')),
        

        '${fecha_inicio_informe}' => $fecha_inicio_texto,
        '${fecha_fin_informe}' => $fecha_fin_texto,
        '${mes_seguridad_social}' => $mes_anterior['mes'],
        '${anio_seguridad_social}' => $mes_anterior['anio'],

        // Asegúrate de tener múltiples versiones de formateo para las fechas
        '${fecha_inicio_informe}' => formatearFechaTextoCompleto($_SESSION['fecha_inicio_informe']),
        '${fecha_fin_informe}' => formatearFechaTextoCompleto($_SESSION['fecha_fin_informe']),
        
      



        '${ejecutado_en_tiempo}' => $porcentajes_tiempo['ejecutado'] . '%',
        '${pendiente_en_tiempo}' => $porcentajes_tiempo['pendiente'] . '%',
        '${valor_ejecutado}' => '$' . number_format($valores_ejecucion['valor_ejecutado'], 0, '', "'"),
        '${valor_pendiente}' => '$' . number_format($valores_ejecucion['valor_pendiente'], 0, '', "'"),
        // Otrosí
        '${tiene_otrosi}' => isset($_SESSION['informe_tiene_otrosi']) && $_SESSION['informe_tiene_otrosi'] ? 'Sí' : 'No',
        '${fecha_inicio_otrosi}' => isset($_SESSION['informe_fecha_inicio_otrosi']) ? 
            formatearFechaTexto($_SESSION['informe_fecha_inicio_otrosi']) : '',
        '${fecha_fin_otrosi}' => isset($_SESSION['informe_fecha_fin_otrosi']) ? 
            formatearFechaTexto($_SESSION['informe_fecha_fin_otrosi']) : '',

        // Cesión
        '${tiene_cesion}' => isset($_SESSION['informe_tiene_cesion']) && $_SESSION['informe_tiene_cesion'] ? 'Sí' : 'No',
        '${fecha_cesion}' => isset($_SESSION['informe_fecha_cesion']) ? 
            formatearFechaTexto($_SESSION['informe_fecha_cesion']) : '',

        // Identificación
        '${tipo_identificacion_sigla}' => $_SESSION['informe_tipo_identificacion_sigla'] ?? '',
        '${lugar_expedicion}' => $_SESSION['informe_lugar_expedicion'] ?? '',

        // Otros datos
        '${valor_cobro_letras}' => $_SESSION['informe_valor_cobro_letras'] ?? '',
    ];
    

    // Buscar todas las variables en la hoja y reemplazar
    // Buscar todas las variables en la hoja y reemplazar
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();
                if (!is_null($value) && is_string($value)) {
                    $newValue = $value; // Inicializar el nuevo valor con el valor original
                    
                    // Aplicar todos los reemplazos sobre la misma cadena
                    foreach ($replacements as $search => $replace) {
                        // Solo realizar reemplazo si la variable existe en la celda
                        if (strpos($newValue, $search) !== false) {
                            $newValue = str_replace($search, $replace, $newValue);
                        }
                    }
                    
                    // Solo actualizar la celda si el valor cambió
                    if ($newValue !== $value) {
                        $cell->setValue($newValue);
                    }
                }
            }
        }
    
    // Procesar actividades en la tabla
    $actividadesStartRow = null;
    
    // Buscar la fila que tiene ${actividad} para iniciar la tabla
    foreach ($sheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $value = $cell->getValue();
            if (!is_null($value) && strpos($value, '${actividad}') !== false) {
                $actividadesStartRow = $row->getRowIndex();
                break 2;
            }
        }
    }
    
    if ($actividadesStartRow) {
        // Estilos para actividades
        $styleArray = [
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        
        // Estilo para centrar
        $centerStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Si no hay actividades, mostrar mensaje
        if (empty($actividades)) {
            $sheet->setCellValue('B' . $actividadesStartRow, '1');
            $sheet->setCellValue('C' . $actividadesStartRow, 'No se encontraron actividades');
            $sheet->mergeCells('C' . $actividadesStartRow . ':E' . $actividadesStartRow);
            $sheet->getStyle('B' . $actividadesStartRow)->applyFromArray($centerStyle);
            $sheet->getStyle('C' . $actividadesStartRow . ':E' . $actividadesStartRow)->applyFromArray($centerStyle);
            $sheet->setCellValue('F' . $actividadesStartRow, '');
            $sheet->setCellValue('G' . $actividadesStartRow, 'N/A');
            $sheet->mergeCells('G' . $actividadesStartRow . ':J' . $actividadesStartRow);
            $sheet->setCellValue('K' . $actividadesStartRow, '');
            $sheet->setCellValue('L' . $actividadesStartRow, '');
            $sheet->mergeCells('K' . $actividadesStartRow . ':L' . $actividadesStartRow);
            $sheet->setCellValue('M' . $actividadesStartRow, '');
            $sheet->mergeCells('M' . $actividadesStartRow . ':N' . $actividadesStartRow);
            $sheet->getStyle('B' . $actividadesStartRow . ':N' . $actividadesStartRow)->applyFromArray($styleArray);
        } else {
            // Calcular cuántas filas necesitamos
            $totalRows = 0;
            foreach ($actividades as $actividad) {
                $actividadId = $actividad['id'];
                $numTareas = max(1, count($tareas_por_actividad[$actividadId] ?? []));
                $totalRows += $numTareas;
            }
            
            // Insertar filas adicionales si es necesario (menos una, que ya existe)
            if ($totalRows > 1) {
                $sheet->insertNewRowBefore($actividadesStartRow + 1, $totalRows - 1);
            }
            
            // Procesar cada actividad
            $currentRow = $actividadesStartRow;
            $activityNum = 1;
            
            foreach ($actividades as $actividad) {
                $actividadId = $actividad['id'];
                $activityDesc = $actividad['descripcion_actividad'];
                $tareas_actividad = $tareas_por_actividad[$actividadId] ?? [];
                
                // Si no hay tareas, crear una fila con un mensaje
                if (empty($tareas_actividad)) {
                    $sheet->setCellValue('B' . $currentRow, $activityNum);
                    $sheet->setCellValue('C' . $currentRow, $activityDesc);
                    $sheet->mergeCells('C' . $currentRow . ':E' . $currentRow);
                    $sheet->setCellValue('F' . $currentRow, '');
                    $sheet->setCellValue('G' . $currentRow, '• Esta actividad no se realizó en el periodo de este informe, sin embargo, se realizará en el marco del contrato.');
                    $sheet->mergeCells('G' . $currentRow . ':J' . $currentRow);
                    $sheet->setCellValue('K' . $currentRow, '');
                    $sheet->setCellValue('L' . $currentRow, '');
                    $sheet->mergeCells('K' . $currentRow . ':L' . $currentRow);
                    $sheet->setCellValue('M' . $currentRow, '');
                    $sheet->mergeCells('M' . $currentRow . ':N' . $currentRow);
                    
                    // Aplicar estilos
                    $sheet->getStyle('B' . $currentRow)->applyFromArray($centerStyle);
                    $sheet->getStyle('B' . $currentRow . ':N' . $currentRow)->applyFromArray($styleArray);
                    
                    // Aumentar altura según el texto
                    $sheet->getRowDimension($currentRow)->setRowHeight(60);
                    
                    $currentRow++;
                    $activityNum++;
                } else {
                    // Hay tareas para esta actividad
                    $activityStartRow = $currentRow;
                    
                    foreach ($tareas_actividad as $tarea_index => $tarea) {
                        $tarea_id = $tarea['id'];
                        
                        if ($tarea_index == 0) {
                            // Primera tarea - mostrar datos de actividad
                            $sheet->setCellValue('B' . $currentRow, $activityNum);
                            $sheet->setCellValue('C' . $currentRow, $activityDesc);
                            $sheet->mergeCells('C' . $currentRow . ':E' . $currentRow);
                            $sheet->setCellValue('F' . $currentRow, '');
                        }
                        
                        // Datos de la tarea
                        $sheet->setCellValue('G' . $currentRow, '• ' . $tarea['descripcion_tarea']);
                        $sheet->mergeCells('G' . $currentRow . ':J' . $currentRow);
                        $sheet->setCellValue('K' . $currentRow, '');
                        $sheet->setCellValue('L' . $currentRow, '');
                        $sheet->mergeCells('K' . $currentRow . ':L' . $currentRow);
                        
                        // Evidencia de la tarea
                        $evidencia = isset($evidencias[$tarea_id]) ? $evidencias[$tarea_id]['url_evidencia'] : '';
                        $sheet->setCellValue('M' . $currentRow, $evidencia ? '• ' . $evidencia : '');
                        $sheet->mergeCells('M' . $currentRow . ':N' . $currentRow);
                        
                        // Aplicar estilos
                        $sheet->getStyle('B' . $currentRow . ':N' . $currentRow)->applyFromArray($styleArray);
                        $sheet->getStyle('B' . $currentRow)->applyFromArray($centerStyle);
                        
                        // Calcular altura según el texto más largo
                        $contenido_maximo = max(
                            strlen($tarea['descripcion_tarea']),
                            strlen($evidencia)
                        );
                        
                        $altura_estimada = ceil($contenido_maximo / 50) * 20; // 20px por línea aprox.
                        $altura_estimada = max(40, $altura_estimada); // Mínimo 40px
                        $sheet->getRowDimension($currentRow)->setRowHeight($altura_estimada);
                        
                        $currentRow++;
                    }
                    
                    // Si hay más de una tarea, combinar celdas de actividad
                    if (count($tareas_actividad) > 1) {
                        $lastRow = $currentRow - 1;
                        $sheet->mergeCells('B' . $activityStartRow . ':B' . $lastRow);
                        $sheet->mergeCells('C' . $activityStartRow . ':E' . $lastRow);
                        $sheet->mergeCells('F' . $activityStartRow . ':F' . $lastRow);
                        
                        // Centrar número de actividad
                        $sheet->getStyle('B' . $activityStartRow . ':B' . $lastRow)->applyFromArray($centerStyle);
                    }
                    
                    $activityNum++;
                }
            }
        }
    }
    
    // Convertir URLs en hipervínculos
    foreach ($sheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $value = $cell->getValue();
            if (is_string($value) && preg_match('/(https?:\/\/[^\s<>"]+)/i', $value)) {
                // Extraer URLs
                preg_match_all('/(https?:\/\/[^\s<>"]+)/i', $value, $matches);
                
                foreach ($matches[0] as $url) {
                    // Crear hipervínculo
                    $hyperlink = new \PhpOffice\PhpSpreadsheet\Cell\Hyperlink($url);
                    $cell->setHyperlink($hyperlink);
                    
                    // Aplicar formato de hipervínculo
                    $sheet->getStyle($cell->getCoordinate())->getFont()->setUnderline(true);
                    $sheet->getStyle($cell->getCoordinate())->getFont()->setColor(
                        new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE)
                    );
                    
                    // Solo procesar la primera URL para evitar problemas
                    break;
                }
            }
        }
    }
    
    $mesActual = getMesActual();
    $outputFile = 'Informe_Cumplido_' . $mesActual . '.xlsx';
    
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($outputFile);
    
    return $outputFile;
}

// Generar el informe
try {
    $excelFile = createExcelFromTemplate(
        $actividades, 
        $tareas_por_actividad, 
        $evidencias, 
        $contrato, 
        $fecha_inicio, 
        $fecha_fin, 
        $valor_cobro, 
        $nro_pago, 
        $total_pagos
    );
    
    // Verificar si se ha creado el archivo
    if (file_exists($excelFile)) {
        // Limpiar cualquier salida previa
        //ob_clean();
        
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Preparar para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $excelFile . '"');
        header('Content-Length: ' . filesize($excelFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        
        // Enviar el archivo al navegador
        readfile($excelFile);
        
        // Eliminar el archivo después de enviarlo
        unlink($excelFile);
        exit;
    } else {
        throw new Exception("Error al crear el archivo del informe");
    }
} catch (Exception $e) {
    echo "Error al generar el informe: " . $e->getMessage();
    
    // Redireccionar a la página anterior después de mostrar el error
    echo "<p>Redireccionando en 5 segundos...</p>";
    echo "<script>setTimeout(function(){ window.location.href = 'contratos.php'; }, 5000);</script>";
}

?>
