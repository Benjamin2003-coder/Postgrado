<?php
// chatbot.php - Chatbot para UNEFA Postgrado
session_start();

// Incluir conexión
require_once __DIR__ . '/../config/conexion.php';

// Verificar conexión PDO
if (!isset($pdo) || $pdo === null) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'respuesta' => '⚠️ Error de conexión con la base de datos. Por favor, contacta al administrador.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Función para limpiar texto
 */
function cleanText($text) {
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return mb_strtolower(trim($text), 'UTF-8');
}

/**
 * Respuestas para saludos
 */
function responderSaludo($pregunta) {
    $pregunta_lower = cleanText($pregunta);
    
    $saludos = [
        'hola' => [
            'respuestas' => [
                "¡Hola! 👋 Soy Excelencia Educativa, tu asistente virtual de UNEFA Postgrado. ¿En qué puedo ayudarte hoy?",
                "¡Hola! 😊 Encantado de saludarte. ¿Necesitas información sobre maestrías, inscripciones o pagos?",
                "¡Hola! ✨ Bienvenido al sistema de información de postgrado. ¿Cómo puedo asistirte?"
            ]
        ],
        'buenos dias' => [
            'respuestas' => [
                "¡Buenos días! ☀️ Espero que tengas un excelente día. ¿En qué puedo colaborarte?",
                "¡Buenos días! 🌅 ¿Qué información necesitas sobre nuestros programas de postgrado?"
            ]
        ],
        'gracias' => [
            'respuestas' => [
                "¡Con gusto! 😊 ¿Necesitas algo más?",
                "¡Un placer ayudarte! 🌟 No dudes en preguntar si tienes más dudas."
            ]
        ],
        'quien eres' => [
            'respuestas' => [
                "Soy <strong>Excelencia Educativa</strong> 🤖, tu asistente virtual de la Dirección de Investigación y Postgrado de UNEFA Núcleo Nueva Esparta."
            ]
        ]
    ];
    
    foreach ($saludos as $clave => $datos) {
        if (strpos($pregunta_lower, $clave) !== false) {
            $respuestas = $datos['respuestas'];
            return $respuestas[array_rand($respuestas)];
        }
    }
    
    return null;
}

/**
 * Buscar información de maestrías
 */
function buscarMaestrias($pdo) {
    try {
        // Usar la tabla programas_posgrado si existe (filtrar por tipo 'maestria')
        $sql = "SELECT * FROM programas_posgrado WHERE tipo_programa = 'maestria' AND estado = 'activo' ORDER BY nombre";
        $stmt = $pdo->query($sql);
        $maestrias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($maestrias)) {
            return "📚 Actualmente no hay programas de maestría disponibles. Pronto estaremos ofreciendo nuevos programas.";
        }
        
        $respuesta = "🎓 <strong>Programas de Maestría Disponibles:</strong><br><br>";
        
        foreach ($maestrias as $m) {
            $respuesta .= "<strong>" . htmlspecialchars($m['nombre']) . "</strong><br>";
            if (!empty($m['descripcion'])) {
                $respuesta .= "• " . htmlspecialchars($m['descripcion']) . "<br>";
            }
            $respuesta .= "• Duración: " . ($m['duracion_meses'] ?? 'N/A') . " meses<br>";
            $respuesta .= "• Créditos totales: " . ($m['total_creditos'] ?? 'N/A') . "<br>";
            $respuesta .= "<br>";
        }
        
        return $respuesta;
        
    } catch (Exception $e) {
        error_log("Error en buscarMaestrias: " . $e->getMessage());
        return null;
    }
}

/**
 * Buscar información de materias
 */
function buscarMaterias($pdo) {
    try {
        $sql = "SELECT m.*, p.nombre as programa_nombre 
            FROM materias m
            LEFT JOIN programas_posgrado p ON m.id_programa = p.id_programa
            WHERE m.estado = 'activa'
            ORDER BY m.nombre LIMIT 10";
        $stmt = $pdo->query($sql);
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($materias)) {
            return "📖 No hay materias disponibles en este momento.";
        }
        
        $respuesta = "📚 <strong>Materias Disponibles:</strong><br><br>";
        
        foreach ($materias as $mat) {
            $respuesta .= "<strong>" . htmlspecialchars($mat['nombre']) . "</strong> (" . $mat['unidades_credito'] . " créditos)<br>";
            if (!empty($mat['descripcion'])) {
                $respuesta .= "• " . htmlspecialchars($mat['descripcion']) . "<br>";
            }
            if (!empty($mat['profesor'])) {
                $respuesta .= "• Profesor: " . htmlspecialchars($mat['profesor']) . "<br>";
            }
            $respuesta .= "<br>";
        }
        
        return $respuesta;
        
    } catch (Exception $e) {
        error_log("Error en buscarMaterias: " . $e->getMessage());
        return null;
    }
}

/**
 * Buscar información de pagos
 */
function buscarPagos($pdo) {
    try {
        $sql = "SELECT * FROM configuracion_financiera 
                WHERE concepto = 'valor_credito' AND activo = 1 
                ORDER BY fecha_efectiva DESC LIMIT 1";
        $stmt = $pdo->query($sql);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $respuesta = "💰 <strong>Información de Pagos:</strong><br><br>";
        
        if ($config) {
            $respuesta .= "• Valor por crédito: <strong>Bs. " . number_format($config['valor'], 2) . "</strong><br>";
            $respuesta .= "• Vigente desde: " . date('d/m/Y', strtotime($config['fecha_efectiva'])) . "<br><br>";
        }
        
        $respuesta .= "📌 <strong>Métodos de pago:</strong><br>";
        $respuesta .= "• Pago Móvil<br>";
        $respuesta .= "• Transferencia Bancaria<br>";
        $respuesta .= "• Efectivo (en taquilla)<br>";
        
        return $respuesta;
        
    } catch (Exception $e) {
        error_log("Error en buscarPagos: " . $e->getMessage());
        return null;
    }
}

/**
 * Buscar horarios
 */
function buscarHorarios() {
    return "🕒 <strong>Horarios de Atención:</strong><br><br>
            • Lunes a Viernes: 8:00 AM - 4:00 PM<br>
            • Sábados: 8:00 AM - 12:00 PM<br>
            • Domingos: Cerrado<br><br>
            📞 <em>Para consultas: (0295) 123.45.67</em>";
}

/**
 * Buscar contacto
 */
function buscarContacto() {
    return "📍 <strong>Información de Contacto:</strong><br><br>
            • Dirección: Vereda Sur, Av. Jóvito Villalba, Sector El Piache<br>
            • Juan Griego, Municipio Marcano, Nueva Esparta<br><br>
            📞 Teléfonos: (0295) 123.45.67 / 123.45.68<br>
            📧 Email: postgrado.ne@unefa.edu.ve";
}

/**
 * Buscar información de inscripción
 */
function buscarInscripcion() {
    return "📝 <strong>Proceso de Inscripción:</strong><br><br>
            1. Debes estar registrado en el sistema<br>
            2. Selecciona el período académico<br>
            3. Elige las materias que deseas cursar<br>
            4. El sistema calcula el pago basado en créditos<br>
            5. Realiza el pago por Pago Móvil o Transferencia<br>
            6. Confirma tu inscripción<br><br>
            🔗 <a href='register.php' style='color: #8B1E3F; font-weight: 600;'>Regístrate aquí</a> si aún no tienes cuenta.";
}

// Procesar pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $pregunta = trim($input['pregunta'] ?? '');
        
        if (empty($pregunta)) {
            throw new Exception('Pregunta vacía');
        }
        
        // Registrar conversación (opcional - si existe la tabla)
        try {
            $sesion_id = session_id();
            $id_usuario = $_SESSION['usuario_id'] ?? 'invitado';
            
            $sql_conv = "INSERT INTO chatbot_conversaciones (sesion_id, id_usuario, pregunta_usuario, fecha_conversacion) 
                         VALUES (?, ?, ?, NOW())";
            $stmt_conv = $pdo->prepare($sql_conv);
            $stmt_conv->execute([$sesion_id, $id_usuario, $pregunta]);
        } catch (Exception $e) {
            // Si no existe la tabla, continuamos sin registrar
            error_log("No se pudo registrar conversación: " . $e->getMessage());
        }
        
        // 1. Verificar si es saludo
        $respuesta = responderSaludo($pregunta);
        
        // 2. Si no es saludo, buscar por tema
        if (!$respuesta) {
            $pregunta_lower = cleanText($pregunta);
            
            if (preg_match('/(maestria|maestrías|posgrado|postgrado|doctorado)/i', $pregunta_lower)) {
                $respuesta = buscarMaestrias($pdo);
            } elseif (preg_match('/(materia|materias|asignatura|curso|clase)/i', $pregunta_lower)) {
                $respuesta = buscarMaterias($pdo);
            } elseif (preg_match('/(pago|pagar|costo|precio|valor|credito|creditos|cuota)/i', $pregunta_lower)) {
                $respuesta = buscarPagos($pdo);
            } elseif (preg_match('/(horario|hora|atencion|abierto|cerrado)/i', $pregunta_lower)) {
                $respuesta = buscarHorarios();
            } elseif (preg_match('/(contacto|direccion|telefono|ubicacion|mapa|email|correo)/i', $pregunta_lower)) {
                $respuesta = buscarContacto();
            } elseif (preg_match('/(inscribir|inscripcion|registrar|registro|matricular)/i', $pregunta_lower)) {
                $respuesta = buscarInscripcion();
            }
        }
        
        // 3. Si no hay respuesta, mensaje genérico
        if (!$respuesta) {
            // Guardar pregunta no respondida (si existe la tabla)
            try {
                $sesion_id = session_id();
                $id_usuario = $_SESSION['usuario_id'] ?? 'invitado';
                $sql_pend = "INSERT INTO chatbot_preguntas_pendientes (sesion_id, id_usuario, pregunta, fecha_pregunta) 
                             VALUES (?, ?, ?, NOW())";
                $stmt_pend = $pdo->prepare($sql_pend);
                $stmt_pend->execute([$sesion_id, $id_usuario, $pregunta]);
            } catch (Exception $e) {
                // Ignorar si no existe la tabla
            }
            
            $respuesta = "🤔 <strong>Excelencia Educativa:</strong> No estoy seguro de entender tu pregunta.<br><br>
                         🔍 <strong>Puedo ayudarte con:</strong><br>
                         • 📚 Programas de maestría<br>
                         • 💰 Información de pagos y créditos<br>
                         • 📝 Proceso de inscripción<br>
                         • 🕒 Horarios de atención<br>
                         • 📞 Contacto y ubicación<br><br>
                         <em>¿Podrías reformular tu pregunta?</em>";
        }
        
        // Actualizar conversación con respuesta (si existe la tabla)
        try {
            if (isset($id_conversacion)) {
                $sql_update = "UPDATE chatbot_conversaciones SET respuesta_bot = ? WHERE id_conversacion = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$respuesta, $id_conversacion]);
            }
        } catch (Exception $e) {
            // Ignorar
        }
        
        echo json_encode([
            'success' => true,
            'respuesta' => $respuesta
        ]);
        
    } catch (Exception $e) {
        error_log("Error en chatbot: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'respuesta' => '⚠️ Lo siento, hubo un error técnico. Por favor, intenta nuevamente.'
        ]);
    }
    
    exit();
}
?>