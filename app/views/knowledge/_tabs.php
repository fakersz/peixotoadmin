<?php
$activeKnowledgeSection = $activeKnowledgeSection ?? '';
$knowledgeTabs = [
    ['articles', '📚 Artigos', '/knowledge/articles'],
    ['repairs', '🔧 Reparos', '/knowledge/repairs'],
    ['models', 'Modelos', '/knowledge/models'],
    ['manufacturers', 'Fabricantes', '/knowledge/manufacturers'],
    ['schematics', '⚡ Esquemas', '/knowledge/schematics'],
    ['boards', '🔩 Placas lógicas', '/knowledge/boards'],
    ['boardviews', '📄 Boardviews', '/knowledge/boardviews'],
    ['sources', '🔎 Fontes', '/knowledge/sources'],
    ['files', '📁 Arquivos', '/knowledge/files'],
    ['photos', '📷 Fotos', '/knowledge/photos'],
    ['videos', '🎥 Vídeos', '/knowledge/videos'],
    ['pdfs', '📑 PDFs', '/knowledge/pdfs'],
    ['links', '🔗 Links', '/knowledge/links'],
    ['datasheets', '📦 Datasheets', '/knowledge/datasheets'],
    ['firmware', '💾 Firmware', '/knowledge/firmware'],
    ['favorites', '⭐ Favoritos', '/knowledge/favorites'],
    ['history', '🕘 Histórico', '/knowledge/history'],
    ['tags', '🏷 Tags', '/knowledge/tags'],
];
if (knowledge_can('model_manage')) {
    $knowledgeTabs[] = ['review_queue', '🚩 Fila de revisão', '/knowledge/review-queue'];
}
?>
<nav class="knowledge-tabs">
    <?php foreach ($knowledgeTabs as [$key, $label, $url]): ?>
        <a href="<?= e($url) ?>" class="chip<?= $activeKnowledgeSection === $key ? ' active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
