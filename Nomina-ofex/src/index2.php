<?php
require 'vendor/autoload.php';
include 'db/conexion.php';
header('Content-Type: text/html; charset=utf-8');

use PhpOffice\PhpSpreadsheet\IOFactory;

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
    
// Consulta para obtener los datos del contrato incluyendo banco, proceso y datos adicionales del contratista
$query = "SELECT ci.*, b.nombre as banco_nombre, p.nombre as proceso_nombre, 
          c.lugar_expedicion, c.tipo_identificacion_id,
          t.nombre as tipo_identificacion_nombre,
          t.sigla as tipo_identificacion_sigla
          FROM contrato_informe ci 
          LEFT JOIN banco b ON ci.banco_id = b.id 
          LEFT JOIN proceso p ON ci.proceso_id = p.id 
          LEFT JOIN contratista c ON ci.cc_contratista = c.cedula
          LEFT JOIN tipo_identificacion t ON c.tipo_identificacion_id = t.id
          WHERE ci.id = '$id_contrato'";
$result = mysqli_query($conexion, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $contrato_data = mysqli_fetch_assoc($result);
    
    // Limpiar el valor del contrato cuando se carga desde la base de datos
    $valor_limpio = preg_replace('/[^0-9.]/', '', $contrato_data['valor']);
    
    // Asignar los valores del contrato a variables de sesión
    $_SESSION['email_contratista_pro'] = $contrato_data['email_contratista'];
    $_SESSION['nombre_supervisor'] = $contrato_data['nombre_supervisor'];
    $_SESSION['email_supervisor'] = $contrato_data['email_supervisor'];
    $_SESSION['numero_contrato'] = $contrato_data['numero_contrato'];
    $_SESSION['fecha_contrato'] = $contrato_data['fecha_contrato'];
    $_SESSION['tipo_identificacion_sigla'] = $contrato_data['tipo_identificacion_sigla'];
    $_SESSION['objeto'] = $contrato_data['objeto'];
    $_SESSION['proceso'] = $contrato_data['proceso_nombre'];
    $_SESSION['unidad_academica'] = $contrato_data['unidad_academica'];
    $_SESSION['sede'] = $contrato_data['sede'];
    $_SESSION['disponibilidad_presupuestal'] = $contrato_data['disponibilidad_presupuestal'];
    $_SESSION['lugar_expedicion'] = $contrato_data['lugar_expedicion'];
    $_SESSION['tipo_identificacion_id'] = $contrato_data['tipo_identificacion_id'];
    $_SESSION['tipo_identificacion_nombre'] = $contrato_data['tipo_identificacion_nombre'];
    $_SESSION['tipo_identificacion_sigla'] = getTipoIdSigla($contrato_data['tipo_identificacion_nombre']);

    
    // Datos bancarios
    $_SESSION['cuenta_bancaria'] = $contrato_data['cuenta_bancaria'];
    $_SESSION['banco_nombre'] = $contrato_data['banco_nombre'];
    $_SESSION['tipo_cuenta'] = $contrato_data['tipo_cuenta'];
    
    // Datos financieros con valor limpio
    $_SESSION['valor'] = $contrato_data['valor']; // Mantener formato original para visualización
    $_SESSION['valor_limpio'] = $valor_limpio; // Añadir el valor limpio para cálculos
    $_SESSION['forma_pago'] = $contrato_data['forma_pago'];
    $_SESSION['rp'] = $contrato_data['rp'];
    
    // Fechas del contrato
    $_SESSION['fecha_inicio'] = $contrato_data['fecha_inicio'];
    $_SESSION['fecha_fin'] = $contrato_data['fecha_fin'];
    
    // Para propósitos de depuración
    error_log("Valor original: " . $contrato_data['valor'] . ", Valor limpio: " . $valor_limpio);
} else {
    echo "No se encontró el contrato especificado.";
    exit;
}

// Consultar si el contrato tiene otro sí
$otrosi_query = "SELECT * FROM contrato_otrosi WHERE contrato_id = '$id_contrato' ORDER BY id DESC LIMIT 1";
$otrosi_result = mysqli_query($conexion, $otrosi_query);
$tiene_otrosi = mysqli_num_rows($otrosi_result) > 0;
if ($tiene_otrosi) {
    $otrosi_data = mysqli_fetch_assoc($otrosi_result);
    $_SESSION['fecha_inicio_otrosi'] = $otrosi_data['fecha_inicio_otrosi'];
    $_SESSION['fecha_fin_otrosi'] = $otrosi_data['fecha_fin_otrosi'];
}

// Consultar si el contrato tiene cesión
$cesion_query = "SELECT * FROM contrato_cesion WHERE contrato_id = '$id_contrato' ORDER BY id DESC LIMIT 1";
$cesion_result = mysqli_query($conexion, $cesion_query);
$tiene_cesion = mysqli_num_rows($cesion_result) > 0;
if ($tiene_cesion) {
    $cesion_data = mysqli_fetch_assoc($cesion_result);
    $_SESSION['fecha_cesion'] = $cesion_data['fecha_cesion'];
    $_SESSION['cc_cesionario'] = $cesion_data['cc_cesionario'];
    $_SESSION['nombre_cesionario'] = $cesion_data['nombre_cesionario'];
}

// Datos del contratista
$cc_contratista = $_SESSION['cedula'];
$nombres_contratista = $_SESSION['nombres'];
$email_contratista = $_SESSION['email'];
$celular_contratista = $_SESSION['celular'];
$email_contratista_pro = $_SESSION['email_contratista_pro'];
$nombre_supervisor = $_SESSION['nombre_supervisor'];
$email_supervisor = $_SESSION['email_supervisor'];
$numero_contrato = $_SESSION['numero_contrato'];
$fecha_contrato = $_SESSION['fecha_contrato'];
$objeto = $_SESSION['objeto'];
$proceso = $_SESSION['proceso'];
$unidad_academica = $_SESSION['unidad_academica'];
$sede = $_SESSION['sede'];
$lugar_expedicion = $_SESSION['lugar_expedicion'] ?? '';
$tipo_identificacion_id = $_SESSION['tipo_identificacion_id'] ?? null;
$tipo_identificacion_nombre = $_SESSION['tipo_identificacion_nombre'] ?? '';

function getMesActual() {
    $meses = array(
        1 => 'Enero', 'Febrero', 'Marzo', 'Abril',
        'Mayo', 'Junio', 'Julio', 'Agosto',
        'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    );
    return $meses[date('n')];
}

// Nueva función para formatear fechas en el formato "DD/MM/AAAA"
function formatearFechaSimple($fecha) {
    if (empty($fecha)) return '';
    
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}
// Función para convertir el nombre del tipo de identificación a siglas
function getTipoIdSigla($nombre) {
    $mapping = [
        'Cédula de ciudadanía' => 'CC',
        'Tarjeta de extranjería' => 'TE',
        'Pasaporte' => 'PA',
        'Número de identificación tributaria' => 'NIT',
        'Permiso especial de permanencia' => 'PEP',
        'Documento de identificación extranjero' => 'DIE'
    ];
    
    // Normalizar la entrada (eliminar acentos, convertir a minúsculas)
    $normalizedNombre = strtolower(preg_replace('/\s+/', ' ', trim($nombre)));
    
    // Buscar coincidencia normalizada
    foreach ($mapping as $key => $sigla) {
        if (strtolower($key) === $normalizedNombre) {
            return $sigla;
        }
    }
    
    // Si no se encuentra coincidencia, devolver el nombre original
    return $nombre;
}
// Función para formatear fechas en texto "12 de enero del 2025"
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


// Función mejorada para limpiar y convertir valores monetarios a números
function limpiarValorMonetario($valor) {
    // Eliminar todos los caracteres que no sean dígitos
    $valor_limpio = preg_replace('/[^0-9.]/', '', $valor);
    
    // Convertir a número (float)
    return floatval($valor_limpio);
}

// Obtener el mes anterior para seguridad social y su año
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

// Función auxiliar para convertir un grupo de números (centenas, decenas, unidades)
function convertirGrupo($numero, $unidades, $decenas, $centenas, $especial_10_a_19, $especial_20_a_29) {
    $letras = '';
    
    // Centenas
    $centena = floor($numero / 100);
    if ($centena > 0) {
        if ($centena == 1 && $numero == 100) {
            $letras .= 'CIEN ';
        } else {
            $letras .= $centenas[$centena] . ' ';
        }
    }
    $numero %= 100;
    
    // Decenas y unidades
    if ($numero > 0) {
        if ($numero < 10) {
            // Solo unidades
            $letras .= $unidades[$numero] . ' ';
        } else if ($numero < 20) {
            // Números del 10 al 19
            $letras .= $especial_10_a_19[$numero - 10] . ' ';
        } else if ($numero < 30) {
            // Números del 20 al 29
            $letras .= $especial_20_a_29[$numero - 20] . ' ';
        } else {
            // Resto de números
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
    
    return $letras;
}


// Función mejorada para convertir número a letras en español
function numeroALetras($numero) {
    // Asegurarse de que el número está limpio antes de procesarlo
    $numero = preg_replace('/[^0-9.]/', '', $numero);
    
    // Convertir a entero
    $numero = intval($numero);
    
    // Registrar para depuración
    error_log("Valor a convertir a letras (después de limpieza): " . $numero);
    
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
        $letras .= convertirGrupo($millones, $unidades, $decenas, $centenas, $especial_10_a_19, $especial_20_a_29) . ' MILLONES ';
    }
    $numero %= 1000000;
    
    // Miles
    $miles = floor($numero / 1000);
    if ($miles == 1) {
        $letras .= 'MIL ';
    } else if ($miles > 1) {
        $letras .= convertirGrupo($miles, $unidades, $decenas, $centenas, $especial_10_a_19, $especial_20_a_29) . ' MIL ';
    }
    $numero %= 1000;
    
    // Centenas, decenas y unidades
    if ($numero > 0) {
        $letras .= convertirGrupo($numero, $unidades, $decenas, $centenas, $especial_10_a_19, $especial_20_a_29);
    }
    
    return trim($letras) . ' PESOS MONEDA CORRIENTE';
}




// Calcular porcentaje de ejecución en tiempo
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



// Calcular valores de ejecución financiera con mejor manejo de valores
function calcularValoresEjecucion($fecha_inicio_contrato, $fecha_fin_contrato, $fecha_inicio_informe, $fecha_fin_informe, $valor_contrato) {
    // Limpiar valor del contrato para tener solo el número
    $valor_contrato = limpiarValorMonetario($valor_contrato);
    
    // Verificar que el valor del contrato sea válido
    if ($valor_contrato <= 0) {
        error_log("Error: Valor del contrato inválido: " . $valor_contrato);
        // Establecer un valor por defecto para evitar divisiones por cero
        $valor_contrato = 1;
    }
    
    // Convertir fechas a objetos DateTime con manejo de errores
    try {
        $inicio_contrato = new DateTime($fecha_inicio_contrato);
        $fin_contrato = new DateTime($fecha_fin_contrato);
        $inicio_informe = new DateTime($fecha_inicio_informe);
        $fin_informe = new DateTime($fecha_fin_informe);
    } catch (Exception $e) {
        error_log("Error al convertir fechas: " . $e->getMessage());
        // Establecer fechas por defecto en caso de error
        $inicio_contrato = new DateTime();
        $fin_contrato = new DateTime("+30 days");
        $inicio_informe = new DateTime();
        $fin_informe = new DateTime("+15 days");
    }
    
    // Verificar que la fecha de fin de informe no exceda la fecha de fin de contrato
    if ($fin_informe > $fin_contrato) {
        $fin_informe = clone $fin_contrato;
    }
    
    // Calcular días totales del contrato con reglas financieras
    $dias_totales = calcularDiasFinancieros($inicio_contrato, $fin_contrato);
    
    // Valor diario del contrato
    $valor_diario = $valor_contrato / max(1, $dias_totales); // Evitar división por cero
    
    // Calcular días del periodo del informe con reglas financieras
    $dias_periodo = calcularDiasFinancieros($inicio_informe, $fin_informe);
    
    // Valor a cobrar del periodo actual
    $valor_a_cobrar = round($valor_diario * $dias_periodo);
    
    // Calcular valor ya ejecutado (hasta el día anterior al inicio del informe)
    $dia_anterior_informe = clone $inicio_informe;
    $dia_anterior_informe->modify('-1 day');
    
    // Si la fecha de inicio del informe es posterior a la fecha de inicio del contrato
    if ($inicio_informe > $inicio_contrato) {
        $dias_ejecutados = calcularDiasFinancieros($inicio_contrato, $dia_anterior_informe);
        $valor_ejecutado = round($valor_diario * $dias_ejecutados);
    } else {
        // Si este es el primer periodo, no hay valor ejecutado previo
        $valor_ejecutado = 0;
    }
    
    // Valor pendiente después de este periodo
    //$valor_pendiente = $valor_contrato - $valor_ejecutado - $valor_a_cobrar;
    $valor_pendiente = $valor_contrato - $valor_ejecutado;
    
    // Si por alguna razón el valor pendiente es negativo, lo ajustamos a cero
    if ($valor_pendiente < 0) {
        $valor_pendiente = 0;
    }
    
    // Calcular número de pago y total de pagos
    $meses_totales = calcularMesesFinancieros($inicio_contrato, $fin_contrato);
    $meses_ejecutados = calcularMesesFinancieros($inicio_contrato, $inicio_informe);
    
    $nro_pago = $meses_ejecutados + 1; // +1 porque este es el siguiente periodo
    $total_pagos = max(1, $meses_totales); // Evitar un total de pagos de cero
    
    // Registrar los valores para depuración
    error_log("Valor contrato: $valor_contrato, Días totales: $dias_totales, Valor diario: $valor_diario");
    error_log("Días periodo: $dias_periodo, Valor a cobrar: $valor_a_cobrar");
    error_log("Valor ejecutado: $valor_ejecutado, Valor pendiente: $valor_pendiente");
    
    return [
        'valor_a_cobrar' => $valor_a_cobrar,
        'valor_ejecutado' => $valor_ejecutado,
        'valor_pendiente' => $valor_pendiente,
        'nro_pago' => $nro_pago,
        'total_pagos' => $total_pagos
    ];
}




// Función para calcular meses financieros entre dos fechas
function calcularMesesFinancieros($fecha_inicio, $fecha_fin) {
    if (!($fecha_inicio instanceof DateTime)) {
        $fecha_inicio = new DateTime($fecha_inicio);
    }
    if (!($fecha_fin instanceof DateTime)) {
        $fecha_fin = new DateTime($fecha_fin);
    }
    
    // Total de días financieros
    $dias_totales = calcularDiasFinancieros($fecha_inicio, $fecha_fin);
    
    // Dividir por 30 días (mes financiero) y redondear hacia arriba
    return ceil($dias_totales / 30);
}

// Función auxiliar mejorada para calcular días entre dos fechas con enfoque financiero (30 días por mes)
function calcularDiasFinancieros($fecha_inicio, $fecha_fin) {
    // Si las fechas son iguales, retornar 1 (se cuenta el día)
    if ($fecha_inicio == $fecha_fin) {
        return 1;
    }
    
    // Convertir a objetos DateTime si no lo son ya
    if (!($fecha_inicio instanceof DateTime)) {
        try {
            $fecha_inicio = new DateTime($fecha_inicio);
        } catch (Exception $e) {
            error_log("Error al convertir fecha_inicio: " . $e->getMessage());
            $fecha_inicio = new DateTime(); // Usar fecha actual como fallback
        }
    }
    
    if (!($fecha_fin instanceof DateTime)) {
        try {
            $fecha_fin = new DateTime($fecha_fin);
        } catch (Exception $e) {
            error_log("Error al convertir fecha_fin: " . $e->getMessage());
            $fecha_fin = new DateTime(); // Usar fecha actual como fallback
        }
    }
    
    // Verificar que la fecha de fin no sea anterior a la fecha de inicio
    if ($fecha_fin < $fecha_inicio) {
        error_log("Error: Fecha fin anterior a fecha inicio - Inicio: " . $fecha_inicio->format('Y-m-d') . ", Fin: " . $fecha_fin->format('Y-m-d'));
        // Intercambiar fechas o usar un valor por defecto
        $temp = clone $fecha_inicio;
        $fecha_inicio = clone $fecha_fin;
        $fecha_fin = $temp;
    }
    
    // Obtener componentes de las fechas
    $inicio_dia = (int)$fecha_inicio->format('d');
    $inicio_mes = (int)$fecha_inicio->format('m');
    $inicio_anio = (int)$fecha_inicio->format('Y');
    
    $fin_dia = (int)$fecha_fin->format('d');
    $fin_mes = (int)$fecha_fin->format('m');
    $fin_anio = (int)$fecha_fin->format('Y');
    
    // Verificar si el día de fin es el último día del mes del calendario
    $ultimo_dia_calendario = (int)(new DateTime($fecha_fin->format('Y-m-t')))->format('d');
    $es_ultimo_dia_del_mes = ($fin_dia == $ultimo_dia_calendario);
    
    // Si es el último día del mes, considerarlo como día 30 financieramente
    if ($es_ultimo_dia_del_mes) {
        $fin_dia = 30;
    }
    
    // Si estamos en el mismo mes y año
    if ($inicio_mes == $fin_mes && $inicio_anio == $fin_anio) {
        return $fin_dia - $inicio_dia + 1; // +1 para incluir el primer día
    }
    
    // Calcular meses completos entre las fechas
    $diff_meses = ($fin_anio - $inicio_anio) * 12 + ($fin_mes - $inicio_mes - 1);
    
    // Días del primer mes (desde inicio_dia hasta el 30)
    $dias_primer_mes = 30 - $inicio_dia + 1; // +1 para incluir el día inicial
    
    // Días de los meses intermedios (cada mes tiene 30 días)
    $dias_meses_intermedios = $diff_meses * 30;
    
    // Días del último mes (desde el 1 hasta fin_dia)
    $dias_ultimo_mes = $fin_dia;
    
    $total_dias = $dias_primer_mes + $dias_meses_intermedios + $dias_ultimo_mes;
    
    // Registrar para depuración
    error_log("Cálculo días financieros - Inicio: " . $fecha_inicio->format('Y-m-d') . ", Fin: " . $fecha_fin->format('Y-m-d') . ", Total días: " . $total_dias);
    
    return $total_dias;
}

if ($nombres_contratista == null || $id_contrato == null) {
    echo '<script>window.location.href = "login.php";</script>';
    exit();
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



/**
 * Función para convertir URLs en texto a hipervínculos en Excel
 * @param PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Hoja de Excel
 * @param string $cellCoordinate Coordenada de la celda (ejemplo: 'M15')
 * @param string $text Texto que puede contener URLs
 */
function createHyperlinksInCell($sheet, $cellCoordinate, $text) {
    // Patrón para detectar URLs
    $pattern = '/(https?:\/\/[^\s<>"]+)/i';
    
    // Si no hay URLs en el texto, no hacer nada
    if (!preg_match($pattern, $text)) {
        return;
    }
    
    // Extraer todas las URLs del texto
    preg_match_all($pattern, $text, $matches);
    
    foreach ($matches[0] as $url) {
        try {
            // Crear un objeto hipervínculo
            $hyperlink = new \PhpOffice\PhpSpreadsheet\Cell\Hyperlink($url);
            
            // Aplicar el hipervínculo a la celda
            $sheet->getCell($cellCoordinate)->setHyperlink($hyperlink);
            
            // Aplicar formato de hipervínculo (azul y subrayado)
            $sheet->getStyle($cellCoordinate)->getFont()->setUnderline(true);
            $sheet->getStyle($cellCoordinate)->getFont()->setColor(
                new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE)
            );
        } catch (Exception $e) {
            error_log("Error al crear hipervínculo en $cellCoordinate: " . $e->getMessage());
        }
    }
}

/**
 * Función para escanear todas las celdas en busca de URLs
 * @param PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Hoja de Excel
 */
function processUrlsInAllCells($sheet) {
    foreach ($sheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $value = $cell->getValue();
            
            // Si la celda tiene contenido
            if ($value !== null && is_string($value)) {
                // Si contiene una URL, convertirla en hipervínculo
                if (preg_match('/(https?:\/\/[^\s<>"]+)/i', $value)) {
                    createHyperlinksInCell($sheet, $cell->getCoordinate(), $value);
                }
            }
        }
    }
}




function createExcelFromTemplate($activities, $templateFile, $startDate, $endDate, $sessionVars) {
    $spreadsheet = IOFactory::load($templateFile);
    $sheet = $spreadsheet->getActiveSheet();

    // Primero reemplazamos las variables básicas en todo el documento
    $replacements = [
        '${cc_contratista}' => $sessionVars['cc'],
        '${nombres}' => $sessionVars['nombres'],
        '${email}' => $sessionVars['email'],
        '${celular}' => $sessionVars['celular'],
        '${email_contratista_pro}' => $sessionVars['email_contratista_pro'],
        '${nombre_supervisor}' => $sessionVars['nombre_supervisor'],
        '${email_supervisor}' => $sessionVars['email_supervisor'],
        '${numero_contrato}' => $sessionVars['numero_contrato'],
        '${lugar_expedicion}' => $sessionVars['lugar_expedicion'],
        '${tipo_identificacion_nombre}' => $sessionVars['tipo_identificacion_nombre'],
        '${tipo_identificacion_sigla}' => $sessionVars['tipo_identificacion_sigla'],
        '${fecha_contrato}' => $sessionVars['fecha_contrato'],
        '${objeto}' => $sessionVars['objeto'],
        '${fecha_inicio}' => $sessionVars['fecha_inicio'],
        '${fecha_fin}' => $sessionVars['fecha_fin'],
        '${valor}' => $sessionVars['valor'],
        '${forma_pago}' => $sessionVars['forma_pago'],
        '${rp}' => $sessionVars['rp'],
        '${cuenta_bancaria}' => $sessionVars['cuenta_bancaria'],
        '${banco_nombre}' => $sessionVars['banco_nombre'],
        '${tipo_cuenta}' => $sessionVars['tipo_cuenta'],
        '${proceso}' => $sessionVars['proceso'],
        '${unidad_academica}' => $sessionVars['unidad_academica'],
        '${sede}' => $sessionVars['sede'],
        '${disponibilidad_presupuestal}' => $sessionVars['disponibilidad_presupuestal'],
        '${nro_pago}' => $sessionVars['nro_pago'],
        '${total_pagos}' => $sessionVars['total_pagos'],
        '${valor_cobro}' => $sessionVars['valor_cobro'],
        '${valor_cobro_letras}' => $sessionVars['valor_cobro_letras'],
        '${fecha_actual}' => $sessionVars['fecha_actual'],
        '${fecha_inicio_informe}' => $sessionVars['fecha_inicio_informe'],
        '${fecha_fin_informe}' => $sessionVars['fecha_fin_informe'],
        '${mes_seguridad_social}' => $sessionVars['mes_seguridad_social'],
        '${anio_seguridad_social}' => $sessionVars['anio_seguridad_social'],
        '${ejecutado_en_tiempo}' => $sessionVars['ejecutado_en_tiempo'] . '%',
        '${pendiente_en_tiempo}' => $sessionVars['pendiente_en_tiempo'] . '%',
        '${valor_ejecutado}' => '$' . number_format($sessionVars['valor_ejecutado'], 0, '', "'"),
        '${valor_pendiente}' => '$' . number_format($sessionVars['valor_pendiente'], 0, '', "'")
    ];

    // Datos de Otrosí (si existe)
    if (isset($sessionVars['fecha_inicio_otrosi'])) {
        $replacements['${tiene_otrosi}'] = 'Sí';
        $replacements['${fecha_inicio_otrosi}'] = $sessionVars['fecha_inicio_otrosi'];
        $replacements['${fecha_fin_otrosi}'] = $sessionVars['fecha_fin_otrosi'];
    } else {
        $replacements['${tiene_otrosi}'] = 'No';
        $replacements['${fecha_inicio_otrosi}'] = '';
        $replacements['${fecha_fin_otrosi}'] = '';
    }

    // Datos de Cesión (si existe)
    if (isset($sessionVars['fecha_cesion'])) {
        $replacements['${tiene_cesion}'] = 'Sí';
        $replacements['${fecha_cesion}'] = $sessionVars['fecha_cesion'];
        $replacements['${cc_cesionario}'] = $sessionVars['cc_cesionario'];
        $replacements['${nombre_cesionario}'] = $sessionVars['nombre_cesionario'];
    } else {
        $replacements['${tiene_cesion}'] = 'No';
        $replacements['${fecha_cesion}'] = '';
        $replacements['${cc_cesionario}'] = '';
        $replacements['${nombre_cesionario}'] = '';
    }
    
    // Reemplazar las variables generales en toda la hoja
    foreach ($replacements as $search => $replace) {
        replaceInSheet($sheet, $search, $replace);
    }

    // Buscar la fila donde comienzan las actividades (buscando ${actividad})
    $actividadesStartRow = null;
    foreach ($sheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $value = $cell->getValue();
            if ($value !== null && strpos($value, '${actividad}') !== false) {
                $actividadesStartRow = $row->getRowIndex();
                break 2;
            }
        }
    }

    // Si encontramos la fila de inicio de actividades
    if ($actividadesStartRow !== null) {
        // Establecer estilos para las celdas
        $styleArray = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_JUSTIFY,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        
        // Estilo centrado para números y títulos
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];

        // Eliminar la fila de template (la que tiene ${actividad})
        $templateRowIndex = $actividadesStartRow;
        
        // Si no hay actividades, dejar un mensaje por defecto
        if (empty($activities)) {
            $sheet->setCellValue('B' . $templateRowIndex, '1');
            $sheet->setCellValue('C' . $templateRowIndex, 'No se encontraron actividades');
            
            // Asegurarse de que las celdas C, D, E estén combinadas horizontalmente
            $sheet->mergeCells('C' . $templateRowIndex . ':E' . $templateRowIndex);
            
            // Aplicar alineación central para el número y nombre de actividad
            $sheet->getStyle('B' . $templateRowIndex)->applyFromArray($centerStyle);
            $sheet->getStyle('C' . $templateRowIndex . ':E' . $templateRowIndex)->applyFromArray($centerStyle);
            
            $sheet->setCellValue('F' . $templateRowIndex, '');  // Valor (vacío)
            
            $sheet->setCellValue('G' . $templateRowIndex, 'N/A');  // Tareas
            $sheet->mergeCells('G' . $templateRowIndex . ':J' . $templateRowIndex);
            
            $sheet->setCellValue('K' . $templateRowIndex, '');  // Producto asociado
            $sheet->setCellValue('L' . $templateRowIndex, '');  // Valor 2
            
            // Combinar columnas K y L horizontalmente
            $sheet->mergeCells('K' . $templateRowIndex . ':L' . $templateRowIndex);
            
            $sheet->setCellValue('M' . $templateRowIndex, '');  // Evidencias
            $sheet->mergeCells('M' . $templateRowIndex . ':N' . $templateRowIndex);
            
            // Aplicar estilos
            $sheet->getStyle('B' . $templateRowIndex . ':N' . $templateRowIndex)->applyFromArray($styleArray);
        } else {
            // PASO 1: Calcular cuántas filas necesitamos en total
            $totalRows = 0;
            foreach ($activities as $activity) {
                $numTasks = max(1, count($activity['tasks']));
                $totalRows += $numTasks;
            }
            
            // PASO 2: Insertar filas adicionales si es necesario (menos una, que ya existe)
            if ($totalRows > 1) {
                $sheet->insertNewRowBefore($templateRowIndex + 1, $totalRows - 1);
            }
            
            // PASO 3: Procesar cada actividad
            $currentRow = $templateRowIndex;
            $activityNum = 1;
            
            foreach ($activities as $activity) {
                // Número de tareas para esta actividad (mínimo 1)
                $taskCount = max(1, count($activity['tasks']));
                
                // Fila inicial para esta actividad
                $activityStartRow = $currentRow;
                
                // PASO 3.1: Establecer valores comunes de la actividad
                $sheet->setCellValue('B' . $activityStartRow, $activityNum);
                
                // Combinar nombre de la actividad con su descripción
                $activityName = $activity['name'];
                $activityDesc = isset($activity['description']) && !empty($activity['description']) ? 
                               ': ' . $activity['description'] : '';
                $activityCombined = $activityName . $activityDesc;
                
                $sheet->setCellValue('C' . $activityStartRow, $activityCombined);
                $sheet->setCellValue('F' . $activityStartRow, ''); // Porcentaje/valor (vacío)
                
                // PASO 3.2: Si hay múltiples tareas, preparar combinación vertical de celdas
                if ($taskCount > 1) {
                    // Combinar verticalmente celdas para actividad: columna B (número)
                    $sheet->mergeCells('B' . $activityStartRow . ':B' . ($activityStartRow + $taskCount - 1));
                    
                    // Combinar verticalmente celdas para nombre actividad: columnas C-E
                    $sheet->mergeCells('C' . $activityStartRow . ':E' . ($activityStartRow + $taskCount - 1));
                    
                    // Combinar verticalmente celdas para porcentaje: columna F
                    $sheet->mergeCells('F' . $activityStartRow . ':F' . ($activityStartRow + $taskCount - 1));
                } else {
                    // Para una sola tarea, combinar horizontalmente C:E
                    $sheet->mergeCells('C' . $activityStartRow . ':E' . $activityStartRow);
                }
                
                // Aplicar estilo centrado para el número y nombre de actividad
                $sheet->getStyle('B' . $activityStartRow)->applyFromArray($centerStyle);
                $sheet->getStyle('C' . $activityStartRow . ':E' . ($activityStartRow + $taskCount - 1))->applyFromArray([
                    'alignment' => [
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'wrapText' => true,
                    ],
                ]);
                
                // Calcular altura para el texto de la actividad
                // Analizar el texto para contar aproximadamente cuántas líneas ocupará
                $activityTextLength = strlen($activityCombined);
                $charsPerLine = 57; // Cantidad aproximada de caracteres que caben en una línea
                
                // Dividir el texto en palabras
                $words = explode(' ', $activityCombined);
                $lines = 1;
                $currentLineLength = 0;
                
                // Simular el wrapping de texto para contar líneas más precisamente
                foreach ($words as $word) {
                    $wordLength = strlen($word) + 1; // +1 por el espacio
                    if ($currentLineLength + $wordLength > $charsPerLine) {
                        // Esta palabra debe ir en la siguiente línea
                        $lines++;
                        $currentLineLength = $wordLength;
                    } else {
                        // Esta palabra cabe en la línea actual
                        $currentLineLength += $wordLength;
                    }
                }
                
                // Asegurar que haya al menos una línea
                $lines = max(1, $lines);
                
                // Calculamos la altura: 22px por cada línea como solicitado
                $activityRowHeight = $lines * 22;
                
                // Establecer altura personalizada para la fila de la actividad
                $sheet->getRowDimension($activityStartRow)->setRowHeight($activityRowHeight);
                
                // PASO 3.3: Procesar tareas de la actividad
                if (!empty($activity['tasks'])) {
                    // Hay tareas reales para procesar
                    for ($i = 0; $i < $taskCount; $i++) {
                        $task = $activity['tasks'][$i];
                        $taskRow = $activityStartRow + $i;
                        
                        // Tarea en celdas G-J
                        $taskText = '• ' . $task['name'];
                        $sheet->setCellValue('G' . $taskRow, $taskText);
                        $sheet->mergeCells('G' . $taskRow . ':J' . $taskRow);
                        
                        // Producto asociado y valor (vacíos) - Columnas K y L
                        $sheet->setCellValue('K' . $taskRow, '');
                        $sheet->setCellValue('L' . $taskRow, '');
                        $sheet->mergeCells('K' . $taskRow . ':L' . $taskRow);
                        
                        // Descripción/evidencia en celdas M-N
                        $evidenceText = !empty($task['description']) ? '• ' . $task['description'] : '';
                        $sheet->setCellValue('M' . $taskRow, $evidenceText);
                        $sheet->mergeCells('M' . $taskRow . ':N' . $taskRow);
                        
                        // Calcular altura para el texto de las tareas y evidencias
                        $charsPerLine = 57;
                        
                        // Función para contar líneas de texto
                        $countLines = function($text) use ($charsPerLine) {
                            if (empty($text)) return 1;
                            
                            $words = explode(' ', $text);
                            $lines = 1;
                            $currentLineLength = 0;
                            
                            foreach ($words as $word) {
                                $wordLength = strlen($word) + 1; // +1 por el espacio
                                if ($currentLineLength + $wordLength > $charsPerLine) {
                                    $lines++;
                                    $currentLineLength = $wordLength;
                                } else {
                                    $currentLineLength += $wordLength;
                                }
                            }
                            
                            return max(1, $lines);
                        };
                        
                        // Contar líneas para tarea y evidencia
                        $taskLines = $countLines($taskText);
                        $evidenceLines = $countLines($evidenceText);
                        
                        // Calcular altura: 22px por cada línea
                        $taskHeight = $taskLines * 22;
                        $evidenceHeight = $evidenceLines * 22;
                        
                        // Tomar la altura mayor entre ambas columnas
                        $rowHeight = max($taskHeight, $evidenceHeight, 22);
                        
                        // Establecer altura de la fila
                        $sheet->getRowDimension($taskRow)->setRowHeight($rowHeight);
                        
                        // Si la descripción contiene URLs, crear hipervínculos
                        if (!empty($task['description']) && preg_match('/(https?:\/\/[^\s<>"]+)/i', $task['description'])) {
                            createHyperlinksInCell($sheet, 'M' . $taskRow, $evidenceText);
                        }
                    }
                } else {
                    // Actividad sin tareas, mostrar mensaje por defecto
                    $sheet->setCellValue('G' . $activityStartRow, '• Esta actividad no se realizó en el periodo de este informe, sin embargo, se realizará en el marco del contrato.');
                    $sheet->mergeCells('G' . $activityStartRow . ':J' . $activityStartRow);
                    
                    // Producto asociado y valor (vacíos) - Columnas K y L
                    $sheet->setCellValue('K' . $activityStartRow, '');
                    $sheet->setCellValue('L' . $activityStartRow, '');
                    $sheet->mergeCells('K' . $activityStartRow . ':L' . $activityStartRow);
                    
                    // Evidencia vacía
                    $sheet->setCellValue('M' . $activityStartRow, '');
                    $sheet->mergeCells('M' . $activityStartRow . ':N' . $activityStartRow);
                    
                    // Calcular altura para actividades sin tareas basado en número de líneas
                    $defaultMessage = '• Esta actividad no se realizó en el periodo de este informe, sin embargo, se realizará en el marco del contrato.';
                    $charsPerLine = 57;
                    
                    // Contar líneas usando el mismo algoritmo
                    $words = explode(' ', $defaultMessage);
                    $lines = 1;
                    $currentLineLength = 0;
                    
                    foreach ($words as $word) {
                        $wordLength = strlen($word) + 1; // +1 por el espacio
                        if ($currentLineLength + $wordLength > $charsPerLine) {
                            // Esta palabra debe ir en la siguiente línea
                            $lines++;
                            $currentLineLength = $wordLength;
                        } else {
                            // Esta palabra cabe en la línea actual
                            $currentLineLength += $wordLength;
                        }
                    }
                    
                    $defaultRowHeight = $lines * 22; // 22px por línea
                    $sheet->getRowDimension($activityStartRow)->setRowHeight($defaultRowHeight);
                }
                
                // PASO 3.4: Aplicar estilos a todas las filas de esta actividad
                $sheet->getStyle('B' . $activityStartRow . ':N' . ($activityStartRow + $taskCount - 1))->applyFromArray($styleArray);
                
                // PASO 3.5: Incrementar contador y avanzar a la siguiente fila
                $currentRow = $activityStartRow + $taskCount;
                $activityNum++;
            }
        }
    }
    
    // Procesar todas las celdas para encontrar URLs y convertirlas en hipervínculos
    processUrlsInAllCells($sheet);

    $mesActual = getMesActual();
    $outputFile = 'Informe_Cumplido_' . $mesActual . '.xlsx';
    
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($outputFile);
    
    return $outputFile;
}






// Función auxiliar para verificar si un rango ya está combinado
function isMergedCell($sheet, $cellStart, $cellEnd) {
    $mergedCells = $sheet->getMergeCells();
    $rangeToCheck = $cellStart . ':' . $cellEnd;
    
    foreach ($mergedCells as $mergedCell) {
        if ($mergedCell == $rangeToCheck) {
            return true;
        }
    }
    
    return false;
}










// Función auxiliar para copiar los estilos de una fila a otra
function copyRowStyle($sheet, $sourceRow, $targetRow) {
    // Obtener todas las celdas de la fila fuente
    foreach ($sheet->getRowIterator($sourceRow, $sourceRow)->current()->getCellIterator() as $cell) {
        $column = $cell->getColumn();
        
        // Copiar el estilo de la celda fuente a la celda destino
        $sourceStyle = $sheet->getStyle($column . $sourceRow);
        $targetCell = $column . $targetRow;
        
        $sheet->duplicateStyle($sourceStyle, $targetCell);
    }
}



// Función mejorada para formatear fechas en el formato "12 de febrero de 2025"


function formatearFechaContrato($fecha) {
    // Depurar el formato de entrada
    error_log("Formato de fecha recibido: " . $fecha);
    
    if (empty($fecha)) return 'Fecha vacía';
    
    // Intentar diferentes formatos de fecha
    $timestamp = strtotime($fecha);
    
    if ($timestamp === false) {
        // Si falla, intentar convertir desde formato DD/MM/YYYY
        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            $timestamp = strtotime($partes[2] . '-' . $partes[1] . '-' . $partes[0]);
        }
        
        if ($timestamp === false) {
            return 'Formato de fecha no reconocido: ' . $fecha;
        }
    }
    
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

            // Saltar la fila de encabezados
            if ($row->getRowIndex() == 1) continue;

            $bucketName = isset($rowData[$columnIndices['Bucket Name']]) ? $rowData[$columnIndices['Bucket Name']] : '';
            $taskName = isset($rowData[$columnIndices['Task Name']]) ? $rowData[$columnIndices['Task Name']] : '';
            $label = isset($rowData[$columnIndices['Labels']]) ? $rowData[$columnIndices['Labels']] : '';
            $description = isset($rowData[$columnIndices['Description']]) ? $rowData[$columnIndices['Description']] : '';

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

        // Ordenar actividades y asignar tareas
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
        $templateFile = __DIR__ . '/informe_cumplido.xlsx';
        
        // Asegurarse de que el valor del contrato es un número limpio
        $valor_contrato_limpio = preg_replace('/[^0-9.]/', '', $_SESSION['valor']);
        if (empty($valor_contrato_limpio)) {
            $valor_contrato_limpio = "0";
        }
        $_SESSION['valor_limpio'] = $valor_contrato_limpio;
        
        // Calcular porcentajes de ejecución de tiempo
        $porcentajes_tiempo = calcularPorcentajeEjecucionTiempo(
            $_SESSION['fecha_inicio'], 
            $_SESSION['fecha_fin'], 
            $endDate
        );
        
        // Calcular valores de ejecución financiera con el valor limpio
        $valores_ejecucion = calcularValoresEjecucion(
            $_SESSION['fecha_inicio'], 
            $_SESSION['fecha_fin'],
            $startDate,
            $endDate,
            $valor_contrato_limpio // Usar el valor limpio
        );
        
        // Obtener mes anterior para seguridad social
        $mes_anterior = obtenerMesAnteriorSegSocial($startDate);
        
        // Valor del cobro en letras
        $valor_cobro_letras = numeroALetras($valores_ejecucion['valor_a_cobrar']);
 
        // Preparar todas las variables para el documento Excel
        $sessionVars = [
            'cc' => $_SESSION['cedula'] ?? '',
            'nombres' => $_SESSION['nombres'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'celular' => $_SESSION['celular'] ?? '',
            'email_contratista_pro' => $_SESSION['email_contratista_pro'] ?? '',
            'nombre_supervisor' => $_SESSION['nombre_supervisor'] ?? '',
            'email_supervisor' => $_SESSION['email_supervisor'] ?? '',
            'lugar_expedicion' => $_SESSION['lugar_expedicion'] ?? '',
            'tipo_identificacion_nombre' => $_SESSION['tipo_identificacion_nombre'] ?? '',
            'tipo_identificacion_sigla' => $_SESSION['tipo_identificacion_sigla'] ?? '',
            'tipo_identificacion_nombre' => $_SESSION['tipo_identificacion_nombre'] ?? '',
            'numero_contrato' => $_SESSION['numero_contrato'] ?? '',
            'fecha_contrato' => (function($fecha) {
                if (empty($fecha)) return '';
                $timestamp = strtotime($fecha);
                if ($timestamp === false) return $fecha;
                
                $dia = date('j', $timestamp);
                $mes = date('n', $timestamp);
                $anio = date('Y', $timestamp);
                
                $meses = array(
                    1 => 'enero', 'febrero', 'marzo', 'abril',
                    'mayo', 'junio', 'julio', 'agosto',
                    'septiembre', 'octubre', 'noviembre', 'diciembre'
                );
                
                return $dia . ' de ' . $meses[$mes] . ' de ' . $anio;
            })($_SESSION['fecha_contrato'] ?? ''),
            'objeto' => $_SESSION['objeto'] ?? '',
            'fecha_inicio' => formatearFechaSimple($_SESSION['fecha_inicio'] ?? ''),
            'fecha_fin' => formatearFechaSimple($_SESSION['fecha_fin'] ?? ''),
            'valor' => '$' . number_format((float)$valor_contrato_limpio, 0, '', "'"), // Usar el valor limpio
            'forma_pago' => $_SESSION['forma_pago'] ?? '',
            'rp' => $_SESSION['rp'] ?? '',
            'cuenta_bancaria' => $_SESSION['cuenta_bancaria'] ?? '',
            'banco_nombre' => $_SESSION['banco_nombre'] ?? '',
            'tipo_cuenta' => $_SESSION['tipo_cuenta'] ?? '',
            'proceso' => $_SESSION['proceso'] ?? '',
            'unidad_academica' => $_SESSION['unidad_academica'] ?? '',
            'sede' => $_SESSION['sede'] ?? '',
            'disponibilidad_presupuestal' => $_SESSION['disponibilidad_presupuestal'] ?? '',
            'nro_pago' => $valores_ejecucion['nro_pago'],
            'total_pagos' => $valores_ejecucion['total_pagos'],
            'valor_cobro' => '$' . number_format($valores_ejecucion['valor_a_cobrar'], 0, '', "'"),
            'valor_cobro_letras' => $valor_cobro_letras,
            'fecha_actual' => formatearFechaSimple(date('Y-m-d')),
            'fecha_inicio_informe' => formatearFechaSimple($startDate),
            'fecha_fin_informe' => formatearFechaSimple($endDate),
            'mes_seguridad_social' => $mes_anterior['mes'],
            'anio_seguridad_social' => $mes_anterior['anio'],
            'ejecutado_en_tiempo' => $porcentajes_tiempo['ejecutado'],
            'pendiente_en_tiempo' => $porcentajes_tiempo['pendiente'],
            'valor_ejecutado' => $valores_ejecucion['valor_ejecutado'],
            'valor_pendiente' => $valores_ejecucion['valor_pendiente']
        ];

        // Si hay otro sí, incluir esos datos
        if (isset($_SESSION['fecha_inicio_otrosi'])) {
            $sessionVars['fecha_inicio_otrosi'] = formatearFechaSimple($_SESSION['fecha_inicio_otrosi']);
            $sessionVars['fecha_fin_otrosi'] = formatearFechaSimple($_SESSION['fecha_fin_otrosi']);
        }

        // Si hay cesión, incluir esos datos
        if (isset($_SESSION['fecha_cesion'])) {
            $sessionVars['fecha_cesion'] = formatearFechaSimple($_SESSION['fecha_cesion']);
            $sessionVars['cc_cesionario'] = $_SESSION['cc_cesionario'];
            $sessionVars['nombre_cesionario'] = $_SESSION['nombre_cesionario'];
        }

        // Obtener el mes actual
        $mesActual = getMesActual();
 
        // Generar el documento Excel
        $excelFile = createExcelFromTemplate($activities, $templateFile, $startDate, $endDate, $sessionVars);
 
        // Renombrar el archivo con el formato solicitado
        $newExcelFile = 'Informe_Cumplido_' . $mesActual . '.xlsx';
        rename($excelFile, $newExcelFile);
 
        if (!file_exists($newExcelFile)) {
            throw new Exception("Error generando archivo");
        }
 
        // Preparar para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$newExcelFile.'"');
        header('Content-Length: ' . filesize($newExcelFile));
        readfile($newExcelFile);
 
        // Limpieza del archivo temporal
        unlink($newExcelFile);
        exit;
 
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    // Mostrar el formulario si no se ha enviado un archivo
  ?>
   <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Informe de Cumplido | Universidad Distrital</title>
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
        
        html, body {
            height: 100%;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-container {
            flex: 1;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-md);
            position: relative;
            z-index: 10;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .logo-wrapper {
            display: flex;
            align-items: center;
        }
        
        .logo-circular {
            width: 40px;
            height: 40px;
            overflow: hidden;
            margin-right: 0.75rem;
        }
        
        .app-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-right: 2rem;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-info {
            margin-right: 1rem;
            display: flex;
            align-items: center;
        }
        
        .user-name {
            font-weight: 500;
            margin-left: 0.5rem;
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
        
        .logo {
            height: 60px;
        }
        
        .title {
            font-size: 1.2em;
            text-align: center;
            flex-grow: 1;
            margin: 0 20px;
        }
        
        .contratos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
            box-shadow: var(--shadow-md);
            border-radius: 8px;
            overflow: hidden;
            background-color: var(--white);
        }
        
        .contratos-table th {
            background-color: var(--primary-light);
            color: var(--white);
            text-align: left;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }
        
        .contratos-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--medium-gray);
            color: var(--text-dark);
        }
        
        .contratos-table tr:last-child td {
            border-bottom: none;
        }
        
        .contratos-table tr:hover {
            background-color: var(--light-gray);
        }
        
        .card {
            background-color: var(--white);
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        .card-header {
            padding: 1.5rem;
            background-color: var(--primary-color);
            color: var(--white);
            display: flex;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-icon {
            margin-right: 0.75rem;
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
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
            
            .contratos-table {
                display: block;
                overflow-x: auto;
            }
            
            .card-header {
                padding: 1.25rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }
        }
        
        /* Animation */
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
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-wrapper">
                <div class="logo-circular">
                    <img src="images/LOGO_IDEXUD.png" alt="Logo IDEXUD" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h1 class="app-title">Generar Informe de Cumplido</h1>
            </div>
        </div>
    </header>

    <div class="main-container">
        <table class="contratos-table fadeIn">
            <thead>
                <tr>
                    <th>NÚMERO DE CONTRATO</th>
                    <th>FECHA CONTRATO</th>
                    <th>OBJETO</th>
                    <th>PROCESO</th>
                    <th>UNIDAD ACADÉMICA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($numero_contrato); ?></td>
                    <td><?php echo htmlspecialchars(formatearFechaSimple($fecha_contrato)); ?></td>
                    <td><?php echo htmlspecialchars($objeto); ?></td>
                    <td><?php echo htmlspecialchars($proceso); ?></td>
                    <td><?php echo htmlspecialchars($unidad_academica); ?></td>
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
            <div class="card fadeIn delay-1">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-file-contract card-icon"></i>
                        Generar Informe de Cumplido
                    </h2>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars("./index2.php?id=" . $id_contrato); ?>" method="post" enctype="multipart/form-data" class="mt-6">
                        <div class="form-group">
                            <label class="form-label" for="excelFile">
                                Selecciona el archivo Excel de actividades:
                            </label>
                            <div class="form-input-wrapper">
                                <i class="fas fa-file-excel input-icon"></i>
                                <input class="form-input" type="file" name="excelFile" id="excelFile" accept=".xlsx,.xls" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="startDate">
                                Fecha de inicio del periodo a informar:
                            </label>
                            <div class="form-input-wrapper">
                                <i class="fas fa-calendar-alt input-icon"></i>
                                <input class="form-input" type="date" name="startDate" id="startDate" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="endDate">
                                Fecha de fin del periodo a informar:
                            </label>
                            <div class="form-input-wrapper">
                                <i class="fas fa-calendar-check input-icon"></i>
                                <input class="form-input" type="date" name="endDate" id="endDate" required>
                            </div>
                        </div>
                        
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_contrato); ?>">
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download btn-icon"></i>
                            Descargar Informe de Cumplido
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="logout-container">
            <a href="back/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt logout-icon"></i>
                Cerrar Sesión
            </a>
        </div>
    </div>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>
    
    <script>
        // Mejora de interacción con los campos de formulario
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Inicializar ajuste de logos
            adjustLogoSize();
            window.addEventListener('resize', adjustLogoSize);
        });
    </script>
</body>
</html>
<?php
}
?>
