<?php
/**
 * Smoke test de Serviços Digitais.
 *
 * Uso:
 *   php tests/digital_services_smoke.php
 */

require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/middleware/auth.php';
require APP_PATH . '/models/Client.php';
require APP_PATH . '/models/Trello.php';
require APP_PATH . '/models/FinancialEntry.php';
require APP_PATH . '/models/DigitalService.php';
require APP_PATH . '/controllers/digital_services.php';

function assert_true(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('Falhou: ' . $label);
    }
    echo "ok\t" . $label . "\n";
}

$pdo = db();
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/007_digital_services.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/007_digital_services.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/008_digital_services_phase2.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/008_digital_services_phase2.sql'));
assert_true(true, 'migration servicos digitais idempotente');

$admin = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetch()
    ?: $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetch();
assert_true((bool) $admin, 'usuario admin existente');
$_SESSION['user_id'] = (int) $admin['id'];
assert_true(digital_service_can('create'), 'admin pode criar servico digital');

$csrfScript = 'require "app/config/config.php"; require APP_PATH . "/helpers/functions.php"; $_SESSION["csrf_token"] = "known"; $_POST["_csrf"] = "bad"; verify_csrf();';
$csrfOutput = [];
exec(PHP_BINARY . ' -r ' . escapeshellarg($csrfScript), $csrfOutput);
assert_true(str_contains(implode("\n", $csrfOutput), 'token inválido'), 'CSRF invalido bloqueado');

$permissionScript = <<<'PHP'
require "app/config/config.php";
require APP_PATH . "/config/database.php";
require APP_PATH . "/helpers/functions.php";
require APP_PATH . "/middleware/auth.php";
require APP_PATH . "/models/DigitalService.php";
$pdo = db();
$stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES ('Usuario Digital', 'usuario-digital@example.com', ?, 'usuario') RETURNING id");
$stmt->execute([password_hash('secret', PASSWORD_DEFAULT)]);
$_SESSION['user_id'] = (int) $stmt->fetchColumn();
echo digital_service_can('create') ? 'bad' : 'ok';
$pdo->prepare("DELETE FROM users WHERE email = 'usuario-digital@example.com'")->execute();
PHP;
$permissionOutput = [];
exec(PHP_BINARY . ' -r ' . escapeshellarg($permissionScript), $permissionOutput);
assert_true(trim(implode('', $permissionOutput)) === 'ok', 'usuario comum nao cria servico digital');

$pdo->beginTransaction();
$uploadedPath = null;
try {
    $clientId = client_create([
        'name' => 'Cliente Digital Smoke',
        'phone' => '11999999999',
        'email' => 'digital-smoke@example.com',
        'document' => null,
        'address' => null,
        'notes' => null,
    ]);

    $baseData = [
        'client_id' => $clientId,
        'temporary_client_name' => null,
        'project_name' => 'Projeto Digital Smoke',
        'service_type' => 'ecommerce',
        'description' => 'E-commerce para teste smoke',
        'status' => 'em_desenvolvimento',
        'priority' => 'alta',
        'progress' => 35,
        'progress_mode' => 'manual',
        'assigned_user_id' => (int) $admin['id'],
        'start_date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+10 days')),
        'delivered_at' => null,
        'next_review_at' => date('Y-m-d', strtotime('+5 days')),
        'total_value' => 5000,
        'down_payment' => 1000,
        'amount_received' => 1500,
        'estimated_cost' => 800,
        'payment_method' => 'Pix',
        'installments' => 3,
        'recurring_value' => 200,
        'recurring_interval' => 'mensal',
        'stack' => 'PHP, PostgreSQL',
        'language' => 'PHP',
        'database_name' => 'PostgreSQL',
        'framework' => 'Sem framework',
        'server_name' => 'VPS',
        'hosting' => 'Hospedagem própria',
        'domain' => 'example.test',
        'ssl_status' => 'Ativo',
        'repository_url' => 'https://github.com/example/projeto',
        'environment' => 'produção',
        'version' => '1.0.0',
        'site_url' => 'https://example.test',
        'admin_url' => null,
        'staging_url' => null,
        'github_url' => 'https://github.com/example/projeto',
        'figma_url' => null,
        'documentation_url' => null,
        'domain_url' => null,
        'hosting_url' => null,
        'internal_notes' => 'Notas internas',
        'client_notes' => 'Notas cliente',
        'pending_items' => 'Pendência',
        'blockers' => 'Bloqueio',
        'important_decisions' => 'Decisão',
        'created_by' => (int) $admin['id'],
    ];

    assert_true(digital_services_validate($baseData) === [], 'valida dados completos');
    $serviceId = digital_service_create($baseData);
    $service = digital_service_find($serviceId);
    assert_true((bool) $service, 'cria servico digital');
    assert_true($service['client_name'] === 'Cliente Digital Smoke', 'vincula cliente existente');

    $noClientId = digital_service_create(array_replace($baseData, [
        'client_id' => null,
        'temporary_client_name' => 'Lead sem cadastro',
        'project_name' => 'Projeto sem cliente',
    ]));
    assert_true(digital_service_find($noClientId)['temporary_client_name'] === 'Lead sem cadastro', 'cria projeto sem cliente cadastrado');

    digital_service_update_progress($serviceId, 140);
    assert_true((int) digital_service_find($serviceId)['progress'] === 100, 'limita progresso manual em 100');

    digital_service_task_create($serviceId, ['title' => 'Briefing']);
    digital_service_task_create($serviceId, ['title' => 'Publicação']);
    digital_service_update($serviceId, array_replace($baseData, ['progress_mode' => 'checklist']));
    $tasks = digital_service_tasks($serviceId);
    assert_true(count($tasks) === 2, 'adiciona checklist');
    digital_service_task_toggle((int) $tasks[0]['id']);
    assert_true((int) digital_service_find($serviceId)['progress'] === 50, 'calcula progresso por checklist');

    digital_service_link_create($serviceId, ['label' => 'Site', 'url' => 'https://example.test', 'type' => 'site']);
    assert_true(count(digital_service_links($serviceId)) === 1, 'adiciona link');
    digital_service_note_create($serviceId, 'Observação smoke', false);
    assert_true(count(digital_service_notes($serviceId)) === 1, 'adiciona observacao');
    trello_seed_columns();
    $columns = trello_columns_all();
    $cardId = trello_card_create((int) $columns[0]['id'], 'Digital smoke card');
    digital_service_set_kanban_card($serviceId, $cardId);
    assert_true((int) digital_service_find($serviceId)['kanban_card_id'] === $cardId, 'vincula cartao Kanban');

    $templateId = digital_service_template_create(['name' => 'Template Smoke', 'service_type' => 'ecommerce'], ['Setup', 'Deploy']);
    digital_service_apply_custom_template($serviceId, $templateId);
    assert_true(count(digital_service_tasks($serviceId)) >= 4, 'template proprio aplicado');

    $entryId = financial_entry_create([
        'type' => 'entrada',
        'description' => 'Serviço digital smoke',
        'amount' => 3500,
        'category' => 'Serviços Digitais',
        'reference_type' => 'digital_service',
        'reference_id' => $serviceId,
        'entry_date' => date('Y-m-d'),
    ]);
    assert_true((int) digital_service_financial_entry($serviceId)['id'] === $entryId, 'financeiro real vinculado');

    $billingId = digital_service_billing_create($serviceId, ['description' => 'Cobrança smoke', 'amount' => 1200, 'due_date' => date('Y-m-d')]);
    digital_service_billing_link_financial($billingId, $entryId);
    assert_true(digital_service_billing_all($serviceId)[0]['status'] === 'lancado', 'cobranca interna vinculada ao financeiro');

    $proposalId = digital_service_document_create($serviceId, 'proposta');
    $contractId = digital_service_document_create($serviceId, 'contrato');
    assert_true((bool) digital_service_document_find($proposalId) && (bool) digital_service_document_find($contractId), 'gera proposta e contrato');

    $token = digital_service_portal_token_create($serviceId, 'Portal smoke');
    assert_true((int) digital_service_find_by_portal_token($token)['id'] === $serviceId, 'portal do cliente por token');

    digital_service_automation_log_create($serviceId, 'follow_up', 'Registrar contato futuro.');
    assert_true(count(digital_service_automation_logs($serviceId)) === 1, 'log de automacao local');

    assert_true(count(digital_services_all(['q' => 'Digital Smoke'])) >= 1, 'busca retorna projeto');
    assert_true(count(digital_services_all(['status' => 'em_desenvolvimento'])) >= 1, 'filtro por status retorna projeto');
    assert_true(count(digital_services_all(['balance_due' => true])) >= 1, 'filtro saldo pendente retorna projeto');

    $overdueId = digital_service_create(array_replace($baseData, [
        'project_name' => 'Projeto atrasado',
        'due_date' => date('Y-m-d', strtotime('-2 days')),
    ]));
    assert_true((bool) digital_service_find($overdueId)['is_overdue'], 'identifica prazo vencido');

    digital_service_archive($serviceId, true);
    assert_true((bool) digital_service_find($serviceId)['is_archived'], 'arquiva projeto');
    digital_service_archive($serviceId, false);
    assert_true(!digital_service_find($serviceId)['is_archived'], 'restaura projeto');

    ob_start();
    $filters = ['q' => '', 'service_type' => '', 'status' => '', 'priority' => '', 'sort' => 'recent', 'overdue' => false, 'balance_due' => false];
    $services = digital_services_all([]);
    $summary = digital_service_summary();
    $clients = clients_all();
    $users = digital_service_users_all();
    require APP_PATH . '/views/digital_services/index.php';
    $html = ob_get_clean();
    assert_true(str_contains($html, 'data-digital-card') && str_contains($html, '<details'), 'card expansivel renderizado');

    $tmp = tempnam(sys_get_temp_dir(), 'digital-upload-');
    file_put_contents($tmp, '%PDF-1.4 smoke');
    $_FILES['file'] = ['name' => 'briefing.pdf', 'type' => 'application/pdf', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
    $upload = digital_services_handle_upload($serviceId);
    $uploadedPath = UPLOADS_PATH . '/' . $upload['path'];
    digital_service_file_create($serviceId, $upload);
    assert_true(is_file($uploadedPath) && count(digital_service_files($serviceId)) === 1, 'upload valido salvo fora da pasta publica');

    $tmpExe = tempnam(sys_get_temp_dir(), 'digital-upload-');
    file_put_contents($tmpExe, '#!/bin/sh');
    $_FILES['file'] = ['name' => 'run.php', 'type' => 'application/x-php', 'tmp_name' => $tmpExe, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmpExe)];
    try {
        digital_services_handle_upload($serviceId);
        assert_true(false, 'bloqueio executavel');
    } catch (RuntimeException) {
        assert_true(true, 'bloqueia executavel');
        @unlink($tmpExe);
    }

    $invalid = array_replace($baseData, ['project_name' => '', 'progress' => 101]);
    assert_true(digital_services_validate($invalid) !== [], 'valida backend dados invalidos');

    digital_service_delete($serviceId);
    assert_true(digital_service_find($serviceId) === null, 'exclusao soft oculta projeto');

    $pdo->rollBack();
    if ($uploadedPath && is_file($uploadedPath)) {
        @unlink($uploadedPath);
    }
    echo "ok\trollback servicos digitais aplicado\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($uploadedPath && is_file($uploadedPath)) {
        @unlink($uploadedPath);
    }
    throw $exception;
}

echo "ok\tservicos digitais smoke concluido\n";
