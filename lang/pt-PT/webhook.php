<?php

return [
    'edit_webhook' => 'Editar Webbook',
    'callback_url' => 'URL de chamada de retorno',
    'method' => 'Método',
    'max_attempts' => 'Máximo de tentativas',
    'secret' => 'Segredo',
    'events' => 'Eventos',
    'webhooks' => 'Webhooks',
    'create_webhook' => 'Criar webhook',
    'deliveries' => 'Entregas de webhooks',
    'never_triggered' => 'Nunca acionado',
    'last_delivery_succeeded' => 'A última entrega foi bem sucedida.',
    'last_delivery_failed' => 'A última entrega falhou.',
    'delete_webhook' => 'Eliminar webhook',
    'delete_webhook_confirm' => 'Tem a certeza de que pretende eliminar este webhook?',
    'webhooks_desc' => 'Permitir que os serviços externos sejam notificados quando ocorrem determinados eventos.',
    'event' => [
        'post' => [
            'created' => 'Lançamento criado',
            'updated' => 'Posto atualizado',
            'deleted' => 'Lançamento eliminado',
            'scheduled' => 'Lançamento programado',
            'published' => 'Post publicado',
            'publishing_failed' => 'Falha na publicação do post'
        ],
        'account' => [
            'added' => 'Conta adicionada',
            'updated' => 'Conta actualizada',
            'deleted' => 'Conta eliminada'
        ]
    ],
    'change_secret' => 'Alterar segredo',
    'change_secret_desc' => 'Se perder ou se esquecer deste segredo, pode alterá-lo, mas lembre-se de atualizar quaisquer integrações que o utilizem.',
    'secret_updated' => 'Segredo atualizado com sucesso',
    'delete_webhooks' => 'Eliminar webhooks',
    'delete_webhooks_confirm' => 'Tem a certeza de que pretende eliminar os webhooks selecionados?',
    'resend' => 'Reenviar',
    'next_retry' => 'Próxima tentativa',
    'resend_manually' => 'Este webhook foi reenviado manualmente',
    'response' => 'Resposta',
    'http_status' => 'Código de estado HTTP',
    'payload' => 'Carga útil',
    'resent' => 'O webhook foi reenviado',
    'updated' => 'Webhook atualizado com sucesso',
    'not_found' => 'Webhook não encontrado',
    'deleted' => 'Webhook eliminado com êxito',
    'created' => 'Webhook criado com êxito',
    'delete_webhooks_failed' => 'Não foi possível eliminar os webhooks',
    'delete_webhooks_success' => 'Webhooks eliminados com êxito',
    'delivery_failed_try_redeliver' => 'A entrega deste webhook falhou. O sistema tentará voltar a entregar o evento mais tarde.',
    'delivery_failed' => 'A entrega deste webhook falhou.',
    'content_type' => 'Tipo de conteúdo'
];
