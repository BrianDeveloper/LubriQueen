<?php
// Prevenir acceso directo al archivo
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Acceso no permitido');
}

// Cargar variables de entorno
require_once __DIR__ . '/env_loader.php';
EnvLoader::load(__DIR__ . '/../.env');

return [
    // Configuración de la API de HuggingFace
    'huggingface_token' => getenv('HUGGINGFACE_API_TOKEN'),
    
    // Configuración de seguridad
    'allowed_origins' => explode(',', getenv('ALLOWED_ORIGINS')),
    
    // Límites de uso
    'max_requests_per_minute' => (int)getenv('MAX_REQUESTS_PER_MINUTE'),
    'max_message_length' => 500,
    
    // Timeouts
    'api_timeout' => (int)getenv('API_TIMEOUT'),
    'max_retries' => (int)getenv('MAX_RETRIES')
];
