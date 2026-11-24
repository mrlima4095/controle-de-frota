<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';


$exportacoes = [
    'veiculos' => [
        'titulo' => 'Veículos',
        'sql' => 'SELECT placa, marca, modelo, tipo, cor, chassi, combustivel,
                         quilometragem_inicial, quilometragemAtual, status, ativo
                  FROM veiculo ORDER BY placa ASC',
        'colunas' => ['placa', 'marca', 'modelo', 'tipo', 'cor', 'chassi', 'combustivel',
                      'quilometragem_inicial', 'quilometragemAtual', 'status', 'ativo'],
    ],
    'motoristas' => [
        'titulo' => 'Motoristas',
        'sql' => 'SELECT nome, cpf, cnh, validade_cnh, telefone, email, uf, cidade, ativo
                  FROM motorista ORDER BY nome ASC',
        'colunas' => ['nome', 'cpf', 'cnh', 'validade_cnh', 'telefone', 'email', 'uf', 'cidade', 'ativo'],
    ],
    'abastecimentos' => [
        'titulo' => 'Abastecimentos',
        'sql' => 'SELECT a.data_aba, a.hora_aba, v.placa, a.posto, a.litros, a.valor,
                         a.quilometragem, a.tipo_combustivel
                  FROM abastecimento a JOIN veiculo v ON v.id_vei = a.id_vei
                  ORDER BY a.data_aba DESC',
        'colunas' => ['data_aba', 'hora_aba', 'placa', 'posto', 'litros', 'valor',
                      'quilometragem', 'tipo_combustivel'],
    ],
    'manutencoes' => [
        'titulo' => 'Manutenções',
        'sql' => 'SELECT m.data_man, m.tipo, v.placa, m.oficina, m.servico, m.custo,
                         m.quilometragem, m.proxima_data
                  FROM manutencao m JOIN veiculo v ON v.id_vei = m.id_vei
                  ORDER BY m.data_man DESC',
        'colunas' => ['data_man', 'tipo', 'placa', 'oficina', 'servico', 'custo',
                      'quilometragem', 'proxima_data'],
    ],
    'viagens' => [
        'titulo' => 'Viagens',
        'sql' => 'SELECT p.data_per, v.placa, m.nome AS motorista, p.origem, p.destino,
                         p.quilometragem, p.hora_ini, p.hora_fim
                  FROM percurso p
                  JOIN veiculo v ON v.id_vei = p.id_vei
                  JOIN motorista m ON m.id_mot = p.id_mot
                  ORDER BY p.data_per DESC',
        'colunas' => ['data_per', 'placa', 'motorista', 'origem', 'destino',
                      'quilometragem', 'hora_ini', 'hora_fim'],
    ],
];

$chave = $_GET['entidade'] ?? 'veiculos';
if (!isset($exportacoes[$chave])) {
    http_response_code(404);
    exit('Exportação inválida.');
}

$config = $exportacoes[$chave];
$linhas = $pdo->query($config['sql'])->fetchAll();

$arquivo = 'drivehub-' . $chave . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$saida = fopen('php://output', 'w');

fwrite($saida, "\xEF\xBB\xBF");
fputcsv($saida, $config['colunas'], ';');

$colunasBooleanas = ['ativo'];

foreach ($linhas as $linha) {
    $valores = [];
    foreach ($config['colunas'] as $coluna) {
        $valor = $linha[$coluna] ?? '';
        if (in_array($coluna, $colunasBooleanas, true)) {
            $valor = $valor ? 'Sim' : 'Não';
        }
        $valores[] = (string) $valor;
    }
    fputcsv($saida, $valores, ';');
}

fclose($saida);
exit;
