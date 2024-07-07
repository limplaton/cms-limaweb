<?php
 

return [
    'create' => 'Criar Workflow',
    'workflows' => 'Workflows',
    'title' => 'Título',
    'description' => 'Descrição',
    'created' => 'Workflow criado com sucesso.',
    'updated' => 'Workflow atualizado com sucesso.',
    'deleted' => 'Workflow excluído com sucesso.',
    'when' => 'Quando',
    'then' => 'Então',
    'field_change_to' => 'Para',
    'total_executions' => 'Execuções: :total',
    'info' => 'A ferramenta de fluxos de trabalho automatiza seus processos de vendas. Os processos internos que podem ser automatizados incluem a criação de atividades, envio de e-mails, acionamento de solicitações HTTP, etc.',
    'validation' => [
        'invalid_webhook_url' => 'O URL do webhook não deve começar com "https://" ou "http://"',
    ],
    'actions' => [
        'webhook' => 'Acionar Webhook',
        'webhook_url_info' => 'Deve ser um URL completo, válido e acessível publicamente.',
    ],
    'fields' => [
        'with_header_name' => 'Com o nome do cabeçalho (opcional)',
        'with_header_value' => 'Com valor de cabeçalho (opcional)',
        'for_owner' => 'Para: Proprietário (Responsável)',
        'dates' => [
            'now' => 'Com data de vencimento: no momento',
            'in_1_day' => 'Com data de vencimento: em um dia',
            'in_2_days' => 'Com data de vencimento: em dois dias',
            'in_3_days' => 'Com data de vencimento: em três dias',
            'in_4_days' => 'Com data de vencimento: em quatro dias',
            'in_5_days' => 'Com data de vencimento: em cinco dias',
        ],
    ],
];
