<?php
/**
 * Smoke test da Fast OS.
 *
 * Uso:
 *   php tests/fast_os_smoke.php
 */

require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/middleware/auth.php';
require APP_PATH . '/models/Client.php';
require APP_PATH . '/models/Device.php';
require APP_PATH . '/models/ServiceOrder.php';

function assert_true(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('Falhou: ' . $label);
    }
    echo "ok\t" . $label . "\n";
}

$pdo = db();
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/005_fast_os.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/005_fast_os.sql'));
assert_true(true, 'migration fast os idempotente');

$user = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetch()
    ?: $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetch();
assert_true((bool) $user, 'usuario existente');
$_SESSION['user_id'] = (int) $user['id'];
assert_true(order_can('fast_create'), 'permissao para criar Fast OS');

$csrfScript = 'require "app/config/config.php"; require APP_PATH . "/helpers/functions.php"; $_SESSION["csrf_token"] = "known"; $_POST["_csrf"] = "bad"; verify_csrf();';
$csrfOutput = [];
exec(PHP_BINARY . ' -r ' . escapeshellarg($csrfScript), $csrfOutput);
assert_true(str_contains(implode("\n", $csrfOutput), 'token inválido'), 'CSRF invalido bloqueado');

$permissionScript = <<<'PHP'
require "app/config/config.php";
require APP_PATH . "/config/database.php";
require APP_PATH . "/helpers/functions.php";
require APP_PATH . "/middleware/auth.php";
$pdo = db();
$pdo->beginTransaction();
$stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES ('Usuario Fast OS', 'usuario-fast-os@example.com', ?, 'usuario') RETURNING id");
$stmt->execute([password_hash('smoke', PASSWORD_DEFAULT)]);
$_SESSION['user_id'] = (int) $stmt->fetchColumn();
echo order_can('fast_create') ? 'bad' : 'ok';
$pdo->rollBack();
PHP;
$permissionOutput = [];
exec(PHP_BINARY . ' -r ' . escapeshellarg($permissionScript), $permissionOutput);
assert_true(trim(implode('', $permissionOutput)) === 'ok', 'usuario comum nao cria Fast OS');

$pdo->beginTransaction();
try {
    $fastId = order_fast_create([
        'quick_customer_name' => 'Cliente Fast Smoke',
        'quick_customer_phone' => '(11) 98888-7777',
        'quick_device_name' => 'Notebook Dell Smoke',
        'quick_brand' => 'Dell',
        'quick_model' => 'Inspiron Smoke',
        'reported_issue' => 'Nao liga no balcao',
        'quick_service_description' => 'Diagnostico',
        'estimated_value' => 150,
        'quick_accessories' => 'Fonte',
        'quick_password' => '1234',
        'quick_notes' => 'Observacao de smoke',
        'quick_due_date' => date('Y-m-d'),
        'status' => 'aberta',
        'priority' => 'normal',
    ]);
    assert_true($fastId > 0, 'cria Fast OS com campos minimos e opcionais');

    $fast = order_find($fastId);
    assert_true((bool) $fast, 'localiza Fast OS criada');
    assert_true((bool) $fast['is_fast_order'], 'marca interna Fast OS');
    assert_true($fast['client_id'] === null, 'Fast OS sem cliente cadastrado');
    assert_true($fast['device_id'] === null, 'Fast OS sem equipamento cadastrado');
    assert_true($fast['quick_customer_phone'] === '11988887777', 'telefone normalizado');
    assert_true((float) $fast['total_price'] === 150.0, 'valor estimado salvo sem financeiro automatico');

    assert_true(count(orders_all(['q' => 'Cliente Fast Smoke'])) >= 1, 'busca encontra nome digitado');
    assert_true(count(orders_all(['q' => 'Notebook Dell Smoke'])) >= 1, 'busca encontra equipamento digitado');
    assert_true(count(orders_all(['fast' => '1'])) >= 1, 'filtro Fast OS retorna resultado');

    order_set_status($fastId, 'diagnostico');
    $fast = order_find($fastId);
    assert_true($fast['status'] === 'diagnostico', 'atualiza status de Fast OS');

    require APP_PATH . '/controllers/order_pdf.php';
    $pdfHtml = render_html('orders/pdf', [
        'order' => $fast,
        'client' => null,
        'device' => null,
    ]);
    assert_true(str_contains($pdfHtml, 'FAST OS'), 'PDF identifica Fast OS');
    assert_true(str_contains($pdfHtml, 'Dados rápidos'), 'PDF inclui dados rapidos');

    $clientId = client_create([
        'name' => 'Cliente Fast Smoke',
        'phone' => '11988887777',
        'email' => null,
        'document' => null,
        'address' => null,
        'notes' => 'Smoke',
    ]);
    assert_true(count(order_fast_client_matches('Cliente Fast Smoke', '11988887777')) >= 1, 'prevencao de duplicidade encontra cliente');
    $deviceId = device_create([
        'client_id' => $clientId,
        'category' => 'Outro',
        'brand' => 'Dell',
        'model' => 'Inspiron Smoke',
        'model_code' => null,
        'serial_number' => null,
        'year' => null,
        'processor' => null,
        'memory' => null,
        'storage' => null,
        'condition_notes' => 'Smoke',
    ]);

    $linked = order_fast_convert($fastId, [
        'client_id' => $clientId,
        'device_id' => $deviceId,
    ]);
    assert_true($linked['client_id'] === $clientId && $linked['device_id'] === $deviceId, 'converte vinculando cliente e equipamento existentes');
    $converted = order_find($fastId);
    assert_true((int) $converted['client_id'] === $clientId, 'OS vinculada ao cliente apos conversao');
    assert_true((int) $converted['device_id'] === $deviceId, 'OS vinculada ao equipamento apos conversao');
    assert_true(!empty($converted['fast_order_converted_at']), 'registra data de conversao');
    assert_true($converted['quick_customer_name'] === 'Cliente Fast Smoke', 'preserva dados rapidos apos conversao');

    $pdo->rollBack();
    echo "ok\trollback fast os aplicado\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

echo "ok\tfast os smoke concluido\n";
