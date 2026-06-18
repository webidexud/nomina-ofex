<?php
require 'vendor/autoload.php';
include 'db/conexion.php';
header('Content-Type: text/html; charset=utf-8');

use PhpOffice\PhpSpreadsheet\IOFactory;

session_start();
$fecha_actual = date('Y-m-d');

// Verificar ID del contrato
if (isset($_GET['id'])) {
    $id_contrato = $_GET['id'];
} else if (isset($_POST['id'])) {
    $id_contrato = $_POST['id'];
} else {
    header("Location: login.php");
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

// Consultar las actividades contractuales
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

// Verificar si hay actividades
if (empty($actividades)) {
    // Si no tiene actividades registradas, redireccionar a actividades_manuales.php
    header("Location: actividades_manuales.php?id=" . $id_contrato);
    exit();
}

// Verificar si existen tareas guardadas para este contrato
$tiene_tareas_guardadas = false;

// Verificar si existen las tablas necesarias
$tablas_existen = true;
$check_tables_query = "SELECT COUNT(*) as total FROM information_schema.tables 
                      WHERE table_schema = DATABASE() 
                      AND table_name IN ('contrato_tareas', 'contrato_evidencias')";
$check_tables_result = mysqli_query($conexion, $check_tables_query);

if (!$check_tables_result || mysqli_fetch_assoc($check_tables_result)['total'] < 2) {
    $tablas_existen = false;
}

if ($tablas_existen) {
    // Consulta directa y específica para verificar tareas asociadas a este contrato y contratista
    $check_tareas_query = "SELECT COUNT(*) as total FROM contrato_tareas 
                          WHERE contrato_id = ? AND cc_contratista = ?";
    
    $check_tareas_stmt = mysqli_prepare($conexion, $check_tareas_query);
    
    if ($check_tareas_stmt) {
        mysqli_stmt_bind_param($check_tareas_stmt, "is", $id_contrato, $cc_contratista);
        
        if (mysqli_stmt_execute($check_tareas_stmt)) {
            $check_result = mysqli_stmt_get_result($check_tareas_stmt);
            $tareas_count = mysqli_fetch_assoc($check_result);
            
            $tiene_tareas_guardadas = ($tareas_count['total'] > 0);
        } else {
            error_log("Error al ejecutar la consulta de verificación de tareas: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_close($check_tareas_stmt);
    } else {
        error_log("Error en la preparación de la consulta de verificación de tareas: " . mysqli_error($conexion));
    }
}

// Definir si se deben cargar tareas
$cargar_tareas = false;
if (isset($_GET['cargar_tareas'])) {
    $cargar_tareas = ($_GET['cargar_tareas'] === 'si');
}

// Funciones para obtener tareas y evidencias
function obtenerTareas($conexion, $id_contrato, $cc_contratista) {
    $tareas = [];
    
    $tareas_query = "SELECT * FROM contrato_tareas 
                    WHERE contrato_id = ? AND cc_contratista = ? 
                    ORDER BY actividad_id, orden ASC";
                    
    $tareas_stmt = mysqli_prepare($conexion, $tareas_query);
    
    if (!$tareas_stmt) {
        error_log("Error en la preparación de la consulta de tareas: " . mysqli_error($conexion));
        return $tareas;
    }
    
    mysqli_stmt_bind_param($tareas_stmt, "is", $id_contrato, $cc_contratista);
    
    if (!mysqli_stmt_execute($tareas_stmt)) {
        error_log("Error al ejecutar la consulta de tareas: " . mysqli_error($conexion));
        return $tareas;
    }
    
    $tareas_result = mysqli_stmt_get_result($tareas_stmt);
    
    while ($tarea = mysqli_fetch_assoc($tareas_result)) {
        $tareas[] = $tarea;
    }
    
    return $tareas;
}

function obtenerEvidencias($conexion, $id_contrato, $cc_contratista) {
    $evidencias = [];
    
    $evidencias_query = "SELECT * FROM contrato_evidencias 
                        WHERE contrato_id = ? AND cc_contratista = ?";
                        
    $evidencias_stmt = mysqli_prepare($conexion, $evidencias_query);
    
    if (!$evidencias_stmt) {
        error_log("Error en la preparación de la consulta de evidencias: " . mysqli_error($conexion));
        return $evidencias;
    }
    
    mysqli_stmt_bind_param($evidencias_stmt, "is", $id_contrato, $cc_contratista);
    
    if (!mysqli_stmt_execute($evidencias_stmt)) {
        error_log("Error al ejecutar la consulta de evidencias: " . mysqli_error($conexion));
        return $evidencias;
    }
    
    $evidencias_result = mysqli_stmt_get_result($evidencias_stmt);
    
    while ($evidencia = mysqli_fetch_assoc($evidencias_result)) {
        $evidencias[$evidencia['tarea_id']] = $evidencia;
    }
    
    return $evidencias;
}

$tareas = [];
$evidencias = [];
$tareas_por_actividad = [];

// Cargar tareas solo si se ha especificado
if ($cargar_tareas) {
    $tareas = obtenerTareas($conexion, $id_contrato, $cc_contratista);
    $evidencias = obtenerEvidencias($conexion, $id_contrato, $cc_contratista);

    // Organizar tareas por actividad
    foreach ($tareas as $tarea) {
    $actividad_id = $tarea['actividad_id'];
    if (!isset($tareas_por_actividad[$actividad_id])) {
        $tareas_por_actividad[$actividad_id] = [];
    }
    $tareas_por_actividad[$actividad_id][] = $tarea;
    }
    
    // Asegurar que cada actividad tenga al menos una tarea
    foreach ($actividades as $actividad) {
        $actividad_id = $actividad['id'];
        if (!isset($tareas_por_actividad[$actividad_id]) || empty($tareas_por_actividad[$actividad_id])) {
            $tareas_por_actividad[$actividad_id] = [];
        }
    }
}

// Procesar el formulario si se ha enviado
$mensaje = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar si es para guardar o para generar informe
    if (isset($_POST['guardar_tareas'])) {
        // Guardar tareas y evidencias
        // Primero, verificar si existen las tablas necesarias
        $crear_tablas = true;
        
        $crear_tabla_tareas = "CREATE TABLE IF NOT EXISTS contrato_tareas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contrato_id INT NOT NULL,
            cc_contratista VARCHAR(20) NOT NULL,
            actividad_id INT NOT NULL,
            orden INT NOT NULL,
            descripcion_tarea TEXT NOT NULL,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (contrato_id),
            INDEX (cc_contratista),
            INDEX (actividad_id)
        )";
        
        $crear_tabla_evidencias = "CREATE TABLE IF NOT EXISTS contrato_evidencias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contrato_id INT NOT NULL,
            cc_contratista VARCHAR(20) NOT NULL,
            tarea_id INT NOT NULL,
            descripcion_evidencia TEXT,
            url_evidencia VARCHAR(500),
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (contrato_id),
            INDEX (cc_contratista),
            INDEX (tarea_id)
        )";
        
        if (!mysqli_query($conexion, $crear_tabla_tareas) || !mysqli_query($conexion, $crear_tabla_evidencias)) {
            $crear_tablas = false;
            $mensaje = [
                'tipo' => 'error',
                'texto' => 'Error al crear las tablas necesarias: ' . mysqli_error($conexion)
            ];
        }
        
        if ($crear_tablas) {
            // Eliminar tareas y evidencias anteriores para este contrato
            mysqli_query($conexion, "DELETE FROM contrato_evidencias WHERE contrato_id = $id_contrato AND cc_contratista = '$cc_contratista'");
            mysqli_query($conexion, "DELETE FROM contrato_tareas WHERE contrato_id = $id_contrato AND cc_contratista = '$cc_contratista'");
            
            // Procesar tareas y evidencias enviadas
            $tareas_guardadas = 0;
            $evidencias_guardadas = 0;
            
            if (isset($_POST['tareas']) && is_array($_POST['tareas'])) {
                foreach ($_POST['tareas'] as $actividad_id => $tareas_actividad) {
                    $orden = 1;
                    foreach ($tareas_actividad as $tarea_data) {
                        $descripcion_tarea = mysqli_real_escape_string($conexion, $tarea_data['descripcion']);
                        
                        if (!empty($descripcion_tarea)) {
                            // Insertar tarea
                            $query_tarea = "INSERT INTO contrato_tareas 
                                          (contrato_id, cc_contratista, actividad_id, orden, descripcion_tarea) 
                                          VALUES (?, ?, ?, ?, ?)";
                            $stmt_tarea = mysqli_prepare($conexion, $query_tarea);
                            mysqli_stmt_bind_param($stmt_tarea, "isiss", $id_contrato, $cc_contratista, $actividad_id, $orden, $descripcion_tarea);
                            
                            if (mysqli_stmt_execute($stmt_tarea)) {
                                $tarea_id = mysqli_insert_id($conexion);
                                $tareas_guardadas++;
                                
                                // Guardar evidencia si existe
                                if (isset($tarea_data['evidencia']) && !empty($tarea_data['evidencia'])) {
                                    $url_evidencia = mysqli_real_escape_string($conexion, $tarea_data['evidencia']);
                                    
                                    $query_evidencia = "INSERT INTO contrato_evidencias 
                                                     (contrato_id, cc_contratista, tarea_id, url_evidencia) 
                                                     VALUES (?, ?, ?, ?)";
                                    $stmt_evidencia = mysqli_prepare($conexion, $query_evidencia);
                                    mysqli_stmt_bind_param($stmt_evidencia, "isis", $id_contrato, $cc_contratista, $tarea_id, $url_evidencia);
                                    
                                    if (mysqli_stmt_execute($stmt_evidencia)) {
                                        $evidencias_guardadas++;
                                    }
                                }
                            }
                            
                            $orden++;
                        }
                    }
                }
                
                $mensaje = [
                    'tipo' => 'success',
                    'texto' => "Se han guardado $tareas_guardadas tareas y $evidencias_guardadas evidencias correctamente."
                ];
                
                // Recargar tareas y evidencias
                $tareas = obtenerTareas($conexion, $id_contrato, $cc_contratista);
                $evidencias = obtenerEvidencias($conexion, $id_contrato, $cc_contratista);
                
                // Recargar tareas_por_actividad
                $tareas_por_actividad = [];
                foreach ($tareas as $tarea) {
                    $actividad_id = $tarea['actividad_id'];
                    if (!isset($tareas_por_actividad[$actividad_id])) {
                        $tareas_por_actividad[$actividad_id] = [];
                    }
                    $tareas_por_actividad[$actividad_id][] = $tarea;
                }
                
                // Marcar que ahora hay tareas guardadas
                $tiene_tareas_guardadas = true;
                $cargar_tareas = true;
            } else {
                $mensaje = [
                    'tipo' => 'error',
                    'texto' => 'No se enviaron tareas para guardar.'
                ];
            }
        }
    } elseif (isset($_POST['generar_informe']) || isset($_POST['guardar_y_generar'])) {
        // Verificar fechas
        if (empty($_POST['fecha_inicio']) || empty($_POST['fecha_fin'])) {
            $mensaje = [
                'tipo' => 'error',
                'texto' => 'Debe ingresar las fechas del periodo para generar el informe.'
            ];
        } else {
            // Procesar fechas
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            
            // Consultar si el contrato tiene otro sí
            $otrosi_query = "SELECT * FROM contrato_otrosi WHERE contrato_id = ? ORDER BY id DESC LIMIT 1";
            $otrosi_stmt = mysqli_prepare($conexion, $otrosi_query);
            if ($otrosi_stmt) {
                mysqli_stmt_bind_param($otrosi_stmt, "i", $id_contrato);
                mysqli_stmt_execute($otrosi_stmt);
                $otrosi_result = mysqli_stmt_get_result($otrosi_stmt);
                $tiene_otrosi = mysqli_num_rows($otrosi_result) > 0;
                if ($tiene_otrosi) {
                    $otrosi_data = mysqli_fetch_assoc($otrosi_result);
                }
            }

            // Consultar si el contrato tiene cesión
            $cesion_query = "SELECT * FROM contrato_cesion WHERE contrato_id = ? ORDER BY id DESC LIMIT 1";
            $cesion_stmt = mysqli_prepare($conexion, $cesion_query);
            if ($cesion_stmt) {
                mysqli_stmt_bind_param($cesion_stmt, "i", $id_contrato);
                mysqli_stmt_execute($cesion_stmt);
                $cesion_result = mysqli_stmt_get_result($cesion_stmt);
                $tiene_cesion = mysqli_num_rows($cesion_result) > 0;
                if ($tiene_cesion) {
                    $cesion_data = mysqli_fetch_assoc($cesion_result);
                }
            }

            // Consultar datos del contratista
            $contratista_query = "SELECT c.*, t.nombre as tipo_identificacion_nombre, t.sigla as tipo_identificacion_sigla 
            FROM contratista c 
            LEFT JOIN tipo_identificacion t ON c.tipo_identificacion_id = t.id 
            WHERE c.cedula = ?";
            $contratista_stmt = mysqli_prepare($conexion, $contratista_query);
            if ($contratista_stmt) {
            mysqli_stmt_bind_param($contratista_stmt, "s", $cc_contratista);
            mysqli_stmt_execute($contratista_stmt);
            $contratista_result = mysqli_stmt_get_result($contratista_stmt);
            if (mysqli_num_rows($contratista_result) > 0) {
            $contratista_data = mysqli_fetch_assoc($contratista_result);
            }
            }

            // Calcular valores automáticos basados en las fechas
            $valor_contrato_limpio = limpiarValorMonetario($contrato['valor']);

            // Calcular porcentajes de ejecución de tiempo
            $porcentajes_tiempo = calcularPorcentajeEjecucionTiempo(
            $contrato['fecha_inicio'],
            $contrato['fecha_fin'],
            $fecha_fin
            );

            // Calcular valores de ejecución financiera
            $valores_ejecucion = calcularValoresEjecucion(
            $contrato['fecha_inicio'],
            $contrato['fecha_fin'],
            $fecha_inicio,
            $fecha_fin,
            $valor_contrato_limpio
            );

            // Obtener mes anterior para seguridad social
            $mes_anterior = obtenerMesAnteriorSegSocial($fecha_inicio);
            $_SESSION['informe_mes_seguridad_social'] = $mes_anterior['mes'];
            $_SESSION['informe_anio_seguridad_social'] = $mes_anterior['anio'];

            // Valor a cobrar automático
            $valor_a_cobrar = $valores_ejecucion['valor_a_cobrar'];
            $valor_cobro = '$' . number_format($valor_a_cobrar, 0, '', "'");
            $valor_cobro_letras = numeroALetras($valor_a_cobrar);
            
            // Si la opción es guardar y generar, primero guardamos
            if (isset($_POST['guardar_y_generar'])) {
                // Verificar si existen las tablas necesarias
                $crear_tablas = true;
                
                $crear_tabla_tareas = "CREATE TABLE IF NOT EXISTS contrato_tareas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    contrato_id INT NOT NULL,
                    cc_contratista VARCHAR(20) NOT NULL,
                    actividad_id INT NOT NULL,
                    orden INT NOT NULL,
                    descripcion_tarea TEXT NOT NULL,
                    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (contrato_id),
                    INDEX (cc_contratista),
                    INDEX (actividad_id)
                )";
                
                $crear_tabla_evidencias = "CREATE TABLE IF NOT EXISTS contrato_evidencias (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    contrato_id INT NOT NULL,
                    cc_contratista VARCHAR(20) NOT NULL,
                    tarea_id INT NOT NULL,
                    descripcion_evidencia TEXT,
                    url_evidencia VARCHAR(500),
                    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (contrato_id),
                    INDEX (cc_contratista),
                    INDEX (tarea_id)
                )";
                
                if (!mysqli_query($conexion, $crear_tabla_tareas) || !mysqli_query($conexion, $crear_tabla_evidencias)) {
                    $crear_tablas = false;
                    $mensaje = [
                        'tipo' => 'error',
                        'texto' => 'Error al crear las tablas necesarias: ' . mysqli_error($conexion)
                    ];
                    // No continuamos con la generación del informe
                    goto skip_generation;
                }
                
                // Eliminar tareas y evidencias anteriores para este contrato
                mysqli_query($conexion, "DELETE FROM contrato_evidencias WHERE contrato_id = $id_contrato AND cc_contratista = '$cc_contratista'");
                mysqli_query($conexion, "DELETE FROM contrato_tareas WHERE contrato_id = $id_contrato AND cc_contratista = '$cc_contratista'");
                
                // Guardar las tareas
                if (isset($_POST['tareas']) && is_array($_POST['tareas'])) {
                    foreach ($_POST['tareas'] as $actividad_id => $tareas_actividad) {
                        $orden = 1;
                        foreach ($tareas_actividad as $tarea_data) {
                            $descripcion_tarea = mysqli_real_escape_string($conexion, $tarea_data['descripcion']);
                            
                            if (!empty($descripcion_tarea)) {
                                // Insertar tarea
                                $query_tarea = "INSERT INTO contrato_tareas 
                                              (contrato_id, cc_contratista, actividad_id, orden, descripcion_tarea) 
                                              VALUES (?, ?, ?, ?, ?)";
                                $stmt_tarea = mysqli_prepare($conexion, $query_tarea);
                                mysqli_stmt_bind_param($stmt_tarea, "isiss", $id_contrato, $cc_contratista, $actividad_id, $orden, $descripcion_tarea);
                                
                                if (mysqli_stmt_execute($stmt_tarea)) {
                                    $tarea_id = mysqli_insert_id($conexion);
                                    
                                    // Guardar evidencia si existe
                                    if (isset($tarea_data['evidencia']) && !empty($tarea_data['evidencia'])) {
                                        $url_evidencia = mysqli_real_escape_string($conexion, $tarea_data['evidencia']);
                                        
                                        $query_evidencia = "INSERT INTO contrato_evidencias 
                                                         (contrato_id, cc_contratista, tarea_id, url_evidencia) 
                                                         VALUES (?, ?, ?, ?)";
                                        $stmt_evidencia = mysqli_prepare($conexion, $query_evidencia);
                                        mysqli_stmt_bind_param($stmt_evidencia, "isis", $id_contrato, $cc_contratista, $tarea_id, $url_evidencia);
                                        mysqli_stmt_execute($stmt_evidencia);
                                    }
                                }
                                
                                $orden++;
                            }
                        }
                    }
                }

                // Para asegurarnos de que se usen los datos más recientes
                $cargar_tareas = true;
            }
            
            // Si hemos decidido usar las tareas guardadas, carguémoslas
            if ($cargar_tareas) {
                $tareas = obtenerTareas($conexion, $id_contrato, $cc_contratista);
                $evidencias = obtenerEvidencias($conexion, $id_contrato, $cc_contratista);
                
                $tareas_por_actividad = [];
                foreach ($tareas as $tarea) {
                    $actividad_id = $tarea['actividad_id'];
                    if (!isset($tareas_por_actividad[$actividad_id])) {
                        $tareas_por_actividad[$actividad_id] = [];
                    }
                    $tareas_por_actividad[$actividad_id][] = $tarea;
                }
            }
            
            // Almacenar datos en sesión para la página de generación
        
        $_SESSION['numero_contrato'] = $contrato['numero_contrato'];
        $_SESSION['numero_contrato'] = $contrato['numero_contrato'];
        $_SESSION['fecha_contrato'] = $contrato['fecha_contrato'];
        $_SESSION['nombres'] = $_SESSION['nombres'];
        $_SESSION['fecha_inicio'] = $fecha_inicio;
        $_SESSION['fecha_fin'] = $fecha_fin;
        $_SESSION['proceso'] = $contrato['proceso_nombre'] ?? 'No especificado';
        $_SESSION['informe_fecha_inicio'] = $fecha_inicio;
        $_SESSION['informe_fecha_fin'] = $fecha_fin;
        $_SESSION['informe_valor_cobro'] = $valor_cobro;
        $_SESSION['informe_nro_pago'] = $valores_ejecucion['nro_pago'];
        $_SESSION['informe_total_pagos'] = $valores_ejecucion['total_pagos'];
        $_SESSION['unidad_academica'] = $contrato['unidad_academica'] ?? 'No especificada';
        $_SESSION['sede'] = $contrato['sede'] ?? 'No especificada';
        $_SESSION['fecha_inicio_informe'] = $fecha_inicio;
        $_SESSION['fecha_fin_informe'] = $fecha_fin;
        $_SESSION['disponibilidad_presupuestal'] = $contrato['disponibilidad_presupuestal'] ?? '';
        $_SESSION['rp'] = $contrato['rp'] ?? '';
        $_SESSION['objeto'] = $contrato['objeto'] ?? '';
        // Datos adicionales para el informe
        $_SESSION['informe_valor_cobro_letras'] = $valor_cobro_letras;
        $_SESSION['informe_porcentaje_ejecutado'] = $porcentajes_tiempo['ejecutado'];
        $_SESSION['informe_porcentaje_pendiente'] = $porcentajes_tiempo['pendiente'];
        $_SESSION['informe_valor_ejecutado'] = $valores_ejecucion['valor_ejecutado'];
        $_SESSION['informe_valor_pendiente'] = $valores_ejecucion['valor_pendiente'];
        
        
        
        // Datos del tipo de identificación
        if (isset($contratista_data)) {
            $_SESSION['informe_tipo_identificacion_nombre'] = $contratista_data['tipo_identificacion_nombre'] ?? '';
            $_SESSION['informe_tipo_identificacion_sigla'] = $contratista_data['tipo_identificacion_sigla'] ?? '';
            $_SESSION['informe_lugar_expedicion'] = $contratista_data['lugar_expedicion'] ?? '';
        }

        // Datos de otrosí
        if (isset($tiene_otrosi) && $tiene_otrosi) {
            $_SESSION['informe_tiene_otrosi'] = true;
            $_SESSION['informe_fecha_inicio_otrosi'] = $otrosi_data['fecha_inicio_otrosi'];
            $_SESSION['informe_fecha_fin_otrosi'] = $otrosi_data['fecha_fin_otrosi'];
        } else {
            $_SESSION['informe_tiene_otrosi'] = false;
        }

        // Datos de cesión
        if (isset($tiene_cesion) && $tiene_cesion) {
            $_SESSION['informe_tiene_cesion'] = true;
            $_SESSION['informe_fecha_cesion'] = $cesion_data['fecha_cesion'];
            $_SESSION['informe_cc_cesionario'] = $cesion_data['cc_cesionario'];
            $_SESSION['informe_nombre_cesionario'] = $cesion_data['nombre_cesionario'];
        } else {
            $_SESSION['informe_tiene_cesion'] = false;
        }
        
        
        
        // Redirigir a la página de generación del informe
        header("Location: generar_informe.php?id=" . $id_contrato);
        exit();
        }
    }
    
    skip_generation:
        // Etiqueta para saltar la generación en caso de error al guardar
}

// Función para formatear fechas
function formatearFechaSimple($fecha) {
    if (empty($fecha)) return '';
    
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

// Calcular número de pago y total de pagos sugeridos
function calcularPagos($fecha_inicio, $fecha_fin) {
    if (empty($fecha_inicio) || empty($fecha_fin)) {
        return ['nro_pago' => 1, 'total_pagos' => 1];
    }
    
    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    $hoy = new DateTime();
    
    // Si la fecha actual está después de la fecha de fin del contrato, usar fecha fin del contrato
    if ($hoy > $fin) {
        $hoy = clone $fin;
    }
    
    $intervalo_total = $inicio->diff($fin);
    $meses_totales = $intervalo_total->y * 12 + $intervalo_total->m + ($intervalo_total->d > 0 ? 1 : 0);
    $meses_totales = max(1, $meses_totales);
    
    // Si la fecha actual está antes de la fecha de inicio, usar fecha de inicio
    if ($hoy < $inicio) {
        $hoy = clone $inicio;
    }
    
    $intervalo_actual = $inicio->diff($hoy);
    $meses_transcurridos = $intervalo_actual->y * 12 + $intervalo_actual->m;
    
    $nro_pago = min($meses_transcurridos + 1, $meses_totales);
    $total_pagos = max(1, $meses_totales);
    
    return ['nro_pago' => $nro_pago, 'total_pagos' => $total_pagos];
}

$pagos_sugeridos = calcularPagos($contrato['fecha_inicio'], $contrato['fecha_fin']);




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

// Función para limpiar valores monetarios
function limpiarValorMonetario($valor) {
    // Eliminar todos los caracteres que no sean dígitos o punto
    return preg_replace('/[^0-9.]/', '', $valor);
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

// Función para convertir número a letras en español
function numeroALetras($numero) {
    // Asegurarse de que el número está limpio antes de procesarlo
    $numero = preg_replace('/[^0-9.]/', '', $numero);
    
    // Convertir a entero
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

// Función para calcular días financieros entre dos fechas
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
    
    return $dias_primer_mes + $dias_meses_intermedios + $dias_ultimo_mes;
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

// Calcular valores de ejecución financiera
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
    $valor_pendiente = $valor_contrato - $valor_ejecutado - $valor_a_cobrar;
    
    // Si por alguna razón el valor pendiente es negativo, lo ajustamos a cero
    if ($valor_pendiente < 0) {
        $valor_pendiente = 0;
    }
    
    // Calcular número de pago y total de pagos
    $meses_totales = calcularMesesFinancieros($inicio_contrato, $fin_contrato);
    $meses_ejecutados = calcularMesesFinancieros($inicio_contrato, $inicio_informe);
    
    $nro_pago = $meses_ejecutados + 1; // +1 porque este es el siguiente periodo
    $total_pagos = max(1, $meses_totales); // Evitar un total de pagos de cero
    
    return [
        'valor_a_cobrar' => $valor_a_cobrar,
        'valor_ejecutado' => $valor_ejecutado,
        'valor_pendiente' => $valor_pendiente,
        'nro_pago' => $nro_pago,
        'total_pagos' => $total_pagos
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades y Tareas | Universidad Distrital</title>
    <link rel="icon" type="image/png" href="images/favicon.png"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #003366;
            --primary-dark: #002244;
            --primary-light: #004488;
            --primary-ultra-light: #E6F0FF;
            --accent-color: #FF8C00;
            --accent-dark: #E67E00;
            --accent-light: #FFD700;
            --white: #FFFFFF;
            --light-gray: #F5F7FA;
            --medium-gray: #E0E5EC;
            --dark-gray: #6B7280;
            --text-dark: #1F2937;
            --success: #10B981;
            --success-light: #ECFDF5;
            --error: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
            --border-color: #D1D5DB;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
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
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
        }
        
        .btn-secondary {
            background: linear-gradient(to right, var(--medium-gray), #C5CAD3);
            color: var(--text-dark);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(to right, #C5CAD3, var(--medium-gray));
            color: var(--text-dark);
        }
        
        .btn-success {
            background: linear-gradient(to right, var(--success), #0D9668);
            color: var(--white);
        }
        
        .btn-success:hover {
            background: linear-gradient(to right, #0D9668, var(--success));
        }
        
        .btn-danger {
            background: linear-gradient(to right, var(--error), #DC2626);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background: linear-gradient(to right, #DC2626, var(--error));
        }
        
        .btn-warning {
            background: linear-gradient(to right, var(--warning), #D97706);
            color: var(--white);
        }
        
        .btn-warning:hover {
            background: linear-gradient(to right, #D97706, var(--warning));
        }
        
        .btn-info {
            background: linear-gradient(to right, var(--info), #1D4ED8);
            color: var(--white);
        }
        
        .btn-info:hover {
            background: linear-gradient(to right, #1D4ED8, var(--info));
        }
        
        .btn-icon {
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--box-shadow);
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
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background-color: var(--success-light);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error);
            color: var(--error);
        }
        
        .alert-info {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--info);
            color: var(--info);
        }
        
        .alert-icon {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        .activities-container {
            margin-bottom: 30px;
        }
        
        .activity-card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid var(--medium-gray);
            transition: all 0.3s ease;
        }
        
        .activity-card:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .activity-header {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: var(--white);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            position: relative;
        }
        .activity-number {
            width: 36px;
            height: 36px;
            background: var(--accent-color);
            color: var(--white);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .activity-title {
            font-weight: 600;
            font-size: 1.1rem;
            flex-grow: 1;
        }
        
        .activity-body {
            padding: 20px;
        }
        
        .activity-description {
            background-color: var(--primary-ultra-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .tasks-container {
            margin-top: 20px;
        }
        
        .task-item {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--medium-gray);
            position: relative;
            transition: all 0.2s ease;
        }
        
        .task-item:hover {
            background-color: var(--white);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .task-number {
            background: var(--primary-light);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 0.8rem;
        }
        
        .task-actions {
            display: flex;
            gap: 5px;
        }
        
        .task-action-btn {
            background: none;
            border: none;
            color: var(--dark-gray);
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .task-action-btn:hover {
            color: var(--primary-dark);
            background-color: rgba(0, 51, 102, 0.1);
        }
        
        .remove-btn {
            color: var(--error);
        }
        
        .remove-btn:hover {
            color: #DC2626;
            background-color: rgba(239, 68, 68, 0.1);
        }
        
        .task-content {
            margin-bottom: 10px;
        }
        
        .task-description {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 6px;
            min-height: 60px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            resize: vertical;
            transition: all 0.2s;
        }
        
        .task-description:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
        
        .task-evidence {
            margin-top: 10px;
            display: flex;
            align-items: center;
        }
        
        .evidence-label {
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.9rem;
            min-width: 80px;
            margin-right: 10px;
        }
        
        .evidence-input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 6px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .evidence-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
        
        .task-hint {
            margin-top: 5px;
            font-size: 0.8rem;
            color: var(--dark-gray);
            font-style: italic;
        }
        
        .add-task-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background-color: var(--primary-ultra-light);
            border: 1px dashed var(--primary-light);
            border-radius: 8px;
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 15px;
        }
        
        .add-task-btn:hover {
            background-color: var(--primary-light);
            color: var(--white);
        }
        
        .add-task-btn i {
            margin-right: 10px;
        }
        
        .form-section {
            background-color: var(--white);
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid var(--primary-color);
        }
        
        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 6px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            border-top: 1px solid var(--medium-gray);
            padding-top: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .form-hint {
            font-size: 0.85rem;
            color: var(--dark-gray);
            margin-top: 4px;
        }
        
        .no-tasks-message {
            text-align: center;
            padding: 30px;
            background-color: var(--light-gray);
            border-radius: 8px;
            color: var(--dark-gray);
            margin: 20px 0;
        }
        
        .no-tasks-icon {
            font-size: 3rem;
            color: var(--medium-gray);
            margin-bottom: 15px;
        }
        
        .tasks-decision {
            background-color: var(--primary-ultra-light);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid var(--primary-light);
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
        }
        
        .tasks-decision-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .tasks-decision-text {
            margin-bottom: 20px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .tasks-decision-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .footer {
            text-align: center;
            padding: 24px;
            margin-top: 40px;
            border-top: 1px solid var(--medium-gray);
            color: var(--dark-gray);
            font-size: 0.85rem;
            background-color: var(--white);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.03);
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                padding: 15px;
            }
            
            .logo-wrapper {
                margin-bottom: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-title {
                margin-bottom: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-actions .btn {
                width: 100%;
            }
            
            .tasks-decision-buttons {
                flex-direction: column;
            }
            
            .tasks-decision-buttons .btn {
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
                <h1 class="app-title">Gestión de Actividades y Tareas</h1>
            </div>
            <div class="user-info">
                <span class="user-name">
                    <i class="fas fa-user-circle" style="margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($_SESSION['nombres']); ?>
                </span>
                <a href="contratos.php" class="btn btn-secondary" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left btn-icon"></i>
                    Volver
                </a>
                <a href="back/logout.php" class="btn btn-danger" style="margin-left: 10px;">
                    <i class="fas fa-sign-out-alt btn-icon"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
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
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Objeto</th>
                            <th>Proceso</th>
                            <th>Unidad Académica</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($contrato['numero_contrato']); ?></td>
                            <td><?php echo htmlspecialchars(formatearFechaSimple($contrato['fecha_contrato'])); ?></td>
                            <td><?php echo htmlspecialchars($contrato['objeto']); ?></td>
                            <td><?php echo htmlspecialchars($contrato['proceso_nombre'] ?? 'No especificado'); ?></td>
                            <td><?php echo htmlspecialchars($contrato['unidad_academica'] ?? 'No especificada'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $mensaje['tipo'] === 'success' ? 'success' : 'error'; ?>">
            <div class="alert-icon">
                <i class="fas fa-<?php echo $mensaje['tipo'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            </div>
            <div>
                <?php echo $mensaje['texto']; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tiene_tareas_guardadas && !isset($_GET['cargar_tareas'])): ?>
        <!-- Mostrar pantalla de decisión si cargar tareas o no -->
        <div class="tasks-decision">
            <div class="tasks-decision-title">
                <i class="fas fa-clipboard-check" style="margin-right: 10px;"></i>
                Tareas guardadas disponibles
            </div>
            <div class="tasks-decision-text">
                <p>Se encontraron tareas previamente guardadas para este contrato. ¿Desea cargarlas o prefiere crear nuevas tareas?</p>
            </div>
            <div class="tasks-decision-buttons">
                <a href="<?php echo htmlspecialchars('index3.php?id=' . $id_contrato . '&cargar_tareas=si'); ?>" class="btn btn-primary">
                    <i class="fas fa-folder-open btn-icon"></i>
                    Cargar tareas guardadas
                </a>
                <a href="<?php echo htmlspecialchars('index3.php?id=' . $id_contrato . '&cargar_tareas=no'); ?>" class="btn btn-secondary">
                    <i class="fas fa-plus-circle btn-icon"></i>
                    Crear nuevas tareas
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Mostrar formulario de actividades y tareas -->
        <form action="<?php echo htmlspecialchars('index3.php?id=' . $id_contrato); ?>" method="post" id="actividadesForm">
            <div class="activities-container">
                <h3 style="margin-bottom: 20px; color: var(--primary-color);">
                    <i class="fas fa-clipboard-list" style="margin-right: 10px;"></i>
                    Actividades Contractuales
                </h3>
                
                <?php foreach ($actividades as $index => $actividad): ?>
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-number"><?php echo $index + 1; ?></div>
                        <div class="activity-title">Actividad Contractual</div>
                    </div>
                    <div class="activity-body">
                        <div class="activity-description">
                            <?php echo htmlspecialchars($actividad['descripcion_actividad']); ?>
                        </div>
                        
                        <h4 style="margin: 20px 0 15px; font-size: 1.05rem; color: var(--primary-color);">
                            <i class="fas fa-tasks" style="margin-right: 8px;"></i>
                            Tareas realizadas en el periodo
                        </h4>
                        
                        <div class="tasks-container" id="tasks-container-<?php echo $actividad['id']; ?>" data-actividad-id="<?php echo $actividad['id']; ?>">
                            <?php 
                            // Mostrar tareas existentes para esta actividad
                            $actividad_tareas = isset($tareas_por_actividad[$actividad['id']]) ? $tareas_por_actividad[$actividad['id']] : [];
                            
                            if (!empty($actividad_tareas)):
                                foreach ($actividad_tareas as $tarea_index => $tarea): 
                                $tarea_id = $tarea['id'];
                                $evidencia = isset($evidencias[$tarea_id]) ? $evidencias[$tarea_id]['url_evidencia'] : '';
                            ?>
                            <div class="task-item" id="task-<?php echo $actividad['id']; ?>-<?php echo $tarea_index + 1; ?>">
                                <div class="task-header">
                                    <div style="display: flex; align-items: center;">
                                        <div class="task-number"><?php echo $tarea_index + 1; ?></div>
                                        <span>Tarea</span>
                                    </div>
                                    <div class="task-actions">
                                        <button type="button" class="task-action-btn remove-btn" 
                                                onclick="window.TaskManager.eliminarTarea(<?php echo $actividad['id']; ?>, <?php echo $tarea_index + 1; ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="task-content">
                                    <textarea class="task-description" 
                                             name="tareas[<?php echo $actividad['id']; ?>][<?php echo $tarea_index + 1; ?>][descripcion]" 
                                             placeholder="Describa la tarea realizada..." required><?php echo htmlspecialchars($tarea['descripcion_tarea']); ?></textarea>
                                </div>
                                <div class="task-evidence">
                                    <div class="evidence-label">Evidencia:</div>
                                    <input type="text" class="evidence-input" 
                                           name="tareas[<?php echo $actividad['id']; ?>][<?php echo $tarea_index + 1; ?>][evidencia]" 
                                           placeholder="URL o referencia de la evidencia (opcional)" 
                                           value="<?php echo htmlspecialchars($evidencia); ?>">
                                </div>
                                <div class="task-hint">Puede incluir enlaces a documentos, sitios web, o referencias físicas.</div>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="task-item" id="task-<?php echo $actividad['id']; ?>-1">
                                <div class="task-header">
                                    <div style="display: flex; align-items: center;">
                                        <div class="task-number">1</div>
                                        <span>Tarea</span>
                                    </div>
                                    <div class="task-actions">
                                        <button type="button" class="task-action-btn remove-btn" 
                                                onclick="window.TaskManager.eliminarTarea(<?php echo $actividad['id']; ?>, 1)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="task-content">
                                    <textarea class="task-description" 
                                             name="tareas[<?php echo $actividad['id']; ?>][1][descripcion]" 
                                             placeholder="Describa la tarea realizada..." required></textarea>
                                </div>
                                <div class="task-evidence">
                                    <div class="evidence-label">Evidencia:</div>
                                    <input type="text" class="evidence-input" 
                                           name="tareas[<?php echo $actividad['id']; ?>][1][evidencia]" 
                                           placeholder="URL o referencia de la evidencia (opcional)">
                                </div>
                                <div class="task-hint">Puede incluir enlaces a documentos, sitios web, o referencias físicas.</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="add-task-btn" onclick="window.TaskManager.agregarTarea(<?php echo $actividad['id']; ?>)">
                            <i class="fas fa-plus"></i>
                            Agregar Tarea
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-file-alt" style="margin-right: 10px;"></i>
                    Información para Generar el Informe
                </h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="fecha_inicio">Fecha de inicio del periodo</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="fecha_fin">Fecha de fin del periodo</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success" name="guardar_tareas">
                        <i class="fas fa-save btn-icon"></i>
                        Solo Guardar Tareas
                    </button>
                    
                    <button type="submit" class="btn btn-info" name="guardar_y_generar">
                        <i class="fas fa-save btn-icon"></i>
                        Guardar y Generar Informe
                    </button>
                    
                    <button type="submit" class="btn btn-primary" name="generar_informe" id="btnGenerarInforme">
                        <i class="fas fa-file-export btn-icon"></i>
                        Solo Generar Informe
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>
    
    <script>
    // Global namespace for functions
    window.TaskManager = {
        // Función para agregar tarea
        agregarTarea: function(actividadId) {
            console.log('Agregando tarea para actividad:', actividadId);
            
            // Obtener el contenedor de tareas
            const contenedor = document.querySelector(`.tasks-container[data-actividad-id="${actividadId}"]`);
            
            if (!contenedor) {
                console.error(`No se encontró el contenedor para la actividad ${actividadId}`);
                return;
            }

            // Eliminar mensaje de no hay tareas si existe
            const mensajeNoTareas = contenedor.querySelector('.no-tasks-message');
            if (mensajeNoTareas) {
                mensajeNoTareas.remove();
            }

            // Calcular el nuevo índice de tarea
            const tareasExistentes = contenedor.querySelectorAll('.task-item');
            const nuevoIndice = tareasExistentes.length + 1;

            // Crear elemento de tarea
            const nuevaTarea = document.createElement('div');
            nuevaTarea.className = 'task-item';
            nuevaTarea.id = `task-${actividadId}-${nuevoIndice}`;
            nuevaTarea.innerHTML = `
                <div class="task-header">
                    <div style="display: flex; align-items: center;">
                        <div class="task-number">${nuevoIndice}</div>
                        <span>Tarea</span>
                    </div>
                    <div class="task-actions">
                        <button type="button" class="task-action-btn remove-btn" 
                                onclick="window.TaskManager.eliminarTarea(${actividadId}, ${nuevoIndice})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="task-content">
                    <textarea class="task-description" 
                            name="tareas[${actividadId}][${nuevoIndice}][descripcion]"
                            placeholder="Describa la tarea realizada..." 
                            required></textarea>
                </div>
                <div class="task-evidence">
                    <div class="evidence-label">Evidencia:</div>
                    <input type="text" class="evidence-input" 
                        name="tareas[${actividadId}][${nuevoIndice}][evidencia]"
                        placeholder="URL o referencia de la evidencia (opcional)">
                </div>
                <div class="task-hint">Puede incluir enlaces a documentos, sitios web, o referencias físicas.</div>
            `;

            // Agregar la nueva tarea al contenedor
            contenedor.appendChild(nuevaTarea);
        },

        // Función para eliminar tarea
        eliminarTarea: function(actividadId, indiceTarea) {
            console.log(`Eliminando tarea ${indiceTarea} de actividad ${actividadId}`);
            
            // Buscar el contenedor de tareas de la actividad específica
            const contenedor = document.querySelector(`.tasks-container[data-actividad-id="${actividadId}"]`);
            
            if (!contenedor) {
                console.error(`No se encontró el contenedor para la actividad ${actividadId}`);
                return;
            }

            // Encontrar la tarea específica a eliminar
            const tarea = document.getElementById(`task-${actividadId}-${indiceTarea}`);
            
            if (!tarea) {
                console.error(`No se encontró la tarea ${indiceTarea} en la actividad ${actividadId}`);
                return;
            }

            // Eliminar la tarea
            tarea.remove();

            // Obtener todas las tareas restantes
            const tareasRestantes = contenedor.querySelectorAll('.task-item');

            // Reordenar tareas si quedan más
            if (tareasRestantes.length > 0) {
                tareasRestantes.forEach((tareaActualizada, index) => {
                    const nuevoIndice = index + 1;
                    
                    // Actualizar ID
                    tareaActualizada.id = `task-${actividadId}-${nuevoIndice}`;
                    
                    // Actualizar número visible
                    const numeroTarea = tareaActualizada.querySelector('.task-number');
                    if (numeroTarea) {
                        numeroTarea.textContent = nuevoIndice;
                    }
                    
                    // Actualizar nombres de inputs
                    const textarea = tareaActualizada.querySelector('textarea');
                    const inputEvidencia = tareaActualizada.querySelector('input[type="text"]');
                    
                    if (textarea) {
                        textarea.name = `tareas[${actividadId}][${nuevoIndice}][descripcion]`;
                    }
                    
                    if (inputEvidencia) {
                        inputEvidencia.name = `tareas[${actividadId}][${nuevoIndice}][evidencia]`;
                    }
                    
                    // Actualizar botón de eliminar
                    const botonEliminar = tareaActualizada.querySelector('.remove-btn');
                    if (botonEliminar) {
                        botonEliminar.setAttribute('onclick', `window.TaskManager.eliminarTarea(${actividadId}, ${nuevoIndice})`);
                    }
                });
            } else {
                // Si no quedan tareas, mostrar mensaje
                const mensajeNoTareas = document.createElement('div');
                mensajeNoTareas.className = 'no-tasks-message';
                mensajeNoTareas.innerHTML = `
                    <div class="no-tasks-icon">
                        <i class="fas fa-clipboard"></i>
                    </div>
                    <p>No hay tareas registradas para esta actividad.</p>
                    <p>Usa el botón "Agregar Tarea" para registrar las tareas realizadas.</p>
                `;
                
                contenedor.appendChild(mensajeNoTareas);
            }
        },

        // Función para cargar tareas existentes
        cargarTareasExistentes: function() {
            console.log('Cargando tareas existentes...');
            
            const contenedoresTareas = document.querySelectorAll('.tasks-container');
            console.log(`Encontrados ${contenedoresTareas.length} contenedores de tareas`);
            
            contenedoresTareas.forEach(contenedor => {
                const actividadId = contenedor.getAttribute('data-actividad-id');
                console.log(`Procesando actividad ${actividadId}`);
                
                // Buscar tareas ya renderizadas para esta actividad
                const tareasExistentes = contenedor.querySelectorAll('.task-item');
                console.log(`La actividad ${actividadId} tiene ${tareasExistentes.length} tareas existentes`);
                
                if (tareasExistentes.length > 0) {
                    // Si ya hay tareas visibles en la interfaz, asegurar que estén correctamente configuradas
                    const mensajeNoTareas = contenedor.querySelector('.no-tasks-message');
                    if (mensajeNoTareas) {
                        mensajeNoTareas.remove();
                    }
                    
                    tareasExistentes.forEach((tarea, index) => {
                        const nuevoIndice = index + 1;
                        console.log(`Actualizando tarea ${nuevoIndice} de actividad ${actividadId}`);
                        
                        // Actualizar el ID de la tarea
                        tarea.id = `task-${actividadId}-${nuevoIndice}`;
                        
                        // Actualizar número mostrado
                        const numeroTarea = tarea.querySelector('.task-number');
                        if (numeroTarea) {
                            numeroTarea.textContent = nuevoIndice;
                        }
                        
                        // Actualizar botón de eliminar
                        const botonEliminar = tarea.querySelector('.remove-btn');
                        if (botonEliminar) {
                            botonEliminar.setAttribute('onclick', `window.TaskManager.eliminarTarea(${actividadId}, ${nuevoIndice})`);
                        }
                        
                        // Asegurar que los campos de entrada tengan los nombres correctos
                        const tareaTextarea = tarea.querySelector('textarea');
                        const evidenciaInput = tarea.querySelector('input[type="text"]');
                        
                        if (tareaTextarea) {
                            tareaTextarea.name = `tareas[${actividadId}][${nuevoIndice}][descripcion]`;
                        }
                        
                        if (evidenciaInput) {
                            evidenciaInput.name = `tareas[${actividadId}][${nuevoIndice}][evidencia]`;
                        }
                    });
                } else if (window.tareasExistentes && window.tareasExistentes[actividadId]) {
                    // Si no hay tareas visibles en la interfaz, pero sí en los datos PHP, 
                    // debemos crearlas dinámicamente
                    console.log(`No hay tareas visibles en la interfaz para actividad ${actividadId}. Verificando datos PHP...`);
                    
                    const tareasDePHP = window.tareasExistentes[actividadId];
                    console.log(`Encontradas ${tareasDePHP.length} tareas en datos PHP para actividad ${actividadId}`);
                    
                    // Eliminar mensaje de no tareas si existe
                    const mensajeNoTareas = contenedor.querySelector('.no-tasks-message');
                    if (mensajeNoTareas) {
                        mensajeNoTareas.remove();
                    }
                    
                    // Crear las tareas en la interfaz
                    tareasDePHP.forEach((tareaPHP, index) => {
                        const nuevoIndice = index + 1;
                        console.log(`Creando tarea ${nuevoIndice} para actividad ${actividadId} desde datos PHP`);
                        
                        // Crear elemento de tarea similar a agregarTarea()
                        const nuevaTarea = document.createElement('div');
                        nuevaTarea.className = 'task-item';
                        nuevaTarea.id = `task-${actividadId}-${nuevoIndice}`;
                        
                        // Construir el HTML de la tarea con los datos de PHP
                        nuevaTarea.innerHTML = `
                            <div class="task-header">
                                <div style="display: flex; align-items: center;">
                                    <div class="task-number">${nuevoIndice}</div>
                                    <span>Tarea</span>
                                </div>
                                <div class="task-actions">
                                    <button type="button" class="task-action-btn remove-btn" 
                                            onclick="window.TaskManager.eliminarTarea(${actividadId}, ${nuevoIndice})">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="task-content">
                                <textarea class="task-description" 
                                         name="tareas[${actividadId}][${nuevoIndice}][descripcion]" 
                                         placeholder="Describa la tarea realizada..." required>${tareaPHP.descripcion}</textarea>
                            </div>
                            <div class="task-evidence">
                                <div class="evidence-label">Evidencia:</div>
                                <input type="text" class="evidence-input" 
                                       name="tareas[${actividadId}][${nuevoIndice}][evidencia]" 
                                       placeholder="URL o referencia de la evidencia (opcional)" 
                                       value="${tareaPHP.evidencia || ''}">
                            </div>
                            <div class="task-hint">Puede incluir enlaces a documentos, sitios web, o referencias físicas.</div>
                        `;
                        
                        // Agregar la tarea al contenedor
                        contenedor.appendChild(nuevaTarea);
                    });
                }
            });
        }
    };

    // Inicialización de eventos
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar fechas por defecto
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');
        
        // Obtener fecha actual y calcular primer y último día del mes
        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        fechaInicio.valueAsDate = primerDiaMes;
        
        const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
        fechaFin.valueAsDate = hoy < ultimoDiaMes ? hoy : ultimoDiaMes;
        
        // Formatear valor de cobro
        const valorCobroInput = document.getElementById('valor_cobro');
        valorCobroInput.addEventListener('input', function() {
            let valor = this.value.replace(/\D/g, '');
            
            if (!valor) {
                this.value = '';
                return;
            }
            
            valor = parseInt(valor).toString();
            valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            
            this.value = '$' + valor;
        });

        // Validación de formulario
        // Validación de formulario
        const form = document.getElementById('actividadesForm');
        if (form) {
            const btnGenerarInforme = document.getElementById('btnGenerarInforme');
            
            form.addEventListener('submit', function(event) {
                if (event.submitter === btnGenerarInforme || event.submitter.name === 'guardar_y_generar') {
                    let formValido = true;
                    let mensaje = '';
        
                    // Verificar tareas
                    document.querySelectorAll('.tasks-container').forEach(tasksContainer => {
                        const tasksItems = tasksContainer.querySelectorAll('.task-item');
                        if (tasksItems.length === 0) {
                            formValido = false;
                            mensaje = 'Cada actividad debe tener al menos una tarea registrada.';
                        } else {
                            // Verificar que las tareas tengan descripción
                            tasksItems.forEach(taskItem => {
                                const textarea = taskItem.querySelector('textarea');
                                if (textarea && !textarea.value.trim()) {
                                    formValido = false;
                                    mensaje = 'Todas las tareas deben tener una descripción.';
                                    textarea.classList.add('error');
                                }
                            });
                        }
                    });
        
                    // Verificar fechas
                    const fechaInicio = document.getElementById('fecha_inicio').value;
                    const fechaFin = document.getElementById('fecha_fin').value;
                    
                    if (!fechaInicio || !fechaFin) {
                        formValido = false;
                        mensaje = 'Debe ingresar las fechas del periodo para generar el informe.';
                    }
        
                    if (!formValido) {
                        event.preventDefault();
                        alert(mensaje);
                    }
                }
            });
        }

        // Cargar tareas existentes
        window.TaskManager.cargarTareasExistentes();
    });
    </script>
    
    <!-- Script para pasar datos de PHP a JavaScript -->
    <script>
        // Pasar datos de PHP a JavaScript
        window.tareasExistentes = <?php 
            $tareas_js = [];
            foreach ($tareas_por_actividad as $actividad_id => $tareas_actividad) {
                $tareas_js[$actividad_id] = [];
                foreach ($tareas_actividad as $tarea) {
                    $evidencia = isset($evidencias[$tarea['id']]) ? $evidencias[$tarea['id']]['url_evidencia'] : '';
                    $tareas_js[$actividad_id][] = [
                        'id' => $tarea['id'],
                        'descripcion' => htmlspecialchars_decode($tarea['descripcion_tarea']),
                        'evidencia' => htmlspecialchars_decode($evidencia)
                    ];
                }
            }
            echo json_encode($tareas_js);
        ?>;
        
        console.log('Tareas existentes cargadas:', window.tareasExistentes);
    </script>
</body>
</html>
