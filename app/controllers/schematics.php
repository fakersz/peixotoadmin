<?php
/** Controller: esquemas elétricos e manuais */

require_once APP_PATH . '/models/Schematic.php';

const SCHEMATIC_MAX_BYTES = 40 * 1024 * 1024; // 40 MB

/** Extensões aceitas para o arquivo do esquema. */
function schematic_allowed_extensions(): array
{
    return [
        'pdf',                                    // esquema elétrico
        'brd', 'bdv', 'bv', 'cad', 'fz', 'tvw',   // boardview
        'asc', 'txt',                             // netlist / notas
        'png', 'jpg', 'jpeg', 'gif', 'webp',      // fotos da placa
        'zip', 'rar', '7z',                       // pacotes
    ];
}

/** Extensão (minúscula) do arquivo anexado, ou null. */
function schematic_file_ext(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return $ext !== '' ? $ext : null;
}

/** Tipos que o navegador consegue exibir direto (inline). */
function schematic_file_inline_type(?string $ext): ?string
{
    return match ($ext) {
        'pdf'          => 'application/pdf',
        'png'          => 'image/png',
        'jpg', 'jpeg'  => 'image/jpeg',
        'gif'          => 'image/gif',
        'webp'         => 'image/webp',
        'txt', 'asc'   => 'text/plain; charset=utf-8',
        default        => null,
    };
}

function schematics_form_data(): array
{
    return [
        'title'           => post_str('title'),
        'device_category' => post_str('device_category'),
        'model_code'      => strtoupper((string) post_str('model_code', '')) ?: null,
        'board_code'      => strtoupper((string) post_str('board_code', '')) ?: null,
        'year'            => post_int('year'),
        'external_link'   => post_str('external_link'),
        'notes'           => post_str('notes'),
    ];
}

/**
 * Valida e move o arquivo enviado (PDF, boardview, imagem, ZIP…) para
 * storage/uploads. Retorna o nome salvo, null se nada foi enviado,
 * ou lança RuntimeException com mensagem amigável.
 */
function schematics_handle_upload(): ?string
{
    $file = $_FILES['pdf'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload do arquivo (código ' . $file['error'] . ').');
    }
    if ($file['size'] > SCHEMATIC_MAX_BYTES) {
        throw new RuntimeException('Arquivo muito grande (máximo 40 MB).');
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, schematic_allowed_extensions(), true)) {
        throw new RuntimeException(
            'Formato não aceito (.' . ($ext ?: '?') . '). Use: ' .
            implode(', ', schematic_allowed_extensions()) . '.'
        );
    }

    // PDFs continuam com verificação de conteúdo; formatos de boardview
    // não têm MIME confiável (chegam como octet-stream).
    if ($ext === 'pdf') {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($mime !== 'application/pdf') {
            throw new RuntimeException('O arquivo enviado não é um PDF válido.');
        }
    }

    $name = 'schematic_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOADS_PATH . '/' . $name)) {
        throw new RuntimeException('Não foi possível salvar o arquivo no servidor.');
    }
    return $name;
}

function schematics_index(): void
{
    redirect('/knowledge/schematics' . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
}

function schematics_catalog(): void
{
    $search   = trim((string) ($_GET['q'] ?? ''));
    $category = trim((string) ($_GET['cat'] ?? ''));

    render('schematics/index', [
        'title'      => 'Esquemas Elétricos',
        'schematics' => schematics_all($search ?: null, $category ?: null),
        'search'     => $search,
        'category'   => $category,
        'counts'     => schematics_category_counts(),
        'schematicsBase' => '/knowledge/schematics',
        'knowledgeMode' => true,
    ]);
}

/**
 * Divide as observações técnicas nas seções do padrão do catálogo:
 * visão geral, defeitos comuns, medições de bancada e observações.
 * Notas fora do padrão caem inteiras em "overview".
 */
function schematic_parse_notes(?string $notes): array
{
    $sections = ['overview' => [], 'defects' => [], 'measurements' => [], 'bench' => []];
    if (!$notes || trim($notes) === '') {
        return $sections;
    }

    $current = 'overview';
    foreach (preg_split('/\r\n|\r|\n/', $notes) as $line) {
        $trim = trim($line);
        if ($trim === '') {
            continue;
        }
        if (preg_match('/^defeitos comuns:?$/iu', $trim)) {
            $current = 'defects';
            continue;
        }
        if (preg_match('/^medi[cç][oõ]es( de bancada)?:?$/iu', $trim)) {
            $current = 'measurements';
            continue;
        }
        if (preg_match('/^observa[cç][oõ]es( de bancada| [uú]teis)?:?$/iu', $trim)) {
            $current = 'bench';
            continue;
        }
        $sections[$current][] = preg_replace('/^[-•]\s*/u', '', $trim);
    }
    return $sections;
}

/**
 * Base de conhecimento: defeito comum → resolução sugerida de bancada.
 * Casa por palavra-chave (primeira que bater vence).
 */
function schematic_resolutions(array $defects): array
{
    $kb = [
        ['flexgate|flex do backlight', 'Sintoma "palco de teatro" na base da tela antes de apagar. Definitivo: trocar o conjunto do display; alternativa: reparo/extensão do flex de backlight com micro-solda.'],
        ['radeongate|6970m|6750m|6770m|d500|d700|gpu amd|gpu aad|r9 m295x|m295x|vega', 'Artefatos/listras ou boot travado. Paliativo: desabilitar a dGPU (gMux/NVRAM) e forçar a integrada. Definitivo: reballing/troca do chip ou da placa MXM — avisar o cliente sobre risco de recidiva.'],
        ['gt 650m|gt 750m|750m|780m|gpu nvidia|gpu mxm|gpu panic|c9560', 'Kernel panic/artefatos de vídeo. No A1286 2010: trocar C9560 (330 µF) resolve a maioria. Nos demais: reflow é paliativo — reballing/troca da GPU ou MXM compatível mais robusta.'],
        ['cd3215|cd3217|cd32|portas usb-c|porta usb-c', 'Medir 5 V e linhas CC na porta afetada. Sem 5 V = trocar o CD32xx daquela porta (BGA). Conferir fusíveis e PPDCIN próximos antes de fechar o diagnóstico.'],
        ['cabo sata', 'Defeito nº 1 destes modelos: trocar o cabo/flex SATA (peça barata) antes de condenar HDD/SSD. Isolar o novo com kapton e evitar dobras agudas.'],
        ['bateria', 'Conferir saúde/ciclos (Informações do Sistema ou coconutBattery). Trocar a bateria; nas coladas, remover com álcool isopropílico/fio de nylon, sem alavancar sobre o trackpad. Calibrar após a troca.'],
        ['teclado borboleta', 'Trocar o teclado completo (vem com o topcase na maioria dos modelos). Limpeza com ar resolve só temporariamente — mecanismo é frágil por projeto.'],
        ['teclado', 'Testar flex e conector na placa; isolar tecla vs. controlador. Trocar teclado/topcase se houver trilha rompida ou oxidação.'],
        ['lp8550|backlight', 'Sem backlight com imagem presente (lanterna na tela): medir fusível de backlight e driver (LP8550 nas Retina — PPVOUT_S0_LCDBKLT). Trocar fusível/driver; conferir o flex da tela antes de energizar de novo.'],
        ['trackpad', 'Testar flex e conector; atualizar o teste com o gesto de clique físico. Trocar o trackpad; nos Force Touch, conferir o parafuso de ajuste e recalibrar.'],
        ['t2|bridgeos', 'Revive/restore do bridgeOS via DFU + Apple Configurator (resolve panics e "não liga" lógicos). Persistindo, analisar logs de panic — falhas de NAND/T2 pedem reparo a nível de componente.'],
        ['ssd proprietário|ssd blade|ssd pcie|ssd base|ssd soldado|ssd', 'Testar com SSD conhecido/adaptador. Nos removíveis: atualizar firmware e migrar dados. Nos soldados: diagnóstico das linhas NAND; recuperação de dados exige a placa viva.'],
        ['nand', 'Tentar revive/restore DFU primeiro. Falhando, medir linhas de alimentação das NAND; reballing/troca com reprogramação (pareamento) — serviço avançado.'],
        ['psu|fonte interna|fonte externa|fonte', 'Medir as saídas (12 V/standby) da fonte. Capacitores estufados: trocar. Sem standby = PSU; com standby e sem power-on = placa.'],
        ['sensor térmico', 'Ao trocar HDD por SSD usar cabo/sensor compatível ou controlar a ventoinha por software (Macs Fan Control) para o cooler não ficar a 100%.'],
        ['adesivo do painel|adesivo', 'Nunca reaproveitar o adesivo: usar kit de fitas específicas do modelo na remontagem. Cortar o antigo com fio/espátula sem flexionar o painel.'],
        ['hdd lento|hdd/fusion|fusion', 'Upgrade para SSD (SATA/PCIe conforme o modelo) + limpeza interna e repaste. Desfazer o Fusion Drive e usar SSD puro quando possível.'],
        ['hdd', 'Testar SMART e superfície; trocar por SSD. Conferir cabo/conector antes de condenar o disco.'],
        ['aquecimento|cooler|térmica|throttling', 'Limpeza interna completa, pasta térmica nova e verificação do cooler (rolamento/ruído). Conferir sensores — sensor ausente/errado também trava a ventoinha no máximo.'],
        ['líquido|oxidação|corrosão', 'NÃO carregar/ligar. Desconectar bateria, lavagem ultrassônica da placa, remover corrosão, trocar componentes atacados e medir as linhas G3H antes de repor energia.'],
        ['tela mini-led|painel 5k|painel 4k|tela 4,5k|painel', 'Painel caro: descartar primeiro flex, conectores e placa (testar com painel de bancada). Proteger o painel durante todo o serviço; troca é do conjunto completo.'],
        ['tela|display|lcd', 'Isolar painel × flex × placa com display de teste. Vincos no flex ou imagem que muda com o ângulo da tampa indicam flex/painel, não placa.'],
        ['touch bar', 'Testar o flex e o conector na placa; trocar o conjunto da Touch Bar. Travamentos só na barra: restaurar bridgeOS antes de trocar hardware.'],
        ['alto-falante', 'Trocar o par de alto-falantes (estalos no 16" 2019 são conhecidos — manter macOS atualizado reduz). Conferir se a distorção não é software antes.'],
        ['magsafe|dc-in', 'Medir PPDCIN e o one-wire (ADAPTER_SENSE). Sem LED no carregador: testar outro carregador original, depois DC-in board/conector, depois SMC.'],
        ['staingate|coating', 'Coating antirreflexo descascando: remoção química completa do coating ou troca do display. Houve programa da Apple (encerrado).'],
        ['ghosting|retenção', 'Retenção de imagem do painel (LG mais afetado): não tem reparo — troca do painel/display.'],
        ['wi-fi|airport', 'Reassentar/trocar a placa AirPort; conferir antenas e conectores U.FL.'],
        ['ram', 'Reassentar e testar os pentes individualmente; limpar slots com álcool isopropílico. Três bipes = RAM não detectada.'],
        ['backplane|cpu tray', 'Testar com configuração mínima (1 pente, GPU básica). Trocar backplane/tray por peça usada — reparo a componente raramente compensa nessas placas.'],
        ['mpx|módulos mpx', 'Reassentar o módulo MPX, limpar contatos e testar em outro slot; conferir travas e alimentação auxiliar.'],
        ['coil whine', 'Ruído de bobina é característico do modelo; só compensa intervir se houver instabilidade junto — do contrário, orientar o cliente.'],
        ['botão de power|botão', 'Testar o acionamento pelos pads de power-on da placa para isolar botão × placa.'],
        ['desempenho fraco', 'Upgrade de SSD + limpeza; alinhar expectativa com o cliente — é limitação de projeto, não defeito.'],
        ['porta thunderbolt|portas', 'Testar todas as portas com carregador e periférico conhecidos; medir 5 V/CC por porta para isolar o controlador da porta afetada.'],
    ];

    $out = [];
    foreach ($defects as $defect) {
        $lower = mb_strtolower($defect);
        $resolution = null;
        foreach ($kb as [$pattern, $text]) {
            if (preg_match('/' . $pattern . '/u', $lower)) {
                $resolution = $text;
                break;
            }
        }
        $out[] = [
            'defect'     => $defect,
            'resolution' => $resolution ?? 'Fluxo padrão: inspeção visual → medições das linhas principais → isolar por subsistema (energia, vídeo, I/O). Registrar a solução encontrada nas observações deste registro.',
        ];
    }
    return $out;
}

function schematics_view(): void
{
    redirect('/knowledge/schematics/view' . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
}

function schematics_show(): void
{
    $schematic = schematic_find(get_int('id', 0));
    if (!$schematic) {
        http_response_code(404);
        render('errors/404', ['title' => 'Esquema não encontrado']);
    }

    $sections = schematic_parse_notes($schematic['notes']);

    render('schematics/view', [
        'title'       => $schematic['title'],
        'schematic'   => $schematic,
        'sections'    => $sections,
        'resolutions' => schematic_resolutions($sections['defects']),
        'schematicsBase' => '/knowledge/schematics',
        'knowledgeMode' => true,
    ]);
}

function schematics_create(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        schematics_create_inner();
    }
    redirect('/knowledge/schematics/create');
}

function schematics_create_inner(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = schematics_form_data();
        if (!$data['title']) {
            flash('error', 'O título é obrigatório.');
        } else {
            try {
                $data['file_path'] = schematics_handle_upload();
                schematic_create($data);
                flash('success', 'Esquema cadastrado.');
                redirect('/knowledge/schematics');
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
            }
        }
    }
    render('schematics/form', ['title' => 'Novo esquema', 'schematic' => null, 'schematicsBase' => '/knowledge/schematics']);
}

function schematics_edit(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        schematics_edit_inner();
    }
    redirect('/knowledge/schematics/edit' . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
}

function schematics_edit_inner(): void
{
    $schematic = schematic_find(get_int('id', 0));
    if (!$schematic) {
        flash('error', 'Esquema não encontrado.');
        redirect('/knowledge/schematics');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = schematics_form_data();
        if (!$data['title']) {
            flash('error', 'O título é obrigatório.');
        } else {
            try {
                $newFile = schematics_handle_upload();
                if ($newFile && $schematic['file_path']) {
                    $old = UPLOADS_PATH . '/' . basename($schematic['file_path']);
                    if (is_file($old)) {
                        unlink($old);
                    }
                }
                $data['file_path'] = $newFile ?: $schematic['file_path'];
                schematic_update((int) $schematic['id'], $data);
                flash('success', 'Esquema atualizado.');
                redirect('/knowledge/schematics');
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
            }
        }
    }

    render('schematics/form', ['title' => 'Editar esquema', 'schematic' => $schematic, 'schematicsBase' => '/knowledge/schematics']);
}

/** Entrega o PDF ao usuário autenticado (arquivos ficam fora da pasta pública). */
function schematics_file(): void
{
    redirect('/knowledge/schematics/file' . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
}

function schematics_file_stream(): void
{
    $schematic = schematic_find(get_int('id', 0));
    $file = $schematic && $schematic['file_path']
        ? UPLOADS_PATH . '/' . basename($schematic['file_path'])
        : null;

    if (!$file || !is_file($file)) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }

    $ext    = schematic_file_ext($file);
    $inline = schematic_file_inline_type($ext);

    // Nome de download amigável: modelo_placa.ext (ou título).
    $base = $schematic['model_code'] ?: preg_replace('/[^A-Za-z0-9]+/', '_', (string) $schematic['title']);
    if ($schematic['board_code']) {
        $base .= '_' . $schematic['board_code'];
    }
    $downloadName = trim($base, '_') . '.' . $ext;

    if ($inline) {
        // PDF, imagem e texto abrem direto no navegador.
        header('Content-Type: ' . $inline);
        header('Content-Disposition: inline; filename="' . $downloadName . '"');
    } else {
        // Boardview/ZIP etc: força download com nome limpo.
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    }
    header('Content-Length: ' . filesize($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

/** Remove apenas o arquivo anexado, mantendo o registro do catálogo. */
function schematics_delete_file(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        schematics_delete_file_inner();
    }
    redirect('/knowledge/schematics/delete-file' . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
}

function schematics_delete_file_inner(): void
{
    require_post('/knowledge/schematics');
    $id = post_int('id', 0);
    $schematic = $id ? schematic_find($id) : null;

    if ($schematic && $schematic['file_path']) {
        $file = UPLOADS_PATH . '/' . basename($schematic['file_path']);
        if (is_file($file)) {
            unlink($file);
        }
        schematic_clear_file((int) $schematic['id']);
        flash('success', 'Arquivo removido. O registro do catálogo foi mantido.');
    }
    redirect('/knowledge/schematics/view?id=' . (int) $id);
}

function schematics_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        schematics_delete_inner();
    }
    redirect('/knowledge/schematics/delete' . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
}

function schematics_delete_inner(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/knowledge/schematics');
    }
    schematic_delete(post_int('id', 0));
    flash('success', 'Esquema excluído.');
    redirect('/knowledge/schematics');
}
