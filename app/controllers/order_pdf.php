<?php
/** Controller: PDF da ordem de serviço (GET /orders/pdf?id=ID) */

use Dompdf\Dompdf;
use Dompdf\Options;

function orders_pdf(): void
{
    $order = order_find(get_int('id', 0));
    if (!$order) {
        flash('error', 'OS não encontrada.');
        redirect('/orders');
    }

    $client = $order['client_id'] ? client_find((int) $order['client_id']) : null;
    $device = $order['device_id'] ? device_find((int) $order['device_id']) : null;

    $html = render_html('orders/pdf', [
        'order'  => $order,
        'client' => $client,
        'device' => $device,
    ]);

    require_once BASE_PATH . '/vendor/autoload.php';

    $options = new Options();
    $options->setIsRemoteEnabled(false);
    $options->setDefaultFont('DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $number = str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);
    $dompdf->stream('OS-' . $number . '.pdf', ['Attachment' => false]);
    exit;
}
