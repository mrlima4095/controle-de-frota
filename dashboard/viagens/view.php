<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes da viagem';
$pageSubtitle = 'Informações completas do percurso.';
$activeMenu = 'viagens';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

function horaBr(?string $valor): string
{
    return $valor ? substr($valor, 0, 5) : '—';
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT p.*, v.placa, v.marca, v.modelo, m.nome AS motorista, m.cpf AS motorista_cpf
     FROM percurso p
     JOIN veiculo v ON v.id_vei = p.id_vei
     JOIN motorista m ON m.id_mot = p.id_mot
     WHERE p.id_perc = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$viagem = $stmt->fetch();

if (!$viagem) {
    header('Location: /dashboard/viagens/index.php');
    exit;
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Viagem atualizada com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1">Viagem #<?= $id ?></h2>
        <p class="text-muted small mb-0"><?= dataBr($viagem['data_per']) ?> · <?= h($viagem['origem']) ?> → <?= h($viagem['destino']) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>"><i class="fa-solid fa-pen"></i> Editar</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Percurso</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Data', dataBr($viagem['data_per'])],
                    ['Hora de início', horaBr($viagem['hora_ini'])],
                    ['Hora de fim', horaBr($viagem['hora_fim'])],
                    ['Origem', $viagem['origem']],
                    ['Destino', $viagem['destino']],
                    ['Quilometragem', number_format((int) $viagem['quilometragem'], 0, ',', '.') . ' km'],
                    ['Observações', $viagem['observacoes']],
                ];
                foreach ($linhas as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end"><?= h((string) ($valor ?? '')) ?: '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Veículo e motorista</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhasVeiculo = [
                    ['Placa', $viagem['placa']],
                    ['Marca', $viagem['marca']],
                    ['Modelo', $viagem['modelo']],
                    ['Motorista', $viagem['motorista']],
                    ['CPF do motorista', $viagem['motorista_cpf']],
                ];
                foreach ($linhasVeiculo as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end"><?= h((string) ($valor ?? '')) ?: '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
