<?php
/**
 * Configuración de Email - Sistema de PQRSs
 * @author crisgacovi
 * @date 2025-07-21
 */

// Configuración del remitente
define('EMAIL_FROM_NAME', 'Auditorias Escamilla');
define('EMAIL_FROM_ADDRESS', 'pqrs@auditoriasescamilla.com');
define('AUDITORIA_EMAIL', 'seguimientopqrs@auditoriasescamilla.com');

// Configuración del servidor SMTP (recomendado)
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // Puerto común para TLS
define('SMTP_USERNAME', 'pqrs@auditoriasescamilla.com');
define('SMTP_PASSWORD', 'Escamilla25&#');
define('SMTP_SECURE', 'ssl'); // tls o ssl

// Nuevas configuraciones para manejo de adjuntos y timeouts
define('SMTP_TIMEOUT', 30); // timeout en segundos
define('SMTP_MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB en bytes
define('SMTP_DEBUG', false); // activar solo para diagnóstico