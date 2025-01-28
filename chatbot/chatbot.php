<?php
header('Content-Type: application/json');

// Habilitar todos los errores para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log de errores personalizado
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - Error: " . $message . "\n", 3, __DIR__ . '/chatbot_error.log');
}

class ChatBot {
    private $API_URL = "https://api-inference.huggingface.co/models/tiiuae/falcon-7b-instruct";
    private $API_TOKEN;
    private $MAX_MESSAGE_LENGTH = 500;
    private $MAX_RETRIES = 2;
    private $TIMEOUT = 15;

    public function __construct() {
        try {
            if (!file_exists(__DIR__ . '/../.env')) {
                throw new Exception('Archivo .env no encontrado');
            }
            
            require_once __DIR__ . '/../includes/env_loader.php';
            EnvLoader::load(__DIR__ . '/../.env');
            
            $this->API_TOKEN = getenv('HUGGINGFACE_API_TOKEN');
            
            if (empty($this->API_TOKEN)) {
                throw new Exception('Token de API no encontrado en .env');
            }
        } catch (Exception $e) {
            logError('Error en constructor: ' . $e->getMessage());
            throw $e;
        }
    }

    public function processMessage($message) {
        try {
            $message = $this->sanitizeInput($message);
            
            if (!$this->isValidInput($message)) {
                return $this->getLocalResponse('default');
            }

            $headers = [
                'Authorization: Bearer ' . $this->API_TOKEN,
                'Content-Type: application/json'
            ];

            $prompt = "Eres un experto en lubricantes automotrices. Da respuestas completas y bien organizadas. " .
                     "Si la respuesta incluye una lista, enumera cada elemento en una línea separada precedida por un número y punto. " .
                     "Pregunta del cliente: " . $message . "\n\n" .
                     "Respuesta (sé específico y organizado, sin usar referencias bibliográficas):";

            $data = json_encode([
                'inputs' => $prompt,
                'parameters' => [
                    'max_new_tokens' => 250,
                    'temperature' => 0.3,
                    'top_p' => 0.9,
                    'do_sample' => true,
                    'return_full_text' => false,
                    'repetition_penalty' => 1.2
                ]
            ]);

            $ch = curl_init($this->API_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $this->TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                logError('Curl error: ' . curl_error($ch));
                curl_close($ch);
                return $this->getLocalResponse($message);
            }

            curl_close($ch);
            
            if ($response && $httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result[0]['generated_text'])) {
                    $aiResponse = trim($result[0]['generated_text']);
                    // Remove any duplicate sentences that might appear in the response
                    $sentences = array_unique(array_map('trim', preg_split('/[.!?]+(?=\s|$)/', $aiResponse)));
                    $aiResponse = implode('. ', array_filter($sentences)) . '.';
                    if (!empty($aiResponse)) {
                        return $this->sanitizeOutput($aiResponse);
                    }
                }
            }

            return $this->getLocalResponse($message);
        } catch (Exception $e) {
            logError('Error en processMessage: ' . $e->getMessage());
            return $this->getLocalResponse('error');
        }
    }

    private function sanitizeInput($input) {
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return trim($input);
    }

    private function formatResponse($text) {
        // Detectar si hay una lista numerada (1., 2., etc.)
        if (preg_match('/\d+\./', $text)) {
            $lines = explode("\n", $text);
            $formattedLines = [];
            $inList = false;
            
            foreach ($lines as $line) {
                // Si es un elemento de lista numerada
                if (preg_match('/^\s*\d+\./', $line)) {
                    $line = preg_replace('/^\s*(\d+\.)/', '  - ', $line);
                    $formattedLines[] = $line . "\n";
                    $inList = true;
                } 
                // Si es un elemento con guión
                elseif (preg_match('/^\s*-/', $line)) {
                    $line = '  ' . $line;
                    $formattedLines[] = $line . "\n";
                    $inList = true;
                }
                // Si es texto normal
                else {
                    if ($inList) {
                        $formattedLines[] = "\n" . $line;
                        $inList = false;
                    } else {
                        $formattedLines[] = $line;
                    }
                }
            }
            return trim(implode("", $formattedLines));
        }
        return $text;
    }

    private function sanitizeOutput($output) {
        // Eliminar números de referencia [1], [2], etc.
        $output = preg_replace('/\[\d+\]/', '', $output);
        
        // Eliminar otros formatos de citas como (1), (2), etc.
        $output = preg_replace('/\(\d+\)/', '', $output);
        
        // Eliminar caracteres HTML y scripts
        $output = strip_tags($output);
        
        // Formatear listas y elementos
        $output = $this->formatResponse($output);
        
        // Asegurarse de que la respuesta termine en un punto si no es una lista
        if (!preg_match('/\n\s*-/', $output) && !in_array(substr($output, -1), ['.', '!', '?'])) {
            $output .= '.';
        }
        
        return trim($output);
    }

    private function isValidInput($input) {
        if (empty($input) || strlen($input) > $this->MAX_MESSAGE_LENGTH) {
            return false;
        }
        
        if (!preg_match('/^[\p{L}\p{N}\s\p{P}]+$/u', $input)) {
            return false;
        }
        
        return true;
    }

    private function getLocalResponse($message) {
        $message = strtolower($message);
        
        // Respuestas específicas para tipos de motores
        if (strpos($message, 'diesel') !== false) {
            return "Para motores diesel recomendamos aceites específicos como:\n" .
                   "1. 15W-40 para uso general\n" .
                   "2. 5W-40 para climas fríos\n" .
                   "3. Aceite sintético con especificación API CJ-4 o superior\n" .
                   "Estos aceites están diseñados para proteger contra el desgaste y la formación de hollín.";
        }
        
        // Respuestas para tipos de aceite
        if (strpos($message, 'sintético') !== false) {
            return "Los aceites sintéticos son la mejor opción por su:\n" .
                   "- Mayor protección del motor\n" .
                   "- Mejor rendimiento en temperaturas extremas\n" .
                   "- Intervalos de cambio más largos (7,500-10,000 km)";
        }
        
        if (strpos($message, 'mineral') !== false) {
            return "Los aceites minerales son:\n" .
                   "- Económicos y confiables\n" .
                   "- Ideales para motores convencionales\n" .
                   "- Requieren cambios cada 5,000 km";
        }
        
        // Respuestas para viscosidad
        if (strpos($message, 'viscosidad') !== false || strpos($message, '5w') !== false || strpos($message, '10w') !== false) {
            return "La viscosidad indica el espesor del aceite:\n" .
                   "- 5W-30: Ideal para autos modernos y climas variados\n" .
                   "- 10W-40: Bueno para motores con kilometraje alto\n" .
                   "- 15W-40: Excelente para motores diesel y clima cálido";
        }
        
        // Respuestas para intervalos de cambio
        if (strpos($message, 'cambio') !== false && (strpos($message, 'aceite') !== false || strpos($message, 'cambiar') !== false)) {
            return "Intervalos recomendados de cambio:\n" .
                   "- Aceite mineral: 5,000 km\n" .
                   "- Semi-sintético: 7,500 km\n" .
                   "- Sintético: 10,000 km\n" .
                   "Nota: Estos intervalos pueden variar según el fabricante.";
        }
        
        // Respuestas para preguntas sobre recomendaciones
        if (strpos($message, 'recomien') !== false || strpos($message, 'mejor') !== false || strpos($message, 'cual') !== false) {
            if (strpos($message, 'motor') !== false) {
                return "La recomendación depende del tipo y edad del motor:\n" .
                       "1. Motores nuevos: Sintético 5W-30\n" .
                       "2. Motores +100,000 km: Semi-sintético 10W-40\n" .
                       "3. Motores antiguos: Mineral 20W-50";
            }
        }

        // Respuesta por defecto
        return "Puedo ayudarte con información específica sobre:\n" .
               "1. Tipos de aceite (mineral, sintético)\n" .
               "2. Viscosidades recomendadas\n" .
               "3. Intervalos de cambio\n" .
               "4. Recomendaciones según tu motor\n" .
               "¿Qué te gustaría saber?";
    }
}

// Procesar la solicitud
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['message'])) {
        throw new Exception('Mensaje vacío');
    }

    $chatbot = new ChatBot();
    $response = $chatbot->processMessage($data['message']);
    echo json_encode(['response' => $response]);
} catch (Exception $e) {
    logError('Error principal: ' . $e->getMessage());
    echo json_encode(['error' => 'Error en el procesamiento de la solicitud: ' . $e->getMessage()]);
}
