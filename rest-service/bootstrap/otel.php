<?php

use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Logs\LoggerProviderFactory;
use OpenTelemetry\API\Globals;

require __DIR__ . '/../vendor/autoload.php';

putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:4317');
putenv('OTEL_LOGS_EXPORTER=otlp');
putenv('OTEL_RESOURCE_ATTRIBUTES=service.name=dash-invoice');

$loggerProvider = (new LoggerProviderFactory())->create();
