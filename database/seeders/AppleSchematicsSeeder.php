<?php
/**
 * AppleSchematicsSeeder — catálogo completo de placas Apple (2010–2026):
 * MacBook Pro, MacBook Air, MacBook 12", iMac, Mac mini e Mac Pro.
 *
 * Popula a tabela `schematics` com um registro por PLACA (board 820-xxxx).
 * Modelos com mais de uma revisão de placa geram um registro por revisão.
 *
 * Uso:
 *   php database/seeders/AppleSchematicsSeeder.php
 *
 * Regras:
 *   - Idempotente (updateOrCreate por model_code + board_code + year):
 *     rodar de novo atualiza título/categoria/notas, sem duplicar.
 *   - NUNCA toca em file_path e external_link — os PDFs anexados
 *     manualmente depois são preservados.
 */

if (PHP_SAPI !== 'cli') {
    exit("Este seeder só pode ser executado via CLI.\n");
}

require dirname(__DIR__, 2) . '/app/config/config.php';
require APP_PATH . '/config/database.php';

// =====================================================================
// Blocos de medições padrão por plataforma
// =====================================================================

const MED_MAGSAFE1 = <<<TXT
- PPBUS_G3H ≈ 8,5–8,6 V (linha principal / ISL6259)
- PP3V42_G3H = 3,42 V (always-on)
- PPDCIN_G3H = tensão do carregador MagSafe (16,5 / 18,5 V)
- One-wire ADAPTER_SENSE: sem LED no MagSafe = SMC/one-wire/fusível
TXT;

const MED_MAGSAFE2 = <<<TXT
- PPBUS_G3H ≈ 8,5–8,6 V (linha principal / ISL6259)
- PP3V42_G3H = 3,42 V (always-on)
- PPDCIN_G3H = tensão do carregador MagSafe 2 (14,85 / 16,5 / 20 V)
- One-wire ADAPTER_SENSE: sem LED no MagSafe = SMC/one-wire
- Backlight: PPVOUT_S0_LCDBKLT (driver LP8550) — até ~50 V
TXT;

const MED_USBC_INTEL = <<<TXT
- PPBUS_G3H ≈ 12,8–13,1 V (linha principal)
- PP3V3_G3H ≈ 3,3 V (always-on)
- USB-C: 5 V iniciais → 20 V após negociação (CD3215/CD3217)
- PP3V3_UPC (LDO dos CD32xx) ≈ 3,3 V em todas as portas
- Sem 5 V em uma porta = CD32xx / linhas CC1-CC2 daquela porta
TXT;

const MED_APPLE_SILICON = <<<TXT
- PPBUS_AON ≈ 12,8–13,0 V (linha principal)
- PP3V3_G3H ≈ 3,3 V (always-on)
- PP1V8_AON presente em repouso
- USB-C: 5 V → 20 V após negociação (CD3217)
- Corrente de boot na fonte de bancada: ~0,05 → 0,3 → 0,5 A+ (sequência normal)
- Sem imagem/boot: testar revive/restore DFU via Apple Configurator
TXT;

const MED_IMAC_INTEL = <<<TXT
- LEDs de diagnóstico da placa: LED1 = standby OK, LED2 = power-on,
  LED3 = GPU/vídeo gerado, LED4 = painel detectado
- PP3V42_G3H = 3,42 V em repouso
- 12 V da fonte em todos os pinos marcados do conector
- PP5V_S0 / PP3V3_S0 presentes após o power-on
- Sem LED1 = fonte/standby; LED3 sem LED4 = suspeitar painel/cabo LVDS-eDP
TXT;

const MED_MINI_INTEL = <<<TXT
- 12 V da fonte interna no conector da placa
- PP3V42_G3H = 3,42 V (standby)
- Power-on pelos pads PWR_ON / conector do botão
- PP5V_S0 / PP3V3_S0 após power-on
TXT;

const MED_DESKTOP_AS = <<<TXT
- Trilhos de 12 V a partir da fonte interna
- PP3V3_G3H ≈ 3,3 V em standby
- Sem boot/imagem: revive/restore DFU via Apple Configurator
  (porta TB específica marcada, com outro Mac)
- Observar sequência de corrente/LED de status ao ligar
TXT;

const MED_MACPRO_INTEL = <<<TXT
- 12 V / 5 V da PSU nos trilhos principais
- LEDs DIAG do backplane (indicam trilhos, GPU e boot)
- Testar com configuração mínima: 1 pente de RAM, GPU básica, sem periféricos
- Reset SMC/NVRAM antes de condenar hardware
TXT;

// =====================================================================
// Montagem das observações técnicas
// =====================================================================

function notas(array $descricao, array $defeitos, string $medicoes, array $bancada = []): string
{
    $texto = implode("\n", $descricao);
    $texto .= "\n\nDefeitos comuns:\n- " . implode("\n- ", $defeitos);
    $texto .= "\n\nMedições de bancada:\n" . $medicoes;
    if ($bancada) {
        $texto .= "\n\nObservações de bancada:\n- " . implode("\n- ", $bancada);
    }
    return $texto;
}

// =====================================================================
// Catálogo
// Formato: [categoria, título, modelo, board (ou null), ano, notas]
// =====================================================================

$AIR = 'MacBook Air';
$PRO = 'MacBook Pro';
$M12 = 'MacBook (12")';

$registros = [];

// ------------------------------------------------------------------
// MACBOOK AIR
// ------------------------------------------------------------------

$registros[] = [$AIR, 'MacBook Air 11" A1370 (Late 2010)', 'A1370', '820-2796', 2010, notas(
    ['MacBook Air 11,6" — Late 2010 (MacBookAir3,1).', 'Tela: 11,6" 1366×768.', 'CPU: Intel Core 2 Duo (Penryn) 1,4/1,6 GHz — Intel.', 'GPU: NVIDIA GeForce 320M.', 'RAM soldada (2/4 GB), SSD proprietário formato blade.'],
    ['bateria esgotada (ciclos altos)', 'não liga por líquido (PP3V42_G3H em curto)', 'conector/flex do teclado', 'trackpad', 'SSD blade proprietário', 'dobradiças e flex do display'],
    MED_MAGSAFE1,
    ['Placa muito compacta — cuidado com os conectores FPC ao desmontar.', 'SSD usa formato proprietário Apple (não é mSATA padrão).']
)];

$registros[] = [$AIR, 'MacBook Air 11" A1370 (Mid 2011)', 'A1370', '820-3024', 2011, notas(
    ['MacBook Air 11,6" — Mid 2011 (MacBookAir4,1).', 'Tela: 11,6" 1366×768.', 'CPU: Intel Core i5/i7 (Sandy Bridge, 2ª geração) — Intel.', 'GPU: Intel HD 3000.', 'RAM soldada, SSD blade proprietário.'],
    ['bateria', 'não liga por líquido', 'teclado/flex', 'trackpad', 'backlight (fusível)', 'porta Thunderbolt'],
    MED_MAGSAFE1,
    ['Primeira geração do Air com Thunderbolt.', 'Verificar fusível de backlight antes de condenar a tela.']
)];

$registros[] = [$AIR, 'MacBook Air 13" A1369 (Late 2010)', 'A1369', '820-2838', 2010, notas(
    ['MacBook Air 13,3" — Late 2010 (MacBookAir3,2).', 'Tela: 13,3" 1440×900.', 'CPU: Intel Core 2 Duo (Penryn) 1,86/2,13 GHz — Intel.', 'GPU: NVIDIA GeForce 320M.', 'RAM soldada, SSD blade proprietário.'],
    ['bateria', 'não liga por líquido (PP3V42)', 'teclado/flex', 'trackpad', 'SSD blade', 'flex do display'],
    MED_MAGSAFE1,
    ['Mesma plataforma do A1370 2010 em chassi 13".']
)];

$registros[] = [$AIR, 'MacBook Air 13" A1369 (Mid 2011)', 'A1369', '820-3023', 2011, notas(
    ['MacBook Air 13,3" — Mid 2011 (MacBookAir4,2).', 'Tela: 13,3" 1440×900.', 'CPU: Intel Core i5/i7 (Sandy Bridge, 2ª geração) — Intel.', 'GPU: Intel HD 3000.', 'RAM soldada, SSD blade proprietário.'],
    ['bateria', 'líquido no teclado (curto em PP3V42/PPBUS)', 'backlight', 'trackpad', 'porta Thunderbolt'],
    MED_MAGSAFE1,
    ['Muito parecida com a 820-3024 (11") — atenção ao pedir esquema.']
)];

$registros[] = [$AIR, 'MacBook Air 11" A1465 (Mid 2012)', 'A1465', '820-3208', 2012, notas(
    ['MacBook Air 11,6" — Mid 2012 (MacBookAir5,1).', 'Tela: 11,6" 1366×768.', 'CPU: Intel Core i5/i7 (Ivy Bridge, 3ª geração) — Intel.', 'GPU: Intel HD 4000.', 'Primeiro Air com MagSafe 2 e USB 3.0.'],
    ['bateria', 'líquido (PP3V42 / one-wire)', 'teclado/flex', 'trackpad', 'SSD blade (novo conector 2012)', 'backlight'],
    MED_MAGSAFE2,
    ['Conector de SSD mudou em relação a 2010/2011 — não são intercambiáveis.']
)];

$registros[] = [$AIR, 'MacBook Air 11" A1465 (2013–2014)', 'A1465', '820-3435', 2013, notas(
    ['MacBook Air 11,6" — Mid 2013 / Early 2014 (MacBookAir6,1).', 'Tela: 11,6" 1366×768.', 'CPU: Intel Core i5/i7 (Haswell, 4ª geração) — Intel.', 'GPU: Intel HD 5000.', 'SSD PCIe proprietário (mudou de novo em 2013).'],
    ['bateria', 'líquido', 'teclado/flex', 'trackpad', 'SSD PCIe', 'Wi-Fi/BT (placa AirPort)'],
    MED_MAGSAFE2,
    ['Mesma placa 820-3435 cobre 2013 e 2014 (só muda clock).']
)];

$registros[] = [$AIR, 'MacBook Air 11" A1465 (Early 2015)', 'A1465', '820-00164', 2015, notas(
    ['MacBook Air 11,6" — Early 2015 (MacBookAir7,1).', 'Tela: 11,6" 1366×768.', 'CPU: Intel Core i5/i7 (Broadwell, 5ª geração) — Intel.', 'GPU: Intel HD 6000.', 'Última geração do Air 11".'],
    ['bateria', 'líquido', 'teclado/flex', 'trackpad', 'SSD PCIe', 'backlight'],
    MED_MAGSAFE2,
    ['Thunderbolt 2 nesta revisão.']
)];

$registros[] = [$AIR, 'MacBook Air 13" A1466 (Mid 2012)', 'A1466', '820-3209', 2012, notas(
    ['MacBook Air 13,3" — Mid 2012 (MacBookAir5,2).', 'Tela: 13,3" 1440×900.', 'CPU: Intel Core i5/i7 (Ivy Bridge, 3ª geração) — Intel.', 'GPU: Intel HD 4000.', 'MagSafe 2, USB 3.0.'],
    ['bateria', 'líquido (PP3V42/PPBUS)', 'teclado/flex', 'trackpad', 'SSD blade 2012', 'backlight'],
    MED_MAGSAFE2,
    ['Modelo de entrada muito comum em bancada até hoje.']
)];

$registros[] = [$AIR, 'MacBook Air 13" A1466 (2013–2014)', 'A1466', '820-3437', 2013, notas(
    ['MacBook Air 13,3" — Mid 2013 / Early 2014 (MacBookAir6,2).', 'Tela: 13,3" 1440×900.', 'CPU: Intel Core i5/i7 (Haswell, 4ª geração) — Intel.', 'GPU: Intel HD 5000.', 'SSD PCIe proprietário.'],
    ['bateria', 'líquido', 'teclado/flex', 'trackpad', 'SSD PCIe', 'U7090/backlight', 'placa AirPort'],
    MED_MAGSAFE2,
    ['A placa 820-3437 é uma das mais comuns da história do Air — vale ter esquema e boardview.']
)];

$registros[] = [$AIR, 'MacBook Air 13" A1466 (2015–2017)', 'A1466', '820-00165', 2015, notas(
    ['MacBook Air 13,3" — Early 2015 a 2017 (MacBookAir7,2).', 'Tela: 13,3" 1440×900.', 'CPU: Intel Core i5/i7 (Broadwell, 5ª geração) — Intel.', 'GPU: Intel HD 6000.', 'Vendido até 2018/2019 — enorme base instalada.'],
    ['bateria', 'líquido', 'teclado/flex', 'trackpad', 'SSD PCIe', 'backlight', 'dobradiças'],
    MED_MAGSAFE2,
    ['Mesma placa 820-00165 cobre 2015 e o refresh 2017.', 'Campeão de volume em assistência — manter peças em estoque.']
)];

$registros[] = [$AIR, 'MacBook Air Retina 13" A1932 (2018)', 'A1932', '820-01521', 2018, notas(
    ['MacBook Air Retina 13,3" — 2018 (MacBookAir8,1).', 'Tela: 13,3" Retina 2560×1600.', 'CPU: Intel Core i5-8210Y (Amber Lake, 8ª geração) — Intel.', 'GPU: Intel UHD 617.', 'Chip Apple T2, SSD soldado, Touch ID, USB-C.'],
    ['teclado borboleta (3ª geração)', 'líquido nos CD3217 (portas USB-C)', 'bateria', 'flex do display', 'oxidação sob o teclado', 'problemas de T2/bridgeOS (restore DFU)'],
    MED_USBC_INTEL,
    ['SSD soldado — recuperação de dados exige placa funcionando ou port DFU + T2.', 'Auto-boot ao abrir a tampa: desligar antes de trabalhar na placa.']
)];

$registros[] = [$AIR, 'MacBook Air Retina 13" A1932 (2019)', 'A1932', '820-01958', 2019, notas(
    ['MacBook Air Retina 13,3" — 2019 (MacBookAir8,2).', 'Tela: 13,3" Retina 2560×1600 com True Tone.', 'CPU: Intel Core i5-8210Y (Amber Lake, 8ª geração) — Intel.', 'GPU: Intel UHD 617.', 'Revisão da placa: 820-01958 (True Tone / ajustes menores).'],
    ['teclado borboleta', 'líquido nos CD3217', 'bateria', 'flex do display', 'T2/bridgeOS'],
    MED_USBC_INTEL,
    ['Externamente idêntico ao 2018 — diferenciar pela placa/nº de série.']
)];

$registros[] = [$AIR, 'MacBook Air Retina 13" A2179 (2020, Intel)', 'A2179', '820-01862', 2020, notas(
    ['MacBook Air Retina 13,3" — Early 2020 Intel (MacBookAir9,1).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Intel Core i3/i5/i7 (Ice Lake, 10ª geração) — Intel.', 'GPU: Intel Iris Plus.', 'Teclado Magic (tesoura) — fim do borboleta. Chip T2.'],
    ['líquido nos CD3217', 'bateria', 'flex do display', 'aquecimento (cooler não encosta no dissipador)', 'T2/bridgeOS'],
    MED_USBC_INTEL,
    ['Último Air Intel.', 'Projeto térmico ruim de fábrica — repasta ajuda pouco, é característica.']
)];

$registros[] = [$AIR, 'MacBook Air M1 A2337 (2020)', 'A2337', '820-02016', 2020, notas(
    ['MacBook Air 13,3" — Late 2020 (MacBookAir10,1).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Apple Silicon — chip Apple M1 (8 GB/16 GB RAM unificada no package).', 'Sem cooler (fanless). SSD soldado.'],
    ['tela (flex/trinca — painel frágil)', 'líquido nos CD3217', 'bateria', 'portas USB-C', 'não dá boot após líquido (rails AON)', 'NAND — recuperação exige DFU/reballing'],
    MED_APPLE_SILICON,
    ['Placa minúscula e muito integrada — maioria dos reparos é a nível de PMU/CD3217/NAND.', 'Revive/restore DFU pelo Apple Configurator resolve muitos "não liga" lógicos.']
)];

$registros[] = [$AIR, 'MacBook Air M2 A2681 (2022)', 'A2681', '820-02536', 2022, notas(
    ['MacBook Air 13,6" — 2022 (Mac14,2).', 'Tela: 13,6" Liquid Retina 2560×1664, notch.', 'CPU: Apple Silicon — chip Apple M2.', 'MagSafe 3 + 2× USB-C. Fanless. SSD soldado.'],
    ['tela (painel fino, trinca fácil)', 'líquido nos CD3217/MagSafe 3', 'bateria', 'SSD base 256 GB (chip único, mais lento)', 'não liga após líquido'],
    MED_APPLE_SILICON,
    ['MagSafe 3 tem one-wire próprio — verificar tensão no conector antes de condenar placa.']
)];

$registros[] = [$AIR, 'MacBook Air 15" M2 A2941 (2023)', 'A2941', null, 2023, notas(
    ['MacBook Air 15,3" — 2023 (Mac14,15).', 'Tela: 15,3" Liquid Retina 2880×1864.', 'CPU: Apple Silicon — chip Apple M2.', 'MagSafe 3 + 2× USB-C. Fanless.'],
    ['tela', 'líquido', 'bateria', 'alto-falantes (6 no 15")', 'portas USB-C/MagSafe'],
    MED_APPLE_SILICON,
    ['Board number não divulgado publicamente até agora — conferir a serigrafia 820-xxxxx ao lado do conector da bateria e completar este registro.', 'Esquema/boardview ainda não circulam — reparo por comparação com A2681 (M2 13").']
)];

$registros[] = [$AIR, 'MacBook Air 13" M3 A3113 (2024)', 'A3113', null, 2024, notas(
    ['MacBook Air 13,6" — 2024 (Mac15,12).', 'Tela: 13,6" Liquid Retina 2560×1664.', 'CPU: Apple Silicon — chip Apple M3.', 'MagSafe 3 + 2× USB-C. Fanless. Suporta 2 monitores externos (com tampa fechada).'],
    ['tela', 'líquido', 'bateria', 'portas USB-C/MagSafe'],
    MED_APPLE_SILICON,
    ['Board number ainda não documentado publicamente — completar com a serigrafia da placa quando passar pela bancada.']
)];

$registros[] = [$AIR, 'MacBook Air 13" M4 A3240 (2025)', 'A3240', null, 2025, notas(
    ['MacBook Air 13,6" — 2025 (modelo existe e é o Air 13" com chip M4).', 'Tela: 13,6" Liquid Retina 2560×1664.', 'CPU: Apple Silicon — chip Apple M4.', 'MagSafe 3 + 2× USB-C. Fanless. Câmera 12 MP Center Stage.'],
    ['tela', 'líquido', 'bateria', 'portas USB-C/MagSafe'],
    MED_APPLE_SILICON,
    ['Modelo recente — esquema não disponível; board number a confirmar na serigrafia.', 'Reparo essencialmente restrito a conectores, bateria, tela e DFU enquanto não houver documentação.']
)];

// ------------------------------------------------------------------
// MACBOOK PRO
// ------------------------------------------------------------------

$registros[] = [$PRO, 'MacBook Pro 13" A1278 (Mid 2010)', 'A1278', '820-2879', 2010, notas(
    ['MacBook Pro Unibody 13,3" — Mid 2010 (MacBookPro7,1).', 'Tela: 13,3" 1280×800.', 'CPU: Intel Core 2 Duo P8600/P8800 — Intel.', 'GPU: NVIDIA GeForce 320M.', 'RAM em 2 slots, HDD SATA 2,5" — upgrades fáceis.'],
    ['cabo SATA do HDD (clássico do A1278)', 'bateria', 'conector MagSafe', 'RAM/slots (não dá vídeo)', 'oxidação', 'cooler travado', 'drive óptico'],
    MED_MAGSAFE1,
    ['Kernel panic esporádico do 320M é conhecido nesta geração.', 'Sempre testar com cabo SATA novo antes de condenar HDD/SSD.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" A1278 (2011)', 'A1278', '820-2936', 2011, notas(
    ['MacBook Pro Unibody 13,3" — Early/Late 2011 (MacBookPro8,1).', 'Tela: 13,3" 1280×800.', 'CPU: Intel Core i5/i7 (Sandy Bridge, 2ª geração) — Intel.', 'GPU: Intel HD 3000 (sem GPU dedicada — não sofre do radeongate).', 'Thunderbolt 1.'],
    ['cabo SATA', 'bateria', 'MagSafe/DC-in', 'líquido no teclado', 'RAM/slots', 'oxidação'],
    MED_MAGSAFE1,
    ['Placa 820-2936 cobre Early e Late 2011 (mudam clocks).']
)];

$registros[] = [$PRO, 'MacBook Pro 13" A1278 (Mid 2012)', 'A1278', '820-3115', 2012, notas(
    ['MacBook Pro Unibody 13,3" — Mid 2012 (MacBookPro9,2).', 'Tela: 13,3" 1280×800.', 'CPU: Intel Core i5-3210M/i7 (Ivy Bridge, 3ª geração) — Intel.', 'GPU: Intel HD 4000.', 'Último MacBook com drive óptico, RAM em slot e HDD SATA — o "upgradeável".'],
    ['cabo SATA do HDD (defeito nº 1)', 'bateria', 'MagSafe/DC-in board', 'RAM/slots', 'líquido', 'cooler', 'oxidação'],
    MED_MAGSAFE1,
    ['A 820-3115 é provavelmente a placa mais comum da história em bancada BR.', 'Excelente candidata a upgrade SSD+RAM em vez de reparo caro.']
)];

$registros[] = [$PRO, 'MacBook Pro 15" A1286 (Mid 2010)', 'A1286', '820-2850', 2010, notas(
    ['MacBook Pro Unibody 15,4" — Mid 2010 (MacBookPro6,2).', 'Tela: 15,4" 1440×900 / 1680×1050.', 'CPU: Intel Core i5/i7 (Arrandale, 1ª geração) — Intel.', 'GPU: Intel HD + NVIDIA GeForce GT 330M (comutada).'],
    ['GPU panic / capacitor C9560 (defeito clássico do 2010)', 'cabo SATA', 'bateria', 'MagSafe', 'cooler', 'oxidação'],
    MED_MAGSAFE1,
    ['Kernel panic aleatório: trocar C9560 (330 µF) resolve na maioria dos casos.', 'GPU GT330M com solda fria também aparece.']
)];

$registros[] = [$PRO, 'MacBook Pro 15" A1286 (2011)', 'A1286', '820-2915', 2011, notas(
    ['MacBook Pro Unibody 15,4" — Early/Late 2011 (MacBookPro8,2).', 'Tela: 15,4" 1440×900 / 1680×1050.', 'CPU: Intel Core i7 (Sandy Bridge, 2ª geração) — Intel.', 'GPU: Intel HD 3000 + AMD Radeon HD 6490M/6750M/6770M.'],
    ['RADEONGATE — GPU AMD morre (vídeo listrado/sem boot); defeito estrutural', 'cabo SATA', 'bateria', 'MagSafe', 'oxidação', 'cooler'],
    MED_MAGSAFE1,
    ['Solução paliativa clássica: desabilitar dGPU via NVRAM/gMux e forçar iGPU.', 'Reballing/troca da GPU tem recidiva alta — avisar o cliente.']
)];

$registros[] = [$PRO, 'MacBook Pro 15" A1286 (Mid 2012)', 'A1286', '820-3330', 2012, notas(
    ['MacBook Pro Unibody 15,4" — Mid 2012 (MacBookPro9,1).', 'Tela: 15,4" 1440×900 / 1680×1050.', 'CPU: Intel Core i7 (Ivy Bridge, 3ª geração) — Intel.', 'GPU: Intel HD 4000 + NVIDIA GeForce GT 650M.'],
    ['GPU GT 650M (solda/vram — menos grave que 2011)', 'cabo SATA', 'bateria', 'MagSafe', 'cooler', 'oxidação'],
    MED_MAGSAFE1,
    ['Último 15" não-Retina, com slot de RAM e SATA.']
)];

$registros[] = [$PRO, 'MacBook Pro 17" A1297 (Mid 2010)', 'A1297', '820-2849', 2010, notas(
    ['MacBook Pro Unibody 17" — Mid 2010 (MacBookPro6,1).', 'Tela: 17" 1920×1200.', 'CPU: Intel Core i5/i7 (Arrandale, 1ª geração) — Intel.', 'GPU: Intel HD + NVIDIA GeForce GT 330M.'],
    ['GPU panic (mesma família do A1286 2010)', 'cabo SATA', 'bateria', 'MagSafe', 'flex do display (chassi grande)'],
    MED_MAGSAFE1,
    ['Peças de 17" são raras — aproveitar doadores quando aparecer.']
)];

$registros[] = [$PRO, 'MacBook Pro 17" A1297 (2011)', 'A1297', '820-2914', 2011, notas(
    ['MacBook Pro Unibody 17" — Early/Late 2011 (MacBookPro8,3).', 'Tela: 17" 1920×1200.', 'CPU: Intel Core i7 (Sandy Bridge, 2ª geração) — Intel.', 'GPU: Intel HD 3000 + AMD Radeon HD 6750M/6770M.'],
    ['RADEONGATE — mesma epidemia do A1286 2011', 'cabo SATA', 'bateria', 'MagSafe', 'oxidação'],
    MED_MAGSAFE1,
    ['Último 17" produzido pela Apple.', 'Mesmo paliativo do A1286 2011 (forçar iGPU).']
)];

$registros[] = [$PRO, 'MacBook Pro Retina 13" A1425 (Late 2012/Early 2013)', 'A1425', '820-3462', 2012, notas(
    ['MacBook Pro Retina 13,3" — Late 2012 / Early 2013 (MacBookPro10,2).', 'Tela: 13,3" Retina 2560×1600.', 'CPU: Intel Core i5/i7 (Ivy Bridge, 3ª geração) — Intel.', 'GPU: Intel HD 4000.', 'Primeiro Retina 13". RAM soldada, SSD proprietário.'],
    ['bateria (colada no topcase)', 'flex do display', 'backlight (LP8550)', 'SSD proprietário', 'teclado', 'líquido'],
    MED_MAGSAFE2,
    ['Driver de backlight LP8550 queima com frequência ao trocar tela — conferir antes de energizar.']
)];

$registros[] = [$PRO, 'MacBook Pro Retina 15" A1398 (2012/Early 2013)', 'A1398', '820-3332', 2012, notas(
    ['MacBook Pro Retina 15,4" — Mid 2012 / Early 2013 (MacBookPro10,1).', 'Tela: 15,4" Retina 2880×1800.', 'CPU: Intel Core i7 (Ivy Bridge, 3ª geração) — Intel.', 'GPU: Intel HD 4000 + NVIDIA GeForce GT 650M.', 'Primeiro Retina da história.'],
    ['GPU GT 650M', 'backlight LP8550 (clássico desta placa)', 'bateria colada', 'SSD proprietário', 'flex do display', 'ghosting da tela (painel LG)'],
    MED_MAGSAFE2,
    ['LP8550 do backlight é o defeito de bancada nº 1 da 820-3332.']
)];

$registros[] = [$PRO, 'MacBook Pro Retina 15" A1398 (Late 2013/2014, iGPU)', 'A1398', '820-3662', 2013, notas(
    ['MacBook Pro Retina 15,4" — Late 2013 / Mid 2014, versão SÓ Intel Iris (MacBookPro11,2).', 'Tela: 15,4" Retina 2880×1800.', 'CPU: Intel Core i7 (Haswell/Crystal Well, 4ª geração) — Intel.', 'GPU: Intel Iris Pro 5200 (sem GPU dedicada).', 'SSD PCIe proprietário.'],
    ['bateria', 'flex do display', 'backlight', 'SSD PCIe', 'teclado', 'líquido', 'coil whine'],
    MED_MAGSAFE2,
    ['Versão iGPU (820-3662) é bem mais confiável que a dGPU (820-3787).']
)];

$registros[] = [$PRO, 'MacBook Pro Retina 15" A1398 (Late 2013/2014, GT 750M)', 'A1398', '820-3787', 2013, notas(
    ['MacBook Pro Retina 15,4" — Late 2013 / Mid 2014, versão com GPU dedicada (MacBookPro11,3).', 'Tela: 15,4" Retina 2880×1800.', 'CPU: Intel Core i7 (Haswell, 4ª geração) — Intel.', 'GPU: Intel Iris Pro + NVIDIA GeForce GT 750M.'],
    ['GPU GT 750M (solda/vram)', 'gMux (comutação de vídeo)', 'bateria', 'backlight', 'SSD PCIe', 'flex do display'],
    MED_MAGSAFE2,
    ['Sem vídeo externo mas com interno OK (ou vice-versa): investigar gMux/dGPU.']
)];

$registros[] = [$PRO, 'MacBook Pro Retina 15" A1398 (Mid 2015, iGPU)', 'A1398', '820-00138', 2015, notas(
    ['MacBook Pro Retina 15,4" — Mid 2015, versão Intel Iris Pro (MacBookPro11,4).', 'Tela: 15,4" Retina 2880×1800.', 'CPU: Intel Core i7-4770HQ/4870HQ (Haswell, 4ª geração) — Intel.', 'GPU: Intel Iris Pro 5200.', 'Force Touch trackpad.'],
    ['staingate (coating da tela descascando)', 'bateria', 'flex do display', 'backlight', 'SSD PCIe', 'teclado'],
    MED_MAGSAFE2,
    ['Modelo querido do mercado usado — vale reparo quase sempre.']
)];

$registros[] = [$PRO, 'MacBook Pro Retina 15" A1398 (Mid 2015, R9 M370X)', 'A1398', '820-00163', 2015, notas(
    ['MacBook Pro Retina 15,4" — Mid 2015, versão com GPU dedicada (MacBookPro11,5).', 'Tela: 15,4" Retina 2880×1800.', 'CPU: Intel Core i7 (Haswell, 4ª geração) — Intel.', 'GPU: Intel Iris Pro + AMD Radeon R9 M370X.'],
    ['GPU AMD (menos epidêmico que 2011, mas ocorre)', 'gMux', 'staingate', 'bateria', 'backlight', 'SSD PCIe'],
    MED_MAGSAFE2,
    ['Última geração com MagSafe 2 / USB-A / HDMI nativo — muito procurada.']
)];

$registros[] = [$PRO, 'MacBook Pro Retina 13" A1502 (Late 2013/2014)', 'A1502', '820-3476', 2013, notas(
    ['MacBook Pro Retina 13,3" — Late 2013 / Mid 2014 (MacBookPro11,1).', 'Tela: 13,3" Retina 2560×1600.', 'CPU: Intel Core i5/i7 (Haswell, 4ª geração) — Intel.', 'GPU: Intel Iris 5100.', 'SSD PCIe proprietário Apple.'],
    ['bateria', 'flex do display', 'teclado', 'trackpad', 'SSD', 'backlight', 'oxidação', 'líquido no conector I/O'],
    MED_MAGSAFE2,
    ['Extremamente comum em assistência técnica.', 'Não confundir com a revisão 2015 (820-4924) — SSD e flex diferentes.']
)];

$registros[] = [$PRO, 'MacBook Pro Retina 13" A1502 (Early 2015)', 'A1502', '820-4924', 2015, notas(
    ['MacBook Pro Retina 13,3" — Early 2015 (MacBookPro12,1).', 'Tela: 13,3" Retina 2560×1600.', 'CPU: Intel Core i5/i7 (Broadwell, 5ª geração) — Intel.', 'GPU: Intel Iris 6100.', 'Force Touch trackpad. SSD PCIe (4 lanes).'],
    ['bateria', 'flex do display', 'teclado', 'trackpad (Force Touch — peça diferente de 2013/14)', 'SSD', 'backlight', 'oxidação'],
    MED_MAGSAFE2,
    ['Modelo extremamente comum em assistência técnica — o "carro-chefe" da bancada.', 'Trackpad e bateria NÃO são intercambiáveis com 2013/2014.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" Touch Bar A1706 (2016)', 'A1706', '820-00239', 2016, notas(
    ['MacBook Pro 13,3" Touch Bar — Late 2016 (MacBookPro13,2).', 'Tela: 13,3" Retina 2560×1600.', 'CPU: Intel Core i5/i7 (Skylake, 6ª geração) — Intel.', 'GPU: Intel Iris 550.', '4× USB-C/TB3. SSD soldado. Touch Bar + Touch ID.'],
    ['FLEXGATE (flex do backlight rompe — tela apaga ao abrir)', 'teclado borboleta', 'CD3215 (não liga / não carrega por uma porta)', 'bateria', 'Touch Bar', 'líquido', 'auto-boot dificulta bancada'],
    MED_USBC_INTEL,
    ['Flexgate: sintoma "palco de teatro" na parte de baixo da tela antes de apagar de vez.', 'SSD soldado — dados dependem da placa viva.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" Touch Bar A1706 (2017)', 'A1706', '820-00923', 2017, notas(
    ['MacBook Pro 13,3" Touch Bar — Mid 2017 (MacBookPro14,2).', 'Tela: 13,3" Retina 2560×1600.', 'CPU: Intel Core i5/i7 (Kaby Lake, 7ª geração) — Intel.', 'GPU: Intel Iris Plus 650.', '4× USB-C/TB3. SSD soldado.'],
    ['flexgate', 'teclado borboleta', 'CD3215', 'bateria', 'Touch Bar', 'líquido'],
    MED_USBC_INTEL,
    ['Mesmos vícios do 2016 com CPU nova.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" A1708 (2016, sem Touch Bar)', 'A1708', '820-00875', 2016, notas(
    ['MacBook Pro 13,3" função Fn — Late 2016 (MacBookPro13,1).', 'Tela: 13,3" Retina 2560×1600.', 'CPU: Intel Core i5-6360U/i7 (Skylake, 6ª geração) — Intel.', 'GPU: Intel Iris 540.', '2× USB-C/TB3. ÚNICO desta era com SSD REMOVÍVEL (blade proprietário).'],
    ['flexgate', 'teclado borboleta', 'CD3215', 'bateria', 'líquido', 'SSD blade (conector)'],
    MED_USBC_INTEL,
    ['SSD removível = recuperação de dados fácil mesmo com placa morta (adaptador próprio).', 'Placa deste modelo é a mais barata da era Touch Bar para doadora de CD3215.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" A1708 (2017, sem Touch Bar)', 'A1708', '820-00840', 2017, notas(
    ['MacBook Pro 13,3" função Fn — Mid 2017 (MacBookPro14,1).', 'Tela: 13,3" Retina 2560×1600.', 'CPU: Intel Core i5-7360U/i7 (Kaby Lake, 7ª geração) — Intel.', 'GPU: Intel Iris Plus 640.', '2× USB-C/TB3. SSD removível (blade).'],
    ['flexgate', 'teclado borboleta', 'CD3215', 'bateria', 'líquido'],
    MED_USBC_INTEL,
    ['Mesma vantagem do 2016: SSD removível.']
)];

$registros[] = [$PRO, 'MacBook Pro 15" Touch Bar A1707 (2016)', 'A1707', '820-00281', 2016, notas(
    ['MacBook Pro 15,4" Touch Bar — Late 2016 (MacBookPro13,3).', 'Tela: 15,4" Retina 2880×1800.', 'CPU: Intel Core i7 (Skylake, 6ª geração) — Intel.', 'GPU: Intel HD 530 + AMD Radeon Pro 450/455/460.', '4× USB-C/TB3. SSD soldado.'],
    ['flexgate', 'teclado borboleta', 'CD3215', 'GPU AMD (falhas de vídeo)', 'bateria', 'Touch Bar', 'líquido'],
    MED_USBC_INTEL,
    ['Vídeo listrado: diferenciar dGPU de flex/painel antes de orçar.']
)];

$registros[] = [$PRO, 'MacBook Pro 15" Touch Bar A1707 (2017)', 'A1707', '820-00928', 2017, notas(
    ['MacBook Pro 15,4" Touch Bar — Mid 2017 (MacBookPro14,3).', 'Tela: 15,4" Retina 2880×1800.', 'CPU: Intel Core i7 (Kaby Lake, 7ª geração) — Intel.', 'GPU: Intel HD 630 + AMD Radeon Pro 555/560.', '4× USB-C/TB3. SSD soldado.'],
    ['flexgate', 'teclado borboleta', 'CD3215', 'GPU AMD', 'bateria', 'Touch Bar'],
    MED_USBC_INTEL,
    ['Mesma plataforma do 2016 revisada.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" Touch Bar A1989 (2018)', 'A1989', '820-00850', 2018, notas(
    ['MacBook Pro 13,3" Touch Bar — 2018 (MacBookPro15,2).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Intel Core i5/i7 quad-core (Coffee Lake, 8ª geração) — Intel.', 'GPU: Intel Iris Plus 655.', 'Chip Apple T2. SSD soldado.'],
    ['teclado borboleta 3ª geração', 'T2/bridgeOS (panics, DFU restore)', 'CD3217', 'bateria', 'flex do display (flexgate atenuado — flex mais longo)', 'líquido'],
    MED_USBC_INTEL,
    ['Com T2, recuperação de dados exige a própria placa viva (NAND pareada).', 'Kernel panic aleatório em repouso é assinatura de T2/NAND nesta geração.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" Touch Bar A1989 (2019)', 'A1989', '820-01598', 2019, notas(
    ['MacBook Pro 13,3" Touch Bar — 2019 (MacBookPro15,4).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Intel Core i5-8279U/i7 (Coffee Lake, 8ª geração) — Intel.', 'GPU: Intel Iris Plus 655.', 'Chip T2. SSD soldado.'],
    ['teclado borboleta', 'T2/bridgeOS', 'CD3217', 'bateria', 'líquido'],
    MED_USBC_INTEL,
    ['Revisão 2019 da mesma família A1989.']
)];

$registros[] = [$PRO, 'MacBook Pro 15" Touch Bar A1990 (2018)', 'A1990', '820-01041', 2018, notas(
    ['MacBook Pro 15,4" Touch Bar — 2018 (MacBookPro15,1).', 'Tela: 15,4" Retina 2880×1800 True Tone.', 'CPU: Intel Core i7/i9 6-core (Coffee Lake, 8ª geração) — Intel.', 'GPU: Intel UHD 630 + AMD Radeon Pro 555X/560X (ou Vega 16/20).', 'Chip T2. SSD soldado.'],
    ['teclado borboleta', 'T2/bridgeOS', 'CD3217', 'GPU AMD', 'bateria', 'throttling do i9 (térmica)', 'líquido'],
    MED_USBC_INTEL,
    ['Versões Vega usam placa com sufixo diferente — conferir serigrafia.']
)];

$registros[] = [$PRO, 'MacBook Pro 15" Touch Bar A1990 (2019)', 'A1990', '820-01814', 2019, notas(
    ['MacBook Pro 15,4" Touch Bar — 2019 (MacBookPro15,3).', 'Tela: 15,4" Retina 2880×1800 True Tone.', 'CPU: Intel Core i7/i9 (Coffee Lake Refresh, 9ª geração) — Intel.', 'GPU: Intel UHD 630 + AMD Radeon Pro 555X/560X/Vega.', 'Chip T2. SSD soldado.'],
    ['teclado borboleta', 'T2/bridgeOS', 'CD3217', 'GPU AMD', 'bateria'],
    MED_USBC_INTEL,
    ['Board 820-01814 — CONFIRMAR na serigrafia da placa (revisões 2018/2019 se parecem muito).']
)];

$registros[] = [$PRO, 'MacBook Pro 16" A2141 (2019)', 'A2141', '820-01700', 2019, notas(
    ['MacBook Pro 16" — Late 2019 (MacBookPro16,1).', 'Tela: 16" Retina 3072×1920 True Tone.', 'CPU: Intel Core i7/i9 (9ª geração) — Intel.', 'GPU: Intel UHD 630 + AMD Radeon Pro 5300M/5500M (8 GB opcional).', 'Chip T2. Teclado Magic (tesoura) — fim do borboleta. SSD soldado.'],
    ['alto-falantes estalando (woofer)', 'GPU AMD 5500M (vram HBM — telas rosas/listras)', 'T2/bridgeOS', 'CD3217', 'bateria (100 Wh)', 'boot loop', 'líquido'],
    MED_USBC_INTEL,
    ['Último 15/16" Intel.', 'Tela rosa/listras intermitentes = VRAM da 5500M na maioria dos casos.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" Touch Bar A2159 (2019, 2 portas)', 'A2159', '820-01987', 2019, notas(
    ['MacBook Pro 13,3" Touch Bar de entrada — 2019, 2× TB3 (MacBookPro15,4).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Intel Core i5-8257U/i7 (Coffee Lake, 8ª geração) — Intel.', 'GPU: Intel Iris Plus 645.', 'Chip T2. SSD soldado. Substituiu o A1708 como entrada.'],
    ['teclado borboleta', 'T2/bridgeOS', 'CD3217', 'bateria', 'líquido', 'flex do display'],
    MED_USBC_INTEL,
    ['Duas portas apenas do lado esquerdo — CD3217 duplo, não quádruplo.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" A2251 (2020, 4 portas)', 'A2251', '820-01949', 2020, notas(
    ['MacBook Pro 13,3" Touch Bar — 2020, 4× TB3 (MacBookPro16,2).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Intel Core i5/i7 (Ice Lake, 10ª geração) — Intel.', 'GPU: Intel Iris Plus G7.', 'Chip T2. Teclado Magic. SSD soldado.'],
    ['T2/bridgeOS', 'CD3217', 'bateria', 'líquido', 'flex do display'],
    MED_USBC_INTEL,
    ['Último 13" Intel topo de linha.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" A2289 (2020, 2 portas)', 'A2289', null, 2020, notas(
    ['MacBook Pro 13,3" Touch Bar de entrada — 2020, 2× TB3 (MacBookPro16,3).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Intel Core i5-8257U/i7 (Coffee Lake, 8ª geração) — Intel.', 'GPU: Intel Iris Plus 645.', 'Chip T2. Teclado Magic. SSD soldado.'],
    ['T2/bridgeOS', 'CD3217', 'bateria', 'líquido', 'teclado'],
    MED_USBC_INTEL,
    ['Board number pouco documentado publicamente — CONFERIR serigrafia 820-xxxxx na placa e completar este registro.', 'Plataforma quase idêntica ao A2159 (2019) — esquema do A2159 ajuda por comparação.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" M1 A2338 (2020)', 'A2338', '820-02020', 2020, notas(
    ['MacBook Pro 13,3" Touch Bar — Late 2020 (MacBookPro17,1).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Apple Silicon — chip Apple M1.', '2× USB-C/TB. Com cooler (diferente do Air M1). SSD soldado.'],
    ['portas USB-C (CD3217)', 'tela/flex', 'bateria', 'líquido (rails AON)', 'NAND (recuperação exige DFU)', 'Touch Bar'],
    MED_APPLE_SILICON,
    ['Esquema/boardview da 820-02020 circulam — placa bem documentada para a era Apple Silicon.', 'Muitos "não liga" resolvem com revive DFU antes de abrir a placa.']
)];

$registros[] = [$PRO, 'MacBook Pro 13" M2 A2338 (2022)', 'A2338', null, 2022, notas(
    ['MacBook Pro 13,3" Touch Bar — 2022, mesmo A-number do M1 (Mac14,7).', 'Tela: 13,3" Retina 2560×1600 True Tone.', 'CPU: Apple Silicon — chip Apple M2.', '2× USB-C/TB. Último modelo com Touch Bar.'],
    ['portas USB-C', 'tela/flex', 'bateria', 'líquido', 'SSD base 256 GB (chip único)'],
    MED_APPLE_SILICON,
    ['ATENÇÃO: A2338 existe em M1 (2020) e M2 (2022) — confirmar chip antes de pedir peça/esquema.', 'Board da versão M2 não documentada publicamente — completar com a serigrafia quando passar pela bancada.']
)];

$registros[] = [$PRO, 'MacBook Pro 14" M1 Pro/Max A2442 (2021)', 'A2442', '820-02098', 2021, notas(
    ['MacBook Pro 14,2" — Late 2021 (MacBookPro18,3/18,4).', 'Tela: 14,2" Liquid Retina XDR mini-LED 3024×1964, ProMotion 120 Hz, notch.', 'CPU: Apple Silicon — chip Apple M1 Pro ou M1 Max.', 'MagSafe 3 + 3× TB4 + HDMI + SD. SSD soldado (módulos NAND).'],
    ['tela mini-LED (custo alto — cuidado no orçamento)', 'flex do display', 'líquido nos CD3217/MagSafe', 'alto-falantes', 'bateria', 'portas HDMI/SD'],
    MED_APPLE_SILICON,
    ['Board 820-02098 — confirmar na serigrafia (variantes Pro/Max mudam o sufixo).', 'Painel XDR é a peça mais cara do equipamento — proteger sempre na bancada.']
)];

$registros[] = [$PRO, 'MacBook Pro 16" M1 Pro/Max A2485 (2021)', 'A2485', '820-02100', 2021, notas(
    ['MacBook Pro 16,2" — Late 2021 (MacBookPro18,1/18,2).', 'Tela: 16,2" Liquid Retina XDR mini-LED 3456×2234, ProMotion.', 'CPU: Apple Silicon — chip Apple M1 Pro ou M1 Max.', 'MagSafe 3 + 3× TB4 + HDMI + SD. Bateria 100 Wh.'],
    ['tela mini-LED', 'flex do display', 'líquido', 'alto-falantes', 'bateria', 'MagSafe 3'],
    MED_APPLE_SILICON,
    ['Board 820-02100 — confirmar na serigrafia (variantes Pro/Max).']
)];

$registros[] = [$PRO, 'MacBook Pro 14" M2 Pro/Max A2779 (2023)', 'A2779', null, 2023, notas(
    ['MacBook Pro 14,2" — Early 2023 (Mac14,5/14,9).', 'Tela: 14,2" Liquid Retina XDR mini-LED, ProMotion.', 'CPU: Apple Silicon — chip Apple M2 Pro ou M2 Max.', 'MagSafe 3 + 3× TB4 + HDMI 2.1 + SD.'],
    ['tela mini-LED', 'flex', 'líquido', 'bateria', 'portas'],
    MED_APPLE_SILICON,
    ['Esquema não disponível publicamente; board number a confirmar na serigrafia.', 'Reparo prático: conectores, bateria, tela, DFU.']
)];

$registros[] = [$PRO, 'MacBook Pro 16" M2 Pro/Max A2780 (2023)', 'A2780', null, 2023, notas(
    ['MacBook Pro 16,2" — Early 2023 (Mac14,6/14,10).', 'Tela: 16,2" Liquid Retina XDR mini-LED, ProMotion.', 'CPU: Apple Silicon — chip Apple M2 Pro ou M2 Max.', 'MagSafe 3 + 3× TB4 + HDMI 2.1 + SD.'],
    ['tela mini-LED', 'flex', 'líquido', 'bateria', 'alto-falantes'],
    MED_APPLE_SILICON,
    ['Esquema não disponível publicamente; board number a confirmar na serigrafia.']
)];

$registros[] = [$PRO, 'MacBook Pro 14" M3 A2918 (Late 2023)', 'A2918', null, 2023, notas(
    ['MacBook Pro 14,2" — Late 2023, versão base (Mac15,3).', 'Tela: 14,2" Liquid Retina XDR mini-LED, ProMotion.', 'CPU: Apple Silicon — chip Apple M3 (base).', 'MagSafe 3 + 2× TB/USB4 + HDMI + SD. Substituiu o 13" Touch Bar.', 'Cor Space Black introduzida na linha.'],
    ['tela mini-LED', 'flex', 'líquido', 'bateria'],
    MED_APPLE_SILICON,
    ['Esquema não disponível publicamente; board number a confirmar na serigrafia.']
)];

$registros[] = [$PRO, 'MacBook Pro 14" M3 Pro/Max A2992 (Late 2023)', 'A2992', null, 2023, notas(
    ['MacBook Pro 14,2" — Late 2023, M3 Pro/Max (Mac15,6/15,8/15,10).', 'Tela: 14,2" Liquid Retina XDR mini-LED, ProMotion.', 'CPU: Apple Silicon — chip Apple M3 Pro ou M3 Max.', 'MagSafe 3 + 3× TB4 + HDMI + SD.'],
    ['tela mini-LED', 'flex', 'líquido', 'bateria', 'portas'],
    MED_APPLE_SILICON,
    ['Esquema não disponível publicamente; board number a confirmar na serigrafia.']
)];

$registros[] = [$PRO, 'MacBook Pro 14" M4 A3112 (Late 2024)', 'A3112', null, 2024, notas(
    ['MacBook Pro 14,2" — Late 2024, versão base (Mac16,1).', 'Tela: 14,2" Liquid Retina XDR mini-LED, ProMotion, opção nano-texture.', 'CPU: Apple Silicon — chip Apple M4.', 'MagSafe 3 + 3× TB4 + HDMI + SD. Câmera 12 MP Center Stage.'],
    ['tela mini-LED', 'flex', 'líquido', 'bateria'],
    MED_APPLE_SILICON,
    ['Modelo recente — sem esquema público; board number a confirmar na serigrafia.']
)];

$registros[] = [$PRO, 'MacBook Pro 16" M4 Pro/Max A3185 (Late 2024)', 'A3185', null, 2024, notas(
    ['MacBook Pro 16,2" — Late 2024, M4 Pro/Max (Mac16,5/16,7).', 'Tela: 16,2" Liquid Retina XDR mini-LED, ProMotion.', 'CPU: Apple Silicon — chip Apple M4 Pro ou M4 Max.', 'MagSafe 3 + 3× TB5 + HDMI + SD.'],
    ['tela mini-LED', 'flex', 'líquido', 'bateria', 'alto-falantes'],
    MED_APPLE_SILICON,
    ['Modelo recente — variante exata (Pro/Max) e board a confirmar na bancada.']
)];

$registros[] = [$PRO, 'MacBook Pro 14" M4 Pro/Max A3401 (Late 2024)', 'A3401', null, 2024, notas(
    ['MacBook Pro 14,2" — Late 2024, M4 Pro/Max (Mac16,6/16,8).', 'Tela: 14,2" Liquid Retina XDR mini-LED, ProMotion.', 'CPU: Apple Silicon — chip Apple M4 Pro ou M4 Max.', 'MagSafe 3 + 3× TB5 + HDMI + SD.'],
    ['tela mini-LED', 'flex', 'líquido', 'bateria'],
    MED_APPLE_SILICON,
    ['Modelo recente — A-number/variante a CONFIRMAR (a família M4 usa A3112/A3185/A3401/A3403 conforme tamanho e chip).', 'Sem esquema público; board na serigrafia.']
)];

$registros[] = [$PRO, 'MacBook Pro 16" M4/M5 A3403 (2024–2026)', 'A3403', null, 2025, notas(
    ['MacBook Pro 16,2" — geração M4/M5 (2024–2026), variante a confirmar.', 'Tela: 16,2" Liquid Retina XDR mini-LED, ProMotion.', 'CPU: Apple Silicon — chip Apple M4/M5 (confirmar na etiqueta ou em Sobre este Mac).', 'MagSafe 3 + TB5 + HDMI + SD.'],
    ['tela mini-LED', 'flex', 'líquido', 'bateria'],
    MED_APPLE_SILICON,
    ['Registro provisório: confirmar chip, ano exato e board number quando o equipamento passar pela bancada.', 'Sem esquema público.']
)];

// ------------------------------------------------------------------
// MACBOOK 12"
// ------------------------------------------------------------------

$registros[] = [$M12, 'MacBook Retina 12" A1534 (2015)', 'A1534', '820-00045', 2015, notas(
    ['MacBook Retina 12" — Early 2015 (MacBook8,1).', 'Tela: 12" Retina 2304×1440.', 'CPU: Intel Core M 5Y31/5Y51/5Y71 (Broadwell) — Intel.', 'GPU: Intel HD 5300.', 'Fanless. UMA porta USB-C (tudo passa por ela). Teclado borboleta 1ª geração.'],
    ['porta USB-C única (CD3215 — não carrega = morto)', 'teclado borboleta 1ª geração (o pior)', 'bateria', 'flex do display', 'cabo-flex da placa', 'líquido'],
    MED_USBC_INTEL,
    ['Placa minúscula, tudo soldado — reparos quase sempre a nível de componente.', 'Sem a única porta USB-C funcionando não há carga nem dados: prioridade CD3215.']
)];

$registros[] = [$M12, 'MacBook Retina 12" A1534 (2016)', 'A1534', '820-00244', 2016, notas(
    ['MacBook Retina 12" — Early 2016 (MacBook9,1).', 'Tela: 12" Retina 2304×1440.', 'CPU: Intel Core m3/m5/m7 (Skylake, 6ª geração) — Intel.', 'GPU: Intel HD 515.', 'Fanless, 1× USB-C, teclado borboleta 2ª geração.'],
    ['porta USB-C/CD3215', 'teclado borboleta', 'bateria', 'flex do display', 'líquido'],
    MED_USBC_INTEL,
    ['Melhorias discretas sobre 2015 — mesmos pontos fracos.']
)];

$registros[] = [$M12, 'MacBook Retina 12" A1534 (2017)', 'A1534', '820-00687', 2017, notas(
    ['MacBook Retina 12" — Mid 2017 (MacBook10,1).', 'Tela: 12" Retina 2304×1440.', 'CPU: Intel Core m3/i5/i7-Y (Kaby Lake, 7ª geração) — Intel.', 'GPU: Intel HD 615.', 'Fanless, 1× USB-C, teclado borboleta 2ª geração revisada.'],
    ['porta USB-C/CD3215', 'teclado borboleta', 'bateria', 'flex do display', 'líquido'],
    MED_USBC_INTEL,
    ['Última revisão do 12" — descontinuado em 2019.']
)];

// ------------------------------------------------------------------
// IMAC
// ------------------------------------------------------------------

$IMAC = 'iMac';
$MINI = 'Mac mini';
$MPRO = 'Mac Pro';

$registros[] = [$IMAC, 'iMac 21,5" A1311 (Mid 2010)', 'A1311', '820-2784', 2010, notas(
    ['iMac 21,5" — Mid 2010 (iMac11,2).', 'Tela: 21,5" 1920×1080 LED (vidro separado do painel).', 'CPU: Intel Core i3/i5 (Clarkdale, 1ª geração) — Intel.', 'GPU: ATI Radeon HD 4670/5670 (MXM).', 'RAM SO-DIMM acessível por baixo. HDD 3,5" SATA.'],
    ['GPU MXM (superaquecimento/artefatos)', 'fonte (PSU interna)', 'HDD (sensor térmico proprietário — fan a 100% com HDD de terceiros)', 'poeira entre vidro e painel', 'capacitores da PSU'],
    MED_IMAC_INTEL,
    ['Board 820-2784 — confirmar na serigrafia.', 'Trocar HDD exige emular o sensor térmico (cabo/софт) para o cooler não disparar.']
)];

$registros[] = [$IMAC, 'iMac 21,5" A1311 (Mid/Late 2011)', 'A1311', '820-2641', 2011, notas(
    ['iMac 21,5" — Mid/Late 2011 (iMac12,1).', 'Tela: 21,5" 1920×1080 LED.', 'CPU: Intel Core i3/i5/i7 (Sandy Bridge, 2ª geração) — Intel.', 'GPU: AMD Radeon HD 6750M/6770M (MXM).', 'Thunderbolt 1.'],
    ['GPU AMD (mesma família do radeongate — artefatos/sem vídeo)', 'PSU', 'HDD/sensor térmico', 'backlight', 'poeira'],
    MED_IMAC_INTEL,
    ['Board 820-2641 — confirmar na serigrafia.', 'GPU MXM pode ser substituída por versão compatível mais robusta.']
)];

$registros[] = [$IMAC, 'iMac 27" A1312 (Mid 2010)', 'A1312', '820-2901', 2010, notas(
    ['iMac 27" — Mid 2010 (iMac11,3).', 'Tela: 27" 2560×1440.', 'CPU: Intel Core i3/i5/i7 (1ª geração) — Intel.', 'GPU: ATI Radeon HD 5670/5750 (MXM).'],
    ['GPU MXM', 'PSU', 'HDD/sensor térmico', 'backlight (metade da tela escura)', 'poeira'],
    MED_IMAC_INTEL,
    ['Board 820-2901 — confirmar na serigrafia.']
)];

$registros[] = [$IMAC, 'iMac 27" A1312 (Mid 2011)', 'A1312', '820-2828', 2011, notas(
    ['iMac 27" — Mid 2011 (iMac12,2).', 'Tela: 27" 2560×1440.', 'CPU: Intel Core i5/i7 (Sandy Bridge, 2ª geração) — Intel.', 'GPU: AMD Radeon HD 6770M/6970M (MXM).', 'Thunderbolt 1.'],
    ['GPU 6970M (falha epidêmica — tela listrada/riscada, sem boot)', 'PSU', 'HDD/sensor térmico', 'backlight', 'poeira'],
    MED_IMAC_INTEL,
    ['A 6970M deste modelo é praticamente consumível — considerar MXM alternativa (K1100M/K2100M com firmware).']
)];

$registros[] = [$IMAC, 'iMac Slim 21,5" A1418 (Late 2012)', 'A1418', '820-3302', 2012, notas(
    ['iMac Slim 21,5" — Late 2012 (iMac13,1).', 'Tela: 21,5" 1920×1080 IPS LAMINADA (painel colado com adesivo).', 'CPU: Intel Core i5/i7 (Ivy Bridge, 3ª geração) — Intel.', 'GPU: Intel HD 4000 + NVIDIA GeForce GT 640M/650M (soldada).', 'RAM soldada ou acessível dependendo da config. HDD 2,5".'],
    ['abertura exige corte do adesivo (fita nova na remontagem)', 'GPU NVIDIA soldada (solda fria)', 'HDD lento (trocar por SSD ao abrir)', 'PSU', 'Wi-Fi/BT'],
    MED_IMAC_INTEL,
    ['Primeira geração slim — NUNCA reaproveitar o adesivo antigo do painel.', 'Board 820-3302 muito documentada (esquema circula).']
)];

$registros[] = [$IMAC, 'iMac Slim 21,5" A1418 (Late 2013)', 'A1418', '820-3588', 2013, notas(
    ['iMac Slim 21,5" — Late 2013 (iMac14,1/14,3).', 'Tela: 21,5" 1920×1080 IPS laminada.', 'CPU: Intel Core i5/i7 (Haswell, 4ª geração) — Intel.', 'GPU: Intel Iris Pro ou NVIDIA GT 750M.'],
    ['GPU 750M (solda)', 'HDD lento', 'PSU', 'adesivo do painel'],
    MED_IMAC_INTEL,
    ['Board 820-3588 — esquema disponível na comunidade.']
)];

$registros[] = [$IMAC, 'iMac Slim 21,5" A1418 (Mid 2014, 1.4 GHz)', 'A1418', '820-4668', 2014, notas(
    ['iMac Slim 21,5" de entrada — Mid 2014 (iMac14,4).', 'Tela: 21,5" 1920×1080 IPS laminada.', 'CPU: Intel Core i5-4260U 1,4 GHz (Haswell-U — CPU de notebook) — Intel.', 'GPU: Intel HD 5000.', 'RAM SOLDADA (primeiro iMac sem upgrade).'],
    ['desempenho fraco de fábrica (HDD 5400 + CPU U)', 'PSU', 'adesivo do painel'],
    MED_IMAC_INTEL,
    ['Modelo "budget" — o upgrade que vale é SSD.']
)];

$registros[] = [$IMAC, 'iMac Slim 21,5" A1418 (Late 2015)', 'A1418', '820-00034', 2015, notas(
    ['iMac Slim 21,5" — Late 2015, versões FHD e 4K Retina (iMac16,1/16,2).', 'Tela: 21,5" 1920×1080 ou 4096×2304 (4K).', 'CPU: Intel Core i5/i7 (Broadwell/Skylake, 5ª/6ª geração) — Intel.', 'GPU: Intel Iris Pro 6200.', 'RAM soldada.'],
    ['HDD lento (Fusion de fábrica em poucos)', 'PSU', 'adesivo do painel', 'painel 4K (caro)'],
    MED_IMAC_INTEL,
    ['Board 820-00034 — confirmar na serigrafia (a versão 4K pode usar variação).']
)];

$registros[] = [$IMAC, 'iMac 21,5" A1418 (2017)', 'A1418', null, 2017, notas(
    ['iMac 21,5" — 2017, versões FHD e 4K Retina (iMac18,1/18,2).', 'Tela: 21,5" FHD ou 4K.', 'CPU: Intel Core i5/i7 (Kaby Lake, 7ª geração) — Intel.', 'GPU: Intel Iris Plus 640 ou Radeon Pro 555/560.', 'RAM SO-DIMM interna (acessível desmontando).'],
    ['HDD lento', 'PSU', 'adesivo do painel'],
    MED_IMAC_INTEL,
    ['Board a confirmar na serigrafia (várias variantes FHD/4K).']
)];

$registros[] = [$IMAC, 'iMac 21,5" 4K A2116 (2019)', 'A2116', null, 2019, notas(
    ['iMac 21,5" 4K Retina — 2019 (iMac19,2).', 'Tela: 21,5" 4096×2304.', 'CPU: Intel Core i3/i5/i7 (8ª geração) — Intel.', 'GPU: Radeon Pro 555X/560X/Vega 20.', 'Último 21,5" Intel (vendido até 2021).'],
    ['HDD/Fusion lento', 'PSU', 'adesivo do painel'],
    MED_IMAC_INTEL,
    ['Board a confirmar na serigrafia.']
)];

$registros[] = [$IMAC, 'iMac 27" A1419 (Late 2012)', 'A1419', '820-3298', 2012, notas(
    ['iMac Slim 27" — Late 2012 (iMac13,2).', 'Tela: 27" 2560×1440 IPS laminada.', 'CPU: Intel Core i5/i7 (Ivy Bridge, 3ª geração) — Intel.', 'GPU: NVIDIA GTX 660M/675MX/680MX (MXM).', 'RAM SO-DIMM com portinhola atrás (fácil upgrade).'],
    ['GPU MXM NVIDIA (solda/superaquecimento)', 'HDD', 'PSU', 'adesivo do painel'],
    MED_IMAC_INTEL,
    ['Board 820-3298 bem documentada.', 'GPU é MXM — substituível, diferente do 21,5" que é soldada.']
)];

$registros[] = [$IMAC, 'iMac 27" A1419 (Late 2013)', 'A1419', '820-3481', 2013, notas(
    ['iMac Slim 27" — Late 2013 (iMac14,2).', 'Tela: 27" 2560×1440 IPS laminada.', 'CPU: Intel Core i5/i7 (Haswell, 4ª geração) — Intel.', 'GPU: NVIDIA GTX 755M/775M/780M (MXM).'],
    ['GPU 780M (falha conhecida — artefatos)', 'HDD', 'PSU', 'adesivo do painel'],
    MED_IMAC_INTEL,
    ['Board 820-3481 — esquema circula.', '780M 4 GB é a que mais falha desta série.']
)];

$registros[] = [$IMAC, 'iMac 27" 5K A1419 (Late 2014/Mid 2015)', 'A1419', '820-4674', 2014, notas(
    ['iMac 27" Retina 5K — Late 2014 / Mid 2015 (iMac15,1).', 'Tela: 27" 5120×2880 (primeiro 5K).', 'CPU: Intel Core i5/i7 (Haswell Refresh, 4ª geração) — Intel.', 'GPU: AMD Radeon R9 M290X/M295X (MXM).'],
    ['GPU M295X (esquenta MUITO — artefatos/travamentos)', 'painel 5K (linhas verticais com o tempo)', 'HDD/Fusion', 'PSU', 'adesivo'],
    MED_IMAC_INTEL,
    ['Board 820-4674 — confirmar na serigrafia.', 'M295X é o ponto fraco desta geração — manter pasta/coolers impecáveis.']
)];

$registros[] = [$IMAC, 'iMac 27" 5K A1419 (Late 2015)', 'A1419', '820-00134', 2015, notas(
    ['iMac 27" Retina 5K — Late 2015 (iMac17,1).', 'Tela: 27" 5K com gama ampla (DCI-P3).', 'CPU: Intel Core i5/i7 (Skylake, 6ª geração) — Intel.', 'GPU: AMD Radeon R9 M380/M390/M395/M395X.'],
    ['painel 5K (linhas/manchas)', 'GPU AMD', 'HDD/Fusion', 'PSU'],
    MED_IMAC_INTEL,
    ['Board 820-00134 — confirmar na serigrafia.']
)];

$registros[] = [$IMAC, 'iMac 27" 5K A1419 (2017)', 'A1419', '820-00609', 2017, notas(
    ['iMac 27" Retina 5K — 2017 (iMac18,3).', 'Tela: 27" 5K DCI-P3.', 'CPU: Intel Core i5/i7 (Kaby Lake, 7ª geração) — Intel.', 'GPU: Radeon Pro 570/575/580 (8 GB).', 'RAM SO-DIMM pela portinhola.'],
    ['painel 5K', 'GPU (menos que 2014/2015)', 'HDD/Fusion', 'PSU'],
    MED_IMAC_INTEL,
    ['Board 820-00609 — confirmar na serigrafia.', 'Geração equilibrada — vale reparo.']
)];

$registros[] = [$IMAC, 'iMac 27" 5K A2115 (2019)', 'A2115', null, 2019, notas(
    ['iMac 27" Retina 5K — 2019 (iMac19,1).', 'Tela: 27" 5K.', 'CPU: Intel Core i5/i9 (8ª/9ª geração) — Intel.', 'GPU: Radeon Pro 570X/580X/Vega 48.'],
    ['painel 5K', 'Fusion Drive lento', 'PSU'],
    MED_IMAC_INTEL,
    ['Board a confirmar na serigrafia.']
)];

$registros[] = [$IMAC, 'iMac 27" 5K A2115 (2020)', 'A2115', null, 2020, notas(
    ['iMac 27" Retina 5K — 2020, ÚLTIMO iMac Intel (iMac20,1/20,2).', 'Tela: 27" 5K, opção nano-texture.', 'CPU: Intel Core i5/i7/i9 (Comet Lake, 10ª geração) — Intel.', 'GPU: Radeon Pro 5300/5500XT/5700XT.', 'Chip T2, SSD de fábrica em todas as configs.'],
    ['painel 5K', 'T2/bridgeOS', 'PSU'],
    MED_IMAC_INTEL,
    ['Board a confirmar na serigrafia.', 'Com T2: recuperação de dados depende da placa (SSD pareado).']
)];

$registros[] = [$IMAC, 'iMac Pro 27" A1862 (2017)', 'A1862', null, 2017, notas(
    ['iMac Pro 27" — 2017 (iMacPro1,1), cinza espacial.', 'Tela: 27" 5K.', 'CPU: Intel Xeon W 8/10/14/18 núcleos — Intel.', 'GPU: Radeon Pro Vega 56/64 (HBM2).', 'Chip T2. RAM ECC (acessível só desmontando tudo).'],
    ['GPU Vega (HBM2)', 'T2/bridgeOS (DFU)', 'PSU', 'painel 5K'],
    MED_IMAC_INTEL,
    ['Board a confirmar na serigrafia.', 'Desmontagem completa para quase tudo — orçar mão de obra de acordo.']
)];

$registros[] = [$IMAC, 'iMac 24" M1 A2438 (2021, 4 portas)', 'A2438', null, 2021, notas(
    ['iMac 24" — 2021, versão 4× USB-C/2× TB (iMac21,1).', 'Tela: 23,5" 4,5K Retina 4480×2520.', 'CPU: Apple Silicon — chip Apple M1.', 'Fonte EXTERNA com Ethernet embutida no conector magnético. Touch ID no teclado.'],
    ['tela 4,5K laminada (fina e cara)', 'fonte externa/conector magnético', 'portas USB-C', 'líquido'],
    MED_DESKTOP_AS,
    ['Board a confirmar na serigrafia.', 'Sem imagem: revive DFU antes de abrir (abertura é pelo painel colado).']
)];

$registros[] = [$IMAC, 'iMac 24" M1 A2439 (2021, 2 portas)', 'A2439', null, 2021, notas(
    ['iMac 24" — 2021, versão de entrada 2× TB (iMac21,2).', 'Tela: 23,5" 4,5K Retina.', 'CPU: Apple Silicon — chip Apple M1 (GPU 7 núcleos na base).', 'Fonte externa magnética (sem Ethernet na base).'],
    ['tela 4,5K', 'fonte externa', 'portas', 'líquido'],
    MED_DESKTOP_AS,
    ['Board a confirmar na serigrafia.']
)];

$registros[] = [$IMAC, 'iMac 24" M3 A2873 (2023, 4 portas)', 'A2873', null, 2023, notas(
    ['iMac 24" — Late 2023, 4 portas (Mac15,5).', 'Tela: 23,5" 4,5K Retina.', 'CPU: Apple Silicon — chip Apple M3.', 'Mesmo chassi do M1.'],
    ['tela 4,5K', 'fonte externa', 'portas'],
    MED_DESKTOP_AS,
    ['Board a confirmar na serigrafia; esquema não público.']
)];

$registros[] = [$IMAC, 'iMac 24" M3 A2874 (2023, 2 portas)', 'A2874', null, 2023, notas(
    ['iMac 24" — Late 2023, 2 portas (Mac15,4).', 'Tela: 23,5" 4,5K Retina.', 'CPU: Apple Silicon — chip Apple M3.'],
    ['tela 4,5K', 'fonte externa', 'portas'],
    MED_DESKTOP_AS,
    ['Board a confirmar na serigrafia.']
)];

$registros[] = [$IMAC, 'iMac 24" M4 (2024)', null, null, 2024, notas(
    ['iMac 24" — Late 2024 (Mac16,2/16,3).', 'Tela: 23,5" 4,5K Retina, opção nano-texture.', 'CPU: Apple Silicon — chip Apple M4.', 'Câmera 12 MP Center Stage. Acessórios USB-C.'],
    ['tela 4,5K', 'fonte externa', 'portas'],
    MED_DESKTOP_AS,
    ['Modelo recente — A-number e board a confirmar na etiqueta/serigrafia quando passar pela bancada.']
)];

// ------------------------------------------------------------------
// MAC MINI
// ------------------------------------------------------------------

$registros[] = [$MINI, 'Mac mini A1347 (Mid 2010)', 'A1347', '820-2711', 2010, notas(
    ['Mac mini Unibody — Mid 2010 (Macmini4,1).', 'CPU: Intel Core 2 Duo — Intel.', 'GPU: NVIDIA GeForce 320M.', 'Primeiro unibody de alumínio com PSU interna. Tampa inferior giratória (RAM fácil).'],
    ['PSU interna', 'HDD/cabo SATA', 'RAM', 'aquecimento (poeira no cooler)'],
    MED_MINI_INTEL,
    ['Board 820-2711 — confirmar na serigrafia.']
)];

$registros[] = [$MINI, 'Mac mini A1347 (Mid 2011)', 'A1347', '820-2993', 2011, notas(
    ['Mac mini — Mid 2011 (Macmini5,1/5,2).', 'CPU: Intel Core i5/i7 (Sandy Bridge, 2ª geração) — Intel.', 'GPU: Intel HD 3000 ou AMD Radeon HD 6630M.', 'Thunderbolt 1. Sem drive óptico.'],
    ['GPU AMD 6630M (versão com dGPU — artefatos)', 'PSU', 'HDD/cabo SATA', 'RAM'],
    MED_MINI_INTEL,
    ['Board 820-2993.', 'Versão com Radeon sofre do mesmo mal da era 2011.']
)];

$registros[] = [$MINI, 'Mac mini A1347 (Late 2012)', 'A1347', null, 2012, notas(
    ['Mac mini — Late 2012 (Macmini6,1/6,2).', 'CPU: Intel Core i5-3210M / i7-3615QM/3720QM (Ivy Bridge, 3ª geração) — Intel.', 'GPU: Intel HD 4000.', 'O queridinho: quad-core, 2 baias possíveis, RAM até 16 GB.'],
    ['HDD/cabo SATA', 'PSU', 'aquecimento no i7 quad', 'RAM'],
    MED_MINI_INTEL,
    ['Board a confirmar na serigrafia (820-xxxx ao lado da RAM).', 'Modelo MUITO comum como servidor/HTPC — vale sempre upgrade SSD.']
)];

$registros[] = [$MINI, 'Mac mini A1347 (Late 2014)', 'A1347', '820-5509', 2014, notas(
    ['Mac mini — Late 2014 (Macmini7,1).', 'CPU: Intel Core i5/i7 (Haswell, 4ª geração, dual-core) — Intel.', 'GPU: Intel HD 5000/Iris 5100.', 'RAM SOLDADA (fim do upgrade). PCIe SSD opcional junto do SATA.'],
    ['HDD lento (Fusion raro)', 'PSU', 'RAM soldada limita revenda'],
    MED_MINI_INTEL,
    ['Board 820-5509 — confirmar na serigrafia.', 'Sem quad-core nesta geração — 2012 i7 rende mais.']
)];

$registros[] = [$MINI, 'Mac mini A1993 (2018)', 'A1993', null, 2018, notas(
    ['Mac mini — Late 2018, cinza espacial (Macmini8,1).', 'CPU: Intel Core i3/i5/i7 (Coffee Lake, 8ª geração) — Intel.', 'GPU: Intel UHD 630.', 'Chip T2, SSD soldado, RAM SO-DIMM (upgrade possível, desmontagem chata).', '4× TB3 + 2× USB-A + HDMI.'],
    ['T2/bridgeOS', 'portas TB3 (CD3217)', 'PSU', 'aquecimento no i7'],
    MED_MINI_INTEL,
    ['Board a confirmar na serigrafia.', 'SSD soldado + T2: dados dependem da placa.']
)];

$registros[] = [$MINI, 'Mac mini M1 A2348 (2020)', 'A2348', null, 2020, notas(
    ['Mac mini — Late 2020 (Macmini9,1).', 'CPU: Apple Silicon — chip Apple M1.', '2× TB + 2× USB-A + HDMI. RAM/SSD soldados.'],
    ['portas TB/USB-C', 'PSU interna', 'NAND (recuperação via DFU)', 'HDMI'],
    MED_DESKTOP_AS,
    ['Board a confirmar na serigrafia.', 'Revive/restore DFU resolve boa parte dos "não liga" lógicos.']
)];

$registros[] = [$MINI, 'Mac mini M2 A2686 (2023)', 'A2686', null, 2023, notas(
    ['Mac mini — Early 2023, versão M2 (Mac14,3).', 'CPU: Apple Silicon — chip Apple M2.', '2× TB4 + 2× USB-A + HDMI.'],
    ['portas', 'PSU', 'SSD base 256 GB (chip único, mais lento)'],
    MED_DESKTOP_AS,
    ['Board a confirmar na serigrafia.']
)];

$registros[] = [$MINI, 'Mac mini M2 Pro A2816 (2023)', 'A2816', null, 2023, notas(
    ['Mac mini — Early 2023, versão M2 Pro (Mac14,12).', 'CPU: Apple Silicon — chip Apple M2 Pro.', '4× TB4 + 2× USB-A + HDMI.'],
    ['portas', 'PSU', 'aquecimento sob carga contínua'],
    MED_DESKTOP_AS,
    ['Board a confirmar na serigrafia.']
)];

$registros[] = [$MINI, 'Mac mini M4 / M4 Pro (2024)', null, null, 2024, notas(
    ['Mac mini — Late 2024, chassi NOVO compacto ~12,7 cm (Mac16,10/16,11).', 'CPU: Apple Silicon — chip Apple M4 ou M4 Pro.', '3× TB4/TB5 + HDMI + 2× USB-C frontais. Botão de power na BASE.', 'Fonte interna.'],
    ['portas', 'aquecimento (chassi pequeno)', 'botão de power em posição infeliz'],
    MED_DESKTOP_AS,
    ['Modelo recente — A-number e board a confirmar na etiqueta/serigrafia.']
)];

// ------------------------------------------------------------------
// MAC PRO
// ------------------------------------------------------------------

$registros[] = [$MPRO, 'Mac Pro Torre A1289 (Mid 2010)', 'A1289', null, 2010, notas(
    ['Mac Pro torre de alumínio — Mid 2010 (MacPro5,1).', 'CPU: Intel Xeon Westmere 4/6 núcleos (1 ou 2 CPUs no tray) — Intel.', 'GPU: PCIe (Radeon HD 5770/5870 de fábrica).', 'Arquitetura: BACKPLANE + CPU TRAY separados; RAM ECC no tray.'],
    ['PSU', 'backplane (slots PCIe)', 'CPU tray (sensores/RAM ECC)', 'GPU de fábrica', 'bateria PRAM'],
    MED_MACPRO_INTEL,
    ['Sem board 820 tradicional: backplane e tray têm part numbers próprios — anotar da peça.', 'Aceita upgrade de CPU (X5690) e GPU Metal p/ Mojave — muito comum na bancada.']
)];

$registros[] = [$MPRO, 'Mac Pro Torre A1289 (Mid 2012)', 'A1289', null, 2012, notas(
    ['Mac Pro torre — Mid 2012, última torre clássica (MacPro5,1).', 'CPU: Intel Xeon Westmere — Intel (mesma plataforma de 2010).', 'GPU: PCIe.', 'Idêntico ao 2010 com clocks maiores.'],
    ['PSU', 'backplane', 'CPU tray', 'GPU'],
    MED_MACPRO_INTEL,
    ['Firmware 5,1: mesmo tratamento do 2010.']
)];

$registros[] = [$MPRO, 'Mac Pro "Lixeira" A1481 (Late 2013)', 'A1481', null, 2013, notas(
    ['Mac Pro cilíndrico — Late 2013 (MacPro6,1).', 'CPU: Intel Xeon E5 4–12 núcleos — Intel.', 'GPU: 2× AMD FirePro D300/D500/D700 em placas proprietárias.', 'Núcleo térmico único triangular; SSD PCIe proprietário; RAM acessível.'],
    ['GPU D500/D700 (falha EPIDÊMICA — travamentos, kernel panic, sem vídeo)', 'PSU interna', 'SSD proprietário', 'aquecimento do núcleo'],
    MED_MACPRO_INTEL,
    ['Houve programa de reparo da Apple para as GPUs (encerrado) — placas remanufaturadas também falham.', 'Sem board 820 público: GPUs e placa-mãe têm 820-xxxx próprios — anotar da peça.']
)];

$registros[] = [$MPRO, 'Mac Pro Torre A1991 (2019)', 'A1991', null, 2019, notas(
    ['Mac Pro torre "ralador" — 2019 (MacPro7,1).', 'CPU: Intel Xeon W 8–28 núcleos — Intel.', 'GPU: módulos MPX (Radeon Pro 580X a W6900X) ou PCIe padrão.', 'Chip T2. RAM ECC em 12 slots. SSD em módulos pareados ao T2.'],
    ['módulos MPX (conector)', 'T2/bridgeOS', 'PSU 1,4 kW', 'SSD pareado (troca exige Configurator)'],
    MED_MACPRO_INTEL,
    ['SSDs são pareados ao T2 — substituição exige restore via Apple Configurator.', 'Board a anotar da própria peça.']
)];

$registros[] = [$MPRO, 'Mac Pro M2 Ultra A2787 (2023)', 'A2787', null, 2023, notas(
    ['Mac Pro torre — 2023, primeiro Apple Silicon (Mac14,8).', 'CPU: Apple Silicon — chip Apple M2 Ultra.', 'Slots PCIe (sem GPUs de terceiros p/ vídeo). RAM unificada soldada.', '8× TB4 + HDMI.'],
    ['portas', 'PSU', 'placas PCIe de expansão (compatibilidade)'],
    MED_DESKTOP_AS,
    ['RAM e SSD não expansíveis por slot convencional (SSD em módulos Apple).', 'Board a confirmar na serigrafia.']
)];

// =====================================================================
// Execução — "updateOrCreate" por (model_code, board_code, year)
// =====================================================================

$pdo = db();
echo "Populando catálogo de esquemas...\n";

$buscar = $pdo->prepare(
    "SELECT id FROM schematics
     WHERE COALESCE(model_code, '') = ? AND COALESCE(board_code, '') = ? AND year = ?"
);
// Registros sem modelo E sem board (lançamentos recentes) são identificados pelo título.
$buscarPorTitulo = $pdo->prepare('SELECT id FROM schematics WHERE title = ?');
$inserir = $pdo->prepare(
    'INSERT INTO schematics (title, device_category, model_code, board_code, year, file_path, external_link, notes)
     VALUES (?, ?, ?, ?, ?, NULL, NULL, ?)'
);
// No update NÃO tocamos em file_path/external_link (PDFs anexados são preservados).
$atualizar = $pdo->prepare(
    'UPDATE schematics SET title = ?, device_category = ?, notes = ? WHERE id = ?'
);

$criados = 0;
$atualizados = 0;

$pdo->beginTransaction();
try {
    foreach ($registros as [$categoria, $titulo, $modelo, $board, $ano, $nota]) {
        if ($modelo === null && $board === null) {
            $buscarPorTitulo->execute([$titulo]);
            $id = $buscarPorTitulo->fetchColumn();
        } else {
            $buscar->execute([(string) $modelo, (string) $board, $ano]);
            $id = $buscar->fetchColumn();
        }

        if ($id) {
            $atualizar->execute([$titulo, $categoria, $nota, (int) $id]);
            $atualizados++;
        } else {
            $inserir->execute([$titulo, $categoria, $modelo, $board, $ano, $nota]);
            $criados++;
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . "\n");
    exit(1);
}

$total = count($registros);
echo "AppleSchematicsSeeder concluído.\n";
echo "  Registros no catálogo: $total\n";
echo "  Criados:     $criados\n";
echo "  Atualizados: $atualizados\n";
