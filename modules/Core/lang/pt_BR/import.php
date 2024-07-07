<?php
 

return [
    'import' => 'Importar',
    'start' => 'Começar Importação',
    'import_records' => 'Importar Registros',
    'download_sample' => 'Baixar exemplo',
    'history' => 'Histórico de importação',
    'no_history' => 'Nenhum histórico de importação encontrado.',
    'spreadsheet_columns' => 'Colunas da planilha',
    'column_will_not_import' => 'não será importado',
    'date' => 'Data',
    'file_name' => 'Nome do arquivo',
    'user' => 'Usuário',
    'total_imported' => 'Importados',
    'total_duplicates' => 'Duplicatas',
    'total_skipped' => 'Ignorados',
    'status' => 'Status',
    'imported' => 'Registros importados com sucesso',
    'why_skipped' => 'Por quê?',
    'download_skip_file' => 'Baixar arquivo de ignorados',
    'skip_file' => 'Arquivo de ignorados',
    'total_rows_skipped' => 'Total de linhas ignoradas: :count',
    'skip_file_generation_info' => 'Um arquivo de ignorados é gerado sempre que uma validação de linha de planilha falha.',
    'skip_file_fix_and_continue' => 'Baixe o arquivo de ignorados para examinar as linhas que falharam e por que falharam. Depois que as linhas forem corrigidas, faça o upload do arquivo corrigido abaixo para continuar o processo de importação para a instância de importação atual.',
    'upload_fixed_skip_file' => 'Carregar arquivo de ignorados corrigido',
    'steps' => [
        'step_1' => [
            'name' => 'Baixar exemplo',
            'description' => 'Guia de formatação da planilha.',
        ],
        'step_2' => [
            'name' => 'Carregar planilha',
            'description' => 'Faça o upload do arquivo para mapeamento.',
        ],
        'step_3' => [
            'name' => 'Mapear colunas',
            'description' => 'Mapeie as colunas com os campos.',
        ],
        'step_4' => [
            'name' => 'Importar',
            'description' => 'Inicie o processo de importação.',
        ],
    ],
    'from_file' => 'Importar do arquivo :file_type',
];
